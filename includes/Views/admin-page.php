<?php
/**
 * Admin Page Template
 * 
 * @package ResumeAIJobPlugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get all users with resume_user role
$users = get_users(array('role__in' => array('resume_user')));

// Get all resume posts
$resume_posts = get_posts(array(
    'post_type' => 'resume_post',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'users';
?>

<style>
.resume-ai-job-wrap {
    margin: 20px;
}

.resume-ai-job-header {
    margin-bottom: 20px;
}

.resume-ai-job-header h1 {
    font-size: 23px;
    font-weight: 400;
    margin: 0;
    padding: 9px 0 4px;
    line-height: 1.3;
}

.resume-ai-job-tabs {
    border-bottom: 1px solid #c3c4c7;
    margin-bottom: 20px;
}

.resume-ai-job-tabs a {
    display: inline-block;
    padding: 10px 15px;
    text-decoration: none;
    color: #646970;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}

.resume-ai-job-tabs a.active {
    color: #2271b1;
    border-bottom-color: #2271b1;
}

.resume-ai-job-tabs a:hover {
    color: #2271b1;
}

/* User Management Styles */
.resume-ai-job-users {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.resume-ai-job-users-header {
    padding: 15px;
    border-bottom: 1px solid #c3c4c7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.resume-ai-job-users-header h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.resume-ai-job-users-table {
    width: 100%;
    border-collapse: collapse;
}

.resume-ai-job-users-table th {
    text-align: left;
    padding: 8px 10px;
    border-bottom: 1px solid #c3c4c7;
    background: #f0f0f1;
    font-weight: 600;
}

.resume-ai-job-users-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #c3c4c7;
    vertical-align: middle;
}

.resume-ai-job-users-table tr:last-child td {
    border-bottom: none;
}

.resume-ai-job-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.resume-ai-job-user-info {
    display: flex;
    align-items: center;
}

.resume-ai-job-user-name {
    font-weight: 600;
    color: #1d2327;
}

.resume-ai-job-user-username {
    color: #646970;
    font-size: 13px;
}

.resume-ai-job-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.resume-ai-job-badge-green {
    background: #edfaef;
    color: #1e7e34;
}

.resume-ai-job-action-link {
    color: #2271b1;
    text-decoration: none;
    margin-right: 10px;
}

.resume-ai-job-action-link:hover {
    color: #135e96;
}

.resume-ai-job-action-link.delete {
    color: #d63638;
}

.resume-ai-job-action-link.delete:hover {
    color: #b32d2e;
}

/* Resume Grid Styles */
.resume-ai-job-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.resume-ai-job-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 15px;
}

.resume-ai-job-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.resume-ai-job-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.resume-ai-job-card-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.resume-ai-job-card-badge.original {
    background: #e6f3ff;
    color: #2271b1;
}

.resume-ai-job-card-badge.ats {
    background: #edfaef;
    color: #1e7e34;
}

.resume-ai-job-card-badge.human {
    background: #f6e7f5;
    color: #8c1749;
}

.resume-ai-job-card-info {
    margin-bottom: 15px;
}

.resume-ai-job-card-info p {
    margin: 5px 0;
    color: #646970;
    font-size: 13px;
}

.resume-ai-job-card-info strong {
    color: #1d2327;
}

.resume-ai-job-card-actions {
    display: flex;
    gap: 10px;
}

/* Modal Styles */
.resume-ai-job-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
}

.resume-ai-job-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 20px;
    border-radius: 3px;
    min-width: 400px;
    max-width: 90%;
}

.resume-ai-job-modal-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #c3c4c7;
}

.resume-ai-job-modal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.resume-ai-job-form-group {
    margin-bottom: 15px;
}

.resume-ai-job-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.resume-ai-job-form-group input {
    width: 100%;
    padding: 5px 8px;
}

.resume-ai-job-modal-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #c3c4c7;
    text-align: right;
}

.resume-ai-job-modal-footer .button {
    margin-left: 10px;
}
</style>

<div class="resume-ai-job-wrap">
    <div class="resume-ai-job-header">
        <h1>Resume AI Job Management</h1>
    </div>

    <!-- Tabs -->
    <nav class="resume-ai-job-tabs">
        <a href="?page=resume-ai-job&tab=users" 
           class="<?php echo $current_tab === 'users' ? 'active' : ''; ?>">
            Users
        </a>
        <a href="?page=resume-ai-job&tab=resumes" 
           class="<?php echo $current_tab === 'resumes' ? 'active' : ''; ?>">
            Resumes
        </a>
    </nav>

    <?php if ($current_tab === 'users'): ?>
        <!-- Users Tab -->
        <div class="resume-ai-job-users">
            <div class="resume-ai-job-users-header">
                <h2>User Management</h2>
                <button type="button" 
                        onclick="openAddUserModal()"
                        class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                    Add User
                </button>
            </div>

            <table class="resume-ai-job-users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Resumes</th>
                        <th>Last Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $resume_count = count(get_posts(array(
                            'post_type' => 'resume_post',
                            'author' => $user->ID,
                            'posts_per_page' => -1
                        )));
                        $last_active = get_user_meta($user->ID, 'last_active', true);
                    ?>
                        <tr>
                            <td>
                                <div class="resume-ai-job-user-info">
                                    <?php echo get_avatar($user->ID, 40, '', '', array('class' => 'resume-ai-job-user-avatar')); ?>
                                    <div>
                                        <div class="resume-ai-job-user-name">
                                            <?php echo esc_html($user->display_name); ?>
                                        </div>
                                        <div class="resume-ai-job-user-username">
                                            <?php echo esc_html($user->user_login); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <span class="resume-ai-job-badge resume-ai-job-badge-green">
                                    <?php echo $resume_count; ?> resume<?php echo $resume_count !== 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $last_active ? date('M j, Y', strtotime($last_active)) : 'Never'; ?>
                            </td>
                            <td>
                                <a href="#" onclick="viewUserResumes(<?php echo esc_js($user->ID); ?>)" 
                                   class="resume-ai-job-action-link">View Resumes</a>
                                <a href="#" onclick="editUser(<?php echo esc_js($user->ID); ?>)" 
                                   class="resume-ai-job-action-link">Edit</a>
                                <a href="#" onclick="deleteUser(<?php echo esc_js($user->ID); ?>)" 
                                   class="resume-ai-job-action-link delete">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- Resumes Tab -->
        <div class="resume-ai-job-grid">
            <?php foreach ($resume_posts as $post): 
                $resume_file_id = get_post_meta($post->ID, '_resume_file_id', true);
                $resume_file = get_attached_file($resume_file_id);
                $resume_type = get_post_meta($resume_file_id, '_resume_type', true);
                $user = get_user_by('id', $post->post_author);
            ?>
                <div class="resume-ai-job-card">
                    <div class="resume-ai-job-card-header">
                        <h3 class="resume-ai-job-card-title">
                            <?php echo esc_html($post->post_title); ?>
                        </h3>
                        <span class="resume-ai-job-card-badge <?php echo $resume_type; ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $resume_type))); ?>
                        </span>
                    </div>

                    <div class="resume-ai-job-card-info">
                        <p>
                            <strong>Author:</strong>
                            <?php echo esc_html($user->display_name); ?>
                        </p>
                        <p>
                            <strong>Date:</strong>
                            <?php echo get_the_date('F j, Y', $post->ID); ?>
                        </p>
                        <p>
                            <strong>Status:</strong>
                            <?php echo esc_html($post->post_status); ?>
                        </p>
                    </div>

                    <div class="resume-ai-job-card-actions">
                        <a href="<?php echo wp_get_attachment_url($resume_file_id); ?>" 
                           target="_blank"
                           class="button">
                            <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span>
                            View
                        </a>
                        
                        <button type="button"
                                class="button button-primary"
                                onclick="downloadResume(<?php echo esc_js($resume_file_id); ?>)">
                            <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                            Download
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" class="resume-ai-job-modal">
    <div class="resume-ai-job-modal-content">
        <div class="resume-ai-job-modal-header">
            <h3>Add New User</h3>
        </div>
        <form id="add-user-form">
            <?php wp_nonce_field('add_user_nonce', 'add_user_nonce'); ?>
            <div class="resume-ai-job-form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="resume-ai-job-form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="resume-ai-job-form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="resume-ai-job-form-group">
                <label for="linkedin_url">LinkedIn URL</label>
                <input type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/username">
            </div>
            <div class="resume-ai-job-form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="resume-ai-job-modal-footer">
                <button type="button" onclick="closeAddUserModal()" class="button">Cancel</button>
                <button type="submit" class="button button-primary">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="resume-ai-job-modal">
    <div class="resume-ai-job-modal-content">
        <div class="resume-ai-job-modal-header">
            <h3>Edit User</h3>
        </div>
        <form id="edit-user-form">
            <?php wp_nonce_field('edit_user_nonce', 'edit_user_nonce'); ?>
            <input type="hidden" id="edit_user_id" name="user_id">
            <div class="resume-ai-job-form-group">
                <label for="edit_first_name">First Name</label>
                <input type="text" id="edit_first_name" name="first_name" required>
            </div>
            <div class="resume-ai-job-form-group">
                <label for="edit_last_name">Last Name</label>
                <input type="text" id="edit_last_name" name="last_name" required>
            </div>
            <div class="resume-ai-job-form-group">
                <label for="edit_email">Email</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            <div class="resume-ai-job-form-group">
                <label for="edit_linkedin_url">LinkedIn URL</label>
                <input type="url" id="edit_linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/username">
            </div>
            <div class="resume-ai-job-form-group">
                <label for="edit_password">New Password (leave blank to keep current)</label>
                <input type="password" id="edit_password" name="password">
            </div>
            <div class="resume-ai-job-modal-footer">
                <button type="button" onclick="closeEditUserModal()" class="button">Cancel</button>
                <button type="submit" class="button button-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
// User Management Functions
function openAddUserModal() {
    document.getElementById('add-user-modal').style.display = 'block';
}

function closeAddUserModal() {
    document.getElementById('add-user-modal').style.display = 'none';
}

function viewUserResumes(userId) {
    window.location.href = `?page=resume-ai-job&tab=resumes&user=${userId}`;
}

function editUser(userId) {
    // Get user data
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'get_user_data',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('get_user_data_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                // Populate form
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_first_name').value = response.data.first_name;
                document.getElementById('edit_last_name').value = response.data.last_name;
                document.getElementById('edit_email').value = response.data.email;
                document.getElementById('edit_linkedin_url').value = response.data.linkedin_url;
                document.getElementById('edit_password').value = '';
                
                // Show modal
                document.getElementById('edit-user-modal').style.display = 'block';
            } else {
                alert('Failed to get user data');
            }
        }
    });
}

function closeEditUserModal() {
    document.getElementById('edit-user-modal').style.display = 'none';
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This will also delete all their resumes.')) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_user',
                user_id: userId,
                nonce: '<?php echo wp_create_nonce('delete_user_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to delete user');
                }
            }
        });
    }
}

// Resume Management Functions
function downloadResume(fileId) {
    const downloadUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    jQuery.ajax({
        url: downloadUrl,
        type: 'POST',
        data: {
            action: 'download_resume',
            file_id: fileId,
            nonce: '<?php echo wp_create_nonce('download_resume_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                window.location.href = response.data.url;
            } else {
                alert('Failed to download resume');
            }
        },
        error: function() {
            alert('An error occurred while downloading the resume');
        }
    });
}

// Handle Add User Form Submission
jQuery('#add-user-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_user');
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to add user');
            }
        }
    });
});

// Handle Edit User Form Submission
jQuery('#edit-user-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'edit_user');
    formData.append('nonce', '<?php echo wp_create_nonce('edit_user_nonce'); ?>');
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to update user');
            }
        }
    });
});
</script>