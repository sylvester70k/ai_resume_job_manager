<?php
/**
 * Resume Versions Template
 * 
 * @package ResumeAIJobPlugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();

// Get user's resume data
global $wpdb;
$table_name = $wpdb->prefix . 'resume_ai_job_user_data';
$resume_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $current_user->ID
));

// Get resume attachments
$original_resume = $resume_data ? get_post($resume_data->original_resume_id) : null;
$ats_resume = $resume_data ? get_post($resume_data->ats_resume_id) : null;
$human_resume = $resume_data ? get_post($resume_data->human_resume_id) : null;
$published_resume = $resume_data ? get_post($resume_data->published_resume_id) : null;

// Function to check if a resume is published
function is_resume_published($resume, $published_resume) {
    return $resume && $published_resume && $resume->ID === $published_resume->ID;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Your Resume Versions</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Original Resume -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">Original Resume</h3>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-50 text-blue-700">Original</span>
                        <?php if (is_resume_published($original_resume, $published_resume)): ?>
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-50 text-purple-700">Published</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($original_resume): ?>
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 min-h-[200px] flex items-center justify-center">
                        <iframe src="<?php echo wp_get_attachment_url($original_resume->ID); ?>" 
                                class="w-full h-[500px] border-0" 
                                frameborder="0"></iframe>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="<?php echo wp_get_attachment_url($original_resume->ID); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                           download>
                            <i class="dashicons dashicons-download mr-2"></i>
                            Download
                        </a>
                        <?php if (!is_resume_published($original_resume, $published_resume)): ?>
                            <button type="button" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    onclick="publishResume(<?php echo $original_resume->ID; ?>)">
                                <i class="dashicons dashicons-visibility mr-2"></i>
                                Publish
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No original resume uploaded yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ATS Resume -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">ATS-Optimized Resume</h3>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-50 text-green-700">ATS</span>
                        <?php if (is_resume_published($ats_resume, $published_resume)): ?>
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-50 text-purple-700">Published</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($ats_resume): ?>
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 min-h-[200px] flex items-center justify-center">
                        <iframe src="<?php echo wp_get_attachment_url($ats_resume->ID); ?>" 
                                class="w-full h-[500px] border-0" 
                                frameborder="0"></iframe>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="<?php echo wp_get_attachment_url($ats_resume->ID); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                           download>
                            <i class="dashicons dashicons-download mr-2"></i>
                            Download
                        </a>
                        <?php if (!is_resume_published($ats_resume, $published_resume)): ?>
                            <button type="button" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    onclick="publishResume(<?php echo $ats_resume->ID; ?>)">
                                <i class="dashicons dashicons-visibility mr-2"></i>
                                Publish
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No ATS-optimized resume generated yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Human Resume -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">Human-Friendly Resume</h3>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-50 text-yellow-700">Human</span>
                        <?php if (is_resume_published($human_resume, $published_resume)): ?>
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-50 text-purple-700">Published</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($human_resume): ?>
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 min-h-[200px] flex items-center justify-center">
                        <iframe src="<?php echo wp_get_attachment_url($human_resume->ID); ?>" 
                                class="w-full h-[500px] border-0" 
                                frameborder="0"></iframe>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="<?php echo wp_get_attachment_url($human_resume->ID); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                           download>
                            <i class="dashicons dashicons-download mr-2"></i>
                            Download
                        </a>
                        <?php if (!is_resume_published($human_resume, $published_resume)): ?>
                            <button type="button" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    onclick="publishResume(<?php echo $human_resume->ID; ?>)">
                                <i class="dashicons dashicons-visibility mr-2"></i>
                                Publish
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No human-friendly resume generated yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function publishResume(resumeId) {
    if (!confirm('Are you sure you want to publish this resume version?')) {
        return;
    }

    jQuery.ajax({
        url: resume_ai_job.ajax_url,
        type: 'POST',
        data: {
            action: 'publish_resume',
            resume_id: resumeId,
            nonce: resume_ai_job.nonce
        },
        success: function(response) {
            if (response.success) {
                alert('Resume published successfully!');
                location.reload();
            } else {
                alert('Failed to publish resume: ' + response.data.message);
            }
        },
        error: function() {
            alert('An error occurred while publishing the resume.');
        }
    });
}
</script> 