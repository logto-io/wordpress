<?php declare(strict_types=1);
namespace Logto\WpPlugin;

define('LOGTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOGTO_PLUGIN_URL', plugin_dir_url(__FILE__));

class LogtoConstants
{
  const PLUGIN_DIR = LOGTO_PLUGIN_DIR;
  const PLUGIN_URL = LOGTO_PLUGIN_URL;
  const LOGIN_CALLBACK_TAG = 'logto_login_callback';
  const LOGIN_CALLBACK_PATH = 'login-callback';
  const USER_META_CLAIMS_KEY = 'logto_user_claims';
  const USER_META_LOGTO_ID_KEY = 'logto_user_id';
  const ASSETS_URL = self::PLUGIN_URL . '../assets/';
  const MENU_SLUG = 'logto';
  const OPTION_KEY = 'logto_options';
  const OPTION_NAME = self::OPTION_KEY;
  const OPTION_GROUP = 'logto_option_group';
}

function getSettingsTitle(string $id): string
{
  switch ($id) {
    case 'endpoint':
      return __('Logto endpoint', 'logto');
    case 'appId':
      return __('App ID', 'logto');
    case 'appSecret':
      return __('App secret', 'logto');
    case 'redirectUri':
      return __('Redirect URI', 'logto');
    case 'postSignOutRedirectUri':
      return __('Post sign-out redirect URI', 'logto');
    case 'scope':
      return __('Scope', 'logto');
    case 'extraParams':
      return __('Extra params', 'logto');
    case 'requireVerifiedEmail':
      return __('Require verified email', 'logto');
    case 'requireOrganizationId':
      return __('Require organization ID', 'logto');
    case 'roleMapping':
      return __('Role mapping', 'logto');
    case 'rememberSession':
      return __('Remember session', 'logto');
    case 'syncProfile':
      return __('Sync profile', 'logto');
    case 'wpFormLogin':
      return __('WordPress form login', 'logto');
    case 'usernameStrategy':
      return __('Username strategy', 'logto');
    default:
      return $id;
  }
}
