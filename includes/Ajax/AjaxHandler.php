<?php
namespace ResumeAIJob\Ajax;

class AjaxHandler {
    /**
     * Initialize AJAX handlers.
     */
    public function init() {
        add_action('wp_ajax_get_user_data', array($this, 'get_user_data'));
        add_action('wp_ajax_update_user', array($this, 'update_user'));
        add_action('wp_ajax_delete_user', array($this, 'delete_user'));
        add_action('wp_ajax_add_user', array($this, 'add_user'));
        add_action('wp_ajax_download_resume', array($this, 'download_resume'));
    }

    /**
     * Get user data.
     */
    public function get_user_data() {
        check_ajax_referer('resume_ai_job_nonce', 'nonce');

        global $wpdb;
        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);
        $custom_table = $wpdb->prefix . 'resume_ai_job_user_data';
        $custom_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $custom_table WHERE user_id = %d",
            $user_id
        ));

        $data = array(
            'user_id' => $user->ID,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'role' => $user->roles[0],
            'linkedin_url' => $custom_data ? $custom_data->linkedin_url : '',
            'resume_url' => $custom_data ? $custom_data->resume_url : ''
        );

        wp_send_json($data);
    }

    /**
     * Update user data.
     */
    public function update_user() {
        check_ajax_referer('resume_ai_job_nonce', 'nonce');

        global $wpdb;
        $user_id = intval($_POST['user_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $role = sanitize_text_field($_POST['role']);
        $linkedin_url = esc_url_raw($_POST['linkedin_url']);
        $resume_url = esc_url_raw($_POST['resume_url']);

        // Update WordPress user
        $user_data = array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $email,
            'role' => $role
        );
        wp_update_user($user_data);

        // Update custom data
        $custom_table = $wpdb->prefix . 'resume_ai_job_user_data';
        $wpdb->replace(
            $custom_table,
            array(
                'user_id' => $user_id,
                'linkedin_url' => $linkedin_url,
                'resume_url' => $resume_url
            ),
            array('%d', '%s', '%s')
        );

        wp_send_json_success();
    }

    /**
     * Delete user.
     */
    public function delete_user() {
        check_ajax_referer('resume_ai_job_nonce', 'nonce');

        global $wpdb;
        $user_id = intval($_POST['user_id']);
        
        // Delete WordPress user
        wp_delete_user($user_id);
        
        // Delete custom data
        $custom_table = $wpdb->prefix . 'resume_ai_job_user_data';
        $wpdb->delete($custom_table, array('user_id' => $user_id), array('%d'));
        
        wp_send_json_success();
    }

    /**
     * Add new user.
     */
    public function add_user() {
        check_ajax_referer('resume_ai_job_nonce', 'nonce');

        global $wpdb;
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $role = sanitize_text_field($_POST['role']);
        $linkedin_url = esc_url_raw($_POST['linkedin_url']);
        $resume_url = esc_url_raw($_POST['resume_url']);

        // Create WordPress user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role
        );

        $user_id = wp_insert_user($user_data);

        if (!is_wp_error($user_id)) {
            // Add custom data
            $custom_table = $wpdb->prefix . 'resume_ai_job_user_data';
            $wpdb->insert(
                $custom_table,
                array(
                    'user_id' => $user_id,
                    'linkedin_url' => $linkedin_url,
                    'resume_url' => $resume_url
                ),
                array('%d', '%s', '%s')
            );
            wp_send_json_success();
        } else {
            wp_send_json_error($user_id->get_error_message());
        }
    }

    /**
     * Handle resume download.
     */
    public function download_resume() {
        check_ajax_referer('download_resume_nonce', 'nonce');

        $file_id = intval($_POST['file_id']);
        
        // Verify the file exists and user has permission
        $file = get_post($file_id);
        if (!$file || $file->post_type !== 'attachment') {
            wp_send_json_error(array('message' => 'Invalid file'));
            return;
        }

        // Get the file path
        $file_path = get_attached_file($file_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(array('message' => 'File not found'));
            return;
        }

        // Get the file URL
        $file_url = wp_get_attachment_url($file_id);
        if (!$file_url) {
            wp_send_json_error(array('message' => 'Could not generate download URL'));
            return;
        }

        wp_send_json_success(array('url' => $file_url));
    }
} 