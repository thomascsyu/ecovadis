<?php
/**
 * ISO/IEC 42001:2023 Self-Assessment Questionnaire
 * Total: 33 Questions
 * Part 1: Clauses 4-10 (3 Questions)
 * Part 2: Annex A - Reference Control Objectives and Controls (30 Questions)
 */

return [

/* =========================
   PART 1: CLAUSES 4-10
   3 Questions covering fundamental requirements
========================= */

// Clause 5.2 - AI policy
[
    'id' => 1,
    'theme' => 'Leadership & Policy',
    'text' => 'Has top management established, documented, and communicated an AI policy that includes commitments to meeting applicable requirements and continual improvement of the AI management system?'
],

// Clause 6.1.2 - AI risk assessment
[
    'id' => 2,
    'theme' => 'Planning & Risk',
    'text' => 'Does your organization have a defined and documented process for AI risk assessment that identifies, analyzes, and evaluates risks related to AI systems?'
],

// Clause 7.2 - Competence
[
    'id' => 3,
    'theme' => 'Resources',
    'text' => 'Has your organization determined the necessary competence for personnel affecting AI performance and ensured they are competent through training, education, or experience?'
],

/* =========================
   PART 2: ANNEX A - REFERENCE CONTROL OBJECTIVES AND CONTROLS
   30 Questions covering specific control areas
========================= */

/* ---- A.2 Policies related to AI ---- */

// A.2.2 - AI policy
[
    'id' => 4,
    'theme' => 'A.2 Policies',
    'text' => 'Is there a documented policy for the development or use of AI systems?'
],

// A.2.3 - Alignment with other organizational policies
[
    'id' => 5,
    'theme' => 'A.2 Policies',
    'text' => 'Has the organization determined where other policies (e.g., security, privacy) intersect with AI objectives?'
],

// A.2.4 - Review of the AI policy
[
    'id' => 6,
    'theme' => 'A.2 Policies',
    'text' => 'Is the AI policy reviewed at planned intervals to ensure its suitability, adequacy, and effectiveness?'
],

/* ---- A.3 Internal organization ---- */

// A.3.2 - AI roles and responsibilities
[
    'id' => 7,
    'theme' => 'A.3 Organization',
    'text' => 'Are roles and responsibilities for AI defined and allocated according to organizational needs?'
],

// A.3.3 - Reporting of concerns
[
    'id' => 8,
    'theme' => 'A.3 Organization',
    'text' => 'Is there a process for reporting concerns about the organization\'s role regarding AI systems throughout their life cycle?'
],

/* ---- A.4 Resources for AI systems ---- */

// A.4.2 - Resource documentation
[
    'id' => 9,
    'theme' => 'A.4 Resources',
    'text' => 'Are relevant resources required for AI system life cycle stages identified and documented?'
],

// A.4.3 - Data resources
[
    'id' => 10,
    'theme' => 'A.4 Resources',
    'text' => 'Is information about data resources used for AI systems documented?'
],

// A.4.4 - Tooling resources
[
    'id' => 11,
    'theme' => 'A.4 Resources',
    'text' => 'Is information about tooling resources used for AI systems documented?'
],

// A.4.5 - System and computing resources
[
    'id' => 12,
    'theme' => 'A.4 Resources',
    'text' => 'Is information about system and computing resources used for AI systems documented?'
],

// A.4.6 - Human resources
[
    'id' => 13,
    'theme' => 'A.4 Resources',
    'text' => 'Are human resources and their competences for AI system development, deployment, and operation documented?'
],

/* ---- A.5 Assessing impacts of AI systems ---- */

// A.5.2 - AI system impact assessment process
[
    'id' => 14,
    'theme' => 'A.5 Impact Assessment',
    'text' => 'Is there a process to assess potential consequences of AI systems on individuals, groups, and societies?'
],

// A.5.3 - Documentation of AI system impact assessments
[
    'id' => 15,
    'theme' => 'A.5 Impact Assessment',
    'text' => 'Are the results of AI system impact assessments documented and retained for a defined period?'
],

// A.5.4 - Assessing AI system impact on individuals or groups
[
    'id' => 16,
    'theme' => 'A.5 Impact Assessment',
    'text' => 'Are potential impacts of AI systems on individuals or groups assessed and documented throughout the life cycle?'
],

// A.5.5 - Assessing societal impacts of AI systems
[
    'id' => 17,
    'theme' => 'A.5 Impact Assessment',
    'text' => 'Are potential societal impacts of AI systems assessed and documented throughout their life cycle?'
],

/* ---- A.6 AI system life cycle ---- */

// A.6.1.2 - Objectives for responsible development of AI system
[
    'id' => 18,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Are objectives for responsible AI system development identified, documented, and integrated into the development life cycle?'
],

// A.6.1.3 - Processes for responsible AI system design and development
[
    'id' => 19,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Are specific processes for responsible AI system design and development defined and documented?'
],

// A.6.2.2 - AI system requirements and specification
[
    'id' => 20,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Are requirements for new AI systems or enhancements specified and documented?'
],

// A.6.2.3 - Documentation of AI system design and development
[
    'id' => 21,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Is AI system design and development documented based on organizational objectives and requirements?'
],

// A.6.2.4 - AI system verification and validation
[
    'id' => 22,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Are verification and validation measures for the AI system defined and documented?'
],

// A.6.2.5 - AI system deployment
[
    'id' => 23,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Is a deployment plan documented and are appropriate requirements met prior to deployment?'
],

// A.6.2.6 - AI system operation and monitoring
[
    'id' => 24,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Are necessary elements for ongoing AI system operation (monitoring, repairs, updates, support) defined and documented?'
],

// A.6.2.7 - AI system technical documentation
[
    'id' => 25,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Is technical documentation for relevant interested parties (users, partners, authorities) determined and provided?'
],

// A.6.2.8 - AI system recording of event logs
[
    'id' => 26,
    'theme' => 'A.6 Life Cycle',
    'text' => 'Is record-keeping of event logs enabled at appropriate AI system life cycle phases, at minimum during use?'
],

/* ---- A.7 Data for AI systems ---- */

// A.7.2 - Data for development and enhancement of AI system
[
    'id' => 27,
    'theme' => 'A.7 Data Management',
    'text' => 'Are data management processes for AI system development defined, documented, and implemented?'
],

// A.7.4 - Quality of data for AI systems
[
    'id' => 28,
    'theme' => 'A.7 Data Management',
    'text' => 'Are data quality requirements defined and documented, and is compliance ensured?'
],

/* ---- A.8 Information for interested parties of AI systems ---- */

// A.8.2 - System documentation and information for users
[
    'id' => 29,
    'theme' => 'A.8 Information & Transparency',
    'text' => 'Is necessary information determined and provided to AI system users?'
],

// A.8.4 - Communication of incidents
[
    'id' => 30,
    'theme' => 'A.8 Information & Transparency',
    'text' => 'Is a plan for communicating incidents to AI system users determined and documented?'
],

/* ---- A.9 Use of AI systems ---- */

// A.9.2 - Processes for responsible use of AI systems
[
    'id' => 31,
    'theme' => 'A.9 Responsible Use',
    'text' => 'Are processes for the responsible use of AI systems defined and documented?'
],

// A.9.4 - Intended use of the AI system
[
    'id' => 32,
    'theme' => 'A.9 Responsible Use',
    'text' => 'Is the AI system used according to its intended use and accompanying documentation?'
],

/* ---- A.10 Third-party and customer relationships ---- */

// A.10.3 - Suppliers
[
    'id' => 33,
    'theme' => 'A.10 Third Parties',
    'text' => 'Is there a process to ensure that suppliers\' services, products, or materials align with the organization\'s responsible AI approach?'
],

];
