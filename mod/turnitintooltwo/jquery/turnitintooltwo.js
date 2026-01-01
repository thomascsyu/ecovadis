jQuery(document).ready(function($) {
    // Handler for PDF/HTML download
    $(document).on('click', '.download-report-btn', function(e) {
        e.preventDefault();
        
        var downloadUrl = $(this).data('download-url');
        var fileName = $(this).data('filename');
        
        if (!downloadUrl) {
            alert('Download URL not found');
            return false;
        }
        
        // Create a temporary link and trigger download
        var link = document.createElement('a');
        link.href = downloadUrl;
        link.download = fileName || 'report';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Alternative method using AJAX if direct linking doesn't work
    $(document).on('click', '.ajax-download-btn', function(e) {
        e.preventDefault();
        
        var downloadUrl = $(this).data('download-url');
        var fileName = $(this).data('filename');
        
        if (!downloadUrl) {
            alert('Download URL not found');
            return false;
        }
        
        // Show loading indicator
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<span class="loading">Downloading...</span>');
        
        $.ajax({
            url: downloadUrl,
            method: 'GET',
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, textStatus, xhr) {
                // Try to get filename from headers if not provided
                var disposition = xhr.getResponseHeader('Content-Disposition');
                if (!fileName && disposition && disposition.indexOf('filename=') !== -1) {
                    fileName = disposition.substring(disposition.indexOf('filename=') + 9).replace(/"/g, '');
                }
                
                // Create blob and download
                var blob = new Blob([data], { type: xhr.getResponseHeader('Content-Type') });
                var url = window.URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = fileName || 'report';
                link.style.display = 'none';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            },
            error: function(xhr, status, error) {
                console.error('Download failed:', error);
                alert('Download failed. Please try again.');
            },
            complete: function() {
                // Restore original button text
                $btn.html(originalText);
            }
        });
    });
});