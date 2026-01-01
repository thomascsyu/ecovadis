<?php if (!defined('ABSPATH')) exit; ?>

<section id="iso42k-step-results" class="iso42k-step">
  <div class="iso42k-card">
    <h2 class="iso42k-section-title">Assessment Complete</h2>

    <div class="iso42k-preview">
      <div class="iso42k-preview-score">
        <div class="iso42k-preview-label">Overall Score</div>
        <div id="iso42k-final-percent" class="iso42k-preview-value">$percentage </div>
      </div>
      <div class="iso42k-preview-score">
        <div class="iso42k-preview-label">Sustainability Maturity Level</div>
        <div id="iso42k-final-maturity" class="iso42k-preview-value">$maturity</div>
      </div>
    </div>

    <div id="iso42k-maturity-explain" class="iso42k-muted" style="margin-top:10px;"></div>

    <div id="iso42k-email-status" style="margin-top:20px;">
      <!-- Will be populated by JavaScript -->
    </div>

    <div id="iso42k-pdf-download-section" style="margin-top:20px;display:none;">
      <!-- PDF download link will appear here after processing -->
    </div>

    <?php
      $email = (array) get_option('iso42k_email_settings', []);
      $meeting_url = esc_url($email['meeting_scheduler_url'] ?? '');
      $btn_text = esc_html($email['meeting_button_text'] ?? 'Book a 30-Min Consultation');
      if (!empty($meeting_url)):
    ?>
      <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e7eb;">
        <p style="text-align:center;margin-bottom:12px;color:#6b7280;">Ready to improve your sustainability performance?</p>
        <div style="text-align:center;">
          <a class="iso42k-btn-primary iso42k-btn-link" target="_blank" rel="noopener" href="<?php echo $meeting_url; ?>">
            <?php echo $btn_text; ?>
          </a>
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-top:20px;text-align:center;">
      <button id="iso42k-restart" type="button" class="iso42k-btn-secondary">Start New Assessment</button>
    </div>
  </div>
</section>
