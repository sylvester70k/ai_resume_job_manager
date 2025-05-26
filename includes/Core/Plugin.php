<?php
namespace ResumeAIJob\Core;

class Plugin {
    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Admin instance.
     *
     * @var \ResumeAIJob\Admin\Admin
     */
    private $admin;

    /**
     * Auth instance.
     *
     * @var \ResumeAIJob\Core\Auth
     */
    private $auth;

    /**
     * Asset Manager instance.
     *
     * @var \ResumeAIJob\Core\AssetManager
     */
    private $asset_manager;

    /**
     * Get plugin instance.
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    public function init() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));

        // Initialize admin
        if (is_admin()) {
            $this->admin = new \ResumeAIJob\Admin\Admin();
            $this->admin->init();
        }

        // Initialize auth
        $this->auth = new Auth();
        $this->auth->init();

        // Initialize asset manager
        $this->asset_manager = new AssetManager();
        $this->asset_manager->init();

        // Initialize AJAX handlers
        $ajax = new \ResumeAIJob\Ajax\AjaxHandler();
        $ajax->init();
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'resume-ai-job',
            false,
            dirname(dirname(dirname(plugin_basename(__FILE__)))) . '/languages/'
        );
    }
} 