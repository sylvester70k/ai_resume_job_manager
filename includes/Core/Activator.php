<?php
namespace ResumeAIJob\Core;

class Activator {
    /**
     * Activate the plugin.
     */
    public function activate() {
        $this->create_roles();
        $this->create_tables();
        $this->create_capabilities();
    }

    /**
     * Create custom roles.
     */
    private function create_roles() {
        add_role(
            'resume_user',
            'Resume User',
            array(
                'read' => false,
                'upload_files' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'edit_pages' => false,
                'edit_others_posts' => false,
                'create_posts' => false,
                'manage_categories' => false,
                'moderate_comments' => false,
                'edit_others_pages' => false,
                'edit_published_pages' => false,
                'publish_pages' => false,
                'delete_pages' => false,
                'delete_others_pages' => false,
                'delete_published_pages' => false,
                'delete_others_posts' => false,
                'delete_published_posts' => false,
                'edit_published_posts' => false,
                'edit_others_posts' => false,
                'manage_options' => false,
            )
        );
    }

    /**
     * Create custom database tables.
     */
    private function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'resume_ai_job_user_data';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            linkedin_url varchar(255) DEFAULT NULL,
            original_resume_id bigint(20) DEFAULT NULL,
            ats_resume_id bigint(20) DEFAULT NULL,
            human_resume_id bigint(20) DEFAULT NULL,
            published_resume_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        $table_name = $wpdb->prefix . 'resume_ai_job_positions';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            location varchar(255) DEFAULT NULL,
            salary_from bigint(20) DEFAULT NULL,
            salary_to bigint(20) DEFAULT NULL,
            salary_currency varchar(255) DEFAULT NULL,
            deadline datetime DEFAULT NULL,
            status varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);
        $table_name = $wpdb->prefix . 'resume_ai_job_applications';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            position_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            cover_letter text,
            resume_id bigint(20) NOT NULL,
            status varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Create custom capabilities.
     */
    private function create_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_resume_ai_job');
            $admin_role->add_cap('view_resume_ai_job_reports');
        }
    }
} 