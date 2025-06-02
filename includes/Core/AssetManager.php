<?php
namespace ResumeAIJob\Core;

class AssetManager {
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_frontend_assets() {
        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue TinyMCE
        wp_enqueue_editor();

        // Enqueue Font Awesome
        wp_enqueue_style(
            'resume-ai-job-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );

        // Enqueue Tailwind CSS
        wp_enqueue_script(
            'resume-ai-job-tailwind',
            'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
            [],
            RESUME_AI_JOB_VERSION,
            false
        );

        // Enqueue custom styles
        wp_enqueue_style(
            'resume-ai-job-custom-styles',
            RESUME_AI_JOB_PLUGIN_URL . 'assets/css/custom.css',
            [],
            RESUME_AI_JOB_VERSION
        );

        // Enqueue plugin scripts
        wp_enqueue_script(
            'resume-ai-job-scripts',
            RESUME_AI_JOB_PLUGIN_URL . 'assets/js/resume-ai-job.js',
            ['jquery'],
            RESUME_AI_JOB_VERSION,
            true
        );

        // Enqueue register form script
        wp_enqueue_script(
            'resume-ai-job-register',
            RESUME_AI_JOB_PLUGIN_URL . 'assets/js/register-form.js',
            ['jquery'],
            RESUME_AI_JOB_VERSION,
            true
        );

        // Localize the script with new data
        wp_localize_script('resume-ai-job-scripts', 'resumeAiJob', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resume_ai_job_nonce')
        ]);

        // Localize the register form script
        wp_localize_script('resume-ai-job-register', 'resumeAiJob', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resume_ai_job_nonce')
        ]);
    }

    public function enqueue_admin_assets() {
        // Enqueue jQuery UI
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        // Enqueue admin styles
        wp_enqueue_style(
            'resume-ai-job-admin-styles',
            RESUME_AI_JOB_PLUGIN_URL . 'assets/css/admin.css',
            ['resume-ai-job-core-styles'],
            RESUME_AI_JOB_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'resume-ai-job-admin-scripts',
            RESUME_AI_JOB_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-dialog'],
            RESUME_AI_JOB_VERSION,
            true
        );

        // Localize the script with new data
        wp_localize_script('resume-ai-job-admin-scripts', 'resumeAiJob', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resume_ai_job_nonce')
        ]);
    }
} 