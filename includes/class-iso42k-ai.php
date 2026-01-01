<?php
if (!defined('ABSPATH')) exit;

/**
 * ISO42K_AI
 * AI-powered gap analysis integration for EcoVadis Sustainability Assessment
 * * @version 2.0.0
 * * Note: This class generates structured AI content in 7 sections specifically tailored
 * for EcoVadis sustainability and CSR assessment framework.
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
                    'content' => 'You are an EcoVadis sustainability and CSR expert. Provide a professional gap analysis in a clear, structured format with exactly these 7 sections: 1) Key Insights, 2) Overview, 3) Current State, 4) Risk Implications, 5) Top Gaps, 6) Recommendations, 7) Quick Win Actions. Your focus is on sustainability across Environment, Labor & Human Rights, Ethics, and Sustainable Procurement themes. Write in plain text without markdown.'
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

        // Load EcoVadis questions with company size detection
        $company_size = 'small';
        if ($total >= 25) {
            $company_size = 'large';
        } elseif ($total > 20) {
            $company_size = 'large'; // Has procurement questions
        }
        
        $questions_loader = include DUO_ISO42K_PATH . 'data/questions.php';
        if (is_callable($questions_loader)) {
            $questions = $questions_loader($company_size);
        } else {
            $questions = $questions_loader;
        }
        
        $detailed_responses = "DETAILED ECOVADIS QUESTION-ANSWER PAIRS:\n";
        foreach ($answers as $index => $answer) {
            $question = null;
            
            // Try to find question by ID or index
            foreach ($questions as $q) {
                if ($q['id'] === $index || array_search($q, $questions) == $index) {
                    $question = $q;
                    break;
                }
            }
            
            if ($question) {
                $ans = strtoupper(trim($answer));
                
                if ($ans === 'A') $a_count++;
                elseif ($ans === 'B') $b_count++;
                else $c_count++;
                
                $answer_meaning = ($ans === 'A') ? 'Advanced - Fully Implemented' : (($ans === 'B') ? 'Basic - Partially Implemented' : 'Absent - Not Implemented');
                
                $detailed_responses .= "{$question['id']} ({$question['theme']} - {$question['indicator']}): {$question['question']}\n";
                $detailed_responses .= "Status: {$answer_meaning} ({$ans})\n";
                $detailed_responses .= "Impact Level: " . ucfirst($question['impact']) . "\n\n";
            }
        }

        // Calculate weighted score
        $max_weighted_score = 0;
        $total_weighted_score = 0;
        
        foreach ($questions as $index => $question) {
            $impact = $question['impact'] ?? 'medium';
            $weight = ($impact === 'high') ? 1.5 : (($impact === 'low') ? 0.5 : 1.0);
            $max_weighted_score += (100 * $weight);
            
            $answer = null;
            if (isset($answers[$question['id']])) {
                $answer = strtoupper(trim($answers[$question['id']]));
            } elseif (isset($answers[$index])) {
                $answer = strtoupper(trim($answers[$index]));
            }
            
            if ($answer === 'A') {
                $total_weighted_score += (100 * $weight);
            } elseif ($answer === 'B') {
                $total_weighted_score += (50 * $weight);
            }
        }
        
        $score_percent = $max_weighted_score > 0 ? round(($total_weighted_score / $max_weighted_score) * 100) : 0;

        $maturity = 'Initial';
        if ($score_percent >= 86) $maturity = 'Leading';
        elseif ($score_percent >= 71) $maturity = 'Advanced';
        elseif ($score_percent >= 51) $maturity = 'Established';
        elseif ($score_percent >= 31) $maturity = 'Developing';

        $answer_summary = "Total Questions: {$total}\n";
        $answer_summary .= "Advanced Implementation (A): {$a_count}\n";
        $answer_summary .= "Basic Implementation (B): {$b_count}\n";
        $answer_summary .= "Not Implemented (C): {$c_count}\n";
        $answer_summary .= "EcoVadis Maturity Score: {$score_percent}%\n";

        $prompt = <<<PROMPT
You are an expert in sustainability and CSR assessment frameworks, specializing in EcoVadis methodology. Analyze the following self-assessment responses to provide a gap analysis.

ASSESSMENT METRICS:
{$answer_summary}
Maturity Level: {$maturity}

{$detailed_responses}

REQUIRED SECTIONS:

Key Insights
Analyze the organization's sustainability posture. Highlight strengths in governance and policy implementation, and identify critical concerns regarding environmental management, labor practices, ethical compliance, or supply chain sustainability.

Overview
Summarize findings across themes (General, Environment, Labor & Human Rights, Ethics, and if applicable, Sustainable Procurement). Identify if the organization approaches sustainability as a compliance exercise (reactive) or as a strategic, integrated business function (proactive).

Current State
Describe the existing sustainability management system. Which controls and practices are effectively implemented? Are policies comprehensive and operational? Is there measurable performance tracking and reporting?

Risk Implications
Explain the business risks arising from identified gaps (specifically B and C answers). Focus on:
- Environmental risks (regulatory fines, resource inefficiency, climate impact)
- Social risks (employee turnover, safety incidents, labor disputes, reputational damage)
- Governance risks (corruption incidents, data breaches, compliance failures)
- Supply chain risks (disruptions, non-compliance of suppliers, reputational contagion)

Top Gaps
List the 5 most critical sustainability management gaps. Prioritize by potential impact on score, risk severity, and alignment with stakeholder expectations. Example: "Absence of a comprehensive GHG emissions inventory" or "Lack of a formal supplier code of conduct."

Recommendations
Provide 5 actionable, strategic steps to address the top gaps and improve maturity. Reference specific thematic areas and indicators (e.g., "Develop and implement a formal Energy & GHG Reduction Policy per ENV100," "Establish a comprehensive DEI program as per LAB365").

Quick Win Actions
List 5 immediate, practical actions that can yield rapid improvements in score or risk posture with relatively low effort (e.g., "Formalize existing ad-hoc recycling into a written Waste Management program," "Document and communicate the existing health & safety practices as a formal policy," "Initiate tracking of key energy consumption metrics").

FORMATTING RULES:
- Use exact section titles.
- Plain text only (no markdown).
- Professional tone.
- Separate sections with double line breaks.

Generate the analysis now.
PROMPT;

        return $prompt;
    }

    /**
     * Analyze answers for maturity scoring (EcoVadis methodology)
     */
    private static function analyze_answers_for_maturity(array $answers): array {
        $total = count($answers);
        if ($total === 0) return ['percent' => 0, 'maturity' => 'Initial'];
        
        // Use EcoVadis scoring methodology
        $company_size = ($total > 20) ? 'large' : 'small';
        
        if (class_exists('ISO42K_Scoring')) {
            $result = ISO42K_Scoring::calculate_score($answers, $company_size);
            
            $maturity_map = [
                'initial' => 'Initial',
                'developing' => 'Developing',
                'established' => 'Established',
                'advanced' => 'Advanced',
                'leading' => 'Leading'
            ];
            
            $maturity = $maturity_map[$result['maturity_level']] ?? 'Initial';
            
            return [
                'percent' => $result['percentage'],
                'maturity' => $maturity
            ];
        }
        
        // Fallback calculation if scoring class not available
        $a_count = 0; $b_count = 0;
        foreach ($answers as $answer) {
            $ans = strtoupper(trim($answer));
            if ($ans === 'A') $a_count++;
            elseif ($ans === 'B') $b_count++;
        }

        // Simple weighted average: A=100, B=50, C=0
        $score_percent = round((($a_count * 100 + $b_count * 50) / ($total * 100)) * 100);

        $maturity = 'Initial';
        if ($score_percent >= 86) $maturity = 'Leading';
        elseif ($score_percent >= 71) $maturity = 'Advanced';
        elseif ($score_percent >= 51) $maturity = 'Established';
        elseif ($score_percent >= 31) $maturity = 'Developing';

        return ['percent' => $score_percent, 'maturity' => $maturity];
    }

    /**
     * Get predefined analysis based on EcoVadis maturity
     * Adapted specifically for Sustainability and CSR Assessment
     */
    private static function get_predefined_analysis(string $maturity, int $percent): string {
        $analysis = "Key Insights\n";
        
        if ($maturity === 'Initial') {
            $analysis .= "Your organization's sustainability maturity is at the {$maturity} level ({$percent}%). This suggests minimal sustainability practices with ad hoc or non-existent policies. The priority is establishing foundational sustainability policies and beginning systematic tracking of environmental and social metrics.\n\n";
        } elseif ($maturity === 'Developing') {
            $analysis .= "Your organization's sustainability maturity is {$maturity} ({$percent}%). You have a basic sustainability framework with partial policy implementation. The focus should be on strengthening existing initiatives and expanding coverage across all sustainability themes.\n\n";
        } elseif ($maturity === 'Established') {
            $analysis .= "Your organization's sustainability maturity is {$maturity} ({$percent}%). You have systematic sustainability programs with comprehensive policies. The priority now is on continuous improvement, external verification, and supply chain engagement.\n\n";
        } elseif ($maturity === 'Advanced') {
            $analysis .= "Your organization is {$maturity} ({$percent}%) in sustainability. You have integrated sustainability strategy with industry-leading practices. Focus on innovation, thought leadership, and setting industry benchmarks.\n\n";
        } else {
            $analysis .= "Your organization is {$maturity} ({$percent}%) in sustainability. You have sustainability deeply embedded in your business model with full transparency and third-party verification. Continue driving innovation and influencing your broader value chain.\n\n";
        }

        $analysis .= "Overview\n";
        $analysis .= "Based on your responses, there are opportunities to strengthen sustainability management across Environment, Labor & Human Rights, Ethics, and Procurement themes. Leading organizations integrate sustainability into strategic decision-making rather than treating it as a compliance exercise.\n\n";

        $analysis .= "Current State\n";
        if ($maturity === 'Initial' || $maturity === 'Developing') {
            $analysis .= "Sustainability activities appear to be early-stage or reactive. Policies may exist on paper but lack consistent implementation. Metrics tracking is limited or non-existent. Employee engagement and training on sustainability topics needs development.\n\n";
        } else {
            $analysis .= "A formal sustainability management system is in place with documented policies and procedures. Regular monitoring and reporting occur, though there may be gaps in external verification or supply chain oversight. Continuous improvement processes are active.\n\n";
        }

        $analysis .= "Risk Implications\n";
        $analysis .= "Key risks identified include: 1) Environmental risks such as regulatory non-compliance, resource inefficiency, and climate-related impacts. 2) Social risks including employee safety incidents, labor disputes, and reputational damage. 3) Ethical risks involving corruption, data breaches, and compliance failures. 4) Supply chain risks from supplier non-compliance and potential disruptions.\n\n";

        $analysis .= "Top Gaps\n";
        if ($maturity === 'Initial' || $maturity === 'Developing') {
            $analysis .= "1. Absence of comprehensive environmental policy (energy, GHG, waste)\n";
            $analysis .= "2. Limited tracking of sustainability metrics and KPIs\n";
            $analysis .= "3. Lack of formal health and safety management system\n";
            $analysis .= "4. No external verification of sustainability reporting\n";
            $analysis .= "5. Missing supplier sustainability assessment process\n\n";
        } else {
            $analysis .= "1. Incomplete Scope 3 GHG emissions calculation\n";
            $analysis .= "2. Limited renewable energy adoption\n";
            $analysis .= "3. Diversity, equity, and inclusion programs need strengthening\n";
            $analysis .= "4. Supply chain sustainability engagement could be deeper\n";
            $analysis .= "5. External verification of sustainability data needed\n\n";
        }

        $analysis .= "Recommendations\n";
        $analysis .= "1. Develop a comprehensive sustainability strategy with board-level oversight and clear targets aligned with SDGs or Science-Based Targets.\n";
        $analysis .= "2. Implement robust environmental management including energy reduction, GHG inventory (Scopes 1, 2, 3), and waste management programs.\n";
        $analysis .= "3. Strengthen labor practices with comprehensive health & safety policies, regular training, and diversity and inclusion initiatives.\n";
        $analysis .= "4. Establish strong ethics framework with anti-corruption measures, data security, and whistleblower mechanisms.\n";
        $analysis .= "5. Develop supplier sustainability program with code of conduct, assessments, and capacity building.\n\n";

        $analysis .= "Quick Win Actions\n";
        $analysis .= "1. Document existing sustainability practices into formal written policies.\n";
        $analysis .= "2. Start tracking basic environmental metrics (energy, water, waste).\n";
        $analysis .= "3. Conduct employee sustainability awareness training.\n";
        $analysis .= "4. Request sustainability information from key suppliers.\n";
        $analysis .= "5. Assign clear responsibility for sustainability to a senior leader or committee.\n";

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
