<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div class="resume-ai-job-admin-content">
        <h2>Welcome to Resume AI Job</h2>
        <p>This is your plugin management page. You can add your plugin settings and features here.</p>

        <!-- User Management Section -->
        <div class="user-management-section">
            <h3>User Management</h3>
            <button id="add-user-button" class="button button-primary">Add User</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>LinkedIn</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $users = get_users(array('role__in' => array('resume_user', 'administrator')));
                $custom_table = $wpdb->prefix . 'resume_ai_job_user_data';
                
                foreach ($users as $user) {
                    $custom_data = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $custom_table WHERE user_id = %d",
                        $user->ID
                    ));
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($user->ID) . '</td>';
                    echo '<td>' . esc_html($user->first_name) . '</td>';
                    echo '<td>' . esc_html($user->last_name) . '</td>';
                    echo '<td>' . esc_html($user->user_email) . '</td>';
                    echo '<td>' . esc_html(implode(', ', $user->roles)) . '</td>';
                    echo '<td>' . esc_html($custom_data ? $custom_data->linkedin_url : '') . '</td>';
                    echo '<td><a href="#" class="edit-user" data-id="' . esc_attr($user->ID) . '">Edit</a> | <a href="#" class="delete-user" data-id="' . esc_attr($user->ID) . '">Delete</a></td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Dialog -->
<div id="edit-user-dialog" style="display:none;">
    <h3>Edit User</h3>
    <form id="edit-user-form" class="edit-user-form">
        <?php wp_nonce_field('resume_ai_job_nonce', 'resume_ai_job_nonce'); ?>
        <input type="hidden" id="edit-user-id" name="user_id">
        <label for="edit-first-name">First Name:</label>
        <input type="text" id="edit-first-name" name="first_name" required class="form-control"><br>
        <label for="edit-last-name">Last Name:</label>
        <input type="text" id="edit-last-name" name="last_name" required class="form-control"><br>
        <label for="edit-email">Email:</label>
        <input type="email" id="edit-email" name="email" required class="form-control"><br>
        <label for="edit-role">Role:</label>
        <select id="edit-role" name="role" required class="form-control">
            <option value="resume_user">Resume User</option>
            <option value="administrator">Administrator</option>
        </select><br>
        <label for="edit-linkedin">LinkedIn URL:</label>
        <input type="url" id="edit-linkedin" name="linkedin_url" class="form-control"><br>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<!-- Add User Dialog -->
<div id="add-user-dialog" style="display:none;">
    <h3>Add User</h3>
    <form id="add-user-form" class="edit-user-form">
        <?php wp_nonce_field('resume_ai_job_nonce', 'resume_ai_job_nonce'); ?>
        <label for="add-first-name">First Name:</label>
        <input type="text" id="add-first-name" name="first_name" required class="form-control"><br>
        <label for="add-last-name">Last Name:</label>
        <input type="text" id="add-last-name" name="last_name" required class="form-control"><br>
        <label for="add-email">Email:</label>
        <input type="email" id="add-email" name="email" required class="form-control"><br>
        <label for="add-password">Password:</label>
        <input type="password" id="add-password" name="password" required class="form-control"><br>
        <label for="add-role">Role:</label>
        <select id="add-role" name="role" required class="form-control">
            <option value="resume_user">Resume User</option>
            <option value="administrator">Administrator</option>
        </select><br>
        <label for="add-linkedin">LinkedIn URL:</label>
        <input type="url" id="add-linkedin" name="linkedin_url" class="form-control"><br>
        <button type="submit" class="btn btn-primary">Add User</button>
    </form>
</div>

<style>
    .edit-user-form {
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-control {
        width: 100%;
        padding: 8px;
        margin: 5px 0;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .user-management-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Edit User Dialog
    $('.edit-user').click(function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        // Fetch user data and populate the dialog
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_data',
                user_id: userId,
                nonce: $('#resume_ai_job_nonce').val()
            },
            success: function(response) {
                $('#edit-user-id').val(response.user_id);
                $('#edit-first-name').val(response.first_name);
                $('#edit-last-name').val(response.last_name);
                $('#edit-email').val(response.email);
                $('#edit-role').val(response.role);
                $('#edit-linkedin').val(response.linkedin_url);
                $('#edit-resume').val(response.resume_url);
                $('#edit-user-dialog').dialog('open');
            }
        });
    });

    // Delete User Confirmation
    $('.delete-user').click(function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        if (confirm('Are you sure you want to delete this user?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_user',
                    user_id: userId,
                    nonce: $('#resume_ai_job_nonce').val()
                },
                success: function(response) {
                    alert('User deleted successfully!');
                    location.reload();
                }
            });
        }
    });

    // Initialize Dialog
    $('#edit-user-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        buttons: {
            'Cancel': function() {
                $(this).dialog('close');
            }
        }
    });

    // Handle Edit Form Submission
    $('#edit-user-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_user',
                user_id: $('#edit-user-id').val(),
                first_name: $('#edit-first-name').val(),
                last_name: $('#edit-last-name').val(),
                email: $('#edit-email').val(),
                role: $('#edit-role').val(),
                linkedin_url: $('#edit-linkedin').val(),
                nonce: $('#resume_ai_job_nonce').val()
            },
            success: function(response) {
                alert('User updated successfully!');
                $('#edit-user-dialog').dialog('close');
                location.reload();
            }
        });
    });

    // Add User Dialog
    $('#add-user-button').click(function() {
        $('#add-user-dialog').dialog('open');
    });

    // Initialize Add User Dialog
    $('#add-user-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        buttons: {
            'Cancel': function() {
                $(this).dialog('close');
            }
        }
    });

    // Handle Add User Form Submission
    $('#add-user-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'add_user',
                first_name: $('#add-first-name').val(),
                last_name: $('#add-last-name').val(),
                email: $('#add-email').val(),
                password: $('#add-password').val(),
                role: $('#add-role').val(),
                linkedin_url: $('#add-linkedin').val(),
                nonce: $('#resume_ai_job_nonce').val()
            },
            success: function(response) {
                alert('User added successfully!');
                $('#add-user-dialog').dialog('close');
                location.reload();
            }
        });
    });
});
</script> 