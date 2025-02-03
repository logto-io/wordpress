<?php declare(strict_types=1);
namespace Logto\WpPlugin;

use Logto\Sdk\LogtoClient;

class LogtoPlugin
{

  public function __construct()
  {
  }

  protected function buildClient(): LogtoClient
  {
    $config = LogtoPluginSettings::get();

    // TODO: check if config is ready

    return new LogtoClient(
      new \Logto\Sdk\LogtoConfig(
        endpoint: $config->endpoint,
        appId: $config->appId,
        appSecret: $config->appSecret,
        scopes: $config->scopes,
      ),
    );
  }

  function register(): void
  {
    add_action('init', function () {
      add_rewrite_rule('^login-callback/?$', 'index.php?' . LogtoConstants::LOGIN_CALLBACK_TAG . '=1', 'top');
      add_rewrite_tag('%' . LogtoConstants::LOGIN_CALLBACK_TAG . '%', '([^&]+)');
    });
    add_action('login_form', [$this, 'handleLoginForm']);
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
    wp_redirect($this->buildClient()->signIn(home_url('login-callback/')));
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
      $client = $this->buildClient();
      $client->handleSignInCallback();
      $user = $this->upsertUser($client);
      wp_set_auth_cookie($user->ID, true);
      wp_safe_redirect(home_url());
      return;
    }

    $this->handleCallbackError();
  }

  protected function handleCallbackError(): void
  {
    $error = $_GET["error"];
    $errorDescription = $_GET["error_description"];

    $this->handleError($error, $errorDescription);
  }

  protected function handleError(string $title, string $content): void
  {
    $body = str_starts_with($content, '<') ? $content : "<p>$content</p>";
    wp_die("<h1>$title</h1>$body");
  }

  protected function upsertUser(LogtoClient $client): \WP_User
  {
    $claims = $client->getIdTokenClaims();

    if (!$claims->email) {
      $this->handleError('Email not found', 'Logto user email is required.');
    }

    if (!$claims->email_verified) {
      $this->handleError('Email not verified', 'Logto user email must be verified.');
    }

    // Try to get user by Logto ID
    $result = get_users([
      'meta_key' => LogtoConstants::USER_META_LOGTO_ID_KEY,
      'meta_value' => $claims->sub,
      'number' => 1,
      'fields' => 'ID',
    ]);

    if ($result[0]) {
      $userId = $result[0];
    } else {
      $user = get_user_by('email', $claims->email);
      $userId = $user ? $user->ID : null;
    }

    $extraClaims = $claims->extra ?? [];
    $userId = wp_insert_user([
      'ID' => $userId,
      'user_email' => $claims->email,
      'user_login' => $claims->username ?? $claims->email,
      'nickname' => ($extraClaims['nickname']) ?? $claims->name,
      'display_name' => $claims->name,
    ]);

    if (is_wp_error($userId)) {
      $this->handleError('Insert user failed', $user->get_error_message());
    }

    update_user_meta($userId, LogtoConstants::USER_META_CLAIMS_KEY, $claims);
    update_user_meta($userId, LogtoConstants::USER_META_LOGTO_ID_KEY, $claims->sub);

    return get_user_by('id', $userId);
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
