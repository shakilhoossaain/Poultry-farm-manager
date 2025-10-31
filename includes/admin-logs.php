<?php
if (!defined('ABSPATH')) exit;

$crud = new Poultry_CRUD();
$projects = $crud->get_all_projects();
$all_customers = $crud->get_all_customers_global();
$all_customers_json = json_encode($all_customers);

// Add or Update Log
if (isset($_POST['pfm_save_log'])) {

    $customers_raw = isset($_POST['customers']) ? sanitize_text_field($_POST['customers']) : '';
    $customers_clean = trim($customers_raw, '/');
    $customers_clean = preg_replace('#/+#', '/', $customers_clean);
    $customers_array = array_map('strtolower', array_filter(explode('/', $customers_clean), fn($c) => !empty($c)));
    $customers_json = wp_json_encode($customers_array);

    $data = [
        'project_id'           => intval($_POST['project_id']),
        'log_date'             => $_POST['log_date'],
        'feed_bags'            => floatval($_POST['feed_bags']),
        'feed_cost'            => floatval($_POST['feed_cost']),
        'eggs_produced'        => intval($_POST['eggs_produced']),
        'eggs_sold'            => intval($_POST['eggs_sold']),
        'egg_price_per_unit'   => floatval($_POST['egg_price_per_unit']),
        'chickens_died'        => intval($_POST['chickens_died']),
        // 'chickens_sold'        => intval($_POST['chickens_sold']),
        // 'chickens_sale_price'  => floatval($_POST['chickens_sale_price']),
        'medication'           => floatval($_POST['medication']),
        'notes'                => sanitize_textarea_field($_POST['notes']),
        'customers'            => $customers_json
    ];

    if (!empty($_POST['log_id'])) {
        $crud->edit_daily_log(intval($_POST['log_id']), $data);
        $msg = 'updated';
        // echo '<div class="updated"><p>Daily log updated.</p></div>';
    } else {
        $crud->add_daily_log($data);
        $msg = 'added';
        // echo '<div class="updated"><p>Daily log added.</p></div>';
    }

    $redirect_url = add_query_arg(
        [
            'project_id' => intval($_POST['project_id']),
            'message'    => $msg
        ],
        admin_url('admin.php?page=poultry-logs')
    );

    wp_safe_redirect($redirect_url);
    exit;
}

// Show message after redirect
if (!empty($_GET['message'])) {
    if ($_GET['message'] === 'added') {
        echo '<div class="updated"><p>Daily log added successfully.</p></div>';
    } elseif ($_GET['message'] === 'updated') {
        echo '<div class="updated"><p>Daily log updated successfully.</p></div>';
    }
}

$logs = $crud->get_all_logs();
?>
<div class="wrap">
    <h1>Daily Logs</h1>
    <div class="pfm-container">

        <!-- Add/Edit Daily Log Form -->
        <div class="pfm-box">
            <h2 id="form-title">Add Daily Log</h2>
            <form method="post" id="log-form">
                <input type="hidden" name="log_id" id="log_id">
                <table class="form-table">
                    <tr>

                        <?php
                        $running_projects = array_filter($projects, fn($p) => $p->status === 'running');
                        $completed_projects = array_filter($projects, fn($p) => $p->status === 'completed');
                        $total_projects = count($projects);
                        ?>

                        <th>Project</th>
                        <td>
                            <?php if ($total_projects === 0) : ?>
                                <!-- No project at all -->
                                <button type="button" class="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=poultry-farm'); ?>'">
                                    Add Project
                                </button>
                            <?php else : ?>

                                <?php $selected_project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0; ?>

                                <!-- Show select field -->
                                <select name="project_id" id="project_id" <?php echo empty($running_projects) ? '' : 'required'; ?>>
                                    <option value="">Select a project</option>
                                    <?php foreach ($projects as $p) : ?>
                                        <option <?php echo $selected_project_id == $p->id ? 'selected="selected"' : ''; ?> value="<?php echo $p->id; ?>" data-status="<?php echo $p->status; ?>">
                                            <?php echo esc_html($p->project_name . ' (' . $p->status . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($total_projects !== 0) : ?>
                        <tr class="log-fields" style="display: none;">
                            <th>Date</th>
                            <td><input type="date" name="log_date" id="log_date" required></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Feed Bags</th>
                            <td><input type="number" step="0.1" name="feed_bags" id="feed_bags"></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Feed Cost</th>
                            <td><input type="number" step="0.01" name="feed_cost" id="feed_cost"></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Eggs Produced</th>
                            <td><input type="number" name="eggs_produced" id="eggs_produced"></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Eggs Sold</th>
                            <td><input type="number" name="eggs_sold" id="eggs_sold"></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Egg Price/unit</th>
                            <td><input type="number" step="0.01" name="egg_price_per_unit" id="egg_price_per_unit"></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Chickens Died</th>
                            <td><input type="number" name="chickens_died" id="chickens_died"></td>
                        </tr>
                        <!-- <tr class="log-fields" style="display: none;">
                            <th>Chickens Sold</th>
                            <td><input type="number" name="chickens_sold" id="chickens_sold"></td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Sale Price</th>
                            <td><input type="number" step="0.01" name="chickens_sale_price" id="chickens_sale_price"></td>
                        </tr> -->
                        <tr class="log-fields" style="display: none;">
                            <th>Medication</th>
                            <td>
                                <input type="number" name="medication" id="medication">
                            </td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Add customers</th>
                            <td>
                                <input type="text" name="customers" id="customers_field">
                            </td>
                        </tr>
                        <tr class="log-fields" style="display: none;">
                            <th>Notes</th>
                            <td><textarea name="notes" id="notes"></textarea></td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if ($total_projects !== 0) : ?>
                    <p class="log-fields" style="display: none;"><input type="submit" name="pfm_save_log" class="button-primary" id="save-btn" value="Save Log"></p>
                <?php endif; ?>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="pfm-box">
            <h2 id="table-head-name"></h2>

            <table class="widefat striped" id="logs_table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Medication</th>
                        <th>Eggs</th>
                        <th>Feed Cost</th>
                        <th>Chickens Died</th>
                        <th class="action-head">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $selected_project = isset($_POST['project_id']) ? intval($_POST['project_id']) : '';

                    if (!$selected_project) {
                        echo '<tr><td colspan="6" style="text-align:center;">Please select a project to see logs.</td></tr>';
                    } else {
                        if ($logs) {
                            foreach ($logs as $log) {
                                $datee = date('d F Y', strtotime($log->log_date));
                                $proj_name = '';
                                $proj_staus = '';
                                foreach ($projects as $p) {
                                    if ($p->id == $log->project_id) {
                                        $proj_name = $p->project_name;
                                        $staus = $p->status;
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

                                $log->customers = $customers_str;
                                $log_data = htmlspecialchars(wp_json_encode($log), ENT_QUOTES, 'UTF-8');

                                echo "<tr data-log='{$log_data}'>
                                <td>{$datee}</td>
                                <td>{$log->medication}</td>
                                <td>{$log->eggs_produced}/{$log->eggs_sold}</td>
                                <td>{$log->feed_cost}</td>
                                <td>{$log->chickens_died}</td>
                                <td class='pfm-actions'>
                                      <button class='button view-log' data-id='{$log->id}'>View</button>
                                      <?php $log->medication === 'completed' ?>
                                    <button class='button edit-log'>Edit</button>
                                    <button class='button delete-log' data-id='{$log->id}'>Delete</button>
                                </td>
                            </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No logs found.</td></tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="log-popup" style="display:none;">
        <div class="log-popup-overlay"></div>
        <div class="log-popup-content">
            <span class="log-popup-close">&times;</span>
            <h2>Log Details</h2>
            <div class="log-popup-body"></div>
        </div>
    </div>
</div>



<script>
    jQuery(document).ready(function($) {
        function updateLogs(projectID) {
            if (!projectID) {
                $('#logs_table tbody').html('<tr><td colspan="6" style="text-align:center;">Please select a project to see logs.</td></tr>');
                return;
            }

            $.post(ajaxurl, {
                action: 'pfm_get_logs',
                project_id: projectID
            }, function(response) {
                $('#logs_table tbody').html(response);
            });
        }



        $('#project_id').on('change' || 'ready', function() {
            const $this = $(this);
            const projectID = $this.val();
            const status = $this.find('option:selected').data('status');

            // Show or hide log fields
            const $form = $this.closest('form');
            const $logFields = $form.find('.log-fields');
            if (status === 'running') {
                $logFields.show();
            } else {
                $logFields.hide();
            }

            // Update logs table
            updateLogs(projectID);
        });

        // On page load
        const initialProjectID = $('#project_id').val();
        updateLogs(initialProjectID);

        // View log popup
        $(document).on('click', '.view-log', function() {
            const logData = $(this).closest('tr').attr('data-log');
            const log = JSON.parse(logData);

            const logDate = new Date(log.log_date);
            const options = {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            };
            const formattedDate = logDate.toLocaleDateString('en-GB', options);

            let customerStr = '';
            if (log.customers && typeof log.customers === 'string') {
                customerStr = log.customers
                    .split('/') // split into parts
                    .filter(Boolean) // remove empty strings
                    .map(c => c.charAt(0).toUpperCase() + c.slice(1)) // capitalize first letter
                    .join('/'); // join back with '/'
            }

            let html = '<table>';
            html += `<tr><td>Project</td><td>${log.project_name}</td></tr>`;
            html += `<tr><td>Date</td><td>${formattedDate}</td></tr>`;
            html += `<tr><td>Feed Bags</td><td>${log.feed_bags}</td></tr>`;
            html += `<tr><td>Feed Cost</td><td>${log.feed_cost}</td></tr>`;
            html += `<tr><td>Eggs Produced</td><td>${log.eggs_produced}</td></tr>`;
            html += `<tr><td>Eggs Sold</td><td>${log.eggs_sold}</td></tr>`;
            html += `<tr><td>Egg Price/unit</td><td>${log.egg_price_per_unit}</td></tr>`;
            html += `<tr><td>Chickens Died</td><td>${log.chickens_died}</td></tr>`;
            // html += `<tr><td>Chickens Sold</td><td>${log.chickens_sold}</td></tr>`;
            // html += `<tr><td>Sale Price</td><td>${log.chickens_sale_price}</td></tr>`;
            html += `<tr><td>Medication</td><td>${log.medication}</td></tr>`;
            html += `<tr><td>Customers</td><td>${customerStr}</td></tr>`;
            html += `<tr><td>Notes</td><td>${log.notes}</td></tr>`;
            html += '</table>';

            $('#log-popup .log-popup-body').html(html);
            $('#log-popup').addClass('active');
        });

        // Close popup
        $(document).on('click', '.log-popup-close, .log-popup-overlay', function() {
            $('#log-popup').removeClass('active');
        });

        // Delete log
        $(document).on('click', '.delete-log', function() {
            if (!confirm('Are you sure you want to delete this log?')) return;
            const logID = $(this).data('id');
            const row = $(this).closest('tr');

            $.post(ajaxurl, {
                action: 'pfm_delete_log',
                log_id: logID
            }, function(response) {
                if (response.success) row.remove();
                else alert('Failed to delete log.');
            });
        });

        // Edit log
        $(document).on('click', '.edit-log', function() {
            const log = $(this).closest('tr').data('log');
            $('#form-title').text('Edit Daily Log');
            $('#log_id').val(log.id);
            $('#project_id').val(log.project_id);
            $('#log_date').val(log.log_date);
            $('#feed_bags').val(log.feed_bags);
            $('#feed_cost').val(log.feed_cost);
            $('#eggs_produced').val(log.eggs_produced);
            $('#eggs_sold').val(log.eggs_sold);
            $('#egg_price_per_unit').val(log.egg_price_per_unit);
            $('#chickens_died').val(log.chickens_died);
            $('#chickens_sold').val(log.chickens_sold);
            $('#chickens_sale_price').val(log.chickens_sale_price);
            $('#medication').val(log.medication);
            $('#customers_field').val(log.customers);
            $('#notes').val(log.notes);
            $('#save-btn').val('Update Log');
        });

        const $input = $('#customers_field');

        // Add / on space press
        $input.on('keydown', function(e) {
            if (e.key === ' ') {
                e.preventDefault();
                let value = $(this).val();
                if (value.length && /[a-zA-Z0-9]$/.test(value)) {
                    $(this).val(value + '/');
                }
            }
        });

        // Optional: remove any spaces typed manually
        $input.on('input', function() {
            let value = $(this).val();
            value = value.replace(/\s/g, '');
            $(this).val(value);
        });

        $("#table-head-name").text($("#project_id option:selected").text());

        // On change
        $("#project_id").on('change', function() {
            $("#table-head-name").text($(this).find('option:selected').text());
        });
    });

    jQuery(document).ready(function($) {
        const $projectSelect = $('#project_id');
        const $form = $projectSelect.closest('form');
        const $logFields = $form.find('.log-fields');

        function toggleLogFields(status) {
            if (status === 'running') {
                $logFields.show();
            } else {
                $logFields.hide();
            }
        }

        // Initial check on page load
        const initialProjectID = $projectSelect.val();
        const initialStatus = $projectSelect.find('option:selected').data('status');
        toggleLogFields(initialStatus);

        if (initialProjectID && typeof updateLogs === 'function') {
            updateLogs(initialProjectID);
        }

        // Handle change event
        $projectSelect.on('change', function() {
            const projectID = $(this).val();
            const status = $(this).find('option:selected').data('status');
            toggleLogFields(status);

            if (projectID && typeof updateLogs === 'function') {
                updateLogs(projectID);
            }
        });
    });
</script>