<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ISO42K_Questions
 * Manages assessment questions for ISO 42001 gap analysis
 * 
 * @version 7.1.5
 */

if (!class_exists('ISO42K_Questions')) {

class ISO42K_Questions {

    /**
     * Cache for questions
     * @var array
     */
    private static $questions_cache = null;

    /**
     * Get all questions from data file
     * 
     * @param string $company_size Company size (small, medium, large)
     * @return array Array of questions with id, theme, indicator, question, options, and impact
     */
    public static function get_all($company_size = 'small') {
        // Load questions from data file
        $questions_file = DUO_ISO42K_PATH . 'data/questions.php';
        
        if (!file_exists($questions_file)) {
            ISO42K_Logger::log('❌ Questions data file not found: ' . $questions_file);
            return [];
        }

        try {
            $questions_loader = require $questions_file;
            
            // The questions file now returns a function that accepts company size
            if (is_callable($questions_loader)) {
                $questions = $questions_loader($company_size);
            } else {
                $questions = $questions_loader;
            }
            
            // Validate questions data
            if (!is_array($questions) || empty($questions)) {
                ISO42K_Logger::log('⚠️ Questions data is empty or invalid');
                return [];
            }
            
            return $questions;
        } catch (Exception $e) {
            ISO42K_Logger::log('❌ Error loading questions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single question by ID
     * 
     * @param string $id Question ID (e.g., 'GEN200', 'ENV100')
     * @param string $company_size Company size (small, medium, large)
     * @return array|null Question data or null if not found
     */
    public static function get_by_id($id, $company_size = 'small') {
        $questions = self::get_all($company_size);
        
        foreach ($questions as $question) {
            if (isset($question['id']) && $question['id'] == $id) {
                return $question;
            }
        }
        
        return null;
    }

    /**
     * Get question by index
     * 
     * @param int $index Zero-based question index
     * @param string $company_size Company size (small, medium, large)
     * @return array|null Question data or null if index is invalid
     */
    public static function get_by_index($index, $company_size = 'small') {
        $questions = self::get_all($company_size);
        
        if (isset($questions[$index])) {
            return $questions[$index];
        }
        
        return null;
    }

    /**
     * Get total number of questions
     * 
     * @param string $company_size Company size (small, medium, large)
     * @return int Number of questions
     */
    public static function get_count($company_size = 'small') {
        $questions = self::get_all($company_size);
        return count($questions);
    }

    /**
     * Get questions by theme
     * 
     * @param string $theme Theme name
     * @param string $company_size Company size (small, medium, large)
     * @return array Array of questions for the specified theme
     */
    public static function get_by_theme($theme, $company_size = 'small') {
        $questions = self::get_all($company_size);
        $filtered = [];
        
        foreach ($questions as $question) {
            if (isset($question['theme']) && $question['theme'] === $theme) {
                $filtered[] = $question;
            }
        }
        
        return $filtered;
    }

    /**
     * Get all unique themes
     * 
     * @param string $company_size Company size (small, medium, large)
     * @return array Array of theme names
     */
    public static function get_themes($company_size = 'small') {
        $questions = self::get_all($company_size);
        $themes = [];
        
        foreach ($questions as $question) {
            if (isset($question['theme']) && !in_array($question['theme'], $themes)) {
                $themes[] = $question['theme'];
            }
        }
        
        return $themes;
    }

    /**
     * Get a batch of questions
     * 
     * @param int $start_index Starting index (0-based)
     * @param int $count Number of questions to retrieve
     * @param string $company_size Company size (small, medium, large)
     * @return array Array of questions
     */
    public static function get_batch($start_index, $count, $company_size = 'small') {
        $questions = self::get_all($company_size);
        $total = count($questions);
        
        // Ensure valid range
        if ($start_index < 0) {
            $start_index = 0;
        }
        
        if ($start_index >= $total) {
            return [];
        }
        
        return array_slice($questions, $start_index, $count);
    }

}

} // End if class_exists check
