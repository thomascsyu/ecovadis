/**
 * ISO 42001 Autosave Handler
 * Manages assessment draft saving/loading for all steps
 */
document.addEventListener('DOMContentLoaded', function() {
    // 检查是否已定义全局变量
    if (typeof ISO42K === 'undefined') {
        console.error('ISO42K global variable is not defined');
        return;
    }

    // Initialize assessment UID
    const assessmentUid = localStorage.getItem('iso42k_assessment_uid') || generateUUID();
    localStorage.setItem('iso42k_assessment_uid', assessmentUid);

    // Auto-save every 60 seconds (reduced from 2min for better data safety)
    let autosaveInterval = setInterval(saveAssessmentDraft, 60000);

    // Initial save on page load
    saveAssessmentDraft();

    // Save when user navigates away
    window.addEventListener('beforeunload', function() {
        saveAssessmentDraft();
    });

    /**
     * Generate UUID v4
     * @returns {string} Valid UUID
     */
    function generateUUID() {
        return crypto.randomUUID ? crypto.randomUUID() : fallbackUUID();
    }

    /**
     * Fallback UUID generator for older browsers
     */
    function fallbackUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Save assessment data to localStorage and server
     */
    function saveAssessmentDraft() {
        const form = document.getElementById('iso42k-form');
        if (!form) return;

        const formData = new FormData(form);
        const draftData = {
            uid: assessmentUid,
            step: document.getElementById('iso42k-step')?.value || 'intro',
            data: Object.fromEntries(formData.entries()),
            timestamp: new Date().toISOString()
        };

        // Save to localStorage first (immediate)
        localStorage.setItem(`iso42k_draft_${assessmentUid}`, JSON.stringify(draftData));

        // Sync to server (background)
        fetch(ISO42K.ajax_url, {  // 使用 ISO42K 而不是 iso42k_ajax
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'iso42k_autosave_draft',
                'nonce': ISO42K.nonce,
                'assessment_uid': assessmentUid,
                'draft_data': JSON.stringify(draftData)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAutosaveStatus('Saved successfully');
            } else {
                updateAutosaveStatus('Saved locally only', 'warning');
            }
        })
        .catch(() => {
            updateAutosaveStatus('Saved locally only', 'warning');
        });
    }

    /**
     * Restore saved draft data
     */
    function restoreAssessmentDraft() {
        const form = document.getElementById('iso42k-form');
        if (!form) return;
        
        const draftData = JSON.parse(localStorage.getItem(`iso42k_draft_${assessmentUid}`));
        if (!draftData?.data) return;

        // Populate form fields
        Object.entries(draftData.data).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = (field.value === value);
                } else {
                    field.value = value;
                }
            }
        });

        updateAutosaveStatus('Draft restored');
    }

    /**
     * Update visual status of autosave
     * @param {string} message 
     * @param {string} type - 'success' (default) or 'warning'
     */
    function updateAutosaveStatus(message, type = 'success') {
        const statusEl = document.getElementById('iso42k-autosave-status');
        if (!statusEl) return;

        statusEl.textContent = `Last saved: ${new Date().toLocaleTimeString()} - ${message}`;
        statusEl.className = `iso42k-autosave-status iso42k-status-${type}`;
        statusEl.classList.add('iso42k-status-show');

        setTimeout(() => {
            statusEl.classList.remove('iso42k-status-show');
        }, 4000);
    }

    // Expose functions for step-specific calls
    window.iso42kSaveDraft = saveAssessmentDraft;
    window.iso42kRestoreDraft = restoreAssessmentDraft;

    // Restore draft on load
    restoreAssessmentDraft();
});