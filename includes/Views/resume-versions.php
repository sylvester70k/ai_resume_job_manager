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

// Helper to get attachment URL safely
function get_resume_url($resume) {
    return ($resume && get_post_type($resume) === 'attachment') ? wp_get_attachment_url($resume->ID) : false;
}
?>

<div class="max-w-6xl mx-auto px-2 sm:px-4 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-10 text-center">Your Resume Versions</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php
        $resumes = [
            [
                'label' => 'Original Resume',
                'badge' => 'Original',
                'badgeColor' => 'bg-blue-100 text-blue-700',
                'resume' => $original_resume,
            ],
            [
                'label' => 'ATS-Optimized Resume',
                'badge' => 'ATS',
                'badgeColor' => 'bg-green-100 text-green-700',
                'resume' => $ats_resume,
            ],
            [
                'label' => 'Human-Friendly Resume',
                'badge' => 'Human',
                'badgeColor' => 'bg-yellow-100 text-yellow-700',
                'resume' => $human_resume,
            ],
        ];
        foreach ($resumes as $item):
            $resume = $item['resume'];
            $resume_url = get_resume_url($resume);
            $is_published = is_resume_published($resume, $published_resume);
        ?>
        <div class="flex flex-col bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transition-transform hover:scale-[1.02]">
            <div class="flex-1 flex flex-col p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 leading-tight"><?php echo $item['label']; ?></h3>
                    <div class="flex gap-2 items-center">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $item['badgeColor']; ?> border border-transparent shadow-sm"><?php echo $item['badge']; ?></span>
                        <?php if ($is_published): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200 shadow-sm animate-pulse">Published</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-1 flex items-center justify-center bg-gray-50 rounded-lg mb-5 min-h-[220px] max-h-[500px] overflow-hidden">
                    <?php if ($resume_url): ?>
                        <iframe src="<?php echo esc_url($resume_url); ?>" class="w-full h-[400px] border-0 rounded" loading="lazy" onerror="this.parentNode.innerHTML='<div class=\'text-center text-gray-400 text-sm\'>Unable to preview this resume.</div>';" ></iframe>
                    <?php else: ?>
                        <div class="text-center text-gray-400 text-sm py-10">Resume not available or not generated yet.</div>
                    <?php endif; ?>
                </div>
                <div class="flex gap-3 mt-auto">
                    <?php if ($resume_url): ?>
                        <a href="<?php echo esc_url($resume_url); ?>" download class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                            <i class="dashicons dashicons-download mr-2"></i>Download
                        </a>
                    <?php endif; ?>
                    <?php if ($resume_url && !$is_published): ?>
                        <button type="button" onclick="publishResume(<?php echo $resume->ID; ?>)" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium border border-transparent bg-blue-600 text-white hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <i class="dashicons dashicons-visibility mr-2"></i>Publish
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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