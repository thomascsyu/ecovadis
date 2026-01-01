<?php
if (!defined('ABSPATH')) exit;

class ISO42K_Logger {

  public static function enabled(): bool {
    $s = (array) get_option('iso42k_debug_settings', []);
    return !empty($s['debug']);
  }

  public static function log(string $msg): void {
    if (!self::enabled()) return;
    error_log('[ISO42K] ' . $msg);
  }
}