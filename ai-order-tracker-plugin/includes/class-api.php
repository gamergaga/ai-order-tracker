<?php
/**
 * AJAX API handlers for order management
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_API
 */
class AIOT_API {

    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Order management AJAX handlers
        add_action('wp_ajax_aiot_add_order', array(__CLASS__, 'add_order'));
        add_action('wp_ajax_nopriv_aiot_add_order', array(__CLASS__, 'add_order'));
        
        add_action('wp_ajax_aiot_get_order_data', array(__CLASS__, 'get_order_data'));
        add_action('wp_ajax_nopriv_aiot_get_order_data', array(__CLASS__, 'get_order_data'));
        
        add_action('wp_ajax_aiot_update_order', array(__CLASS__, 'update_order'));
        add_action('wp_ajax_nopriv_aiot_update_order', array(__CLASS__, 'update_order'));
        
        add_action('wp_ajax_aiot_delete_order', array(__CLASS__, 'delete_order'));
        add_action('wp_ajax_nopriv_aiot_delete_order', array(__CLASS__, 'delete_order'));
        
        add_action('wp_ajax_aiot_get_order_details', array(__CLASS__, 'get_order_details'));
        add_action('wp_ajax_nopriv_aiot_get_order_details', array(__CLASS__, 'get_order_details'));
    }

    /**
     * Add new order
     */
    public static function add_order() {
        // Check nonce
        check_ajax_referer('aiot_add_order', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize form data
        $order_id = sanitize_text_field($_POST['order_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $location = sanitize_text_field($_POST['location']);
        $status = sanitize_text_field($_POST['status']);
        $carrier = sanitize_text_field($_POST['carrier']);
        $estimated_delivery = sanitize_text_field($_POST['estimated_delivery']);
        
        // Validate required fields
        if (empty($order_id) || empty($location) || empty($status)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'ai-order-tracker')));
        }
        
        // Generate tracking ID
        $tracking_id = aiot_generate_tracking_id();
        
        // Prepare order data
        $order_data = array(
            'tracking_id' => $tracking_id,
            'order_id' => $order_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'location' => $location,
            'status' => $status,
            'carrier' => $carrier,
            'estimated_delivery' => !empty($estimated_delivery) ? $estimated_delivery : null,
            'progress' => aiot_calculate_progress($status),
        );
        
        // Create order
        $result = AIOT_Database::create_order($order_data);
        
        if ($result) {
            // Generate tracking events
            $events = aiot_generate_tracking_events($tracking_id, $status);
            
            // Add tracking events
            foreach ($events as $event) {
                $event['order_id'] = $result;
                AIOT_Database::add_tracking_event($event);
            }
            
            wp_send_json_success(array(
                'message' => __('Order added successfully.', 'ai-order-tracker'),
                'tracking_id' => $tracking_id,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to add order. Please try again.', 'ai-order-tracker')));
        }
    }

    /**
     * Get order data for editing
     */
    public static function get_order_data() {
        // Check nonce
        check_ajax_referer('aiot_edit_order', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        
        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }
        
        // Get order data
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if ($order) {
            wp_send_json_success(array(
                'order' => $order,
            ));
        } else {
            wp_send_json_error(array('message' => __('Order not found.', 'ai-order-tracker')));
        }
    }

    /**
     * Update order
     */
    public static function update_order() {
        // Check nonce
        check_ajax_referer('aiot_edit_order', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize form data
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        $order_id = sanitize_text_field($_POST['order_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $location = sanitize_text_field($_POST['location']);
        $status = sanitize_text_field($_POST['status']);
        $carrier = sanitize_text_field($_POST['carrier']);
        $estimated_delivery = sanitize_text_field($_POST['estimated_delivery']);
        
        // Validate required fields
        if (empty($tracking_id) || empty($order_id) || empty($location) || empty($status)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'ai-order-tracker')));
        }
        
        // Prepare update data
        $update_data = array(
            'order_id' => $order_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'location' => $location,
            'carrier' => $carrier,
            'estimated_delivery' => !empty($estimated_delivery) ? $estimated_delivery : null,
        );
        
        // Update order status
        $result = AIOT_Database::update_order_status($tracking_id, $status, $update_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Order updated successfully.', 'ai-order-tracker'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update order. Please try again.', 'ai-order-tracker')));
        }
    }

    /**
     * Delete order
     */
    public static function delete_order() {
        // Check nonce
        check_ajax_referer('aiot_delete_order', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        
        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }
        
        global $wpdb;
        $orders_table = AIOT_Database::get_table_name('orders');
        $events_table = AIOT_Database::get_table_name('tracking_events');
        
        // Get order ID first
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'ai-order-tracker')));
        }
        
        // Delete tracking events
        $wpdb->delete($events_table, array('order_id' => $order['id']), array('%d'));
        
        // Delete order
        $result = $wpdb->delete($orders_table, array('tracking_id' => $tracking_id), array('%s'));
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Order deleted successfully.', 'ai-order-tracker'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete order. Please try again.', 'ai-order-tracker')));
        }
    }

    /**
     * Get order details for viewing
     */
    public static function get_order_details() {
        // Check nonce
        check_ajax_referer('aiot_get_order_details', 'nonce');
        
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        
        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }
        
        // Get order data
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'ai-order-tracker')));
        }
        
        // Get tracking events
        $events = AIOT_Database::get_tracking_events($order['id']);
        
        // Get status info
        $status_info = aiot_get_order_status($order['status']);
        
        // Generate HTML for order details
        ob_start();
        ?>
        <div class="aiot-order-details">
            <div class="aiot-order-header">
                <h3><?php echo esc_html($order['tracking_id']); ?></h3>
                <div class="aiot-status-badge" style="background-color: <?php echo esc_attr($status_info['color']); ?>; color: white;">
                    <?php echo esc_html($status_info['label']); ?>
                </div>
            </div>
            
            <div class="aiot-order-info">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Order ID', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html($order['order_id']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Customer Name', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html($order['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Customer Email', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html($order['customer_email']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Location', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html($order['location']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Carrier', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html($order['carrier']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Estimated Delivery', 'ai-order-tracker'); ?></th>
                        <td><?php echo !empty($order['estimated_delivery']) ? esc_html(aiot_format_date($order['estimated_delivery'])) : __('Not set', 'ai-order-tracker'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Created', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html(aiot_format_date($order['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Updated', 'ai-order-tracker'); ?></th>
                        <td><?php echo esc_html(aiot_format_date($order['updated_at'])); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if (!empty($events)) : ?>
                <div class="aiot-tracking-events">
                    <h4><?php _e('Tracking Events', 'ai-order-tracker'); ?></h4>
                    <div class="aiot-timeline">
                        <?php foreach ($events as $event) : ?>
                            <div class="aiot-timeline-item">
                                <div class="aiot-timeline-marker"></div>
                                <div class="aiot-timeline-content">
                                    <div class="aiot-timeline-date"><?php echo esc_html(aiot_format_date($event['timestamp'])); ?></div>
                                    <div class="aiot-timeline-status"><?php echo esc_html($event['event_status']); ?></div>
                                    <div class="aiot-timeline-location"><?php echo esc_html($event['location']); ?></div>
                                    <div class="aiot-timeline-description"><?php echo esc_html($event['description']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
        ));
    }
}

// Initialize the API
AIOT_API::init();