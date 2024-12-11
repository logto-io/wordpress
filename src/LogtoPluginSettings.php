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
      error_log('Failed to get Logto settings: ' . $e->getMessage());
      return new self();
    }
  }

  public function __construct(
    public string $endpoint = '',
    public string $appId = '',
    public string $appSecret = '',
    // Logto SDK includes default scopes, so no need to include them here
    public string $scope = UserScope::email->value . ' ' . UserScope::roles->value,
    public string $extraParams = '',
    public bool $requireVerifiedEmail = true,
    public string $requireOrganizationId = '',
    public array $roleMapping = [],
    public bool $rememberSession = true,
    public bool $syncProfile = true,
    public string $wpFormLogin = WpFormLogin::query->value,
    public string $usernameStrategy = WpUsernameStrategy::smart->value,
  ) {
  }
}
