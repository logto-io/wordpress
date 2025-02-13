<?php declare(strict_types=1);
namespace Logto\WpPlugin;

function write_log($data): void
{
  if (true === WP_DEBUG) {
    if (is_array($data) || is_object($data)) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
      error_log(print_r($data, true));
    } else {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log($data);
    }
  }
}
