<?php declare(strict_types=1);
namespace Logto\WpPlugin;

define('LOGTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOGTO_PLUGIN_URL', plugin_dir_url(__FILE__));

class LogtoConstants
{
  const PLUGIN_DIR = LOGTO_PLUGIN_DIR;
  const PLUGIN_URL = LOGTO_PLUGIN_URL;
  const LOGIN_CALLBACK_TAG = 'logto_login_callback';
  const USER_META_CLAIMS_KEY = 'logto_user_claims';
  const USER_META_LOGTO_ID_KEY = 'logto_user_id';
  const ASSETS_URL = self::PLUGIN_URL . '../assets/';
  const MENU_SLUG = 'logto';
  const OPTION_KEY = 'logto_options';
  const OPTION_NAME = self::OPTION_KEY;
  const OPTION_GROUP = 'logto_option_group';
}
