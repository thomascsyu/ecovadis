<?php
if (!defined('ABSPATH')) exit;

/**
 * ISO42K_Leads
 * Database operations for storing and retrieving assessment leads
 * 
 * @version 7.2.1
 */
class ISO42K_Leads {

  /**
   * Create leads table on plugin activation
   */
  public static function create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
      id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      company varchar(255) DEFAULT NULL,
      staff int(11) DEFAULT NULL,
      name varchar(255) DEFAULT NULL,
      email varchar(255) DEFAULT NULL,
      phone varchar(100) DEFAULT NULL,
      percent int(11) DEFAULT NULL,
      maturity varchar(50) DEFAULT NULL,
      answers longtext DEFAULT NULL,
      ai_summary longtext DEFAULT NULL,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      pdf_downloaded_at datetime DEFAULT NULL,
      PRIMARY KEY (id),
      KEY idx_created (created_at),
      KEY idx_email (email),
      KEY idx_percent (percent),
      KEY idx_maturity (maturity),
      KEY idx_pdf_downloaded (pdf_downloaded_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    
    // Ensure all columns exist (in case of schema updates)
    $columns = $wpdb->get_results("DESCRIBE {$table}", ARRAY_A);
    $column_names = array_column($columns, 'Field');
    
    // Add missing columns if needed
    $expected_columns = ['company', 'staff', 'name', 'email', 'phone', 'percent', 'maturity', 'answers', 'ai_summary', 'created_at', 'pdf_downloaded_at'];
    $missing_columns = array_diff($expected_columns, $column_names);
    
    foreach ($missing_columns as $column) {
      switch ($column) {
        case 'company':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN company varchar(255) DEFAULT NULL;");
          break;
        case 'staff':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN staff int(11) DEFAULT NULL;");
          break;
        case 'name':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN name varchar(255) DEFAULT NULL;");
          break;
        case 'email':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN email varchar(255) DEFAULT NULL;");
          break;
        case 'phone':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN phone varchar(100) DEFAULT NULL;");
          break;
        case 'percent':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN percent int(11) DEFAULT NULL;");
          break;
        case 'maturity':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN maturity varchar(50) DEFAULT NULL;");
          break;
        case 'answers':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN answers longtext DEFAULT NULL;");
          break;
        case 'ai_summary':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN ai_summary longtext DEFAULT NULL;");
          break;
        case 'created_at':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP;");
          break;
        case 'pdf_downloaded_at':
          $wpdb->query("ALTER TABLE {$table} ADD COLUMN pdf_downloaded_at datetime DEFAULT NULL;");
          break;
      }
    }

    // Log table creation
    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('Leads table created/verified: ' . $table);
      if (!empty($missing_columns)) {
        ISO42K_Logger::log('Added missing columns: ' . implode(', ', $missing_columns));
      }
    }
  }

  /**
   * Insert a new lead - FIXED FORMAT MISMATCH
   * 
   * @param array $data Lead data
   * @return int|false Insert ID or false on failure
   */
  public static function insert(array $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('ISO42K_Leads::insert() called');
    }

    // Validate required fields
    if (empty($data['email'])) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('❌ Lead insert failed: email is required');
      }
      return false;
    }

    if (!is_email($data['email'])) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('❌ Lead insert failed: invalid email format');
      }
      return false;
    }

    // Prepare data for insertion with safer defaults
    $insert_data = [
      'company'    => sanitize_text_field($data['company'] ?? ($data['org'] ?? '')),
      'staff'      => absint($data['staff'] ?? 0),
      'name'       => sanitize_text_field($data['name'] ?? ($data['contact_name'] ?? '')),
      'email'      => sanitize_email($data['email']),
      'phone'      => sanitize_text_field($data['phone'] ?? ''),
      'percent'    => absint($data['percent'] ?? ($data['score'] ?? 0)),
      'maturity'   => sanitize_text_field($data['maturity'] ?? 'Initial'),
      'answers'    => is_array($data['answers'] ?? null) 
                      ? wp_json_encode($data['answers']) 
                      : (is_string($data['answers'] ?? null) ? $data['answers'] : '[]'),
      'ai_summary' => sanitize_textarea_field($data['ai_summary'] ?? ($data['ai_analysis'] ?? '')),
      'created_at' => current_time('mysql'),
      'pdf_downloaded_at' => null, // Always null on creation
    ];

    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('Prepared insert data:');
      ISO42K_Logger::log('  company: "' . $insert_data['company'] . '"');
      ISO42K_Logger::log('  staff: ' . $insert_data['staff']);
      ISO42K_Logger::log('  name: "' . $insert_data['name'] . '"');
      ISO42K_Logger::log('  email: "' . $insert_data['email'] . '"');
      ISO42K_Logger::log('  phone: "' . $insert_data['phone'] . '"');
      ISO42K_Logger::log('  percent: ' . $insert_data['percent']);
      ISO42K_Logger::log('  maturity: ' . $insert_data['maturity']);
    }

    // ✅ FIXED: Format specification - MUST MATCH number of fields (11 fields)
    $formats = [
      '%s', // company
      '%d', // staff
      '%s', // name
      '%s', // email
      '%s', // phone
      '%d', // percent
      '%s', // maturity
      '%s', // answers
      '%s', // ai_summary
      '%s', // created_at
      '%s'  // pdf_downloaded_at (WAS MISSING!)
    ];

    // Enable error reporting
    $wpdb->show_errors();
    
    $result = $wpdb->insert($table, $insert_data, $formats);

    if ($result === false) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('❌ wpdb->insert() returned FALSE');
        ISO42K_Logger::log('MySQL Error: ' . $wpdb->last_error);
        ISO42K_Logger::log('Last Query: ' . $wpdb->last_query);
      }
      $wpdb->hide_errors();
      return false;
    }

    $insert_id = $wpdb->insert_id;

    if (!$insert_id || $insert_id === 0) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('❌ Insert succeeded but insert_id is 0');
      }
      $wpdb->hide_errors();
      return false;
    }

    $wpdb->hide_errors();

    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('✅ Lead inserted: ID ' . $insert_id . ' (' . $insert_data['email'] . ')');
    }

    return $insert_id;
  }

  /**
   * Get all leads (backward compatible)
   * 
   * @param array $args Query arguments
   * @return array Lead records
   */
  public static function all(array $args = []): array {
    return self::get_all($args);
  }

  /**
   * Get all leads
   * 
   * @param array $args Query arguments
   * @return array Lead records
   */
  public static function get_all(array $args = []): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $defaults = [
      'orderby' => 'created_at',
      'order'   => 'DESC',
      'limit'   => 1000,
      'offset'  => 0
    ];

    $args = wp_parse_args($args, $defaults);

    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    $limit = absint($args['limit']);
    $offset = absint($args['offset']);

    $sql = "SELECT * FROM {$table}";
    
    if ($orderby) {
      $sql .= " ORDER BY {$orderby}";
    }
    
    if ($limit > 0) {
      $sql .= " LIMIT {$limit}";
    }
    
    if ($offset > 0) {
      $sql .= " OFFSET {$offset}";
    }

    $results = $wpdb->get_results($sql, ARRAY_A);

    if (!$results) {
      return [];
    }

    // Decode JSON fields and add backward compatibility aliases
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      
      // Add backward compatibility aliases
      $lead = self::add_field_aliases($lead);
    }

    return $results;
  }

  /**
   * Get lead by ID (backward compatible alias)
   * 
   * @param int $id Lead ID
   * @return array|null Lead data or null if not found
   */
  public static function get(int $id): ?array {
    return self::get_by_id($id);
  }

  /**
   * Get lead by ID
   * 
   * @param int $id Lead ID
   * @return array|null Lead data or null if not found
   */
  public static function get_by_id(int $id): ?array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $lead = $wpdb->get_row(
      $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
      ARRAY_A
    );

    if (!$lead) {
      return null;
    }

    // Decode JSON field
    if (isset($lead['answers']) && is_string($lead['answers'])) {
      $decoded = json_decode($lead['answers'], true);
      $lead['answers'] = is_array($decoded) ? $decoded : [];
    }

    // Add backward compatibility aliases
    return self::add_field_aliases($lead);
  }

  /**
   * Get lead by email
   * 
   * @param string $email Email address
   * @return array|null Lead data or null if not found
   */
  public static function get_by_email(string $email): ?array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $lead = $wpdb->get_row(
      $wpdb->prepare("SELECT * FROM {$table} WHERE email = %s ORDER BY created_at DESC LIMIT 1", $email),
      ARRAY_A
    );

    if (!$lead) {
      return null;
    }

    // Decode JSON field
    if (isset($lead['answers']) && is_string($lead['answers'])) {
      $decoded = json_decode($lead['answers'], true);
      $lead['answers'] = is_array($decoded) ? $decoded : [];
    }

    return self::add_field_aliases($lead);
  }

  /**
   * Get answers for a lead
   * 
   * @param int $lead_id Lead ID
   * @return array Answers array
   */
  public static function get_answers(int $lead_id): array {
    $lead = self::get_by_id($lead_id);
    
    if (!$lead || empty($lead['answers'])) {
      return [];
    }

    // If answers are already an array, return them
    if (is_array($lead['answers'])) {
      // Convert simple array to structured format if needed
      $structured = [];
      foreach ($lead['answers'] as $index => $answer) {
        $structured[] = [
          'question' => 'Question ' . ($index + 1),
          'answer' => $answer
        ];
      }
      return $structured;
    }

    return [];
  }

  /**
   * Update lead
   * 
   * @param int $id Lead ID
   * @param array $data Data to update
   * @return bool Success status
   */
  public static function update(int $id, array $data): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    // Prepare update data
    $update_data = [];
    $formats = [];

    $allowed_fields = [
      'company', 'staff', 'name', 'email', 'phone', 
      'percent', 'maturity', 'answers', 'ai_summary', 'pdf_downloaded_at'
    ];

    // Map old field names to new ones
    $field_map = [
      'org' => 'company',
      'contact_name' => 'name',
      'score' => 'percent',
      'ai_analysis' => 'ai_summary'
    ];

    // Apply field mapping
    foreach ($field_map as $old => $new) {
      if (isset($data[$old]) && !isset($data[$new])) {
        $data[$new] = $data[$old];
        unset($data[$old]);
      }
    }

    foreach ($data as $key => $value) {
      if (!in_array($key, $allowed_fields, true)) {
        continue;
      }

      // Handle different field types
      if ($key === 'staff' || $key === 'percent') {
        $update_data[$key] = absint($value);
        $formats[] = '%d';
      } elseif ($key === 'answers') {
        $update_data[$key] = is_array($value) ? wp_json_encode($value) : $value;
        $formats[] = '%s';
      } elseif ($key === 'email') {
        $update_data[$key] = sanitize_email($value);
        $formats[] = '%s';
      } elseif ($key === 'ai_summary') {
        $update_data[$key] = sanitize_textarea_field($value);
        $formats[] = '%s';
      } elseif ($key === 'pdf_downloaded_at') {
        $update_data[$key] = !empty($value) ? $value : null;
        $formats[] = '%s';
      } else {
        $update_data[$key] = sanitize_text_field($value);
        $formats[] = '%s';
      }
    }

    if (empty($update_data)) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('Lead update failed: no valid data provided for ID ' . $id);
      }
      return false;
    }

    $result = $wpdb->update(
      $table,
      $update_data,
      ['id' => $id],
      $formats,
      ['%d']
    );

    if ($result === false) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('Lead update failed for ID ' . $id . ': ' . $wpdb->last_error);
      }
      return false;
    }

    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('Lead updated successfully: ID ' . $id);
    }

    return true;
  }

  /**
   * Delete lead
   * 
   * @param int $id Lead ID
   * @return bool Success status
   */
  public static function delete(int $id): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $result = $wpdb->delete($table, ['id' => $id], ['%d']);

    if ($result === false) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('Lead delete failed for ID ' . $id . ': ' . $wpdb->last_error);
      }
      return false;
    }

    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('Lead deleted successfully: ID ' . $id);
    }

    return $result !== false;
  }

  /**
   * Get statistics
   * 
   * @return array Statistics data
   */
  public static function stats(): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    // Total count
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

    // Average score
    $avg_score = (float) $wpdb->get_var("SELECT AVG(percent) FROM {$table}");
    $avg_score = round($avg_score, 1);

    // Last 7 days count
    $last7 = (int) $wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
        date('Y-m-d H:i:s', strtotime('-7 days'))
      )
    );

    // Maturity distribution
    $maturity_dist = $wpdb->get_results(
      "SELECT maturity, COUNT(*) as count FROM {$table} GROUP BY maturity",
      ARRAY_A
    );

    $maturity_counts = [];
    if ($maturity_dist) {
      foreach ($maturity_dist as $row) {
        $maturity_counts[$row['maturity']] = (int) $row['count'];
      }
    }

    // PDF download statistics
    $pdf_downloaded = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE pdf_downloaded_at IS NOT NULL");
    $pdf_not_downloaded = $total - $pdf_downloaded;

    return [
      'total'       => $total,
      'avg_score'   => $avg_score,
      'last7'       => $last7,
      'by_maturity' => $maturity_counts,
      'pdf_downloaded' => $pdf_downloaded,
      'pdf_not_downloaded' => $pdf_not_downloaded,
      'pdf_download_rate' => $total > 0 ? round(($pdf_downloaded / $total) * 100, 1) : 0
    ];
  }

  /**
   * Search leads
   * 
   * @param string $search Search term
   * @param array $args Additional arguments
   * @return array Matching leads
   */
  public static function search(string $search, array $args = []): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $search = sanitize_text_field($search);
    
    if (empty($search)) {
      return self::get_all($args);
    }

    $like = '%' . $wpdb->esc_like($search) . '%';

    $sql = $wpdb->prepare(
      "SELECT * FROM {$table} 
       WHERE company LIKE %s 
       OR name LIKE %s 
       OR email LIKE %s 
       OR phone LIKE %s 
       ORDER BY created_at DESC",
      $like, $like, $like, $like
    );

    $results = $wpdb->get_results($sql, ARRAY_A);

    if (!$results) {
      return [];
    }

    // Decode JSON fields
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      $lead = self::add_field_aliases($lead);
    }

    return $results;
  }

  /**
   * Get leads by maturity level
   * 
   * @param string $maturity Maturity level
   * @return array Matching leads
   */
  public static function get_by_maturity(string $maturity): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE maturity = %s ORDER BY created_at DESC",
        $maturity
      ),
      ARRAY_A
    );

    if (!$results) {
      return [];
    }

    // Decode JSON fields
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      $lead = self::add_field_aliases($lead);
    }

    return $results;
  }

  /**
   * Get recent leads
   * 
   * @param int $limit Number of leads to retrieve
   * @return array Recent leads
   */
  public static function get_recent(int $limit = 10): array {
    return self::get_all([
      'orderby' => 'created_at',
      'order'   => 'DESC',
      'limit'   => $limit
    ]);
  }

  /**
   * Count total leads
   * 
   * @return int Total count
   */
  public static function count(): int {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
  }

  /**
   * Check if table exists
   * 
   * @return bool True if table exists
   */
  public static function table_exists(): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table);
    return $wpdb->get_var($query) === $table;
  }

  /**
   * Get table name
   * 
   * @return string Table name
   */
  public static function get_table_name(): string {
    global $wpdb;
    return $wpdb->prefix . 'iso42k_leads';
  }

  /**
   * Truncate table (delete all records)
   * WARNING: This is irreversible!
   * 
   * @return bool Success status
   */
  public static function truncate(): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('WARNING: Truncating leads table');
    }
    
    return $wpdb->query("TRUNCATE TABLE {$table}") !== false;
  }

  /**
   * Export leads to CSV
   * 
   * @return void Outputs CSV file
   */
  public static function export_csv() {
    $leads = self::get_all();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=iso42k-leads-' . gmdate('Ymd-His') . '.csv');

    $out = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($out, [
      'Date', 'Company', 'Staff', 'Contact', 'Email', 'Phone', 
      'Score (%)', 'Maturity', 'AI Analysis Available', 'PDF Downloaded', 'PDF Downloaded Date'
    ]);

    // CSV Rows
    foreach ($leads as $l) {
      fputcsv($out, [
        $l['created_at'],
        $l['company'],
        $l['staff'],
        $l['name'],
        $l['email'],
        $l['phone'],
        $l['percent'],
        $l['maturity'],
        !empty($l['ai_summary']) ? 'Yes' : 'No',
        !empty($l['pdf_downloaded_at']) ? 'Yes' : 'No',
        $l['pdf_downloaded_at'] ?? 'Never'
      ]);
    }
    
    fclose($out);
    exit;
  }

  /**
   * Add backward compatibility field aliases
   * 
   * @param array $lead Lead data
   * @return array Lead data with aliases
   */
  private static function add_field_aliases(array $lead): array {
    // Add old field names as aliases for backward compatibility
    if (isset($lead['company'])) {
      $lead['org'] = $lead['company'];
    }
    if (isset($lead['name'])) {
      $lead['contact_name'] = $lead['name'];
    }
    if (isset($lead['percent'])) {
      $lead['score'] = $lead['percent'];
    }
    if (isset($lead['ai_summary'])) {
      $lead['ai_analysis'] = $lead['ai_summary'];
    }

    return $lead;
  }

  /**
   * Get leads with filters
   * 
   * @param array $filters Filter criteria
   * @return array Filtered leads
   */
  public static function get_filtered(array $filters = []): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $where = [];
    $params = [];

    // Filter by maturity
    if (!empty($filters['maturity'])) {
      $where[] = 'maturity = %s';
      $params[] = $filters['maturity'];
    }

    // Filter by score range
    if (isset($filters['score_min'])) {
      $where[] = 'percent >= %d';
      $params[] = (int) $filters['score_min'];
    }
    if (isset($filters['score_max'])) {
      $where[] = 'percent <= %d';
      $params[] = (int) $filters['score_max'];
    }

    // Filter by date range
    if (!empty($filters['date_from'])) {
      $where[] = 'created_at >= %s';
      $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
      $where[] = 'created_at <= %s';
      $params[] = $filters['date_to'];
    }

    // Filter by PDF download status
    if (isset($filters['pdf_downloaded'])) {
      if ($filters['pdf_downloaded'] === true || $filters['pdf_downloaded'] === '1') {
        $where[] = 'pdf_downloaded_at IS NOT NULL';
      } elseif ($filters['pdf_downloaded'] === false || $filters['pdf_downloaded'] === '0') {
        $where[] = 'pdf_downloaded_at IS NULL';
      }
    }

    // Filter by PDF download date range
    if (!empty($filters['pdf_downloaded_from'])) {
      $where[] = 'pdf_downloaded_at >= %s';
      $params[] = $filters['pdf_downloaded_from'];
    }
    if (!empty($filters['pdf_downloaded_to'])) {
      $where[] = 'pdf_downloaded_at <= %s';
      $params[] = $filters['pdf_downloaded_to'];
    }

    $sql = "SELECT * FROM {$table}";
    
    if (!empty($where)) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    
    $sql .= ' ORDER BY created_at DESC';

    if (!empty($params)) {
      $sql = $wpdb->prepare($sql, $params);
    }

    $results = $wpdb->get_results($sql, ARRAY_A);

    if (!$results) {
      return [];
    }

    // Decode JSON fields
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      $lead = self::add_field_aliases($lead);
    }

    return $results;
  }

  /**
   * Bulk delete leads
   * 
   * @param array $ids Array of lead IDs
   * @return int Number of deleted records
   */
  public static function bulk_delete(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';

    $ids = array_map('absint', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    
    $sql = "DELETE FROM {$table} WHERE id IN ($placeholders)";
    $sql = $wpdb->prepare($sql, $ids);
    
    $deleted = $wpdb->query($sql);

    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('Bulk deleted ' . $deleted . ' leads');
    }

    return (int) $deleted;
  }

  /**
   * Record PDF download timestamp for a lead
   * 
   * @param int $lead_id Lead ID
   * @return bool Success status
   */
  public static function record_pdf_download(int $lead_id): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $result = $wpdb->update(
      $table,
      ['pdf_downloaded_at' => current_time('mysql')],
      ['id' => $lead_id],
      ['%s'],
      ['%d']
    );
    
    if ($result === false) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('PDF download record failed for lead ID ' . $lead_id . ': ' . $wpdb->last_error);
      }
      return false;
    }
    
    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('PDF download recorded for lead ID ' . $lead_id);
    }
    
    return true;
  }

  /**
   * Get leads who haven't downloaded their PDF
   * 
   * @param array $args Query arguments
   * @return array Lead records
   */
  public static function get_without_pdf_download(array $args = []): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $defaults = [
      'orderby' => 'created_at',
      'order'   => 'DESC',
      'limit'   => 1000,
      'offset'  => 0
    ];
    
    $args = wp_parse_args($args, $defaults);
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    $limit = absint($args['limit']);
    $offset = absint($args['offset']);
    
    $sql = "SELECT * FROM {$table} WHERE pdf_downloaded_at IS NULL";
    
    if ($orderby) {
      $sql .= " ORDER BY {$orderby}";
    }
    
    if ($limit > 0) {
      $sql .= " LIMIT {$limit}";
    }
    
    if ($offset > 0) {
      $sql .= " OFFSET {$offset}";
    }
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (!$results) {
      return [];
    }
    
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      $lead = self::add_field_aliases($lead);
    }
    
    return $results;
  }

  /**
   * Get leads who have downloaded their PDF
   * 
   * @param array $args Query arguments
   * @return array Lead records
   */
  public static function get_with_pdf_download(array $args = []): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $defaults = [
      'orderby' => 'pdf_downloaded_at',
      'order'   => 'DESC',
      'limit'   => 1000,
      'offset'  => 0
    ];
    
    $args = wp_parse_args($args, $defaults);
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    $limit = absint($args['limit']);
    $offset = absint($args['offset']);
    
    $sql = "SELECT * FROM {$table} WHERE pdf_downloaded_at IS NOT NULL";
    
    if ($orderby) {
      $sql .= " ORDER BY {$orderby}";
    }
    
    if ($limit > 0) {
      $sql .= " LIMIT {$limit}";
    }
    
    if ($offset > 0) {
      $sql .= " OFFSET {$offset}";
    }
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (!$results) {
      return [];
    }
    
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      $lead = self::add_field_aliases($lead);
    }
    
    return $results;
  }

  /**
   * Check if a lead has downloaded their PDF
   * 
   * @param int $lead_id Lead ID
   * @return bool True if PDF has been downloaded
   */
  public static function has_downloaded_pdf(int $lead_id): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $result = $wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE id = %d AND pdf_downloaded_at IS NOT NULL",
        $lead_id
      )
    );
    
    return (bool) $result;
  }

  /**
   * Get PDF download statistics by date range
   * 
   * @param string $start_date Start date (YYYY-MM-DD)
   * @param string $end_date End date (YYYY-MM-DD)
   * @return array Download statistics
   */
  public static function get_pdf_download_stats(string $start_date, string $end_date): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $sql = $wpdb->prepare(
      "SELECT 
        DATE(pdf_downloaded_at) as download_date,
        COUNT(*) as download_count
       FROM {$table}
       WHERE pdf_downloaded_at IS NOT NULL
         AND DATE(pdf_downloaded_at) >= %s
         AND DATE(pdf_downloaded_at) <= %s
       GROUP BY DATE(pdf_downloaded_at)
       ORDER BY download_date DESC",
      $start_date,
      $end_date
    );
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (!$results) {
      return [];
    }
    
    return $results;
  }

  /**
   * Clear PDF download timestamp for a lead
   * 
   * @param int $lead_id Lead ID
   * @return bool Success status
   */
  public static function clear_pdf_download(int $lead_id): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $result = $wpdb->update(
      $table,
      ['pdf_downloaded_at' => null],
      ['id' => $lead_id],
      ['%s'],
      ['%d']
    );
    
    if ($result === false) {
      if (class_exists('ISO42K_Logger')) {
        ISO42K_Logger::log('Clear PDF download failed for lead ID ' . $lead_id . ': ' . $wpdb->last_error);
      }
      return false;
    }
    
    if (class_exists('ISO42K_Logger')) {
      ISO42K_Logger::log('PDF download cleared for lead ID ' . $lead_id);
    }
    
    return true;
  }

  /**
   * Get leads that need PDF download follow-up (older than X days without download)
   * 
   * @param int $days_old Days since creation
   * @param int $limit Maximum results
   * @return array Leads needing follow-up
   */
  public static function get_needs_followup(int $days_old = 7, int $limit = 50): array {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    
    $sql = $wpdb->prepare(
      "SELECT * FROM {$table} 
       WHERE pdf_downloaded_at IS NULL
         AND created_at <= DATE_SUB(NOW(), INTERVAL %d DAY)
       ORDER BY created_at ASC
       LIMIT %d",
      $days_old,
      $limit
    );
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (!$results) {
      return [];
    }
    
    foreach ($results as &$lead) {
      if (isset($lead['answers']) && is_string($lead['answers'])) {
        $decoded = json_decode($lead['answers'], true);
        $lead['answers'] = is_array($decoded) ? $decoded : [];
      }
      $lead = self::add_field_aliases($lead);
    }
    
    return $results;
  }
}