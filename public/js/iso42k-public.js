/**
 * ISO 42001 Public Script (Autosave + Step Navigation)
 */
document.addEventListener('DOMContentLoaded', function() {
    // 检查是否已定义全局变量
    if (typeof ISO42K === 'undefined') {
        console.error('ISO42K global variable is not defined');
        return;
    }
    
    // 初始化变量
    const assessmentUid = localStorage.getItem('iso42k_assessment_uid') || generateUUID();
    localStorage.setItem('iso42k_assessment_uid', assessmentUid);
    
    // 每2分钟自动保存
    let autosaveInterval = setInterval(saveAssessmentDraft, 120000);
    
    // 初始保存（页面加载时）
    saveAssessmentDraft();

    // 步骤切换验证
    document.querySelectorAll('.iso42k-next-step').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const step = this.dataset.step;
            const form = document.getElementById('iso42k-form');
            
            // 验证表单
            if (validateForm(form)) {
                // 保存当前步骤数据
                saveAssessmentDraft();
                // 跳转下一步
                window.location.href = window.location.href.split('?')[0] + '?iso42k_step=' + step;
            }
        });
    });

    // 语言切换
    document.querySelectorAll('.iso42k-lang-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const target = this.dataset.target;
            document.querySelectorAll(`.iso42k-question-${target}`).forEach(el => {
                el.classList.toggle('hidden');
            });
        });
    });

    // 恢复草稿数据
    restoreAssessmentDraft();

    /**
     * 生成UUID
     */
    function generateUUID() {
        return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
    }

    /**
     * 保存评估草稿
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

        // 保存到localStorage
        localStorage.setItem('iso42k_draft_' + assessmentUid, JSON.stringify(draftData));

        // 发送到后台
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
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  updateAutosaveStatus('Saved successfully!');
              }
          }).catch(error => {
              updateAutosaveStatus('Auto-save failed (local only)');
          });
    }

    /**
     * 恢复评估草稿
     */
    function restoreAssessmentDraft() {
        const draftData = JSON.parse(localStorage.getItem('iso42k_draft_' + assessmentUid));
        if (!draftData) return;

        // 填充表单数据
        Object.keys(draftData.data).forEach(key => {
            const el = document.querySelector(`[name="${key}"]`);
            if (el) el.value = draftData.data[key];
        });

        updateAutosaveStatus('Draft restored');
    }

    /**
     * 验证表单
     */
    function validateForm(form) {
        if (!form) return false;
        
        let isValid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('iso42k-invalid');
                field.addEventListener('blur', function() {
                    this.classList.remove('iso42k-invalid');
                });
            }
        });
        return isValid;
    }

    /**
     * 更新自动保存状态
     */
    function updateAutosaveStatus(message) {
        const statusEl = document.getElementById('iso42k-autosave-status');
        if (statusEl) {
            statusEl.textContent = `Last save: ${new Date().toLocaleTimeString()} - ${message}`;
            statusEl.classList.add('iso42k-status-show');
            setTimeout(() => {
                statusEl.classList.remove('iso42k-status-show');
            }, 3000);
        }
    }
});