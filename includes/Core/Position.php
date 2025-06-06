<?php
namespace ResumeAIJob\Core;

class Position {
    /**
     * Initialize the Position class
     */
    public function init() {
        add_action('wp_ajax_get_positions', array($this, 'get_positions'));
        add_action('wp_ajax_nopriv_get_positions', array($this, 'get_positions'));
        add_action('wp_ajax_apply_position', array($this, 'apply_position'));
        add_action('wp_ajax_get_user_resumes', array($this, 'get_user_resumes'));
        add_shortcode('resume_ai_job_listings', array($this, 'render_job_listings'));
    }

    /**
     * Get available positions with filters
     * 
     * @param array $filters Optional filters for positions
     * @return array List of positions
     */
    public function get_positions($filters = array()) {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'resume_ai_job_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'resume_ai_job_positions';
        $applications_table = $wpdb->prefix . 'resume_ai_job_applications';
        
        $where = array('p.status = "active"');
        $params = array();

        if (!empty($filters['title'])) {
            $where[] = 'p.title LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['title']) . '%';
        }

        if (!empty($filters['location'])) {
            $where[] = 'p.location LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['location']) . '%';
        }

        if (!empty($filters['salary_from'])) {
            $where[] = 'p.salary_from >= %d';
            $params[] = intval($filters['salary_from']);
        }

        if (!empty($filters['salary_to'])) {
            $where[] = 'p.salary_to <= %d';
            $params[] = intval($filters['salary_to']);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get current user ID (0 if not logged in)
        $user_id = get_current_user_id();
        
        // If we have parameters, use prepare, otherwise use direct query
        if (!empty($params)) {
            $query = $wpdb->prepare(
                "SELECT p.*, 
                CASE 
                    WHEN %d > 0 THEN a.status 
                    ELSE NULL 
                END as application_status 
                FROM $table_name p 
                LEFT JOIN $applications_table a ON p.id = a.position_id AND a.user_id = %d 
                $where_clause 
                ORDER BY p.created_at DESC",
                array_merge([$user_id, $user_id], $params)
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT p.*, 
                CASE 
                    WHEN %d > 0 THEN a.status 
                    ELSE NULL 
                END as application_status 
                FROM $table_name p 
                LEFT JOIN $applications_table a ON p.id = a.position_id AND a.user_id = %d 
                $where_clause 
                ORDER BY p.created_at DESC",
                $user_id,
                $user_id
            );
        }

        $results = $wpdb->get_results($query);
        
        // If this is an AJAX request, send JSON response
        if (wp_doing_ajax()) {
            wp_send_json_success($results);
        }
        
        return $results;
    }

    /**
     * Apply for a position
     * 
     * @param int $position_id The position ID
     * @param int $user_id The user ID
     * @param string $cover_letter The cover letter
     * @param int $resume_id The resume ID
     * @return array|WP_Error Result of the application
     */
    public function apply_position() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'resume_ai_job_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }

        // Get and validate required fields
        $position_id = isset($_POST['position_id']) ? intval($_POST['position_id']) : 0;
        $cover_letter = isset($_POST['cover_letter']) ? wp_kses_post($_POST['cover_letter']) : '';
        $resume_id = isset($_POST['resume_id']) ? intval($_POST['resume_id']) : 0;

        if (!$position_id || !$cover_letter || !$resume_id) {
            wp_send_json_error('Missing required fields');
            return;
        }

        global $wpdb;
        
        // Check if position exists and is active
        $position = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}resume_ai_job_positions WHERE id = %d AND status = 'active'",
            $position_id
        ));

        if (!$position) {
            wp_send_json_error('Position not found or not active');
            return;
        }

        // Check if user has already applied
        $existing_application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}resume_ai_job_applications 
            WHERE position_id = %d AND user_id = %d",
            $position_id,
            $user_id
        ));

        if ($existing_application) {
            wp_send_json_error('You have already applied for this position');
            return;
        }

        // Insert application
        $result = $wpdb->insert(
            $wpdb->prefix . 'resume_ai_job_applications',
            array(
                'position_id' => $position_id,
                'user_id' => $user_id,
                'cover_letter' => $cover_letter,
                'resume_id' => $resume_id,
                'status' => 'pending'
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );

        if ($result === false) {
            wp_send_json_error('Failed to submit application');
            return;
        }

        wp_send_json_success(array(
            'message' => 'Application submitted successfully'
        ));
    }

    /**
     * Get user's applications
     * 
     * @param int $user_id The user ID
     * @return array List of user's applications
     */
    public function get_user_applications($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.title as position_title, p.location 
            FROM {$wpdb->prefix}resume_ai_job_applications a
            JOIN {$wpdb->prefix}resume_ai_job_positions p ON a.position_id = p.id
            WHERE a.user_id = %d
            ORDER BY a.created_at DESC",
            $user_id
        ));
    }

    /**
     * Get user's resumes for application
     */
    public function get_user_resumes() {
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        
        $resumes = $wpdb->get_results($wpdb->prepare(
            "SELECT ud.id, p.post_title as title, ud.published_resume_id
            FROM {$wpdb->prefix}resume_ai_job_user_data ud
            JOIN {$wpdb->posts} p ON ud.published_resume_id = p.ID
            WHERE ud.user_id = %d AND ud.published_resume_id IS NOT NULL",
            $user_id
        ));

        // Add preview URL for each resume
        foreach ($resumes as &$resume) {
            $pdf_url = wp_get_attachment_url($resume->published_resume_id);
            if ($pdf_url) {
                $resume->preview_url = str_replace('.pdf', '-pdf.jpg', $pdf_url);
            }
        }

        wp_send_json_success($resumes);
    }

    /**
     * Render job listings template
     */
    public function render_job_listings() {
        // Enqueue necessary scripts
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'resume_ai_job', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resume_ai_job_nonce'),
            'is_user_logged_in' => is_user_logged_in(),
            'login_url' => wp_login_url(get_permalink())
        ));

        ob_start();
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/job-listings.php';
        return ob_get_clean();
    }
}