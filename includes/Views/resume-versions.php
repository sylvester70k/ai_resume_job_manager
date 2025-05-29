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
    if (!$resume) {
        error_log('Resume object is null');
        return false;
    }
    if (get_post_type($resume) !== 'attachment') {
        error_log('Resume is not an attachment. Post type: ' . get_post_type($resume));
        return false;
    }
    $url = wp_get_attachment_url($resume->ID);
    if (!$url) {
        error_log('Failed to get attachment URL for resume ID: ' . $resume->ID);
        return false;
    }
    
    // Check if file exists
    $file_path = get_attached_file($resume->ID);
    if (!file_exists($file_path)) {
        error_log('File does not exist at path: ' . $file_path);
        return false;
    }
    
    // Check file permissions
    if (!is_readable($file_path)) {
        error_log('File is not readable: ' . $file_path);
        return false;
    }
    
    return $url;
}
?>

<div class="bg-gradient-to-br from-blue-50 to-purple-100 py-10 px-2 !max-w-full">
    <div class="mx-auto container">
        <h1 class="text-4xl font-extrabold text-center text-gray-900 mb-2 tracking-tight">Your Resume Versions</h1>
        <p class="text-center text-gray-500 mb-10">Easily manage and publish your different resume versions</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $resumes = [
                [
                    'label' => 'Original Resume',
                    'badge' => 'Original',
                    'badgeColor' => 'bg-blue-100 text-blue-700',
                    'icon' => 'document-text',
                    'resume' => $original_resume,
                ],
                [
                    'label' => 'ATS-Optimized Resume',
                    'badge' => 'ATS',
                    'badgeColor' => 'bg-green-100 text-green-700',
                    'icon' => 'cpu',
                    'resume' => $ats_resume,
                ],
                [
                    'label' => 'Human-Friendly Resume',
                    'badge' => 'Human',
                    'badgeColor' => 'bg-yellow-100 text-yellow-700',
                    'icon' => 'user',
                    'resume' => $human_resume,
                ],
            ];
            foreach ($resumes as $item):
                $resume = $item['resume'];
                $resume_url = get_resume_url($resume);
                $is_published = is_resume_published($resume, $published_resume);
                error_log('Processing resume: ' . $item['label']);
                error_log('Resume object: ' . print_r($resume, true));
                error_log('Resume URL: ' . ($resume_url ? $resume_url : 'null'));
                error_log('Is published: ' . ($is_published ? 'true' : 'false'));
            ?>
            <div class="w-full flex flex-col bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transition-transform hover:scale-[1.03] animate-fade-in">
                <div class="flex-1 flex flex-col p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 leading-tight"><?php echo esc_html($item['label']); ?></h3>
                        <div class="flex gap-2 items-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold <?php echo esc_attr($item['badgeColor']); ?> border border-transparent shadow-sm">
                                <?php if ($item['icon'] === 'document-text'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" />
                                    </svg>
                                <?php endif; ?>
                                <?php if ($item['icon'] === 'cpu'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3v2m0 14v2m7-7h-2M5 12H3m15.364-6.364l-1.414 1.414M6.343 17.657l-1.414 1.414m12.728 0l-1.414-1.414M6.343 6.343L4.929 4.929" />
                                    </svg>
                                <?php endif; ?>
                                <?php if ($item['icon'] === 'user'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1112 21a9 9 0 01-6.879-3.196z" />
                                    </svg>
                                <?php endif; ?>
                                <?php echo esc_html($item['badge']); ?>
                            </span>
                            <?php if ($is_published): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200 shadow-sm animate-pulse">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Published
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg mb-5 min-h-[220px] max-h-[400px] overflow-hidden border border-gray-200 shadow-inner">
                        <?php if ($resume_url): ?>
                            <iframe 
                                id="resume-preview-<?php echo esc_attr($resume->ID); ?>" 
                                src="<?php echo esc_url($resume_url); ?>#toolbar=0&navpanes=0" 
                                class="w-full h-[300px] border-0 rounded" 
                                loading="lazy"
                            ></iframe>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center w-full h-full text-gray-400 text-sm py-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 018 0v2m-4-4a4 4 0 100-8 4 4 0 000 8z" />
                                </svg>
                                Resume not available or not generated yet.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-3 mt-auto">
                        <?php if ($resume_url): ?>
                            <a href="<?php echo esc_url($resume_url); ?>" 
                               download="<?php echo esc_attr($resume->post_title); ?>" 
                               class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold border border-gray-300 bg-white text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                </svg>
                                Download
                            </a>
                            <?php if (!$is_published): ?>
                                <button type="button" 
                                        onclick="publishResume(<?php echo esc_attr($resume->ID); ?>)" 
                                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold border border-transparent bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    Publish
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// function injectTailwindStyles(iframe) {
//     try {
//         const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        
//         // Create and inject Tailwind script
//         const tailwindScript = iframeDoc.createElement('script');
//         tailwindScript.src = 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4';
//         iframeDoc.head.appendChild(tailwindScript);

//         // Add a style tag for any additional custom styles
//         const customStyles = iframeDoc.createElement('style');
//         customStyles.textContent = `
//             body { margin: 0; padding: 1rem; }
//             * { box-sizing: border-box; }
//         `;
//         iframeDoc.head.appendChild(customStyles);
//     } catch (e) {
//         console.error('Error injecting styles into iframe:', e);
//     }
// }

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