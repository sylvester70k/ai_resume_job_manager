<?php
namespace ResumeAIJob\Core;

class Config {
    public static function init() {
        // Define TCPDF paths
        if (!defined('K_PATH_MAIN')) {
            define('K_PATH_MAIN', RESUME_AI_JOB_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/');
        }
        
        if (!defined('K_PATH_URL')) {
            define('K_PATH_URL', plugin_dir_url(RESUME_AI_JOB_PLUGIN_DIR) . 'vendor/tecnickcom/tcpdf/');
        }
        
        if (!defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
        }
        
        if (!defined('K_PATH_IMAGES')) {
            define('K_PATH_IMAGES', K_PATH_MAIN . 'images/');
        }
        
        if (!defined('K_PATH_CACHE')) {
            $upload_dir = wp_upload_dir();
            define('K_PATH_CACHE', $upload_dir['basedir'] . '/tcpdf_cache/');
            
            // Create cache directory if it doesn't exist
            if (!file_exists(K_PATH_CACHE)) {
                wp_mkdir_p(K_PATH_CACHE);
            }
        }

        // Set TCPDF external config flag
        if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
            define('K_TCPDF_EXTERNAL_CONFIG', true);
        }
        
        // Set default TCPDF settings
        if (!defined('PDF_PAGE_FORMAT')) {
            define('PDF_PAGE_FORMAT', 'A4');
        }
        
        if (!defined('PDF_PAGE_ORIENTATION')) {
            define('PDF_PAGE_ORIENTATION', 'P');
        }
        
        if (!defined('PDF_CREATOR')) {
            define('PDF_CREATOR', 'Resume AI Job');
        }
        
        if (!defined('PDF_AUTHOR')) {
            define('PDF_AUTHOR', 'Resume AI Job');
        }
        
        if (!defined('PDF_UNIT')) {
            define('PDF_UNIT', 'mm');
        }
        
        // Set margins
        if (!defined('PDF_MARGIN_HEADER')) {
            define('PDF_MARGIN_HEADER', 5);
        }
        
        if (!defined('PDF_MARGIN_FOOTER')) {
            define('PDF_MARGIN_FOOTER', 10);
        }
        
        if (!defined('PDF_MARGIN_TOP')) {
            define('PDF_MARGIN_TOP', 27);
        }
        
        if (!defined('PDF_MARGIN_BOTTOM')) {
            define('PDF_MARGIN_BOTTOM', 25);
        }
        
        if (!defined('PDF_MARGIN_LEFT')) {
            define('PDF_MARGIN_LEFT', 15);
        }
        
        if (!defined('PDF_MARGIN_RIGHT')) {
            define('PDF_MARGIN_RIGHT', 15);
        }

        // Set font settings
        if (!defined('PDF_FONT_NAME_MAIN')) {
            define('PDF_FONT_NAME_MAIN', 'helvetica');
        }

        if (!defined('PDF_FONT_SIZE_MAIN')) {
            define('PDF_FONT_SIZE_MAIN', 10);
        }

        if (!defined('PDF_FONT_NAME_DATA')) {
            define('PDF_FONT_NAME_DATA', 'helvetica');
        }

        if (!defined('PDF_FONT_SIZE_DATA')) {
            define('PDF_FONT_SIZE_DATA', 8);
        }

        // Set image settings
        if (!defined('PDF_IMAGE_SCALE_RATIO')) {
            define('PDF_IMAGE_SCALE_RATIO', 1.25);
        }

        // Set other settings
        if (!defined('HEAD_MAGNIFICATION')) {
            define('HEAD_MAGNIFICATION', 1.1);
        }

        if (!defined('K_CELL_HEIGHT_RATIO')) {
            define('K_CELL_HEIGHT_RATIO', 1.25);
        }

        if (!defined('K_TITLE_MAGNIFICATION')) {
            define('K_TITLE_MAGNIFICATION', 1.3);
        }

        if (!defined('K_SMALL_RATIO')) {
            define('K_SMALL_RATIO', 2/3);
        }

        // Set timezone
        if (!defined('K_TIMEZONE')) {
            define('K_TIMEZONE', 'UTC');
        }
    }
} 