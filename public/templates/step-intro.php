<?php if (!defined('ABSPATH')) exit; ?>

<section id="iso42k-step-intro" class="iso42k-step is-active">
  <div class="iso42k-hero">
    <h1 class="iso42k-title">ECOVADIS SELF ASSESSMENT</h1>
    <p class="iso42k-subtitle">Discover your company's sustainability maturity level with our comprehensive assessment</p>

    <div class="iso42k-expect">
      <h3 class="iso42k-expect-title">What to expect:</h3>
      <ul class="iso42k-expect-list">
        <li>Answer questions across Environment, Labor & Human Rights, Ethics, and Procurement themes</li>
        <li>Receive a sustainability maturity score and actionable recommendations</li>
        <li>Get detailed results by email in a professional format</li>
        <li>Duration: approximately 10-15 minutes</li>
      </ul>
    </div>

    <div class="iso42k-form">
      <label class="iso42k-label">Organization Name <span class="iso42k-required">*</span></label>
      <input id="iso42k-org" class="iso42k-input" type="text" 
             placeholder="e.g., Acme Corporation Ltd" 
             required 
             autocomplete="organization" />

      <label class="iso42k-label">Company Size <span class="iso42k-required">*</span></label>
      <select id="iso42k-staff" class="iso42k-input" required>
        <option value="">Select staff count...</option>
        <option value="1-10">1-10 employees</option>
        <option value="11-24">11-24 employees</option>
        <option value="25+">25+ employees (includes Procurement questions)</option>
      </select>
      
      <div class="iso42k-center-note">Your answers are confidential and will help assess your sustainability practices.</div>

      <button id="iso42k-start" class="iso42k-btn-primary" type="button">Begin Assessment →</button>

      <div id="iso42k-intro-error" class="iso42k-error" aria-live="polite"></div>
    </div>
  </div>
</section>