<?php
/**
 * Tracking shortcode class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Tracking_Shortcode
 */
class AIOT_Tracking_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('aiot_tracking', array($this, 'render_tracking_form'));
        add_shortcode('aiot_tracker', array($this, 'render_tracking_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Only load if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'aiot_tracking') || has_shortcode($post->post_content, 'aiot_tracker')) {
            
            // Vue.js
            wp_enqueue_script(
                'vue',
                AIOT_URL . 'assets/libs/vue.global.prod.js',
                array(),
                '3.0.0',
                true
            );

            // Lottie player
            wp_enqueue_script(
                'lottie-player',
                AIOT_URL . 'assets/libs/lottie-player.js',
                array(),
                '1.0.0',
                true
            );

            // Leaflet (for maps)
            wp_enqueue_script(
                'leaflet',
                AIOT_URL . 'assets/libs/leaflet.js',
                array(),
                '1.7.1',
                true
            );

            // Public tracking script
            wp_enqueue_script(
                'aiot-public',
                AIOT_URL . 'public/js/public.js',
                array('jquery'),
                AIOT_VERSION,
                true
            );

            // Vue tracking app
            wp_enqueue_script(
                'aiot-tracking-app',
                AIOT_URL . 'public/js/tracking-app.js',
                array('vue', 'lottie-player'),
                AIOT_VERSION,
                true
            );

            // Progress animations
            wp_enqueue_script(
                'aiot-progress-animations',
                AIOT_URL . 'public/js/progress-animations.js',
                array('aiot-public'),
                AIOT_VERSION,
                true
            );

            // Localize script
            wp_localize_script('aiot-public', 'aiot_public', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aiot_public_nonce'),
                'api_url' => rest_url('aiot/v1/'),
                'plugin_url' => AIOT_URL,
                'strings' => array(
                    'loading' => __('Loading...', 'ai-order-tracker'),
                    'error' => __('An error occurred', 'ai-order-tracker'),
                    'not_found' => __('Tracking information not found', 'ai-order-tracker'),
                    'invalid_id' => __('Invalid tracking ID', 'ai-order-tracker'),
                    'try_again' => __('Please try again', 'ai-order-tracker'),
                    'tracking_info' => __('Tracking Information', 'ai-order-tracker'),
                    'order_details' => __('Order Details', 'ai-order-tracker'),
                    'package_journey' => __('Package Journey', 'ai-order-tracker'),
                    'delivered' => __('Delivered', 'ai-order-tracker'),
                    'in_transit' => __('In Transit', 'ai-order-tracker'),
                    'processing' => __('Processing', 'ai-order-tracker'),
                    'confirmed' => __('Order Confirmed', 'ai-order-tracker'),
                    'packed' => __('Packed', 'ai-order-tracker'),
                    'shipped' => __('Shipped', 'ai-order-tracker'),
                    'out_for_delivery' => __('Out for Delivery', 'ai-order-tracker'),
                    'estimated_delivery' => __('Estimated Delivery', 'ai-order-tracker'),
                    'current_location' => __('Current Location', 'ai-order-tracker'),
                    'next_destination' => __('Next Destination', 'ai-order-tracker'),
                    'progress' => __('Progress', 'ai-order-tracker'),
                    'status' => __('Status', 'ai-order-tracker'),
                    'tracking_id' => __('Tracking ID', 'ai-order-tracker'),
                    'order_id' => __('Order ID', 'ai-order-tracker'),
                    'customer' => __('Customer', 'ai-order-tracker'),
                    'carrier' => __('Carrier', 'ai-order-tracker'),
                    'service' => __('Service', 'ai-order-tracker'),
                    'weight' => __('Weight', 'ai-order-tracker'),
                    'dimensions' => __('Dimensions', 'ai-order-tracker'),
                    'origin' => __('Origin', 'ai-order-tracker'),
                    'destination' => __('Destination', 'ai-order-tracker'),
                    'date' => __('Date', 'ai-order-tracker'),
                    'time' => __('Time', 'ai-order-tracker'),
                    'location' => __('Location', 'ai-order-tracker'),
                    'description' => __('Description', 'ai-order-tracker'),
                    'track_order' => __('Track Order', 'ai-order-tracker'),
                    'enter_tracking_id' => __('Enter tracking ID', 'ai-order-tracker'),
                    'search' => __('Search', 'ai-order-tracker'),
                ),
                'settings' => array(
                    'theme' => get_option('aiot_theme', 'modern'),
                    'primary_color' => get_option('aiot_primary_color', '#0073aa'),
                    'secondary_color' => get_option('aiot_secondary_color', '#28a745'),
                    'show_progress' => get_option('aiot_show_progress', '1'),
                    'show_map' => get_option('aiot_show_map', '0'),
                    'show_timeline' => get_option('aiot_show_timeline', '1'),
                    'show_details' => get_option('aiot_show_details', '1'),
                    'animation_speed' => get_option('aiot_animation_speed', 'normal'),
                    'simulation_mode' => get_option('aiot_simulation_mode', '1'),
                    'auto_update' => get_option('aiot_auto_update', '1'),
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
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'aiot_tracking') || has_shortcode($post->post_content, 'aiot_tracker')) {
            
            // Leaflet CSS
            wp_enqueue_style(
                'leaflet',
                AIOT_URL . 'assets/libs/leaflet.css',
                array(),
                '1.7.1'
            );

            // Public CSS
            wp_enqueue_style(
                'aiot-public',
                AIOT_URL . 'public/css/public.css',
                array(),
                AIOT_VERSION
            );
        }
    }

    /**
     * Render tracking form
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_tracking_form($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Track Your Order', 'ai-order-tracker'),
            'placeholder' => __('Enter your tracking ID', 'ai-order-tracker'),
            'button_text' => __('Track Order', 'ai-order-tracker'),
            'show_branding' => '1',
            'theme' => get_option('aiot_theme', 'modern'),
            'primary_color' => get_option('aiot_primary_color', '#0073aa'),
            'secondary_color' => get_option('aiot_secondary_color', '#28a745'),
            'background_color' => get_option('aiot_background_color', '#ffffff'),
            'text_color' => get_option('aiot_text_color', '#333333'),
            'border_color' => get_option('aiot_border_color', '#e0e0e0'),
            'success_color' => get_option('aiot_success_color', '#28a745'),
            'warning_color' => get_option('aiot_warning_color', '#ffc107'),
            'error_color' => get_option('aiot_error_color', '#dc3545'),
        ), $atts, 'aiot_tracking');

        // Generate custom CSS for this instance
        $custom_css = $this->generate_custom_css($atts);
        
        ob_start();
        ?>
        <style><?php echo $custom_css; ?></style>
        <div class="aiot-tracking-container aiot-theme-<?php echo esc_attr($atts['theme']); ?>" id="aiot-tracking-app">
            <!-- Tracking Form -->
            <div class="aiot-tracking-form" v-if="!trackingInfo">
                <div class="aiot-form-header">
                    <h2 class="aiot-form-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="aiot-form-subtitle"><?php _e('Enter your tracking ID to get real-time updates on your package', 'ai-order-tracker'); ?></p>
                </div>
                
                <form @submit.prevent="trackOrder" class="aiot-form">
                    <div class="aiot-form-group">
                        <input 
                            type="text" 
                            v-model="trackingId"
                            :placeholder="'<?php echo esc_attr($atts['placeholder']); ?>'"
                            class="aiot-tracking-input"
                            required
                        >
                    </div>
                    <button type="submit" class="aiot-tracking-button" :disabled="loading">
                        <span v-if="loading" class="aiot-loading"></span>
                        <span v-else><?php echo esc_html($atts['button_text']); ?></span>
                    </button>
                </form>
                
                <div v-if="error" class="aiot-error-message">
                    {{ error }}
                </div>
            </div>

            <!-- Tracking Results -->
            <div class="aiot-tracking-results" v-if="trackingInfo">
                <div class="aiot-results-header">
                    <button @click="resetForm" class="aiot-back-button">
                        ← <?php _e('Track Another Order', 'ai-order-tracker'); ?>
                    </button>
                    <h2 class="aiot-results-title"><?php _e('Tracking Information', 'ai-order-tracker'); ?></h2>
                </div>

                <!-- Order Summary -->
                <div class="aiot-order-summary">
                    <div class="aiot-summary-grid">
                        <div class="aiot-summary-item">
                            <label><?php _e('Tracking ID', 'ai-order-tracker'); ?></label>
                            <span>{{ trackingInfo.tracking_id }}</span>
                        </div>
                        <div class="aiot-summary-item">
                            <label><?php _e('Order ID', 'ai-order-tracker'); ?></label>
                            <span>{{ trackingInfo.order_id || '<?php _e('N/A', 'ai-order-tracker'); ?>' }}</span>
                        </div>
                        <div class="aiot-summary-item">
                            <label><?php _e('Status', 'ai-order-tracker'); ?></label>
                            <span class="aiot-status-badge" :style="{ backgroundColor: trackingInfo.status_info.color }">
                                {{ trackingInfo.status_info.label }}
                            </span>
                        </div>
                        <div class="aiot-summary-item">
                            <label><?php _e('Carrier', 'ai-order-tracker'); ?></label>
                            <span>{{ trackingInfo.carrier || '<?php _e('Standard', 'ai-order-tracker'); ?>' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar with Lottie Animation -->
                <div class="aiot-progress-section" v-if="settings.show_progress">
                    <div class="aiot-progress-header">
                        <h3><?php _e('Delivery Progress', 'ai-order-tracker'); ?></h3>
                        <span class="aiot-progress-percentage">{{ trackingInfo.progress }}%</span>
                    </div>
                    <div class="aiot-progress-container">
                        <div class="aiot-progress-bar">
                            <div class="aiot-progress-fill" :style="{ width: trackingInfo.progress + '%', backgroundColor: settings.primary_color }"></div>
                        </div>
                        <div class="aiot-progress-animation">
                            <lottie-player
                                :src="getAnimationUrl(trackingInfo.status)"
                                :background="'transparent'"
                                :speed="getAnimationSpeed()"
                                :style="'width: 80px; height: 80px;'"
                                :loop="true"
                                :autoplay="true">
                            </lottie-player>
                        </div>
                    </div>
                    <div class="aiot-progress-steps">
                        <div v-for="step in progressSteps" :key="step.status" class="aiot-progress-step" :class="{ active: step.active, completed: step.completed }">
                            <div class="aiot-step-icon">{{ step.icon }}</div>
                            <div class="aiot-step-label">{{ step.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Estimated Delivery -->
                <div class="aiot-delivery-info">
                    <div class="aiot-delivery-card">
                        <div class="aiot-delivery-icon">📅</div>
                        <div class="aiot-delivery-details">
                            <h4><?php _e('Estimated Delivery', 'ai-order-tracker'); ?></h4>
                            <p class="aiot-delivery-date">{{ formatDate(trackingInfo.estimated_delivery) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Current Location -->
                <div class="aiot-location-section">
                    <h3><?php _e('Current Location', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-location-info">
                        <div class="aiot-location-icon">📍</div>
                        <div class="aiot-location-details">
                            <p class="aiot-location-text">{{ trackingInfo.location || '<?php _e('Location not available', 'ai-order-tracker'); ?>' }}</p>
                            <p class="aiot-location-time">{{ formatDateTime(trackingInfo.updated_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Already Delivered Button (for verification) -->
                <div class="aiot-delivery-verification" v-if="trackingInfo.status !== 'delivered'">
                    <button @click="verifyDelivery" class="aiot-verify-delivery-button">
                        <?php _e('Already Delivered?', 'ai-order-tracker'); ?>
                    </button>
                </div>

                <!-- Package Journey Timeline -->
                <div class="aiot-timeline-section" v-if="settings.show_timeline">
                    <h3><?php _e('Package Journey', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-timeline">
                        <div v-for="(event, index) in trackingInfo.tracking_events" :key="index" class="aiot-timeline-item">
                            <div class="aiot-timeline-marker">
                                <div class="aiot-marker-icon">📦</div>
                                <div class="aiot-marker-line" v-if="index < trackingInfo.tracking_events.length - 1"></div>
                            </div>
                            <div class="aiot-timeline-content">
                                <div class="aiot-timeline-header">
                                    <h4 class="aiot-timeline-title">{{ event.event_status }}</h4>
                                    <span class="aiot-timeline-time">{{ formatDateTime(event.timestamp) }}</span>
                                </div>
                                <p class="aiot-timeline-location">{{ event.location }}</p>
                                <p class="aiot-timeline-description">{{ event.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="aiot-branding" v-if="atts.show_branding">
                    <p><?php _e('Powered by', 'ai-order-tracker'); ?> <strong>AI Order Tracker</strong></p>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Generate custom CSS for the tracking form
     *
     * @param array $atts Shortcode attributes
     * @return string Generated CSS
     */
    private function generate_custom_css($atts) {
        $css = "
        /* Custom styles for tracking instance */
        .aiot-tracking-container {
            background-color: " . esc_attr($atts['background_color']) . ";
            color: " . esc_attr($atts['text_color']) . ";
            border-color: " . esc_attr($atts['border_color']) . ";
        }
        
        .aiot-tracking-button {
            background-color: " . esc_attr($atts['primary_color']) . ";
            color: #ffffff;
        }
        
        .aiot-tracking-button:hover {
            background-color: " . $this->adjust_color($atts['primary_color'], -20) . ";
        }
        
        .aiot-progress-fill {
            background-color: " . esc_attr($atts['primary_color']) . ";
        }
        
        .aiot-status-badge {
            background-color: " . esc_attr($atts['primary_color']) . ";
        }
        
        .aiot-delivery-card {
            border-color: " . esc_attr($atts['border_color']) . ";
        }
        
        .aiot-timeline-item::before {
            background-color: " . esc_attr($atts['border_color']) . ";
        }
        
        .aiot-marker-icon {
            background-color: " . esc_attr($atts['primary_color']) . ";
            color: white;
        }
        
        .aiot-verify-delivery-button {
            background-color: " . esc_attr($atts['success_color']) . ";
            color: white;
        }
        
        .aiot-error-message {
            background-color: " . esc_attr($atts['error_color']) . ";
            color: white;
        }
        
        .aiot-loading {
            border-color: " . esc_attr($atts['primary_color']) . ";
        }
        ";
        
        return $css;
    }

    /**
     * Adjust color brightness
     *
     * @param string $hex Hex color code
     * @param int $steps Brightness adjustment
     * @return string Adjusted color
     */
    private function adjust_color($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        $color_parts = str_split($hex, 2);
        $r = hexdec($color_parts[0]);
        $g = hexdec($color_parts[1]);
        $b = hexdec($color_parts[2]);
        
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}