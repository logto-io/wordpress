<?php declare(strict_types=1);
namespace Logto\WpPlugin;

class LogtoPluginAdminDashboard extends Classes\Singleton
{
  protected LogtoPluginSettings $settings;

  function __construct()
  {
    parent::__construct();
    $this->settings = LogtoPluginSettings::get();
  }

  public function register(): void
  {
    add_action('admin_menu', function () {
      $this->initMenu();
      $this->initSettings();
    });
  }

  public function initMenu(): void
  {
    $capability = 'manage_options';
    $slug = LogtoConstants::MENU_SLUG;
    $settings_page_title = __('Logto Settings', 'logto');
    $settings_menu_title = __('Settings', 'logto');
    $settings_callback = [$this, 'renderSettings'];
    $help_page_title = __('Logto Help', 'logto');
    $help_menu_title = __('Help', 'logto');
    $help_callback = [$this, 'renderHelp'];

    add_menu_page(
      $settings_page_title,
      'Logto',
      $capability,
      $slug,
      $settings_callback,
      LogtoConstants::ASSETS_URL . 'logto-logo.svg',
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

  public function renderSettings(): void
  {
    include LogtoConstants::PLUGIN_DIR . 'pages/Settings.php';
  }

  public function renderHelp(): void
  {
    include LogtoConstants::PLUGIN_DIR . 'pages/Help.php';
  }

  public function initSettings(): void
  {
    register_setting(LogtoConstants::OPTION_GROUP, LogtoConstants::OPTION_NAME, [$this, 'validateSettings']);

    // Add plain css from string
    add_action('admin_head', function () {
      echo '<style>
        #form-logto_options h2 {
          font-size: 1.5em;
          margin-block: 2em 1em;
        }
        #form-logto_options p.subtitle {
          color: #74777a;
          margin-block: -0.5em 1.5em;
          padding-inline: 0;
        }
        #form-logto_options p.description {
          color: #74777a;
          margin-top: 8px;
          max-width: 600px;
        }
        #form-logto_options .form-table th {
          padding: 12px 0;
        }
        #form-logto_options .form-table .radio-group * + * {
          margin-block-start: 0.5em;
        }
</style>';
    });

    add_action('admin_notices', [$this, 'renderSettingsErrors']);

    if (WP_DEBUG === true) {
      error_log('Init Logto settings:');
      error_log(print_r($this->settings, true));
    }

    $basicSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_basic_settings',
      __('Basic settings', 'logto'),
      _x('Settings required to connect to Logto. You can find these settings in the Logto Console application details page.', 'Basic settings description', 'logto'),
    );
    $basicSettings->render();
    $basicSettings->addInputField(
      'endpoint',
      getSettingsTitle('endpoint'),
      _x('The endpoint of your Logto instance. If you are using a custom domain, enter the custom domain here.', 'Logto endpoint description', 'logto'),
      $this->settings->endpoint
    );
    $basicSettings->addInputField(
      'appId',
      getSettingsTitle('appId'),
      _x('The app ID that shows up in the Logto Console application details page.', 'App ID description', 'logto'),
      $this->settings->appId
    );
    $basicSettings->addInputField(
      'appSecret',
      getSettingsTitle('appSecret'),
      _x('One of the app secrets that shows up in the Logto Console application details page.', 'App secret description', 'logto'),
      $this->settings->appSecret
    );
    $basicSettings->addReadonlyField(
      'redirectUri',
      getSettingsTitle('redirectUri'),
      _x('The redirect URI you need to enter and save in the Logto Console application details page.', 'Redirect URI description', 'logto'),
      $this->settings->getRedirectUri()
    );
    $basicSettings->addReadonlyField(
      'postSignOutRedirectUri',
      getSettingsTitle('postSignOutRedirectUri'),
      _x('The post sign-out redirect URI you need to enter and save in the Logto Console application details page.', 'Post sign-out redirect URI description', 'logto'),
      $this->settings->getPostSignOutRedirectUri()
    );

    $authenticationSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_authentication_settings',
      __('Authentication settings'),
      _x('Settings related to user authentication. These settings may affect the user experience.', 'Authentication settings description', 'logto'),
    );
    $authenticationSettings->render();
    $authenticationSettings->addInputField(
      'scope',
      getSettingsTitle('scope'),
      _x('The scopes to use for the authentication request. Separate multiple scopes by spaces.', 'Scope description', 'logto'),
      $this->settings->scope
    );
    $authenticationSettings->addInputField(
      'extraParams',
      getSettingsTitle('extraParams'),
      _x('Extra authentication parameters to include in the authentication request. Use the URL query string format, e.g., <code>param1=value1&amp;param2=value2</code>.', 'Extra params description', 'logto'),
      $this->settings->extraParams
    );
    $authenticationSettings->addSwitchField(
      'requireVerifiedEmail',
      getSettingsTitle('requireVerifiedEmail'),
      _x('Require user email to be verified before logging in', 'Require verified email description', 'logto'),
      _x('Whether to require user email to be verified at Logto. If enabled, users with unverified emails will not be able to log in.', 'Require verified email explanation', 'logto'),
      $this->settings->requireVerifiedEmail
    );
    $authenticationSettings->addInputField(
      'requireOrganizationId',
      getSettingsTitle('requireOrganizationId'),
      _x('When set, users must belong to the specified organization to log in.', 'Require organization ID description', 'logto'),
      $this->settings->requireOrganizationId
    );

    $authorizationSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_authorization_settings',
      __('Authorization settings', 'logto'),
      _x('Settings related to user authorization. These settings may affect the user experience and access control.', 'Authorization settings description', 'logto'),
    );

    $authorizationSettings->render();
    $authorizationSettings->addKeyValuePairsField(
      'roleMapping',
      getSettingsTitle('roleMapping'),
      _x('Map Logto roles to WordPress roles with order of precedence (case-sensitive).<br/>When a role is found in the mapping, the user will be assigned the corresponding WordPress role and the rest of the mapping will be ignored.', 'Role mapping description', 'logto'),
      $this->settings->roleMapping,
      [
        'keyPlaceholder' => __('Logto role', 'logto'),
        'valuePlaceholder' => __('WordPress role', 'logto'),
      ]
    );

    $advancedSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_advanced_settings',
      __('Advanced settings', 'logto'),
      null
    );
    $advancedSettings->render();
    $advancedSettings->addSwitchField(
      'rememberSession',
      getSettingsTitle('rememberSession'),
      _x('Remember user session for a longer period', 'Remember session description', 'logto'),
      _x('By default, WordPress session expires after 2 days. Enable this setting to remember user session for a longer period (14 days).', 'Remember session explanation', 'logto'),
      $this->settings->rememberSession
    );
    $advancedSettings->addSwitchField(
      'syncProfile',
      getSettingsTitle('syncProfile'),
      _x('Sync user profile at every login', 'Sync profile description', 'logto'),
      _x('When enabled, user profile will be synced from Logto at every login and existing WordPress profile will be overwritten (including role mapping).', 'Sync profile explanation', 'logto'),
      $this->settings->syncProfile
    );
    $advancedSettings->addRadioField(
      'wpFormLogin',
      getSettingsTitle('wpFormLogin'),
      _x('Choose how to handle WordPress form login. You can disable WordPress form login to secure your site with Logto, or allow users to log in with WordPress form by appending a query parameter to the login URL.', 'WordPress form login description', 'logto'),
      $this->settings->wpFormLogin,
      [
        WpFormLogin::disabled->value => __('Disabled', 'logto'),
        WpFormLogin::query->value => _x('Query (append <code>?form=1</code> to use WordPress form login)', 'WordPress form login query option', 'logto'),
      ]
    );
    $advancedSettings->addRadioField(
      'usernameStrategy',
      getSettingsTitle('usernameStrategy'),
      _x('Choose how to determine the WordPress username when a user logs in with Logto.', 'Username strategy description', 'logto'),
      $this->settings->usernameStrategy,
      [
        WpUsernameStrategy::smart->value => _x('<b>Smart:</b> Use Logto email if available, otherwise use Logto username', 'Smart username strategy', 'logto'),
        WpUsernameStrategy::email->value => _x('<b>Email:</b> Use Logto email', 'Email username strategy', 'logto'),
        WpUsernameStrategy::username->value => _x('<b>Username:</b> Use Logto username', 'Username username strategy', 'logto'),
      ]
    );
  }

  public function validateSettings(array $input): array|false
  {
    if (WP_DEBUG === true) {
      error_log('Validating Logto settings:');
      error_log(print_r($input, true));
    }

    $oldValue = get_option(LogtoConstants::OPTION_NAME, []);

    // Check if mandatory fields are filled
    $mandatoryFields = ['endpoint', 'appId', 'appSecret'];
    foreach ($mandatoryFields as $field) {
      if (empty($input[$field])) {
        add_settings_error(
          LogtoConstants::OPTION_NAME,
          'logto_settings_invalid',
          /* translators: %s is the field name that is missing. */
          sprintf(__('Field "%s" is required.', 'logto'), getSettingsTitle($field)),
          'error'
        );
      }
    }

    if (get_settings_errors(LogtoConstants::OPTION_NAME)) {
      return $oldValue;
    }

    // Convert checkbox values to boolean. We need to iterate over all settings because $input will
    // only contain values that are checked ('on') and unchecked values will be missing.
    foreach ($this->settings as $key => $value) {
      if (is_bool($value)) {
        $input[$key] = $input[$key] === 'on';
      }
    }

    $input['roleMapping'] = $this->handleKeyValuePairsSettings('roleMapping', $input);

    add_settings_error(
      LogtoConstants::OPTION_NAME,
      'logto_settings_updated',
      __('Settings updated.', 'logto'),
      'updated'
    );

    return $input;
  }

  protected function handleKeyValuePairsSettings(string $key, array $input): array
  {
    if (!isset($input[$key])) {
      return [];
    }

    $keys = $input[$key]['keys'];
    $values = $input[$key]['values'];
    return array_map(
      fn($key, $value) => [$key, $value],
      $keys,
      $values
    );
  }

  public function renderSettingsErrors()
  {
    settings_errors(LogtoConstants::OPTION_NAME);
  }
}
