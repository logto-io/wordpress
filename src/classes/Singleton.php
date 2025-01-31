<?php declare(strict_types=1);
namespace Logto\WpPlugin\Classes;

class Singleton
{
  private static ?self $instance = null;

  public static function getInstance(): static
  {
    if (self::$instance === null) {
      self::$instance = new static();
    }

    return self::$instance;
  }

  public function __construct()
  {
    if (self::$instance !== null) {
      throw new \Exception('Singleton instance already exists');
    }
  }
}
