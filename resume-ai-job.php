<?php
/**
 * Plugin Name: Resume AI Job
 * Plugin URI: https://yourwebsite.com/resume-ai-job
 * Description: Adds AI-powered resume features to your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: resume-ai-job
 * Domain Path: /languages
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RESUME_AI_JOB_VERSION', '1.0.0');
define('RESUME_AI_JOB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RESUME_AI_JOB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer's autoloader
if (file_exists(RESUME_AI_JOB_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once RESUME_AI_JOB_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Resume AI Job plugin requires Composer dependencies to be installed. Please run <code>composer install</code> in the plugin directory.', 'resume-ai-job'); ?></p>
        </div>
        <?php
    });
    return;
}

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'ResumeAIJob\\';
    
    // Base directory for the namespace prefix
    $base_dir = RESUME_AI_JOB_PLUGIN_DIR . 'includes/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function resume_ai_job_init() {
    // Initialize the main plugin class
    $plugin = new ResumeAIJob\Core\Plugin();
    $plugin->init();
}

// Register settings
function resume_ai_job_register_settings() {
    register_setting('resume_ai_job_options', 'resume_ai_job_versions_page');
    
    add_settings_section(
        'resume_ai_job_main_section',
        'Main Settings',
        null,
        'resume_ai_job_options'
    );
}

// Hook into WordPress
add_action('plugins_loaded', 'resume_ai_job_init');
add_action('admin_init', 'resume_ai_job_register_settings');

// Activation hook
register_activation_hook(__FILE__, 'resume_ai_job_activate');
function resume_ai_job_activate() {
    // Create template directories
    $template_dir = RESUME_AI_JOB_PLUGIN_DIR . 'templates/html';
    if (!file_exists($template_dir)) {
        wp_mkdir_p($template_dir);
    }
    
    // Create cache directory in uploads
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/resume-ai-cache';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    $deactivator = new ResumeAIJob\Core\Deactivator();
    $deactivator->deactivate();
}); 