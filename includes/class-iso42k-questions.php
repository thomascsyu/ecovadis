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
     * @return array Array of questions with id, theme, and text
     */
    public static function get_all() {
        // Return cached questions if available
        if (self::$questions_cache !== null) {
            return self::$questions_cache;
        }

        // Load questions from data file
        $questions_file = DUO_ISO42K_PATH . 'data/questions.php';
        
        if (!file_exists($questions_file)) {
            ISO42K_Logger::log('❌ Questions data file not found: ' . $questions_file);
            return [];
        }

        try {
            $questions = require_once $questions_file;
            
            // Validate questions data
            if (!is_array($questions) || empty($questions)) {
                ISO42K_Logger::log('⚠️ Questions data is empty or invalid');
                return [];
            }

            // Cache the questions
            self::$questions_cache = $questions;
            
            return $questions;
        } catch (Exception $e) {
            ISO42K_Logger::log('❌ Error loading questions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single question by ID
     * 
     * @param int $id Question ID
     * @return array|null Question data or null if not found
     */
    public static function get_by_id($id) {
        $questions = self::get_all();
        
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
     * @return array|null Question data or null if index is invalid
     */
    public static function get_by_index($index) {
        $questions = self::get_all();
        
        if (isset($questions[$index])) {
            return $questions[$index];
        }
        
        return null;
    }

    /**
     * Get total number of questions
     * 
     * @return int Number of questions
     */
    public static function get_count() {
        $questions = self::get_all();
        return count($questions);
    }

    /**
     * Get questions by theme
     * 
     * @param string $theme Theme name
     * @return array Array of questions for the specified theme
     */
    public static function get_by_theme($theme) {
        $questions = self::get_all();
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
     * @return array Array of theme names
     */
    public static function get_themes() {
        $questions = self::get_all();
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
     * @return array Array of questions
     */
    public static function get_batch($start_index, $count) {
        $questions = self::get_all();
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

    /**
     * Clear the questions cache
     * 
     * @return void
     */
    public static function clear_cache() {
        self::$questions_cache = null;
    }

}

} // End if class_exists check
