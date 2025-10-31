<?php

if (!defined('ABSPATH')) exit;

// In your plugin main file or includes/ajax-handlers.php
add_action('wp_ajax_pfm_delete_project', function () {
    if (!isset($_POST['project_id'])) {
        wp_send_json_error('Project ID missing.');
    }

    $project_id = intval($_POST['project_id']);
    $crud = new Poultry_CRUD();

    $deleted = $crud->delete_project($project_id);

    if ($deleted) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to delete project.');
    }
});


add_action('wp_ajax_pfm_complete_project', function () {
    if (!isset($_POST['project_id'])) {
        wp_send_json_error('Project ID missing.');
    }

    $project_id = intval($_POST['project_id']);
    $crud = new Poultry_CRUD();

    $updated = $crud->complete_project($project_id);

    if ($updated) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to complete project.');
    }
});
