function submitWithContact() {
  // ... validation code ...
  
  postForm(ISO42K.ajax_url, payload)
    .then(function (res) {
      // Stage 1 response handling
      // ...
    })
    .catch(function (err) {
      console.error('Stage 1 error:', err);
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Get Results';
      }
      setContactError('Network error. Please check your connection and try again.');
    });
}