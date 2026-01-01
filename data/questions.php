<?php
/**
 * EcoVadis Self-Assessment Questionnaire
 * Total: 20-25 Questions (based on company size)
 * Themes: General, Environment, Labor & Human Rights, Ethics, Sustainable Procurement
 */

return function($company_size = 'small') {
    // Determine if company is large (25+ staff)
    $is_large = ($company_size === 'large');
    
    // Standard questions for all companies (20 questions)
    $questions = array(
        
        // GENERAL (2)
        array(
            'id' => 'GEN200',
            'theme' => 'General',
            'indicator' => 'Certifications',
            'question' => 'Does your company have sustainability certifications (ISO 14001, ISO 45001, SA8000)?',
            'options' => array(
                'A' => 'Yes, comprehensive certifications fully implemented',
                'B' => 'We have some certifications',
                'C' => 'No certifications'
            ),
            'impact' => 'medium'
        ),
        array(
            'id' => 'GEN600',
            'theme' => 'General',
            'indicator' => 'Reporting',
            'question' => 'Does your company report sustainability metrics with external verification?',
            'options' => array(
                'A' => 'Yes, externally verified reporting',
                'B' => 'Internal reporting only',
                'C' => 'No sustainability reporting'
            ),
            'impact' => 'medium'
        ),
        
        // ENVIRONMENT (6)
        array(
            'id' => 'ENV100',
            'theme' => 'Environment',
            'indicator' => 'Energy Policy',
            'question' => 'Does your company have an energy and GHG reduction policy?',
            'options' => array(
                'A' => 'Yes, comprehensive policy fully implemented',
                'B' => 'Partial policy implementation',
                'C' => 'No energy policy'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'ENV310',
            'theme' => 'Environment',
            'indicator' => 'Energy Actions',
            'question' => 'What energy reduction actions has your company implemented?',
            'options' => array(
                'A' => 'Comprehensive: renewable energy, efficiency upgrades, training',
                'B' => 'Some energy reduction initiatives',
                'C' => 'No energy reduction actions'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'ENV355',
            'theme' => 'Environment',
            'indicator' => 'Waste Management',
            'question' => 'What waste management actions has your company implemented?',
            'options' => array(
                'A' => 'Comprehensive recycling and reduction programs',
                'B' => 'Basic waste sorting',
                'C' => 'No waste management'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'ENV600',
            'theme' => 'Environment',
            'indicator' => 'Environmental Metrics',
            'question' => 'Does your company track energy and emissions metrics?',
            'options' => array(
                'A' => 'Yes, comprehensive regular tracking',
                'B' => 'Some metrics tracked',
                'C' => 'No metrics tracked'
            ),
            'impact' => 'medium'
        ),
        array(
            'id' => 'ENV630',
            'theme' => 'Environment',
            'indicator' => 'GHG Reporting',
            'question' => 'Does your company calculate GHG emissions (Scope 1, 2, 3)?',
            'options' => array(
                'A' => 'Yes, Scope 1, 2, and 3 calculated',
                'B' => 'Scope 1 and 2 only',
                'C' => 'No GHG calculations'
            ),
            'impact' => 'medium'
        ),
        array(
            'id' => 'ENV313',
            'theme' => 'Environment',
            'indicator' => 'Renewable Energy',
            'question' => 'Does your company use renewable energy?',
            'options' => array(
                'A' => 'Yes, 50%+ renewable energy',
                'B' => 'Some renewable energy (<50%)',
                'C' => 'No renewable energy'
            ),
            'impact' => 'high'
        ),
        
        // LABOR & HUMAN RIGHTS (8)
        array(
            'id' => 'LAB100',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'Health & Safety Policy',
            'question' => 'Does your company have a health and safety policy?',
            'options' => array(
                'A' => 'Yes, comprehensive policy fully implemented',
                'B' => 'Partial implementation',
                'C' => 'No H&S policy'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB310',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'Working Conditions',
            'question' => 'Does your company have a working conditions policy (wages, hours, benefits)?',
            'options' => array(
                'A' => 'Yes, comprehensive policy implemented',
                'B' => 'Partial implementation',
                'C' => 'No policy'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB312',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'H&S Actions',
            'question' => 'What health and safety actions has your company implemented?',
            'options' => array(
                'A' => 'Comprehensive: risk assessments, PPE, training',
                'B' => 'Basic measures',
                'C' => 'No specific actions'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB320',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'Healthcare',
            'question' => 'Does your company provide healthcare coverage?',
            'options' => array(
                'A' => 'Yes, comprehensive coverage for all',
                'B' => 'Basic coverage',
                'C' => 'No healthcare'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB340',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'Training',
            'question' => 'Does your company provide employee skills development training?',
            'options' => array(
                'A' => 'Yes, regular comprehensive programs',
                'B' => 'Occasional training',
                'C' => 'No training'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB360',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'Anti-Discrimination',
            'question' => 'What anti-discrimination actions has your company implemented?',
            'options' => array(
                'A' => 'Comprehensive: policies, training, grievance system',
                'B' => 'Basic measures',
                'C' => 'No specific actions'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB365',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'DEI',
            'question' => 'Does your company promote diversity, equity, and inclusion?',
            'options' => array(
                'A' => 'Yes, comprehensive DEI programs',
                'B' => 'Some DEI initiatives',
                'C' => 'No DEI programs'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'LAB600',
            'theme' => 'Labor & Human Rights',
            'indicator' => 'Labor Metrics',
            'question' => 'Does your company track labor and safety metrics?',
            'options' => array(
                'A' => 'Yes, comprehensive tracking',
                'B' => 'Some metrics tracked',
                'C' => 'No tracking'
            ),
            'impact' => 'medium'
        ),
        
        // ETHICS (4)
        array(
            'id' => 'ETH100',
            'theme' => 'Ethics',
            'indicator' => 'Anti-Corruption Policy',
            'question' => 'Does your company have an anti-corruption policy?',
            'options' => array(
                'A' => 'Yes, comprehensive policy implemented',
                'B' => 'Some measures in place',
                'C' => 'No policy'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'ETH310',
            'theme' => 'Ethics',
            'indicator' => 'Corruption Prevention',
            'question' => 'What corruption prevention actions has your company implemented?',
            'options' => array(
                'A' => 'Comprehensive: training, whistleblower, audits',
                'B' => 'Some measures',
                'C' => 'No actions'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'ETH315',
            'theme' => 'Ethics',
            'indicator' => 'Data Security',
            'question' => 'What information security actions has your company implemented?',
            'options' => array(
                'A' => 'Comprehensive: protection, training, incident response',
                'B' => 'Basic measures',
                'C' => 'No actions'
            ),
            'impact' => 'high'
        ),
        array(
            'id' => 'ETH600',
            'theme' => 'Ethics',
            'indicator' => 'Ethics Training',
            'question' => 'What percentage of employees receive ethics training?',
            'options' => array(
                'A' => '75-100% annually',
                'B' => '25-74%',
                'C' => '<25% or none'
            ),
            'impact' => 'medium'
        )
    );
    
    // Add 5 procurement questions for large companies (25+ staff)
    if ($is_large) {
        $procurement = array(
            array(
                'id' => 'SUP100',
                'theme' => 'Sustainable Procurement',
                'indicator' => 'Supplier Policy',
                'question' => 'Does your company have supplier sustainability policies?',
                'options' => array(
                    'A' => 'Yes, comprehensive policies implemented',
                    'B' => 'Some requirements',
                    'C' => 'No policies'
                ),
                'impact' => 'high'
            ),
            array(
                'id' => 'SUP305',
                'theme' => 'Sustainable Procurement',
                'indicator' => 'Supplier Code',
                'question' => 'Does your company have a supplier code of conduct?',
                'options' => array(
                    'A' => 'Yes, with contractual enforcement',
                    'B' => 'Basic guidelines',
                    'C' => 'No code'
                ),
                'impact' => 'high'
            ),
            array(
                'id' => 'SUP306',
                'theme' => 'Sustainable Procurement',
                'indicator' => 'Supplier Assessment',
                'question' => 'Does your company assess supplier sustainability?',
                'options' => array(
                    'A' => 'Yes, regular comprehensive assessments',
                    'B' => 'Occasional assessments',
                    'C' => 'No assessments'
                ),
                'impact' => 'high'
            ),
            array(
                'id' => 'SUP320',
                'theme' => 'Sustainable Procurement',
                'indicator' => 'Inclusive Sourcing',
                'question' => 'Does your company practice inclusive sourcing?',
                'options' => array(
                    'A' => 'Yes, comprehensive program',
                    'B' => 'Some measures',
                    'C' => 'No actions'
                ),
                'impact' => 'high'
            ),
            array(
                'id' => 'SUP600',
                'theme' => 'Sustainable Procurement',
                'indicator' => 'Supplier Metrics',
                'question' => 'Does your company track supplier sustainability metrics?',
                'options' => array(
                    'A' => 'Yes, comprehensive tracking',
                    'B' => 'Some metrics',
                    'C' => 'No tracking'
                ),
                'impact' => 'medium'
            )
        );
        
        $questions = array_merge($questions, $procurement);
    }
    
    return $questions;
};
