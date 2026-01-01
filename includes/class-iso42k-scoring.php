<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EcoVadis Scoring Class
 * Implements EcoVadis-specific scoring methodology
 */
if (!class_exists('ISO42K_Scoring')) {

class ISO42K_Scoring {
    /**
     * Get all assessment questions (EcoVadis format)
     * @param string $company_size Company size (small/medium/large)
     * @return array Structured questions list
     */
    public static function get_questions($company_size = 'small') {
        // Determine if company is large (25+ staff)
        $is_large = ($company_size === 'large');
        
        // Load questions from the data file
        $questions_file = DUO_ISO42K_PATH . 'data/questions.php';
        
        if (!file_exists($questions_file)) {
            ISO42K_Logger::log('❌ Questions data file not found: ' . $questions_file);
            return [];
        }
        
        $questions_loader = require $questions_file;
        
        // The questions file returns a function that accepts company size
        if (is_callable($questions_loader)) {
            $questions = $questions_loader($company_size);
        } else {
            $questions = $questions_loader;
        }
        
        return $questions;
    }

    /**
     * Calculate score using EcoVadis methodology
     * @param array $answers Answers array
     * @param string $company_size Company size
     * @return array Score + maturity level
     */
    public static function calculate_score($answers, $company_size = 'small') {
        $questions = self::get_questions($company_size);
        $total_questions = count($questions);
        
        if ($total_questions === 0) {
            return [
                'percentage' => 0,
                'maturity_level' => 'initial',
                'total_questions' => 0,
                'total_score' => 0,
                'max_score' => 0
            ];
        }

        // EcoVadis scoring:
        // Option A: 100 points (Advanced)
        // Option B: 50 points (Basic)
        // Option C: 0 points (Absent/Initial)
        
        // Impact weighting:
        // High Impact: Multiply by 1.5
        // Medium Impact: Multiply by 1.0
        // Low Impact: Multiply by 0.5 (not present in provided questions)
        
        $total_weighted_score = 0;
        $max_weighted_score = 0;
        
        foreach ($questions as $index => $question) {
            $impact = $question['impact'] ?? 'medium';
            
            // Determine weight multiplier
            $weight = 1.0;
            if ($impact === 'high') {
                $weight = 1.5;
            } elseif ($impact === 'low') {
                $weight = 0.5;
            }
            
            // Maximum possible score for this question
            $max_weighted_score += (100 * $weight);
            
            // Get answer for this question
            $answer = null;
            $question_id = $question['id'];
            
            // Try to find answer by question ID
            if (isset($answers[$question_id])) {
                $answer = strtoupper(trim($answers[$question_id]));
            } else {
                // Try by index (0-based or 1-based)
                if (isset($answers[$index])) {
                    $answer = strtoupper(trim($answers[$index]));
                }
            }
            
            // Calculate weighted score for this question
            if ($answer === 'A') {
                $total_weighted_score += (100 * $weight);
            } elseif ($answer === 'B') {
                $total_weighted_score += (50 * $weight);
            }
            // Answer C or no answer = 0 points
        }

        // Calculate percentage
        $percentage = $max_weighted_score > 0 ? round(($total_weighted_score / $max_weighted_score) * 100, 0) : 0;
        
        // Determine maturity level
        $maturity_level = self::get_maturity_level($percentage);

        return [
            'percentage' => $percentage,
            'maturity_level' => $maturity_level,
            'total_questions' => $total_questions,
            'total_score' => round($total_weighted_score, 0),
            'max_score' => round($max_weighted_score, 0)
        ];
    }

    /**
     * Get maturity level based on EcoVadis score
     * @param int $percentage Percentage score
     * @return string Maturity level
     */
    public static function get_maturity_level($percentage) {
        // EcoVadis Maturity Levels:
        // Level 1: Initial (0-30%)
        // Level 2: Developing (31-50%)
        // Level 3: Established (51-70%)
        // Level 4: Advanced (71-85%)
        // Level 5: Leading (86-100%)
        
        if ($percentage >= 86) {
            return 'leading';
        }
        if ($percentage >= 71) {
            return 'advanced';
        }
        if ($percentage >= 51) {
            return 'established';
        }
        if ($percentage >= 31) {
            return 'developing';
        }
        return 'initial';
    }
    
    /**
     * Get maturity level description
     * @param string $level Maturity level
     * @return string Description
     */
    public static function get_maturity_description($level) {
        $descriptions = [
            'initial' => 'Minimal sustainability practices. Ad hoc or non-existent policies. No tracking or reporting. Reactive approach only.',
            'developing' => 'Basic sustainability framework. Partial policy implementation. Internal tracking only. Some initiatives in high-impact areas.',
            'established' => 'Systematic sustainability program. Comprehensive policies implemented. Regular tracking with some external verification. Proactive initiatives across most themes.',
            'advanced' => 'Integrated sustainability strategy. Fully implemented with continuous improvement. Externally verified reporting. Industry-leading practices.',
            'leading' => 'Sustainability embedded in business model. Exceeds regulatory requirements. Full transparency with third-party verification. Innovative solutions and supply chain engagement.'
        ];
        
        return $descriptions[$level] ?? 'Unknown maturity level';
    }
    
    /**
     * Calculate theme-specific scores
     * @param array $answers Answers array
     * @param string $company_size Company size
     * @return array Theme scores
     */
    public static function calculate_theme_scores($answers, $company_size = 'small') {
        $questions = self::get_questions($company_size);
        $theme_scores = [];
        
        foreach ($questions as $index => $question) {
            $theme = $question['theme'];
            $impact = $question['impact'] ?? 'medium';
            
            // Initialize theme if not exists
            if (!isset($theme_scores[$theme])) {
                $theme_scores[$theme] = [
                    'total_weighted_score' => 0,
                    'max_weighted_score' => 0,
                    'question_count' => 0
                ];
            }
            
            // Determine weight
            $weight = 1.0;
            if ($impact === 'high') {
                $weight = 1.5;
            } elseif ($impact === 'low') {
                $weight = 0.5;
            }
            
            // Add to max score
            $theme_scores[$theme]['max_weighted_score'] += (100 * $weight);
            $theme_scores[$theme]['question_count']++;
            
            // Get answer
            $answer = null;
            $question_id = $question['id'];
            
            if (isset($answers[$question_id])) {
                $answer = strtoupper(trim($answers[$question_id]));
            } elseif (isset($answers[$index])) {
                $answer = strtoupper(trim($answers[$index]));
            }
            
            // Add to actual score
            if ($answer === 'A') {
                $theme_scores[$theme]['total_weighted_score'] += (100 * $weight);
            } elseif ($answer === 'B') {
                $theme_scores[$theme]['total_weighted_score'] += (50 * $weight);
            }
        }
        
        // Calculate percentages
        foreach ($theme_scores as $theme => $data) {
            if ($data['max_weighted_score'] > 0) {
                $theme_scores[$theme]['percentage'] = round(($data['total_weighted_score'] / $data['max_weighted_score']) * 100, 0);
            } else {
                $theme_scores[$theme]['percentage'] = 0;
            }
        }
        
        return $theme_scores;
    }
}
} // End class_exists wrapper
