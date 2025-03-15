<?php declare(strict_types=1);
namespace Logto\WpPlugin;

use Logto\Sdk\Constants\UserScope;
use Logto\Sdk\Models\JsonModel;

enum WpUsernameStrategy: string
{
  case email = 'email';
  case username = 'username';
  case smart = 'smart';
}

enum WpFormLogin: string
{
  case disabled = 'disabled';
  case query = 'query';
}

class LogtoPluginSettings extends JsonModel
{
  static function get(): self
  {
    try {
      return new self(
        ...get_option(LogtoConstants::OPTION_KEY, [])
      );
    } catch (\Throwable $e) {
      write_log('Failed to get Logto settings: ' . $e->getMessage());
      return new self();
    }
  }

  // We have to define all properties here and assign them in the constructor because WordPress
  // plugin directory's SVN pre-commit hook will reject the commit if we assign the enum values
  // as default values on property promotion. The error message is:
  //
  // > Fatal error: Constant expression contains invalid operations in Standard input code on line 47
  // > Errors parsing Standard input code
  //
  // Maybe we can bring back the enum default values when WordPress SVN switches to a newer PHP
  // version.

  public string $endpoint;
  public string $appId;
  public string $appSecret;
  public string $scope;
  public string $extraParams;
  public bool $requireVerifiedEmail;
  public string $requireOrganizationId;
  public array $roleMapping;
  public bool $rememberSession;
  public bool $syncProfile;
  public string $wpFormLogin;
  public string $usernameStrategy;

  public function __construct(
    string $endpoint = '',
    string $appId = '',
    string $appSecret = '',
    ?string $scope = null,
    string $extraParams = '',
    bool $requireVerifiedEmail = true,
    string $requireOrganizationId = '',
    array $roleMapping = [],
    bool $rememberSession = true,
    bool $syncProfile = true,
    ?string $wpFormLogin = null,
    ?string $usernameStrategy = null,
    // Ignored
    ...$extra
  ) {
    $this->endpoint = $endpoint;
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    // Logto SDK includes default scopes, so no need to include them here
    $this->scope = $scope ?? UserScope::email->value . ' ' . UserScope::roles->value;
    $this->extraParams = $extraParams;
    $this->requireVerifiedEmail = $requireVerifiedEmail;
    $this->requireOrganizationId = $requireOrganizationId;
    $this->roleMapping = $roleMapping;
    $this->rememberSession = $rememberSession;
    $this->syncProfile = $syncProfile;
    $this->wpFormLogin = $wpFormLogin ?? WpFormLogin::query->value;
    $this->usernameStrategy = $usernameStrategy ?? WpUsernameStrategy::smart->value;
  }

  /**
   * Check if the settings are ready to use. Returns true if the following conditions are met:
   * - Endpoint is not empty
   * - App ID is not empty
   * - App secret is not empty
   */
  function isReady(): bool
  {
    return !empty($this->endpoint)
      && !empty($this->appId)
      && !empty($this->appSecret);
  }

  function getRedirectUri(): string
  {
    return home_url(LogtoConstants::LOGIN_CALLBACK_PATH . '/');
  }

  function getPostSignOutRedirectUri(): string
  {
    return home_url('/');
  }
}
