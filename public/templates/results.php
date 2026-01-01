<?php if (!defined('ABSPATH')) exit; ?>

<section id="iso42k-step-results" class="iso42k-step">
  <div class="iso42k-card">
    <h2 class="iso42k-section-title">Assessment Results</h2>

    <div class="iso42k-preview">
      <div class="iso42k-preview-score">
        <div class="iso42k-preview-label">Overall Score</div>
        <div id="iso42k-final-percent" class="iso42k-preview-value">0%</div>
      </div>
      <div class="iso42k-preview-score">
        <div class="iso42k-preview-label">Information Security Maturity Level</div>
        <div id="iso42k-final-maturity" class="iso42k-preview-value">Initial</div>
      </div>
    </div>

    <div id="iso42k-maturity-explain" class="iso42k-muted" style="margin-top:10px;"></div>

    <div id="iso42k-email-status" class="iso42k-muted" style="margin-top:10px;">
      A copy of your results and recommendations has been sent to your email.
    </div>

    <div id="iso42k-pdf-download-section" style="margin-top:20px;display:none;">
      <!-- Will be populated with download link after background processing -->
    </div>

    <?php
      $email = (array) get_option('iso42k_email_settings', []);
      $meeting_url = esc_url($email['meeting_scheduler_url'] ?? '');
      $btn_text = esc_html($email['meeting_button_text'] ?? 'Book a 30 Min Consultation Call');
      if (!empty($meeting_url)):
    ?>
      <div style="margin-top:16px;">
        <a class="iso42k-btn-primary iso42k-btn-link" target="_blank" rel="noopener" href="<?php echo $meeting_url; ?>">
          <?php echo $btn_text; ?>
        </a>
      </div>
    <?php endif; ?>

    <div style="margin-top:16px;">
      <button id="iso42k-restart" type="button" class="iso42k-btn-secondary">Start New Assessment</button>
    </div>
  </div>
</section>

<script>
window.iso42kShowResults = function(data) {
  console.log('Showing results:', data);
  
  const $status = document.getElementById('iso42k-email-status');
  const $pdfSection = document.getElementById('iso42k-pdf-download-section');
  
  let statusHtml = '';
  
  if (data.processing_background) {
    statusHtml = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;text-align:center;">' +
      '<div style="font-size:18px;margin-bottom:10px;">⏳ Processing Your Results</div>' +
      '<p style="margin:0;color:#92400e;">Your detailed gap analysis with AI-powered recommendations is being generated.</p>' +
      '<p style="margin:10px 0 0;color:#92400e;font-weight:600;">Results will be emailed within the next 2-3 minutes.</p>' +
      '<div class="spinner is-active" style="margin:15px auto;float:none;"></div>' +
      '</div>';
  } else if (data.email_user_sent) {
    statusHtml = '<div style="padding:20px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;text-align:center;">' +
      '<p style="margin:0;color:#065f46;font-size:16px;">✓ <strong>Results sent to your email!</strong></p>' +
      '<p style="margin:10px 0 0;color:#047857;font-size:14px;">Check your inbox for your personalized ISO 42001 gap analysis.</p>' +
      '</div>';
      
    if (data.pdf_url) {
      if ($pdfSection) {
        $pdfSection.innerHTML = '<div style="text-align:center;padding:20px;background:#f0f9ff;border-radius:8px;border:1px solid #3b82f6;">' +
          '<p style="margin:0 0 15px;font-size:16px;color:#1e40af;font-weight:600;">📄 Your Detailed Report is Ready</p>' +
          '<a href="' + data.pdf_url + '" class="iso42k-btn-primary" target="_blank" style="display:inline-block;padding:12px 24px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Download PDF Report</a>' +
          '</div>';
        $pdfSection.style.display = 'block';
      }
    }
  }
  
  if ($status) {
    $status.innerHTML = statusHtml;
  }
};
</script>