<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ISO 42001 Scoring Class
 */
if (!class_exists('ISO42K_Scoring')) {

class ISO42K_Scoring {
    /**
     * Get all assessment questions (organized by category)
     * @param string $company_size Company size
     * @return array Structured questions list
     */
    public static function get_questions($company_size = 'small') {
        $raw_questions = [
            'organizational' => [
                ['id'=>'org_1','control'=>'5.1','title'=>'Information Security Policies','question_simple'=>'Do we have clear, written security rules that every employee knows and follows?','question_technical'=>'Has an information security policy been defined, approved, published, and communicated to relevant personnel?'],
                ['id'=>'org_2','control'=>'5.2','title'=>'Security Roles & Responsibilities','question_simple'=>'Does everyone know who is responsible for which security task?','question_technical'=>'Are information security roles and responsibilities defined and allocated?'],
                ['id'=>'org_3','control'=>'5.3','title'=>'Segregation of Duties','question_simple'=>'Do we divide critical tasks between different people to prevent fraud?','question_technical'=>'Are conflicting duties and responsibilities segregated?'],
                ['id'=>'org_4','control'=>'5.7','title'=>'Threat Intelligence','question_simple'=>'Do we gather information about potential security threats?','question_technical'=>'Is threat intelligence collected and analyzed?'],
                ['id'=>'org_5','control'=>'5.9','title'=>'Asset Inventory','question_simple'=>'Do we have a complete list of our important devices and data?','question_technical'=>'Is an inventory of information and assets maintained with identified owners?'],
                ['id'=>'org_6','control'=>'5.10','title'=>'Acceptable Use Policy','question_simple'=>'Are there clear rules about what employees can do with company computers?','question_technical'=>'Are acceptable use rules for information and assets documented?'],
                ['id'=>'org_7','control'=>'5.12','title'=>'Information Classification','question_simple'=>'Do we sort information into categories like Public, Internal, or Confidential?','question_technical'=>'Is information classified based on confidentiality, integrity, and availability?'],
                ['id'=>'org_8','control'=>'5.15','title'=>'Access Control','question_simple'=>'Do we have rules deciding who can access our systems?','question_technical'=>'Are access control rules established based on security requirements?'],
                ['id'=>'org_9','control'=>'5.16','title'=>'Identity Management','question_simple'=>'Do we have a process for creating and deleting user accounts?','question_technical'=>'Is the full lifecycle of user identities managed?'],
                ['id'=>'org_10','control'=>'5.17','title'=>'Authentication Management','question_simple'=>'Do we require secure passwords and proper authentication?','question_technical'=>'Is authentication information controlled by a formal process?'],
                ['id'=>'org_11','control'=>'5.19','title'=>'Supplier Security','question_simple'=>'Do we check if our vendors are secure before working with them?','question_technical'=>'Are processes implemented to manage security risks with suppliers?'],
                ['id'=>'org_12','control'=>'5.23','title'=>'Cloud Security','question_simple'=>'Do we have rules for using cloud apps safely?','question_technical'=>'Are processes for cloud service management established?'],
                ['id'=>'org_13','control'=>'5.24','title'=>'Incident Management Planning','question_simple'=>'Do we have a plan for what to do if we get hacked?','question_technical'=>'Has incident management planning been defined with roles?'],
                ['id'=>'org_14','control'=>'5.29','title'=>'Business Continuity','question_simple'=>'Do we have a plan to keep data secure during disasters?','question_technical'=>'Has the organization planned to maintain security during disruption?'],
                ['id'=>'org_15','control'=>'5.31','title'=>'Legal Requirements','question_simple'=>'Do we know which security laws we must follow?','question_technical'=>'Are legal and regulatory requirements identified and documented?'],
                ['id'=>'org_16','control'=>'5.34','title'=>'Privacy & PII Protection','question_simple'=>'Do we follow privacy laws for customer personal data?','question_technical'=>'Does the organization meet privacy and PII protection requirements?']
            ],
            'people' => [
                ['id'=>'ppl_1','control'=>'6.1','title'=>'Personnel Screening','question_simple'=>'Do we run background checks on new hires?','question_technical'=>'Are background verification checks conducted on all candidates?'],
                ['id'=>'ppl_2','control'=>'6.2','title'=>'Employment Terms','question_simple'=>'Do employment contracts state security responsibilities?','question_technical'=>'Do contractual agreements define information security responsibilities?'],
                ['id'=>'ppl_3','control'=>'6.3','title'=>'Security Awareness Training','question_simple'=>'Do we train employees to spot security risks like phishing?','question_technical'=>'Do personnel receive appropriate security awareness and training?'],
                ['id'=>'ppl_4','control'=>'6.7','title'=>'Remote Working Security','question_simple'=>'Do we have safety measures for people working from home?','question_technical'=>'Are security measures implemented for remote working?']
            ],
            'physical' => [
                ['id'=>'phy_1','control'=>'7.1','title'=>'Physical Perimeters','question_simple'=>'Do we have barriers to stop unauthorized people from entering?','question_technical'=>'Are security perimeters defined to protect information assets?'],
                ['id'=>'phy_2','control'=>'7.2','title'=>'Physical Entry Controls','question_simple'=>'Do we control who can walk through our doors?','question_technical'=>'Are secure areas protected by entry controls?'],
                ['id'=>'phy_3','control'=>'7.4','title'=>'Physical Monitoring','question_simple'=>'Do we have cameras or alarms watching our buildings?','question_technical'=>'Are premises continuously monitored for unauthorized access?'],
                ['id'=>'phy_4','control'=>'7.5','title'=>'Environmental Protection','question_simple'=>'Is our equipment protected from fire, floods, or power surges?','question_technical'=>'Is protection against physical and environmental threats implemented?'],
                ['id'=>'phy_5','control'=>'7.7','title'=>'Clear Desk & Screen','question_simple'=>'Do employees lock computers and clear desks when they leave?','question_technical'=>'Are clear desk and clear screen rules enforced?'],
                ['id'=>'phy_6','control'=>'7.8','title'=>'Equipment Protection','question_simple'=>'Is important equipment placed safely to avoid damage?','question_technical'=>'Is equipment sited securely and protected from threats?'],
                ['id'=>'phy_7','control'=>'7.10','title'=>'Storage Media Management','question_simple'=>'Do we handle USB drives and hard drives carefully?','question_technical'=>'Is storage media managed through its lifecycle?'],
                ['id'=>'phy_8','control'=>'7.14','title'=>'Secure Disposal','question_simple'=>'Do we wipe hard drives before throwing away old computers?','question_technical'=>'Is sensitive data removed before equipment disposal?']
            ],
            'technological' => [
                ['id'=>'tech_1','control'=>'8.1','title'=>'Endpoint Security','question_simple'=>'Are laptops and phones securely configured?','question_technical'=>'Is information on user endpoint devices protected?'],
                ['id'=>'tech_2','control'=>'8.2','title'=>'Privileged Access','question_simple'=>'Do we strictly limit admin access to only those who need it?','question_technical'=>'Is privileged access restricted and managed?'],
                ['id'=>'tech_3','control'=>'8.5','title'=>'Secure Authentication','question_simple'=>'Do we require strong logins like multi-factor authentication?','question_technical'=>'Are secure authentication technologies implemented?'],
                ['id'=>'tech_4','control'=>'8.7','title'=>'Malware Protection','question_simple'=>'Do we have antivirus software to stop viruses?','question_technical'=>'Is malware protection implemented with user awareness?'],
                ['id'=>'tech_5','control'=>'8.8','title'=>'Vulnerability Management','question_simple'=>'Do we scan for software weak spots and fix them?','question_technical'=>'Are technical vulnerabilities identified and addressed?'],
                ['id'=>'tech_6','control'=>'8.9','title'=>'Configuration Management','question_simple'=>'Do we have a secure standard setup for computers?','question_technical'=>'Are security configurations established and monitored?'],
                ['id'=>'tech_7','control'=>'8.12','title'=>'Data Leakage Prevention','question_simple'=>'Do we prevent sensitive data from being accidentally sent outside?','question_technical'=>'Are data leakage prevention measures applied?'],
                ['id'=>'tech_8','control'=>'8.13','title'=>'Information Backup','question_simple'=>'Do we make regular backup copies of our data?','question_technical'=>'Are backup copies maintained and regularly tested?'],
                ['id'=>'tech_9','control'=>'8.15','title'=>'Security Logging','question_simple'=>'Do we keep records of who did what in our systems?','question_technical'=>'Are logs produced, stored, protected, and analyzed?'],
                ['id'=>'tech_10','control'=>'8.16','title'=>'Security Monitoring','question_simple'=>'Do we watch our networks for suspicious behavior?','question_technical'=>'Are systems monitored for anomalous behavior?'],
                ['id'=>'tech_11','control'=>'8.20','title'=>'Network Security','question_simple'=>'Is our network securely built to keep hackers out?','question_technical'=>'Are networks and devices secured and controlled?'],
                ['id'=>'tech_12','control'=>'8.24','title'=>'Cryptography','question_simple'=>'Do we use encryption to protect sensitive data?','question_technical'=>'Are cryptography rules defined and implemented?']
            ]
        ];

        $flattened_questions = [];
        $global_index = 1;
        
        foreach ($raw_questions as $category => $cat_questions) {
            foreach ($cat_questions as $q) {
                $flattened_questions[$global_index] = array_merge(
                    $q,
                    ['category' => $category, 'global_index' => $global_index]
                );
                $global_index++;
            }
        }

        return $flattened_questions;
    }

    /**
     * Calculate score
     * @param array $answers Answers array
     * @param string $company_size Company size
     * @return array Score + maturity level
     */
    public static function calculate_score($answers, $company_size = 'small') {
        $questions = self::get_questions($company_size);
        $total_questions = count($questions);
        $total_score = 0;

        // 3-option scale (customer requirement):
        // A = 10, B = 5, C = 0
        // C means "Not implemented at all".
        $option_scores = ['A' => 10, 'B' => 5, 'C' => 0];
        
        foreach ($answers as $q_key => $answer) {
            if (!is_numeric($q_key)) {
                $q_index = self::get_question_index_by_id($q_key);
                if (!$q_index) {
                    continue;
                }
                $q_key = $q_index;
            } else {
                // Convert 0-based index to 1-based index since questions array is 1-based
                $q_key = (int)$q_key + 1;
            }

            // Only add score if the question exists at the given index
            if (isset($questions[$q_key]) && isset($option_scores[$answer])) {
                $total_score += $option_scores[$answer];
            }
        }

        $max_score = $total_questions * 10;
        $percentage = $max_score > 0 ? round(($total_score / $max_score) * 100, 0) : 0;
        $maturity_level = self::get_maturity_level($percentage);

        return [
            'percentage' => $percentage,
            'maturity_level' => $maturity_level,
            'total_questions' => $total_questions,
            'total_score' => $total_score
        ];
    }

    /**
     * Get global index by question ID
     * @param string $q_id Question ID
     * @return int|null Global index
     */
    public static function get_question_index_by_id($q_id) {
        $questions = self::get_questions();
        foreach ($questions as $index => $q) {
            if ($q['id'] === $q_id) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Get maturity level based on score
     * @param int $percentage Percentage score
     * @return string Maturity level
     */
    public static function get_maturity_level($percentage) {
        if ($percentage >= 80) {
            return 'optimizing';
        }
        if ($percentage >= 60) {
            return 'quantitative';
        }
        if ($percentage >= 40) {
            return 'defined';
        }
        if ($percentage >= 20) {
            return 'managed';
        }
        return 'initial';
    }
}
} // End class_exists wrapper