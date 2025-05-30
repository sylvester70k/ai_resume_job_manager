<?php
if (!defined('ABSPATH')) {
    exit;
}
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
            <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 transition-colors whitespace-nowrap">
                Search Jobs
            </button>
        </form>
    </div>

    <!-- Job Listings Section -->
    <div class="space-y-3">
        <div id="job-listings-container">
            <!-- Job listings will be loaded here dynamically -->
        </div>
    </div>

    <!-- Application Modal -->
    <div id="application-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-16 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white max-w-2xl">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold text-gray-800">Apply for Position</h2>
                <span class="close text-gray-500 hover:text-gray-700 cursor-pointer text-xl">&times;</span>
            </div>
            <form id="application-form" class="space-y-3">
                <input type="hidden" id="position-id" name="position_id">
                
                <div class="space-y-1">
                    <label for="cover-letter" class="block text-xs font-medium text-gray-700">Cover Letter</label>
                    <textarea id="cover-letter" name="cover_letter" rows="4" required
                              class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
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

                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-1.5 text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 transition-colors">
                    Submit Application
                </button>
            </form>
        </div>
    </div>
</div>

<script>
console.log('Script loaded'); // Test if script is loading

// Define functions in global scope
function openApplicationModal(positionId) {
    console.log('Opening modal for position:', positionId);
    jQuery('#position-id').val(positionId);
    loadUserResumes();
    jQuery('#application-modal').show();
}

function loadUserResumes() {
    jQuery.ajax({
        url: resume_ai_job.ajaxurl,
        type: 'POST',
        data: {
            action: 'get_user_resumes',
            nonce: resume_ai_job.nonce
        },
        success: function(response) {
            console.log('Resumes response:', response);
            const select = jQuery('#resume-select');
            select.empty();
            
            if (response.success && response.data && response.data.length > 0) {
                response.data.forEach(function(resume) {
                    select.append(`<option value="${resume.id}" data-preview="${resume.preview_url || ''}">${resume.title}</option>`);
                });
                // Show preview for first resume
                updateResumePreview(select.val());
            } else {
                select.append('<option value="">No resumes available</option>');
            }
        },
        error: function(xhr, status, error) {
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

jQuery(document).ready(function($) {
    console.log('Document ready'); // Test if jQuery ready is working

    // Load initial job listings
    loadJobListings();

    // Handle filter form submission
    $('#job-filters-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Filter form submitted');
        loadJobListings();
    });

    // Direct click handler for submit button
    $('#application-form button[type="submit"]').on('click', function(e) {
        e.preventDefault();
        console.log('Submit button clicked');
        submitApplication();
    });

    // Handle application form submission
    $('#application-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Application form submitted');
        submitApplication();
    });

    // Close modal when clicking the close button
    $('.close').on('click', function() {
        $('#application-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).is('#application-modal')) {
            $('#application-modal').hide();
        }
    });

    // Add event listener for resume selection change
    $('#resume-select').on('change', function() {
        updateResumePreview($(this).val());
    });

    function loadJobListings() {
        const filters = {
            title: $('#job-title').val(),
            location: $('#location').val(),
            salary_from: $('#salary-from').val(),
            salary_to: $('#salary-to').val()
        };

        console.log('Filters:', filters);

        $.ajax({
            url: resume_ai_job.ajax_url,
            type: 'POST',
            data: {
                action: 'get_positions',
                filters: filters,
                nonce: resume_ai_job.nonce
            },
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    displayJobListings(response.data);
                } else {
                    $('#job-listings-container').html('<p class="text-center text-red-500 py-4 text-sm">Error loading job listings. Please try again.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#job-listings-container').html('<p class="text-center text-red-500 py-4 text-sm">Error loading job listings. Please try again.</p>');
            }
        });
    }

    function displayJobListings(jobs) {
        const container = $('#job-listings-container');
        container.empty();

        if (jobs.length === 0) {
            container.html('<p class="text-center text-gray-500 py-4 text-sm">No jobs found matching your criteria.</p>');
            return;
        }

        jobs.forEach(function(job) {
            const jobCard = `
                <div class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <h3 class="text-base font-semibold text-blue-600 mb-2">${job.title}</h3>
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
                    <p class="text-sm text-gray-700 mb-3">${job.description}</p>
                    <button class="apply-button bg-blue-600 text-white px-4 py-1.5 text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 transition-colors" 
                            data-position-id="${job.id}">
                        Apply Now
                    </button>
                </div>
            `;
            container.append(jobCard);
        });

        // Add click handler for apply buttons
        $('.apply-button').on('click', function() {
            const positionId = $(this).data('position-id');
            openApplicationModal(positionId);
        });
    }

    function formatSalary(job) {
        if (!job.salary_from && !job.salary_to) return 'Salary not specified';
        const currency = job.salary_currency || 'USD';
        if (job.salary_from && job.salary_to) {
            return `${currency} ${job.salary_from} - ${job.salary_to}`;
        }
        return `${currency} ${job.salary_from || job.salary_to}`;
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
            cover_letter: $('#cover-letter').val(),
            resume_id: $('#resume-select').val(),
            nonce: resume_ai_job.nonce
        };

        console.log('Form data:', formData);
        if (!formData.position_id || !formData.cover_letter || !formData.resume_id) {
            alert('Please fill in all required fields');
            return;
        }

        $.ajax({
            url: resume_ai_job.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                console.log('Sending AJAX request...');
            },
            success: function(response) {
                console.log('Application response:', response);
                if (response.success) {
                    alert('Application submitted successfully!');
                    $('#application-modal').hide();
                    $('#application-form')[0].reset();
                } else {
                    alert(response.data?.message || 'Error submitting application. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Application submission error:', error);
                alert('Error submitting application. Please try again.');
            }
        });
    }
});
</script> 