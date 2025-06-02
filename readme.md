# Resume AI Job - WordPress Plugin

A WordPress plugin that adds AI-powered resume management features to your website.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Composer

## Installation

1. Download the plugin files and upload them to your WordPress plugins directory (`wp-content/plugins/resume-ai-job`), or install the plugin through the WordPress plugins screen directly.

2. Install the required dependencies using Composer:
   ```bash
   cd wp-content/plugins/resume-ai-job
   composer install
   ```

3. Activate the plugin through the 'Plugins' screen in WordPress.

## Features

- AI-powered resume management
- PDF and Word document parsing
- Template-based resume generation
- Caching system for improved performance

## Shortcodes and Functions

### Available Shortcodes

1. `[resume_upload_form]`
   - Displays the resume upload form
   - Requires user to be logged in with 'resume_user' role
   - Supports PDF and DOCX file uploads

2. `[resume_versions]`
   - Displays the user's resume versions
   - Shows ATS-optimized and human-friendly versions
   - Allows users to manage their resume versions

3. `[resume_ai_job_listings]`
   - Displays available job listings
   - Includes filtering options for job search
   - Requires user to be logged in

4. `[resume_ai_login]`
   - Displays the login form
   - Handles user authentication

5. `[resume_ai_register]`
   - Displays the registration form
   - Creates new user accounts with 'resume_user' role

### Page Setup

1. Create a page for resume uploads and add the shortcode:
   ```
   [resume_upload_form]
   ```

2. Create a page for viewing resume versions and add the shortcode:
   ```
   [resume_versions]
   ```

3. Create a page for job listings and add the shortcode:
   ```
   [resume_ai_job_listings]
   ```

4. Create pages and add the shortcodes for login and registration:
   ```
   [resume_ai_login]
   [resume_ai_register]
   ```

### User Roles

The plugin creates a custom role 'resume_user' with the following capabilities:
- Upload files
- Manage their own resumes
- View job listings
- Apply for positions

## Configuration

1. After activation, the plugin will automatically create necessary directories:
   - Template directory for HTML templates
   - Cache directory in the WordPress uploads folder

2. Access the plugin settings through the WordPress admin panel to configure:
   - Main settings
   - Template options
   - Cache settings
   - Admin Panel Setup:
     * Resume Upload Page
     * Resume Versions Page
     * Login Page
     * Register Page

## Usage

1. The plugin adds new features to your WordPress site for managing resumes.
2. Use the WordPress admin panel to access and configure the plugin settings.
3. Templates can be customized in the `templates/html` directory.

## Dependencies

- smalot/pdfparser: ^2.5
- phpoffice/phpword: ^1.1
- tecnickcom/tcpdf: ^6.6
- twig/twig: ^3.11 