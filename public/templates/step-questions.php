<?php if (!defined('ABSPATH')) exit; ?>

<section id="iso42k-step-questions" class="iso42k-step">
  <div class="iso42k-progress-wrap">
    <div class="iso42k-progress-top">
      <div id="iso42k-progress-text">Question 1 of 1</div>
      <div id="iso42k-progress-pct">0%</div>
    </div>
    <div class="iso42k-progress-bar" aria-label="Progress">
      <div id="iso42k-progress-fill" class="iso42k-progress-fill"></div>
    </div>
    <div class="iso42k-progress-note">You can review and change answers later.</div>
  </div>

  <div class="iso42k-card">
    <div id="iso42k-theme" class="iso42k-theme"></div>
    <div id="iso42k-question" class="iso42k-question">Loading…</div>

    <div class="iso42k-options">
      <button type="button" class="iso42k-option" data-answer="A">
        <div class="iso42k-option-key">A</div>
        <div class="iso42k-option-text">
          <strong id="iso42k-opt-a-title">Advanced - Fully implemented</strong>
          <div id="iso42k-opt-a-hint" class="iso42k-option-hint">Comprehensive practices fully documented and consistently applied</div>
        </div>
      </button>

      <button type="button" class="iso42k-option" data-answer="B">
        <div class="iso42k-option-key">B</div>
        <div class="iso42k-option-text">
          <strong id="iso42k-opt-b-title">Basic - Partially implemented</strong>
          <div id="iso42k-opt-b-hint" class="iso42k-option-hint">Some measures in place or inconsistent implementation</div>
        </div>
      </button>

      <button type="button" class="iso42k-option" data-answer="C">
        <div class="iso42k-option-key">C</div>
        <div class="iso42k-option-text">
          <strong id="iso42k-opt-c-title">Absent - Not implemented</strong>
          <div id="iso42k-opt-c-hint" class="iso42k-option-hint">No practices or policies in place</div>
        </div>
      </button>
    </div>

    <div class="iso42k-nav">
      <button id="iso42k-prev" type="button" class="iso42k-btn-secondary">Previous</button>
      <button id="iso42k-next" type="button" class="iso42k-btn-primary" disabled>Next</button>
    </div>

    <div class="iso42k-nav-note">You’ll be able to review your answers before submitting.</div>
    <div class="iso42k-hint">Keyboard: ← / → to navigate, 1–3 to select A–C</div>

    <div id="iso42k-q-error" class="iso42k-error" aria-live="polite" style="display:none;"></div>
  </div>
</section>