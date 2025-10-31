<?php
if (!defined('ABSPATH')) exit;

class Poultry_Installer
{
    public static function install()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $projects = $wpdb->prefix . 'poultry_projects';
        $logs     = $wpdb->prefix . 'poultry_daily_logs';
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $projects (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_name VARCHAR(255) NOT NULL,
            start_date DATE NOT NULL,
            initial_chickens INT NOT NULL,
            starting_age_days INT NOT NULL,
            stock_eggs INT NOT NULL,
            status VARCHAR(50) DEFAULT 'running',
            notes TEXT,
            PRIMARY KEY(id)
        ) $charset_collate;";
        dbDelta($sql);

        $sql = "CREATE TABLE $logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            log_date DATE NOT NULL,
            feed_bags FLOAT DEFAULT 0,
            feed_cost FLOAT DEFAULT 0,
            eggs_produced INT DEFAULT 0,
            eggs_sold INT DEFAULT 0,
            egg_price_per_unit FLOAT DEFAULT 0,
            chickens_died INT DEFAULT 0,
            chickens_sold INT DEFAULT 0,
            chickens_sale_price FLOAT DEFAULT 0,
            medication FLOAT DEFAULT 0,
            customers JSON DEFAULT NULL,
            notes TEXT,
            PRIMARY KEY(id),
            FOREIGN KEY(project_id) REFERENCES $projects(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }
}
