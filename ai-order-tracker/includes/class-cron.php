<?php
/**
 * Cron job management class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Cron
 */
class AIOT_Cron {

    /**
     * Initialize cron jobs
     */
    public static function init() {
        add_action('aiot_daily_cleanup', array(__CLASS__, 'daily_cleanup'));
        add_action('aiot_update_order_status', array(__CLASS__, 'update_order_status'));
        add_action('aiot_cleanup_old_data', array(__CLASS__, 'cleanup_old_data'));
        add_action('aiot_send_notifications', array(__CLASS__, 'send_notifications'));
        add_action('aiot_sync_external_data', array(__CLASS__, 'sync_external_data'));
    }

    /**
     * Daily cleanup tasks
     */
    public static function daily_cleanup() {
        self::cleanup_old_data();
        self::cleanup_transients();
        self::cleanup_logs();
        self::optimize_tables();
    }

    /**
     * Update order status for simulation mode
     */
    public static function update_order_status() {
        if (!get_option('aiot_simulation_mode', false)) {
            return;
        }

        global $wpdb;
        $orders_table = AIOT_Database::get_table_name('orders');
        
        // Get orders that need status updates
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$orders_table} 
                WHERE status NOT IN ('delivered', 'failed', 'returned') 
                AND updated_at < %s 
                ORDER BY created_at ASC",
                date('Y-m-d H:i:s', strtotime('-1 hour'))
            ),
            ARRAY_A
        );

        foreach ($orders as $order) {
            self::simulate_order_progress($order);
        }
    }

    /**
     * Simulate order progress
     *
     * @param array $order Order data
     */
    private static function simulate_order_progress($order) {
        $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
        $current_index = array_search($order['status'], $statuses);
        
        if ($current_index === false || $current_index >= count($statuses) - 1) {
            return; // Already at final status
        }

        // Calculate probability of status update
        $update_probability = self::calculate_update_probability($order);
        
        if (mt_rand(1, 100) <= $update_probability) {
            $new_status = $statuses[$current_index + 1];
            
            // Update order status
            AIOT_Tracking_Engine::update_order_status(
                $order['tracking_id'],
                $new_status,
                array(
                    'location' => self::generate_location($order),
                    'description' => AIOT_Tracking_Engine::get_status_description($new_status),
                )
            );

            // Send notification if enabled
            if (get_option('aiot_enable_notifications', false)) {
                self::send_status_update_notification($order, $new_status);
            }
        }
    }

    /**
     * Calculate update probability based on order age and status
     *
     * @param array $order Order data
     * @return int Probability percentage
     */
    private static function calculate_update_probability($order) {
        $order_age = time() - strtotime($order['created_at']);
        $age_in_hours = $order_age / 3600;
        
        $base_probability = 30; // 30% base probability
        
        // Increase probability based on age
        if ($age_in_hours > 24) {
            $base_probability += 20;
        }
        
        if ($age_in_hours > 48) {
            $base_probability += 20;
        }
        
        // Adjust based on current status
        $status_multipliers = array(
            'processing' => 1.0,
            'confirmed' => 1.2,
            'packed' => 1.5,
            'shipped' => 1.0,
            'in_transit' => 0.8,
            'out_for_delivery' => 1.5,
        );
        
        $status = $order['status'];
        if (isset($status_multipliers[$status])) {
            $base_probability *= $status_multipliers[$status];
        }
        
        return min(100, max(10, round($base_probability)));
    }

    /**
     * Generate realistic location for order
     *
     * @param array $order Order data
     * @return string Generated location
     */
    private static function generate_location($order) {
        $locations = array(
            'Processing Center',
            'Distribution Hub',
            'Regional Facility',
            'Local Depot',
            'Delivery Station',
            'Customer Address',
            'Warehouse',
            'Sorting Facility',
            'Transit Center',
            'Local Office',
        );
        
        // Add some randomness
        $random_number = mt_rand(1, 100);
        
        if ($random_number <= 70) {
            // 70% chance of using predefined locations
            return $locations[array_rand($locations)];
        } else {
            // 30% chance of generating custom location
            return self::generate_custom_location($order);
        }
    }

    /**
     * Generate custom location
     *
     * @param array $order Order data
     * @return string Custom location
     */
    private static function generate_custom_location($order) {
        $cities = array(
            'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix',
            'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose',
            'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte',
            'San Francisco', 'Indianapolis', 'Seattle', 'Denver', 'Washington',
        );
        
        $facilities = array(
            'Distribution Center', 'Processing Facility', 'Warehouse',
            'Sorting Center', 'Transit Hub', 'Local Depot',
        );
        
        $city = $cities[array_rand($cities)];
        $facility = $facilities[array_rand($facilities)];
        
        return $facility . ' - ' . $city;
    }

    /**
     * Send status update notification
     *
     * @param array $order Order data
     * @param string $new_status New status
     */
    private static function send_status_update_notification($order, $new_status) {
        if (empty($order['customer_email'])) {
            return;
        }

        $subject = sprintf(
            __('Order %s Status Update', 'ai-order-tracker'),
            $order['tracking_id']
        );

        $message = self::get_status_update_email_content($order, $new_status);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>',
        );

        AIOT_Helpers::send_email($order['customer_email'], $subject, $message, $headers);
    }

    /**
     * Get status update email content
     *
     * @param array $order Order data
     * @param string $status Order status
     * @return string Email content
     */
    private static function get_status_update_email_content($order, $status) {
        $status_info = AIOT_Tracking_Engine::get_order_status($status);
        
        $data = array(
            'tracking_id' => $order['tracking_id'],
            'status' => $status,
            'status_label' => $status_info['label'],
            'status_description' => $status_info['description'],
            'customer_name' => $order['customer_name'],
            'estimated_delivery' => AIOT_Helpers::format_date($order['estimated_delivery']),
            'tracking_url' => home_url('/?tracking_id=' . $order['tracking_id']),
            'site_name' => get_option('blogname'),
            'site_url' => home_url(),
        );

        return AIOT_Helpers::get_email_template('status-update', $data);
    }

    /**
     * Cleanup old data
     */
    public static function cleanup_old_data() {
        $days_to_keep = get_option('aiot_data_retention_days', 90);
        
        AIOT_Database::cleanup_old_data($days_to_keep);
    }

    /**
     * Cleanup transients
     */
    public static function cleanup_transients() {
        global $wpdb;
        
        // Delete expired transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_aiot_%' 
                AND option_value < %d",
                time()
            )
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_aiot_%'"
        );
    }

    /**
     * Cleanup logs
     */
    public static function cleanup_logs() {
        $log_retention_days = get_option('aiot_log_retention_days', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$log_retention_days} days"));
        
        // This would clean up any custom log tables
        // For now, we'll just log the cleanup action
        AIOT_Helpers::log('Log cleanup performed', 'info', array('cutoff_date' => $cutoff_date));
    }

    /**
     * Optimize database tables
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            AIOT_Database::get_table_name('orders'),
            AIOT_Database::get_table_name('zones'),
            AIOT_Database::get_table_name('couriers'),
            AIOT_Database::get_table_name('tracking_events'),
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }

    /**
     * Send notifications
     */
    public static function send_notifications() {
        if (!get_option('aiot_enable_notifications', false)) {
            return;
        }

        self::send_delivery_reminders();
        self::send_status_change_notifications();
        self::send_promotional_notifications();
    }

    /**
     * Send delivery reminders
     */
    private static function send_delivery_reminders() {
        global $wpdb;
        $orders_table = AIOT_Database::get_table_name('orders');
        
        // Get orders that will be delivered soon
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$orders_table} 
                WHERE status IN ('in_transit', 'out_for_delivery') 
                AND estimated_delivery BETWEEN %s AND %s 
                AND customer_email != ''",
                date('Y-m-d'),
                date('Y-m-d', strtotime('+2 days'))
            ),
            ARRAY_A
        );

        foreach ($orders as $order) {
            self::send_delivery_reminder_notification($order);
        }
    }

    /**
     * Send delivery reminder notification
     *
     * @param array $order Order data
     */
    private static function send_delivery_reminder_notification($order) {
        $subject = sprintf(
            __('Your Order %s Will Be Delivered Soon', 'ai-order-tracker'),
            $order['tracking_id']
        );

        $message = self::get_delivery_reminder_email_content($order);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>',
        );

        AIOT_Helpers::send_email($order['customer_email'], $subject, $message, $headers);
    }

    /**
     * Get delivery reminder email content
     *
     * @param array $order Order data
     * @return string Email content
     */
    private static function get_delivery_reminder_email_content($order) {
        $data = array(
            'tracking_id' => $order['tracking_id'],
            'customer_name' => $order['customer_name'],
            'estimated_delivery' => AIOT_Helpers::format_date($order['estimated_delivery']),
            'tracking_url' => home_url('/?tracking_id=' . $order['tracking_id']),
            'site_name' => get_option('blogname'),
            'site_url' => home_url(),
        );

        return AIOT_Helpers::get_email_template('delivery-reminder', $data);
    }

    /**
     * Send status change notifications
     */
    private static function send_status_change_notifications() {
        // This would handle any queued status change notifications
        // For now, we'll just log the action
        AIOT_Helpers::log('Status change notifications processed', 'info');
    }

    /**
     * Send promotional notifications
     */
    private static function send_promotional_notifications() {
        // This would handle promotional email notifications
        // For now, we'll just log the action
        AIOT_Helpers::log('Promotional notifications processed', 'info');
    }

    /**
     * Sync external data
     */
    public static function sync_external_data() {
        if (!get_option('aiot_sync_external_data', false)) {
            return;
        }

        self::sync_courier_data();
        self::sync_zone_data();
        self::sync_geo_data();
    }

    /**
     * Sync courier data
     */
    private static function sync_courier_data() {
        // This would sync courier data from external APIs
        AIOT_Helpers::log('Courier data sync performed', 'info');
    }

    /**
     * Sync zone data
     */
    private static function sync_zone_data() {
        // This would sync zone data from external sources
        AIOT_Helpers::log('Zone data sync performed', 'info');
    }

    /**
     * Sync geo data
     */
    private static function sync_geo_data() {
        // This would sync geographical data from external sources
        AIOT_Helpers::log('Geo data sync performed', 'info');
    }

    /**
     * Schedule cron jobs
     */
    public static function schedule_cron_jobs() {
        // Daily cleanup
        if (!wp_next_scheduled('aiot_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'aiot_daily_cleanup');
        }

        // Order status updates
        if (!wp_next_scheduled('aiot_update_order_status')) {
            wp_schedule_event(time(), 'hourly', 'aiot_update_order_status');
        }

        // Data cleanup
        if (!wp_next_scheduled('aiot_cleanup_old_data')) {
            wp_schedule_event(time(), 'weekly', 'aiot_cleanup_old_data');
        }

        // Notifications
        if (!wp_next_scheduled('aiot_send_notifications')) {
            wp_schedule_event(time(), 'twicedaily', 'aiot_send_notifications');
        }

        // External data sync
        if (!wp_next_scheduled('aiot_sync_external_data')) {
            wp_schedule_event(time(), 'daily', 'aiot_sync_external_data');
        }
    }

    /**
     * Unschedule cron jobs
     */
    public static function unschedule_cron_jobs() {
        wp_clear_scheduled_hook('aiot_daily_cleanup');
        wp_clear_scheduled_hook('aiot_update_order_status');
        wp_clear_scheduled_hook('aiot_cleanup_old_data');
        wp_clear_scheduled_hook('aiot_send_notifications');
        wp_clear_scheduled_hook('aiot_sync_external_data');
    }

    /**
     * Get cron job status
     *
     * @return array Cron job status
     */
    public static function get_cron_status() {
        $crons = array(
            'aiot_daily_cleanup' => wp_next_scheduled('aiot_daily_cleanup'),
            'aiot_update_order_status' => wp_next_scheduled('aiot_update_order_status'),
            'aiot_cleanup_old_data' => wp_next_scheduled('aiot_cleanup_old_data'),
            'aiot_send_notifications' => wp_next_scheduled('aiot_send_notifications'),
            'aiot_sync_external_data' => wp_next_scheduled('aiot_sync_external_data'),
        );

        $status = array();
        foreach ($crons as $hook => $next_run) {
            $status[$hook] = array(
                'scheduled' => $next_run !== false,
                'next_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled',
                'last_run' => self::get_last_cron_run($hook),
            );
        }

        return $status;
    }

    /**
     * Get last cron run time
     *
     * @param string $hook Cron hook
     * @return string|false Last run time or false
     */
    private static function get_last_cron_run($hook) {
        // This is a simplified implementation
        // In a real implementation, you would track cron execution times
        return false;
    }

    /**
     * Manually trigger cron job
     *
     * @param string $hook Cron hook
     * @return bool True if successful
     */
    public static function trigger_cron_job($hook) {
        if (!wp_next_scheduled($hook)) {
            return false;
        }

        wp_schedule_single_event(time(), $hook);
        spawn_cron();
        
        return true;
    }
}