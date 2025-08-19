<?php
/**
 * Enhanced Real-time Tracking class for AI Order Tracker
 *
 * Handles hybrid tracking with simulation and real courier integration
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

class AIOT_Real_Time_Tracking {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_aiot_real_time_tracking', array($this, 'ajax_real_time_tracking'));
        add_action('wp_ajax_nopriv_aiot_real_time_tracking', array($this, 'ajax_real_time_tracking'));
        add_action('wp_ajax_aiot_fetch_woocommerce_orders', array($this, 'ajax_fetch_woocommerce_orders'));
        add_action('wp_ajax_aiot_add_real_tracking', array($this, 'ajax_add_real_tracking'));
    }
    
    /**
     * AJAX handler for real-time tracking
     */
    public function ajax_real_time_tracking() {
        // Verify nonce
        if (!check_ajax_referer('aiot_tracking_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Get and sanitize input
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        $courier_slug = sanitize_text_field($_POST['courier']);
        
        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }
        
        // Get tracking data
        $result = $this->get_tracking_data($tracking_id, $courier_slug);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for fetching WooCommerce orders
     */
    public function ajax_fetch_woocommerce_orders() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get WooCommerce orders
        $orders = $this->get_woocommerce_orders();
        
        wp_send_json_success($orders);
    }
    
    /**
     * AJAX handler for adding real tracking to order
     */
    public function ajax_add_real_tracking() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize input
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        $real_tracking_id = sanitize_text_field($_POST['real_tracking_id']);
        $courier = sanitize_text_field($_POST['courier']);
        
        if (empty($tracking_id) || empty($real_tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID and Real Tracking ID are required.', 'ai-order-tracker')));
        }
        
        // Add real tracking
        $result = $this->add_real_tracking_to_order($tracking_id, $real_tracking_id, $courier);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Get tracking data with hybrid simulation
     *
     * @param string $tracking_id Tracking ID
     * @param string $courier_slug Courier slug
     * @return array|WP_Error Tracking data
     */
    public function get_tracking_data($tracking_id, $courier_slug = '') {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Get order information
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', __('Order not found.', 'ai-order-tracker'));
        }
        
        // Get courier information
        $courier = null;
        if (!empty($courier_slug)) {
            $courier = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $couriers_table WHERE slug = %s AND is_active = 1",
                $courier_slug
            ));
        }
        
        // If no specific courier, try to auto-detect
        if (!$courier) {
            $courier = $this->auto_detect_courier($tracking_id);
        }
        
        // Get tracking info with hybrid simulation
        $tracking_info = AIOT_Tracking_Engine::get_tracking_info($tracking_id);
        
        if (!$tracking_info) {
            return new WP_Error('tracking_info_not_found', __('Tracking information not found.', 'ai-order-tracker'));
        }
        
        // Auto-update status if needed
        AIOT_Tracking_Engine::auto_update_order_status($tracking_id);
        
        // Get fresh tracking info after potential update
        $tracking_info = AIOT_Tracking_Engine::get_tracking_info($tracking_id);
        
        // Add courier information
        if ($courier) {
            $tracking_info['courier'] = array(
                'name' => $courier->name,
                'slug' => $courier->slug,
                'website' => $this->get_courier_setting($courier, 'website'),
                'phone' => $this->get_courier_setting($courier, 'phone'),
                'image' => $this->get_courier_setting($courier, 'image'),
                'country' => $this->get_courier_setting($courier, 'country'),
            );
        }
        
        // Generate tracking URL if available
        if ($courier && !empty($courier->url_pattern)) {
            $tracking_info['tracking_url'] = $this->generate_tracking_url($courier, $tracking_id);
        }
        
        // Add map coordinates if available
        $tracking_info['map_coordinates'] = $this->get_map_coordinates($tracking_info['tracking_events']);
        
        return $tracking_info;
    }
    
    /**
     * Get WooCommerce orders for tracking integration
     *
     * @return array WooCommerce orders
     */
    private function get_woocommerce_orders() {
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        $args = array(
            'status' => array('processing', 'completed'),
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $orders = wc_get_orders($args);
        $result = array();
        
        foreach ($orders as $order) {
            $order_data = array(
                'id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'total' => $order->get_total(),
                'customer_name' => $order->get_formatted_billing_full_name(),
                'customer_email' => $order->get_billing_email(),
                'shipping_address' => $order->get_formatted_shipping_address(),
                'has_tracking' => $this->order_has_tracking($order->get_id()),
            );
            
            $result[] = $order_data;
        }
        
        return $result;
    }
    
    /**
     * Check if WooCommerce order has tracking
     *
     * @param int $order_id Order ID
     * @return bool Has tracking
     */
    private function order_has_tracking($order_id) {
        global $wpdb;
        $orders_table = AIOT_Database::get_table_name('orders');
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $orders_table WHERE order_id = %s",
            $order_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Add real tracking to order
     *
     * @param string $tracking_id Internal tracking ID
     * @param string $real_tracking_id Real courier tracking ID
     * @param string $courier Courier name
     * @return array|WP_Error Result
     */
    private function add_real_tracking_to_order($tracking_id, $real_tracking_id, $courier) {
        // Get order
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', __('Order not found.', 'ai-order-tracker'));
        }
        
        // Get courier information
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        $courier_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $couriers_table WHERE slug = %s AND is_active = 1",
            $courier
        ));
        
        if (!$courier_data) {
            return new WP_Error('courier_not_found', __('Courier not found.', 'ai-order-tracker'));
        }
        
        // Generate tracking URL
        $tracking_url = $this->generate_tracking_url($courier_data, $real_tracking_id);
        
        // Update order with real tracking information
        $update_data = array(
            'real_tracking_id' => $real_tracking_id,
            'carrier' => $courier_data->name,
            'carrier_url' => $tracking_url,
        );
        
        // Update status to 'shipped' if not already shipped or later
        $current_status = $order['status'];
        $statuses = array('shipped', 'in_transit', 'out_for_delivery', 'delivered');
        
        if (!in_array($current_status, $statuses)) {
            $update_data['status'] = 'shipped';
        }
        
        $result = AIOT_Tracking_Engine::update_order_status($tracking_id, $update_data['status'], $update_data);
        
        if (!$result) {
            return new WP_Error('update_failed', __('Failed to update order.', 'ai-order-tracker'));
        }
        
        return array(
            'message' => __('Real tracking added successfully.', 'ai-order-tracker'),
            'tracking_url' => $tracking_url,
            'courier' => $courier_data->name,
        );
    }
    
    /**
     * Auto-detect courier based on tracking ID format
     *
     * @param string $tracking_id Tracking ID
     * @return object|null Courier object
     */
    private function auto_detect_courier($tracking_id) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Common tracking patterns
        $patterns = array(
            'ups' => '/^1Z[0-9A-Z]{16}$/',
            'fedex' => '/^[0-9]{12,14}$/',
            'dhl' => '/^[0-9]{10,11}$/',
            'usps' => '/^[0-9]{20,22}$/',
            'canada-post' => '/^[A-Z0-9]{16}$/',
            'royal-mail' => '/^[A-Z0-9]{9,13}$/',
            'dpd' => '/^[0-9]{11,14}$/',
            'hermes' => '/^[0-9]{16}$/',
        );
        
        foreach ($patterns as $slug => $pattern) {
            if (preg_match($pattern, $tracking_id)) {
                $courier = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $couriers_table WHERE slug = %s AND is_active = 1",
                    $slug
                ));
                
                if ($courier) {
                    return $courier;
                }
            }
        }
        
        // Default to first active courier if no pattern matches
        return $wpdb->get_row("SELECT * FROM $couriers_table WHERE is_active = 1 ORDER BY name LIMIT 1");
    }
    
    /**
     * Generate tracking URL
     *
     * @param object $courier Courier object
     * @param string $tracking_id Tracking ID
     * @return string Tracking URL
     */
    private function generate_tracking_url($courier, $tracking_id) {
        $url_pattern = $courier->url_pattern;
        
        if (empty($url_pattern)) {
            return '';
        }
        
        // Replace placeholder with actual tracking ID
        $tracking_url = str_replace('{tracking_id}', $tracking_id, $url_pattern);
        
        return esc_url($tracking_url);
    }
    
    /**
     * Get courier setting
     *
     * @param object $courier Courier object
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    private function get_courier_setting($courier, $key, $default = '') {
        $settings = json_decode($courier->settings, true);
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Get map coordinates from tracking events
     *
     * @param array $events Tracking events
     * @return array Map coordinates
     */
    private function get_map_coordinates($events) {
        $coordinates = array();
        
        foreach ($events as $event) {
            if (!empty($event['latitude']) && !empty($event['longitude'])) {
                $coordinates[] = array(
                    'lat' => floatval($event['latitude']),
                    'lng' => floatval($event['longitude']),
                    'location' => $event['location'],
                    'status' => $event['event_status'],
                    'timestamp' => $event['timestamp'],
                );
            }
        }
        
        return $coordinates;
    }
    
    /**
     * Get available couriers for tracking
     *
     * @return array Available couriers
     */
    public function get_available_couriers() {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $couriers = $wpdb->get_results(
            "SELECT name, slug, description 
            FROM $couriers_table 
            WHERE is_active = 1 
            ORDER BY name"
        );
        
        return $couriers;
    }
    
    /**
     * Validate tracking ID format for a specific courier
     *
     * @param string $tracking_id Tracking ID
     * @param string $courier_slug Courier slug
     * @return bool Is valid
     */
    public function validate_tracking_id($tracking_id, $courier_slug) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $courier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $couriers_table WHERE slug = %s AND is_active = 1",
            $courier_slug
        ));
        
        if (!$courier) {
            return false;
        }
        
        $settings = json_decode($courier->settings, true);
        if (!isset($settings['tracking_format']) || empty($settings['tracking_format'])) {
            return true; // No specific format required
        }
        
        return preg_match($settings['tracking_format'], $tracking_id) === 1;
    }
    
    /**
     * Get tracking statistics
     *
     * @return array Tracking statistics
     */
    public function get_tracking_stats() {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $total_couriers = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table");
        $active_couriers = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table WHERE is_active = 1");
        $couriers_with_urls = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table WHERE url_pattern != ''");
        
        return array(
            'total_couriers' => intval($total_couriers),
            'active_couriers' => intval($active_couriers),
            'couriers_with_urls' => intval($couriers_with_urls),
        );
    }
}