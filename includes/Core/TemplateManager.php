<?php
namespace ResumeAIJob\Core;

class TemplateManager {
    private $template_dir;
    private $templates = [];

    public function __construct() {
        $this->template_dir = RESUME_AI_JOB_PLUGIN_DIR . 'templates/';
        $this->load_templates();
    }

    private function load_templates() {
        // Load PDF templates
        $pdf_dir = $this->template_dir . 'pdf/';
        if (is_dir($pdf_dir)) {
            $this->templates['pdf'] = $this->scan_templates($pdf_dir);
        }

        // Load DOC templates
        $doc_dir = $this->template_dir . 'doc/';
        if (is_dir($doc_dir)) {
            $this->templates['doc'] = $this->scan_templates($doc_dir);
        }

        // Load DOCX templates
        $docx_dir = $this->template_dir . 'docx/';
        if (is_dir($docx_dir)) {
            $this->templates['docx'] = $this->scan_templates($docx_dir);
        }
    }

    private function scan_templates($dir) {
        $templates = [];
        $files = glob($dir . '*.{php,pdf,doc,docx}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            
            // Load configuration if it's a PHP file
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
                $config = include $file;
                $templates[$name] = [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'settings' => $config['settings'],
                    'type' => pathinfo($dir, PATHINFO_FILENAME)
                ];
            } else {
                $templates[$name] = [
                    'path' => $file,
                    'name' => $name,
                    'type' => pathinfo($filename, PATHINFO_EXTENSION)
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
        if (isset($this->templates[$type][$name])) {
            return $this->templates[$type][$name];
        }
        return null;
    }

    public function apply_template($type, $name, $content) {
        $template = $this->get_template($type, $name);
        if (!$template) {
            return new \WP_Error('template_not_found', 'Template not found');
        }

        switch ($type) {
            case 'pdf':
                return $this->apply_pdf_template($template, $content);
            case 'doc':
            case 'docx':
                return $this->apply_word_template($template, $content);
            default:
                return new \WP_Error('invalid_type', 'Invalid template type');
        }
    }

    private function apply_pdf_template($template, $content) {
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
                $settings['margins']['left'],
                $settings['margins']['top'],
                $settings['margins']['right']
            );
            
            // Set font
            $pdf->SetFont(
                $settings['font']['family'],
                '',
                $settings['font']['size']
            );
        }
        
        // Add content
        if (is_array($content)) {
            foreach ($content as $pageContent) {
                $pdf->AddPage();
                
                // Apply section formatting
                $sections = explode("\n\n", $pageContent);
                foreach ($sections as $section) {
                    if (strpos($section, ':') !== false) {
                        // This is a header section
                        list($header, $text) = explode(':', $section, 2);
                        $pdf->SetFont($settings['font']['family'], 'B', $settings['font']['header_size']);
                        $pdf->Write(10, trim($header) . ":\n");
                        $pdf->SetFont($settings['font']['family'], '', $settings['font']['size']);
                        $pdf->Write(10, trim($text) . "\n\n");
                    } else {
                        $pdf->Write(10, $section . "\n\n");
                    }
                }
            }
        } else {
            $pdf->AddPage();
            $pdf->Write(10, $content);
        }
        
        return $pdf;
    }

    private function apply_word_template($template, $content) {
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'vendor/autoload.php';
        
        // Create new Word document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Apply template settings
        if (isset($template['settings'])) {
            $settings = $template['settings'];
            
            // Add styles
            $phpWord->addTitleStyle(1, $settings['styles']['header']);
            $phpWord->addTitleStyle(2, $settings['styles']['subheader']);
            $phpWord->addParagraphStyle('Normal', $settings['styles']['normal']);
        }
        
        // Add a section
        $section = $phpWord->addSection();
        
        // Add content
        if (is_array($content)) {
            foreach ($content as $pageContent) {
                // Apply section formatting
                $sections = explode("\n\n", $pageContent);
                foreach ($sections as $section) {
                    if (strpos($section, ':') !== false) {
                        // This is a header section
                        list($header, $text) = explode(':', $section, 2);
                        $section->addTitle(trim($header), 1);
                        $section->addText(trim($text), 'Normal');
                    } else {
                        $section->addText($section, 'Normal');
                    }
                }
                $section->addPageBreak();
            }
        } else {
            $section->addText($content, 'Normal');
        }
        
        return $phpWord;
    }
} 