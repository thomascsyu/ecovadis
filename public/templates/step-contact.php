<?php if (!defined('ABSPATH')) exit; ?>

<section id="iso42k-step-contact" class="iso42k-step">
  <div class="iso42k-card">
    <h2 class="iso42k-section-title">Your Preliminary Security Maturity</h2>
    <p class="iso42k-muted">Based on your responses so far. A full analysis will be emailed.</p>

    <div class="iso42k-preview">
      <div class="iso42k-preview-score">
        <div class="iso42k-preview-label">Current Score</div>
        <div id="iso42k-preview-percent" class="iso42k-preview-value">0%</div>
      </div>
      <div class="iso42k-preview-score">
        <div class="iso42k-preview-label">Maturity Level</div>
        <div id="iso42k-preview-maturity" class="iso42k-preview-value">Initial</div>
      </div>
    </div>

    <div id="iso42k-preview-explain" class="iso42k-muted" style="margin-top:10px;"></div>

    <h2 class="iso42k-section-title" style="margin-top:18px;">Contact Information</h2>
    <p class="iso42k-muted">Enter your details to receive your results and recommendations by email.</p>

    <div class="iso42k-form">
      <label class="iso42k-label">Contact Name <span class="iso42k-required">*</span></label>
      <input id="iso42k-contact-name" class="iso42k-input" type="text" placeholder="e.g., Alex Chan" />

      <label class="iso42k-label">Email Address <span class="iso42k-required">*</span></label>
      <input id="iso42k-contact-email" class="iso42k-input" type="email" placeholder="e.g., alex@company.com" />
      <div class="iso42k-help">We'll only use this to send your assessment results.</div>

      <label class="iso42k-label">Phone Number <span class="iso42k-required">*</span></label>
      <input id="iso42k-contact-phone" class="iso42k-input" type="text" placeholder="e.g., +852 9123 4567" />

      <div class="iso42k-nav">
        <button id="iso42k-review" type="button" class="iso42k-btn-secondary">Review Answers</button>
        <button id="iso42k-submit" type="button" class="iso42k-btn-primary">Get Results</button>
      </div>

      <div id="iso42k-contact-error" class="iso42k-error" aria-live="polite" style="display:none;"></div>
    </div>
  </div>
</section>