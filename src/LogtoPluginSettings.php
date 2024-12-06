<?php declare(strict_types=1);
namespace Logto\WpPlugin;

use Logto\Sdk\Constants\UserScope;
use Logto\Sdk\Models\JsonModel;

class LogtoPluginSettings extends JsonModel
{
  const OPTION_KEY = 'logto_config';

  public function __construct(
    public ?string $endpoint = null,
    public ?string $appId = null,
    public ?string $appSecret = null,
    public array $scopes = [UserScope::email], // Logto SDK includes default scopes, so no need to include them here
    public bool $syncProfile = true,
    public array $extraParams = [],
    public bool $wpLoginEnabled = true,
    ...$extra
  ) {
    $this->extra = $extra;
  }
}
