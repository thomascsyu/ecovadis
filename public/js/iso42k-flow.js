/**
 * ISO42K Assessment Flow (Front-end)
 * Version: 7.3.1 - Two-stage async processing with PDF download links and performance optimization
 */

(function () {
  'use strict';

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

  function postForm(url, data) {
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
    })
    .then(function (r) {
      if (!r.ok) {
        throw new Error(`HTTP error! Status: ${r.status}`);
      }
      return r.text();
    })
    .then(function (t) {
      if (t === '0') return { _raw: '0' };
      try { 
        return JSON.parse(t); 
      } catch (e) { 
        // Check if it's a nonce error in HTML response
        if (t.includes('Security check failed') || t.includes('nonce') || t.toLowerCase().includes('security')) {
          return { success: false, data: { message: 'Security check failed. Please refresh the page.' } };
        }
        console.error('JSON parse error:', e);
        console.error('Raw response:', t.substring(0, 200) + '...');
        return { _raw: t, success: false, data: { message: 'Invalid server response format.' } }; 
      }
    })
    .catch(function(error) {
      console.error('Network error in postForm:', error);
      return { _raw: null, success: false, data: { message: 'Network error: ' + error.message } };
    });
  }

  var STATE = {
    org: '',
    staffRange: '',
    staff: 1,
    index: 0,
    total: 0,
    answers: [],
    questionsCache: {}, // Cache for batch-loaded questions
    currentBatchIndex: -1,
    batchSize: 5 // Load 5 questions at a time for better performance
  };

  window.ISO42K_STATE = STATE;

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
    if (el) el.textContent = msg || '';
  }

  function setQError(msg) {
    var el = qs('#iso42k-q-error');
    if (!el) return;
    if (!msg) {
      el.style.display = 'none';
      el.textContent = '';
    } else {
      el.style.display = 'block';
      el.textContent = msg;
    }
  }

  function setContactError(msg) {
    var el = qs('#iso42k-contact-error');
    if (!el) return;
    if (!msg) {
      el.style.display = 'none';
      el.textContent = '';
    } else {
      el.style.display = 'block';
      el.textContent = msg;
    }
  }

  function getFreshNonce() {
    // Try to get nonce from meta tag first
    var metaTag = document.querySelector('meta[name="iso42k-nonce"]');
    if (metaTag && metaTag.content) {
      return metaTag.content;
    }
    // Fallback to the global ISO42K object
    if (typeof ISO42K !== 'undefined' && ISO42K.nonce) {
      return ISO42K.nonce;
    }
    // If neither is available, return empty string to be caught by validation
    return '';
  }
  
  // Function to refresh nonce from server
  function refreshNonce() {
    return postForm(ISO42K.ajax_url, { 
      action: 'iso42k_refresh_nonce',
      nonce: getFreshNonce()
    }).then(function(res) {
      if (res && res.success && res.data && res.data.nonce) {
        // Update the global ISO42K object with new nonce
        if (typeof ISO42K !== 'undefined') {
          ISO42K.nonce = res.data.nonce;
        }
        // Update the meta tag as well
        var metaTag = document.querySelector('meta[name="iso42k-nonce"]');
        if (metaTag) {
          metaTag.content = res.data.nonce;
        }
        console.log('Nonce refreshed successfully');
        return res.data.nonce;
      }
      console.warn('Nonce refresh failed, using fallback');
      return getFreshNonce(); // fallback to current nonce
    }).catch(function(error) {
      console.error('Nonce refresh error:', error);
      return getFreshNonce(); // fallback to current nonce
    });
  }

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

  function updateProgressUI() {
    var pct = STATE.total ? Math.round(((STATE.index + 1) / STATE.total) * 100) : 0;
    var text = qs('#iso42k-progress-text');
    var pctEl = qs('#iso42k-progress-pct');
    var fill = qs('#iso42k-progress-fill');

    if (text) text.textContent = 'Question ' + (STATE.index + 1) + ' of ' + STATE.total;
    if (pctEl) pctEl.textContent = pct + '%';
    if (fill) fill.style.width = pct + '%';
  }

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
      // Update button text to reflect that all questions need to be completed
      if (STATE.index >= STATE.total - 1) {
        next.textContent = 'Complete Questions';
      } else {
        next.textContent = 'Next';
      }
    }

    updateProgressUI();
    
    // Preload next batch if we're approaching the end of the current batch
    var nextBatchIndex = Math.floor((STATE.index + 1) / STATE.batchSize);
    var currentBatchIndex = Math.floor(STATE.index / STATE.batchSize);
    if (nextBatchIndex > currentBatchIndex && !STATE.questionsCache['batch_' + nextBatchIndex]) {
      // Preload the next batch in the background
      setTimeout(function() {
        preloadBatch(nextBatchIndex);
      }, 100); // Small delay to not interfere with current UI update
    }
  }
  
  function preloadBatch(batchIndex) {
    var cacheKey = 'batch_' + batchIndex;
    if (STATE.questionsCache[cacheKey]) {
      return; // Already loaded
    }
    
    var payload = {
      action: 'iso42k_get_questions_batch',
      nonce: getFreshNonce(),
      start_index: batchIndex * STATE.batchSize,
      count: STATE.batchSize,
      staff: STATE.staff
    };

    // Don't show errors for preloaded batches, just silently cache
    postForm(ISO42K.ajax_url, payload)
      .then(function (res) {
        if (res && res.success) {
          STATE.questionsCache[cacheKey] = res.data;
          // Update total if not already set
          if (STATE.total === 0) {
            STATE.total = parseInt(res.data.total, 10) || 0;
          }
        } else {
          // Check if it's a nonce error and try to refresh (but don't show error since it's preloading)
          var errorMessage = (res.data && res.data.message) || 'Failed to preload questions batch.';
          if (errorMessage.includes('Security check failed')) {
            console.log('Nonce error detected in question preloading, attempting to refresh...');
            refreshNonce().then(function(freshNonce) {
              console.log('Nonce refreshed, retrying question preloading...');
              // Retry with fresh nonce
              var retryPayload = {
                action: 'iso42k_get_questions_batch',
                nonce: freshNonce,
                start_index: batchIndex * STATE.batchSize,
                count: STATE.batchSize,
                staff: STATE.staff
              };
              
              return postForm(ISO42K.ajax_url, retryPayload);
            }).then(function(retryRes) {
              if (retryRes && retryRes.success) {
                STATE.questionsCache[cacheKey] = retryRes.data;
                // Update total if not already set
                if (STATE.total === 0) {
                  STATE.total = parseInt(retryRes.data.total, 10) || 0;
                }
              }
            }).catch(function(err) {
              console.error('Retry question preloading failed:', err);
            });
          }
        }
      })
      .catch(function (error) {
        console.error('Error in preloadBatch:', error);
        // Ignore errors during preloading
      });
  }

  function loadQuestion(index) {
    setQError('');

    // Check if we need to load a new batch
    var batchIndex = Math.floor(index / STATE.batchSize);
    var cacheKey = 'batch_' + batchIndex;
    
    if (!STATE.questionsCache[cacheKey]) {
      // Load a batch of questions
      var payload = {
        action: 'iso42k_get_questions_batch',
        nonce: getFreshNonce(),
        start_index: batchIndex * STATE.batchSize,
        count: STATE.batchSize,
        staff: STATE.staff
      };

      return postForm(ISO42K.ajax_url, payload)
        .then(function (res) {
          if (!res || res._raw === '0') {
            setQError('AJAX handler not found.');
            return;
          }
          if (!res.success) {
            var errorMessage = (res.data && res.data.message) || 'Failed to load questions batch.';
            
            // Check if it's a nonce error and try to refresh
            if (errorMessage.includes('Security check failed')) {
              console.log('Nonce error detected in question loading, attempting to refresh...');
              return refreshNonce().then(function(freshNonce) {
                console.log('Nonce refreshed, retrying question loading...');
                // Retry with fresh nonce
                var retryPayload = {
                  action: 'iso42k_get_questions_batch',
                  nonce: freshNonce,
                  start_index: batchIndex * STATE.batchSize,
                  count: STATE.batchSize,
                  staff: STATE.staff
                };
                
                return postForm(ISO42K.ajax_url, retryPayload);
              }).then(function(retryRes) {
                if (!retryRes || !retryRes.success) {
                  var retryErrorMessage = (retryRes.data && retryRes.data.message) || 'Failed to load questions batch after retry.';
                  setQError(retryErrorMessage);
                  return;
                }
                
                // Success after retry - continue with normal flow
                STATE.questionsCache[cacheKey] = retryRes.data;
                STATE.total = parseInt(retryRes.data.total, 10) || 0;
                
                // Now apply the specific question
                applyQuestionFromBatch(retryRes.data, index);
              }).catch(function(err) {
                console.error('Retry question loading failed:', err);
                setQError('Failed to load questions. Please refresh the page and try again.');
              });
            } else {
              // Not a nonce error, handle normally
              setQError(errorMessage);
              return;
            }
          }

          // Cache the batch
          STATE.questionsCache[cacheKey] = res.data;
          STATE.total = parseInt(res.data.total, 10) || 0;
          
          // Now apply the specific question
          applyQuestionFromBatch(res.data, index);
        })
        .catch(function (error) {
          console.error('Error in loadQuestion:', error);
          setQError('Network error. Please try again.');
        });
    } else {
      // Use cached batch
      STATE.total = parseInt(STATE.questionsCache[cacheKey].total, 10) || 0;
      applyQuestionFromBatch(STATE.questionsCache[cacheKey], index);
    }
  }
  
  function applyQuestionFromBatch(batchData, index) {
    // Find the specific question in the batch
    var questionIndexInBatch = index - batchData.start_index;
    if (questionIndexInBatch >= 0 && questionIndexInBatch < batchData.questions.length) {
      var question = batchData.questions[questionIndexInBatch];
      STATE.index = index;
      
      if (!question) {
        setQError('Question data missing.');
        return;
      }
      
      applyQuestion(question);
    } else {
      setQError('Question not found in batch.');
    }
  }

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

  /**
   * UPDATED: Two-stage submission with background processing
   */
  function submitWithContact() {
    setContactError('');

    var name = (qs('#iso42k-contact-name') && qs('#iso42k-contact-name').value || '').trim();
    var email = (qs('#iso42k-contact-email') && qs('#iso42k-contact-email').value || '').trim();
    var phone = (qs('#iso42k-contact-phone') && qs('#iso42k-contact-phone').value || '').trim();

    if (!name || !email || !phone) {
      setContactError('Please enter Name, Email and Phone Number.');
      return;
    }

    if (!name.trim()) {
      setContactError('Please enter your name.');
      return;
    }

    if (!email.trim()) {
      setContactError('Please enter your email address.');
      return;
    }

    if (!phone.trim()) {
      setContactError('Please enter your phone number.');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setContactError('Please enter a valid email address.');
      return;
    }

    // Additional validation for phone number format
    var phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    var cleanPhone = phone.replace(/[\s\-\(\)\.]/g, '');
    if (!phoneRegex.test(cleanPhone)) {
      setContactError('Please enter a valid phone number.');
      return;
    }

    var payload = {
      action: 'iso42k_submit',
      nonce: getFreshNonce(),
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
          setContactError('Submit handler not found. Please refresh the page and try again.');
          return;
        }

        if (!res.success) {
          var errorMessage = (res.data && res.data.message) || 'Submission failed. Please try again.';
          console.error('Submission error:', errorMessage);
          
          // Check if it's a nonce error and try to refresh
          if (errorMessage.includes('Security check failed')) {
            console.log('Nonce error detected, attempting to refresh...');
            refreshNonce().then(function(freshNonce) {
              console.log('Nonce refreshed, retrying submission...');
              // Retry with fresh nonce
              var retryPayload = {
                action: 'iso42k_submit',
                nonce: freshNonce,
                staff: STATE.staff,
                answers: STATE.answers,
                contact: {
                  org: STATE.org,
                  name: name,
                  email: email,
                  phone: phone
                }
              };
              
              return postForm(ISO42K.ajax_url, retryPayload);
            }).then(function(retryRes) {
              if (btn) {
                btn.disabled = false;
                btn.textContent = 'Get Results';
              }
              
              if (!retryRes || !retryRes.success) {
                var retryErrorMessage = (retryRes.data && retryRes.data.message) || 'Submission failed after retry. Please refresh the page.';
                setContactError(retryErrorMessage);
                return;
              }
              
              // Success after retry - continue with normal flow
              var data = retryRes.data || {};
              var percent = data.percent || 0;
              var maturity = data.maturity || 'Initial';
              var leadId = data.lead_id || 0;

              console.log('✅ Stage 1 Complete (after retry):', {
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
                  nonce: getFreshNonce(),
                  lead_id: leadId
                })
                .then(function(bgRes) {
                  console.log('📨 Stage 2 Response:', bgRes);

                  if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Get Results';
                  }

                  if (!bgRes || !bgRes.success) {
                    var bgErrorMessage = (bgRes.data && bgRes.data.message) || 'Background processing failed.';
                    console.error('Background processing failed:', bgRes);
                    
                    // Check if it's a nonce error for background processing and try to refresh
                    if (bgErrorMessage.includes('Security check failed')) {
                      console.log('Nonce error detected in background processing, attempting to refresh...');
                      refreshNonce().then(function(freshNonce) {
                        console.log('Nonce refreshed, retrying background processing...');
                        // Retry background processing with fresh nonce
                        return postForm(ISO42K.ajax_url, {
                          action: 'iso42k_process_background',
                          nonce: freshNonce,
                          lead_id: leadId
                        });
                      }).then(function(retryBgRes) {
                        if (btn) {
                          btn.disabled = false;
                          btn.textContent = 'Get Results';
                        }

                        if (!retryBgRes || !retryBgRes.success) {
                          console.error('Background processing failed after retry:', retryBgRes);
                          
                          if (es) {
                            es.innerHTML = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                              '<p style="margin:0;color:#92400e;font-size:15px;">⚠️ Your assessment has been saved.</p>' +
                              '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                              '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
                              '</div>';
                          }
                          return;
                        }

                        var retryBgData = retryBgRes.data || {};

                        console.log('✅ Stage 2 Complete (after retry):', {
                          ai: retryBgData.ai_generated,
                          pdf: retryBgData.pdf_generated,
                          email: retryBgData.email_user_sent
                        });

                        // Update status with final results
                        var statusHtml = '';

                        if (retryBgData.email_user_sent) {
                          statusHtml = '<div style="padding:24px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;text-align:center;">' +
                            '<div style="font-size:48px;margin-bottom:12px;">✉️</div>' +
                            '<p style="margin:0;color:#065f46;font-size:18px;font-weight:700;">Results Sent Successfully!</p>' +
                            '<p style="margin:12px 0;color:#047857;font-size:15px;">A comprehensive email with your personalized gap analysis has been sent to:</p>' +
                            '<p style="margin:0;color:#065f46;font-size:16px;font-weight:600;">' + escHtml(email) + '</p>';

                          if (retryBgData.ai_generated) {
                            statusHtml += '<p style="margin:12px 0 0;color:#047857;font-size:14px;">✓ AI-powered recommendations included</p>';
                          }

                          if (retryBgData.pdf_generated) {
                            statusHtml += '<p style="margin:4px 0 0;color:#047857;font-size:14px;">✓ Downloadable PDF report included</p>';
                          }

                          statusHtml += '</div>';

                          // Show PDF download link on page too
                          if (retryBgData.pdf_url) {
                            var pdfSection = qs('#iso42k-pdf-download-section');
                            if (pdfSection) {
                              pdfSection.innerHTML = '<div style="margin-top:20px;padding:24px;background:linear-gradient(135deg,#3b82f6,#1e40af);border-radius:12px;text-align:center;">' +
                                '<div style="font-size:40px;margin-bottom:10px;">📄</div>' +
                                '<h3 style="margin:0 0 10px;color:#fff;font-size:18px;">Your Detailed Report</h3>' +
                                '<p style="margin:0 0 20px;color:#e0e7ff;font-size:14px;">Complete gap analysis with all your answers and AI recommendations</p>' +
                                '<a href="' + retryBgData.pdf_url + '" target="_blank" class="iso42k-btn-primary" style="display:inline-block;background:#fff;color:#1e40af;padding:14px 28px;text-decoration:none;border-radius:10px;font-weight:700;">Download PDF Report</a>' +
                                '</div>';
                              pdfSection.style.display = 'block';
                            }
                          }
                        } else {
                          statusHtml = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                            '<p style="margin:0;color:#92400e;font-size:15px;">✅ Your assessment has been saved successfully.</p>' +
                            '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                            '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
                            '</div>';
                        }

                        if (es) {
                          es.innerHTML = statusHtml;
                        }
                        
                        // Clear the timeout since processing completed
                        clearTimeout(bgTimeout);
                      }).catch(function(err) {
                        console.error('Retry background processing failed:', err);
                        if (btn) {
                          btn.disabled = false;
                          btn.textContent = 'Get Results';
                        }
                        if (es) {
                          es.innerHTML = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                            '<p style="margin:0;color:#92400e;font-size:15px;">⚠️ Your assessment has been saved.</p>' +
                            '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                            '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
                            '</div>';
                        }
                      });
                    } else {
                      // Not a nonce error, handle normally
                      if (es) {
                        es.innerHTML = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                          '<p style="margin:0;color:#92400e;font-size:15px;">⚠️ Your assessment has been saved.</p>' +
                          '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                          '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
                          '</div>';
                      }
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
                    statusHtml = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                      '<p style="margin:0;color:#92400e;font-size:15px;">✅ Your assessment has been saved successfully.</p>' +
                      '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                      '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
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
                      '</div>';
                  }
                });
              }, 500); // Small delay to ensure lead is saved
            }).catch(function(err) {
              console.error('Retry submission failed:', err);
              if (btn) {
                btn.disabled = false;
                btn.textContent = 'Get Results';
              }
              setContactError('Submission failed. Please refresh the page and try again.');
            });
          } else {
            // Not a nonce error, handle normally
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Get Results';
            }
            setContactError(errorMessage);
          }
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

        // Set a timeout for background processing to avoid indefinite waiting
        var bgTimeout = setTimeout(function() {
          var es = qs('#iso42k-email-status');
          if (es) {
            es.innerHTML = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
              '<p style="margin:0;color:#92400e;font-size:15px;">⏳ Your assessment is still being processed.</p>' +
              '<p style="margin:10px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
              '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">This may take a few more minutes for complex assessments.</p>' +
              '</div>';
          }
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'Get Results';
          }
        }, 120000); // 2 minutes timeout

        setTimeout(function() {
          postForm(ISO42K.ajax_url, {
            action: 'iso42k_process_background',
            nonce: getFreshNonce(),
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
                  '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
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
              statusHtml = '<div style="padding:20px;background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;text-align:center;">' +
                '<p style="margin:0;color:#92400e;font-size:15px;">✅ Your assessment has been saved successfully.</p>' +
                '<p style="margin:12px 0 0;color:#78350f;font-weight:600;">Results will be emailed to ' + escHtml(email) + ' shortly.</p>' +
                '<p style="margin:10px 0 0;color:#78350f;font-size:13px;">If you don\'t receive it within 10 minutes, please contact support.</p>' +
                '</div>';
            }
            
            if (es) {
              es.innerHTML = statusHtml;
            }
            
            // Clear the timeout since processing completed
            clearTimeout(bgTimeout);
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
                '</div>';
            }
            
            // Clear the timeout since we're handling the error
            clearTimeout(bgTimeout);
          });
        }, 500); // Small delay to ensure lead is saved
      })
      .catch(function (err) {
        console.error('Stage 1 error:', err);
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Get Results';
        }
        var errorMessage = 'Network error. Please check your connection and try again.';
        if (err && err.message) {
          errorMessage = 'Submission error: ' + err.message;
        }
        console.error('Submission error details:', errorMessage);
        setContactError(errorMessage);
      });
  }

  function onClick(e) {
    var t = e.target;
    if (!t) return;

    if (t.id === 'iso42k-start') {
      setIntroError('');
      if (!window.ISO42K || !ISO42K.ajax_url || !ISO42K.nonce) {
        setIntroError('Configuration missing.');
        return;
      }

      var orgInput = qs('#iso42k-org') || qs('#iso42k-company');
      var staffInput = qs('#iso42k-staff');
      
      if (!orgInput || !staffInput) {
        setIntroError('Form fields not found. Please refresh.');
        return;
      }

      var org = (orgInput.value || '').trim();
      var staffRange = (staffInput.value || '').trim();
      
      if (!org || !staffRange) {
        setIntroError('Please enter company name and staff size.');
        return;
      }

      // 1. Update UI to show activity
      var startBtn = t;
      var originalText = startBtn.textContent;
      startBtn.textContent = 'Starting...';
      startBtn.disabled = true;

      // 2. Refresh Nonce FIRST (Solves the Caching Issue)
      refreshNonce().then(function() {
          // Now we have a fresh nonce in ISO42K.nonce
          
          STATE.org = org;
          STATE.staffRange = staffRange;
          STATE.staff = staffRangeToNumeric(staffRange);
          STATE.answers = [];
          STATE.index = 0;
          STATE.total = 0;
    
          console.log('🏢 Started:', STATE.org, '(' + STATE.staff + ' staff)');
    
          showStep('#iso42k-step-questions');
          setQError('');
    
          // 3. Track start using the NEW nonce
          var trackStartPayload = {
            action: 'iso42k_track_start',
            nonce: getFreshNonce()
          };
          
          // Fire track start (fire and forget)
          postForm(ISO42K.ajax_url, trackStartPayload).catch(function(err) {
             console.log('Tracking failed, continuing anyway');
          });

          // 4. Load questions using the NEW nonce
          loadQuestion(0);

      }).catch(function(err) {
          console.error('Failed to refresh nonce:', err);
          setIntroError('Connection failed. Please refresh and try again.');
          startBtn.textContent = originalText;
          startBtn.disabled = false;
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
        setQError('Please select an answer.');
        return;
      }
      // Only allow going to contact page after ALL questions have been answered
      if (STATE.index >= STATE.total - 1) {
        // Check if all questions have been answered
        var allAnswered = true;
        for (var i = 0; i < STATE.total; i++) {
          if (!STATE.answers[i]) {
            allAnswered = false;
            break;
          }
        }
        if (allAnswered) {
          goToContactStep();
        } else {
          setQError('Please answer all questions before proceeding to the contact page.');
        }
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
      window.location.reload();
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

  document.addEventListener('click', onClick);
  document.addEventListener('keydown', onKeyDown);

  console.log('ISO42K Flow v7.3.0 initialized (Two-stage processing)');
  console.log('STATE:', window.ISO42K_STATE);

})();