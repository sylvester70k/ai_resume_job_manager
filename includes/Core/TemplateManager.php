<?php
namespace ResumeAIJob\Core;

class TemplateManager {
    private $template_dir;
    private $templates = [];
    private $twig;

    public function __construct() {
        // Use plugin's templates directory with absolute path
        $this->template_dir = RESUME_AI_JOB_PLUGIN_DIR . 'templates/';
        error_log('Template directory: ' . $this->template_dir);
        
        // Create cache directory in WordPress uploads
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/resume-ai-cache';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $this->initialize_twig($cache_dir);
        $this->load_templates();
    }

    private function initialize_twig($cache_dir) {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'vendor/autoload.php';
        
        $loader = new \Twig\Loader\FilesystemLoader($this->template_dir);
        $this->twig = new \Twig\Environment($loader, [
            'cache' => $cache_dir,
            'auto_reload' => true,
            'debug' => true
        ]);
    }

    private function load_templates() {
        // Load HTML templates
        $html_dir = $this->template_dir . 'html/';
        error_log('Loading templates from: ' . $html_dir);
        
        if (is_dir($html_dir)) {
            $this->templates['html'] = $this->scan_templates($html_dir);
            error_log('Loaded templates: ' . print_r($this->templates['html'], true));
        } else {
            error_log('HTML template directory not found: ' . $html_dir);
        }
    }

    private function scan_templates($dir) {
        $templates = [];
        $files = glob($dir . '*.{html,twig}', GLOB_BRACE);
        error_log('Found template files: ' . print_r($files, true));
        
        foreach ($files as $file) {
            $filename = basename($file);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            
            // Load configuration if it exists
            $config_file = $dir . $name . '.config.php';
            if (file_exists($config_file)) {
                $config = include $config_file;
                $templates[$name] = [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'settings' => $config['settings'],
                    'type' => 'html',
                    'template' => $file
                ];
            } else {
                $templates[$name] = [
                    'name' => $name,
                    'type' => 'html',
                    'template' => $file
                ];
            }
        }
        
        return $templates;
    }

    public function get_templates($type = null) {
        if ($type) {
            return isset($this->templates[$type]) ? $this->templates[$type] : [];
        }
        return $this->templates;
    }

    public function get_template($type, $name) {
        error_log('Getting template: ' . $type . '/' . $name);
        error_log('Available templates: ' . print_r($this->templates, true));
        
        if (isset($this->templates[$type][$name])) {
            return $this->templates[$type][$name];
        }
        return null;
    }

    public function apply_template($type, $name, $content) {
        $template = $this->get_template($type, $name);
        if (!$template) {
            return new \WP_Error('template_not_found', 'Template not found: ' . $type . '/' . $name);
        }

        try {
            // Render the template with Twig
            $template_file = basename($template['template']);
            error_log('Rendering template file: ' . $template_file);
            
            $html = $this->twig->render($template_file, [
                'content' => $content,
                'settings' => $template['settings'] ?? []
            ]);

            // Convert HTML to PDF or DOCX based on type
            if ($type === 'pdf') {
                return $this->convert_to_pdf($html, $template);
            } else {
                return $this->convert_to_docx($html, $template);
            }
        } catch (\Exception $e) {
            error_log('Template error: ' . $e->getMessage());
            return new \WP_Error('template_error', $e->getMessage());
        }
    }

    private function convert_to_pdf($html, $template) {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'vendor/autoload.php';
        
        // Create new PDF instance
        $pdf = new \TCPDF();
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Resume AI Job');
        $pdf->SetTitle('Resume');
        
        // Apply template settings
        if (isset($template['settings'])) {
            $settings = $template['settings'];
            
            // Set margins
            $pdf->SetMargins(
                $settings['margins']['left'] ?? 15,
                $settings['margins']['top'] ?? 15,
                $settings['margins']['right'] ?? 15
            );
            
            // Set font
            $pdf->SetFont(
                $settings['font']['family'] ?? 'helvetica',
                '',
                $settings['font']['size'] ?? 12
            );
        }
        
        // Add page
        $pdf->AddPage();
        
        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf;
    }

    private function convert_to_docx($html, $template) {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'vendor/autoload.php';
        
        // Create new Word document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Apply template settings
        if (isset($template['settings'])) {
            $settings = $template['settings'];
            
            // Add styles
            $phpWord->addTitleStyle(1, $settings['styles']['header'] ?? ['bold' => true, 'size' => 16]);
            $phpWord->addTitleStyle(2, $settings['styles']['subheader'] ?? ['bold' => true, 'size' => 14]);
            $phpWord->addParagraphStyle('Normal', $settings['styles']['normal'] ?? ['size' => 12]);
        }
        
        // Add a section
        $section = $phpWord->addSection();
        
        // Convert HTML to Word
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
        
        return $phpWord;
    }
} 