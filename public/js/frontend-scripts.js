/**
 * ISO42K Assessment Flow (Front-end)
 * Version: 7.3.1 - Enhanced error handling and nonce validation
 */

(function () {
  'use strict';

  // ===== UTILITY FUNCTIONS =====
  
  function qs(sel, root) { 
    return (root || document).querySelector(sel); 
  }
  
  function qsa(sel, root) { 
    return Array.from((root || document).querySelectorAll(sel)); 
  }

  function escHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function staffRangeToNumeric(range) {
    if (range === '1-10') return 5;
    if (range === '11-20') return 15;
    if (range === '20+' || range === '21+') return 25;
    return 5;
  }

  // ===== CONFIGURATION VALIDATION =====
  
  function validateConfig() {
    var errors = [];

    if (typeof ISO42K === 'undefined') {
      errors.push('ISO42K configuration object not found');
    } else {
      if (!ISO42K.ajax_url) {
        errors.push('AJAX URL not configured');
      }
      if (!ISO42K.nonce) {
        errors.push('Security nonce not configured');
      }
    }

    if (errors.length > 0) {
      console.error('ISO42K Configuration Errors:', errors);
      
      // Show user-friendly error
      var wrapper = qs('#iso42k-wrapper');
      if (wrapper) {
        wrapper.innerHTML = '<div style="padding:30px;background:#fee;border:2px solid #f00;border-radius:10px;text-align:center;max-width:600px;margin:40px auto;">' +
          '<h3 style="color:#991b1b;margin:0 0 15px;">⚠️ Configuration Error</h3>' +
          '<p style="margin:0 0 10px;color:#7c2d12;">The assessment form is not properly configured.</p>' +
          '<p style="margin:0 0 15px;color:#7c2d12;font-size:14px;">Please contact the site administrator or try refreshing the page.</p>' +
          '<button onclick="location.reload()" class="button" style="padding:10px 20px;background:#dc2626;color:#fff;border:none;border-radius:6px;cursor:pointer;">Reload Page</button>' +
          '<details style="margin-top:15px;text-align:left;background:#fff;padding:10px;border-radius:5px;">' +
          '<summary style="cursor:pointer;font-weight:600;">Technical Details</summary>' +
          '<pre style="font-size:12px;margin:10px 0 0;color:#666;white-space:pre-wrap;">' + JSON.stringify(errors, null, 2) + '</pre>' +
          '</details>' +
          '</div>';
      }
      
      return false;
    }

    console.log('✅ ISO42K Configuration OK:', {
      ajax_url: ISO42K.ajax_url,
      nonce_length: ISO42K.nonce.length,
      version: ISO42K.version || 'unknown'
    });

    return true;
  }

  // Run validation immediately
  if (!validateConfig()) {
    console.error('ISO42K initialization aborted due to configuration errors');
    return; // Stop script execution
  }

  // ===== ENHANCED POST FORM WITH NONCE REFRESH =====
  
  function refreshNonceAndRetry(originalRequest) {
    console.log('🔄 Nonce expired, attempting refresh...');
    
    return fetch(ISO42K.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({
        action: 'iso42k_refresh_nonce'
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success && res.data.nonce) {
        console.log('✅ Nonce refreshed successfully');
        ISO42K.nonce = res.data.nonce;
        
        // Retry original request with new nonce
        return originalRequest();
      } else {
        throw new Error('Failed to refresh security token. Please reload the page.');
      }
    })
    .catch(function(err) {
      console.error('Nonce refresh failed:', err);
      throw new Error('Security token expired. Please reload the page to continue.');
    });
  }

  function postForm(url, data) {
    var attemptRequest = function() {
      var body = new URLSearchParams();
      
      Object.keys(data || {}).forEach(function (k) {
        var v = data[k];
        if (v === undefined || v === null) return;
        if (Array.isArray(v)) {
          v.forEach(function (item) { body.append(k + '[]', item); });
        } else if (typeof v === 'object') {
          Object.keys(v).forEach(function (kk) {
            body.append(k + '[' + kk + ']', v[kk]);
          });
        } else {
          body.append(k, String(v));
        }
      });

      return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString()
      }).then(function (r) {
        return r.text().then(function (t) {
          if (t === '0') return { _raw: '0' };
          
          try { 
            var json = JSON.parse(t);
            
            // Check for nonce error and auto-refresh
            if (!json.success && json.data && json.data.message) {
              var msg = json.data.message.toLowerCase();
              if (msg.includes('security check failed') || msg.includes('nonce')) {
                console.warn('⚠️ Nonce validation failed, attempting refresh...');
                return refreshNonceAndRetry(attemptRequest);
              }
            }
            
            return json;
          } catch (e) { 
            console.error('JSON parse error:', e, 'Response:', t.substring(0, 200));
            return { _raw: t }; 
          }
        });
      });
    };
    
    return attemptRequest();
  }

  // ===== STATE MANAGEMENT =====
  
  var STATE = {
    org: '',
    staffRange: '',
    staff: 1,
    index: 0,
    total: 0,
    answers: []
  };

  window.ISO42K_STATE = STATE;

  // ===== UI FUNCTIONS =====
  
  function showStep(stepId) {
    ['#iso42k-step-intro', '#iso42k-step-questions', '#iso42k-step-contact', '#iso42k-step-results']
      .forEach(function (id) {
        var el = qs(id);
        if (!el) return;
        el.classList.toggle('is-active', id === stepId);
      });
  }

  function setIntroError(msg) {
    var el = qs('#iso42k-intro-error');
    if (el) {
      el.innerHTML = msg || '';
      el.style.display = msg ? 'block' : 'none';
    }
  }

  function setQError(msg) {
    var el = qs('#iso42k-q-error');
    if (!el) return;
    if (!msg) {
      el.style.display = 'none';
      el.innerHTML = '';
    } else {
      el.style.display = 'block';
      el.innerHTML = msg;
    }
  }

  function setContactError(msg) {
    var el = qs('#iso42k-contact-error');
    if (!el) return;
    if (!msg) {
      el.style.display = 'none';
      el.innerHTML = '';
    } else {
      el.style.display = 'block';
      el.innerHTML = msg;
    }
  }

  // ===== CALCULATION FUNCTIONS =====
  
  function computePercent(total) {
    // EcoVadis scoring: A=100, B=50, C=0 (simplified client-side calculation)
    // Note: Server-side uses impact weighting, but for progress display we use simple average
    var map = { A: 100, B: 50, C: 0 };
    var score = 0;
    for (var i = 0; i < total; i++) {
      var a = (STATE.answers[i] || 'C').toUpperCase();
      score += (map[a] != null ? map[a] : 0);
    }
    return Math.round((score / (total * 100)) * 100);
  }

  function computeMaturity(percent) {
    if (percent >= 86) return 'Leading';
    if (percent >= 71) return 'Advanced';
    if (percent >= 51) return 'Established';
    if (percent >= 31) return 'Developing';
    return 'Initial';
  }

  function maturityExplain(maturity) {
    if (maturity === 'Initial') return 'Minimal sustainability practices. Ad hoc or non-existent policies. Priority is establishing foundational sustainability policies and systematic tracking.';
    if (maturity === 'Developing') return 'Basic sustainability framework with partial policy implementation. Focus on strengthening existing initiatives and expanding coverage across all themes.';
    if (maturity === 'Established') return 'Systematic sustainability programs with comprehensive policies. Priority is continuous improvement, external verification, and supply chain engagement.';
    if (maturity === 'Advanced') return 'Integrated sustainability strategy with industry-leading practices. Focus on innovation, thought leadership, and setting industry benchmarks.';
    if (maturity === 'Leading') return 'Sustainability deeply embedded in business model with full transparency and third-party verification. Continue driving innovation and influencing your value chain.';
    return '';
  }

  // ===== PROGRESS UI =====
  
  function updateProgressUI() {
    var pct = STATE.total ? Math.round(((STATE.index + 1) / STATE.total) * 100) : 0;
    var text = qs('#iso42k-progress-text');
    var pctEl = qs('#iso42k-progress-pct');
    var fill = qs('#iso42k-progress-fill');

    if (text) text.textContent = 'Question ' + (STATE.index + 1) + ' of ' + STATE.total;
    if (pctEl) pctEl.textContent = pct + '%';
    if (fill) fill.style.width = pct + '%';
  }

  // ===== QUESTION HANDLING =====
  
  function applyQuestion(q) {
    var theme = qs('#iso42k-theme');
    var question = qs('#iso42k-question');
    if (theme) theme.textContent = q.theme || '';
    if (question) question.textContent = q.text || '';

    qsa('.iso42k-option').forEach(function (btn) {
      btn.classList.remove('is-selected');
      var a = btn.getAttribute('data-answer');
      if (a && STATE.answers[STATE.index] === a) {
        btn.classList.add('is-selected');
      }
    });

    var prev = qs('#iso42k-prev');
    var next = qs('#iso42k-next');
    if (prev) prev.disabled = (STATE.index <= 0);
    if (next) {
      next.disabled = !STATE.answers[STATE.index];
      next.textContent = (STATE.index >= STATE.total - 1) ? 'Finish' : 'Next';
    }

    updateProgressUI();
  }

  function loadQuestion(index) {
    setQError('');

    var payload = {
      action: 'iso42k_get_question',
      nonce: ISO42K.nonce,
      index: index,
      staff: STATE.staff
    };

    return postForm(ISO42K.ajax_url, payload)
      .then(function (res) {
        if (!res || res._raw === '0') {
          setQError('⚠️ <strong>Server Error:</strong> Question handler not found. Please contact the site administrator.');
          return;
        }
        
        if (!res.success) {
          var errorMsg = (res.data && res.data.message) || 'Failed to load question.';
          setQError('⚠️ <strong>Error:</strong> ' + escHtml(errorMsg));
          return;
        }

        STATE.total = parseInt(res.data.total, 10) || 0;
        STATE.index = index;

        if (!STATE.total || !res.data.question) {
          setQError('⚠️ <strong>Data Error:</strong> Question data is missing. Please try refreshing the page.');
          return;
        }

        applyQuestion(res.data.question);
      })
      .catch(function (err) {
        console.error('Load question error:', err);
        setQError('🔌 <strong>Network Error:</strong> ' + err.message + '<br><small>Please check your internet connection and try again.</small>');
      });
  }

  // ===== CONTACT STEP =====
  
  function goToContactStep() {
    var percent = computePercent(STATE.total);
    var maturity = computeMaturity(percent);

    console.log('📊 Contact step:', {
      org: STATE.org,
      staff: STATE.staff,
      total: STATE.total,
      answers: STATE.answers.length,
      score: percent + '%',
      maturity: maturity
    });

    var pEl = qs('#iso42k-preview-percent');
    var mEl = qs('#iso42k-preview-maturity');
    var ex = qs('#iso42k-preview-explain');
    if (pEl) pEl.textContent = percent + '%';
    if (mEl) mEl.textContent = maturity;
    if (ex) ex.textContent = maturityExplain(maturity);

    setContactError('');
    showStep('#iso42k-step-contact');
  }

  // ===== FORM SUBMISSION =====
  
  function submitWithContact() {
    setContactError('');

    var name = (qs('#iso42k-contact-name') && qs('#iso42k-contact-name').value || '').trim();
    var email = (qs('#iso42k-contact-email') && qs('#iso42k-contact-email').value || '').trim();
    var phone = (qs('#iso42k-contact-phone') && qs('#iso42k-contact-phone').value || '').trim();

    if (!name || !email || !phone) {
      setContactError('❌ Please enter Name, Email and Phone Number.');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setContactError('❌ Please enter a valid email address.');
      return;
    }

    var payload = {
      action: 'iso42k_submit',
      nonce: ISO42K.nonce,
      staff: STATE.staff,
      answers: STATE.answers,
      contact: {
        org: STATE.org,
        name: name,
        email: email,
        phone: phone
      }
    };

    console.log('🚀 STAGE 1: Quick Submit', {
      org: payload.contact.org,
      staff: payload.staff,
      answers: payload.answers.length
    });

    var btn = qs('#iso42k-submit');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Saving assessment...';
    }

    // ===== STAGE 1: QUICK SUBMIT =====
    postForm(ISO42K.ajax_url, payload)
      .then(function (res) {
        console.log('📨 Stage 1 Response:', res);

        if (!res || res._raw === '0') {
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'Get Results';
          }
          setContactError('❌ <strong>Server Error:</strong> Submit handler not found.<br><small>Please contact support at <strong>info@gabriel.hk</strong></small>');
          return;
        }

        if (!res.success) {
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'Get Results';
          }
          
          var errorMessage = res.data && res.data.message 
            ? res.data.message 
            : 'Submission failed. Please try again.';
          
          setContactError('❌ <strong>Error:</strong> ' + escHtml(errorMessage) + '<br><small>If this continues, please contact <strong>info@gabriel.hk</strong></small>');
          return;
        }

        var data = res.data || {};
        var percent = data.percent || 0;
        var maturity = data.maturity || 'Initial';
        var leadId = data.lead_id || 0;

        console.log('✅ Stage 1 Complete:', {
          leadId: leadId,
          score: percent + '%',
          maturity: maturity
        });

        // Show results screen immediately
        var fp = qs('#iso42k-final-percent');
        var fm = qs('#iso42k-final-maturity');
        var fx = qs('#iso42k-maturity-explain');
        if (fp) fp.textContent = percent + '%';
        if (fm) fm.textContent = maturity;
        if (fx) fx.textContent = maturityExplain(maturity);

        // Show "processing" message
        var es = qs('#iso42k-email-status');
        if (es) {
          es.innerHTML = '<div style="padding:24px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;margin-top:20px;">' +
            '<div style="font-size:20px;font-weight:600;margin-bottom:12px;color:#92400e;">⏳ Processing Your Detailed Results</div>' +
            '<p style="margin:0 0 8px;color:#78350f;font-size:15px;">We are generating your personalized gap analysis with AI-powered recommendations and PDF report.</p>' +
            '<p style="margin:0;color:#78350f;font-size:16px;font-weight:700;">Results will be emailed to ' + escHtml(email) + ' within 2-3 minutes</p>' +
            '<div class="spinner is-active" style="margin:20px auto;float:none;"></div>' +
            '<p style="margin:15px 0 0;color:#92400e;font-size:13px;">Please keep this page open while we complete the analysis...</p>' +
            '</div>';
        }

        showStep('#iso42k-step-results');

        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Processing AI analysis...';
        }

        // ===== STAGE 2: BACKGROUND PROCESSING =====
        console.log('🔄 Stage 2: Starting background processing (AI + PDF + Email)...');

        setTimeout(function() {
          postForm(ISO42K.ajax_url, {
            action: 'iso42k_process_background',
            nonce: ISO42K.nonce,
            lead_id: leadId
          })
          .then(function(bgRes) {
            console.log('📨 Stage 2 Response:', bgRes);

            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Get Results';
            }

            if (!bgRes || !bgRes.success) {
              console.error('Background processing failed:', bgRes);
              
              if (es) {
                es.innerHTML = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                  '<p style="margin:0;color:#92400e;font-size:15px;">⚠️ Your assessment has been saved.</p>' +
                  '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                  '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support at <strong>info@gabriel.hk</strong></p>' +
                  '</div>';
              }
              return;
            }

            var bgData = bgRes.data || {};
            
            console.log('✅ Stage 2 Complete:', {
              ai: bgData.ai_generated,
              pdf: bgData.pdf_generated,
              email: bgData.email_user_sent
            });
            
            // Update status with final results
            var statusHtml = '';
            
            if (bgData.email_user_sent) {
              statusHtml = '<div style="padding:24px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;text-align:center;">' +
                '<div style="font-size:48px;margin-bottom:12px;">✉️</div>' +
                '<p style="margin:0;color:#065f46;font-size:18px;font-weight:700;">Results Sent Successfully!</p>' +
                '<p style="margin:12px 0;color:#047857;font-size:15px;">A comprehensive email with your personalized gap analysis has been sent to:</p>' +
                '<p style="margin:0;color:#065f46;font-size:16px;font-weight:600;">' + escHtml(email) + '</p>';
              
              if (bgData.ai_generated) {
                statusHtml += '<p style="margin:12px 0 0;color:#047857;font-size:14px;">✓ AI-powered recommendations included</p>';
              }
              
              if (bgData.pdf_generated) {
                statusHtml += '<p style="margin:4px 0 0;color:#047857;font-size:14px;">✓ Downloadable PDF report included</p>';
              }
              
              statusHtml += '</div>';
              
              // Show PDF download link on page too
              if (bgData.pdf_url) {
                var pdfSection = qs('#iso42k-pdf-download-section');
                if (pdfSection) {
                  pdfSection.innerHTML = '<div style="margin-top:20px;padding:24px;background:linear-gradient(135deg,#3b82f6,#1e40af);border-radius:12px;text-align:center;">' +
                    '<div style="font-size:40px;margin-bottom:10px;">📄</div>' +
                    '<h3 style="margin:0 0 10px;color:#fff;font-size:18px;">Your Detailed Report</h3>' +
                    '<p style="margin:0 0 20px;color:#e0e7ff;font-size:14px;">Complete gap analysis with all your answers and AI recommendations</p>' +
                    '<a href="' + bgData.pdf_url + '" target="_blank" class="iso42k-btn-primary" style="display:inline-block;background:#fff;color:#1e40af;padding:14px 28px;text-decoration:none;border-radius:10px;font-weight:700;">Download PDF Report</a>' +
                    '</div>';
                  pdfSection.style.display = 'block';
                }
              }
            } else {
              statusHtml = '<div style="padding:20px;background:#fee;border:1px solid #fcc;border-radius:12px;">' +
                '<p style="margin:0;color:#991b1b;font-size:15px;">⚠️ Email sending encountered an issue.</p>' +
                '<p style="margin:10px 0 0;color:#7c2d12;">Your assessment has been saved. Please contact us at <strong>info@gabriel.hk</strong> or call <strong>+852 XXXX XXXX</strong> to receive your results.</p>' +
                '</div>';
            }
            
            if (es) {
              es.innerHTML = statusHtml;
            }
          })
          .catch(function(err) {
            console.error('Stage 2 error:', err);
            
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Get Results';
            }
            
            if (es) {
              es.innerHTML = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;">' +
                '<p style="margin:0;color:#92400e;font-size:15px;">⏳ Your assessment is being processed.</p>' +
                '<p style="margin:10px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' within a few minutes.</p>' +
                '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it, please contact <strong>info@gabriel.hk</strong></p>' +
                '</div>';
            }
          });
        }, 500);
      })
      .catch(function (err) {
        console.error('Stage 1 error:', err);
        
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Get Results';
        }
        
        var errorMsg = '';
        
        // Check for specific error types
        if (err.message && err.message.includes('Failed to fetch')) {
          errorMsg = '🔌 <strong>Connection Error</strong><br>Unable to reach the server. Please check your internet connection and try again.';
        } else if (err.message && err.message.includes('NetworkError')) {
          errorMsg = '🌐 <strong>Network Error</strong><br>Your request could not be completed. Please check your internet connection.';
        } else if (err.message && err.message.includes('timeout')) {
          errorMsg = '⏱️ <strong>Request Timeout</strong><br>The server took too long to respond. Please try again.';
        } else if (err.message && err.message.includes('Security token expired')) {
          errorMsg = '🔒 <strong>Session Expired</strong><br>Your session has expired. Please reload the page to continue.';
        } else if (err.name === 'TypeError') {
          errorMsg = '⚠️ <strong>Technical Error</strong><br>There was a problem processing your request. Please refresh the page and try again.';
        } else {
          errorMsg = '❌ <strong>Submission Error</strong><br>';
          if (err.message) {
            errorMsg += escHtml(err.message);
          } else {
            errorMsg += 'An unexpected error occurred. Please try again.';
          }
        }
        
        // Add contact information for persistent issues
        errorMsg += '<br><br><small style="color:#6b7280;">If this problem persists, please contact us at <strong>info@gabriel.hk</strong> or call <strong>+852 XXXX XXXX</strong> for immediate assistance.</small>';
        
        setContactError(errorMsg);
        
        // Log detailed error for debugging
        console.error('Detailed error info:', {
          name: err.name,
          message: err.message,
          stack: err.stack,
          type: typeof err
        });
      });
  }

  // ===== EVENT HANDLERS =====
  
  function onClick(e) {
    var t = e.target;
    if (!t) return;

    if (t.id === 'iso42k-start') {
      setIntroError('');
      
      if (!window.ISO42K || !ISO42K.ajax_url || !ISO42K.nonce) {
        setIntroError('⚠️ Configuration missing. Please refresh the page.');
        return;
      }

      var orgInput = qs('#iso42k-org') || qs('#iso42k-company');
      var staffInput = qs('#iso42k-staff');
      
      if (!orgInput || !staffInput) {
        setIntroError('⚠️ Form fields not found. Please refresh the page.');
        return;
      }

      var org = (orgInput.value || '').trim();
      var staffRange = (staffInput.value || '').trim();
      
      if (!org || !staffRange) {
        setIntroError('❌ Please enter company name and staff size.');
        return;
      }

      STATE.org = org;
      STATE.staffRange = staffRange;
      STATE.staff = staffRangeToNumeric(staffRange);
      STATE.answers = [];
      STATE.index = 0;
      STATE.total = 0;

      console.log('🏢 Started:', STATE.org, '(' + STATE.staff + ' staff)');

      showStep('#iso42k-step-questions');
      setQError('');

      postForm(ISO42K.ajax_url, { action: 'iso42k_track_start', nonce: ISO42K.nonce })
        .catch(function () {})
        .finally(function () {
          loadQuestion(0);
        });
      return;
    }

    var opt = t.closest ? t.closest('.iso42k-option') : null;
    if (opt && opt.getAttribute('data-answer')) {
      var a = opt.getAttribute('data-answer');
      if (a === 'A' || a === 'B' || a === 'C') {
        STATE.answers[STATE.index] = a;
        console.log('Q' + (STATE.index + 1) + ' = ' + a);
        setQError('');
        qsa('.iso42k-option').forEach(function (b) { b.classList.remove('is-selected'); });
        opt.classList.add('is-selected');

        var next = qs('#iso42k-next');
        if (next) next.disabled = false;
      }
      return;
    }

    if (t.id === 'iso42k-prev') {
      if (STATE.index > 0) loadQuestion(STATE.index - 1);
      return;
    }

    if (t.id === 'iso42k-next') {
      if (!STATE.answers[STATE.index]) {
        setQError('❌ Please select an answer before continuing.');
        return;
      }
      if (STATE.index >= STATE.total - 1) {
        goToContactStep();
      } else {
        loadQuestion(STATE.index + 1);
      }
      return;
    }

    if (t.id === 'iso42k-review') {
      showStep('#iso42k-step-questions');
      if (STATE.index < STATE.total) {
        loadQuestion(STATE.index);
      }
      return;
    }

    if (t.id === 'iso42k-submit') {
      submitWithContact();
      return;
    }

    if (t.id === 'iso42k-restart') {
      if (confirm('Are you sure you want to start a new assessment? Your current progress will be lost.')) {
        window.location.reload();
      }
    }
  }

  function onKeyDown(e) {
    if (!qs('#iso42k-step-questions.is-active')) return;

    if (e.key === '1') { e.preventDefault(); var b1 = qs('.iso42k-option[data-answer="A"]'); if (b1) b1.click(); }
    if (e.key === '2') { e.preventDefault(); var b2 = qs('.iso42k-option[data-answer="B"]'); if (b2) b2.click(); }
    if (e.key === '3') { e.preventDefault(); var b3 = qs('.iso42k-option[data-answer="C"]'); if (b3) b3.click(); }

    if (e.key === 'ArrowLeft') { e.preventDefault(); var prev = qs('#iso42k-prev'); if (prev && !prev.disabled) prev.click(); }
    if (e.key === 'ArrowRight') { e.preventDefault(); var next = qs('#iso42k-next'); if (next && !next.disabled) next.click(); }
  }

  // ===== INITIALIZE =====
  
  document.addEventListener('click', onClick);
  document.addEventListener('keydown', onKeyDown);

  console.log('ISO42K Flow v7.3.1 initialized');
  console.log('Configuration:', {
    ajax_url: ISO42K.ajax_url,
    nonce_available: !!ISO42K.nonce,
    version: ISO42K.version || 'unknown'
  });

})();