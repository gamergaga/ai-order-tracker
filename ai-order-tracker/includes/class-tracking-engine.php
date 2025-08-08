<?php
/**
 * Tracking engine class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Tracking_Engine
 */
class AIOT_Tracking_Engine {

    /**
     * Generate unique tracking ID
     *
     * @param string $prefix Prefix for tracking ID
     * @return string Unique tracking ID
     */
    public static function generate_tracking_id($prefix = null) {
        // Get settings
        $general_settings = get_option('aiot_general_settings', array());
        
        // Use provided prefix or get from settings
        if ($prefix === null) {
            $prefix = isset($general_settings['aiot_tracking_id_prefix']) ? $general_settings['aiot_tracking_id_prefix'] : 'AIOT';
        }
        
        // Get format from settings
        $format = isset($general_settings['aiot_tracking_id_format']) ? $general_settings['aiot_tracking_id_format'] : 'alphanumeric';
        
        // Generate based on format
        switch ($format) {
            case 'numeric':
                $tracking_id = self::generate_numeric_tracking_id($prefix);
                break;
            case 'custom':
                $tracking_id = self::generate_custom_tracking_id($prefix);
                break;
            case 'alphanumeric':
            default:
                $tracking_id = self::generate_alphanumeric_tracking_id($prefix);
                break;
        }
        
        return $tracking_id;
    }
    
    /**
     * Generate alphanumeric tracking ID
     *
     * @param string $prefix Prefix for tracking ID
     * @return string Tracking ID
     */
    private static function generate_alphanumeric_tracking_id($prefix) {
        $random = mt_rand(10000, 99999);
        $timestamp = time();
        $hash = substr(md5($timestamp . $random), 0, 5);
        return strtoupper($prefix . '-' . $random . $hash);
    }
    
    /**
     * Generate numeric tracking ID
     *
     * @param string $prefix Prefix for tracking ID
     * @return string Tracking ID
     */
    private static function generate_numeric_tracking_id($prefix) {
        $random = mt_rand(100000000, 999999999);
        return $prefix . $random;
    }
    
    /**
     * Generate custom format tracking ID
     *
     * @param string $prefix Prefix for tracking ID
     * @return string Tracking ID
     */
    private static function generate_custom_tracking_id($prefix) {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $random = mt_rand(1000, 9999);
        $sequence = mt_rand(100, 999);
        
        return strtoupper($prefix . $year . $month . $day . $random . $sequence);
    }

    /**
     * Create new order tracking
     *
     * @param array $order_data Order data
     * @return string|false Tracking ID or false on failure
     */
    public static function create_order_tracking($order_data) {
        // Generate tracking ID if not provided
        if (empty($order_data['tracking_id'])) {
            $order_data['tracking_id'] = self::generate_tracking_id();
        }

        // Set default values
        $order_data['status'] = isset($order_data['status']) ? $order_data['status'] : 'processing';
        $order_data['progress'] = self::calculate_progress($order_data['status']);
        $order_data['current_step'] = self::get_status_step($order_data['status']);
        
        // Calculate estimated delivery
        if (empty($order_data['estimated_delivery'])) {
            $order_data['estimated_delivery'] = self::calculate_estimated_delivery($order_data);
        }

        // Create order in database
        $order_id = AIOT_Database::create_order($order_data);
        
        if (!$order_id) {
            return false;
        }

        // Generate initial tracking events
        self::generate_tracking_events($order_id, $order_data['status']);

        return $order_data['tracking_id'];
    }

    /**
     * Update order status
     *
     * @param string $tracking_id Tracking ID
     * @param string $status New status
     * @param array $additional_data Additional data
     * @return bool True on success
     */
    public static function update_order_status($tracking_id, $status, $additional_data = array()) {
        // Get order data
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            return false;
        }

        // Calculate new progress and step
        $update_data = array(
            'progress' => self::calculate_progress($status),
            'current_step' => self::get_status_step($status),
        );

        // Add additional data
        $update_data = array_merge($update_data, $additional_data);

        // Update order in database
        $result = AIOT_Database::update_order_status($tracking_id, $status, $update_data);

        if ($result) {
            // Add tracking event
            AIOT_Database::add_tracking_event(array(
                'order_id' => $order['id'],
                'event_type' => 'status_update',
                'event_status' => $status,
                'location' => isset($additional_data['location']) ? $additional_data['location'] : '',
                'description' => isset($additional_data['description']) ? $additional_data['description'] : self::get_status_description($status),
            ));
        }

        return $result;
    }

    /**
     * Get order tracking information
     *
     * @param string $tracking_id Tracking ID
     * @return array|false Tracking data or false if not found
     */
    public static function get_tracking_info($tracking_id) {
        // Validate tracking ID
        $tracking_id = AIOT_Security::sanitize_tracking_id($tracking_id);
        
        if (!$tracking_id) {
            return false;
        }

        // Get order data
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            return false;
        }

        // Get tracking events
        $events = AIOT_Database::get_tracking_events($order['id']);

        // Format response
        $tracking_info = array(
            'tracking_id' => $order['tracking_id'],
            'order_id' => $order['order_id'],
            'status' => $order['status'],
            'status_info' => self::get_order_status($order['status']),
            'progress' => $order['progress'],
            'current_step' => $order['current_step'],
            'estimated_delivery' => $order['estimated_delivery'],
            'location' => $order['location'],
            'carrier' => $order['carrier'],
            'carrier_url' => $order['carrier_url'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'origin_address' => $order['origin_address'],
            'destination_address' => $order['destination_address'],
            'package_info' => array(
                'weight' => $order['weight'],
                'dimensions' => $order['dimensions'],
                'package_type' => $order['package_type'],
                'service_type' => $order['service_type'],
            ),
            'tracking_events' => $events,
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
        );

        // Decode JSON fields if they exist
        if (!empty($order['tracking_history'])) {
            $tracking_info['tracking_history'] = json_decode($order['tracking_history'], true);
        }

        if (!empty($order['meta'])) {
            $tracking_info['meta'] = json_decode($order['meta'], true);
        }

        return $tracking_info;
    }

    /**
     * Get order status information
     *
     * @param string $status Order status
     * @return array Status information
     */
    public static function get_order_status($status) {
        $statuses = array(
            'processing' => array(
                'label' => __('Processing', 'ai-order-tracker'),
                'color' => '#ffc107',
                'icon' => '⚙️',
                'step' => 1,
                'description' => __('Order is being processed', 'ai-order-tracker'),
            ),
            'confirmed' => array(
                'label' => __('Order Confirmed', 'ai-order-tracker'),
                'color' => '#17a2b8',
                'icon' => '✅',
                'step' => 2,
                'description' => __('Order has been confirmed', 'ai-order-tracker'),
            ),
            'packed' => array(
                'label' => __('Packed', 'ai-order-tracker'),
                'color' => '#6f42c1',
                'icon' => '📦',
                'step' => 3,
                'description' => __('Package has been packed', 'ai-order-tracker'),
            ),
            'shipped' => array(
                'label' => __('Shipped', 'ai-order-tracker'),
                'color' => '#007bff',
                'icon' => '🚚',
                'step' => 4,
                'description' => __('Package has been shipped', 'ai-order-tracker'),
            ),
            'in_transit' => array(
                'label' => __('In Transit', 'ai-order-tracker'),
                'color' => '#fd7e14',
                'icon' => '🚛',
                'step' => 5,
                'description' => __('Package is in transit', 'ai-order-tracker'),
            ),
            'out_for_delivery' => array(
                'label' => __('Out for Delivery', 'ai-order-tracker'),
                'color' => '#20c997',
                'icon' => '🏃',
                'step' => 6,
                'description' => __('Package is out for delivery', 'ai-order-tracker'),
            ),
            'delivered' => array(
                'label' => __('Delivered', 'ai-order-tracker'),
                'color' => '#28a745',
                'icon' => '🎉',
                'step' => 7,
                'description' => __('Package has been delivered', 'ai-order-tracker'),
            ),
            'failed' => array(
                'label' => __('Delivery Failed', 'ai-order-tracker'),
                'color' => '#dc3545',
                'icon' => '❌',
                'step' => 0,
                'description' => __('Delivery attempt failed', 'ai-order-tracker'),
            ),
            'returned' => array(
                'label' => __('Returned', 'ai-order-tracker'),
                'color' => '#6c757d',
                'icon' => '🔄',
                'step' => 0,
                'description' => __('Package has been returned', 'ai-order-tracker'),
            ),
        );

        return isset($statuses[$status]) ? $statuses[$status] : $statuses['processing'];
    }

    /**
     * Calculate progress percentage based on status
     *
     * @param string $status Order status
     * @return int Progress percentage
     */
    public static function calculate_progress($status) {
        $progress_map = array(
            'processing' => 10,
            'confirmed' => 20,
            'packed' => 35,
            'shipped' => 50,
            'in_transit' => 70,
            'out_for_delivery' => 90,
            'delivered' => 100,
            'failed' => 0,
            'returned' => 0,
        );

        return isset($progress_map[$status]) ? $progress_map[$status] : 0;
    }

    /**
     * Get status step number
     *
     * @param string $status Order status
     * @return int Step number
     */
    public static function get_status_step($status) {
        $step_map = array(
            'processing' => 1,
            'confirmed' => 2,
            'packed' => 3,
            'shipped' => 4,
            'in_transit' => 5,
            'out_for_delivery' => 6,
            'delivered' => 7,
            'failed' => 0,
            'returned' => 0,
        );

        return isset($step_map[$status]) ? $step_map[$status] : 0;
    }

    /**
     * Get status description
     *
     * @param string $status Order status
     * @return string Description
     */
    public static function get_status_description($status) {
        $status_info = self::get_order_status($status);
        return $status_info['description'];
    }

    /**
     * Calculate estimated delivery date
     *
     * @param array $order_data Order data
     * @return string Estimated delivery date
     */
    public static function calculate_estimated_delivery($order_data) {
        $status = isset($order_data['status']) ? $order_data['status'] : 'processing';
        $destination = isset($order_data['destination_address']) ? $order_data['destination_address'] : '';
        
        // Get delivery zone based on destination
        $zone = self::get_delivery_zone($destination);
        
        if ($zone) {
            $delivery_days = $zone['delivery_days'];
        } else {
            $delivery_days = get_option('aiot_default_delivery_days', 3);
        }

        // Calculate based on current status
        $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
        $current_index = array_search($status, $statuses);
        
        if ($current_index === false) {
            $current_index = 0;
        }

        $remaining_steps = count($statuses) - $current_index - 1;
        $adjusted_days = max(1, $delivery_days - $remaining_steps);

        $delivery_date = new DateTime();
        $delivery_date->add(new DateInterval('P' . $adjusted_days . 'D'));

        return $delivery_date->format('Y-m-d');
    }

    /**
     * Get delivery zone based on address
     *
     * @param string $address Address
     * @return array|false Zone data or false if not found
     */
    public static function get_delivery_zone($address) {
        // Simple implementation - in real version, this would be more sophisticated
        $zones = AIOT_Database::get_zones(array('is_active' => true));
        
        foreach ($zones as $zone) {
            // Check if address matches zone criteria
            $countries = json_decode($zone['countries'], true);
            $states = json_decode($zone['states'], true);
            $cities = json_decode($zone['cities'], true);
            
            if (is_array($countries) && self::address_contains($address, $countries)) {
                return $zone;
            }
            
            if (is_array($states) && self::address_contains($address, $states)) {
                return $zone;
            }
            
            if (is_array($cities) && self::address_contains($address, $cities)) {
                return $zone;
            }
        }
        
        return false;
    }

    /**
     * Check if address contains any of the specified terms
     *
     * @param string $address Address
     * @param array $terms Terms to search for
     * @return bool True if match found
     */
    private static function address_contains($address, $terms) {
        foreach ($terms as $term) {
            if (stripos($address, $term) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate tracking events for simulation with city-based route
     *
     * @param int $order_id Order ID
     * @param string $status Current status
     * @param string $origin_city Origin city
     * @param string $destination_city Destination city
     * @return bool True on success
     */
    public static function generate_tracking_events($order_id, $status, $origin_city = '', $destination_city = '') {
        $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
        
        $current_index = array_search($status, $statuses);
        if ($current_index === false) {
            $current_index = 0;
        }

        // Generate route cities if origin and destination are provided
        $route_cities = self::generate_route_cities($origin_city, $destination_city);
        
        $base_time = time() - (86400 * $current_index);

        for ($i = 0; $i <= $current_index; $i++) {
            $event_time = date('Y-m-d H:i:s', $base_time + (86400 * $i));
            $event_status = $statuses[$i];
            
            // Get location from route or use default
            $location = isset($route_cities[$i]) ? $route_cities[$i] : self::get_default_location($i);
            
            AIOT_Database::add_tracking_event(array(
                'order_id' => $order_id,
                'event_type' => 'status_update',
                'event_status' => $event_status,
                'location' => $location,
                'description' => self::get_status_description($event_status),
                'timestamp' => $event_time,
            ));
        }

        return true;
    }

    /**
     * Generate route cities between origin and destination
     *
     * @param string $origin_city Origin city
     * @param string $destination_city Destination city
     * @return array Route cities
     */
    private static function generate_route_cities($origin_city, $destination_city) {
        $route = array();
        
        if (empty($origin_city) || empty($destination_city)) {
            return self::get_default_locations();
        }

        // Load cities data from assets
        $cities_data = self::load_cities_data();
        
        // Add origin city
        $route[] = $origin_city;
        
        // Generate intermediate cities (max 8 intermediate cities)
        $intermediate_cities = self::get_intermediate_cities($origin_city, $destination_city, $cities_data);
        $route = array_merge($route, $intermediate_cities);
        
        // Add destination city
        $route[] = $destination_city;
        
        // Limit to 10 cities total
        return array_slice($route, 0, 10);
    }

    /**
     * Load cities data from assets
     *
     * @return array Cities data
     */
    private static function load_cities_data() {
        $cities_file = AIOT_PATH . 'assets/geo/states-world.json';
        $countries_file = AIOT_PATH . 'assets/geo/countries.json';
        
        $cities_data = array();
        
        if (file_exists($cities_file)) {
            $cities_content = file_get_contents($cities_file);
            if ($cities_content) {
                // Parse GeoJSON data
                $geo_data = json_decode($cities_content, true);
                if ($geo_data && isset($geo_data['features'])) {
                    foreach ($geo_data['features'] as $feature) {
                        if (isset($feature['properties']['name'])) {
                            $cities_data[] = $feature['properties']['name'];
                        }
                    }
                }
            }
        }
        
        return $cities_data;
    }

    /**
     * Get intermediate cities between origin and destination
     *
     * @param string $origin Origin city
     * @param string $destination Destination city
     * @param array $cities_data Available cities data
     * @return array Intermediate cities
     */
    private static function get_intermediate_cities($origin, $destination, $cities_data) {
        $intermediate = array();
        
        // Simple implementation - in real version, this would use geospatial data
        // For now, we'll use some common hub cities
        $hub_cities = array(
            'Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata',
            'Pune', 'Hyderabad', 'Ahmedabad', 'Jaipur', 'Lucknow'
        );
        
        // Shuffle and take random hub cities
        shuffle($hub_cities);
        $intermediate = array_slice($hub_cities, 0, min(8, count($hub_cities)));
        
        return $intermediate;
    }

    /**
     * Get default locations for tracking events
     *
     * @return array Default locations
     */
    private static function get_default_locations() {
        return array(
            'Processing Center',
            'Distribution Hub',
            'Regional Facility',
            'Local Depot',
            'Delivery Station',
            'Customer Address'
        );
    }

    /**
     * Get default location by index
     *
     * @param int $index Location index
     * @return string Location name
     */
    private static function get_default_location($index) {
        $locations = self::get_default_locations();
        return isset($locations[$index]) ? $locations[$index] : 'Unknown';
    }

    /**
     * Simulate tracking progress
     *
     * @param string $tracking_id Tracking ID
     * @return bool True on success
     */
    public static function simulate_progress($tracking_id) {
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            return false;
        }

        $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
        $current_index = array_search($order['status'], $statuses);
        
        if ($current_index === false || $current_index >= count($statuses) - 1) {
            return false; // Already at final status
        }

        $new_status = $statuses[$current_index + 1];
        
        return self::update_order_status($tracking_id, $new_status);
    }

    /**
     * Get tracking statistics
     *
     * @return array Statistics
     */
    public static function get_statistics() {
        global $wpdb;
        
        $orders_table = AIOT_Database::get_table_name('orders');
        
        $stats = array(
            'total_orders' => 0,
            'delivered_orders' => 0,
            'in_transit_orders' => 0,
            'processing_orders' => 0,
            'failed_orders' => 0,
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
        }

        return $stats;
    }
}