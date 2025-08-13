<?php
/**
 * Enhanced Tracking Engine class for AI Order Tracker
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
     * Create new order tracking with automatic zone detection
     *
     * @param array $order_data Order data
     * @return string|false Tracking ID or false on failure
     */
    public static function create_order_tracking($order_data) {
        // Generate tracking ID if not provided
        if (empty($order_data['tracking_id'])) {
            $order_data['tracking_id'] = self::generate_tracking_id();
        }

        // Get admin location (default origin)
        $admin_location = get_option('aiot_admin_location', array(
            'address' => 'Admin Warehouse',
            'city' => 'Admin City',
            'state' => 'Admin State',
            'country' => 'US',
            'coordinates' => array(39.8283, -98.5795) // Default to US center
        ));

        // Set default origin address
        if (empty($order_data['origin_address'])) {
            $order_data['origin_address'] = $admin_location['address'] . ', ' . $admin_location['city'] . ', ' . $admin_location['state'] . ', ' . $admin_location['country'];
        }

        // Set default values
        $order_data['status'] = isset($order_data['status']) ? $order_data['status'] : 'processing';
        $order_data['progress'] = self::calculate_progress($order_data['status']);
        $order_data['current_step'] = self::get_status_step($order_data['status']);
        
        // Calculate estimated delivery based on zone
        if (empty($order_data['estimated_delivery'])) {
            $order_data['estimated_delivery'] = self::calculate_estimated_delivery($order_data);
        }

        // Create order in database
        $order_id = AIOT_Database::create_order($order_data);
        
        if (!$order_id) {
            return false;
        }

        // Generate initial tracking events with route simulation
        self::generate_tracking_events_with_route($order_id, $order_data);

        return $order_data['tracking_id'];
    }

    /**
     * Update order status with real tracking ID support
     *
     * @param string $tracking_id Tracking ID
     * @param string $status New status
     * @param array $additional_data Additional data including real tracking info
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

        // Add real tracking information if provided
        if (isset($additional_data['real_tracking_id'])) {
            $update_data['carrier'] = isset($additional_data['carrier']) ? $additional_data['carrier'] : 'External Courier';
            $update_data['carrier_url'] = isset($additional_data['carrier_url']) ? $additional_data['carrier_url'] : '';
            
            // Store real tracking info in meta
            $meta = json_decode($order['meta'], true);
            if (!is_array($meta)) {
                $meta = array();
            }
            $meta['real_tracking_id'] = $additional_data['real_tracking_id'];
            $meta['real_carrier'] = isset($additional_data['carrier']) ? $additional_data['carrier'] : '';
            $meta['real_tracking_url'] = isset($additional_data['carrier_url']) ? $additional_data['carrier_url'] : '';
            $update_data['meta'] = wp_json_encode($meta);
        }

        // Add additional data
        $update_data = array_merge($update_data, $additional_data);

        // Update order in database
        $result = AIOT_Database::update_order_status($tracking_id, $status, $update_data);

        if ($result) {
            // Add tracking event
            $event_description = self::get_status_description($status);
            
            // If real tracking is provided and status is 'shipped' or later, add special message
            if (isset($additional_data['real_tracking_id']) && in_array($status, array('shipped', 'in_transit', 'out_for_delivery', 'delivered'))) {
                $carrier_name = isset($additional_data['carrier']) ? $additional_data['carrier'] : 'External Courier';
                $event_description = sprintf(__('Package picked up by %s. Real tracking ID: %s', 'ai-order-tracker'), $carrier_name, $additional_data['real_tracking_id']);
            }
            
            AIOT_Database::add_tracking_event(array(
                'order_id' => $order['id'],
                'event_type' => 'status_update',
                'event_status' => $status,
                'location' => isset($additional_data['location']) ? $additional_data['location'] : '',
                'description' => $event_description,
            ));
        }

        return $result;
    }

    /**
     * Get order tracking information with hybrid display
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

        // Get real tracking info from meta
        $meta = json_decode($order['meta'], true);
        $real_tracking_info = array();
        if (is_array($meta)) {
            $real_tracking_info = array(
                'real_tracking_id' => isset($meta['real_tracking_id']) ? $meta['real_tracking_id'] : '',
                'real_carrier' => isset($meta['real_carrier']) ? $meta['real_carrier'] : '',
                'real_tracking_url' => isset($meta['real_tracking_url']) ? $meta['real_tracking_url'] : '',
            );
        }

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
            'real_tracking_info' => $real_tracking_info,
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
        );

        // Decode JSON fields if they exist
        if (!empty($order['tracking_history'])) {
            $tracking_info['tracking_history'] = json_decode($order['tracking_history'], true);
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
     * Calculate estimated delivery date based on zone
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
     * Generate tracking events with route simulation
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @return bool True on success
     */
    public static function generate_tracking_events_with_route($order_id, $order_data) {
        $origin_address = isset($order_data['origin_address']) ? $order_data['origin_address'] : '';
        $destination_address = isset($order_data['destination_address']) ? $order_data['destination_address'] : '';
        
        // Parse addresses to get cities
        $origin_city = self::extract_city_from_address($origin_address);
        $destination_city = self::extract_city_from_address($destination_address);
        
        // Get route cities
        $route_cities = self::generate_route_cities($origin_city, $destination_city);
        
        // Get zone for delivery days calculation
        $zone = self::get_delivery_zone($destination_address);
        $delivery_days = $zone ? $zone['delivery_days'] : get_option('aiot_default_delivery_days', 3);
        
        // Generate events based on route and delivery days
        $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
        $current_status = isset($order_data['status']) ? $order_data['status'] : 'processing';
        $current_index = array_search($current_status, $statuses);
        
        if ($current_index === false) {
            $current_index = 0;
        }
        
        $base_time = time() - (86400 * $current_index);
        $time_interval = max(3600, (86400 * $delivery_days) / count($statuses));
        
        for ($i = 0; $i <= $current_index; $i++) {
            $status = $statuses[$i];
            $event_time = date('Y-m-d H:i:s', $base_time + ($i * $time_interval));
            
            // Get location for this status
            $location_index = min($i, count($route_cities) - 1);
            $location = $route_cities[$location_index];
            
            // Create event
            AIOT_Database::add_tracking_event(array(
                'order_id' => $order_id,
                'event_type' => 'status_update',
                'event_status' => $status,
                'location' => $location,
                'description' => self::get_status_description($status),
                'timestamp' => $event_time,
                'latitude' => null,
                'longitude' => null,
            ));
        }
        
        return true;
    }

    /**
     * Extract city from address
     *
     * @param string $address Address
     * @return string City name
     */
    private static function extract_city_from_address($address) {
        // Simple city extraction - in real implementation, use geocoding API
        $parts = explode(',', $address);
        if (count($parts) > 1) {
            return trim($parts[0]);
        }
        return $address;
    }

    /**
     * Generate route cities between origin and destination
     *
     * @param string $origin_city Origin city
     * @param string $destination_city Destination city
     * @return array Route cities
     */
    private static function generate_route_cities($origin_city, $destination_city) {
        // Simplified route generation - in real implementation, use routing API
        $route = array();
        
        if ($origin_city) {
            $route[] = $origin_city;
        }
        
        // Add some intermediate cities for longer routes
        if ($origin_city && $destination_city && strtolower($origin_city) !== strtolower($destination_city)) {
            $intermediate_cities = array(
                'Distribution Center',
                'Regional Hub',
                'Local Facility',
            );
            
            foreach ($intermediate_cities as $city) {
                $route[] = $city;
            }
        }
        
        if ($destination_city) {
            $route[] = $destination_city;
        }
        
        return $route;
    }

    /**
     * Auto-update order status based on time elapsed
     *
     * @param string $tracking_id Tracking ID
     * @return bool True if status was updated
     */
    public static function auto_update_order_status($tracking_id) {
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            return false;
        }
        
        // Get zone for delivery days
        $zone = self::get_delivery_zone($order['destination_address']);
        $delivery_days = $zone ? $zone['delivery_days'] : get_option('aiot_default_delivery_days', 3);
        
        // Calculate time elapsed
        $created_time = strtotime($order['created_at']);
        $current_time = time();
        $elapsed_hours = ($current_time - $created_time) / 3600;
        
        // Calculate expected status based on elapsed time
        $expected_status = self::calculate_expected_status($elapsed_hours, $delivery_days);
        
        // Update if status is different
        if ($expected_status !== $order['status']) {
            return self::update_order_status($tracking_id, $expected_status);
        }
        
        return false;
    }

    /**
     * Calculate expected status based on elapsed time
     *
     * @param int $elapsed_hours Hours elapsed
     * @param int $delivery_days Total delivery days
     * @return string Expected status
     */
    private static function calculate_expected_status($elapsed_hours, $delivery_days) {
        $total_hours = $delivery_days * 24;
        $progress_ratio = min(1, $elapsed_hours / $total_hours);
        
        if ($progress_ratio < 0.1) {
            return 'processing';
        } elseif ($progress_ratio < 0.2) {
            return 'confirmed';
        } elseif ($progress_ratio < 0.35) {
            return 'packed';
        } elseif ($progress_ratio < 0.5) {
            return 'shipped';
        } elseif ($progress_ratio < 0.7) {
            return 'in_transit';
        } elseif ($progress_ratio < 0.9) {
            return 'out_for_delivery';
        } else {
            return 'delivered';
        }
    }

    /**
     * Get tracking statistics
     *
     * @return array Tracking statistics
     */
    public static function get_tracking_statistics() {
        global $wpdb;
        $orders_table = AIOT_Database::get_table_name('orders');
        
        $stats = array(
            'total_orders' => 0,
            'active_orders' => 0,
            'delivered_orders' => 0,
            'average_delivery_time' => 0,
            'status_distribution' => array(),
        );
        
        // Get basic stats
        $stats['total_orders'] = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table");
        $stats['active_orders'] = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status NOT IN ('delivered', 'failed', 'returned')");
        $stats['delivered_orders'] = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status = 'delivered'");
        
        // Get status distribution
        $statuses = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $orders_table GROUP BY status", ARRAY_A);
        foreach ($statuses as $status) {
            $stats['status_distribution'][$status['status']] = intval($status['count']);
        }
        
        // Calculate average delivery time (simplified)
        if ($stats['delivered_orders'] > 0) {
            $avg_time = $wpdb->get_var("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) FROM $orders_table WHERE status = 'delivered'");
            $stats['average_delivery_time'] = round($avg_time / 24, 1); // Convert to days
        }
        
        return $stats;
    }
}