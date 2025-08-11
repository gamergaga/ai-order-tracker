<?php
/**
 * Simple tracking shortcode class
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
        add_shortcode('aiot_simple_tracking', array($this, 'render_simple_tracking'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_ajax_aiot_track_order', array($this, 'ajax_track_order'));
        add_action('wp_ajax_nopriv_aiot_track_order', array($this, 'ajax_track_order'));
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Only load if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'aiot_simple_tracking')) {
            
            wp_enqueue_script(
                'aiot-simple-tracking',
                AIOT_URL . 'public/js/simple-tracking.js',
                array('jquery'),
                AIOT_VERSION,
                true
            );

            // Localize script
            wp_localize_script('aiot-simple-tracking', 'aiot_simple_tracking', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aiot_public_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'ai-order-tracker'),
                    'error' => __('An error occurred', 'ai-order-tracker'),
                    'not_found' => __('Tracking information not found', 'ai-order-tracker'),
                    'invalid_id' => __('Invalid tracking ID', 'ai-order-tracker'),
                    'track_order' => __('Track Order', 'ai-order-tracker'),
                    'enter_tracking_id' => __('Enter tracking ID', 'ai-order-tracker'),
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
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'aiot_simple_tracking')) {
            
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
    public function render_simple_tracking($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Track Your Order', 'ai-order-tracker'),
            'placeholder' => __('Enter your tracking ID', 'ai-order-tracker'),
            'button_text' => __('Track Order', 'ai-order-tracker'),
        ), $atts, 'aiot_simple_tracking');

        ob_start();
        ?>
        <div class="aiot-simple-tracking-container">
            <div class="aiot-tracking-form">
                <div class="aiot-form-header">
                    <h2 class="aiot-form-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="aiot-form-subtitle"><?php _e('Enter your tracking ID to get real-time updates on your package', 'ai-order-tracker'); ?></p>
                </div>
                
                <form id="aiot-tracking-form" class="aiot-form">
                    <div class="aiot-form-group">
                        <input 
                            type="text" 
                            id="aiot-tracking-id"
                            name="tracking_id"
                            placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                            class="aiot-tracking-input"
                            required
                        >
                    </div>
                    <button type="submit" class="aiot-tracking-button">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </form>
                
                <div id="aiot-tracking-error" class="aiot-error-message" style="display: none;"></div>
            </div>

            <div id="aiot-tracking-results" class="aiot-tracking-results" style="display: none;">
                <div class="aiot-results-header">
                    <button id="aiot-back-button" class="aiot-back-button">
                        ← <?php _e('Track Another Order', 'ai-order-tracker'); ?>
                    </button>
                    <h2 class="aiot-results-title"><?php _e('Tracking Information', 'ai-order-tracker'); ?></h2>
                </div>

                <div id="aiot-tracking-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX track order
     */
    public function ajax_track_order() {
        // Verify nonce
        if (!check_ajax_referer('aiot_public_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }

        // Get tracking ID
        $tracking_id = sanitize_text_field($_POST['tracking_id']);

        if (empty($tracking_id)) {
            wp_send_json_error(array('message' => __('Please enter a tracking ID.', 'ai-order-tracker')));
        }

        // Get order data
        $order = AIOT_Database::get_order_by_tracking_id($tracking_id);

        if (!$order) {
            wp_send_json_error(array('message' => __('Tracking information not found.', 'ai-order-tracker')));
        }

        // Get status information
        $status_info = $this->get_status_info($order['status']);

        // Get tracking events
        $tracking_events = AIOT_Database::get_tracking_events($order['id']);

        // Prepare response data
        $response_data = array(
            'tracking_id' => $order['tracking_id'],
            'order_id' => $order['order_id'],
            'customer_email' => $order['customer_email'],
            'customer_name' => $order['customer_name'],
            'status' => $order['status'],
            'status_info' => $status_info,
            'location' => $order['location'],
            'progress' => $order['progress'],
            'estimated_delivery' => $order['estimated_delivery'],
            'carrier' => $order['carrier'],
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
            'tracking_events' => $tracking_events,
        );

        wp_send_json_success($response_data);
    }

    /**
     * Get status information
     *
     * @param string $status Order status
     * @return array Status information
     */
    private function get_status_info($status) {
        $status_map = array(
            'processing' => array(
                'label' => __('Processing', 'ai-order-tracker'),
                'color' => '#6c757d',
                'description' => __('Your order is being processed.', 'ai-order-tracker')
            ),
            'confirmed' => array(
                'label' => __('Order Confirmed', 'ai-order-tracker'),
                'color' => '#007bff',
                'description' => __('Your order has been confirmed.', 'ai-order-tracker')
            ),
            'packed' => array(
                'label' => __('Packed', 'ai-order-tracker'),
                'color' => '#17a2b8',
                'description' => __('Your order has been packed.', 'ai-order-tracker')
            ),
            'shipped' => array(
                'label' => __('Shipped', 'ai-order-tracker'),
                'color' => '#ffc107',
                'description' => __('Your order has been shipped.', 'ai-order-tracker')
            ),
            'in_transit' => array(
                'label' => __('In Transit', 'ai-order-tracker'),
                'color' => '#fd7e14',
                'description' => __('Your order is in transit.', 'ai-order-tracker')
            ),
            'out_for_delivery' => array(
                'label' => __('Out for Delivery', 'ai-order-tracker'),
                'color' => '#20c997',
                'description' => __('Your order is out for delivery.', 'ai-order-tracker')
            ),
            'delivered' => array(
                'label' => __('Delivered', 'ai-order-tracker'),
                'color' => '#28a745',
                'description' => __('Your order has been delivered.', 'ai-order-tracker')
            ),
        );

        return isset($status_map[$status]) ? $status_map[$status] : $status_map['processing'];
    }
}