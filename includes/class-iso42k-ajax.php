<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ISO42K_Ajax
 * Handles all Ajax requests for the assessment
 * 
 * @version 7.3.1
 */

if (!class_exists('ISO42K_Ajax')) { // ✅ Add this check

class ISO42K_Ajax {

    /**
     * Register Ajax handlers
     */
    public static function init() {
        // Public handlers (nopriv = not logged in)
        add_action('wp_ajax_nopriv_iso42k_submit', [__CLASS__, 'handle_submit']);
        add_action('wp_ajax_nopriv_iso42k_process_background', [__CLASS__, 'handle_background_processing']);
        add_action('wp_ajax_nopriv_iso42k_get_question', [__CLASS__, 'handle_get_question']);
        add_action('wp_ajax_nopriv_iso42k_get_questions_batch', [__CLASS__, 'handle_get_questions_batch']);
        add_action('wp_ajax_nopriv_iso42k_track_start', [__CLASS__, 'handle_track_start']);
        add_action('wp_ajax_nopriv_iso42k_refresh_nonce', [__CLASS__, 'handle_refresh_nonce']);
        
        // Also allow for logged-in users
        add_action('wp_ajax_iso42k_submit', [__CLASS__, 'handle_submit']);
        add_action('wp_ajax_iso42k_process_background', [__CLASS__, 'handle_background_processing']);
        add_action('wp_ajax_iso42k_get_question', [__CLASS__, 'handle_get_question']);
        add_action('wp_ajax_iso42k_get_questions_batch', [__CLASS__, 'handle_get_questions_batch']);
        add_action('wp_ajax_iso42k_track_start', [__CLASS__, 'handle_track_start']);
        add_action('wp_ajax_iso42k_refresh_nonce', [__CLASS__, 'handle_refresh_nonce']); // <--- ADDED THIS
        
        // Admin-only handlers
        add_action('wp_ajax_iso42k_test_email', [__CLASS__, 'handle_test_email']);
        
        // Add Ajax handlers for individual AI tests
        add_action('wp_ajax_iso42k_test_deepseek', [__CLASS__, 'handle_test_deepseek']);
        add_action('wp_ajax_iso42k_test_qwen', [__CLASS__, 'handle_test_qwen']);
        add_action('wp_ajax_iso42k_test_grok', [__CLASS__, 'handle_test_grok']);
    }

    /**
     * Verify nonce for security
     */
    private static function verify_nonce() {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        
        // First try the provided nonce
        if (!empty($nonce) && wp_verify_nonce($nonce, 'iso42k_assessment_nonce')) {
            return; // Valid nonce
        }
        
        // If direct nonce fails, try to get from HTTP headers (for cases where it's sent differently)
        $header_nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? $_SERVER['HTTP_X-WP-NONCE'] ?? '';
        if (!empty($header_nonce) && wp_verify_nonce($header_nonce, 'iso42k_assessment_nonce')) {
            return; // Valid nonce from header
        }
        
        // Also try to get from request headers using getallheaders() if available
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['X-WP-Nonce']) && wp_verify_nonce($headers['X-WP-Nonce'], 'iso42k_assessment_nonce')) {
                return; // Valid nonce from header
            }
            if (isset($headers['X_WP_NONCE']) && wp_verify_nonce($headers['X_WP_NONCE'], 'iso42k_assessment_nonce')) {
                return; // Valid nonce from header
            }
        }
        
        // If all nonce checks fail, return error
        // For debugging purposes, log that we're receiving a request without proper nonce
        ISO42K_Logger::log('Nonce verification failed for Ajax request');
        
        // In some cases, we might want to be more lenient during development or for specific scenarios
        // This could be controlled by a constant or setting
        if (defined('ISO42K_ALLOW_NO_NONCE') && ISO42K_ALLOW_NO_NONCE) {
            ISO42K_Logger::log('⚠️ Nonce check bypassed due to ISO42K_ALLOW_NO_NONCE setting');
            return;
        }
        
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page.'], 403);
    }

    /**
     * Handle initial submission (Stage 1 - Quick save)
     */
    public static function handle_submit() {
        self::verify_nonce();

        ISO42K_Logger::log('=== STAGE 1: QUICK SUBMIT ===');

        try {
            // Validate required data
            if (!isset($_POST['contact']) || !isset($_POST['answers']) || !isset($_POST['staff']) || !isset($_POST['nonce'])) {
                wp_send_json_error(['message' => 'Missing required data: contact, answers, staff, or security token'], 400);
            }

            $contact = $_POST['contact'];
            $answers = $_POST['answers'];
            $staff = intval($_POST['staff']);

            // Validate email
            if (empty($contact['email']) || !is_email($contact['email'])) {
                wp_send_json_error(['message' => 'Valid email address is required'], 400);
            }

            // Calculate score
            if (!class_exists('ISO42K_Assessment')) {
                ISO42K_Logger::log('❌ Assessment class not found');
                wp_send_json_error(['message' => 'Assessment system is temporarily unavailable. Please try again later.'], 500);
            }

            $assessment = new ISO42K_Assessment();
            try {
                $result = $assessment->calculate($answers, $staff);
            } catch (Exception $e) {
                ISO42K_Logger::log('❌ Assessment calculation failed: ' . $e->getMessage());
                wp_send_json_error(['message' => 'Assessment calculation failed. Please try again later.'], 500);
            }

            ISO42K_Logger::log('Score calculated: ' . $result['percent'] . '% (' . $result['maturity'] . ')');

            // Save to database
            if (!class_exists('ISO42K_Leads')) {
                ISO42K_Logger::log('❌ Database class not found');
                wp_send_json_error(['message' => 'Database system is temporarily unavailable. Please try again later.'], 500);
            }

            $lead_data = [
                'company' => sanitize_text_field($contact['org'] ?? ''),
                'staff' => $staff,
                'name' => sanitize_text_field($contact['name'] ?? ''),
                'email' => sanitize_email($contact['email']),
                'phone' => sanitize_text_field($contact['phone'] ?? ''),
                'percent' => $result['percent'],
                'maturity' => $result['maturity'],
                'answers' => $answers,
                'ai_summary' => '', // Will be filled in background
                'created_at' => current_time('mysql')
            ];

            $lead_id = ISO42K_Leads::insert($lead_data);

            if (!$lead_id) {
                ISO42K_Logger::log('❌ Failed to save lead to database');
                wp_send_json_error(['message' => 'Failed to save your assessment. Please try again.'], 500);
            }

            ISO42K_Logger::log('✅ Lead saved: ID ' . $lead_id);

            // Trigger background processing asynchronously
            $background_success = self::trigger_background_processing($lead_id, $contact['email']);

            // Return success immediately - even if background processing fails, we want to give positive user feedback
            $response = [
                'lead_id' => $lead_id,
                'percent' => $result['percent'],
                'maturity' => $result['maturity'],
                'message' => 'Thank you for completing the assessment! Your results are being processed. You will receive an email with your detailed report shortly.'
            ];

            // Add background processing status for debugging if needed
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $response['background_processing_triggered'] = $background_success;
            }

            wp_send_json_success($response);

        } catch (Exception $e) {
            ISO42K_Logger::log('❌ Submission error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An error occurred while processing your assessment. Your information has been saved and we will process it shortly.'
            ]);
        }
    }

    /**
     * Handle background processing (Stage 2 - AI + PDF + Email)
     */
    public static function handle_background_processing() {
        self::verify_nonce();

        ISO42K_Logger::log('=== STAGE 2: BACKGROUND PROCESSING ===');

        try {
            if (!isset($_POST['lead_id']) || !isset($_POST['nonce'])) {
                wp_send_json_error(['message' => 'Lead ID or security token required'], 400);
            }

            $lead_id = intval($_POST['lead_id']);
            ISO42K_Logger::log('Processing lead ID: ' . $lead_id);

            // Get lead data
            $lead = ISO42K_Leads::get_by_id($lead_id);
            if (!$lead) {
                ISO42K_Logger::log('❌ Lead not found: ' . $lead_id);
                wp_send_json_error(['message' => 'Lead not found'], 404);
            }

            $response = [
                'ai_generated' => false,
                'pdf_generated' => false,
                'email_user_sent' => false,
                'email_admin_sent' => false,
                'pdf_url' => '',
                'processing_time' => 0
            ];

            $start_time = microtime(true);

            // Step 1: Generate AI Analysis
            try {
                if (class_exists('ISO42K_AI')) {
                    ISO42K_Logger::log('Generating AI analysis...');
                    $ai_start_time = microtime(true);
                    $ai_result = ISO42K_AI::analyse($lead['answers']);
                    $ai_summary = $ai_result['content'];
                    $is_ai_generated = $ai_result['is_ai_generated'];
                    $ai_duration = round((microtime(true) - $ai_start_time) * 1000); // in milliseconds
                    ISO42K_Logger::log('AI processing took ' . $ai_duration . 'ms');
                    
                    if (!empty($ai_summary)) {
                        // Update lead with AI summary
                        $update_result = ISO42K_Leads::update($lead_id, [
                            'ai_summary' => $ai_summary,
                            'is_ai_generated' => $is_ai_generated ? 1 : 0  // Store whether it was AI generated
                        ]);
                        if ($update_result) {
                            $lead['ai_summary'] = $ai_summary; // Update local variable for consistency
                            $response['ai_generated'] = $is_ai_generated; // Only set to true if it was actually AI generated
                            $ai_summary_length = is_string($ai_summary) ? strlen($ai_summary) : (is_array($ai_summary) ? count($ai_summary) : 0);
                            ISO42K_Logger::log('✅ AI analysis saved (' . $ai_summary_length . ' chars/items), AI Generated: ' . ($is_ai_generated ? 'YES' : 'NO'));
                        } else {
                            ISO42K_Logger::log('⚠️ Failed to update lead with AI summary');
                        }
                    } else {
                        ISO42K_Logger::log('⚠️ AI analysis returned empty');
                    }
                } else {
                    ISO42K_Logger::log('⚠️ AI class not found - skipping');
                }
            } catch (Exception $e) {
                ISO42K_Logger::log('❌ AI generation failed: ' . $e->getMessage());
                // Don't fail the entire process if AI fails
            }

            // Step 2: Generate PDF
            $pdf_path = null;
            try {
                if (class_exists('ISO42K_PDF')) {
                    ISO42K_Logger::log('Generating PDF...');
                    $pdf_start_time = microtime(true);
                    $questions = ISO42K_Questions::get_all();
                    $pdf_result = ISO42K_PDF::generate_pdf_and_attach(
                        $lead,
                        $questions,
                        $lead['answers'] ?? []
                    );
                    $pdf_duration = round((microtime(true) - $pdf_start_time) * 1000); // in milliseconds
                    ISO42K_Logger::log('PDF processing took ' . $pdf_duration . 'ms');
                    
                    if (!is_wp_error($pdf_result) && isset($pdf_result['path'])) {
                        $pdf_path = $pdf_result['path'];
                        
                        if ($pdf_path && file_exists($pdf_path)) {
                            $response['pdf_generated'] = true;
                            $filesize = size_format(filesize($pdf_path));
                            ISO42K_Logger::log('✅ PDF generated: ' . basename($pdf_path) . ' (' . $filesize . ')');
                            
                            // Create secure download URL
                            $token = wp_hash($lead['email'] . $lead_id . time() . 'iso42k_pdf');
                            $token_data = [
                                'lead_id' => $lead_id,
                                'email' => $lead['email'],
                                'path' => $pdf_path,
                                'expires' => time() + (7 * 24 * 60 * 60) // 7 days
                            ];
                            
                            // Use non-autoload option for better performance
                            update_option('iso42k_pdf_token_' . $token, $token_data, false);
                            
                            $response['pdf_url'] = add_query_arg([
                                'iso42k_download' => 'pdf',
                                'token' => $token
                            ], home_url('/'));
                        } else {
                            ISO42K_Logger::log('⚠️ PDF generation returned no file');
                        }
                    } else {
                        ISO42K_Logger::log('❌ PDF generation failed: ' . ($pdf_result->get_error_message() ?? 'Unknown error'));
                        $pdf_path = null;
                    }
                } else {
                    ISO42K_Logger::log('⚠️ PDF class not found - skipping');
                }
            } catch (Exception $e) {
                ISO42K_Logger::log('❌ PDF generation failed: ' . $e->getMessage());
                // Don't fail the entire process if PDF generation fails
            }

            // Step 3: Send User Email (with performance optimization)
            try {
                if (class_exists('ISO42K_Email')) {
                    ISO42K_Logger::log('Sending user email to: ' . $lead['email']);
                    // Add PDF URL to lead data if available
                    $lead_for_email = $lead;
                    if (isset($response['pdf_url']) && !empty($response['pdf_url'])) {
                        $lead_for_email['pdf_url'] = $response['pdf_url'];
                    }
                    
                    $email_start_time = microtime(true);
                    $user_sent = ISO42K_Email::send_user(
                        $lead_for_email,
                        $lead['percent'],
                        $lead['maturity'],
                        $lead['ai_summary'] ?? '',
                        $pdf_path
                    );
                    $email_duration = round((microtime(true) - $email_start_time) * 1000); // in milliseconds
                    ISO42K_Logger::log('User email processing took ' . $email_duration . 'ms');
                    
                    $response['email_user_sent'] = $user_sent;
                    
                    if ($user_sent) {
                        ISO42K_Logger::log('✅ User email sent successfully');
                    } else {
                        ISO42K_Logger::log('❌ User email failed to send');
                    }
                } else {
                    ISO42K_Logger::log('⚠️ Email class not found - skipping');
                }
            } catch (Exception $e) {
                ISO42K_Logger::log('❌ User email exception: ' . $e->getMessage());
                // Don't fail the entire process if email fails
            }

            // Step 4: Send Admin Email (with performance optimization)
            try {
                if (class_exists('ISO42K_Email')) {
                    ISO42K_Logger::log('Attempting to send admin notification...');
                    $admin_start_time = microtime(true);
                    $admin_sent = ISO42K_Email::send_admin(
                        $lead_for_email,
                        $lead['percent'],
                        $lead['maturity'],
                        $lead['ai_summary'] ?? '',
                        $pdf_path
                    );
                    $admin_duration = round((microtime(true) - $admin_start_time) * 1000); // in milliseconds
                    ISO42K_Logger::log('Admin email processing took ' . $admin_duration . 'ms');
                    
                    $response['email_admin_sent'] = $admin_sent;
                    
                    if ($admin_sent) {
                        ISO42K_Logger::log('✅ Admin email sent successfully');
                    } else {
                        ISO42K_Logger::log('❌ Admin email failed or disabled');
                    }
                } else {
                    ISO42K_Logger::log('⚠️ Email class not found - skipping');
                }
            } catch (Exception $e) {
                ISO42K_Logger::log('❌ Admin email exception: ' . $e->getMessage());
                // Don't fail the entire process if admin email fails
            }

            $response['processing_time'] = round((microtime(true) - $start_time) * 1000); // Total processing time in milliseconds
            ISO42K_Logger::log('=== BACKGROUND PROCESSING COMPLETE ===');
            ISO42K_Logger::log('Total processing time: ' . $response['processing_time'] . 'ms');
            ISO42K_Logger::log('AI: ' . ($response['ai_generated'] ? 'YES' : 'NO') . 
                              ' | PDF: ' . ($response['pdf_generated'] ? 'YES' : 'NO') . 
                              ' | User Email: ' . ($response['email_user_sent'] ? 'YES' : 'NO') . 
                              ' | Admin Email: ' . ($response['email_admin_sent'] ? 'YES' : 'NO'));

            wp_send_json_success($response);
            
        } catch (Exception $e) {
            ISO42K_Logger::log('❌ Background processing error: ' . $e->getMessage());
            
            // Even if background processing fails, return success to avoid user seeing errors
            wp_send_json_success([
                'ai_generated' => false,
                'pdf_generated' => false,
                'email_user_sent' => false,
                'email_admin_sent' => false,
                'pdf_url' => '',
                'message' => 'Background processing encountered an issue, but your assessment has been saved.'
            ]);
        }
    }
    
    /**
 * Handle DeepSeek connection test
 */
public static function handle_test_deepseek() {
  if (!check_ajax_referer('iso42k_admin_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'Security check failed']);
    return;
  }
  
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
    return;
  }
  
  ISO42K_Logger::log('=== DeepSeek Connection Test Started ===');
  
  $ai_settings = get_option('iso42k_ai_settings', []);
  $api_key = $ai_settings['deepseek_api_key'] ?? '';
  $model = $ai_settings['deepseek_model'] ?? 'deepseek-chat';
  $endpoint = $ai_settings['deepseek_endpoint'] ?? 'https://api.deepseek.com/v1/chat/completions';
  
  if (empty($api_key)) {
    ISO42K_Logger::log('❌ DeepSeek API key not configured');
    wp_send_json_error(['message' => 'DeepSeek API key is not configured. Please enter your API key and save settings first.']);
    return;
  }
  
  $result = self::test_ai_provider_connection($api_key, $model, $endpoint, 'DeepSeek', false);
  
  if ($result['success']) {
    wp_send_json_success(['message' => $result['message']]);
  } else {
    wp_send_json_error(['message' => $result['message']]);
  }
}

/**
 * Handle Qwen (via OpenRouter) connection test
 */
public static function handle_test_qwen() {
  if (!check_ajax_referer('iso42k_admin_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'Security check failed']);
    return;
  }
  
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
    return;
  }
  
  ISO42K_Logger::log('=== Qwen (OpenRouter) Connection Test Started ===');
  
  $ai_settings = get_option('iso42k_ai_settings', []);
  $api_key = $ai_settings['qwen_openrouter_api_key'] ?? '';
  $model = $ai_settings['qwen_model'] ?? 'qwen/qwen-2.5-coder-32b-instruct';
  $endpoint = $ai_settings['qwen_endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
  
  if (empty($api_key)) {
    ISO42K_Logger::log('❌ Qwen OpenRouter API key not configured');
    wp_send_json_error(['message' => 'Qwen (OpenRouter) API key is not configured. Please enter your API key and save settings first.']);
    return;
  }
  
  $result = self::test_ai_provider_connection($api_key, $model, $endpoint, 'Qwen (via OpenRouter)', true);
  
  if ($result['success']) {
    wp_send_json_success(['message' => $result['message']]);
  } else {
    wp_send_json_error(['message' => $result['message']]);
  }
}

/**
 * Handle Grok (via OpenRouter) connection test
 */
public static function handle_test_grok() {
  if (!check_ajax_referer('iso42k_admin_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'Security check failed']);
    return;
  }
  
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
    return;
  }
  
  ISO42K_Logger::log('=== Grok (OpenRouter) Connection Test Started ===');
  
  $ai_settings = get_option('iso42k_ai_settings', []);
  $api_key = $ai_settings['grok_openrouter_api_key'] ?? '';
  $model = $ai_settings['grok_model'] ?? 'x-ai/grok-beta';
  $endpoint = $ai_settings['grok_endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
  
  if (empty($api_key)) {
    ISO42K_Logger::log('❌ Grok OpenRouter API key not configured');
    wp_send_json_error(['message' => 'Grok (OpenRouter) API key is not configured. Please enter your API key and save settings first.']);
    return;
  }
  
  $result = self::test_ai_provider_connection($api_key, $model, $endpoint, 'Grok (via OpenRouter)', true);
  
  if ($result['success']) {
    wp_send_json_success(['message' => $result['message']]);
  } else {
    wp_send_json_error(['message' => $result['message']]);
  }
}

/**
 * Generic AI provider connection test helper
 */
private static function test_ai_provider_connection($api_key, $model, $endpoint, $provider_name, $is_openrouter = false) {
  ISO42K_Logger::log("Testing {$provider_name}");
  ISO42K_Logger::log("Endpoint: {$endpoint}");
  ISO42K_Logger::log("Model: {$model}");
  ISO42K_Logger::log("API Key length: " . strlen($api_key));
  
  $request_body = [
    'model' => $model,
    'messages' => [
      [
        'role' => 'user',
        'content' => 'Respond with only the word "connected" if you receive this test message.'
      ]
    ],
    'max_tokens' => 10,
    'temperature' => 0.1
  ];
  
  $headers = [
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $api_key,
  ];
  
  // Add OpenRouter-specific headers
  if ($is_openrouter) {
    $headers['HTTP-Referer'] = home_url();
    $headers['X-Title'] = 'ISO 42001 Gap Analysis Tool';
  }
  
  $args = [
    'headers' => $headers,
    'body' => json_encode($request_body),
    'timeout' => 30,
    'sslverify' => true
  ];
  
  ISO42K_Logger::log("Making API request...");
  
  $response = wp_remote_post($endpoint, $args);
  
  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    ISO42K_Logger::log("❌ HTTP Error: {$error_message}");
    
    if (strpos($error_message, 'cURL error') !== false) {
      $error_message .= ' This may be a network or SSL certificate issue.';
    }
    
    return [
      'success' => false,
      'message' => "Connection failed: {$error_message}"
    ];
  }
  
  $response_code = wp_remote_retrieve_response_code($response);
  $response_body = wp_remote_retrieve_body($response);
  
  ISO42K_Logger::log("Response code: {$response_code}");
  ISO42K_Logger::log("Response body (first 500 chars): " . substr($response_body, 0, 500));
  
  if ($response_code === 200) {
    $data = json_decode($response_body, true);
    
    if (isset($data['choices'][0]['message']['content'])) {
      $content = trim($data['choices'][0]['message']['content']);
      ISO42K_Logger::log("✅ Success! AI responded: {$content}");
      
      return [
        'success' => true,
        'message' => "✅ {$provider_name} is working correctly! AI responded: \"{$content}\""
      ];
    } else {
      $error_details = '';
      if (isset($data['error']['message'])) {
        $error_details = ': ' . $data['error']['message'];
      }
      ISO42K_Logger::log("❌ Unexpected response format{$error_details}");
      
      return [
        'success' => false,
        'message' => "API responded but with unexpected format{$error_details}. Check debug logs for details."
      ];
    }
  } else {
    $data = json_decode($response_body, true);
    $error_msg = "API Error (HTTP {$response_code})";
    
    if (isset($data['error']['message'])) {
      $error_msg .= ": " . $data['error']['message'];
    } elseif (isset($data['message'])) {
      $error_msg .= ": " . $data['message'];
    } else {
      $error_msg .= ". Check your API key and endpoint configuration.";
    }
    
    ISO42K_Logger::log("❌ {$error_msg}");
    
    return [
      'success' => false,
      'message' => $error_msg
    ];
  }
}

    /**
     * Get a single question
     */
    public static function handle_get_question() {
        self::verify_nonce();

        if (!isset($_POST['index']) || !isset($_POST['staff'])) {
            wp_send_json_error(['message' => 'Missing required parameters: index, staff'], 400);
        }

        $index = intval($_POST['index']);
        $staff = intval($_POST['staff']);

        if ($index < 0 || $staff <= 0) {
            wp_send_json_error(['message' => 'Invalid parameters'], 400);
        }

        if (!class_exists('ISO42K_Questions')) {
            wp_send_json_error(['message' => 'Questions class not found'], 500);
        }

        $questions = ISO42K_Questions::get_all();
        $total = count($questions);

        if ($index < 0 || $index >= $total) {
            wp_send_json_error(['message' => 'Invalid question index'], 400);
        }

        wp_send_json_success([
            'question' => $questions[$index],
            'total' => $total,
            'index' => $index
        ]);
    }

    /**
     * Get multiple questions in a batch
     */
    public static function handle_get_questions_batch() {
        self::verify_nonce();

        if (!isset($_POST['start_index']) || !isset($_POST['count']) || !isset($_POST['staff'])) {
            wp_send_json_error(['message' => 'Missing required parameters: start_index, count, staff'], 400);
        }

        $start_index = intval($_POST['start_index']);
        $count = min(intval($_POST['count']), 10); // Limit batch size to prevent overload
        $staff = intval($_POST['staff']);

        if ($start_index < 0 || $count <= 0 || $staff <= 0) {
            wp_send_json_error(['message' => 'Invalid parameters'], 400);
        }

        if (!class_exists('ISO42K_Questions')) {
            wp_send_json_error(['message' => 'Questions class not found'], 500);
        }

        $questions = ISO42K_Questions::get_all();
        $total = count($questions);

        // Ensure requested range is valid
        $end_index = min($start_index + $count, $total);
        $batch_questions = array_slice($questions, $start_index, $end_index - $start_index);

        wp_send_json_success([
            'questions' => $batch_questions,
            'total' => $total,
            'start_index' => $start_index,
            'end_index' => $end_index,
            'requested_count' => $count,
            'actual_count' => count($batch_questions)
        ]);
    }

    /**
     * Track assessment start
     */
    public static function handle_track_start() {
        self::verify_nonce();
        
        // Verify that required data is present
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'Security token missing'], 400);
        }
        
        // Optional: Track assessment starts for analytics
        ISO42K_Logger::log('Assessment started');
        
        wp_send_json_success(['message' => 'Tracked']);
    }

    /**
     * Test email configuration (admin only)
     */
    public static function handle_test_email() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        self::verify_nonce();

        $test_email = sanitize_email($_POST['test_email'] ?? '');
        
        if (!class_exists('ISO42K_Email')) {
            wp_send_json_error(['message' => 'Email class not found'], 500);
        }

        $result = ISO42K_Email::test_config($test_email);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test admin email configuration
     */
    public static function handle_test_admin_email() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        self::verify_nonce();

        if (!class_exists('ISO42K_Email')) {
            wp_send_json_error(['message' => 'Email class not found'], 500);
        }

        $result = ISO42K_Email::test_admin_notification();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Trigger background processing via Ajax
     */
    private static function trigger_background_processing($lead_id, $email) {
        try {
            // Prepare data for background processing
            $data = [
                'action' => 'iso42k_process_background',
                'lead_id' => $lead_id,
                'nonce' => wp_create_nonce('iso42k_assessment_nonce')
            ];

            // Use WordPress HTTP API to trigger background processing
            $response = wp_remote_post(
                admin_url('admin-ajax.php'),
                [
                    'method' => 'POST',
                    'timeout' => 0.01, // Very short timeout to make this truly async
                    'blocking' => false,
                    'sslverify' => false, // Required for local environments
                    'body' => $data
                ]
            );

            if (is_wp_error($response)) {
                ISO42K_Logger::log('Background processing trigger failed: ' . $response->get_error_message());
                return false;
            }

            return true;
        } catch (Exception $e) {
            ISO42K_Logger::log('Background processing trigger exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle nonce refresh request
     */
    public static function handle_refresh_nonce() {
        // Don't verify the old nonce here since it might be expired
        // Just create and return a new one
        
        wp_send_json_success([
            'nonce' => wp_create_nonce('iso42k_assessment_nonce')
        ]);
    }
} // End of class ISO42K_Ajax

} // ✅ End of class_exists wrapper



// Removed duplicate init call - already called in main plugin file
// ISO42K_Ajax::init();