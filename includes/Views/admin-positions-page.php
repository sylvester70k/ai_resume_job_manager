<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get all positions
global $wpdb;
$positions_table = $wpdb->prefix . 'resume_ai_job_positions';
$positions = $wpdb->get_results("SELECT * FROM $positions_table ORDER BY created_at DESC");

// Get all applications
$applications_table = $wpdb->prefix . 'resume_ai_job_applications';
$applications = $wpdb->get_results("SELECT a.*, p.title as position_title, u.display_name as applicant_name 
    FROM $applications_table a 
    JOIN $positions_table p ON a.position_id = p.id 
    JOIN {$wpdb->users} u ON a.user_id = u.ID 
    ORDER BY a.created_at DESC");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Job Positions</h1>
    <a href="#" class="page-title-action" id="add-position-btn">Add New Position</a>
    
    <!-- Positions List -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Active Positions</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Salary Range</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positions as $position): ?>
                <tr>
                    <td><?php echo esc_html($position->title); ?></td>
                    <td><?php echo esc_html($position->location); ?></td>
                    <td>
                        <?php 
                        if ($position->salary_from && $position->salary_to) {
                            echo esc_html($position->salary_currency . ' ' . $position->salary_from . ' - ' . $position->salary_to);
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html($position->deadline); ?></td>
                    <td><?php echo esc_html($position->status); ?></td>
                    <td>
                        <a href="#" class="edit-position" data-id="<?php echo esc_attr($position->id); ?>">Edit</a> |
                        <a href="#" class="delete-position" data-id="<?php echo esc_attr($position->id); ?>">Delete</a> |
                        <a href="#" class="view-applications" data-id="<?php echo esc_attr($position->id); ?>">View Applications</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Applications List -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Recent Applications</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Applicant</th>
                    <th>Status</th>
                    <th>Applied Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                <tr>
                    <td><?php echo esc_html($application->position_title); ?></td>
                    <td><?php echo esc_html($application->applicant_name); ?></td>
                    <td><?php echo esc_html($application->status); ?></td>
                    <td><?php echo esc_html($application->created_at); ?></td>
                    <td>
                        <a href="#" class="view-application" data-id="<?php echo esc_attr($application->id); ?>">View Details</a> |
                        <a href="#" class="update-status" data-id="<?php echo esc_attr($application->id); ?>">Update Status</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Position Modal -->
<div id="position-modal" class="hidden">
    <div class="wp-dialog-overlay"></div>
    <div class="wp-dialog" style="width: 600px; max-width: 90%;">
        <div class="wp-dialog-header">
            <h3 id="modal-title">Add New Position</h3>
            <button type="button" class="wp-dialog-close" id="cancel-position">×</button>
        </div>
        <div class="wp-dialog-content">
            <form id="position-form">
                <input type="hidden" id="position-id" name="position_id" value="">
                
                <div class="form-field">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="regular-text" required>
                </div>

                <div class="form-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="large-text" rows="4"></textarea>
                </div>

                <div class="form-field">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="regular-text">
                </div>

                <div class="form-field">
                    <label>Salary Range</label>
                    <div class="form-row">
                        <input type="number" id="salary_from" name="salary_from" class="small-text" placeholder="From">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <input type="number" id="salary_to" name="salary_to" class="small-text" placeholder="To">
                        <select id="salary_currency" name="salary_currency" class="small-text">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label for="deadline">Application Deadline</label>
                    <input type="datetime-local" id="deadline" name="deadline" class="regular-text">
                </div>

                <div class="form-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="regular-text">
                        <option value="active">Active</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="wp-dialog-footer">
            <button type="button" class="button button-secondary" id="cancel-position">Cancel</button>
            <button type="button" class="button button-primary" id="save-position">Save Position</button>
        </div>
    </div>
</div>

<!-- Application Details Modal -->
<div id="application-modal" class="hidden">
    <div class="wp-dialog-overlay"></div>
    <div class="wp-dialog" style="width: 600px; max-width: 90%;">
        <div class="wp-dialog-header">
            <h3>Application Details</h3>
            <button type="button" class="wp-dialog-close" id="close-application">×</button>
        </div>
        <div class="wp-dialog-content">
            <div id="application-details">
                <!-- Application details will be loaded here -->
            </div>
            <div class="form-field">
                <label for="application-status">Update Status</label>
                <select id="application-status" class="regular-text">
                    <option value="pending">Pending</option>
                    <option value="review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
        <div class="wp-dialog-footer">
            <button type="button" class="button button-secondary" id="close-application">Close</button>
            <button type="button" class="button button-primary" id="save-application-status">Update Status</button>
        </div>
    </div>
</div>

<!-- Applications List Modal -->
<div id="applications-modal" class="hidden">
    <div class="wp-dialog-overlay"></div>
    <div class="wp-dialog" style="width: 800px; max-width: 90%;">
        <div class="wp-dialog-header">
            <h3>Position Applications</h3>
            <button type="button" class="wp-dialog-close" id="close-applications">×</button>
        </div>
        <div class="wp-dialog-content">
            <div id="applications-list">
                <!-- Applications will be loaded here -->
            </div>
        </div>
        <div class="wp-dialog-footer">
            <button type="button" class="button button-secondary" id="close-applications">Close</button>
        </div>
    </div>
</div>

<style>
.wp-dialog-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 159900;
}

.wp-dialog {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 3px;
    box-shadow: 0 5px 15px rgba(0,0,0,.3);
    z-index: 160000;
}

.wp-dialog-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    position: relative;
}

.wp-dialog-header h3 {
    margin: 0;
    padding: 0;
    font-size: 1.3em;
    font-weight: 600;
}

.wp-dialog-close {
    position: absolute;
    top: 15px;
    right: 20px;
    border: none;
    background: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
}

.wp-dialog-content {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.wp-dialog-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-row .dashicons {
    color: #666;
}

#application-details {
    margin-bottom: 20px;
}

#application-details h4 {
    margin: 0 0 5px 0;
    color: #1d2327;
}

#application-details p {
    margin: 0 0 15px 0;
}

#application-details a {
    color: #2271b1;
    text-decoration: none;
}

#application-details a:hover {
    color: #135e96;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add Position
    $('#add-position-btn').click(function(e) {
        e.preventDefault();
        $('#modal-title').text('Add New Position');
        $('#position-form')[0].reset();
        $('#position-id').val('');
        $('#position-modal').removeClass('hidden');
    });

    // Edit Position
    $('.edit-position').click(function(e) {
        e.preventDefault();
        const positionId = $(this).data('id');
        $('#modal-title').text('Edit Position');
        
        // Load position data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_position_data',
                position_id: positionId,
                nonce: resume_ai_job_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const position = response.data;
                    $('#position-id').val(position.id);
                    $('#title').val(position.title);
                    $('#description').val(position.description);
                    $('#location').val(position.location);
                    $('#salary_from').val(position.salary_from);
                    $('#salary_to').val(position.salary_to);
                    $('#salary_currency').val(position.salary_currency);
                    $('#deadline').val(position.deadline);
                    $('#status').val(position.status);
                    $('#position-modal').removeClass('hidden');
                }
            }
        });
    });

    // Save Position
    $('#save-position').click(function() {
        const formData = {
            title: $('#title').val(),
            description: $('#description').val(),
            location: $('#location').val(),
            salary_from: $('#salary_from').val(),
            salary_to: $('#salary_to').val(),
            salary_currency: $('#salary_currency').val(),
            deadline: $('#deadline').val(),
            status: $('#status').val(),
            position_id: $('#position-id').val()
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_position',
                ...formData,
                nonce: resume_ai_job_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to save position');
                }
            },
            error: function() {
                alert('An error occurred while saving the position');
            }
        });
    });

    // Delete Position
    $('.delete-position').click(function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this position?')) {
            const positionId = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_position',
                    position_id: positionId,
                    nonce: resume_ai_job_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // View Application
    $('.view-application').click(function(e) {
        e.preventDefault();
        const applicationId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_application_data',
                application_id: applicationId,
                nonce: resume_ai_job_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const application = response.data;
                    let details = `
                        <div class="form-field">
                            <h4>Position</h4>
                            <p>${application.position_title}</p>
                        </div>
                        <div class="form-field">
                            <h4>Applicant</h4>
                            <p>${application.applicant_name}</p>
                        </div>
                        <div class="form-field">
                            <h4>Cover Letter</h4>
                            <p>${application.cover_letter}</p>
                        </div>
                        <div class="form-field">
                            <h4>Resume</h4>
                            ${application.resume_url ? 
                                `<a href="${application.resume_url}" target="_blank">View Resume</a>` :
                                `<span class="text-gray-500">Resume not available</span>`
                            }
                        </div>
                    `;
                    $('#application-details').html(details);
                    $('#application-details').data('application-id', applicationId);
                    $('#application-status').val(application.status);
                    $('#application-modal').removeClass('hidden');
                }
            }
        });
    });

    // Update Application Status
    $('#save-application-status').click(function() {
        const applicationId = $('#application-details').data('application-id');
        const status = $('#application-status').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_application_status',
                application_id: applicationId,
                status: status,
                nonce: resume_ai_job_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Close Modals
    $('#cancel-position, #close-application, .wp-dialog-close').click(function() {
        $('#position-modal, #application-modal').addClass('hidden');
    });

    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).is('.wp-dialog-overlay')) {
            $('#position-modal, #application-modal').addClass('hidden');
        }
    });

    // View Applications
    $('.view-applications').click(function(e) {
        e.preventDefault();
        const positionId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_position_applications',
                position_id: positionId,
                nonce: resume_ai_job_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const applications = response.data;
                    let html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr>';
                    html += '<th>Applicant</th>';
                    html += '<th>Applied Date</th>';
                    html += '<th>Status</th>';
                    html += '<th>Actions</th>';
                    html += '</tr></thead><tbody>';
                    
                    if (applications.length === 0) {
                        html += '<tr><td colspan="4">No applications found for this position.</td></tr>';
                    } else {
                        applications.forEach(function(app) {
                            html += '<tr>';
                            html += '<td>' + app.applicant_name + '</td>';
                            html += '<td>' + app.created_at + '</td>';
                            html += '<td>' + app.status + '</td>';
                            html += '<td>';
                            html += '<a href="#" class="view-application" data-id="' + app.id + '">View Details</a>';
                            html += '</td>';
                            html += '</tr>';
                        });
                    }
                    
                    html += '</tbody></table>';
                    $('#applications-list').html(html);
                    $('#applications-modal').removeClass('hidden');
                }
            }
        });
    });

    // Close Applications Modal
    $('#close-applications, .wp-dialog-close').click(function() {
        $('#applications-modal').addClass('hidden');
    });

    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).is('.wp-dialog-overlay')) {
            $('#applications-modal').addClass('hidden');
        }
    });
});
</script>
