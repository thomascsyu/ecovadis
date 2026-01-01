<?php if (!defined('ABSPATH')) exit; ?>

<section id="iso42k-step-intro" class="iso42k-step is-active">
  <div class="iso42k-hero">
    <h1 class="iso42k-title">ISO 42001 SELF ASSESSMENT</h1>
    <p class="iso42k-subtitle">Discover your company's information security maturity level with our assessment</p>

    <div class="iso42k-expect">
      <h3 class="iso42k-expect-title">What to expect:</h3>
      <ul class="iso42k-expect-list">
        <li>Answer questions aligned to ISO 42001/2023 controls</li>
        <li>Receive a maturity snapshot and recommended next steps</li>
        <li>Get results by email in a professional format</li>
        <li>Duration: approximately 10 minutes</li>
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
        <option value="11-20">11-20 employees</option>
        <option value="20+">20+ employees</option>
      </select>
      
      <div class="iso42k-center-note">Your answers are confidential.</div>

      <button id="iso42k-start" class="iso42k-btn-primary" type="button">Begin Assessment →</button>

      <div id="iso42k-intro-error" class="iso42k-error" aria-live="polite"></div>
    </div>
  </div>
</section>