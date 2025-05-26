<?php
namespace ResumeAIJob\Admin;

class Admin {
    /**
     * Initialize admin functionality.
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }

    /**
     * Render the admin page.
     */
    public function render_admin_page() {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/admin-page.php';
    }
} 