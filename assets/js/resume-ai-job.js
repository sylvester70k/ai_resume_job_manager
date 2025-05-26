jQuery(document).ready(function($) {
    // Handle login form submission
    $('#resume-ai-job-login').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'resume_ai_login',
            email: $('#email').val(),
            password: $('#password').val(),
            nonce: $('#resume_ai_job_login_nonce').val()
        };

        $.ajax({
            url: resumeAiJob.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert(response.data.message || 'Login failed. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
}); 