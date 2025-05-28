jQuery(document).ready(function($) {
    $('#resume-ai-job-register').on('submit', function(e) {
        e.preventDefault();
        
        // Log to verify the script is running
        console.log('Form submitted');
        console.log('resumeAiJob object:', resumeAiJob);
        if ($('#password').val() !== $('#repassword').val()) {
            alert('Passwords do not match');
            return;
        }
        
        var formData = {
            action: 'resume_ai_register',
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            linkedin_url: $('#linkedin_url').val(),
            nonce: $('#resume_ai_auth_nonce').val()
        };

        // Log the form data (excluding password)
        console.log('Form data:', {
            ...formData,
            password: '[REDACTED]'
        });

        $.ajax({
            url: resumeAiJob.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert(response.data.message || 'Registration failed. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                alert('An error occurred. Please try again.');
            }
        });
    });
}); 