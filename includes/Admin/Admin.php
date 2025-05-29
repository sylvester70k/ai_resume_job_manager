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

        // Position management AJAX handlers
        add_action('wp_ajax_save_position', array($this, 'handle_save_position'));
        add_action('wp_ajax_delete_position', array($this, 'handle_delete_position'));
        add_action('wp_ajax_get_position_data', array($this, 'handle_get_position_data'));
        add_action('wp_ajax_get_position_applications', array($this, 'handle_get_position_applications'));
        
        // Application management AJAX handlers
        add_action('wp_ajax_get_application_data', array($this, 'handle_get_application_data'));
        add_action('wp_ajax_update_application_status', array($this, 'handle_update_application_status'));
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

        add_submenu_page(
            'resume-ai-job',
            'Positions',
            'Positions',
            'manage_options',
            'resume-ai-job-positions',
            array($this, 'render_positions_page')
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

        register_setting('resume_ai_job_settings', 'resume_ai_job_versions_page', array(
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

        add_settings_field(
            'resume_ai_job_versions_page',
            'Resume Versions Page',
            array($this, 'render_versions_page_field'),
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
     * Render versions page field.
     */
    public function render_versions_page_field() {
        $versions_page = get_option('resume_ai_job_versions_page');
        wp_dropdown_pages(array(
            'name' => 'resume_ai_job_versions_page',
            'selected' => $versions_page,
            'show_option_none' => 'Select a page',
            'option_none_value' => '0',
            'class' => 'regular-text'
        ));
        echo '<p class="description">Select the page where the resume versions will be displayed. This page should contain the [resume_versions] shortcode.</p>';
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

    /**
     * Render the positions page.
     */
    public function render_positions_page() {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/admin-positions-page.php';
    }

    /**
     * Handle save position AJAX request.
     */
    public function handle_save_position() {
        check_ajax_referer('resume_ai_job_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        // Validate required fields
        $required_fields = array('title', 'description', 'location', 'status');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => ucfirst($field) . ' is required'));
            }
        }

        global $wpdb;
        $positions_table = $wpdb->prefix . 'resume_ai_job_positions';

        $position_id = isset($_POST['position_id']) ? intval($_POST['position_id']) : 0;
        
        // Prepare data with proper validation
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'location' => sanitize_text_field($_POST['location']),
            'salary_from' => isset($_POST['salary_from']) ? intval($_POST['salary_from']) : 0,
            'salary_to' => isset($_POST['salary_to']) ? intval($_POST['salary_to']) : 0,
            'salary_currency' => isset($_POST['salary_currency']) ? sanitize_text_field($_POST['salary_currency']) : 'USD',
            'deadline' => isset($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : null,
            'status' => sanitize_text_field($_POST['status'])
        );

        error_log(print_r($data, true));

        // Validate salary range
        if ($data['salary_from'] > 0 && $data['salary_to'] > 0 && $data['salary_from'] > $data['salary_to']) {
            wp_send_json_error(array('message' => 'Salary "from" amount cannot be greater than "to" amount'));
        }

        // Validate deadline
        if (!empty($data['deadline'])) {
            $deadline = strtotime($data['deadline']);
            if ($deadline === false) {
                wp_send_json_error(array('message' => 'Invalid deadline date format'));
            }
            if ($deadline < time()) {
                wp_send_json_error(array('message' => 'Deadline cannot be in the past'));
            }
        }

        if ($position_id) {
            // Update existing position
            $result = $wpdb->update(
                $positions_table,
                $data,
                array('id' => $position_id)
            );
        } else {
            // Insert new position
            $result = $wpdb->insert($positions_table, $data);
        }

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to save position: ' . $wpdb->last_error));
        }

        wp_send_json_success(array('message' => 'Position saved successfully'));
    }

    /**
     * Handle delete position AJAX request.
     */
    public function handle_delete_position() {
        check_ajax_referer('resume_ai_job_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $positions_table = $wpdb->prefix . 'resume_ai_job_positions';
        $applications_table = $wpdb->prefix . 'resume_ai_job_applications';
        
        $position_id = intval($_POST['position_id']);

        // Delete associated applications first
        $wpdb->delete($applications_table, array('position_id' => $position_id));

        // Delete position
        $result = $wpdb->delete($positions_table, array('id' => $position_id));

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to delete position'));
        }

        wp_send_json_success(array('message' => 'Position deleted successfully'));
    }

    /**
     * Handle get position data AJAX request.
     */
    public function handle_get_position_data() {
        check_ajax_referer('resume_ai_job_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $positions_table = $wpdb->prefix . 'resume_ai_job_positions';
        
        $position_id = intval($_POST['position_id']);
        $position = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $positions_table WHERE id = %d",
            $position_id
        ));

        if (!$position) {
            wp_send_json_error(array('message' => 'Position not found'));
        }

        wp_send_json_success($position);
    }

    /**
     * Handle get application data AJAX request.
     */
    public function handle_get_application_data() {
        check_ajax_referer('resume_ai_job_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $applications_table = $wpdb->prefix . 'resume_ai_job_applications';
        $positions_table = $wpdb->prefix . 'resume_ai_job_positions';
        
        $application_id = intval($_POST['application_id']);
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, p.title as position_title, u.display_name as applicant_name 
            FROM $applications_table a 
            JOIN $positions_table p ON a.position_id = p.id 
            JOIN {$wpdb->users} u ON a.user_id = u.ID 
            WHERE a.id = %d",
            $application_id
        ));

        if (!$application) {
            wp_send_json_error(array('message' => 'Application not found'));
        }

        // Get resume URL
        $resume_url = wp_get_attachment_url($application->resume_id);
        $application->resume_url = $resume_url;

        wp_send_json_success($application);
    }

    /**
     * Handle update application status AJAX request.
     */
    public function handle_update_application_status() {
        check_ajax_referer('resume_ai_job_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $applications_table = $wpdb->prefix . 'resume_ai_job_applications';
        
        $application_id = intval($_POST['application_id']);
        $status = sanitize_text_field($_POST['status']);

        $result = $wpdb->update(
            $applications_table,
            array('status' => $status),
            array('id' => $application_id)
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to update application status'));
        }

        wp_send_json_success(array('message' => 'Application status updated successfully'));
    }

    /**
     * Handle get position applications AJAX request.
     */
    public function handle_get_position_applications() {
        check_ajax_referer('resume_ai_job_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $applications_table = $wpdb->prefix . 'resume_ai_job_applications';
        
        $position_id = intval($_POST['position_id']);
        $applications = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as applicant_name 
            FROM $applications_table a 
            JOIN {$wpdb->users} u ON a.user_id = u.ID 
            WHERE a.position_id = %d 
            ORDER BY a.created_at DESC",
            $position_id
        ));

        wp_send_json_success($applications);
    }
} 