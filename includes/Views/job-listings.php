<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <form id="job-filters-form" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label for="job-title" class="block text-sm font-medium text-gray-700">Job Title</label>
                    <input type="text" id="job-title" name="title" placeholder="Search by job title" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" id="location" name="location" placeholder="Search by location"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Salary Range</label>
                    <div class="flex items-center space-x-4">
                        <input type="number" id="salary-from" name="salary_from" placeholder="From"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-gray-500">to</span>
                        <input type="number" id="salary-to" name="salary_to" placeholder="To"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Search Jobs
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Job Listings Section -->
    <div class="space-y-6">
        <div id="job-listings-container">
            <!-- Job listings will be loaded here dynamically -->
        </div>
    </div>

    <!-- Application Modal -->
    <div id="application-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Apply for Position</h2>
                <span class="close text-gray-500 hover:text-gray-700 cursor-pointer text-2xl">&times;</span>
            </div>
            <form id="application-form" class="space-y-4">
                <input type="hidden" id="position-id" name="position_id">
                
                <div class="space-y-2">
                    <label for="cover-letter" class="block text-sm font-medium text-gray-700">Cover Letter</label>
                    <textarea id="cover-letter" name="cover_letter" rows="6" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="space-y-2">
                    <label for="resume-select" class="block text-sm font-medium text-gray-700">Select Resume</label>
                    <select id="resume-select" name="resume_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <!-- Resumes will be loaded here dynamically -->
                    </select>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Submit Application
                </button>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load initial job listings
    loadJobListings();

    // Handle filter form submission
    $('#job-filters-form').on('submit', function(e) {
        e.preventDefault();
        loadJobListings();
    });

    // Handle application form submission
    $('#application-form').on('submit', function(e) {
        e.preventDefault();
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

    function loadJobListings() {
        const filters = {
            title: $('#job-title').val(),
            location: $('#location').val(),
            salary_from: $('#salary-from').val(),
            salary_to: $('#salary-to').val()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_positions',
                filters: filters
            },
            success: function(response) {
                displayJobListings(response);
            }
        });
    }

    function displayJobListings(jobs) {
        const container = $('#job-listings-container');
        container.empty();

        if (jobs.length === 0) {
            container.html('<p class="text-center text-gray-500 py-8">No jobs found matching your criteria.</p>');
            return;
        }

        jobs.forEach(function(job) {
            const jobCard = `
                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <h3 class="text-xl font-semibold text-blue-600 mb-3">${job.title}</h3>
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            ${job.location}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ${formatSalary(job)}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Posted ${formatDate(job.created_at)}
                        </span>
                    </div>
                    <p class="text-gray-700 mb-4">${job.description}</p>
                    <button onclick="openApplicationModal(${job.id})" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Apply Now
                    </button>
                </div>
            `;
            container.append(jobCard);
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

    function openApplicationModal(positionId) {
        $('#position-id').val(positionId);
        loadUserResumes();
        $('#application-modal').show();
    }

    function loadUserResumes() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_resumes'
            },
            success: function(response) {
                const select = $('#resume-select');
                select.empty();
                response.forEach(function(resume) {
                    select.append(`<option value="${resume.id}">${resume.title}</option>`);
                });
            }
        });
    }

    function submitApplication() {
        const formData = {
            action: 'apply_position',
            position_id: $('#position-id').val(),
            cover_letter: $('#cover-letter').val(),
            resume_id: $('#resume-select').val()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Application submitted successfully!');
                    $('#application-modal').hide();
                    $('#application-form')[0].reset();
                } else {
                    alert(response.message || 'Error submitting application');
                }
            }
        });
    }
});
</script> 