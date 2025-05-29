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
        try {
            check_ajax_referer('resume_upload_nonce', 'resume_upload_nonce');

            if (!isset($_FILES['resume_file'])) {
                error_log('Resume Upload Error: No file uploaded');
                wp_send_json_error(array('message' => 'No file uploaded'));
                return;
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
                return;
            }

            // Use WordPress's built-in file handling
            $upload = wp_handle_upload($file, array('test_form' => false));
            if (isset($upload['error'])) {
                error_log('Resume Upload - Upload Error: ' . $upload['error']);
                wp_send_json_error(array('message' => $upload['error']));
                return;
            }

            error_log('Resume Upload - Upload Success: ' . print_r($upload, true));

            // Create attachment
            $attachment_id = $this->create_resume_attachment($upload['file'], $user_id);
            if (is_wp_error($attachment_id)) {
                error_log('Resume Upload - Attachment Error: ' . $attachment_id->get_error_message());
                wp_send_json_error(array('message' => $attachment_id->get_error_message()));
                return;
            }

            error_log('Resume Upload - Attachment Created: ' . $attachment_id);

            // Extract text from resume
            $resume_text = $this->extract_resume_text($upload['file']);
            if (is_wp_error($resume_text)) {
                error_log('Resume Upload - Text Extraction Error: ' . $resume_text->get_error_message());
                wp_send_json_error(array('message' => $resume_text->get_error_message()));
                return;
            }

            // Process with AI
            $ai_versions = $this->process_resume_with_ai($resume_text);
            if (is_wp_error($ai_versions)) {
                error_log('Resume Upload - AI Processing Error: ' . $ai_versions->get_error_message());
                wp_send_json_error(array('message' => $ai_versions->get_error_message()));
                return;
            }

            error_log('Resume Upload - AI Versions Created: ');

            // Save AI versions
            $version_ids = $this->save_ai_versions($ai_versions, $user_id, $attachment_id);
            error_log('Resume Upload - Version IDs: ' . print_r($version_ids, true));

            wp_send_json_success(array(
                'message' => 'Resume processed successfully',
                'original_id' => $attachment_id,
                'versions' => $version_ids
            ));

        } catch (\Exception $e) {
            error_log('Resume Upload - Critical Error: ' . $e->getMessage());
            error_log('Resume Upload - Stack Trace: ' . $e->getTraceAsString());
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while processing your resume. Please try again later.',
                'error_details' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
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
            
            $result = [
                'metadata' => [
                    'title' => $phpWord->getDocInfo()->getTitle() ?: '',
                    'creator' => $phpWord->getDocInfo()->getCreator() ?: '',
                    'company' => $phpWord->getDocInfo()->getCompany() ?: '',
                    'description' => $phpWord->getDocInfo()->getDescription() ?: '',
                    'category' => $phpWord->getDocInfo()->getCategory() ?: '',
                    'keywords' => $phpWord->getDocInfo()->getKeywords() ?: '',
                    'lastModifiedBy' => $phpWord->getDocInfo()->getLastModifiedBy() ?: '',
                    'created' => $phpWord->getDocInfo()->getCreated() ?: '',
                    'modified' => $phpWord->getDocInfo()->getModified() ?: ''
                ],
                'pages' => [],
                'images' => []
            ];

            // Process each section as a "page"
            $pageIndex = 0;
            foreach ($phpWord->getSections() as $section) {
                $pageData = [
                    'number' => $pageIndex + 1,
                    'text' => '',
                    'elements' => [],
                    'textWithPosition' => []
                ];

                $yPosition = 0;
                foreach ($section->getElements() as $element) {
                    $elementData = [
                        'type' => get_class($element),
                        'content' => '',
                        'style' => null,
                        'position' => ['y' => $yPosition]
                    ];

                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $textContent = '';
                        foreach ($element->getElements() as $textElement) {
                            if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                $textContent .= $textElement->getText();
                                if ($textElement->getFontStyle()) {
                                    $fontStyle = $textElement->getFontStyle();
                                    $elementData['style'] = [
                                        'name' => $fontStyle->getName() ?: '',
                                        'size' => $fontStyle->getSize() ?: 12,
                                        'bold' => $fontStyle->isBold() ?: false,
                                        'italic' => $fontStyle->isItalic() ?: false,
                                        'underline' => $fontStyle->getUnderline() ?: '',
                                        'color' => $fontStyle->getColor() ?: ''
                                    ];
                                }
                            } elseif ($textElement instanceof \PhpOffice\PhpWord\Element\Image) {
                                // Handle images within text runs
                                $result['images'][] = [
                                    'type' => $textElement->getImageType() ?: '',
                                    'width' => $textElement->getWidth() ?: 0,
                                    'height' => $textElement->getHeight() ?: 0,
                                    'page' => $pageIndex + 1,
                                    'position' => $yPosition
                                ];
                            }
                        }
                        $elementData['content'] = $textContent;
                        $pageData['text'] .= $textContent . "\n";
                        $pageData['textWithPosition'][] = [
                            'text' => $textContent,
                            'y' => $yPosition
                        ];
                        $yPosition += 20; // Approximate line height
                    } 
                    elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $elementData['content'] = $element->getText();
                        $pageData['text'] .= $element->getText() . "\n";
                        $pageData['textWithPosition'][] = [
                            'text' => $element->getText(),
                            'y' => $yPosition
                        ];
                        if ($element->getFontStyle()) {
                            $fontStyle = $element->getFontStyle();
                            $elementData['style'] = [
                                'name' => $fontStyle->getName() ?: '',
                                'size' => $fontStyle->getSize() ?: 12,
                                'bold' => $fontStyle->isBold() ?: false,
                                'italic' => $fontStyle->isItalic() ?: false,
                                'underline' => $fontStyle->getUnderline() ?: '',
                                'color' => $fontStyle->getColor() ?: ''
                            ];
                        }
                        $yPosition += 20;
                    }
                    elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                        $elementData['content'] = "• " . $element->getText();
                        $pageData['text'] .= "• " . $element->getText() . "\n";
                        $pageData['textWithPosition'][] = [
                            'text' => "• " . $element->getText(),
                            'y' => $yPosition
                        ];
                        $yPosition += 20;
                    }
                    elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        $tableContent = '';
                        foreach ($element->getRows() as $row) {
                            $rowContent = '';
                            foreach ($row->getCells() as $cell) {
                                $cellContent = '';
                                foreach ($cell->getElements() as $cellElement) {
                                    if ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                        foreach ($cellElement->getElements() as $textElement) {
                                            if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                                $cellContent .= $textElement->getText() . ' ';
                                            } elseif ($textElement instanceof \PhpOffice\PhpWord\Element\Image) {
                                                // Handle images within table cells
                                                $result['images'][] = [
                                                    'type' => $textElement->getImageType() ?: '',
                                                    'width' => $textElement->getWidth() ?: 0,
                                                    'height' => $textElement->getHeight() ?: 0,
                                                    'page' => $pageIndex + 1,
                                                    'position' => $yPosition
                                                ];
                                            }
                                        }
                                    } elseif ($cellElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                        $cellContent .= $cellElement->getText() . ' ';
                                    }
                                }
                                $rowContent .= trim($cellContent) . "\t";
                            }
                            $tableContent .= trim($rowContent) . "\n";
                        }
                        $elementData['content'] = $tableContent;
                        $pageData['text'] .= $tableContent;
                        $pageData['textWithPosition'][] = [
                            'text' => $tableContent,
                            'y' => $yPosition
                        ];
                        $yPosition += 20 * count($element->getRows());
                    }
                    elseif ($element instanceof \PhpOffice\PhpWord\Element\Image) {
                        // Handle standalone images
                        $result['images'][] = [
                            'type' => $element->getImageType() ?: '',
                            'width' => $element->getWidth() ?: 0,
                            'height' => $element->getHeight() ?: 0,
                            'page' => $pageIndex + 1,
                            'position' => $yPosition
                        ];
                        $yPosition += ($element->getHeight() ?: 0) + 20; // Add image height plus some padding
                    }

                    $pageData['elements'][] = $elementData;
                }

                $result['pages'][] = $pageData;
                $pageIndex++;
            }

            if (empty($result['pages'])) {
                return new \WP_Error('extraction_error', 'Could not extract content from DOCX file');
            }

            return $result;
        } catch (\Exception $e) {
            return new \WP_Error('docx_error', 'Error processing DOCX: ' . $e->getMessage());
        }
    }

    private function extract_text_from_doc($file_path) {
        try {
            // Check if the PhpWord library is available
            if (!class_exists('\\PhpOffice\\PhpWord\\IOFactory')) {
                return new \WP_Error('missing_library', 'PhpWord library is not installed. Please install phpoffice/phpword via Composer.');
            }

            // For old DOC files, we'll need to convert them to DOCX first
            try {
                // Load the DOC file
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($file_path);
                
                // Create a temporary DOCX file
                $temp_docx = wp_upload_dir()['path'] . '/temp_' . time() . '.docx';
                
                // Save as DOCX
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($temp_docx);
                
                // Extract text from the converted DOCX
                $result = $this->extract_text_from_docx($temp_docx);
                
                // Clean up temporary file
                @unlink($temp_docx);
                
                return $result;
            } catch (\Exception $e) {
                return new \WP_Error('doc_error', 'Error converting DOC to DOCX: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            return new \WP_Error('doc_error', 'Error processing DOC: ' . $e->getMessage());
        }
    }

    private function process_resume_with_ai($detailedElements) {
        try {
            if (empty($this->ai_api_key)) {
                error_log('AI Processing Error: API key not configured');
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
                        try {
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
                                throw new \Exception('Error processing with AI: ' . $error_message);
                            }

                            $response_code = wp_remote_retrieve_response_code($response);
                            if ($response_code !== 200) {
                                $error_message = 'AI API returned non-200 status code: ' . $response_code;
                                error_log($error_message);
                                error_log('Response body: ' . wp_remote_retrieve_body($response));
                                throw new \Exception($error_message);
                            }

                            $body = json_decode(wp_remote_retrieve_body($response), true);
                            if (isset($body['choices'][0]['message']['content'])) {
                                if (!isset($versions[$type])) {
                                    $versions[$type] = array();
                                }
                                $ai_version = json_decode($body['choices'][0]['message']['content'], true);
                                
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    error_log('AI Response JSON Decode Error: ' . json_last_error_msg());
                                    error_log('Raw AI Response: ' . $body['choices'][0]['message']['content']);
                                    throw new \Exception('Invalid JSON response from AI service');
                                }
                                
                                // Merge new content with existing version
                                if ($type == 'ats') {
                                    $this->ai_ats_version = $this->mergeResumeData($this->ai_ats_version, $ai_version);
                                    $versions[$type] = $this->ai_ats_version;
                                } else if ($type == 'human') {
                                    $this->ai_human_version = $this->mergeResumeData($this->ai_human_version, $ai_version);
                                    $versions[$type] = $this->ai_human_version;
                                }
                                
                                $success = true;
                                error_log("Resume analyze successful for type {$type}: ");
                            } else {
                                error_log('AI Response Error: ' . print_r($body, true));
                                throw new \Exception('Invalid response structure from AI service');
                            }
                        } catch (\Exception $e) {
                            error_log('AI Processing Exception (Attempt ' . ($retryCount + 1) . '): ' . $e->getMessage());
                            $retryCount++;
                            if ($retryCount < $maxRetries) {
                                sleep(2);
                                continue;
                            }
                            throw $e;
                        }
                    }

                    if (!$success) {
                        throw new \Exception('Failed to process page ' . ($pageIndex + 1) . ' after ' . $maxRetries . ' attempts');
                    }
                }
            }

            if (empty($versions)) {
                throw new \Exception('No content was generated by the AI service');
            }

            return $versions;

        } catch (\Exception $e) {
            error_log('AI Processing Critical Error: ' . $e->getMessage());
            error_log('AI Processing Stack Trace: ' . $e->getTraceAsString());
            return new \WP_Error('ai_error', 'Error processing resume with AI: ' . $e->getMessage());
        }
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
        try {
            $version_ids = array();
            
            foreach ($versions as $type => $content) {
                error_log('Processing version type: ' . $type);
                error_log('Content structure: ' . print_r(array_keys($content), true));
                
                // Clean and deduplicate content
                $content = $this->clean_resume_content($content);
                error_log('Content cleaned successfully');
                
                // Get template type based on original file
                $original_file = get_attached_file($original_id);
                if (!$original_file) {
                    error_log('Error: Could not get original file path for attachment ID: ' . $original_id);
                    throw new \Exception('Could not retrieve original file path');
                }
                
                $file_extension = pathinfo($original_file, PATHINFO_EXTENSION);
                error_log('File extension: ' . $file_extension);
                
                // Apply template
                error_log('Attempting to apply template for type: ' . $type);
                error_log('Template manager class: ' . get_class($this->template_manager));
                error_log('Template manager methods: ' . print_r(get_class_methods($this->template_manager), true));
                
                try {
                    $document = $this->template_manager->apply_template('html', 'default', $content);
                    error_log('Template application result type: ' . gettype($document));
                    
                    if (is_wp_error($document)) {
                        error_log('Template Error for ' . $type . ': ' . $document->get_error_message());
                        throw new \Exception('Template application failed: ' . $document->get_error_message());
                    }
                    
                    if (!$document) {
                        error_log('Template application returned null or false');
                        throw new \Exception('Template application returned no document');
                    }
                    
                    error_log('Template applied successfully');
                } catch (\Exception $e) {
                    error_log('Template application exception: ' . $e->getMessage());
                    error_log('Template application stack trace: ' . $e->getTraceAsString());
                    throw $e;
                }
                
                // Generate file path with better naming convention
                $upload_dir = wp_upload_dir();
                if (!isset($upload_dir['path']) || !is_writable($upload_dir['path'])) {
                    error_log('Error: Upload directory is not writable or does not exist: ' . print_r($upload_dir, true));
                    throw new \Exception('Upload directory is not writable');
                }
                
                $timestamp = date('Ymd_His');
                $file_path = $upload_dir['path'] . '/resume_' . $type . '_' . $user_id . '_' . $timestamp . '.' . $file_extension;
                error_log('Saving file to: ' . $file_path);
                
                try {
                    // Save document
                    if ($file_extension === 'pdf') {
                        error_log('Attempting to save PDF document');
                        // Convert HTML to PDF using template manager
                        $pdf = $this->template_manager->convert_to_pdf($document, $this->template_manager->get_template('html', 'default'));
                        if (!$pdf || !method_exists($pdf, 'Output')) {
                            error_log('Error: Failed to convert HTML to PDF');
                            throw new \Exception('Failed to convert HTML to PDF');
                        }
                        $pdf->Output($file_path, 'F');
                        error_log('PDF document saved successfully');
                    } else if ($file_extension === 'html') {
                        error_log('Attempting to save HTML document');
                        if (!is_string($document)) {
                            error_log('Error: Document is not a string');
                            throw new \Exception('Invalid document format for HTML generation');
                        }
                        if (file_put_contents($file_path, $document) === false) {
                            error_log('Error: Failed to write HTML file');
                            throw new \Exception('Failed to write HTML file');
                        }
                        error_log('HTML document saved successfully');
                    } else {
                        error_log('Attempting to save Word document');
                        if (!class_exists('\\PhpOffice\\PhpWord\\IOFactory')) {
                            error_log('Error: PhpWord library not found');
                            throw new \Exception('PhpWord library is not installed');
                        }

                        // Create new PhpWord instance
                        $phpWord = new \PhpOffice\PhpWord\PhpWord();
                        
                        // Set document properties
                        $phpWord->getDocInfo()
                            ->setCreator('Resume AI Job Manager')
                            ->setCompany('Resume AI')
                            ->setTitle('Resume - ' . $content['name'])
                            ->setDescription('Generated Resume')
                            ->setCategory('Resume')
                            ->setLastModifiedBy('Resume AI Job Manager')
                            ->setCreated(time())
                            ->setModified(time());

                        // Add a section
                        $section = $phpWord->addSection([
                            'marginLeft' => 600,
                            'marginRight' => 600,
                            'marginTop' => 600,
                            'marginBottom' => 600
                        ]);

                        // Set default font
                        $phpWord->setDefaultFontName('Helvetica');
                        $phpWord->setDefaultFontSize(12);
                        
                        // Convert HTML to Word document
                        $dom = new \DOMDocument();
                        @$dom->loadHTML(mb_convert_encoding($document, 'HTML-ENTITIES', 'UTF-8'));
                        
                        // Process each element
                        $this->processHtmlElement($dom->documentElement, $section);
                        
                        // Save the document
                        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                        $objWriter->save($file_path);
                        error_log('Word document saved successfully');
                    }
                    
                    // Verify file was created
                    if (!file_exists($file_path)) {
                        error_log('Error: File was not created at path: ' . $file_path);
                        throw new \Exception('Failed to create output file');
                    }
                    
                    // Create attachment
                    error_log('Creating attachment for file: ' . $file_path);
                    $attachment_id = $this->create_resume_attachment($file_path, $user_id);
                    if (is_wp_error($attachment_id)) {
                        error_log('Failed to create attachment: ' . $attachment_id->get_error_message());
                        throw new \Exception('Failed to create attachment: ' . $attachment_id->get_error_message());
                    }
                    
                    update_post_meta($attachment_id, '_resume_type', 'ai_' . $type);
                    update_post_meta($attachment_id, '_original_resume_id', $original_id);
                    $version_ids[$type] = $attachment_id;
                    
                    error_log('Successfully saved ' . $type . ' version with ID: ' . $attachment_id);
                    
                } catch (\Exception $e) {
                    error_log('Error saving ' . $type . ' version: ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    throw $e;
                }
            }

            if (empty($version_ids)) {
                error_log('No versions were successfully created');
                throw new \Exception('Failed to create any resume versions');
            } else {
                error_log('Created versions: ' . print_r($version_ids, true));
            }

            return $version_ids;
            
        } catch (\Exception $e) {
            error_log('Critical error in save_ai_versions: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Process HTML element and convert it to Word document elements
     */
    private function processHtmlElement($element, $section) {
        if (!$element) return;

        // Handle different node types
        if ($element instanceof \DOMText) {
            // Handle text nodes
            if (trim($element->textContent) !== '') {
                $section->addText($element->textContent);
            }
            return;
        }

        if ($element instanceof \DOMElement) {
            // Get element style
            $style = [];
            if ($element->hasAttribute('style')) {
                $styleString = $element->getAttribute('style');
                $styles = explode(';', $styleString);
                foreach ($styles as $styleItem) {
                    $parts = explode(':', $styleItem);
                    if (count($parts) === 2) {
                        $style[trim($parts[0])] = trim($parts[1]);
                    }
                }
            }

            // Get class for styling
            $classes = $element->hasAttribute('class') ? explode(' ', $element->getAttribute('class')) : [];

            // Process different element types
            switch (strtolower($element->nodeName)) {
                case 'body':
                    // Set default font and size for the document
                    $section->setStyle([
                        'font' => 'helvetica',
                        'size' => 12,
                        'lineHeight' => 1.6
                    ]);
                    // Process children
                    foreach ($element->childNodes as $child) {
                        $this->processHtmlElement($child, $section);
                    }
                    break;

                case 'div':
                    if (in_array('header', $classes)) {
                        $section->addTextBreak(1);
                        // Process header content
                        foreach ($element->childNodes as $child) {
                            $this->processHtmlElement($child, $section);
                        }
                        $section->addTextBreak(1);
                    } elseif (in_array('section', $classes)) {
                        $section->addTextBreak(1);
                        // Process section content
                        foreach ($element->childNodes as $child) {
                            $this->processHtmlElement($child, $section);
                        }
                        $section->addTextBreak(1);
                    } else {
                        // Process other divs
                        foreach ($element->childNodes as $child) {
                            $this->processHtmlElement($child, $section);
                        }
                    }
                    break;

                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                    $text = $element->textContent;
                    if (in_array('section-title', $classes)) {
                        $section->addText($text, [
                            'bold' => true,
                            'size' => 16,
                            'underline' => 'single'
                        ]);
                    } else {
                        $section->addText($text, [
                            'bold' => true,
                            'size' => 16
                        ]);
                    }
                    break;

                case 'p':
                    if (in_array('name', $classes)) {
                        $section->addText($element->textContent, [
                            'bold' => true,
                            'size' => 24,
                            'alignment' => 'center'
                        ]);
                    } elseif (in_array('contact-info', $classes)) {
                        $section->addText($element->textContent, [
                            'alignment' => 'center'
                        ]);
                    } else {
                        $section->addText($element->textContent, [
                            'size' => 12
                        ]);
                    }
                    break;

                case 'ul':
                case 'ol':
                    if (in_array('skills-list', $classes)) {
                        // Handle skills list differently
                        $text = '';
                        foreach ($element->childNodes as $li) {
                            if ($li instanceof \DOMElement && $li->nodeName === 'li') {
                                $text .= $li->textContent . ' | ';
                            }
                        }
                        $section->addText(trim($text, ' | '), ['size' => 12]);
                    } else {
                        foreach ($element->childNodes as $li) {
                            if ($li instanceof \DOMElement && $li->nodeName === 'li') {
                                $text = $li->textContent;
                                $section->addListItem($text, 0);
                            }
                        }
                    }
                    break;

                case 'table':
                    $table = $section->addTable([
                        'borderSize' => 6,
                        'borderColor' => '999999',
                        'cellMargin' => 80
                    ]);
                    foreach ($element->childNodes as $tr) {
                        if ($tr instanceof \DOMElement && $tr->nodeName === 'tr') {
                            $row = $table->addRow();
                            foreach ($tr->childNodes as $td) {
                                if ($td instanceof \DOMElement && ($td->nodeName === 'td' || $td->nodeName === 'th')) {
                                    $cell = $row->addCell();
                                    $cell->addText($td->textContent);
                                }
                            }
                        }
                    }
                    break;

                case 'br':
                    $section->addTextBreak();
                    break;

                case 'span':
                    if (in_array('item-header', $classes)) {
                        $section->addText($element->textContent, ['bold' => true]);
                    } elseif (in_array('item-date', $classes)) {
                        $section->addText($element->textContent, ['italic' => true]);
                    } else {
                        $section->addText($element->textContent);
                    }
                    break;

                default:
                    // For unknown elements, just process their children
                    foreach ($element->childNodes as $child) {
                        $this->processHtmlElement($child, $section);
                    }
                    break;
            }
        }
    }

    /**
     * Clean and deduplicate resume content
     */
    private function clean_resume_content($content) {
        // Clean basic fields
        $basic_fields = ['name', 'email', 'phone', 'address', 'summary'];
        foreach ($basic_fields as $field) {
            if (isset($content[$field])) {
                $content[$field] = trim($content[$field]);
                if ($content[$field] === 'N/A') {
                    $content[$field] = '';
                }
            }
        }

        // Clean and deduplicate arrays
        $array_fields = ['social_links', 'skills', 'certifications', 'languages', 'interests', 'awards'];
        foreach ($array_fields as $field) {
            if (isset($content[$field]) && is_array($content[$field])) {
                // Remove empty values and duplicates
                $content[$field] = array_values(array_filter(array_unique($content[$field])));
            }
        }

        // Clean and deduplicate experience
        if (isset($content['experience']) && is_array($content['experience'])) {
            $unique_experiences = [];
            $seen = [];
            
            foreach ($content['experience'] as $exp) {
                // Skip if missing required fields
                if (empty($exp['company']) || empty($exp['title'])) {
                    continue;
                }
                
                // Create unique key
                $key = $exp['company'] . '|' . $exp['title'] . '|' . $exp['start_date'] . '|' . $exp['end_date'];
                
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique_experiences[] = $exp;
                }
            }
            
            $content['experience'] = $unique_experiences;
        }

        // Clean and deduplicate education
        if (isset($content['education']) && is_array($content['education'])) {
            $unique_education = [];
            $seen = [];
            
            foreach ($content['education'] as $edu) {
                // Skip if missing required fields
                if (empty($edu['school']) || empty($edu['degree'])) {
                    continue;
                }
                
                // Create unique key
                $key = $edu['school'] . '|' . $edu['degree'] . '|' . $edu['start_date'] . '|' . $edu['end_date'];
                
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique_education[] = $edu;
                }
            }
            
            $content['education'] = $unique_education;
        }

        // Clean and deduplicate projects
        if (isset($content['projects']) && is_array($content['projects'])) {
            $unique_projects = [];
            $seen = [];
            
            foreach ($content['projects'] as $proj) {
                // Skip if missing required fields
                if (empty($proj['name'])) {
                    continue;
                }
                
                // Create unique key
                $key = $proj['name'] . '|' . $proj['start_date'] . '|' . $proj['end_date'];
                
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique_projects[] = $proj;
                }
            }
            
            $content['projects'] = $unique_projects;
        }

        return $content;
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

