<?php
/**
 * Simple tracking shortcode without Vue.js dependency
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Simple_Tracking_Shortcode
 */
class AIOT_Simple_Tracking_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('aiot_simple_tracking', array($this, 'render_simple_tracking_form'));
        add_shortcode('aiot_simple_tracker', array($this, 'render_simple_tracking_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_ajax_aiot_simple_track_order', array($this, 'handle_tracking_request'));
        add_action('wp_ajax_nopriv_aiot_simple_track_order', array($this, 'handle_tracking_request'));
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Only load if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'aiot_simple_tracking') || has_shortcode($post->post_content, 'aiot_simple_tracker'))) {
            
            // jQuery
            wp_enqueue_script('jquery');

            // Simple tracking script
            wp_enqueue_script(
                'aiot-simple-tracking',
                AIOT_URL . 'public/js/simple-tracking.js',
                array('jquery'),
                AIOT_VERSION,
                true
            );

            // Localize script
            wp_localize_script('aiot-simple-tracking', 'aiot_simple', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aiot_simple_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'ai-order-tracker'),
                    'error' => __('An error occurred', 'ai-order-tracker'),
                    'not_found' => __('Tracking information not found', 'ai-order-tracker'),
                    'invalid_id' => __('Invalid tracking ID', 'ai-order-tracker'),
                    'try_again' => __('Please try again', 'ai-order-tracker'),
                    'tracking_info' => __('Tracking Information', 'ai-order-tracker'),
                    'order_details' => __('Order Details', 'ai-order-tracker'),
                    'package_journey' => __('Package Journey', 'ai-order-tracker'),
                    'estimated_delivery' => __('Estimated Delivery', 'ai-order-tracker'),
                    'current_location' => __('Current Location', 'ai-order-tracker'),
                    'track_order' => __('Track Order', 'ai-order-tracker'),
                    'enter_tracking_id' => __('Enter your tracking ID', 'ai-order-tracker'),
                    'track_another' => __('Track Another Order', 'ai-order-tracker'),
                ),
                'settings' => array(
                    'theme' => get_option('aiot_theme', 'modern'),
                    'primary_color' => get_option('aiot_primary_color', '#0073aa'),
                    'secondary_color' => get_option('aiot_secondary_color', '#28a745'),
                    'show_progress' => get_option('aiot_show_progress', '1'),
                    'show_timeline' => get_option('aiot_show_timeline', '1'),
                    'show_details' => get_option('aiot_show_details', '1'),
                )
            ));
        }
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        // Only load if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'aiot_simple_tracking') || has_shortcode($post->post_content, 'aiot_simple_tracker'))) {
            
            // Simple tracking CSS
            wp_enqueue_style(
                'aiot-simple-tracking',
                AIOT_URL . 'public/css/simple-tracking.css',
                array(),
                AIOT_VERSION
            );
        }
    }

    /**
     * Render simple tracking form
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_simple_tracking_form($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Track Your Order', 'ai-order-tracker'),
            'placeholder' => __('Enter your tracking ID', 'ai-order-tracker'),
            'button_text' => __('Track Order', 'ai-order-tracker'),
            'theme' => get_option('aiot_theme', 'modern'),
        ), $atts, 'aiot_simple_tracking');

        ob_start();
        ?>
        <div class="aiot-simple-tracking-container aiot-theme-<?php echo esc_attr($atts['theme']); ?>">
            <!-- Tracking Form -->
            <div class="aiot-tracking-form" id="aiot-tracking-form">
                <div class="aiot-form-header">
                    <h2 class="aiot-form-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="aiot-form-subtitle"><?php _e('Enter your tracking ID to get real-time updates on your package', 'ai-order-tracker'); ?></p>
                </div>
                
                <form id="aiot-tracking-form-element">
                    <div class="aiot-form-group">
                        <input 
                            type="text" 
                            id="aiot-tracking-id"
                            placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                            class="aiot-tracking-input"
                            required
                        >
                    </div>
                    <button type="submit" class="aiot-tracking-button">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </form>
                
                <div class="aiot-error-message" id="aiot-error-message" style="display: none;"></div>
            </div>

            <!-- Loading Spinner -->
            <div class="aiot-loading-spinner" id="aiot-loading-spinner" style="display: none;">
                <div class="aiot-spinner"></div>
                <p><?php _e('Loading tracking information...', 'ai-order-tracker'); ?></p>
            </div>

            <!-- Tracking Results -->
            <div class="aiot-tracking-results" id="aiot-tracking-results" style="display: none;">
                <div class="aiot-results-header">
                    <button class="aiot-back-button" id="aiot-back-button">
                        ← <?php _e('Track Another Order', 'ai-order-tracker'); ?>
                    </button>
                    <h2 class="aiot-results-title"><?php _e('Tracking Information', 'ai-order-tracker'); ?></h2>
                </div>

                <!-- Order Summary -->
                <div class="aiot-order-summary">
                    <div class="aiot-summary-grid">
                        <div class="aiot-summary-item">
                            <label><?php _e('Tracking ID', 'ai-order-tracker'); ?></label>
                            <span id="aiot-tracking-id-display">-</span>
                        </div>
                        <div class="aiot-summary-item">
                            <label><?php _e('Order ID', 'ai-order-tracker'); ?></label>
                            <span id="aiot-order-id-display">-</span>
                        </div>
                        <div class="aiot-summary-item">
                            <label><?php _e('Status', 'ai-order-tracker'); ?></label>
                            <span class="aiot-status-badge" id="aiot-status-display">-</span>
                        </div>
                        <div class="aiot-summary-item">
                            <label><?php _e('Carrier', 'ai-order-tracker'); ?></label>
                            <span id="aiot-carrier-display">-</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="aiot-progress-section" id="aiot-progress-section" style="display: none;">
                    <div class="aiot-progress-header">
                        <h3><?php _e('Delivery Progress', 'ai-order-tracker'); ?></h3>
                        <span class="aiot-progress-percentage" id="aiot-progress-percentage">0%</span>
                    </div>
                    <div class="aiot-progress-container">
                        <div class="aiot-progress-bar">
                            <div class="aiot-progress-fill" id="aiot-progress-fill"></div>
                        </div>
                        <div class="aiot-progress-animation">
                            <div class="aiot-status-icon" id="aiot-status-icon">📦</div>
                        </div>
                    </div>
                    <div class="aiot-progress-steps" id="aiot-progress-steps">
                        <!-- Progress steps will be inserted here -->
                    </div>
                </div>

                <!-- Estimated Delivery -->
                <div class="aiot-delivery-info">
                    <div class="aiot-delivery-card">
                        <div class="aiot-delivery-icon">📅</div>
                        <div class="aiot-delivery-details">
                            <h4><?php _e('Estimated Delivery', 'ai-order-tracker'); ?></h4>
                            <p class="aiot-delivery-date" id="aiot-delivery-date">-</p>
                        </div>
                    </div>
                </div>

                <!-- Current Location -->
                <div class="aiot-location-section">
                    <h3><?php _e('Current Location', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-location-info">
                        <div class="aiot-location-icon">📍</div>
                        <div class="aiot-location-details">
                            <p class="aiot-location-text" id="aiot-location-text">-</p>
                            <p class="aiot-location-time" id="aiot-location-time">-</p>
                        </div>
                    </div>
                </div>

                <!-- Package Journey Timeline -->
                <div class="aiot-timeline-section" id="aiot-timeline-section" style="display: none;">
                    <h3><?php _e('Package Journey', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-timeline" id="aiot-timeline">
                        <!-- Timeline events will be inserted here -->
                    </div>
                </div>

                <!-- Order Details -->
                <div class="aiot-details-section" id="aiot-details-section" style="display: none;">
                    <h3><?php _e('Order Details', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-details-grid">
                        <div class="aiot-details-item">
                            <label><?php _e('Customer', 'ai-order-tracker'); ?></label>
                            <span id="aiot-customer-name">-</span>
                        </div>
                        <div class="aiot-details-item">
                            <label><?php _e('Email', 'ai-order-tracker'); ?></label>
                            <span id="aiot-customer-email">-</span>
                        </div>
                        <div class="aiot-details-item">
                            <label><?php _e('Weight', 'ai-order-tracker'); ?></label>
                            <span id="aiot-weight">-</span>
                        </div>
                        <div class="aiot-details-item">
                            <label><?php _e('Dimensions', 'ai-order-tracker'); ?></label>
                            <span id="aiot-dimensions">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle tracking request
     */
    public function handle_tracking_request() {
        // Verify nonce
        if (!check_ajax_referer('aiot_simple_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }

        // Get tracking ID
        $tracking_id = isset($_POST['tracking_id']) ? sanitize_text_field($_POST['tracking_id']) : '';

        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Tracking ID is required.', 'ai-order-tracker')));
        }

        // Get tracking info using the existing tracking engine
        if (class_exists('AIOT_Real_Time_Tracking')) {
            $tracking_info = AIOT_Real_Time_Tracking::get_tracking_info($tracking_id);
        } else {
            // Fallback to simple database query
            global $wpdb;
            $table_name = $wpdb->prefix . 'aiot_orders';
            $order = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table_name} WHERE tracking_id = %s", $tracking_id),
                ARRAY_A
            );

            if (!$order) {
                // Create simulated tracking if simulation mode is enabled
                if (get_option('aiot_simulation_mode', false)) {
                    $tracking_info = $this->create_simulated_tracking($tracking_id);
                } else {
                    wp_send_json_error(array('message' => __('Tracking information not found.', 'ai-order-tracker')));
                }
            } else {
                $tracking_info = $this->format_order_data($order);
            }
        }

        if ($tracking_info) {
            wp_send_json_success(array('data' => $tracking_info));
        } else {
            wp_send_json_error(array('message' => __('Tracking information not found.', 'ai-order-tracker')));
        }
    }

    /**
     * Create simulated tracking information
     *
     * @param string $tracking_id Tracking ID
     * @return array Simulated tracking information
     */
    private function create_simulated_tracking($tracking_id) {
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
            'progress' => 10,
            'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
        );

        return $this->format_order_data($order_data);
    }

    /**
     * Format order data for display
     *
     * @param array $order Order data
     * @return array Formatted tracking information
     */
    private function format_order_data($order) {
        $status_info = $this->get_status_info($order['status']);
        
        return array(
            'tracking_id' => $order['tracking_id'],
            'order_id' => isset($order['order_id']) ? $order['order_id'] : '',
            'status' => $order['status'],
            'status_info' => $status_info,
            'progress' => isset($order['progress']) ? $order['progress'] : 0,
            'estimated_delivery' => isset($order['estimated_delivery']) ? $order['estimated_delivery'] : '',
            'location' => isset($order['location']) ? $order['location'] : '',
            'carrier' => isset($order['carrier']) ? $order['carrier'] : 'Standard',
            'customer_name' => isset($order['customer_name']) ? $order['customer_name'] : '',
            'customer_email' => isset($order['customer_email']) ? $order['customer_email'] : '',
            'origin_address' => isset($order['origin_address']) ? $order['origin_address'] : '',
            'destination_address' => isset($order['destination_address']) ? $order['destination_address'] : '',
            'package_info' => array(
                'weight' => isset($order['weight']) ? $order['weight'] : 0,
                'dimensions' => isset($order['dimensions']) ? $order['dimensions'] : '',
                'package_type' => isset($order['package_type']) ? $order['package_type'] : '',
                'service_type' => isset($order['service_type']) ? $order['service_type'] : '',
            ),
            'tracking_events' => $this->generate_tracking_events($order['status']),
            'updated_at' => isset($order['updated_at']) ? $order['updated_at'] : current_time('mysql'),
        );
    }

    /**
     * Get status information
     *
     * @param string $status Order status
     * @return array Status information
     */
    private function get_status_info($status) {
        $statuses = array(
            'processing' => array(
                'label' => __('Processing', 'ai-order-tracker'),
                'color' => '#ffc107',
                'icon' => '⚙️',
            ),
            'confirmed' => array(
                'label' => __('Order Confirmed', 'ai-order-tracker'),
                'color' => '#17a2b8',
                'icon' => '✅',
            ),
            'packed' => array(
                'label' => __('Packed', 'ai-order-tracker'),
                'color' => '#6f42c1',
                'icon' => '📦',
            ),
            'shipped' => array(
                'label' => __('Shipped', 'ai-order-tracker'),
                'color' => '#007bff',
                'icon' => '🚚',
            ),
            'in_transit' => array(
                'label' => __('In Transit', 'ai-order-tracker'),
                'color' => '#fd7e14',
                'icon' => '🚛',
            ),
            'out_for_delivery' => array(
                'label' => __('Out for Delivery', 'ai-order-tracker'),
                'color' => '#20c997',
                'icon' => '🏃',
            ),
            'delivered' => array(
                'label' => __('Delivered', 'ai-order-tracker'),
                'color' => '#28a745',
                'icon' => '🎉',
            ),
        );

        return isset($statuses[$status]) ? $statuses[$status] : $statuses['processing'];
    }

    /**
     * Generate tracking events
     *
     * @param string $status Current status
     * @return array Tracking events
     */
    private function generate_tracking_events($status) {
        $events = array();
        $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
        
        $current_index = array_search($status, $statuses);
        if ($current_index === false) {
            $current_index = 0;
        }

        $locations = array(
            'Processing Center',
            'Distribution Hub',
            'Regional Facility',
            'Local Depot',
            'Delivery Station',
            'Customer Address'
        );

        $descriptions = array(
            'processing' => 'Order received and being processed',
            'confirmed' => 'Order confirmed and payment verified',
            'packed' => 'Package packed and ready for shipment',
            'shipped' => 'Package shipped from origin facility',
            'in_transit' => 'Package in transit to destination',
            'out_for_delivery' => 'Package out for final delivery',
            'delivered' => 'Package delivered successfully'
        );

        $base_time = time() - (86400 * $current_index);

        for ($i = 0; $i <= $current_index; $i++) {
            $event_time = date('Y-m-d H:i:s', $base_time + (86400 * $i));
            $event_status = $statuses[$i];
            
            $events[] = array(
                'event_type' => 'status_update',
                'event_status' => $event_status,
                'location' => isset($locations[$i]) ? $locations[$i] : 'Unknown',
                'description' => isset($descriptions[$event_status]) ? $descriptions[$event_status] : 'Status updated',
                'timestamp' => $event_time,
            );
        }

        return $events;
    }
}