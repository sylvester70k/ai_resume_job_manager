<?php
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts and localize them
wp_enqueue_script('jquery');
wp_localize_script('jquery', 'resume_ai_job', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('resume_ai_job_nonce'),
    'is_user_logged_in' => is_user_logged_in(),
    'login_url' => get_permalink(get_option('resume_ai_job_login_page'))
));
?>

<div class="!max-w-full mx-auto px-3 sm:px-4 lg:px-6 py-4">
    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form id="job-filters-form" class="flex flex-col lg:flex-row gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="job-title" class="block text-xs font-medium text-gray-700 mb-1">Job Title</label>
                <input type="text" id="job-title" name="title" placeholder="Search by job title"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="location" class="block text-xs font-medium text-gray-700 mb-1">Location</label>
                <input type="text" id="location" name="location" placeholder="Search by location"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Salary Range</label>
                <div class="flex items-center gap-2">
                    <input type="number" id="salary-from" name="salary_from" placeholder="From"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <span class="text-gray-500 text-sm">to</span>
                    <input type="number" id="salary-to" name="salary_to" placeholder="To"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-1.5 text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 transition-colors whitespace-nowrap">
                Search Jobs
            </button>
        </form>
    </div>

    <!-- Job Listings Section -->
    <div class="space-y-3">
        <div id="job-listings-container" class="flex flex-col gap-3">
            <!-- Job listings will be loaded here dynamically -->
        </div>
    </div>

    <!-- Application Modal -->
    <div id="application-modal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-16 mx-auto p-4 border w-full shadow-lg rounded-md bg-white max-w-3xl flex flex-col items-center h-[90vh]"
            style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold text-gray-800">Apply for Position</h2>
                <span class="close text-gray-500 hover:text-gray-700 cursor-pointer text-xl">&times;</span>
            </div>
            <form id="application-form" class="space-y-3 flex-1 overflow-y-auto">
                <input type="hidden" id="position-id" name="position_id">

                <div class="space-y-1">
                    <label for="cover-letter" class="block text-xs font-medium text-gray-700">Cover Letter</label>
                    <?php 
                    wp_editor('', 'cover-letter', array(
                        'media_buttons' => false,
                        'textarea_name' => 'cover_letter',
                        'editor_height' => 250,
                        'teeny' => true,
                        'quicktags' => false,
                        'tinymce' => array(
                            'height' => 250,
                            'menubar' => false,
                            'plugins' => 'lists link',
                            'toolbar1' => 'formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link | removeformat',
                            'content_style' => 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
                        ),
                    )); 
                    ?>
                </div>

                <div class="space-y-1">
                    <label for="resume-select" class="block text-xs font-medium text-gray-700">Select Resume</label>
                    <div class="flex flex-col items-center gap-3">
                        <select id="resume-select" name="resume_id" required
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <!-- Resumes will be loaded here dynamically -->
                        </select>
                        <div id="resume-preview" class="hidden">
                            <img src="" alt="Resume Preview" class="w-full h-auto object-cover rounded">
                        </div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white px-4 py-1.5 text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 transition-colors">
                    Submit Application
                </button>
            </form>
        </div>
    </div>
</div>

<!--[CDATA[-->
<script>
    console.log('Script loaded'); // Test if script is loading

    // Define functions in global scope
    function openApplicationModal(positionId) {
        console.log('Opening modal for position:', positionId);
        
        // Check if user is logged in
        if (!resume_ai_job.is_user_logged_in) {
            // Redirect to login page
            window.location.href = resume_ai_job.login_url;
            return;
        }
        
        jQuery('#position-id').val(positionId);
        loadUserResumes();
        jQuery('#application-modal').show();
    }

    function closeApplicationModal() {
        jQuery('#application-modal').hide();
    }

    function loadUserResumes() {
        jQuery.ajax({
            url: resume_ai_job.ajax_url,
            type: 'POST',
            data: {
                action: 'get_user_resumes',
                nonce: resume_ai_job.nonce
            },
            success: function (response) {
                console.log('Resumes response:', response);
                const select = jQuery('#resume-select');
                select.empty();

                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(function (resume) {
                        select.append(`<option value="${resume.published_resume_id}" data-preview="${resume.preview_url || ''}">${resume.title}</option>`);
                    });
                    // Show preview for first resume
                    updateResumePreview(select.val());
                } else {
                    select.append('<option value="">No resumes available</option>');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading resumes:', error);
                const select = jQuery('#resume-select');
                select.empty();
                select.append('<option value="">Error loading resumes</option>');
            }
        });
    }

    function updateResumePreview(resumeId) {
        const preview = jQuery('#resume-preview');
        const selectedOption = jQuery('#resume-select option:selected');
        const previewUrl = selectedOption.data('preview');

        if (previewUrl) {
            preview.find('img').attr('src', previewUrl);
            preview.removeClass('hidden');
        } else {
            preview.addClass('hidden');
        }
    }

    jQuery(document).ready(function ($) {
        console.log('Document ready - Initializing job listings page');

        // Load initial job listings
        loadJobListings();

        // Handle filter form submission
        $('#job-filters-form').on('submit', function (e) {
            e.preventDefault();
            console.log('Filter form submitted');
            loadJobListings();
        });

        // Handle application form submission - using both submit and click events
        const $applicationForm = $('#application-form');
        const $submitButton = $applicationForm.find('button[type="submit"]');

        console.log('Form found:', $applicationForm.length > 0);
        console.log('Submit button found:', $submitButton.length > 0);

        $applicationForm.on('submit', function (e) {
            e.preventDefault();
            console.log('Form submit event triggered');
            submitApplication();
        });

        $submitButton.on('click', function (e) {
            e.preventDefault();
            console.log('Submit button clicked');
            submitApplication();
        });

        // Close modal when clicking the close button
        $('.close').on('click', function () {
            closeApplicationModal();
        });

        // Close modal when clicking outside
        $(window).on('click', function (e) {
            if ($(e.target).is('#application-modal')) {
                closeApplicationModal();
            }
        });

        // Add event listener for resume selection change
        $('#resume-select').on('change', function () {
            updateResumePreview($(this).val());
        });

        function loadJobListings() {
            const filters = {
                title: $('#job-title').val(),
                location: $('#location').val(),
                salary_from: $('#salary-from').val(),
                salary_to: $('#salary-to').val()
            };

            console.log('Loading job listings with filters:', filters);
            console.log('AJAX URL:', resume_ai_job.ajax_url);
            console.log('Nonce:', resume_ai_job.nonce);

            $.ajax({
                url: resume_ai_job.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_positions',
                    filters: filters,
                    nonce: resume_ai_job.nonce
                },
                beforeSend: function() {
                    console.log('Sending AJAX request...');
                    $('#job-listings-container').html('<p class="text-center text-gray-500 py-4 text-sm">Loading jobs...</p>');
                },
                success: function (response) {
                    console.log('AJAX Response:', response);
                    if (response.success) {
                        console.log('Jobs data:', response.data);
                        displayJobListings(response.data);
                    } else {
                        console.error('Error in response:', response);
                        $('#job-listings-container').html('<p class="text-center text-red-500 py-4 text-sm">Error loading job listings. Please try again.</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    $('#job-listings-container').html('<p class="text-center text-red-500 py-4 text-sm">Error loading job listings. Please try again.</p>');
                }
            });
        }

        function displayJobListings(jobs) {
            const container = $('#job-listings-container');
            container.empty();

            if (!Array.isArray(jobs)) {
                console.error('Invalid jobs data:', jobs);
                container.html('<p class="text-center text-red-500 py-4 text-sm">Error: Invalid data received</p>');
                return;
            }

            if (jobs.length === 0) {
                container.html('<p class="text-center text-gray-500 py-4 text-sm">No jobs found matching your criteria.</p>');
                return;
            }

            jobs.forEach(function (job) {
                if (!job || typeof job !== 'object') {
                    console.error('Invalid job data:', job);
                    return;
                }

                const statusHtml = job.application_status && resume_ai_job.is_user_logged_in
                    ? `<div class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-medium ${getStatusColor(job.application_status)}">
                            ${getStatusText(job.application_status)}
                        </span>
                       </div>`
                    : `<button class="apply-button ${job.application_status ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'} text-white px-4 py-1.5 text-sm rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 transition-colors"
                        data-position-id="${job.id}"
                        ${job.application_status ? 'disabled' : ''}>
                        ${job.application_status ? 'Already Applied' : 'Apply Now'}
                       </button>`;

                const descriptionHtml = job.description.length > 200 
                    ? `<div class="description-preview">${truncateText(job.description, 200)}</div>
                       <div class="description-full hidden mt-2">${job.description}</div>
                       <button class="text-blue-600 text-xs font-medium hover:text-blue-700 mt-1 toggle-description">Show More</button>`
                    : `<div class="description-preview">${job.description}</div>`;

                const jobCard = `
                <div class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <h3 class="text-base font-semibold text-blue-600 mb-2 flex items-center w-full justify-between">
                        <span>${job.title}</span>
                        ${statusHtml}
                    </h3>
                    <div class="flex flex-wrap gap-3 text-xs text-gray-600 mb-2">
                        <span class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            ${job.location}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ${formatSalary(job)}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Posted ${formatDate(job.created_at)}
                        </span>
                    </div>
                    <div class="text-sm text-gray-700 mb-3">
                        ${descriptionHtml}
                    </div>
                </div>`;
                container.append(jobCard);
            });

            // Add click handler for apply buttons
            $('.apply-button').on('click', function () {
                const positionId = $(this).data('position-id');
                if (!$(this).prop('disabled')) {
                    openApplicationModal(positionId);
                }
            });

            // Add click handler for description toggle
            $('.toggle-description').on('click', function () {
                const $button = $(this);
                const $preview = $button.siblings('.description-preview');
                const $full = $button.siblings('.description-full');

                if ($full.hasClass('hidden')) {
                    $preview.addClass('hidden');
                    $full.removeClass('hidden');
                    $button.text('Show Less');
                } else {
                    $preview.removeClass('hidden');
                    $full.addClass('hidden');
                    $button.text('Show More');
                }
            });
        }

        function truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        function getStatusColor(status) {
            switch (status) {
                case 'pending':
                    return 'text-yellow-600';
                case 'review':
                    return 'text-blue-600';
                case 'approved':
                    return 'text-green-600';
                case 'rejected':
                    return 'text-red-600';
                default:
                    return 'text-gray-600';
            }
        }

        function getStatusText(status) {
            switch (status) {
                case 'pending':
                    return 'Application Pending';
                case 'review':
                    return 'Under Review';
                case 'approved':
                    return 'Application Approved';
                case 'rejected':
                    return 'Application Rejected';
                default:
                    return 'Status Unknown';
            }
        }

        function formatSalary(job) {
            const currency = job.salary_currency || 'USD';
            
            // Check if salary_from exists
            if (job.salary_from !== null) {
                if (job.salary_from !== undefined) {
                    // Check if salary_to exists
                    if (job.salary_to !== null) {
                        if (job.salary_to !== undefined) {
                            return `${currency} ${job.salary_from.toLocaleString()} - ${job.salary_to.toLocaleString()}`;
                        }
                    }
                    return `${currency} ${job.salary_from.toLocaleString()}+`;
                }
            }
            
            // Check if salary_to exists
            if (job.salary_to !== null) {
                if (job.salary_to !== undefined) {
                    return `Up to ${currency} ${job.salary_to.toLocaleString()}`;
                }
            }

            return 'Salary not specified';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }

        function submitApplication() {
            console.log('submitApplication function called');
            const formData = {
                action: 'apply_position',
                position_id: $('#position-id').val(),
                cover_letter: tinyMCE.get('cover-letter').getContent(),
                resume_id: $('#resume-select').val(),
                nonce: resume_ai_job.nonce
            };

            console.log('Form data:', formData);
            if (!formData.position_id || !formData.cover_letter || !formData.resume_id) {
                alert('Please fill in all required fields');
                return;
            }

            $.ajax({
                url: resume_ai_job.ajax_url,
                type: 'POST',
                data: formData,
                beforeSend: function () {
                    console.log('Sending AJAX request...');
                },
                success: function (response) {
                    console.log('Application response:', response);
                    if (response.success) {
                        alert('Application submitted successfully!');
                        closeApplicationModal();
                        $('#application-form')[0].reset();
                        document.location.reload();
                    } else {
                        alert(response.data?.message || 'Error submitting application. Please try again.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Application submission error:', error);
                    alert('Error submitting application. Please try again.');
                }
            });
        }
    });
</script>
<!--]]-->