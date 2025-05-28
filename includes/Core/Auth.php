<?php
namespace ResumeAIJob\Core;

class Auth {
    /**
     * Initialize authentication functionality.
     */
    public function init() {
        // Add shortcodes
        add_shortcode('resume_ai_login', array($this, 'render_login_form'));
        add_shortcode('resume_ai_register', array($this, 'render_register_form'));

        // Add AJAX handlers
        add_action('wp_ajax_nopriv_resume_ai_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_resume_ai_register', array($this, 'handle_registration'));
    }

    /**
     * Render login form.
     */
    public function render_login_form() {
        ob_start();
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/login-form.php';
        return ob_get_clean();
    }

    /**
     * Render registration form.
     */
    public function render_register_form() {
        ob_start();
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/register-form.php';
        return ob_get_clean();
    }

    /**
     * Handle login AJAX request.
     */
    public function handle_login() {
        if (!check_ajax_referer('resume_ai_job_login_nonce', 'nonce', false)) {
            error_log('Nonce verification failed');
            wp_send_json_error('Security check failed');
        }

        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            wp_send_json_error('Invalid credentials');
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        wp_send_json_success(array(
            'redirect_url' => home_url('/')
        ));
    }

    /**
     * Handle registration AJAX request.
     */
    public function handle_registration() {
        // Log the incoming request
        error_log('Registration request received: ' . print_r($_POST, true));

        // Verify nonce
        if (!check_ajax_referer('resume_ai_auth_nonce', 'nonce', false)) {
            error_log('Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $linkedin_url = esc_url_raw($_POST['linkedin_url']);

        // Log sanitized data
        error_log('Sanitized registration data: ' . print_r([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'linkedin_url' => $linkedin_url
        ], true));

        // Check if email already exists
        if (email_exists($email)) {
            error_log('Email already exists: ' . $email);
            wp_send_json_error('Email already exists');
            return;
        }

        // Create user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'resume_user'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            error_log('User creation failed: ' . $user_id->get_error_message());
            wp_send_json_error($user_id->get_error_message());
            return;
        }

        // Add custom data
        global $wpdb;
        $custom_table = $wpdb->prefix . 'resume_ai_job_user_data';
        $result = $wpdb->insert(
            $custom_table,
            array(
                'user_id' => $user_id,
                'linkedin_url' => $linkedin_url
            ),
            array('%d', '%s')
        );

        if ($result === false) {
            error_log('Failed to insert user data: ' . $wpdb->last_error);
            wp_send_json_error('Failed to save user data');
            return;
        }

        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        error_log('Registration successful for user: ' . $user_id);
        wp_send_json_success(array(
            'redirect_url' => home_url('/')
        ));
    }
} 