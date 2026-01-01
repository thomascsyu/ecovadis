<?php
if (!defined('ABSPATH')) exit;

class ISO42K_PDF {

  /**
   * Build professional PDF HTML with questions and answers.
   */
  public static function build_report_html(array $lead, array $questions = [], array $answers = []): string {
    $email_settings = (array) get_option('iso42k_email_settings', []);
    $brand = sanitize_text_field($email_settings['brand_name'] ?? get_bloginfo('name'));
    $logo  = esc_url($email_settings['company_logo_url'] ?? '');

    $company = esc_html($lead['company'] ?? '');
    $staff   = esc_html($lead['staff'] ?? '');
    $name    = esc_html($lead['name'] ?? '');
    $email   = esc_html($lead['email'] ?? '');
    $phone   = esc_html($lead['phone'] ?? '');

    $percent  = (int) ($lead['percent'] ?? 0);
    $percent  = max(0, min(100, $percent));
    $maturity = esc_html($lead['maturity'] ?? 'Initial');

    $date = esc_html(date_i18n('Y-m-d H:i:s'));

    $meeting = esc_url($email_settings['meeting_scheduler_url'] ?? 'https://calendly.com/gabrielconsultant/30min');

    // AI summary
    $ai = trim((string)($lead['ai_summary'] ?? ''));
    $ai_html = $ai !== '' ? nl2br(esc_html($ai)) : '<em>No AI summary available.</em>';

    // Maturity band
    $band = 'Initial';
    if ($percent >= 85) {
      $band = 'Optimised';
    } elseif ($percent >= 70) {
      $band = 'Established';
    } elseif ($percent >= 40) {
      $band = 'Managed';
    }

    $logo_html = $logo ? '<img src="'.$logo.'" style="height:42px;margin-bottom:10px;" />' : '';

    // Build questions/answers table
    $qa_html = '';
    if (!empty($questions) && !empty($answers)) {
      $qa_rows = '';
      $answer_labels = [
        'A' => 'Fully Implemented',
        'B' => 'Partially Implemented',
        'C' => 'Not Implemented'
      ];
      
      foreach ($questions as $idx => $q) {
        $q_text = esc_html($q['text'] ?? 'Question ' . ($idx + 1));
        $q_theme = esc_html($q['theme'] ?? '');
        $answer = strtoupper($answers[$idx] ?? 'C');
        $answer_text = esc_html($answer_labels[$answer] ?? 'Unknown');
        
        // Color code answers
        $answer_color = '#DC2626'; // Red (C)
        if ($answer === 'A') {
          $answer_color = '#10B981'; // Green
        } elseif ($answer === 'B') {
          $answer_color = '#F59E0B'; // Orange
        }
        
        $qa_rows .= '
          <tr>
            <td style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:10px;width:60px;color:#6b7280;">' . ($idx + 1) . '</td>
            <td style="padding:10px;border-bottom:1px solid #e5e7eb;font-size:10px;">
              ' . ($q_theme ? '<strong style="color:#3b82f6;">' . $q_theme . '</strong><br>' : '') . '
              ' . $q_text . '
            </td>
            <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:center;width:140px;">
              <span style="display:inline-block;padding:4px 8px;border-radius:4px;background:' . $answer_color . ';color:#fff;font-weight:700;font-size:9px;">' . $answer_text . '</span>
            </td>
          </tr>
        ';
      }
      
      $qa_html = '
        <div class="card" style="margin-top:18px;">
          <p class="section-title">Your Detailed Responses</p>
          <table style="width:100%;border-collapse:collapse;margin-top:10px;">
            <thead>
              <tr style="background:#f8fafc;">
                <th style="padding:8px;text-align:left;font-size:10px;color:#6b7280;border-bottom:2px solid #e5e7eb;">#</th>
                <th style="padding:8px;text-align:left;font-size:10px;color:#6b7280;border-bottom:2px solid #e5e7eb;">Question</th>
                <th style="padding:8px;text-align:center;font-size:10px;color:#6b7280;border-bottom:2px solid #e5e7eb;width:140px;">Response</th>
              </tr>
            </thead>
            <tbody>
              ' . $qa_rows . '
            </tbody>
          </table>
        </div>
      ';
    }

    return '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#111827; }
  .page { padding: 22px 26px; }
  .header { border-bottom:1px solid #e5e7eb; padding-bottom:14px; margin-bottom:18px; }
  .title { font-size: 18px; font-weight: 700; margin: 0; }
  .subtitle { margin: 6px 0 0; color:#6b7280; }
  .card { border:1px solid #e5e7eb; border-radius:10px; padding:14px; margin: 12px 0; }
  .row { display: table; width:100%; }
  .col { display: table-cell; width:50%; vertical-align: top; padding-right: 10px; }
  .label { color:#6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; }
  .val { font-size: 12px; margin-top:4px; }
  .score { font-size: 32px; font-weight:800; margin: 6px 0 0; }
  .pill { display:inline-block; padding:6px 10px; border-radius:999px; background:#111827; color:#fff; font-weight:700; font-size: 11px; }
  .barwrap { height:10px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin-top:10px; }
  .bar { height:10px; width: '.$percent.'%; background:#111827; }
  .section-title { font-size: 13px; font-weight: 700; margin: 0 0 8px; }
  .muted { color:#6b7280; }
  .footer { border-top:1px solid #e5e7eb; margin-top:18px; padding-top:12px; font-size: 10px; color:#6b7280; }
  a { color:#111827; }
  table { page-break-inside: auto; }
  tr { page-break-inside: avoid; page-break-after: auto; }
</style>
</head>
<body>
  <div class="page">
    <div class="header">
      '.$logo_html.'
      <p class="title">ISO 42001 Self Assessment Report</p>
      <p class="subtitle">'.$brand.' • Generated '.$date.'</p>
    </div>

    <div class="card">
      <div class="row">
        <div class="col">
          <div class="label">Organisation</div>
          <div class="val">'.$company.'</div>
          <div class="label" style="margin-top:10px;">Staff Size</div>
          <div class="val">'.$staff.'</div>
        </div>
        <div class="col">
          <div class="label">Contact</div>
          <div class="val">'.$name.'</div>
          <div class="val">'.$email.'</div>
          <div class="val">'.$phone.'</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="label">Overall Score</div>
      <div class="score">'.$percent.'%</div>
      <div style="margin-top:8px;">
        <span class="pill">Maturity: '.$maturity.'</span>
        <span class="pill" style="background:#374151;margin-left:8px;">Band: '.esc_html($band).'</span>
      </div>
      <div class="barwrap"><div class="bar"></div></div>
      <div class="muted" style="margin-top:8px;">This score indicates your current alignment with common ISO 42001 control expectations based on your answers.</div>
    </div>

    <div class="card">
      <p class="section-title">AI-Powered Gap Summary</p>
      <div>'.$ai_html.'</div>
    </div>

    ' . $qa_html . '

    <div class="card">
      <p class="section-title">Recommended Next Step</p>
      <div class="muted">
        Book a 30-minute consultation to review gaps and build a practical remediation roadmap:
        <br><strong><a href="'.$meeting.'">'.$meeting.'</a></strong>
      </div>
    </div>

    <div class="footer">
      <div><strong>Gabriel Consultant Ltd (Hong Kong)</strong></div>
      <div>Email: info@gabriel.hk • Phone: +852 23664622 • Website: www.gabriel.hk</div>
      <div style="margin-top:6px;">Meeting link: '.$meeting.'</div>
    </div>
  </div>
</body>
</html>';
  }

  /**
   * Generate PDF file + return array with path/url/attachment_id.
   */
  public static function generate_pdf_and_attach(array $lead, array $questions = [], array $answers = []): array|\WP_Error {
    $upload = wp_upload_dir();
    
    if ($upload['error']) {
        ISO42K_Logger::log('❌ Upload directory error: ' . $upload['error']);
        return new \WP_Error('iso42k_upload_error', $upload['error']);
    }
    
    $dir = trailingslashit($upload['basedir']) . 'iso42k-reports/';
    $url = trailingslashit($upload['baseurl']) . 'iso42k-reports/';

    ISO42K_Logger::log('PDF directory: ' . $dir);
    
    if (!file_exists($dir)) {
        ISO42K_Logger::log('PDF directory does not exist, creating...');
        if (!wp_mkdir_p($dir)) {
            ISO42K_Logger::log('❌ Failed to create directory: ' . $dir);
            return new \WP_Error('iso42k_pdf_dir', 'Unable to create reports directory: ' . $dir);
        }
        ISO42K_Logger::log('✅ Directory created');
    }
    
    if (!is_writable($dir)) {
        ISO42K_Logger::log('❌ Directory not writable: ' . $dir);
        return new \WP_Error('iso42k_pdf_dir', 'Reports directory is not writable: ' . $dir);
    }

    $company = sanitize_file_name($lead['company'] ?? 'report');
    $filename = $company . '-iso42001-assessment-' . date('Ymd-His') . '.pdf';
    $path = $dir . $filename;
    
    ISO42K_Logger::log('PDF target file: ' . $path);

    // Build HTML
    $html = self::build_report_html($lead, $questions, $answers);
    
    ISO42K_Logger::log('PDF HTML generated: ' . strlen($html) . ' chars');

    // Render to PDF
    if (class_exists('\Dompdf\Dompdf')) {
        try {
            ISO42K_Logger::log('Using Dompdf...');
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'chroot' => $dir,
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $output = $dompdf->output();
            $bytes_written = file_put_contents($path, $output);
            
            if ($bytes_written === false) {
                ISO42K_Logger::log('❌ Failed to write PDF file');
                return new \WP_Error('iso42k_pdf_write', 'Failed to write PDF file');
            }
            
            ISO42K_Logger::log('✅ PDF written: ' . size_format($bytes_written));
            
        } catch (\Throwable $e) {
            ISO42K_Logger::log('❌ Dompdf error: ' . $e->getMessage());
            return new \WP_Error('iso42k_dompdf', 'Dompdf error: ' . $e->getMessage());
        }
    } elseif (class_exists('\Mpdf\Mpdf')) {
        try {
            ISO42K_Logger::log('Using mPDF...');
            $mpdf = new \Mpdf\Mpdf(['tempDir' => WP_CONTENT_DIR . '/uploads']);
            $mpdf->WriteHTML($html);
            $mpdf->Output($path, \Mpdf\Output\Destination::FILE);
            ISO42K_Logger::log('✅ PDF generated with mPDF');
        } catch (\Throwable $e) {
            ISO42K_Logger::log('❌ mPDF error: ' . $e->getMessage());
            return new \WP_Error('iso42k_mpdf', 'mPDF error: ' . $e->getMessage());
        }
    } else {
        // Provide a fallback by creating a simple HTML report instead of PDF
        ISO42K_Logger::log('⚠️ No PDF library found, creating HTML report as fallback');
        
        $html_path = str_replace('.pdf', '_report.html', $path);
        $bytes_written = file_put_contents($html_path, $html);
        
        if ($bytes_written === false) {
            ISO42K_Logger::log('❌ Failed to write HTML report file');
            return new \WP_Error('iso42k_html_write', 'Failed to write HTML report file');
        }
        
        ISO42K_Logger::log('✅ HTML report created as fallback: ' . size_format($bytes_written));
        
        // Update the path to HTML file for further processing
        $path = $html_path;
        $file_url = $url . basename($html_path);
    }

    // Verify file was created
    if (!file_exists($path)) {
        ISO42K_Logger::log('❌ PDF file was not created at: ' . $path);
        return new \WP_Error('iso42k_pdf_file', 'PDF file was not created');
    }

    // Only update file_url if it wasn't already set for HTML fallback
    if (!isset($file_url)) {
        $file_url = $url . $filename;
    }
    ISO42K_Logger::log('✅ File URL: ' . $file_url);

    // Optional: add to Media Library
    $attachment_id = 0;
    $filetype = wp_check_filetype(basename($path), null);

    $attachment = [
      'post_mime_type' => $filetype['type'] ?: (pathinfo($path, PATHINFO_EXTENSION) === 'html' ? 'text/html' : 'application/pdf'),
      'post_title'     => sanitize_text_field(basename($path)),
      'post_content'   => '',
      'post_status'    => 'inherit'
    ];

    $attachment_id = wp_insert_attachment($attachment, $path);
    if (!is_wp_error($attachment_id) && $attachment_id > 0) {
      require_once ABSPATH . 'wp-admin/includes/image.php';
      wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $path));
      $file_type_log = pathinfo($path, PATHINFO_EXTENSION) === 'html' ? 'HTML report' : 'PDF';
      ISO42K_Logger::log('✅ ' . $file_type_log . ' added to media library: ID ' . $attachment_id);
    }

    return [
      'path'          => $path,
      'url'           => $file_url,
      'attachment_id' => (int) $attachment_id,
    ];
  }
}