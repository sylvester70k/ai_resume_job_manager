<?php
namespace ResumeAIJob\Admin;

class Admin {
    /**
     * Initialize admin functionality.
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // User management AJAX handlers
        add_action('wp_ajax_add_user', array($this, 'handle_add_user'));
        add_action('wp_ajax_delete_user', array($this, 'handle_delete_user'));
        add_action('wp_ajax_edit_user', array($this, 'handle_edit_user'));
        add_action('wp_ajax_get_user_data', array($this, 'handle_get_user_data'));
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        add_menu_page(
            'Resume AI Job',
            'Resume AI Job',
            'manage_options',
            'resume-ai-job',
            array($this, 'render_admin_page'),
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'resume-ai-job',
            'Settings',
            'Settings',
            'manage_options',
            'resume-ai-job-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        // Add Tailwind CSS
        wp_enqueue_style(
            'tailwindcss',
            'https://cdn.tailwindcss.com',
            array(),
            '3.0.0'
        );

        // Add custom admin script
        wp_enqueue_script(
            'resume-ai-job-admin',
            RESUME_AI_JOB_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            RESUME_AI_JOB_VERSION,
            true
        );

        wp_localize_script('resume-ai-job-admin', 'resume_ai_job_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resume_ai_job_admin_nonce')
        ));
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('resume_ai_job_settings', 'resume_ai_job_api_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_api_key'),
            'default' => ''
        ));

        register_setting('resume_ai_job_settings', 'resume_ai_job_upload_page', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));

        add_settings_section(
            'resume_ai_job_api_settings',
            'AI API Settings',
            array($this, 'render_api_settings_section'),
            'resume-ai-job-settings'
        );

        add_settings_field(
            'resume_ai_job_api_key',
            'API Key',
            array($this, 'render_api_key_field'),
            'resume-ai-job-settings',
            'resume_ai_job_api_settings'
        );

        add_settings_section(
            'resume_ai_job_page_settings',
            'Page Settings',
            array($this, 'render_page_settings_section'),
            'resume-ai-job-settings'
        );

        add_settings_field(
            'resume_ai_job_upload_page',
            'Resume Upload Page',
            array($this, 'render_upload_page_field'),
            'resume-ai-job-settings',
            'resume_ai_job_page_settings'
        );
    }

    /**
     * Handle add user AJAX request.
     */
    public function handle_add_user() {
        check_ajax_referer('add_user_nonce', 'add_user_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $linkedin_url = esc_url_raw($_POST['linkedin_url']);

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Required fields are missing'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already exists'));
        }

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
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Save LinkedIn URL
        if (!empty($linkedin_url)) {
            update_user_meta($user_id, 'linkedin_url', $linkedin_url);
        }

        wp_send_json_success(array('message' => 'User added successfully'));
    }

    /**
     * Handle delete user AJAX request.
     */
    public function handle_delete_user() {
        check_ajax_referer('delete_user_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $user_id = intval($_POST['user_id']);

        // Delete user's resumes
        $resumes = get_posts(array(
            'post_type' => 'resume_post',
            'author' => $user_id,
            'posts_per_page' => -1
        ));

        foreach ($resumes as $resume) {
            $resume_file_id = get_post_meta($resume->ID, '_resume_file_id', true);
            if ($resume_file_id) {
                wp_delete_attachment($resume_file_id, true);
            }
            wp_delete_post($resume->ID, true);
        }

        // Delete user
        if (wp_delete_user($user_id)) {
            wp_send_json_success(array('message' => 'User deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete user'));
        }
    }

    /**
     * Handle edit user AJAX request.
     */
    public function handle_edit_user() {
        check_ajax_referer('edit_user_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $user_id = intval($_POST['user_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $linkedin_url = esc_url_raw($_POST['linkedin_url']);

        if (empty($first_name) || empty($last_name) || empty($email)) {
            wp_send_json_error(array('message' => 'Required fields are missing'));
        }

        // Check if email exists for another user
        $existing_user = get_user_by('email', $email);
        if ($existing_user && $existing_user->ID !== $user_id) {
            wp_send_json_error(array('message' => 'Email already exists'));
        }

        $user_data = array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $email
        );

        // Update password if provided
        if (!empty($_POST['password'])) {
            $user_data['user_pass'] = $_POST['password'];
        }

        $result = wp_update_user($user_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Update LinkedIn URL
        update_user_meta($user_id, 'linkedin_url', $linkedin_url);

        wp_send_json_success(array('message' => 'User updated successfully'));
    }

    /**
     * Handle get user data AJAX request.
     */
    public function handle_get_user_data() {
        check_ajax_referer('get_user_data_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);

        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }

        wp_send_json_success(array(
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'linkedin_url' => get_user_meta($user_id, 'linkedin_url', true)
        ));
    }

    /**
     * Sanitize API key.
     */
    public function sanitize_api_key($key) {
        return sanitize_text_field($key);
    }

    /**
     * Render API settings section.
     */
    public function render_api_settings_section() {
        echo '<p class="text-gray-600">Configure your AI API settings for resume analysis.</p>';
    }

    /**
     * Render API key field.
     */
    public function render_api_key_field() {
        $api_key = get_option('resume_ai_job_api_key');
        ?>
        <div class="max-w-xl">
            <input type="password"
                   name="resume_ai_job_api_key"
                   value="<?php echo esc_attr($api_key); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Enter your AI API key">
            <p class="mt-2 text-sm text-gray-500">
                Get your API key from the AI service provider (e.g., OpenAI).
            </p>
        </div>
        <?php
    }

    /**
     * Render page settings section.
     */
    public function render_page_settings_section() {
        echo '<p class="text-gray-600">Configure the pages used by the plugin.</p>';
    }

    /**
     * Render upload page field.
     */
    public function render_upload_page_field() {
        $upload_page = get_option('resume_ai_job_upload_page');
        wp_dropdown_pages(array(
            'name' => 'resume_ai_job_upload_page',
            'selected' => $upload_page,
            'show_option_none' => 'Select a page',
            'option_none_value' => '0',
            'class' => 'regular-text'
        ));
        echo '<p class="description">Select the page where you have placed the [resume_upload_form] shortcode.</p>';
    }

    /**
     * Render the admin page.
     */
    public function render_admin_page() {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/admin-page.php';
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1 class="text-2xl font-bold mb-6">Resume AI Job Settings</h1>
            
            <form method="post" action="options.php" class="max-w-2xl">
                <?php
                settings_fields('resume_ai_job_settings');
                do_settings_sections('resume-ai-job-settings');
                submit_button('Save Settings', 'primary', 'submit', true, array('class' => 'mt-4'));
                ?>
            </form>

            <div class="mt-8 p-4 bg-white rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">API Status</h2>
                <?php
                $api_key = get_option('resume_ai_job_api_key');
                if (empty($api_key)) {
                    echo '<div class="p-4 bg-yellow-100 text-yellow-700 rounded">API key not configured</div>';
                } else {
                    echo '<div class="p-4 bg-green-100 text-green-700 rounded">API key configured</div>';
                }
                ?>
            </div>
        </div>
        <?php
    }
} 