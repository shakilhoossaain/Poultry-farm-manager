<?php
if (!defined('ABSPATH')) exit;

$crud = new Poultry_CRUD();

// Get selected project from GET param
$selected_project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Fetch all projects
$projects = $crud->get_all_projects();

// Fetch logs only for the selected project
$logs = $selected_project_id ? $crud->get_all_logs($selected_project_id) : [];
?>

<div class="wrap">
    <h1>Poultry Reports</h1>

    <div class="report_form_block">
        <form method="get">
            <input type="hidden" name="page" value="poultry-reports">
            <select style="margin-bottom:6px" id="project_select" name="project_id" onchange="this.form.submit()">
                <option value="">Select a Project</option>
                <?php foreach ($projects as $p) : ?>
                    <option value="<?php echo $p->id; ?>" <?php selected($selected_project_id, $p->id); ?>>
                        <?php echo esc_html($p->project_name . ' (' . $p->status . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <span id="egg_stock" style="display: none;"></span>
        <span id="initial_chickens" style="display: none;"></span>
        <span id="start_date" style="display: none;"></span>
    </div>

    <div class="pfm-container">
        <div class="pfm-box">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Age</th>
                        <th>Feed</th>
                        <th>Feed Cost</th>
                        <th>Mortality</th>
                        <th>Population</th>
                        <th>Medication</th>
                        <th>Eggs Produced</th>
                        <th>Productivity</th>
                        <!-- <th>Eggs Stock</th> -->
                        <th>Eggs Sold</th>
                        <!-- <th>Egg Price/unit</th> -->
                        <th>Sales Value</th>
                        <th>Gross Profit</th>
                        <!-- <th>Chickens Sold</th> -->
                        <!-- <th>Sale Price</th> -->
                        <th>Customer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($selected_project_id) :
                        $project = array_filter($projects, fn($prj) => $prj->id == $selected_project_id);
                        $project = reset($project);
                        $current_chickens = $project->initial_chickens;
                        $age = intval($project->starting_age_days);

                    ?>
                        <script>
                            jQuery(document).ready(function($) {
                                $('#start_date').show().text('Initiation ' + <?php echo json_encode(date('d F Y', strtotime($project->start_date))) ?>);
                                $('#initial_chickens').show().text('Initial Chickens ' + <?php echo json_encode(intval($project->initial_chickens)) ?>);
                                $('#egg_stock').show().text('Eggs IN Stock ' + <?php echo json_encode(intval($project->stock_eggs)) ?>);
                            })
                        </script>
                        <?php

                        // Initialize totals
                        $total_feed_bags = 0;
                        $total_feed_cost = 0;
                        $total_eggs_produced = 0;
                        $total_eggs_sold = 0;
                        $total_egg_price = 0;
                        $total_chickens_died = 0;
                        $total_sale_value = 0;
                        $total_gross_profit = 0;
                        $total_medication = 0;
                        $total_productivity = [];

                        $age = intval($project->starting_age_days);
                        $final_age = $age;

                        if ($logs) :
                            foreach ($logs as $log) :
                                $current_chickens -= intval($log->chickens_died);

                                // Check if it's a full week
                                $is_week_row = ($age % 7 === 0);
                                $row_style = $is_week_row ? 'background-color: #d4edda;' : '';

                                if ($is_week_row) {
                                    // We can compute week number since start. For example:
                                    $week_number = intval($age / 7);
                                    // If age = 7 => week_number = 1, age=14 =>2 etc.
                                    $age_display = "{$week_number} Weeks";
                                } else {
                                    $age_display = $age;
                                }

                                $customers_array = json_decode($log->customers, true);
                                $customers_str = '';
                                if (is_array($customers_array) && !empty($customers_array)) {
                                    $customers_str = implode('/', $customers_array);
                                }

                                $stock_egg = $log->eggs_produced - $log->eggs_sold;
                                $salse_value = $log->eggs_sold * $log->egg_price_per_unit;
                                $gross_profit = $salse_value - ($log->medication + ($log->feed_bags * $log->feed_cost));
                                $feeed_cost = $log->feed_bags * $log->feed_cost;
                                $productivity = (($log->eggs_produced * 30) / $current_chickens) * 100;
                        ?>
                                <tr style="<?php echo $row_style; ?>">
                                    <td><?php echo date('d F Y', strtotime($log->log_date)); ?></td>
                                    <td><?php echo $age_display; ?></td>
                                    <td><?php echo $log->feed_bags; ?></td>
                                    <td><?php echo pfm_format_number($feeed_cost, 2); ?></td>
                                    <td><?php echo intval($log->chickens_died); ?></td>
                                    <td><?php echo floatval($current_chickens); ?></td>
                                    <td><?php echo $log->medication; ?></td>
                                    <td><?php echo intval($log->eggs_produced); ?></td>
                                    <td><?php echo '%' . pfm_format_number($productivity, 2); ?></td>
                                    <!-- <td><php echo $stock_egg; ?></td> -->
                                    <td><?php echo intval($log->eggs_sold); ?></td>
                                    <td><?php echo pfm_format_number($salse_value, 2) ?></td>
                                    <td><?php echo pfm_format_number($gross_profit, 2); ?></td>
                                    <td><?php echo $customers_str; ?></td>
                                    <!-- <td><php echo intval($log->chickens_sold); ></td> -->
                                    <!-- <td><php echo floatval($log->chickens_sale_price); ></td> -->
                                </tr>
                            <?php

                                // update
                                $total_feed_bags += $log->feed_bags;
                                $total_feed_cost += $feeed_cost;
                                $total_chickens_died += intval($log->chickens_died);
                                $total_current_chickens = $current_chickens;
                                $total_eggs_produced += intval($log->eggs_produced);
                                $total_eggs_sold += intval($log->eggs_sold);
                                $total_egg_price += $log->egg_price_per_unit;
                                $total_medication += $log->medication;
                                $total_productivity[] = $productivity;
                                $total_sale_value += $salse_value;
                                $total_gross_profit += $gross_profit;
                                // $total_chickens_sold += intval($log->chickens_sold);
                                // $total_sale_price += $log->chickens_sale_price;
                                $final_age = $age; // Track last age
                                $age++; // Increment AFTER showing

                            endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="13" style="text-align:center;">No logs found for this project.</td>
                            </tr>
                        <?php endif; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="13" style="text-align:center;">Please select a project to view logs.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <?php if ($selected_project_id && $logs) : ?>
                    <?php
                    function calculateAverage($arr)
                    {
                        if (count($arr) === 0) return 0;

                        $sum = array_sum($arr);
                        $count = count($arr);

                        $average = $sum / $count;
                        return $average;
                    }

                    $total_productivity_prcent = calculateAverage($total_productivity);

                    ?>

                    <tfoot>
                        <tr class="footer-total">
                            <td>Total</td>
                            <td><?php echo pfm_format_number($final_age) . ' Days'; ?></td>
                            <td><?php echo pfm_format_number($total_feed_bags, 2); ?></td>
                            <td><?php echo '₦' . pfm_format_number($total_feed_cost, 2); ?></td>
                            <td><?php echo $total_chickens_died; ?></td>
                            <td><?php echo $total_current_chickens; ?></td>
                            <td><?php echo '₦' . pfm_format_number($total_medication, 2); ?></td>
                            <td><?php echo $total_eggs_produced; ?></td>
                            <td><?php echo '%' . pfm_format_number($total_productivity_prcent, 2); ?></td>
                            <td><?php echo $total_eggs_sold; ?></td>
                            <td><?php echo '₦' . pfm_format_number($total_sale_value, 2); ?></td>
                            <td><?php echo '₦' . pfm_format_number($total_gross_profit, 2); ?></td>
                            <!-- <td><php echo '$' . pfm_format_number($total_chickens_sold); ?></td> -->
                            <!-- <td>?php echo '$' . pfm_format_number($total_sale_price); ?></td> -->
                            <td><?php echo '' ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>