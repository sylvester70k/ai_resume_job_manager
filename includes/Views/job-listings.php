<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="resume-ai-job-container">
    <!-- Filters Section -->
    <div class="job-filters">
        <form id="job-filters-form" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="job-title">Job Title</label>
                    <input type="text" id="job-title" name="title" placeholder="Search by job title">
                </div>
                <div class="filter-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" placeholder="Search by location">
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <label for="salary-from">Salary Range</label>
                    <div class="salary-range">
                        <input type="number" id="salary-from" name="salary_from" placeholder="From">
                        <span>to</span>
                        <input type="number" id="salary-to" name="salary_to" placeholder="To">
                    </div>
                </div>
                <div class="filter-group">
                    <button type="submit" class="search-button">Search Jobs</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Job Listings Section -->
    <div class="job-listings">
        <div id="job-listings-container">
            <!-- Job listings will be loaded here dynamically -->
        </div>
    </div>

    <!-- Application Modal -->
    <div id="application-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Apply for Position</h2>
            <form id="application-form">
                <input type="hidden" id="position-id" name="position_id">
                
                <div class="form-group">
                    <label for="cover-letter">Cover Letter</label>
                    <textarea id="cover-letter" name="cover_letter" rows="6" required></textarea>
                </div>

                <div class="form-group">
                    <label for="resume-select">Select Resume</label>
                    <select id="resume-select" name="resume_id" required>
                        <!-- Resumes will be loaded here dynamically -->
                    </select>
                </div>

                <button type="submit" class="submit-button">Submit Application</button>
            </form>
        </div>
    </div>
</div>

<style>
.resume-ai-job-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.job-filters {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.filter-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.salary-range {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.job-listings {
    display: grid;
    gap: 20px;
}

.job-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.job-card h3 {
    margin: 0 0 10px 0;
    color: #0073aa;
}

.job-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    color: #666;
}

.apply-button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 20px;
    width: 80%;
    max-width: 600px;
    border-radius: 8px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group textarea,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.submit-button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
}
</style>

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
            container.html('<p>No jobs found matching your criteria.</p>');
            return;
        }

        jobs.forEach(function(job) {
            const jobCard = `
                <div class="job-card">
                    <h3>${job.title}</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> ${job.location}</span>
                        <span><i class="fas fa-money-bill-wave"></i> ${formatSalary(job)}</span>
                        <span><i class="fas fa-clock"></i> Posted ${formatDate(job.created_at)}</span>
                    </div>
                    <p>${job.description}</p>
                    <button class="apply-button" onclick="openApplicationModal(${job.id})">Apply Now</button>
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