<?php
/*
Plugin Name: Poultry Farm Manager
Description: Manage your poultry projects, daily logs and reports.
Version: 1.0
Author: Shakil Hossain
*/

if (!defined('ABSPATH')) exit;



ob_start(function ($buffer) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    error_log("OUTPUT DETECTED: " . substr($buffer, 0, 100));
    error_log(print_r($trace, true));
    return $buffer;
});



// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-poultry-installer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-poultry-crud.php';

// Install DB tables on activation
register_activation_hook(__FILE__, ['Poultry_Installer', 'install']);

// Enqueue CSS
add_action('admin_enqueue_scripts', function ($hook) {
    // Enqueue CSS for all plugin admin pages (any page under your plugin)
    if (strpos($hook, 'poultry') !== false) {

        wp_enqueue_style('pfm-admin-style', plugin_dir_url(__FILE__) . 'assets/admin-style.css');
    }
});

require_once plugin_dir_path(__FILE__) . 'includes/fetch-logs.php';

// Admin Menu
add_action('admin_menu', function () {
    add_menu_page('Poultry Farm', 'Poultry Farm', 'manage_options', 'poultry-farm', 'pfm_projects_page', 'dashicons-chart-pie', 1);
    // add_submenu_page('poultry-farm', 'Projects', 'Projects', 'manage_options', 'poultry-projects', 'pfm_projects_page');
    add_submenu_page('poultry-farm', 'Daily Logs', 'Daily Logs', 'manage_options', 'poultry-logs', 'pfm_logs_page');
    add_submenu_page('poultry-farm', 'Reports', 'Reports', 'manage_options', 'poultry-reports', 'pfm_reports_page');
});

//thousand separators
require_once plugin_dir_path(__FILE__) . 'includes/thousand-separators.php';

// project detele handler
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';

// Page Callbacks
function pfm_projects_page()
{
    include plugin_dir_path(__FILE__) . 'includes/admin-projects.php';
}
function pfm_logs_page()
{
    include plugin_dir_path(__FILE__) . 'includes/admin-logs.php';
}
function pfm_reports_page()
{
    include plugin_dir_path(__FILE__) . 'includes/admin-reports.php';
}
