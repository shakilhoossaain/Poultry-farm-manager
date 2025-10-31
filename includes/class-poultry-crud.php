<?php
if (!defined('ABSPATH')) exit;

class Poultry_CRUD
{
    private $wpdb;
    private $projects_table;
    private $logs_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->projects_table = $wpdb->prefix . 'poultry_projects';
        $this->logs_table = $wpdb->prefix . 'poultry_daily_logs';
    }

    /* ---------- PROJECT METHODS ---------- */
    public function add_project($data)
    {
        $inserted = $this->wpdb->insert($this->projects_table, $data);
        if ($inserted !== false) {
            return $this->wpdb->insert_id;
        }
        return false;
    }

    public function edit_project($id, $data)
    {
        $updated = $this->wpdb->update($this->projects_table, $data, ['id' => $id]);
        if ($updated !== false) {
            return $id;
        }
        return false;
    }

    public function delete_project($id)
    {
        $this->wpdb->delete($this->logs_table, ['project_id' => $id], ['%d']);

        return $this->wpdb->delete($this->projects_table, ['id' => $id], ['%d']);
    }

    public function complete_project($id)
    {
        return $this->wpdb->update($this->projects_table, ['status' => 'completed'], ['id' => $id], ['%s'], ['%d']);
    }

    /* ---------- LOG METHODS ---------- */
    public function add_daily_log($data)
    {
        $current_stock = $this->get_stock_eggs($data['project_id']);
        $new_stock = $current_stock + (intval($data['eggs_produced']) - intval($data['eggs_sold']));
        $this->wpdb->update($this->projects_table, ['stock_eggs' => $new_stock], ['id' => $data['project_id']], ['%d'], ['%d']);

        return $this->wpdb->insert($this->logs_table, $data);
    }

    public function edit_daily_log($id, $data)
    {
        $old = $this->wpdb->get_row("SELECT eggs_produced, eggs_sold, project_id FROM {$this->logs_table} WHERE id = $id");

        $old_produced = intval($old->eggs_produced);
        $old_sold     = intval($old->eggs_sold);
        $project_id   = intval($old->project_id);

        // 2. Get new values from edit form
        $new_produced = intval($data['eggs_produced']);
        $new_sold     = intval($data['eggs_sold']);

        // 3. Calculate net difference
        $diff = ($new_produced - $old_produced) - ($new_sold - $old_sold);

        $this->wpdb->query("UPDATE {$this->projects_table} SET stock_eggs = stock_eggs + $diff WHERE id = $project_id");

        return $this->wpdb->update($this->logs_table, $data, ['id' => $id]);
    }

    public function delete_daily_log($id)
    {
        return $this->wpdb->delete($this->logs_table, ['id' => $id]);
    }

    /* ---------- GET METHODS ---------- */
    public function get_project_summary($project_id)
    {
        $project = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->projects_table} WHERE id=%d", $project_id)
        );
        $logs = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->logs_table} WHERE project_id=%d ORDER BY log_date ASC", $project_id)
        );
        return ['project' => $project, 'logs' => $logs];
    }

    public function get_all_projects()
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->projects_table} ORDER BY id DESC");
    }

    public function get_stock_eggs($project_id)
    {
        if (!$project_id) return 0;

        $stock = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT stock_eggs FROM {$this->projects_table} WHERE id = %d", $project_id)
        );

        return $stock ? intval($stock) : 0;
    }

    public function get_all_logs($project_id = 0)
    {
        if ($project_id) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare("SELECT * FROM {$this->logs_table} WHERE project_id=%d ORDER BY log_date DESC", $project_id)
            );
        }
        return $this->wpdb->get_results("SELECT * FROM {$this->logs_table} ORDER BY log_date DESC");
    }

    public function get_all_customers_global()
    {
        // Get all customer columns from all logs
        $results = $this->wpdb->get_col("SELECT customer FROM {$this->logs_table}");

        $customers = [];
        foreach ($results as $c) {
            if (!$c) continue;
            $arr = maybe_unserialize($c); // if stored as serialized array
            if (!is_array($arr)) $arr = json_decode($c, true);
            if (is_array($arr)) $customers = array_merge($customers, $arr);
        }

        $customers = array_unique($customers);

        return $customers;
    }
}
