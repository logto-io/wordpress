<?php declare(strict_types=1);
namespace Logto\WpPlugin;

use Logto\Sdk\LogtoClient;
use Logto\Sdk\Constants\UserScope;

class LogtoPlugin
{

  public function __construct()
  {
  }

  protected function buildClient(): LogtoClient
  {
    $config = LogtoPluginSettings::get();
    $scopes = explode(' ', $config->scope);

    // Append organizations scope if "Require organization ID' is set
    if ($config->requireOrganizationId) {
      $scopes[] = UserScope::organizations->value;
    }

    // Normalize and deduplicate scopes
    $scopes = array_unique(array_map('trim', $scopes));

    return new LogtoClient(
      new \Logto\Sdk\LogtoConfig(
        endpoint: $config->endpoint,
        appId: $config->appId,
        appSecret: $config->appSecret,
        scopes: $scopes,
      ),
    );
  }

  function register(): void
  {
    add_action('init', function () {
      add_rewrite_rule('^' . LogtoConstants::LOGIN_CALLBACK_PATH . '/?$', 'index.php?' . LogtoConstants::LOGIN_CALLBACK_TAG . '=1', 'top');
      add_rewrite_tag('%' . LogtoConstants::LOGIN_CALLBACK_TAG . '%', '([^&]+)');
    });
    add_action('login_form', [$this, 'handleLoginForm']);
    add_action('lostpassword_form', [$this, 'handleLoginForm']);
    add_action('wp_logout', [$this, 'handleLogout']);
    add_action('template_redirect', [$this, 'handleCallback']);
    add_action('user_profile_update_errors', [$this, 'handleProfileUpdateErrors'], 10, 3);
    add_action('plugins_loaded', [$this, 'handlePluginsLoaded']);
    LogtoPluginAdminDashboard::getInstance()->register();
  }

  function handlePluginsLoaded(): void
  {
    // We intentionally don't use a constant here since all translation functions (e.g. `__()`)
    // cannot be used with a constant as the text domain as the WP CLI command `i18n make-pot`
    // will not be able to extract the strings. We have to use string literals instead; and it
    // makes a constant useless.
    // Rules of thumb: Don't be too smart with the WordPress ecosystem.
    load_plugin_textdomain('logto', false, plugin_basename(LogtoConstants::PLUGIN_DIR . 'languages/'));
  }

  function handleLoginForm(): void
  {
    $config = LogtoPluginSettings::get();

    if (!$config->isReady() || $this->shouldShowWordPressLoginForm()) {
      return;
    }

    parse_str($config->extraParams, $extraParams);

    // Overwrite first screen parameter if it's lost password form
    if (isset($_GET['action']) && $_GET['action'] === 'lostpassword') {
      $extraParams['first_screen'] = 'reset_password';
    }

    wp_redirect($this->buildClient()->signIn(
      redirectUri: $config->getRedirectUri(),
      extraParams: $extraParams,
    ));
    exit;
  }

  function handleLogout(): void
  {
    wp_redirect($this->buildClient()->signOut(home_url()));
    exit;
  }

  function handleCallback(): void
  {
    if (!get_query_var(LogtoConstants::LOGIN_CALLBACK_TAG)) {
      return;
    }

    if (isset($_GET["code"])) {
      try {
        $client = $this->buildClient();
        $client->handleSignInCallback();
        $user = $this->upsertUser($client);
        $config = LogtoPluginSettings::get();
        wp_set_auth_cookie($user->ID, $config->rememberSession);
        wp_safe_redirect(home_url());
        return;
      } catch (\Throwable $e) {
        $this->handleError(
          _x('Failed to login', 'Error title', 'logto'),
          $e->getMessage()
        );
      }
    }

    $this->handleCallbackError();
  }

  protected function shouldShowWordPressLoginForm(): bool
  {
    $config = LogtoPluginSettings::get();
    return $config->wpFormLogin === WpFormLogin::query->value && isset($_GET['form']) && $_GET['form'] === '1';
  }

  protected function handleCallbackError(): void
  {
    $error = $_GET["error"] ?? _x('Unknown error', 'Error title when error is unknown', 'logto');
    $errorDescription = $_GET["error_description"] ?? _x('Please try again later or contact the site administrator.', 'Default error description for login failure', 'logto');

    $this->handleError(
      sprintf(
        /* translators: %s is the actual error title. */
        _x('Failed to login: %s', 'Error title', 'logto'),
        $error
      ),
      $errorDescription
    );
  }

  protected function handleError(string $title, string $content): void
  {
    $body = str_starts_with($content, '<') ? $content : "<p>$content</p>";
    wp_die(
      "<h1>$title</h1><p>$body</p>" .
      "<a href='" . wp_login_url() . "'>" . _x('Login again', 'logto') . '</a><br/>' .
      "<a href='" . home_url() . "'>" . _x('Back to home', 'logto') . '</a>',
      $title,
      ['response' => 400]
    );
  }

  protected function upsertUser(LogtoClient $client): \WP_User
  {
    $config = LogtoPluginSettings::get();
    $claims = $client->getIdTokenClaims();

    if (!$claims->email) {
      $this->handleError(
        _x('Email not found', 'Error title', 'logto'),
        _x('Email is required to complete login. Please contact the site administrator.', 'Error content for email not found', 'logto')
      );
    }

    if ($config->requireVerifiedEmail && !$claims->email_verified) {
      $this->handleError(
        _x('Email not verified', 'Error title', 'logto'),
        _x('Email should be verified to complete login. Please contact the site administrator.', 'Error content for email not verified', 'logto')
      );
    }

    if (
      $config->requireOrganizationId &&
      !in_array($config->requireOrganizationId, $claims->organizations ?? [], true)
    ) {
      $this->handleError(
        _x('Unauthorized', 'Error title', 'logto'),
        _x('You must be in the specified organization to complete login. Please contact the site administrator.', 'logto')
      );
    }

    // Try to get user by Logto ID. The result will be an array of user IDs.
    //
    // See https://developer.wordpress.org/reference/functions/get_users/#more-information
    // - If ‘fields‘ is set to any individual wp_users table field, an array of IDs will be
    // returned.
    $result = get_users([
      'meta_key' => LogtoConstants::USER_META_LOGTO_ID_KEY,
      'meta_value' => $claims->sub,
      'number' => 1,
      'fields' => 'ID',
    ]);

    if ($result[0]) {
      $userId = $result[0];
    } else {
      // If not found, try to get an existing WordPress user by email.
      $user = get_user_by('email', $claims->email);
      $userId = $user ? $user->ID : null;
    }

    // Upsert user if not found or sync profile is enabled
    if (!$userId || $config->syncProfile) {
      $extraClaims = $claims->extra ?? [];

      // Apply username (user_login) strategy
      switch ($config->usernameStrategy) {
        case WpUsernameStrategy::smart->value:
          $userLogin = $claims->email ?? $claims->username;
          break;
        case WpUsernameStrategy::email->value:
          $userLogin = $claims->email;
          break;
        case WpUsernameStrategy::username->value:
          $userLogin = $claims->username;
          break;
      }

      // If user ID is not found, it will create a new user.
      // See https://developer.wordpress.org/reference/functions/wp_insert_user/#parameters
      $userId = wp_insert_user([
        'ID' => $userId,
        'user_email' => $claims->email,
        'user_login' => $userLogin,
        'nickname' => ($extraClaims['nickname']) ?? $claims->name,
        'display_name' => $claims->name,
      ]);

      if (is_wp_error($userId)) {
        $this->handleError(
          _x('Failed to create user', 'Error title', 'logto'),
          $user->get_error_message()
        );
      }

      // Sync user data from Logto claims
      update_user_meta($userId, LogtoConstants::USER_META_CLAIMS_KEY, $claims);
      update_user_meta($userId, LogtoConstants::USER_META_LOGTO_ID_KEY, $claims->sub);
    }

    $user = get_user_by('id', $userId);

    if ($user && $claims->roles) {
      // Iterate role mapping and update user role if needed
      foreach ($config->roleMapping as [$logtoRole, $wpRole]) {
        if (in_array($logtoRole, $claims->roles, true)) {
          $user->set_role($wpRole);
          break;
        }
      }
    }

    return $user;
  }

  function handleProfileUpdateErrors($errors, $update, $user)
  {
    $pendingEmail = get_user_meta($user->ID, '_new_email', true);

    if ($pendingEmail) {
      $errors->add('email', 'Email cannot be updated.');
      delete_user_meta($user->ID, '_new_email');
    } else if ($update && isset($_POST['email']) && $_POST['email'] !== $user->user_email) {
      $errors->add('email', 'Email cannot be updated.');
    }

    if ($update && isset($_POST['pass1']) && $_POST['pass1']) {
      $errors->add('password', 'Password cannot be updated.');
    }
  }
}
