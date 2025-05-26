<?php
namespace ResumeAIJob\Core;

class Deactivator {
    /**
     * Deactivate the plugin.
     */
    public function deactivate() {
        $this->remove_roles();
        $this->remove_capabilities();
        $this->remove_tables();
    }

    /**
     * Remove custom roles.
     */
    private function remove_roles() {
        remove_role('resume_user');
    }

    /**
     * Remove custom capabilities.
     */
    private function remove_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('manage_resume_ai_job');
            $admin_role->remove_cap('view_resume_ai_job_reports');
        }
    }

    /**
     * Remove custom database tables.
     */
    private function remove_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}resume_ai_job_user_data");
    }
} 