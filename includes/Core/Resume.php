<?php
namespace ResumeAIJob\Core;

class Resume {
    private $ai_api_key;
    private $ai_api_url = 'https://api.openai.com/v1/chat/completions'; // Example AI API endpoint
    private $template_manager;
    private $ai_ats_version = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'social_links' => [],
        'summary' => '',
        'experience' => [],
        'education' => [],
        'skills' => [],
        'projects' => [],
        'certifications' => [],
        'languages' => [],
        'interests' => [],
        'awards' => []
    ];
    private $ai_human_version = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'social_links' => [],
        'summary' => '',
        'experience' => [],
        'education' => [],
        'skills' => [],
        'projects' => [],
        'certifications' => [],
        'languages' => [],
        'interests' => [],
        'awards' => []
    ];

    public function init() {
        // Add shortcode for resume upload form
        add_shortcode('resume_upload_form', array($this, 'render_resume_upload_form'));
        
        // Add AJAX handlers
        add_action('wp_ajax_resume_upload', array($this, 'handle_resume_upload'));
        add_action('wp_ajax_get_resume_versions', array($this, 'get_resume_versions'));
        add_action('wp_ajax_publish_resume', array($this, 'publish_resume'));
        
        // Add login redirect
        add_filter('login_redirect', array($this, 'handle_login_redirect'), 10, 3);
        
        // Get AI API key from options
        $this->ai_api_key = get_option('resume_ai_job_api_key');
        
        // Initialize template manager
        $this->template_manager = new TemplateManager();
    }

    /**
     * Handle login redirect
     */
    public function handle_login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (!is_wp_error($user) && $user->has_cap('resume_user')) {
            // Get the resume upload page URL
            $upload_page = get_option('resume_ai_job_upload_page');
            if ($upload_page) {
                return get_permalink($upload_page);
            }
        }
        return $redirect_to;
    }

    /**
     * Render resume upload form
     */
    public function render_resume_upload_form() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="resume-ai-job-error">You must be logged in to upload resumes.</div>
            <a href="javascript:void(0)" class="resume-ai-job-login-link" onclick="window.location.href=\'' . home_url() . '\'">Go to Home</a>';
        }

        // Check if user has resume_user role
        $current_user = wp_get_current_user();
        if (!in_array('resume_user', $current_user->roles)) {
            return '<div class="resume-ai-job-error">You do not have permission to upload resumes.</div>';
        }

        // Enqueue necessary scripts
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'resume_ai_job', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resume_upload_nonce')
        ));

        ob_start();
        require_once RESUME_AI_JOB_PLUGIN_DIR . 'includes/Views/resume-upload-form.php';
        return ob_get_clean();
    }

    public function handle_resume_upload() {
        check_ajax_referer('resume_upload_nonce', 'resume_upload_nonce');

        if (!isset($_FILES['resume_file'])) {
            wp_send_json_error(array('message' => 'No file uploaded'));
        }

        $file = $_FILES['resume_file'];
        $user_id = get_current_user_id();

        // Log file information
        error_log('Resume Upload - File Info: ' . print_r($file, true));

        // Validate file
        $validation = $this->validate_resume_file($file);
        if (is_wp_error($validation)) {
            error_log('Resume Upload - Validation Error: ' . $validation->get_error_message());
            wp_send_json_error(array('message' => $validation->get_error_message()));
        }

        // Use WordPress's built-in file handling
        $upload = wp_handle_upload($file, array('test_form' => false));
        if (isset($upload['error'])) {
            error_log('Resume Upload - Upload Error: ' . $upload['error']);
            wp_send_json_error(array('message' => $upload['error']));
        }

        error_log('Resume Upload - Upload Success: ' . print_r($upload, true));

        // Create attachment
        $attachment_id = $this->create_resume_attachment($upload['file'], $user_id);
        if (is_wp_error($attachment_id)) {
            error_log('Resume Upload - Attachment Error: ' . $attachment_id->get_error_message());
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        error_log('Resume Upload - Attachment Created: ' . $attachment_id);

        // Extract text from resume
        $resume_text = $this->extract_resume_text($upload['file']);
        if (is_wp_error($resume_text)) {
            error_log('Resume Upload - Text Extraction Error: ' . $resume_text->get_error_message());
            wp_send_json_error(array('message' => $resume_text->get_error_message()));
        }
        // error_log('Resume Upload - Text Extracted: ' . $resume_text);

        // error_log('Resume Upload - Text Extracted: ' . substr($resume_text, 0, 100) . '...');

        // Process with AI
        $ai_versions = $this->process_resume_with_ai($resume_text);
        if (is_wp_error($ai_versions)) {
            error_log('Resume Upload - AI Processing Error: ' . $ai_versions->get_error_message());
            wp_send_json_error(array('message' => $ai_versions->get_error_message()));
        }

        error_log('Resume Upload - AI Versions Created: ' . print_r($ai_versions, true));

        // Save AI versions
        $version_ids = $this->save_ai_versions($ai_versions, $user_id, $attachment_id);
        error_log('Resume Upload - Version IDs: ' . print_r($version_ids, true));

        wp_send_json_success(array(
            'message' => 'Resume processed successfully',
            'original_id' => $attachment_id,
            'versions' => $version_ids
        ));
    }

    private function validate_resume_file($file) {
        // Check file type
        $allowed_types = array(
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword'
        );
        
        if (!in_array($file['type'], $allowed_types)) {
            return new \WP_Error('invalid_type', 'Only PDF, DOC, and DOCX files are allowed');
        }

        // Check file size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return new \WP_Error('invalid_size', 'File size must be less than 5MB');
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('upload_error', 'File upload failed');
        }

        return true;
    }

    private function create_resume_attachment($file_path, $user_id) {
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name, null);

        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name($file_name),
            'post_content' => '',
            'post_status' => 'private',
            'post_author' => $user_id
        );

        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Add custom meta
        update_post_meta($attachment_id, '_resume_user_id', $user_id);
        update_post_meta($attachment_id, '_resume_type', 'original');

        return $attachment_id;
    }

    private function extract_resume_text($file_path) {
        $file_type = wp_check_filetype(basename($file_path), null);
        
        if ($file_type['type'] === 'application/pdf') {
            return $this->extract_text_from_pdf($file_path);
        } else if ($file_type['type'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return $this->extract_text_from_docx($file_path);
        } else if ($file_type['type'] === 'application/msword') {
            return $this->extract_text_from_doc($file_path);
        }
        
        return new \WP_Error('invalid_type', 'Unsupported file type');
    }

    private function extract_text_from_pdf($file_path) {
        try {
            if (!class_exists('\\Smalot\\PdfParser\\Parser')) {
                return new \WP_Error('missing_library', 'PDF Parser library is not installed.');
            }

            $detailedElements = extractDetailedPdfElements($file_path);
            
            if (is_wp_error($detailedElements)) {
                return $detailedElements;
            }
            
            // Return the detailed elements structure instead of combining text
            return $detailedElements;
            
        } catch (\Exception $e) {
            return new \WP_Error('pdf_error', 'Error processing PDF: ' . $e->getMessage());
        }
    }

    private function extract_text_from_docx($file_path) {
        try {
            // Check if the PhpWord library is available
            if (!class_exists('\\PhpOffice\\PhpWord\\IOFactory')) {
                return new \WP_Error('missing_library', 'PhpWord library is not installed. Please install phpoffice/phpword via Composer.');
            }

            // Load the document
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file_path);
            
            // Extract text from all sections
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            
            // Clean up the text
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (empty($text)) {
                return new \WP_Error('extraction_error', 'Could not extract text from DOCX file');
            }
            
            return $text;
        } catch (\Exception $e) {
            return new \WP_Error('docx_error', 'Error processing DOCX: ' . $e->getMessage());
        }
    }

    private function extract_text_from_doc($file_path) {
        // For old DOC files, we'll need to convert them to DOCX first
        try {
            // Check if the PhpWord library is available
            if (!class_exists('\\PhpOffice\\PhpWord\\IOFactory')) {
                return new \WP_Error('missing_library', 'PhpWord library is not installed. Please install phpoffice/phpword via Composer.');
            }

            // Convert DOC to DOCX using PhpWord
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file_path);
            
            // Create a temporary DOCX file
            $temp_docx = wp_upload_dir()['path'] . '/temp_' . time() . '.docx';
            $phpWord->save($temp_docx);
            
            // Extract text from the converted DOCX
            $text = $this->extract_text_from_docx($temp_docx);
            
            // Clean up temporary file
            @unlink($temp_docx);
            
            return $text;
        } catch (\Exception $e) {
            return new \WP_Error('doc_error', 'Error processing DOC: ' . $e->getMessage());
        }
    }

    private function process_resume_with_ai($detailedElements) {
        if (empty($this->ai_api_key)) {
            return new \WP_Error('no_api_key', 'AI API key not configured');
        }

        $versions = array();
        $prompts = array(
            'ats' => "Analyze this resume section and create an ATS-optimized version that maintains the original content but improves keyword optimization and structure for applicant tracking systems:\n\n",
            'human' => "Analyze this resume section and create a human-friendly version that maintains the original content but improves readability, formatting, and impact:\n\n"
        );

        $systemPrompt = "You are a professional resume writer.";

        // Process each page separately
        foreach ($detailedElements['pages'] as $pageIndex => $page) {
            $pageText = $page['text'];
            
            // Process each version type
            foreach ($prompts as $type => $basePrompt) {
                $prompt = $basePrompt . '<resume>' . $pageText . '</resume>';
                
                // Add timeout and retry logic
                $maxRetries = 3;
                $retryCount = 0;
                $success = false;
                $systemPrompt = $systemPrompt . $prompt;
                $userPrompt = "return valid json following the format:
                {
                    'name': 'name',
                    'email': 'email',
                    'phone': 'phone',
                    'address': 'address',
                    'social_links': ['social_link1', 'social_link2', 'social_link3'],
                    'summary': 'summary',
                    'experience': [
                        {
                            'company': 'company',
                            'title': 'title',
                            'start_date': 'start_date',
                            'end_date': 'end_date',
                            'description': 'description'
                        }
                    ],
                    'education': [
                        {
                            'school': 'school',
                            'degree': 'degree',
                            'start_date': 'start_date',
                            'end_date': 'end_date',
                            'description': 'description'
                        }
                    ],
                    'skills': ['skill1', 'skill2', 'skill3'],
                    'projects': [
                        {
                            'name': 'project_name',
                            'description': 'project_description',
                            'start_date': 'start_date',
                            'end_date': 'end_date'
                        }
                    ],
                    'certifications': ['certification1', 'certification2', 'certification3'],
                    'languages': ['language1', 'language2', 'language3'],
                    'interests': ['interest1', 'interest2', 'interest3'],
                    'awards': ['award1', 'award2', 'award3']
                }";
                
                while (!$success && $retryCount < $maxRetries) {
                    $response = wp_remote_post($this->ai_api_url, array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $this->ai_api_key,
                            'Content-Type' => 'application/json'
                        ),
                        'body' => json_encode(array(
                            'model' => 'gpt-4o',
                            'messages' => array(
                                array('role' => 'system', 'content' => $systemPrompt),
                                array('role' => 'user', 'content' => $userPrompt)
                            ),
                            'temperature' => 0.7,
                            'response_format' => array(
                                'type' => 'json_object'
                            )
                        )),
                        'timeout' => 30,
                        'sslverify' => false
                    ));

                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();
                        error_log('AI Processing Error (Attempt ' . ($retryCount + 1) . '): ' . $error_message);
                        
                        if (strpos($error_message, 'timeout') !== false) {
                            $retryCount++;
                            if ($retryCount < $maxRetries) {
                                sleep(2);
                                continue;
                            }
                        }
                        return new \WP_Error('ai_error', 'Error processing with AI: ' . $error_message);
                    }

                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (isset($body['choices'][0]['message']['content'])) {
                        if (!isset($versions[$type])) {
                            $versions[$type] = array();
                        }
                        $ai_version = json_decode($body['choices'][0]['message']['content'], true);
                        
                        // Merge new content with existing version
                        if ($type == 'ats') {
                            $this->ai_ats_version = $this->mergeResumeData($this->ai_ats_version, $ai_version);
                            $versions[$type] = $this->ai_ats_version;
                        } else if ($type == 'human') {
                            $this->ai_human_version = $this->mergeResumeData($this->ai_human_version, $ai_version);
                            $versions[$type] = $this->ai_human_version;
                        }
                        
                        $success = true;
                        error_log("resume analyze: " . print_r($ai_version, true));
                    } else {
                        error_log('AI Response Error: ' . print_r($body, true));
                        $retryCount++;
                        if ($retryCount < $maxRetries) {
                            sleep(2);
                            continue;
                        }
                        return new \WP_Error('ai_error', 'Invalid response from AI service');
                    }
                }

                if (!$success) {
                    return new \WP_Error('ai_error', 'Failed to process page ' . ($pageIndex + 1) . ' after ' . $maxRetries . ' attempts');
                }
            }
        }

        if (empty($versions)) {
            return new \WP_Error('ai_error', 'No content was generated by the AI service');
        }

        return $versions;
    }

    /**
     * Merge new resume data with existing data
     */
    private function mergeResumeData($existing, $new) {
        $merged = $existing;
        
        // Merge basic fields if they're empty in existing
        $basicFields = ['name', 'email', 'phone', 'address', 'summary'];
        foreach ($basicFields as $field) {
            if (empty($existing[$field]) && !empty($new[$field])) {
                $merged[$field] = $new[$field];
            }
        }
        
        // Merge arrays by appending new items
        $arrayFields = ['social_links', 'skills', 'certifications', 'languages', 'interests', 'awards'];
        foreach ($arrayFields as $field) {
            if (!empty($new[$field])) {
                $merged[$field] = array_unique(array_merge($existing[$field], $new[$field]));
            }
        }
        
        // Merge complex arrays (experience, education, projects)
        $complexFields = ['experience', 'education', 'projects'];
        foreach ($complexFields as $field) {
            if (!empty($new[$field])) {
                $merged[$field] = array_merge($existing[$field], $new[$field]);
            }
        }
        
        return $merged;
    }

    private function save_ai_versions($versions, $user_id, $original_id) {
        $version_ids = array();
        
        foreach ($versions as $type => $content) {
            // Get template type based on original file
            $original_file = get_attached_file($original_id);
            $template_type = pathinfo($original_file, PATHINFO_EXTENSION);
            
            // Apply template
            $document = $this->template_manager->apply_template($template_type, 'default', $content);
            if (is_wp_error($document)) {
                error_log('Template Error: ' . $document->get_error_message());
                continue;
            }
            
            // Generate file path
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/resume_' . $type . '_' . time() . '.' . $template_type;
            
            // Save document
            if ($template_type === 'pdf') {
                $document->Output($file_path, 'F');
            } else {
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($document, 'Word2007');
                $objWriter->save($file_path);
            }
            
            // Create attachment
            $attachment_id = $this->create_resume_attachment($file_path, $user_id);
            if (!is_wp_error($attachment_id)) {
                update_post_meta($attachment_id, '_resume_type', 'ai_' . $type);
                update_post_meta($attachment_id, '_original_resume_id', $original_id);
                $version_ids[$type] = $attachment_id;
            }
        }

        return $version_ids;
    }

    public function get_available_templates() {
        return $this->template_manager->get_templates();
    }

    public function get_resume_versions() {
        check_ajax_referer('resume_upload_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $original_id = intval($_POST['original_id']);
        
        $versions = array(
            'original' => $original_id,
            'ats' => get_post_meta($original_id, '_ai_version_ats', true),
            'human' => get_post_meta($original_id, '_ai_version_human', true)
        );
        
        wp_send_json_success($versions);
    }

    public function publish_resume() {
        check_ajax_referer('resume_upload_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $resume_id = intval($_POST['resume_id']);
        
        // Verify ownership
        if (get_post_meta($resume_id, '_resume_user_id', true) != $user_id) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        // Create resume post
        $resume_post = array(
            'post_title' => 'Resume - ' . get_the_author_meta('display_name', $user_id),
            'post_type' => 'resume_post',
            'post_status' => 'publish',
            'post_author' => $user_id
        );
        
        $post_id = wp_insert_post($resume_post);
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Failed to publish resume'));
        }
        
        // Link resume file
        update_post_meta($post_id, '_resume_file_id', $resume_id);
        
        wp_send_json_success(array('message' => 'Resume published successfully'));
    }
}

function extractDetailedPdfElements($file_path) {
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($file_path);
        
        $result = [
            'metadata' => $pdf->getDetails(),
            'pages' => [],
            'fonts' => [],
            'images' => []
        ];
        
        // Get all pages
        $pages = $pdf->getPages();
        foreach ($pages as $index => $page) {
            $pageData = [
                'number' => $index + 1,
                'text' => $page->getText(),
                'textWithPosition' => $page->getTextArray(),
                'elements' => []
            ];
            
            // Get page elements
            if (method_exists($page, 'getElements')) {
                $pageData['elements'] = $page->getElements();
            }
            
            $result['pages'][] = $pageData;
        }
        
        // Get fonts - Fixed to handle missing methods
        $fonts = $pdf->getFonts();
        foreach ($fonts as $font) {
            $fontData = [
                'name' => method_exists($font, 'getName') ? $font->getName() : 'Unknown',
                'type' => method_exists($font, 'getType') ? $font->getType() : 'Unknown'
            ];
            
            // Only add encoding if the method exists
            if (method_exists($font, 'getEncoding')) {
                $fontData['encoding'] = $font->getEncoding();
            }
            
            $result['fonts'][] = $fontData;
        }
        
        // Get images
        $images = $pdf->getObjectsByType('XObject', 'Image');
        foreach ($images as $image) {
            $imageData = [];
            
            // Only add properties if methods exist
            if (method_exists($image, 'getWidth')) {
                $imageData['width'] = $image->getWidth();
            }
            if (method_exists($image, 'getHeight')) {
                $imageData['height'] = $image->getHeight();
            }
            if (method_exists($image, 'getType')) {
                $imageData['type'] = $image->getType();
            }
            
            if (!empty($imageData)) {
                $result['images'][] = $imageData;
            }
        }
        
        return $result;
        
    } catch (\Exception $e) {
        return new \WP_Error('pdf_error', 'Error processing PDF: ' . $e->getMessage());
    }
}
