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
?>

<style>
.resume-versions-container {
    max-width: 1200px;
    margin: 2em auto;
    padding: 0 1em;
}

.resume-versions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2em;
    margin-top: 2em;
}

.resume-version-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 1.5em;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.resume-version-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1em;
}

.resume-version-title {
    font-size: 1.2em;
    font-weight: 600;
    color: #1d2327;
    margin: 0;
}

.resume-version-badge {
    padding: 0.3em 0.8em;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 500;
}

.resume-version-badge.original {
    background: #e6f3ff;
    color: #2271b1;
}

.resume-version-badge.ats {
    background: #edfaef;
    color: #1e7e34;
}

.resume-version-badge.human {
    background: #fff8e5;
    color: #996300;
}

.resume-version-badge.published {
    background: #f0f6fc;
    color: #2271b1;
}

.resume-version-actions {
    display: flex;
    gap: 1em;
    margin-top: 1.5em;
}

.resume-version-button {
    display: inline-flex;
    align-items: center;
    padding: 0.5em 1em;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.resume-version-button.primary {
    background: #2271b1;
    color: #fff;
    border: none;
}

.resume-version-button.primary:hover {
    background: #135e96;
}

.resume-version-button.secondary {
    background: #f0f0f1;
    color: #1d2327;
    border: 1px solid #c3c4c7;
}

.resume-version-button.secondary:hover {
    background: #e5e5e5;
}

.resume-version-button i {
    margin-right: 0.5em;
}

.resume-version-preview {
    margin-top: 1em;
    padding: 1em;
    background: #f6f7f7;
    border-radius: 4px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.resume-version-preview iframe {
    width: 100%;
    height: 500px;
    border: none;
}
</style>

<div class="resume-versions-container">
    <h1 class="text-2xl font-bold mb-4">Your Resume Versions</h1>
    
    <div class="resume-versions-grid">
        <!-- Original Resume -->
        <div class="resume-version-card">
            <div class="resume-version-header">
                <h3 class="resume-version-title">Original Resume</h3>
                <span class="resume-version-badge original">Original</span>
            </div>
            
            <?php if ($original_resume): ?>
                <div class="resume-version-preview">
                    <iframe src="<?php echo wp_get_attachment_url($original_resume->ID); ?>" frameborder="0"></iframe>
                </div>
                
                <div class="resume-version-actions">
                    <a href="<?php echo wp_get_attachment_url($original_resume->ID); ?>" 
                       class="resume-version-button secondary" 
                       download>
                        <i class="dashicons dashicons-download"></i>
                        Download
                    </a>
                    <button type="button" 
                            class="resume-version-button primary"
                            onclick="publishResume(<?php echo $original_resume->ID; ?>)">
                        <i class="dashicons dashicons-visibility"></i>
                        Publish
                    </button>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No original resume uploaded yet.</p>
            <?php endif; ?>
        </div>

        <!-- ATS Resume -->
        <div class="resume-version-card">
            <div class="resume-version-header">
                <h3 class="resume-version-title">ATS-Optimized Resume</h3>
                <span class="resume-version-badge ats">ATS</span>
            </div>
            
            <?php if ($ats_resume): ?>
                <div class="resume-version-preview">
                    <iframe src="<?php echo wp_get_attachment_url($ats_resume->ID); ?>" frameborder="0"></iframe>
                </div>
                
                <div class="resume-version-actions">
                    <a href="<?php echo wp_get_attachment_url($ats_resume->ID); ?>" 
                       class="resume-version-button secondary" 
                       download>
                        <i class="dashicons dashicons-download"></i>
                        Download
                    </a>
                    <button type="button" 
                            class="resume-version-button primary"
                            onclick="publishResume(<?php echo $ats_resume->ID; ?>)">
                        <i class="dashicons dashicons-visibility"></i>
                        Publish
                    </button>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No ATS-optimized resume generated yet.</p>
            <?php endif; ?>
        </div>

        <!-- Human Resume -->
        <div class="resume-version-card">
            <div class="resume-version-header">
                <h3 class="resume-version-title">Human-Friendly Resume</h3>
                <span class="resume-version-badge human">Human</span>
            </div>
            
            <?php if ($human_resume): ?>
                <div class="resume-version-preview">
                    <iframe src="<?php echo wp_get_attachment_url($human_resume->ID); ?>" frameborder="0"></iframe>
                </div>
                
                <div class="resume-version-actions">
                    <a href="<?php echo wp_get_attachment_url($human_resume->ID); ?>" 
                       class="resume-version-button secondary" 
                       download>
                        <i class="dashicons dashicons-download"></i>
                        Download
                    </a>
                    <button type="button" 
                            class="resume-version-button primary"
                            onclick="publishResume(<?php echo $human_resume->ID; ?>)">
                        <i class="dashicons dashicons-visibility"></i>
                        Publish
                    </button>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No human-friendly resume generated yet.</p>
            <?php endif; ?>
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