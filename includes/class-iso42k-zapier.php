<?php
if (!defined('ABSPATH')) exit;

/**
 * ISO42K_Zapier
 * Zapier webhook integration for assessment leads
 * 
 * Features:
 * - Send assessment data to Zapier webhook
 * - Custom field mapping
 * - Error handling and logging
 * - Test webhook functionality
 */
 
class ISO42K_Zapier {

  /**
   * Send assessment data to Zapier webhook
   * 
   * @param array $lead Lead data
   * @param int $percent Score percentage
   * @param string $maturity Maturity level
   * @param string $ai_summary AI analysis summary
   * @return bool Success status
   */
  public static function send_to_zapier(array $lead, int $percent, string $maturity, string $ai_summary = ''): bool {
    $settings = (array) get_option('iso42k_zapier_settings', []);
    
    // Check if Zapier is enabled
    $enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
    if (!$enabled) {
      ISO42K_Logger::log('Zapier integration is disabled');
      return false;
    }

    // Get webhook URL
    $webhook_url = trim($settings['webhook_url'] ?? '');
    if (empty($webhook_url)) {
      ISO42K_Logger::log('Zapier webhook URL not configured');
      return false;
    }

    // Validate webhook URL
    if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
      ISO42K_Logger::log('Invalid Zapier webhook URL: ' . $webhook_url);
      return false;
    }

    // Prepare data payload
    $payload = self::prepare_payload($lead, $percent, $maturity, $ai_summary);

    // Log outgoing data (without sensitive info)
    ISO42K_Logger::log('Sending to Zapier: ' . $lead['email'] . ' (' . $percent . '%)');

    // Send to Zapier
    $start_time = microtime(true);
    
    $response = wp_remote_post($webhook_url, [
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'body' => wp_json_encode($payload),
      'timeout' => 15,
      'sslverify' => true,
    ]);

    $duration_ms = round((microtime(true) - $start_time) * 1000);

    // Handle response
    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      ISO42K_Logger::log('Zapier webhook error: ' . $error_message);
      self::log_metric(false, $error_message, $duration_ms);
      return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code >= 200 && $response_code < 300) {
      ISO42K_Logger::log('✅ Zapier webhook success (HTTP ' . $response_code . ') in ' . $duration_ms . 'ms');
      self::log_metric(true, null, $duration_ms);
      return true;
    } else {
      ISO42K_Logger::log('❌ Zapier webhook failed: HTTP ' . $response_code . ' - ' . substr($response_body, 0, 200));
      self::log_metric(false, 'HTTP ' . $response_code, $duration_ms);
      return false;
    }
  }

  /**
   * Prepare data payload for Zapier
   */
  private static function prepare_payload(array $lead, int $percent, string $maturity, string $ai_summary): array {
    $settings = (array) get_option('iso42k_zapier_settings', []);
    $include_answers = isset($settings['include_answers']) ? (bool) $settings['include_answers'] : true;
    $include_ai = isset($settings['include_ai']) ? (bool) $settings['include_ai'] : true;

    // Base payload
    $payload = [
      'event_type' => 'iso42001_assessment_completed',
      'timestamp' => current_time('c'), // ISO 8601 format
      'assessment' => [
        'score_percent' => $percent,
        'maturity_level' => $maturity,
        'submitted_at' => wp_date('Y-m-d H:i:s'),
      ],
      'company' => [
        'name' => $lead['company'] ?? '',
        'staff_count' => (int) ($lead['staff'] ?? 0),
      ],
      'contact' => [
        'name' => $lead['name'] ?? '',
        'email' => $lead['email'] ?? '',
        'phone' => $lead['phone'] ?? '',
      ],
    ];

    // Add answers if enabled
    if ($include_answers && !empty($lead['answers'])) {
      $answers = is_array($lead['answers']) ? $lead['answers'] : json_decode($lead['answers'], true);
      
      if (is_array($answers)) {
        $payload['assessment']['answers'] = $answers;
        $payload['assessment']['total_questions'] = count($answers);
        
        // Count responses by type - FIXED: Remove arrow function for PHP 7.2+ compatibility
        $a_count = 0;
        $b_count = 0;
        $c_count = 0;
        
        foreach ($answers as $answer) {
          $answer = strtoupper($answer);
          if ($answer === 'A') {
            $a_count++;
          } elseif ($answer === 'B') {
            $b_count++;
          } elseif ($answer === 'C') {
            $c_count++;
          }
        }
        
        $payload['assessment']['responses'] = [
          'fully_implemented' => $a_count,
          'partially_implemented' => $b_count,
          'not_implemented' => $c_count,
        ];
      }
    }

    // Add AI summary if enabled
    if ($include_ai && !empty($ai_summary)) {
      $payload['assessment']['ai_analysis'] = $ai_summary;
    }

    // Add custom fields from settings
    $custom_fields = $settings['custom_fields'] ?? '';
    if (!empty($custom_fields)) {
      $custom_data = self::parse_custom_fields($custom_fields, $lead);
      if (!empty($custom_data)) {
        $payload['custom'] = $custom_data;
      }
    }

    return $payload;
  }

  /**
   * Parse custom field mappings
   */
  private static function parse_custom_fields(string $custom_fields, array $lead): array {
    $custom_data = [];
    $lines = explode("\n", $custom_fields);
    
    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line) || strpos($line, '=') === false) {
        continue;
      }
      
      $parts = explode('=', $line, 2);
      $key = trim($parts[0]);
      $value = trim($parts[1]);
      
      // Support template variables like {company}, {score}
      $value = str_replace(
        ['{company}', '{staff}', '{name}', '{email}', '{phone}', '{score}', '{maturity}'],
        [
          $lead['company'] ?? '',
          $lead['staff'] ?? '',
          $lead['name'] ?? '',
          $lead['email'] ?? '',
          $lead['phone'] ?? '',
          $lead['percent'] ?? 0,
          $lead['maturity'] ?? ''
        ],
        $value
      );
      
      $custom_data[$key] = $value;
    }
    
    return $custom_data;
  }

  /**
   * Test Zapier webhook connection
   */
  public static function test_webhook(string $webhook_url): array {
    if (empty($webhook_url)) {
      return [
        'success' => false,
        'message' => 'Webhook URL is required'
      ];
    }

    if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
      return [
        'success' => false,
        'message' => 'Invalid webhook URL format'
      ];
    }

    // Test payload
    $test_payload = [
      'event_type' => 'test_connection',
      'timestamp' => current_time('c'),
      'test_data' => [
        'message' => 'This is a test webhook from ISO 42001 Gap Analysis plugin',
        'site_url' => get_site_url(),
        'sent_at' => wp_date('Y-m-d H:i:s'),
      ],
    ];

    $response = wp_remote_post($webhook_url, [
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'body' => wp_json_encode($test_payload),
      'timeout' => 15,
      'sslverify' => true,
    ]);

    if (is_wp_error($response)) {
      return [
        'success' => false,
        'message' => 'Connection failed: ' . $response->get_error_message()
      ];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code >= 200 && $response_code < 300) {
      return [
        'success' => true,
        'message' => 'Connection successful! Zapier received the test webhook (HTTP ' . $response_code . ')'
      ];
    } else {
      return [
        'success' => false,
        'message' => 'Webhook failed: HTTP ' . $response_code . ' - ' . substr($response_body, 0, 100)
      ];
    }
  }

  /**
   * Log Zapier metrics
   */
  private static function log_metric(bool $success, ?string $error, int $duration_ms) {
    $metrics = get_option('iso42k_zapier_metrics', []);
    
    $metrics['total'] = ($metrics['total'] ?? 0) + 1;
    if ($success) {
      $metrics['success'] = ($metrics['success'] ?? 0) + 1;
    } else {
      $metrics['failed'] = ($metrics['failed'] ?? 0) + 1;
      $metrics['last_error'] = $error;
    }
    
    $metrics['last_call_at'] = current_time('mysql');
    
    // Calculate average latency
    $old_avg = $metrics['avg_latency_ms'] ?? 0;
    $old_count = $metrics['total'] - 1;
    $metrics['avg_latency_ms'] = (int) (($old_avg * $old_count + $duration_ms) / $metrics['total']);
    
    update_option('iso42k_zapier_metrics', $metrics);
  }

  /**
   * Get Zapier metrics for monitoring
   */
  public static function get_metrics(): array {
    return (array) get_option('iso42k_zapier_metrics', []);
  }

  /**
   * Reset Zapier metrics
   */
  public static function reset_metrics(): bool {
    return update_option('iso42k_zapier_metrics', []);
  }
}