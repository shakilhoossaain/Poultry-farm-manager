<?php
// Get logs by project (AJAX)
add_action('wp_ajax_pfm_get_logs', function () {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    global $wpdb;
    $crud = new Poultry_CRUD();
    $projects = $crud->get_all_projects();
    $project_id = intval($_POST['project_id']);
    $logs = $project_id ? $crud->get_all_logs($project_id) : $crud->get_all_logs();

    ob_start();
    if ($logs) {
        foreach ($logs as $log) {
            $datee = date('d F Y', strtotime($log->log_date));
            $proj_name = '';
            $proj_staus = '';
            foreach ($projects as $p) {
                if ($p->id == $log->project_id) {
                    $proj_name = $p->project_name;
                    $proj_staus = $p->status;
                    break;
                }
            }

            $log->project_name = $proj_name;
            $log->project_status = $proj_staus;


            $customers_array = json_decode($log->customers, true);
            $customers_str = '';
            if (is_array($customers_array) && !empty($customers_array)) {
                $customers_str = implode('/', $customers_array);
            }

            $log->customers = $customers_str;

            $log_data = htmlspecialchars(wp_json_encode($log), ENT_QUOTES, 'UTF-8');

            $editbutton = ($proj_staus ?? '') !== 'completed' ? "<button class='button edit-log'>Edit</button>" : '';

            echo "<tr data-log='{$log_data}'>
            <td>{$datee}</td>
            <td>{$log->medication}</td>
            <td>{$log->eggs_produced}/{$log->eggs_sold}</td>
            <td>{$log->feed_cost}</td>
            <td>{$log->chickens_died}</td>
            <td class='pfm-actions'>
                <button class='button view-log' data-id='{$log->id}'>View</button>
                {$editbutton}
                <button class='button delete-log' data-id='{$log->id}'>Delete</button>
            </td>
        </tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No logs found.</td></tr>";
    }
    $html = ob_get_clean();
    echo $html;
    wp_die();
});

// Delete log (AJAX)
add_action('wp_ajax_pfm_delete_log', function () {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    $log_id = intval($_POST['log_id']);
    $crud = new Poultry_CRUD();
    $deleted = $crud->delete_daily_log($log_id);
    if ($deleted) wp_send_json_success();
    wp_send_json_error();
});
