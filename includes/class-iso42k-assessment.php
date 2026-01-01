<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ISO42K_Assessment
 * Handles assessment calculation and processing for EcoVadis
 * 
 * @version 2.0.0
 */
if (!class_exists('ISO42K_Assessment')) {

class ISO42K_Assessment {

    /**
     * Calculate assessment results from answers
     */
    public function calculate($answers, $staff) {
        // Ensure the scoring class is available
        if (!class_exists('ISO42K_Scoring')) {
            // The scoring class should be available since it's included in the main plugin file
            // If it's not available, there might be an issue with the plugin loading
            if (defined('DUO_ISO42K_PATH')) {
                $scoring_file = DUO_ISO42K_PATH . 'includes/class-iso42k-scoring.php';
                if (file_exists($scoring_file)) {
                    require_once $scoring_file;
                }
            }
            
            // Check again if the class exists after attempting to load it
            if (!class_exists('ISO42K_Scoring')) {
                // Log the error if logging is available
                if (class_exists('ISO42K_Logger')) {
                    ISO42K_Logger::log('❌ Scoring class not found and could not be loaded');
                }
                // Return a default result instead of throwing an exception
                return [
                    'percent' => 0,
                    'maturity' => 'Initial'
                ];
            }
        }
        
        // Convert answers to the format expected by the scoring class
        $scoring_answers = $this->convertAnswersFormat($answers);
        
        $result = ISO42K_Scoring::calculate_score($scoring_answers, $this->getCompanySizeFromStaff($staff));
        
        return [
            'percent' => $result['percentage'],
            'maturity' => $this->convertMaturityFormat($result['maturity_level'])
        ];
    }
    
    /**
     * Convert answers from simple array format to the expected format
     */
    private function convertAnswersFormat($answers) {
        $converted = [];
        
        foreach ($answers as $index => $answer) {
            // Check if the index is already a question ID (like 'org_1', 'ppl_1', etc.)
            if (is_string($index) && preg_match('/^[a-z_]+\d+$/', $index)) {
                // It's already a question ID, use as-is
                $converted[$index] = $answer;
            } else {
                // If it's a numeric index (typically 0-based from frontend), convert to 1-based index 
                // since questions are 1-indexed in the scoring system
                $question_index = is_numeric($index) ? (int)$index + 1 : $index;
                $converted[$question_index] = $answer;
            }
        }
        
        return $converted;
    }
    
    /**
     * Convert staff number to company size string for EcoVadis
     * Large companies (25+ staff) receive additional procurement questions
     */
    private function getCompanySizeFromStaff($staff) {
        if ($staff >= 25) {
            return 'large';
        } elseif ($staff >= 10) {
            return 'medium';
        } else {
            return 'small';
        }
    }
    
    /**
     * Convert maturity level to the expected format
     */
    private function convertMaturityFormat($maturity_level) {
        $maturity_map = [
            'initial' => 'Initial',
            'developing' => 'Developing',
            'established' => 'Established',
            'advanced' => 'Advanced',
            'leading' => 'Leading'
        ];
        
        return isset($maturity_map[$maturity_level]) ? $maturity_map[$maturity_level] : 'Initial';
    }
}

} // End class_exists wrapper