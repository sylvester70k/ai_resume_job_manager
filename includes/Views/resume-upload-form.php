<?php
/**
 * Resume Upload Form Template
 * 
 * @package ResumeAIJobPlugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
?>

<style>
.resume-ai-job-login-form {
    max-width: 400px;
    margin: 2em auto;
    padding: 2em;
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.resume-ai-job-login-form h2 {
    margin: 0 0 1em;
    padding: 0;
    font-size: 1.5em;
    font-weight: 600;
    color: #1d2327;
}

.resume-ai-job-login-form label {
    display: block;
    margin-bottom: 0.5em;
    font-weight: 500;
}

.resume-ai-job-login-form input[type="text"],
.resume-ai-job-login-form input[type="password"] {
    width: 100%;
    padding: 8px;
    margin-bottom: 1em;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
}

.resume-ai-job-login-form input[type="submit"] {
    width: 100%;
    padding: 8px 16px;
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.resume-ai-job-login-form input[type="submit"]:hover {
    background: #135e96;
}

.resume-ai-job-login-links {
    margin-top: 1em;
    text-align: center;
}

.resume-ai-job-login-links a {
    color: #2271b1;
    text-decoration: none;
}

.resume-ai-job-login-links a:hover {
    color: #135e96;
}

.resume-ai-job-error {
    padding: 1em;
    margin: 1em 0;
    background: #f8d7da;
    border: 1px solid #f5c2c7;
    color: #842029;
    border-radius: 3px;
}
</style>

<div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Upload Your Resume</h2>
        <p class="text-gray-600">Please upload your resume in PDF or DOCX format (max 5MB)</p>
    </div>

    <form id="resume-upload-form" class="space-y-6" enctype="multipart/form-data">
        <?php wp_nonce_field('resume_upload_nonce', 'resume_upload_nonce'); ?>
        
        <!-- File Upload Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-center w-full">
                <label for="resume-file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500">PDF or DOCX (MAX. 5MB)</p>
                    </div>
                    <input id="resume-file" name="resume_file" type="file" class="hidden" accept=".pdf,.docx" />
                </label>
            </div>
            
            <!-- File Preview -->
            <div id="file-preview" class="hidden">
                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                    <svg class="w-8 h-8 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div class="flex-1">
                        <p id="file-name" class="text-sm font-medium text-gray-900"></p>
                        <p id="file-size" class="text-xs text-gray-500"></p>
                    </div>
                    <button type="button" id="remove-file" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div id="upload-error" class="hidden p-4 text-sm text-red-700 bg-red-100 rounded-lg"></div>

        <!-- Success Message -->
        <div id="upload-success" class="hidden p-4 text-sm text-green-700 bg-green-100 rounded-lg"></div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" id="submit-resume" class="px-6 py-2.5 bg-blue-600 text-white font-medium text-sm leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="flex items-center">
                    <svg id="loading-spinner" class="hidden animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Upload Resume
                </span>
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    const form = $('#resume-upload-form');
    const fileInput = $('#resume-file');
    const filePreview = $('#file-preview');
    const fileName = $('#file-name');
    const fileSize = $('#file-size');
    const removeFile = $('#remove-file');
    const uploadError = $('#upload-error');
    const uploadSuccess = $('#upload-success');
    const submitButton = $('#submit-resume');
    const loadingSpinner = $('#loading-spinner');

    // Handle file selection
    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const validTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!validTypes.includes(file.type)) {
                showError('Please upload a PDF or DOCX file');
                fileInput.val('');
                return;
            }

            // Validate file size (5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                showError('File size must be less than 5MB');
                fileInput.val('');
                return;
            }

            // Show file preview
            fileName.text(file.name);
            fileSize.text(formatFileSize(file.size));
            filePreview.removeClass('hidden');
            uploadError.addClass('hidden');
        }
    });

    // Remove file
    removeFile.on('click', function() {
        fileInput.val('');
        filePreview.addClass('hidden');
        uploadError.addClass('hidden');
    });

    // Handle form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Show loading state
        submitButton.prop('disabled', true);
        loadingSpinner.removeClass('hidden');
        uploadError.addClass('hidden');
        uploadSuccess.addClass('hidden');

        $.ajax({
            url: resume_ai_job.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                formData.append('action', 'resume_upload');
                formData.append('resume_upload_nonce', $('#resume_upload_nonce').val());
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Resume uploaded successfully!');
                    form[0].reset();
                    filePreview.addClass('hidden');
                } else {
                    showError(response.data.message || 'An error occurred while uploading your resume.');
                }
            },
            error: function() {
                showError('An error occurred while uploading your resume. Please try again.');
            },
            complete: function() {
                submitButton.prop('disabled', false);
                loadingSpinner.addClass('hidden');
            }
        });
    });

    // Helper functions
    function showError(message) {
        uploadError.text(message).removeClass('hidden');
        uploadSuccess.addClass('hidden');
    }

    function showSuccess(message) {
        uploadSuccess.text(message).removeClass('hidden');
        uploadError.addClass('hidden');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
