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
    $settings_page_title = 'Logto Settings';
    $settings_menu_title = 'Settings';
    $settings_callback = [$this, 'renderMenu'];
    $help_page_title = 'Logto Help';
    $help_menu_title = 'Help';
    $help_callback = [$this, 'renderMenu'];

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

  public function renderMenu(): void
  {
    include LogtoConstants::PLUGIN_DIR . 'pages/MenuSettings.php';
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

    error_log('Init settings');
    error_log(print_r($this->settings, true));

    $basicSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_basic_settings',
      'Basic settings',
      'Settings required to connect to Logto. You can find these settings in the Logto Console application details page.',
    );
    $basicSettings->render();
    $basicSettings->addInputField(
      'endpoint',
      'Logto endpoint',
      'The endpoint of your Logto instance. If you are using a custom domain, enter the custom domain here.',
      $this->settings->endpoint
    );
    $basicSettings->addInputField(
      'appId',
      'App ID',
      'The app ID that shows up in the Logto Console application details page.',
      $this->settings->appId
    );
    $basicSettings->addInputField(
      'appSecret',
      'App secret',
      'One of the app secrets that shows up in the Logto Console application details page.',
      $this->settings->appSecret
    );

    $authenticationSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_authentication_settings',
      'Authentication settings',
      'Settings related to user authentication. These settings may affect the user experience.',
    );
    $authenticationSettings->render();
    $authenticationSettings->addInputField(
      'scope',
      'Scope',
      'The scopes to use for the authentication request. Separate multiple scopes with a space.',
      $this->settings->scope
    );
    $authenticationSettings->addInputField(
      'extraParams',
      'Extra params',
      'Extra authentication parameters to include in the authentication request. Use the URL query string format, e.g., <code>param1=value1&amp;param2=value2</code>.',
      $this->settings->extraParams
    );
    $authenticationSettings->addSwitchField(
      'requireVerifiedEmail',
      'Require verified email',
      'Require user email to be verified before logging in',
      'Whether to require user email to be verified at Logto. If enabled, users with unverified emails will not be able to log in.',
      $this->settings->requireVerifiedEmail
    );
    $authenticationSettings->addInputField(
      'requireOrganizationId',
      'Require organization ID',
      'When set, users must belong to the specified organization to log in.',
      $this->settings->requireOrganizationId
    );

    $authorizationSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_authorization_settings',
      'Authorization settings',
      'Settings related to user authorization. These settings may affect the user experience and access control.',
    );

    $authorizationSettings->render();
    $authorizationSettings->addKeyValuePairsField(
      'roleMapping',
      'Role mapping',
      'Map Logto roles to WordPress roles with order of precedence.<br/>When a role is found in the mapping, the user will be assigned the corresponding WordPress role and the rest of the mapping will be ignored.',
      $this->settings->roleMapping,
      [
        'keyPlaceholder' => 'Logto role',
        'valuePlaceholder' => 'WordPress role',
      ]
    );

    $advancedSettings = new Settings\SettingsSection(
      LogtoConstants::MENU_SLUG,
      LogtoConstants::OPTION_NAME,
      'logto_advanced_settings',
      'Advanced settings',
      null
    );
    $advancedSettings->render();
    $advancedSettings->addSwitchField(
      'rememberSession',
      'Remember session',
      'Remember user session for a longer period',
      'By default, WordPress session expires after 2 days. Enable this setting to remember user session for a longer period (14 days).',
      $this->settings->rememberSession
    );
    $advancedSettings->addSwitchField(
      'syncProfile',
      'Sync profile',
      'Sync user profile at every login',
      'When enabled, user profile will be synced from Logto at every login and existing WordPress profile will be overwritten.',
      $this->settings->syncProfile
    );
    $advancedSettings->addRadioField(
      'wpFormLogin',
      'WordPress form login',
      'Choose how to handle WordPress form login. You can disable WordPress form login to secure your site with Logto, or allow users to log in with WordPress form by appending a query parameter to the login URL.',
      $this->settings->wpFormLogin,
      [
        WpFormLogin::disabled->value => 'Disabled',
        WpFormLogin::query->value => 'Query (append <code>?form=1</code> to use WordPress form login)',
      ]
    );
    $advancedSettings->addRadioField(
      'usernameStrategy',
      'Username strategy',
      'Choose how to determine the WordPress username when a user logs in with Logto.',
      $this->settings->usernameStrategy,
      [
        WpUsernameStrategy::smart->value => '<b>Smart:</b> Use Logto email if available, otherwise use Logto username',
        WpUsernameStrategy::email->value => '<b>Email:</b> Use Logto email',
        WpUsernameStrategy::username->value => '<b>Username:</b> Use Logto username',
      ]
    );
  }

  public function validateSettings(array $input): array
  {
    error_log('Validating settings');
    error_log(print_r($input, true));

    // Convert checkbox values to boolean. We need to iterate over all settings because $input will
    // only contain values that are checked ('on') and unchecked values will be missing.
    foreach ($this->settings as $key => $value) {
      if (is_bool($value)) {
        $input[$key] = $input[$key] === 'on';
      }
    }

    $input['roleMapping'] = $this->handleKeyValuePairsSettings('roleMapping', $input);

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
}
