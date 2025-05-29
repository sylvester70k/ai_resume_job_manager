<?php
namespace ResumeAIJob\Core;

class Position {
    /**
     * Initialize the Position class
     */
    public function init() {
        add_action('wp_ajax_get_positions', array($this, 'get_positions'));
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
        global $wpdb;
        $table_name = $wpdb->prefix . 'resume_ai_job_positions';
        
        $where = array('status = "active"');
        $params = array();

        if (!empty($filters['title'])) {
            $where[] = 'title LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['title']) . '%';
        }

        if (!empty($filters['location'])) {
            $where[] = 'location LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['location']) . '%';
        }

        if (!empty($filters['salary_from'])) {
            $where[] = 'salary_from >= %d';
            $params[] = intval($filters['salary_from']);
        }

        if (!empty($filters['salary_to'])) {
            $where[] = 'salary_to <= %d';
            $params[] = intval($filters['salary_to']);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC",
            $params
        );

        return $wpdb->get_results($query);
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
    public function apply_position($position_id, $user_id, $cover_letter, $resume_id) {
        global $wpdb;
        
        // Check if position exists and is active
        $position = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}resume_ai_job_positions WHERE id = %d AND status = 'active'",
            $position_id
        ));

        if (!$position) {
            return new \WP_Error('invalid_position', 'Position not found or not active');
        }

        // Check if user has already applied
        $existing_application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}resume_ai_job_applications 
            WHERE position_id = %d AND user_id = %d",
            $position_id,
            $user_id
        ));

        if ($existing_application) {
            return new \WP_Error('already_applied', 'You have already applied for this position');
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
            return new \WP_Error('db_error', 'Failed to submit application');
        }

        return array(
            'success' => true,
            'message' => 'Application submitted successfully'
        );
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
            "SELECT id, title FROM {$wpdb->prefix}resume_ai_job_user_data 
            WHERE user_id = %d AND published_resume_id IS NOT NULL",
            $user_id
        ));

        wp_send_json_success($resumes);
    }

    /**
     * Render job listings template
     */
    public function render_job_listings() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to view job listings.</p>';
        }

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'Views/job-listings.php';
        return ob_get_clean();
    }
}