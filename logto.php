<?php

if (!defined('ABSPATH'))
  exit;

/*
 * Plugin Name: Logto - User Authentication and Authorization
 * Description: Enable beautiful and secure user authentication, including passwordless, social login, single sign-on (SSO), multi-factor authentication (MFA). Generic OAuth2, OpenID Connect, SAML are also supported. Use role-based access control (RBAC) to manage user permissions.
 * Version: 1.0.1
 * Author: Logto
 * Author URI: https://logto.io/
 * License: MPL-2.0
 * License URI: https://www.mozilla.org/MPL/2.0/
 * Text Domain: logto
 */

require_once __DIR__ . '/vendor/autoload.php';

use Logto\WpPlugin\LogtoPlugin;

$plugin = new LogtoPlugin();
$plugin->register();
