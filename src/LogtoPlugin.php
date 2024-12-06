<?php declare(strict_types=1);
namespace Logto\WpPlugin;

use Logto\Sdk\LogtoClient;

define('LOGTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOGTO_PLUGIN_URL', plugin_dir_url(__FILE__));

class LogtoPlugin
{
  const LOGIN_CALLBACK_TAG = 'logto_login_callback';
  const USER_META_CLAIMS_KEY = 'logto_user_claims';
  const USER_META_LOGTO_ID_KEY = 'logto_user_id';
  const ASSETS_URL = LOGTO_PLUGIN_URL . '../assets/';
  const MENU_SLUG = 'logto';

  public function __construct()
  {
  }

  protected function getConfig(): LogtoPluginSettings
  {
    return new LogtoPluginSettings(
      json_decode(
        get_option(LogtoPluginSettings::OPTION_KEY, '{}'),
        true
      ),
    );
  }

  protected function buildClient(): LogtoClient
  {
    $config = $this->getConfig();

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
      add_rewrite_rule('^login-callback/?$', 'index.php?' . self::LOGIN_CALLBACK_TAG . '=1', 'top');
      add_rewrite_tag('%' . self::LOGIN_CALLBACK_TAG . '%', '([^&]+)');
    });
    add_action('login_form', [$this, 'handleLoginForm']);
    add_action('wp_logout', [$this, 'handleLogout']);
    add_action('template_redirect', [$this, 'handleCallback']);
    add_action('user_profile_update_errors', [$this, 'handleProfileUpdateErrors'], 10, 3);
    add_action('admin_menu', [$this, 'init_menu']);
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
    if (!get_query_var(self::LOGIN_CALLBACK_TAG)) {
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

  function init_menu(): void
  {
    $capability = 'manage_options';
    $slug = self::MENU_SLUG;
    $settings_page_title = 'Logto Settings';
    $settings_menu_title = 'Settings';
    $settings_callback = [$this, 'render_menu'];
    $help_page_title = 'Logto Help';
    $help_menu_title = 'Help';
    $help_callback = [$this, 'render_menu'];

    add_menu_page(
      $settings_page_title,
      'Logto',
      $capability,
      $slug,
      $settings_callback,
      self::ASSETS_URL . 'logto-logo.svg',
      86,
    );
    add_submenu_page(
      $slug,
      $settings_page_title,
      $settings_menu_title,
      $capability,
      // Use the same slug as the parent menu to remove duplicate menu item created by add_menu_page
      $slug,
      $settings_callback
    );
    add_submenu_page(
      $slug,
      $help_page_title,
      $help_menu_title,
      $capability,
      "$slug-help",
      $help_callback
    );
    add_action('admin_head', function () {
      echo '<style>
          #adminmenu .toplevel_page_logto .wp-menu-image img {
            padding-top: 8px;
          }
      </style>';
    });
  }

  function render_menu(): void
  {
    include LOGTO_PLUGIN_DIR . 'pages/MenuSettings.php';
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
      'meta_key' => self::USER_META_LOGTO_ID_KEY,
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

    $userId = wp_insert_user([
      'ID' => $userId,
      'user_email' => $claims->email,
      'user_login' => $claims->username ?? "user_$claims->sub",
      'display_name' => $claims->name,
    ]);

    if (is_wp_error($userId)) {
      $this->handleError('Insert user failed', $user->get_error_message());
    }

    update_user_meta($userId, self::USER_META_CLAIMS_KEY, $claims);
    update_user_meta($userId, self::USER_META_LOGTO_ID_KEY, $claims->sub);

    return get_user_by('id', $userId);
  }

  protected function handleProfileUpdateErrors($errors, $update, $user)
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
