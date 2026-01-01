<?php
if (!defined('ABSPATH')) exit;

/**
 * ISO42K_AI
 * AI-powered gap analysis integration for ISO/IEC 42001:2023 (AIMS)
 * * @version 1.0.0
 * * Note: This class generates structured AI content in 7 sections specifically tailored
 * for Artificial Intelligence Management Systems (AIMS).
 */

// Register email settings
register_setting('iso42k_email_group', 'iso42k_email_settings');

class ISO42K_AI {

    /**
     * Main analysis function - generates gap analysis from answers
     * * @param array $answers Array of answers (A/B/C)
     * @return array ['content' => string, 'is_ai_generated' => bool]
     */
public static function analyse(array $answers): array {
        if (empty($answers)) {
            ISO42K_Logger::log('AI: No answers provided');
            return [
                'content' => self::get_predefined_analysis('Initial', 0),
                'is_ai_generated' => false
            ];
        }

        ISO42K_Logger::log('AI: Starting analysis for ' . count($answers) . ' answers');

        $settings = (array) get_option('iso42k_ai_settings', []);
        
        // Try DeepSeek first
        if (!empty($settings['deepseek_api_key'])) {
            $provider = 'deepseek';
            $api_key = $settings['deepseek_api_key'] ?? '';
            $endpoint = !empty($settings['deepseek_endpoint']) ? $settings['deepseek_endpoint'] : 'https://api.deepseek.com/v1/chat/completions';
            $model = !empty($settings['deepseek_model']) ? $settings['deepseek_model'] : 'deepseek-chat';
            
            $result = self::make_api_request($provider, $api_key, $endpoint, $model, $answers);
            
            if ($result['is_ai_generated']) {
                return $result;
            }
            
            ISO42K_Logger::log('AI: DeepSeek failed, trying Qwen via OpenRouter');
        }
        
        // Try Qwen via OpenRouter
        if (!empty($settings['qwen_openrouter_api_key'])) {
            $provider = 'openrouter';
            $api_key = $settings['qwen_openrouter_api_key'] ?? '';
            $endpoint = !empty($settings['qwen_endpoint']) ? $settings['qwen_endpoint'] : 'https://openrouter.ai/api/v1/chat/completions';
            $model = !empty($settings['qwen_model']) ? $settings['qwen_model'] : 'qwen/qwen-2.5-coder-32b-instruct';
            
            $result = self::make_api_request($provider, $api_key, $endpoint, $model, $answers);
            
            if ($result['is_ai_generated']) {
                return $result;
            }
            
            ISO42K_Logger::log('AI: Qwen via OpenRouter failed, trying Grok via OpenRouter');
        }
        
        // Try xAI: Grok via OpenRouter
        if (!empty($settings['grok_openrouter_api_key'])) {
            $provider = 'openrouter';
            $api_key = $settings['grok_openrouter_api_key'] ?? '';
            $endpoint = !empty($settings['grok_endpoint']) ? $settings['grok_endpoint'] : 'https://openrouter.ai/api/v1/chat/completions';
            $model = !empty($settings['grok_model']) ? $settings['grok_model'] : 'x-ai/grok-beta';
            
            $result = self::make_api_request($provider, $api_key, $endpoint, $model, $answers);
            
            if ($result['is_ai_generated']) {
                return $result;
            }
            
            ISO42K_Logger::log('AI: Grok via OpenRouter failed, using predefined content');
        }
        
        // If all providers failed, use predefined content
        $maturity_analysis = self::analyze_answers_for_maturity($answers);
        return [
            'content' => self::get_predefined_analysis($maturity_analysis['maturity'], $maturity_analysis['percent']),
            'is_ai_generated' => false
        ];
    }

    /**
     * Make API request to a specific provider
     */
    private static function make_api_request(string $provider, string $api_key, string $endpoint, string $model, array $answers): array {
        if (empty($api_key)) {
            $maturity_analysis = self::analyze_answers_for_maturity($answers);
            return [
                'content' => self::get_predefined_analysis($maturity_analysis['maturity'], $maturity_analysis['percent']),
                'is_ai_generated' => false
            ];
        }

        // Build ISO 42001 specific prompt
        $prompt = self::build_analysis_prompt($answers);
        
        if (empty($prompt)) {
            $maturity_analysis = self::analyze_answers_for_maturity($answers);
            return [
                'content' => self::get_predefined_analysis($maturity_analysis['maturity'], $maturity_analysis['percent']),
                'is_ai_generated' => false
            ];
        }
        
        $request_body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an ISO/IEC 42001:2023 Lead Auditor and AI Governance Expert. Provide a professional gap analysis in a clear, structured format with exactly these 7 sections: 1) Key Insights, 2) Overview, 3) Current State, 4) Risk Implications, 5) Top Gaps, 6) Recommendations, 7) Quick Win Actions. Your focus is on AI Management Systems (AIMS), specifically addressing AI risk, data quality, human oversight, and algorithmic transparency. Write in plain text without markdown.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2500,
            'temperature' => 0.7,
            'stream' => false
        ];

        // ... (Header and Request logic remains identical to original class) ...
        // Note: Reusing the standard wp_remote_post logic from your original file for brevity
        // Ensure you copy the full HTTP request block here from ISO42K_AI
        
        // Placeholder for the HTTP request logic to save space in this view:
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];
        
        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = home_url();
            $headers['X-Title'] = 'ISO 42001 Gap Analysis Tool';
        }

        $args = [
            'headers' => $headers,
            'body' => wp_json_encode($request_body),
            'timeout' => 45,
            'sslverify' => true,
            'httpversion' => '1.1',
        ];

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $maturity_analysis = self::analyze_answers_for_maturity($answers);
            return [
                'content' => self::get_predefined_analysis($maturity_analysis['maturity'], $maturity_analysis['percent']),
                'is_ai_generated' => false
            ];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $raw_content = trim($data['choices'][0]['message']['content'] ?? '');
        
        if (empty($raw_content)) {
            $maturity_analysis = self::analyze_answers_for_maturity($answers);
            return [
                'content' => self::get_predefined_analysis($maturity_analysis['maturity'], $maturity_analysis['percent']),
                'is_ai_generated' => false
            ];
        }

        return [
            'content' => $raw_content,
            'is_ai_generated' => true
        ];
    }

    /**
     * Build enhanced analysis prompt based on answers
     */
    private static function build_analysis_prompt(array $answers): string {
        $total = count($answers);
        if ($total === 0) return '';
        
        $a_count = 0; $b_count = 0; $c_count = 0;

        // Load ISO 42001 questions
        // Ensure you have defined this constant to point to your new questions.php
        $questions = include DUO_ISO42K_PATH . 'data/questions.php';
        
        $detailed_responses = "DETAILED ISO 42001 QUESTION-ANSWER PAIRS:\n";
        foreach ($answers as $index => $answer) {
            if (isset($questions[$index])) {
                $question = $questions[$index];
                $ans = strtoupper(trim($answer));
                
                if ($ans === 'A') $a_count++;
                elseif ($ans === 'B') $b_count++;
                else $c_count++;
                
                $answer_meaning = ($ans === 'A') ? 'Fully Implemented' : (($ans === 'B') ? 'Partially Implemented' : 'Not Implemented');
                
                $detailed_responses .= "Q{$question['id']} ({$question['theme']}): {$question['text']}\n";
                $detailed_responses .= "Status: {$answer_meaning} ({$ans})\n\n";
            }
        }

        $score_percent = $total > 0 ? round((($a_count * 10 + $b_count * 6) / ($total * 10)) * 100) : 0;

        $maturity = 'Initial';
        if ($score_percent >= 75) $maturity = 'Optimised';
        elseif ($score_percent >= 50) $maturity = 'Established';
        elseif ($score_percent >= 25) $maturity = 'Managed';

        $answer_summary = "Total Questions: {$total}\n";
        $answer_summary .= "Fully Implemented (A): {$a_count}\n";
        $answer_summary .= "Partially Implemented (B): {$b_count}\n";
        $answer_summary .= "Not Implemented (C): {$c_count}\n";
        $answer_summary .= "AIMS Maturity Score: {$score_percent}%\n";

        $prompt = <<<PROMPT
You are an expert in ISO/IEC 42001:2023 (Artificial Intelligence Management System). Analyze the following self-assessment responses to provide a gap analysis.

ASSESSMENT METRICS:
{$answer_summary}
Maturity Level: {$maturity}

{$detailed_responses}

REQUIRED SECTIONS:
1) Key Insights
Analyze the AIMS posture. Highlight strengths in AI governance and concerns regarding AI risk management, data quality, or lifecycle controls.

2) Overview
Summarize findings across themes (Leadership, Planning, Operation, Support). Identify if the organization treats AI as a standard IT issue (incorrect) or manages specific AI risks like bias, explainability, and autonomy (correct).

3) Current State
Describe the existing AI governance posture. Which controls are working? Are AI policies defined? Is there human oversight?

4) Risk Implications
Explain risks from the identified gaps (specifically B and C answers). Focus on:
- Ethical risks (bias, discrimination)
- Operational risks (model drift, hallucinations)
- Compliance risks (regulatory penalties)
- Reputational risks (loss of trust)

5) Top Gaps
List the 5 most critical ISO 42001 gaps. Prioritize by severity. Example: "Lack of AI System Impact Assessment" or "Undefined Data Provenance."

6) Recommendations
Provide 5 actionable steps to address the top gaps. Reference specific ISO 42001 clauses or Annex A controls (e.g., A.6.2.4 Verification and Validation).

7) Quick Win Actions
List 5 immediate, low-effort actions (e.g., "Draft an AI Policy," "Create an AI System Inventory," "Assign AI Management roles").

FORMATTING RULES:
- Use exact section titles.
- Plain text only (no markdown).
- Professional tone.
- Separate sections with double line breaks.

Generate the analysis now:
PROMPT;

        return $prompt;
    }

    /**
     * Analyze answers for maturity scoring
     */
    private static function analyze_answers_for_maturity(array $answers): array {
        $total = count($answers);
        if ($total === 0) return ['percent' => 0, 'maturity' => 'Initial'];
        
        $a_count = 0; $b_count = 0; 
        foreach ($answers as $answer) {
            $ans = strtoupper(trim($answer));
            if ($ans === 'A') $a_count++;
            elseif ($ans === 'B') $b_count++;
        }

        $score_percent = round((($a_count * 10 + $b_count * 6) / ($total * 10)) * 100);

        $maturity = 'Initial';
        if ($score_percent >= 75) $maturity = 'Optimised';
        elseif ($score_percent >= 50) $maturity = 'Established';
        elseif ($score_percent >= 25) $maturity = 'Managed';

        return ['percent' => $score_percent, 'maturity' => $maturity];
    }

    /**
     * Get predefined analysis based on ISO 42001 maturity
     * Adapted specifically for AI Management Systems
     */
    private static function get_predefined_analysis(string $maturity, int $percent): string {
        $analysis = "1) Key Insights\n";
        
        if ($maturity === 'Initial') {
            $analysis .= "Your organization's AI maturity is at the {$maturity} level ({$percent}%). This suggests AI usage may be occurring in 'shadow' pockets without formal governance, policy, or risk assessment. The priority is establishing an AI Policy and determining the scope of your AI Management System.\n\n";
        } elseif ($maturity === 'Managed') {
            $analysis .= "Your organization's AI maturity is {$maturity} ({$percent}%). You likely have some AI guidelines, but they are not consistently applied across the AI lifecycle. Risk assessments may be generic rather than AI-specific (missing bias or explainability checks).\n\n";
        } elseif ($maturity === 'Established') {
            $analysis .= "Your organization's AI maturity is {$maturity} ({$percent}%). You have a defined AIMS with impact assessments and data governance. The priority is now on continuous monitoring of models in production and managing third-party AI suppliers.\n\n";
        } else {
            $analysis .= "Your organization is {$maturity} ({$percent}%) in AI governance. You have robust controls for ethical AI, data provenance, and automated compliance. The focus should be on advanced auditing and adapting to new AI regulations.\n\n";
        }

        $analysis .= "2) Overview\n";
        $analysis .= "Based on your responses, there are gaps in how AI specific risks are handled. Unlike standard IT, AI requires continuous monitoring for model drift, bias, and data quality. Your current setup may treat AI too similarly to standard software.\n\n";

        $analysis .= "3) Current State\n";
        if ($maturity === 'Initial') {
            $analysis .= "AI activities are likely ad-hoc. There is no central register of AI systems, and roles for 'AI Oversight' are undefined. Data used for training or fine-tuning is likely not tracked for provenance.\n\n";
        } elseif ($maturity === 'Managed') {
            $analysis .= "Basic AI controls exist but are manual. You may have an AI policy, but developers or users might bypass it. Impact assessments are performed occasionally but not systematically for every new AI deployment.\n\n";
        } else {
            $analysis .= "A formal AIMS is in place. AI risks are documented, and data quality checks are integrated into the development pipeline. Human oversight mechanisms are defined.\n\n";
        }

        $analysis .= "4) Risk Implications\n";
        $analysis .= "The primary risks identified include: 1) Unintended bias or discrimination in AI outputs leading to reputational damage. 2) Lack of explainability making it difficult to debug errors. 3) 'Black box' risks where decision-making logic is unknown. 4) Regulatory non-compliance with emerging AI laws.\n\n";

        $analysis .= "5) Top Gaps\n";
        if ($maturity === 'Initial') {
            $analysis .= "1. Lack of a formal AI Policy (Clause 5.2)\n";
            $analysis .= "2. Undefined AI Risk Assessment criteria (Clause 6.1.2)\n";
            $analysis .= "3. No AI System Impact Assessment process (Clause 6.1.4)\n";
            $analysis .= "4. Missing data quality and provenance controls (Annex A.7)\n";
            $analysis .= "5. Undefined roles for AI human oversight (Annex A.9)\n\n";
        } else {
            $analysis .= "1. Incomplete AI system inventory and classification\n";
            $analysis .= "2. Inconsistent monitoring of AI models in production (Drift)\n";
            $analysis .= "3. Third-party AI supplier due diligence gaps\n";
            $analysis .= "4. Insufficient transparency/documentation for users\n";
            $analysis .= "5. Lack of formal model validation procedures\n\n";
        }

        $analysis .= "6) Recommendations\n";
        $analysis .= "1. Establish a governing body or committee responsible for AI.\n";
        $analysis .= "2. Develop and publish an AI Policy covering ethical principles and risk tolerance.\n";
        $analysis .= "3. Implement an AI Impact Assessment (AIA) for all new projects.\n";
        $analysis .= "4. Create a data governance framework specifically for training/testing data.\n";
        $analysis .= "5. Define clear 'Human-in-the-loop' protocols for critical AI decisions.\n\n";

        $analysis .= "7) Quick Win Actions\n";
        $analysis .= "1. Create an inventory of all AI tools currently in use.\n";
        $analysis .= "2. Draft a simple Acceptable Use Policy for AI (e.g., ChatGPT usage).\n";
        $analysis .= "3. Assign a specific owner for AI compliance.\n";
        $analysis .= "4. Require vendors to disclose if AI is used in their products.\n";
        $analysis .= "5. Conduct a basic AI literacy training session for staff.\n";

        return $analysis;
    }

    /**
     * Test connection for a specific AI provider
     * @param string $provider The provider to test ('deepseek', 'qwen', 'grok')
     * @return array ['success' => bool, 'message' => string]
     */
    public static function test_connection(string $provider): array {
        $settings = (array) get_option('iso42k_ai_settings', []);
        
        // Log raw settings for debugging
        ISO42K_Logger::log("Raw settings retrieved from database:");
        ISO42K_Logger::log(print_r($settings, true));
        
        // Configure provider settings
        $config = self::get_provider_config($provider, $settings);
        
        if (!$config) {
            return [
                'success' => false,
                'message' => 'Invalid provider specified'
            ];
        }
        
        // Log the config being used
        ISO42K_Logger::log("Provider config after get_provider_config:");
        ISO42K_Logger::log(print_r($config, true));
        
        // Check if API key is configured
        if (empty($config['api_key'])) {
            return [
                'success' => false,
                'message' => ucfirst($provider) . ' API key is not configured. Please enter your API key in the settings.'
            ];
        }
        
        // Check if endpoint is valid
        if (empty($config['endpoint']) || !filter_var($config['endpoint'], FILTER_VALIDATE_URL)) {
            ISO42K_Logger::log("❌ Invalid endpoint: " . ($config['endpoint'] ?? 'empty'));
            return [
                'success' => false,
                'message' => 'Invalid or missing endpoint URL. Please check the endpoint configuration.'
            ];
        }
        
        ISO42K_Logger::log("Testing {$provider} connection...");
        ISO42K_Logger::log("Endpoint: {$config['endpoint']}");
        ISO42K_Logger::log("Model: {$config['model']}");
        
        // Build test request
        $request_body = [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Reply with: Connection test successful'
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.1
        ];
        
        // Build headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $config['api_key'],
        ];
        
        // Add OpenRouter specific headers
        if ($config['provider_type'] === 'openrouter') {
            $headers['HTTP-Referer'] = home_url();
            $headers['X-Title'] = 'ISO 42001 Gap Analysis Tool';
        }
        
        $args = [
            'headers' => $headers,
            'body' => wp_json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true,
            'httpversion' => '1.1',
        ];
        
        // Make the API request
        $start_time = microtime(true);
        $response = wp_remote_post($config['endpoint'], $args);
        $latency_ms = round((microtime(true) - $start_time) * 1000);
        
        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            ISO42K_Logger::log("❌ Connection failed: {$error_message}");
            return [
                'success' => false,
                'message' => "Connection failed: {$error_message}"
            ];
        }
        
        // Check HTTP status code
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        ISO42K_Logger::log("HTTP Status: {$status_code}");
        ISO42K_Logger::log("Latency: {$latency_ms}ms");
        
        if ($status_code !== 200) {
            // Try to parse error message from response
            $data = json_decode($response_body, true);
            $error_msg = $data['error']['message'] ?? $data['message'] ?? "HTTP {$status_code}";
            
            // Provide helpful error messages
            if ($status_code === 401) {
                $error_msg = "Invalid API key. Please check your API key is correct.";
            } elseif ($status_code === 403) {
                $error_msg = "Access forbidden. Please verify your API key has the correct permissions.";
            } elseif ($status_code === 404) {
                $error_msg = "Endpoint not found. Please check your endpoint URL and model name.";
            } elseif ($status_code === 429) {
                $error_msg = "Rate limit exceeded. Please wait a moment and try again.";
            } elseif ($status_code === 500 || $status_code === 502 || $status_code === 503) {
                $error_msg = "Provider server error. The API service may be temporarily unavailable.";
            }
            
            ISO42K_Logger::log("❌ Test failed: {$error_msg}");
            ISO42K_Logger::log("Response body: " . substr($response_body, 0, 500));
            
            return [
                'success' => false,
                'message' => $error_msg
            ];
        }
        
        // Parse response
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ISO42K_Logger::log("❌ Invalid JSON response");
            return [
                'success' => false,
                'message' => "Invalid response from API (JSON parse error)"
            ];
        }
        
        // Check if we got a valid response with content
        $content = $data['choices'][0]['message']['content'] ?? '';
        
        if (empty($content)) {
            ISO42K_Logger::log("❌ Empty response content");
            return [
                'success' => false,
                'message' => "API returned empty response"
            ];
        }
        
        ISO42K_Logger::log("✅ Connection successful!");
        ISO42K_Logger::log("Response preview: " . substr($content, 0, 100));
        
        return [
            'success' => true,
            'message' => "Connection successful! Latency: {$latency_ms}ms. Model: {$config['model']}"
        ];
    }
    
    /**
     * Get provider configuration
     * @param string $provider Provider name
     * @param array $settings Settings array
     * @return array|null Configuration array or null if invalid
     */
    private static function get_provider_config(string $provider, array $settings): ?array {
        switch ($provider) {
            case 'deepseek':
                $endpoint = !empty($settings['deepseek_endpoint']) ? $settings['deepseek_endpoint'] : 'https://api.deepseek.com/v1/chat/completions';
                $model = !empty($settings['deepseek_model']) ? $settings['deepseek_model'] : 'deepseek-chat';
                return [
                    'provider_type' => 'deepseek',
                    'api_key' => $settings['deepseek_api_key'] ?? '',
                    'endpoint' => $endpoint,
                    'model' => $model,
                ];
                
            case 'qwen':
                $endpoint = !empty($settings['qwen_endpoint']) ? $settings['qwen_endpoint'] : 'https://openrouter.ai/api/v1/chat/completions';
                $model = !empty($settings['qwen_model']) ? $settings['qwen_model'] : 'qwen/qwen-2.5-coder-32b-instruct';
                return [
                    'provider_type' => 'openrouter',
                    'api_key' => $settings['qwen_openrouter_api_key'] ?? '',
                    'endpoint' => $endpoint,
                    'model' => $model,
                ];
                
            case 'grok':
                $endpoint = !empty($settings['grok_endpoint']) ? $settings['grok_endpoint'] : 'https://openrouter.ai/api/v1/chat/completions';
                $model = !empty($settings['grok_model']) ? $settings['grok_model'] : 'x-ai/grok-beta';
                return [
                    'provider_type' => 'openrouter',
                    'api_key' => $settings['grok_openrouter_api_key'] ?? '',
                    'endpoint' => $endpoint,
                    'model' => $model,
                ];
                
            default:
                return null;
        }
    }
}
