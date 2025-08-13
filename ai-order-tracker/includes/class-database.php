<?php
/**
 * Database management class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Database
 */
class AIOT_Database {

    /**
     * Get database table name
     *
     * @param string $table Table name
     * @return string Full table name
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'aiot_' . $table;
    }

    /**
     * Create order tracking entry
     *
     * @param array $data Order data
     * @return int|false Order ID or false on failure
     */
    public static function create_order($data) {
        global $wpdb;
        
        $table = self::get_table_name('orders');
        
        $defaults = array(
            'tracking_id' => '',
            'order_id' => '',
            'customer_id' => 0,
            'customer_email' => '',
            'customer_name' => '',
            'status' => 'processing',
            'location' => '',
            'current_step' => 0,
            'progress' => 0,
            'estimated_delivery' => null,
            'carrier' => '',
            'carrier_url' => '',
            'origin_address' => '',
            'destination_address' => '',
            'weight' => 0.00,
            'dimensions' => '',
            'package_type' => '',
            'service_type' => '',
            'tracking_history' => '',
            'meta' => '',
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Sanitize data
        $data = array_map('sanitize_text_field', $data);
        
        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get order by order ID
     *
     * @param string $order_id Order ID
     * @return array|false Order data or false if not found
     */
    public static function get_order_by_order_id($order_id) {
        global $wpdb;
        
        $table = self::get_table_name('orders');
        
        $order_id = sanitize_text_field($order_id);
        
        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE order_id = %s",
                $order_id
            ),
            ARRAY_A
        );
        
        return $order ? $order : false;
    }

    /**
     * Get order by tracking ID
     *
     * @param string $tracking_id Tracking ID
     * @return array|false Order data or false if not found
     */
    public static function get_order_by_tracking_id($tracking_id) {
        global $wpdb;
        
        $table = self::get_table_name('orders');
        
        $tracking_id = sanitize_text_field($tracking_id);
        
        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE tracking_id = %s",
                $tracking_id
            ),
            ARRAY_A
        );
        
        return $order ? $order : false;
    }

    /**
     * Update order status
     *
     * @param string $tracking_id Tracking ID
     * @param string $status New status
     * @param array $data Additional data to update
     * @return bool True on success, false on failure
     */
    public static function update_order_status($tracking_id, $status, $data = array()) {
        global $wpdb;
        
        $table = self::get_table_name('orders');
        
        $tracking_id = sanitize_text_field($tracking_id);
        $status = sanitize_text_field($status);
        
        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        if (!empty($data)) {
            $update_data = array_merge($update_data, $data);
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('tracking_id' => $tracking_id),
            array('%s', '%s'),
            array('%s')
        );
        
        return $result !== false;
    }

    /**
     * Get tracking events for order
     *
     * @param int $order_id Order ID
     * @return array Tracking events
     */
    public static function get_tracking_events($order_id) {
        global $wpdb;
        
        $table = self::get_table_name('tracking_events');
        
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE order_id = %d ORDER BY timestamp ASC",
                $order_id
            ),
            ARRAY_A
        );
        
        return $events;
    }

    /**
     * Add tracking event
     *
     * @param array $event Event data
     * @return int|false Event ID or false on failure
     */
    public static function add_tracking_event($event) {
        global $wpdb;
        
        $table = self::get_table_name('tracking_events');
        
        $defaults = array(
            'order_id' => 0,
            'event_type' => '',
            'event_status' => '',
            'location' => '',
            'description' => '',
            'timestamp' => current_time('mysql'),
            'latitude' => null,
            'longitude' => null,
            'meta' => '',
        );
        
        $event = wp_parse_args($event, $defaults);
        
        // Sanitize data
        $event = array_map('sanitize_text_field', $event);
        
        $result = $wpdb->insert(
            $table,
            $event,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get zones
     *
     * @param array $args Query arguments
     * @return array Zones
     */
    public static function get_zones($args = array()) {
        global $wpdb;
        
        $table = self::get_table_name('zones');
        
        $defaults = array(
            'is_active' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $prepare = array();
        
        if ($args['is_active'] !== null) {
            $where[] = 'is_active = %d';
            $prepare[] = $args['is_active'] ? 1 : 0;
        }
        
        $sql = "SELECT * FROM {$table}";
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= ' LIMIT %d';
            $prepare[] = $args['limit'];
        }
        
        if (!empty($prepare)) {
            $zones = $wpdb->get_results($wpdb->prepare($sql, $prepare), ARRAY_A);
        } else {
            $zones = $wpdb->get_results($sql, ARRAY_A);
        }
        
        return $zones;
    }

    /**
     * Get zone by ID
     *
     * @param int $zone_id Zone ID
     * @return array|false Zone data or false if not found
     */
    public static function get_zone($zone_id) {
        global $wpdb;
        
        $table = self::get_table_name('zones');
        
        $zone = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $zone_id
            ),
            ARRAY_A
        );
        
        return $zone ? $zone : false;
    }

    /**
     * Get couriers
     *
     * @param array $args Query arguments
     * @return array Couriers
     */
    public static function get_couriers($args = array()) {
        global $wpdb;
        
        $table = self::get_table_name('couriers');
        
        $defaults = array(
            'is_active' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $prepare = array();
        
        if ($args['is_active'] !== null) {
            $where[] = 'is_active = %d';
            $prepare[] = $args['is_active'] ? 1 : 0;
        }
        
        $sql = "SELECT * FROM {$table}";
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= ' LIMIT %d';
            $prepare[] = $args['limit'];
        }
        
        if (!empty($prepare)) {
            $couriers = $wpdb->get_results($wpdb->prepare($sql, $prepare), ARRAY_A);
        } else {
            $couriers = $wpdb->get_results($sql, ARRAY_A);
        }
        
        return $couriers;
    }

    /**
     * Get courier by ID
     *
     * @param int $courier_id Courier ID
     * @return array|false Courier data or false if not found
     */
    public static function get_courier($courier_id) {
        global $wpdb;
        
        $table = self::get_table_name('couriers');
        
        $courier = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $courier_id
            ),
            ARRAY_A
        );
        
        return $courier ? $courier : false;
    }

    /**
     * Clean up old data
     *
     * @param int $days_old Number of days to keep
     * @return int Number of rows deleted
     */
    public static function cleanup_old_data($days_old = 90) {
        global $wpdb;
        
        $orders_table = self::get_table_name('orders');
        $events_table = self::get_table_name('tracking_events');
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        // Delete old orders
        $deleted_orders = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$orders_table} WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        // Delete old tracking events
        $deleted_events = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$events_table} WHERE timestamp < %s",
                $cutoff_date
            )
        );
        
        return $deleted_orders + $deleted_events;
    }
}