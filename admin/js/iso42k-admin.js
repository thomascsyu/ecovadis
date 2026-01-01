/**
 * ISO42K Admin JavaScript - Modern UI & Performance Optimized
 * Version: 7.4.1 - Fixed event handling and tab navigation
 */

(function($) {
  'use strict';

  $(document).ready(function() {
    
    console.log('ISO42K Modern Admin JS Loaded');
    console.log('AJAX URL:', ISO42K_ADMIN?.ajax_url || 'NOT DEFINED');
    console.log('Nonce:', ISO42K_ADMIN?.nonce ? 'Present' : 'Missing');
    
    // Modern tab navigation
    $('.iso42k-tab').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const tabId = $(this).data('tab');
      const container = $(this).closest('.iso42k-settings-container');
      
      if (!container.length || !tabId) {
        console.error('Tab container or ID not found');
        return;
      }
      
      // Update active tab
      container.find('.iso42k-tab').removeClass('active');
      $(this).addClass('active');
      
      // Show active content
      container.find('.iso42k-tab-content').removeClass('active');
      $('#' + tabId).addClass('active');
      
      console.log('Tab switched to:', tabId);
    });
    
    // ===== INDIVIDUAL AI CONNECTION TESTS =====

    // DeepSeek Connection Test
    $(document).on('click', '#iso42k-deepseek-test', function(e) {
      e.preventDefault();
      e.stopPropagation();
      testAIConnection('deepseek');
    });

    // Qwen Connection Test
    $(document).on('click', '#iso42k-qwen-test', function(e) {
      e.preventDefault();
      e.stopPropagation();
      testAIConnection('qwen');
    });

    // Grok Connection Test
    $(document).on('click', '#iso42k-grok-test', function(e) {
      e.preventDefault();
      e.stopPropagation();
      testAIConnection('grok');
    });

    // Unified AI Connection Test Function
    function testAIConnection(provider) {
      console.log('Testing AI connection for:', provider);
      
      const $btn = $('#iso42k-' + provider + '-test');
      const $result = $('#iso42k-' + provider + '-test-result');
      
      if (!$btn.length || !$result.length) {
        console.error('Button or result element not found for:', provider);
        return;
      }
      
      $btn.prop('disabled', true).html('<span class="iso42k-spinner"></span> Testing...');
      $result.html('<div class="iso42k-loading"><span class="iso42k-spinner"></span> Connecting to ' + provider.charAt(0).toUpperCase() + provider.slice(1) + ' API...</div>');
      
      $.ajax({
        url: ISO42K_ADMIN.ajax_url,
        type: 'POST',
        data: {
          action: 'iso42k_test_' + provider,
          nonce: ISO42K_ADMIN.nonce
        },
        timeout: 30000,
        success: function(response) {
          console.log(provider + ' Test Response:', response);
          
          if (response.success) {
            $result.html('<div class="iso42k-test-result success"><p><strong>✓ Connection Successful!</strong><br>' + response.data.message + '</p></div>');
          } else {
            $result.html('<div class="iso42k-test-result error"><p><strong>✗ Connection Failed</strong><br>' + (response.data?.message || 'Unknown error occurred') + '</p></div>');
          }
        },
        error: function(xhr, status, error) {
          console.error(provider + ' Test Error:', status, error);
          
          let errorMsg = 'Network error occurred';
          if (status === 'timeout') {
            errorMsg = 'Request timed out (30s). The API may be slow or unreachable.';
          } else if (xhr.status === 0) {
            errorMsg = 'No response from server. Check your internet connection.';
          } else if (xhr.status) {
            errorMsg = 'HTTP ' + xhr.status + ': ' + (xhr.statusText || 'Unknown error');
          }
          
          $result.html('<div class="iso42k-test-result error"><p><strong>✗ Request Failed</strong><br>' + errorMsg + '</p></div>');
        },
        complete: function() {
          $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Test ' + provider.charAt(0).toUpperCase() + provider.slice(1) + ' Connection');
        }
      });
    }
    
    // ===== EMAIL CONFIGURATION VALIDATION =====
    $(document).on('click', '#iso42k-validate-email-config', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $btn = $(this);
      const $result = $('#iso42k-validate-result');
      
      $btn.prop('disabled', true).text('Validating...');
      $result.html('<div class="iso42k-loading"><span class="iso42k-spinner"></span> Checking configuration...</div>').show();
      
      $.ajax({
        url: ISO42K_ADMIN.ajax_url,
        type: 'POST',
        data: {
          action: 'iso42k_validate_email_config',
          nonce: ISO42K_ADMIN.nonce
        },
        timeout: 10000,
        success: function(response) {
          console.log('Email Validation Response:', response);
          
          if (response.success) {
            $result.html('<div class="iso42k-test-result success"><p>✓ ' + response.data.message + '</p></div>');
          } else {
            let errorHtml = '<div class="iso42k-test-result error"><p><strong>✗ Configuration Errors:</strong></p><ul>';
            if (response.data?.errors && Array.isArray(response.data.errors)) {
              response.data.errors.forEach(err => {
                errorHtml += '<li>' + err + '</li>';
              });
            } else {
              errorHtml += '<li>' + (response.data?.message || 'Unknown error') + '</li>';
            }
            errorHtml += '</ul></div>';
            $result.html(errorHtml);
          }
        },
        error: function(xhr, status, error) {
          console.error('Validation Error:', status, error);
          $result.html('<div class="iso42k-test-result error"><p>✗ Request failed: ' + error + '</p></div>');
        },
        complete: function() {
          $btn.prop('disabled', false).text('Check Email Config');
        }
      });
    });
    
    // ===== TEST USER EMAIL =====
    $(document).on('click', '#iso42k-test-user-email', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $btn = $(this);
      const $result = $('#iso42k-test-user-email-result');
      const testEmail = $('#iso42k-test-user-email-address').val().trim();
      
      if (!testEmail) {
        $result.html('<div class="iso42k-test-result error"><p>❌ Please enter an email address</p></div>').show();
        return;
      }
      
      // Basic email validation
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(testEmail)) {
        $result.html('<div class="iso42k-test-result error"><p>❌ Please enter a valid email address</p></div>').show();
        return;
      }
      
      $btn.prop('disabled', true).text('Sending...');
      $result.html('<div class="iso42k-loading"><span class="iso42k-spinner"></span> Sending test email...</div>').show();
      
      $.ajax({
        url: ISO42K_ADMIN.ajax_url,
        type: 'POST',
        data: {
          action: 'iso42k_test_user_email',
          nonce: ISO42K_ADMIN.nonce,
          test_email: testEmail
        },
        timeout: 20000,
        success: function(response) {
          console.log('User Email Test Response:', response);
          
          if (response.success) {
            $result.html('<div class="iso42k-test-result success"><p>✓ ' + response.data.message + '</p></div>');
          } else {
            $result.html('<div class="iso42k-test-result error"><p>✗ ' + (response.data?.message || 'Failed to send') + '</p></div>');
          }
        },
        error: function(xhr, status, error) {
          console.error('User Email Test Error:', status, error);
          $result.html('<div class="iso42k-test-result error"><p>✗ Network error: ' + error + '</p></div>');
        },
        complete: function() {
          $btn.prop('disabled', false).text('Send Test User Email');
        }
      });
    });
    
    // ===== TEST ADMIN EMAIL =====
    $(document).on('click', '#iso42k-test-admin-email', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $btn = $(this);
      const $result = $('#iso42k-test-email-result');
      
      $btn.prop('disabled', true).text('Sending...');
      $result.html('<div class="iso42k-loading"><span class="iso42k-spinner"></span> Sending test notification...</div>').show();
      
      $.ajax({
        url: ISO42K_ADMIN.ajax_url,
        type: 'POST',
        data: {
          action: 'iso42k_test_admin_email',
          nonce: ISO42K_ADMIN.nonce
        },
        timeout: 15000,
        success: function(response) {
          console.log('Admin Email Test Response:', response);
          
          if (response.success) {
            let message = '<div class="iso42k-test-result success"><p>✓ ' + response.data.message + '</p>';
            
            // Add information about invalid emails if present
            if (response.data.invalid_emails && response.data.invalid_emails.length > 0) {
              message += '<p>⚠️ Invalid emails (skipped): ' + response.data.invalid_emails.join(', ') + '</p>';
            }
            
            message += '</div>';
            $result.html(message);
          } else {
            $result.html('<div class="iso42k-test-result error"><p>✗ ' + (response.data?.message || 'Failed to send') + '</p></div>');
          }
        },
        error: function(xhr, status, error) {
          console.error('Admin Email Test Error:', status, error);
          $result.html('<div class="iso42k-test-result error"><p>✗ Network error: ' + error + '</p></div>');
        },
        complete: function() {
          $btn.prop('disabled', false).text('Send Test Email');
        }
      });
    });
    
    // ===== ZAPIER TEST =====
    $(document).on('click', '#iso42k-zapier-test', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $btn = $(this);
      const $spinner = $('.iso42k-zapier-spinner');
      const $result = $('#iso42k-zapier-test-result');
      const webhookUrl = $('#iso42k-zapier-webhook').val().trim();

      if (!webhookUrl) {
        $result.html('<div class="iso42k-test-result error"><p>⚠️ Please enter a Zapier webhook URL first</p></div>').show();
        return;
      }

      $btn.prop('disabled', true);
      $spinner.show();
      $result.hide();

      $.ajax({
        url: ISO42K_ADMIN.ajax_url,
        type: 'POST',
        data: {
          action: 'iso42k_test_zapier',
          nonce: ISO42K_ADMIN.nonce,
          webhook_url: webhookUrl
        },
        timeout: 20000,
        success: function(response) {
          console.log('Zapier Test Response:', response);
          
          if (response.success) {
            $result.html('<div class="iso42k-test-result success"><p><strong>✓ Success:</strong> ' + response.data.message + '</p></div>');
          } else {
            $result.html('<div class="iso42k-test-result error"><p><strong>✗ Error:</strong> ' + (response.data?.message || 'Test failed') + '</p></div>');
          }
          $result.show();
        },
        error: function(xhr, status, error) {
          console.error('Zapier Test Error:', status, error);
          
          let errorMsg = 'Request failed';
          if (status === 'timeout') {
            errorMsg = 'Request timed out (webhook may be unreachable)';
          }
          
          $result.html('<div class="iso42k-test-result error"><p><strong>✗ Error:</strong> ' + errorMsg + ' - ' + error + '</p></div>').show();
        },
        complete: function() {
          $btn.prop('disabled', false);
          $spinner.hide();
        }
      });
    });
    
    // ===== TEST LOG WRITE =====
    $(document).on('click', '#iso42k-write-test-log', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $btn = $(this);
      const $result = $('#iso42k-write-test-log-result');
      
      $btn.prop('disabled', true);
      $result.html('<div class="iso42k-loading"><span class="iso42k-spinner"></span></div>');
      
      $.ajax({
        url: ISO42K_ADMIN.ajax_url,
        type: 'POST',
        data: {
          action: 'iso42k_write_test_log',
          nonce: ISO42K_ADMIN.nonce
        },
        success: function(response) {
          if (response.success) {
            $result.html('<div class="iso42k-test-result success"><p>✓ ' + (response.data?.message || 'Success') + '</p></div>');
          } else {
            $result.html('<div class="iso42k-test-result error"><p>✗ Failed</p></div>');
          }
        },
        error: function() {
          $result.html('<div class="iso42k-test-result error"><p>✗ Network error</p></div>');
        },
        complete: function() {
          $btn.prop('disabled', false);
        }
      });
    });
    
    // ===== COPY SHORTCODE =====
    $(document).on('click', '.iso42k-copy-shortcode', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const shortcode = $(this).data('shortcode');
      const $btn = $(this);
      
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(shortcode).then(function() {
          $btn.text('Copied!').addClass('iso42k-btn-success').removeClass('iso42k-btn-outline');
          setTimeout(function() {
            $btn.text('Copy').removeClass('iso42k-btn-success').addClass('iso42k-btn-outline');
          }, 2000);
        }).catch(function() {
          fallbackCopy(shortcode, $btn);
        });
      } else {
        fallbackCopy(shortcode, $btn);
      }
      
      function fallbackCopy(text, $btn) {
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        const success = document.execCommand('copy');
        $temp.remove();
        
        if (success) {
          $btn.text('Copied!').addClass('iso42k-btn-success').removeClass('iso42k-btn-outline');
          setTimeout(function() {
            $btn.text('Copy').removeClass('iso42k-btn-success').addClass('iso42k-btn-outline');
          }, 2000);
        } else {
          alert('Failed to copy. Please copy manually: ' + text);
        }
      }
    });
    
    // Performance optimizations
    // Debounced search for leads table
    let searchTimeout;
    $('#iso42k-leads-search').on('input', function() {
      clearTimeout(searchTimeout);
      const searchTerm = $(this).val().toLowerCase();
      
      searchTimeout = setTimeout(function() {
        $('.iso42k-leads-table tbody tr').each(function() {
          const $row = $(this);
          const text = $row.text().toLowerCase();
          if (text.includes(searchTerm)) {
            $row.show();
          } else {
            $row.hide();
          }
        });
      }, 300);
    });
    
    // Batch operations for leads
    $('#iso42k-select-all-leads').on('change', function() {
      const isChecked = $(this).is(':checked');
      $('.iso42k-lead-checkbox').prop('checked', isChecked);
    });
    
    // Performance: Use event delegation for dynamically added elements
    $(document).on('click', '.iso42k-expand-details', function() {
      const $btn = $(this);
      const $content = $btn.closest('tr').find('.iso42k-lead-details');
      
      if ($content.is(':visible')) {
        $content.slideUp(200);
        $btn.text('Show Details');
      } else {
        $content.slideDown(200);
        $btn.text('Hide Details');
      }
    });
    
    // Smooth scrolling for anchor links
    $(document).on('click', 'a[href^="#"]', function(e) {
      const target = $($(this).attr('href'));
      if (target.length) {
        e.preventDefault();
        $('html, body').animate({
          scrollTop: target.offset().top - 100
        }, 500);
      }
    });
    
    // Lazy loading for images in AI insights
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove('lazy');
          imageObserver.unobserve(img);
        }
      });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // Initialize active tab based on URL parameter or first tab
    function initializeActiveTab() {
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      const container = $('.iso42k-settings-container');
      
      if (container.length) {
        let activeTab = 'ai'; // Default
        
        if (tabParam && ['ai', 'email', 'zapier', 'display'].includes(tabParam)) {
          activeTab = tabParam;
        }
        
        // Activate the corresponding tab
        const tabElement = container.find(`.iso42k-tab[data-tab="iso42k-${activeTab}-settings"]`);
        if (tabElement.length) {
          tabElement.click();
        } else {
          // Fallback to first tab
          container.find('.iso42k-tab').first().click();
        }
      }
    }
    
    // Initialize tabs
    initializeActiveTab();
    
  });

})(jQuery);