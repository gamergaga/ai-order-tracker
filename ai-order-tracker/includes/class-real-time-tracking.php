<?php
/**
 * Real-time tracking class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Real_Time_Tracking
 */
class AIOT_Real_Time_Tracking {

    /**
     * Initialize real-time tracking
     */
    public static function init() {
        add_action('wp_ajax_aiot_track_order', array(__CLASS__, 'handle_tracking_request'));
        add_action('wp_ajax_nopriv_aiot_track_order', array(__CLASS__, 'handle_tracking_request'));
        add_action('wp_ajax_aiot_get_order_details', array(__CLASS__, 'get_order_details'));
        add_action('wp_ajax_nopriv_aiot_get_order_details', array(__CLASS__, 'get_order_details'));
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
    }

    /**
     * Handle tracking request
     */
    public static function handle_tracking_request() {
        // Verify nonce
        if (!check_ajax_referer('aiot_public_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }

        // Get tracking ID
        $tracking_id = isset($_POST['tracking_id']) ? sanitize_text_field($_POST['tracking_id']) : '';

        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }

        // Get tracking info
        $tracking_info = self::get_tracking_info($tracking_id);

        if ($tracking_info === false) {
            wp_send_json_error(array('message' => __('Tracking information not found.', 'ai-order-tracker')));
        }

        wp_send_json_success(array('data' => $tracking_info));
    }

    /**
     * Get order details
     */
    public static function get_order_details() {
        // Verify nonce
        if (!check_ajax_referer('aiot_public_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }

        // Get tracking ID
        $tracking_id = isset($_POST['tracking_id']) ? sanitize_text_field($_POST['tracking_id']) : '';

        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }

        // Get order details
        $order_details = self::get_order_details_by_tracking_id($tracking_id);

        if ($order_details === false) {
            wp_send_json_error(array('message' => __('Order details not found.', 'ai-order-tracker')));
        }

        wp_send_json_success(array('data' => $order_details));
    }

    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        register_rest_route('aiot/v1', '/track/(?P<tracking_id>[a-zA-Z0-9\-_]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'rest_get_tracking_info'),
            'permission_callback' => '__return_true',
            'args' => array(
                'tracking_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route('aiot/v1', '/orders/(?P<tracking_id>[a-zA-Z0-9\-_]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'rest_get_order_details'),
            'permission_callback' => '__return_true',
            'args' => array(
                'tracking_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route('aiot/v1', '/orders', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'rest_create_order'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => array(
                'order_data' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_array($param) && !empty($param);
                    },
                ),
            ),
        ));

        register_rest_route('aiot/v1', '/orders/(?P<tracking_id>[a-zA-Z0-9\-_]+)', array(
            'methods' => 'PUT',
            'callback' => array(__CLASS__, 'rest_update_order'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => array(
                'tracking_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'status' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    /**
     * REST API get tracking info
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response
     */
    public static function rest_get_tracking_info($request) {
        $tracking_id = $request->get_param('tracking_id');
        $tracking_info = self::get_tracking_info($tracking_id);

        if ($tracking_info === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Tracking information not found.', 'ai-order-tracker'),
            ), 404);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $tracking_info,
        ), 200);
    }

    /**
     * REST API get order details
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response
     */
    public static function rest_get_order_details($request) {
        $tracking_id = $request->get_param('tracking_id');
        $order_details = self::get_order_details_by_tracking_id($tracking_id);

        if ($order_details === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Order details not found.', 'ai-order-tracker'),
            ), 404);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $order_details,
        ), 200);
    }

    /**
     * REST API create order
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response
     */
    public static function rest_create_order($request) {
        $order_data = $request->get_param('order_data');
        
        // Sanitize order data
        $order_data = AIOT_Security::sanitize_array($order_data, array(
            'tracking_id' => 'tracking_id',
            'order_id' => 'text',
            'customer_id' => 'int',
            'customer_email' => 'email',
            'customer_name' => 'text',
            'status' => 'text',
            'location' => 'text',
            'estimated_delivery' => 'text',
            'carrier' => 'text',
            'origin_address' => 'textarea',
            'destination_address' => 'textarea',
            'weight' => 'float',
            'dimensions' => 'text',
            'package_type' => 'text',
            'service_type' => 'text',
        ));

        // Create order
        $tracking_id = AIOT_Tracking_Engine::create_order_tracking($order_data);

        if ($tracking_id === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to create order.', 'ai-order-tracker'),
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Order created successfully.', 'ai-order-tracker'),
            'data' => array(
                'tracking_id' => $tracking_id,
            ),
        ), 201);
    }

    /**
     * REST API update order
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response
     */
    public static function rest_update_order($request) {
        $tracking_id = $request->get_param('tracking_id');
        $status = $request->get_param('status');
        $additional_data = $request->get_param('additional_data') ?: array();

        // Sanitize additional data
        $additional_data = AIOT_Security::sanitize_array($additional_data, array(
            'location' => 'text',
            'description' => 'textarea',
        ));

        // Update order status
        $result = AIOT_Tracking_Engine::update_order_status($tracking_id, $status, $additional_data);

        if (!$result) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to update order.', 'ai-order-tracker'),
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Order updated successfully.', 'ai-order-tracker'),
        ), 200);
    }

    /**
     * Get tracking information
     *
     * @param string $tracking_id Tracking ID
     * @return array|false Tracking information
     */
    public static function get_tracking_info($tracking_id) {
        // Try to get from database first
        $tracking_info = AIOT_Tracking_Engine::get_tracking_info($tracking_id);
        
        if ($tracking_info !== false) {
            return $tracking_info;
        }

        // If not found in database and simulation mode is enabled, create simulated tracking
        if (get_option('aiot_simulation_mode', false)) {
            return self::create_simulated_tracking($tracking_id);
        }

        return false;
    }

    /**
     * Create simulated tracking information
     *
     * @param string $tracking_id Tracking ID
     * @return array Simulated tracking information
     */
    private static function create_simulated_tracking($tracking_id) {
        // Generate random order data
        $order_data = array(
            'tracking_id' => $tracking_id,
            'order_id' => 'ORD-' . rand(10000, 99999),
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'status' => 'processing',
            'location' => 'Processing Center',
            'origin_address' => '123 Main St, New York, NY 10001',
            'destination_address' => '456 Oak Ave, Los Angeles, CA 90001',
            'weight' => rand(1, 10),
            'dimensions' => '10x8x4',
            'package_type' => 'Package',
            'service_type' => 'Standard',
        );

        // Create order tracking
        $result = AIOT_Tracking_Engine::create_order_tracking($order_data);

        if ($result === false) {
            return false;
        }

        // Return tracking info
        return AIOT_Tracking_Engine::get_tracking_info($tracking_id);
    }

    /**
     * Get order details by tracking ID
     *
     * @param string $tracking_id Tracking ID
     * @return array|false Order details
     */
    public static function get_order_details_by_tracking_id($tracking_id) {
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if ($order === false) {
            return false;
        }

        // Get tracking events
        $events = AIOT_Database::get_tracking_events($order['id']);

        // Get zone information
        $zone_info = AIOT_Zone_Manager::get_delivery_time($order['destination_address']);

        // Get courier information
        $courier_info = array();
        if (!empty($order['carrier'])) {
            $courier_info = AIOT_Courier_Manager::get_courier_by_slug($order['carrier']);
        }

        return array(
            'order' => $order,
            'events' => $events,
            'zone_info' => $zone_info,
            'courier_info' => $courier_info,
            'status_info' => AIOT_Tracking_Engine::get_order_status($order['status']),
        );
    }

    /**
     * Simulate tracking progress
     *
     * @param string $tracking_id Tracking ID
     * @return array|false Updated tracking information
     */
    public static function simulate_tracking_progress($tracking_id) {
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if ($order === false) {
            return false;
        }

        // Simulate progress
        $result = AIOT_Tracking_Engine::simulate_progress($tracking_id);

        if (!$result) {
            return false;
        }

        // Return updated tracking info
        return self::get_tracking_info($tracking_id);
    }

    /**
     * Get real-time tracking data
     *
     * @param string $tracking_id Tracking ID
     * @param string $courier Courier name
     * @return array|false Real-time tracking data
     */
    public static function get_real_time_tracking_data($tracking_id, $courier) {
        // Check if real-time tracking is enabled
        if (!get_option('aiot_enable_real_time_tracking', false)) {
            return false;
        }

        // Get API key
        $api_key = AIOT_Real_Time_API::get_api_key($courier);
        
        if (!$api_key) {
            return false;
        }

        // Check rate limit
        if (AIOT_Real_Time_API::is_rate_limit_exceeded($courier)) {
            return false;
        }

        // Get real-time tracking data
        $tracking_data = AIOT_Real_Time_API::track_package($courier, $tracking_id);

        if ($tracking_data === false) {
            return false;
        }

        return $tracking_data;
    }

    /**
     * Update order with real-time data
     *
     * @param string $tracking_id Tracking ID
     * @param array $real_time_data Real-time tracking data
     * @return bool True on success
     */
    public static function update_order_with_real_time_data($tracking_id, $real_time_data) {
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if ($order === false) {
            return false;
        }

        // Map real-time status to internal status
        $status_mapping = array(
            'delivered' => 'delivered',
            'out_for_delivery' => 'out_for_delivery',
            'in_transit' => 'in_transit',
            'shipped' => 'shipped',
            'picked_up' => 'shipped',
            'processing' => 'processing',
            'unknown' => 'processing',
        );

        $status = isset($status_mapping[$real_time_data['status']]) ? 
            $status_mapping[$real_time_data['status']] : 'processing';

        // Update order status
        $update_data = array(
            'location' => $real_time_data['location'],
            'description' => $real_time_data['description'],
        );

        if (!empty($real_time_data['estimated_delivery'])) {
            $update_data['estimated_delivery'] = $real_time_data['estimated_delivery'];
        }

        $result = AIOT_Tracking_Engine::update_order_status($tracking_id, $status, $update_data);

        if (!$result) {
            return false;
        }

        // Add tracking events
        if (!empty($real_time_data['events'])) {
            foreach ($real_time_data['events'] as $event) {
                AIOT_Database::add_tracking_event(array(
                    'order_id' => $order['id'],
                    'event_type' => 'status_update',
                    'event_status' => $event['status'],
                    'location' => $event['location'],
                    'description' => $event['description'],
                    'timestamp' => $event['date'] . ' ' . $event['time'],
                ));
            }
        }

        return true;
    }

    /**
     * Get tracking statistics
     *
     * @return array Tracking statistics
     */
    public static function get_tracking_statistics() {
        global $wpdb;
        
        $orders_table = AIOT_Database::get_table_name('orders');
        $events_table = AIOT_Database::get_table_name('tracking_events');
        
        $stats = array(
            'total_orders' => 0,
            'delivered_orders' => 0,
            'in_transit_orders' => 0,
            'processing_orders' => 0,
            'failed_orders' => 0,
            'total_events' => 0,
            'average_delivery_time' => 0,
            'popular_carriers' => array(),
            'status_distribution' => array(),
        );

        // Get total orders
        $stats['total_orders'] = $wpdb->get_var("SELECT COUNT(*) FROM {$orders_table}");

        // Get orders by status
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$orders_table} GROUP BY status",
            ARRAY_A
        );

        foreach ($status_counts as $row) {
            switch ($row['status']) {
                case 'delivered':
                    $stats['delivered_orders'] = $row['count'];
                    break;
                case 'in_transit':
                case 'out_for_delivery':
                    $stats['in_transit_orders'] += $row['count'];
                    break;
                case 'processing':
                case 'confirmed':
                case 'packed':
                case 'shipped':
                    $stats['processing_orders'] += $row['count'];
                    break;
                case 'failed':
                    $stats['failed_orders'] = $row['count'];
                    break;
            }
            
            $stats['status_distribution'][$row['status']] = $row['count'];
        }

        // Get total events
        $stats['total_events'] = $wpdb->get_var("SELECT COUNT(*) FROM {$events_table}");

        // Get popular carriers
        $carrier_counts = $wpdb->get_results(
            "SELECT carrier, COUNT(*) as count FROM {$orders_table} WHERE carrier != '' GROUP BY carrier ORDER BY count DESC LIMIT 5",
            ARRAY_A
        );

        foreach ($carrier_counts as $row) {
            $stats['popular_carriers'][$row['carrier']] = $row['count'];
        }

        // Calculate average delivery time
        $delivery_times = $wpdb->get_results(
            "SELECT TIMESTAMPDIFF(HOUR, created_at, updated_at) as delivery_time 
            FROM {$orders_table} 
            WHERE status = 'delivered' 
            AND created_at IS NOT NULL 
            AND updated_at IS NOT NULL",
            ARRAY_A
        );

        if (!empty($delivery_times)) {
            $total_time = 0;
            foreach ($delivery_times as $row) {
                $total_time += $row['delivery_time'];
            }
            $stats['average_delivery_time'] = round($total_time / count($delivery_times), 2);
        }

        return $stats;
    }

    /**
     * Search orders
     *
     * @param array $args Search arguments
     * @return array Search results
     */
    public static function search_orders($args = array()) {
        global $wpdb;
        
        $orders_table = AIOT_Database::get_table_name('orders');
        
        $defaults = array(
            'search' => '',
            'status' => '',
            'customer_email' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $prepare = array();
        
        // Search term
        if (!empty($args['search'])) {
            $where[] = "(tracking_id LIKE %s OR order_id LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare[] = $search_term;
            $prepare[] = $search_term;
            $prepare[] = $search_term;
            $prepare[] = $search_term;
        }
        
        // Status filter
        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $prepare[] = $args['status'];
        }
        
        // Customer email filter
        if (!empty($args['customer_email'])) {
            $where[] = "customer_email = %s";
            $prepare[] = $args['customer_email'];
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $where[] = "created_at >= %s";
            $prepare[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = "created_at <= %s";
            $prepare[] = $args['date_to'];
        }
        
        // Build query
        $sql = "SELECT * FROM {$orders_table}";
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $prepare[] = $args['limit'];
            $prepare[] = $args['offset'];
        }
        
        if (!empty($prepare)) {
            $orders = $wpdb->get_results($wpdb->prepare($sql, $prepare), ARRAY_A);
        } else {
            $orders = $wpdb->get_results($sql, ARRAY_A);
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$orders_table}";
        if (!empty($where)) {
            $count_sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        if (!empty($prepare)) {
            $total = $wpdb->get_var($wpdb->prepare($count_sql, $prepare));
        } else {
            $total = $wpdb->get_var($count_sql);
        }
        
        return array(
            'orders' => $orders,
            'total' => $total,
            'limit' => $args['limit'],
            'offset' => $args['offset'],
        );
    }
}