jQuery(document).ready(function($) {
    // Test DeepSeek Connection
    $('#test-deepseek').on('click', function() {
        const $button = $(this);
        const $results = $('#connection-test-results');
        // Get API key from corresponding input field
        const apiKey = $('#deepseek-api-key').val().trim();

        // Validate API key input
        if (!apiKey) {
            $results.addClass('error').text(__('Please enter a DeepSeek API key first.', 'iso42001-gap-analysis'));
            return;
        }

        // Update UI state
        $button.prop('disabled', true);
        $results.removeClass('success error').text(iso42kAdmin.loading_text);
        
        // AJAX request with API key
        $.ajax({
            url: iso42kAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'iso42k_test_deepseek_connection', // Matches PHP action hook
                nonce: iso42kAdmin.nonce,
                api_key: apiKey // Include API key in request
            },
            success: function(response) {
                if (response.success) {
                    $results.addClass('success').text(response.data); // Use response.data directly
                } else {
                    $results.addClass('error').text(
                        iso42kAdmin.error_text + ' ' + (response.data || __('Unknown error', 'iso42001-gap-analysis'))
                    );
                }
            },
            error: function() {
                $results.addClass('error').text(
                    iso42kAdmin.error_text + ' ' + __('Network error', 'iso42001-gap-analysis')
                );
            },
            complete: function() {
                $button.prop('disabled', false); // Re-enable button after request completes
            }
        });
    });

    // Test OpenRouter Connection
    $('#test-openrouter').on('click', function() {
        const $button = $(this);
        const $results = $('#connection-test-results');
        // Get API key from corresponding input field
        const apiKey = $('#openrouter-api-key').val().trim();

        // Validate API key input
        if (!apiKey) {
            $results.addClass('error').text(__('Please enter an OpenRouter API key first.', 'iso42001-gap-analysis'));
            return;
        }

        // Update UI state
        $button.prop('disabled', true);
        $results.removeClass('success error').text(iso42kAdmin.loading_text);
        
        // AJAX request with API key
        $.ajax({
            url: iso42kAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'iso42k_test_openrouter_connection', // Matches PHP action hook
                nonce: iso42kAdmin.nonce,
                api_key: apiKey // Include API key in request
            },
            success: function(response) {
                if (response.success) {
                    $results.addClass('success').text(response.data); // Use response.data directly
                } else {
                    $results.addClass('error').text(
                        iso42kAdmin.error_text + ' ' + (response.data || __('Unknown error', 'iso42001-gap-analysis'))
                    );
                }
            },
            error: function() {
                $results.addClass('error').text(
                    iso42kAdmin.error_text + ' ' + __('Network error', 'iso42001-gap-analysis')
                );
            },
            complete: function() {
                $button.prop('disabled', false); // Re-enable button after request completes
            }
        });
    });
});