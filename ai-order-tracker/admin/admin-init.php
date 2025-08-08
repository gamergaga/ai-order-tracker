<?php
/**
 * Admin initialization
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Admin menu setup
 */
function aiot_admin_menu() {
    // Main menu
    add_menu_page(
        __('AI Order Tracker', 'ai-order-tracker'),
        __('AI Order Tracker', 'ai-order-tracker'),
        'manage_options',
        'ai-order-tracker',
        'aiot_admin_dashboard',
        'dashicons-location',
        30
    );

    // Dashboard submenu
    add_submenu_page(
        'ai-order-tracker',
        __('Dashboard', 'ai-order-tracker'),
        __('Dashboard', 'ai-order-tracker'),
        'manage_options',
        'ai-order-tracker',
        'aiot_admin_dashboard'
    );

    // Orders submenu
    add_submenu_page(
        'ai-order-tracker',
        __('Orders', 'ai-order-tracker'),
        __('Orders', 'ai-order-tracker'),
        'manage_options',
        'aiot-orders',
        'aiot_admin_orders'
    );

    // Zones submenu
    add_submenu_page(
        'ai-order-tracker',
        __('Delivery Zones', 'ai-order-tracker'),
        __('Delivery Zones', 'ai-order-tracker'),
        'manage_options',
        'aiot-zones',
        'aiot_admin_zones'
    );

    // Couriers submenu
    add_submenu_page(
        'ai-order-tracker',
        __('Couriers', 'ai-order-tracker'),
        __('Couriers', 'ai-order-tracker'),
        'manage_options',
        'aiot-couriers',
        'aiot_admin_couriers'
    );

    // Settings submenu
    add_submenu_page(
        'ai-order-tracker',
        __('Settings', 'ai-order-tracker'),
        __('Settings', 'ai-order-tracker'),
        'manage_options',
        'aiot-settings',
        'aiot_admin_settings'
    );
}

add_action('admin_menu', 'aiot_admin_menu');

/**
 * Admin dashboard page
 */
function aiot_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="aiot-dashboard">
            <div class="aiot-dashboard-grid">
                <!-- Statistics Cards -->
                <div class="aiot-card">
                    <h3><?php _e('Total Orders', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number"><?php echo esc_html(aiot_get_total_orders()); ?></div>
                </div>
                
                <div class="aiot-card">
                    <h3><?php _e('Delivered', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number"><?php echo esc_html(aiot_get_delivered_orders()); ?></div>
                </div>
                
                <div class="aiot-card">
                    <h3><?php _e('In Transit', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number"><?php echo esc_html(aiot_get_in_transit_orders()); ?></div>
                </div>
                
                <div class="aiot-card">
                    <h3><?php _e('Processing', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number"><?php echo esc_html(aiot_get_processing_orders()); ?></div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="aiot-card aiot-recent-orders">
                <h2><?php _e('Recent Orders', 'ai-order-tracker'); ?></h2>
                <?php aiot_render_recent_orders_table(); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Admin orders page
 */
function aiot_admin_orders() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="aiot-orders-page">
            <div class="aiot-card">
                <div class="aiot-card-header">
                    <h2><?php _e('All Orders', 'ai-order-tracker'); ?></h2>
                    <div>
                        <button class="button" id="aiot-fetch-woocommerce-orders">
                            <?php _e('Fetch WooCommerce Orders', 'ai-order-tracker'); ?>
                        </button>
                        <button class="button button-primary" id="aiot-add-order">
                            <?php _e('Add New Order', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                </div>
                <?php aiot_render_orders_table(); ?>
            </div>
        </div>
    </div>
    
    <!-- Order Modal -->
    <div id="aiot-order-modal" class="aiot-modal">
        <div class="aiot-modal-content">
            <div class="aiot-modal-header">
                <h2 id="aiot-modal-title"><?php _e('Add New Order', 'ai-order-tracker'); ?></h2>
                <button type="button" class="aiot-modal-close">&times;</button>
            </div>
            <form id="aiot-order-form" method="post">
                <div class="aiot-form-body">
                    <div class="aiot-form-row">
                        <div class="aiot-form-group">
                            <label for="aiot-order-tracking-id"><?php _e('Tracking ID', 'ai-order-tracker'); ?></label>
                            <input type="text" id="aiot-order-tracking-id" name="tracking_id" required>
                            <p class="description"><?php _e('Leave empty to auto-generate', 'ai-order-tracker'); ?></p>
                        </div>
                        <div class="aiot-form-group">
                            <label for="aiot-order-order-id"><?php _e('Order ID', 'ai-order-tracker'); ?></label>
                            <input type="text" id="aiot-order-order-id" name="order_id">
                        </div>
                    </div>
                    
                    <div class="aiot-form-row">
                        <div class="aiot-form-group">
                            <label for="aiot-order-customer-email"><?php _e('Customer Email', 'ai-order-tracker'); ?></label>
                            <input type="email" id="aiot-order-customer-email" name="customer_email" required>
                        </div>
                        <div class="aiot-form-group">
                            <label for="aiot-order-customer-name"><?php _e('Customer Name', 'ai-order-tracker'); ?></label>
                            <input type="text" id="aiot-order-customer-name" name="customer_name">
                        </div>
                    </div>
                    
                    <div class="aiot-form-row">
                        <div class="aiot-form-group">
                            <label for="aiot-order-status"><?php _e('Status', 'ai-order-tracker'); ?></label>
                            <select id="aiot-order-status" name="status">
                                <option value="processing"><?php _e('Processing', 'ai-order-tracker'); ?></option>
                                <option value="confirmed"><?php _e('Confirmed', 'ai-order-tracker'); ?></option>
                                <option value="packed"><?php _e('Packed', 'ai-order-tracker'); ?></option>
                                <option value="shipped"><?php _e('Shipped', 'ai-order-tracker'); ?></option>
                                <option value="in_transit"><?php _e('In Transit', 'ai-order-tracker'); ?></option>
                                <option value="out_for_delivery"><?php _e('Out for Delivery', 'ai-order-tracker'); ?></option>
                                <option value="delivered"><?php _e('Delivered', 'ai-order-tracker'); ?></option>
                            </select>
                        </div>
                        <div class="aiot-form-group">
                            <label for="aiot-order-carrier"><?php _e('Carrier', 'ai-order-tracker'); ?></label>
                            <select id="aiot-order-carrier" name="carrier">
                                <option value="standard"><?php _e('Standard', 'ai-order-tracker'); ?></option>
                                <?php 
                                $couriers = AIOT_Database::get_couriers(array('is_active' => true));
                                foreach ($couriers as $courier) : 
                                ?>
                                <option value="<?php echo esc_attr($courier['slug']); ?>"><?php echo esc_html($courier['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="aiot-form-group">
                        <label for="aiot-order-location"><?php _e('Location', 'ai-order-tracker'); ?></label>
                        <input type="text" id="aiot-order-location" name="location">
                    </div>
                    
                    <div class="aiot-form-row">
                        <div class="aiot-form-group">
                            <label for="aiot-order-estimated-delivery"><?php _e('Estimated Delivery', 'ai-order-tracker'); ?></label>
                            <input type="date" id="aiot-order-estimated-delivery" name="estimated_delivery">
                        </div>
                        <div class="aiot-form-group">
                            <label for="aiot-order-weight"><?php _e('Weight (kg)', 'ai-order-tracker'); ?></label>
                            <input type="number" id="aiot-order-weight" name="weight" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="aiot-modal-footer">
                    <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Save Order', 'ai-order-tracker'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Admin zones page
 */
function aiot_admin_zones() {
    if (!current_user_can('manage_options')) {
        return;
    }
    AIOT_Admin_Zones::render_page();
}

/**
 * Admin couriers page
 */
function aiot_admin_couriers() {
    if (!current_user_can('manage_options')) {
        return;
    }
    AIOT_Admin_Couriers::render_page();
}

/**
 * Admin settings page
 */
function aiot_admin_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    AIOT_Admin_Settings::render_page();
}

/**
 * Register admin settings
 */
function aiot_register_settings() {
    // General settings
    register_setting('aiot_settings_group', 'aiot_enable_tracking');
    register_setting('aiot_settings_group', 'aiot_simulation_mode');
    register_setting('aiot_settings_group', 'aiot_auto_update');
    register_setting('aiot_settings_group', 'aiot_default_carrier');
    register_setting('aiot_settings_group', 'aiot_cache_duration');
    register_setting('aiot_settings_group', 'aiot_debug_mode');

    // Display settings
    register_setting('aiot_settings_group', 'aiot_theme');
    register_setting('aiot_settings_group', 'aiot_primary_color');
    register_setting('aiot_settings_group', 'aiot_secondary_color');
    register_setting('aiot_settings_group', 'aiot_show_progress');
    register_setting('aiot_settings_group', 'aiot_show_map');
    register_setting('aiot_settings_group', 'aiot_show_timeline');
    register_setting('aiot_settings_group', 'aiot_show_details');
    register_setting('aiot_settings_group', 'aiot_animation_speed');

    // Email settings
    register_setting('aiot_settings_group', 'aiot_enable_notifications');
    register_setting('aiot_settings_group', 'aiot_status_change_email');
    register_setting('aiot_settings_group', 'aiot_delivery_email');
    register_setting('aiot_settings_group', 'aiot_email_template');

    // Advanced settings
    register_setting('aiot_settings_group', 'aiot_api_rate_limit');
    register_setting('aiot_settings_group', 'aiot_request_timeout');
    register_setting('aiot_settings_group', 'aiot_enable_cron');
    register_setting('aiot_settings_group', 'aiot_log_level');

    // Add settings sections
    add_settings_section(
        'aiot_general_section',
        __('General Settings', 'ai-order-tracker'),
        'aiot_general_section_callback',
        'aiot-settings'
    );

    add_settings_section(
        'aiot_display_section',
        __('Display Settings', 'ai-order-tracker'),
        'aiot_display_section_callback',
        'aiot-settings'
    );

    add_settings_section(
        'aiot_email_section',
        __('Email Settings', 'ai-order-tracker'),
        'aiot_email_section_callback',
        'aiot-settings'
    );

    add_settings_section(
        'aiot_advanced_section',
        __('Advanced Settings', 'ai-order-tracker'),
        'aiot_advanced_section_callback',
        'aiot-settings'
    );

    // Add settings fields
    // General fields
    add_settings_field(
        'aiot_enable_tracking',
        __('Enable Tracking', 'ai-order-tracker'),
        'aiot_checkbox_field',
        'aiot-settings',
        'aiot_general_section',
        array(
            'label_for' => 'aiot_enable_tracking',
            'description' => __('Enable order tracking functionality', 'ai-order-tracker')
        )
    );

    add_settings_field(
        'aiot_simulation_mode',
        __('Simulation Mode', 'ai-order-tracker'),
        'aiot_checkbox_field',
        'aiot-settings',
        'aiot_general_section',
        array(
            'label_for' => 'aiot_simulation_mode',
            'description' => __('Enable realistic tracking simulation', 'ai-order-tracker')
        )
    );

    add_settings_field(
        'aiot_auto_update',
        __('Auto Update', 'ai-order-tracker'),
        'aiot_checkbox_field',
        'aiot-settings',
        'aiot_general_section',
        array(
            'label_for' => 'aiot_auto_update',
            'description' => __('Automatically update tracking status', 'ai-order-tracker')
        )
    );

    add_settings_field(
        'aiot_default_carrier',
        __('Default Carrier', 'ai-order-tracker'),
        'aiot_select_field',
        'aiot-settings',
        'aiot_general_section',
        array(
            'label_for' => 'aiot_default_carrier',
            'options' => aiot_get_carier_options(),
            'description' => __('Default carrier service', 'ai-order-tracker')
        )
    );

    // Display fields
    add_settings_field(
        'aiot_theme',
        __('Theme', 'ai-order-tracker'),
        'aiot_select_field',
        'aiot-settings',
        'aiot_display_section',
        array(
            'label_for' => 'aiot_theme',
            'options' => array(
                'modern' => __('Modern', 'ai-order-tracker'),
                'classic' => __('Classic', 'ai-order-tracker'),
                'minimal' => __('Minimal', 'ai-order-tracker')
            ),
            'description' => __('Select tracking page theme', 'ai-order-tracker')
        )
    );

    add_settings_field(
        'aiot_primary_color',
        __('Primary Color', 'ai-order-tracker'),
        'aiot_color_field',
        'aiot-settings',
        'aiot_display_section',
        array(
            'label_for' => 'aiot_primary_color',
            'description' => __('Primary color for the tracking interface', 'ai-order-tracker')
        )
    );

    add_settings_field(
        'aiot_secondary_color',
        __('Secondary Color', 'ai-order-tracker'),
        'aiot_color_field',
        'aiot-settings',
        'aiot_display_section',
        array(
            'label_for' => 'aiot_secondary_color',
            'description' => __('Secondary color for the tracking interface', 'ai-order-tracker')
        )
    );
}

add_action('admin_init', 'aiot_register_settings');

/**
 * AJAX handler for fetching WooCommerce orders
 */
function aiot_fetch_woocommerce_orders() {
    // Verify nonce
    if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
    }
    
    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error(array('message' => __('WooCommerce is not active.', 'ai-order-tracker')));
    }
    
    // Get WooCommerce orders
    $args = array(
        'status' => 'completed',
        'limit' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $orders = wc_get_orders($args);
    $imported_count = 0;
    
    foreach ($orders as $order) {
        // Check if order already exists
        $existing_order = AIOT_Database::get_order_by_order_id($order->get_id());
        
        if (!$existing_order) {
            // Create new tracking order
            $order_data = array(
                'tracking_id' => aiot_generate_tracking_id(),
                'order_id' => $order->get_id(),
                'customer_id' => $order->get_customer_id(),
                'customer_email' => $order->get_billing_email(),
                'customer_name' => $order->get_formatted_billing_full_name(),
                'status' => 'processing',
                'location' => $order->get_billing_city() . ', ' . $order->get_billing_country(),
                'current_step' => 1,
                'progress' => 10,
                'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
                'carrier' => 'standard',
                'origin_address' => '',
                'destination_address' => $order->get_formatted_billing_address(),
                'weight' => 0,
                'dimensions' => '',
                'package_type' => '',
                'service_type' => '',
                'tracking_history' => '',
                'meta' => json_encode(array(
                    'woocommerce_order_id' => $order->get_id(),
                    'woocommerce_total' => $order->get_total(),
                    'woocommerce_currency' => $order->get_currency(),
                )),
            );
            
            $result = AIOT_Database::create_order($order_data);
            if ($result) {
                $imported_count++;
            }
        }
    }
    
    if ($imported_count > 0) {
        wp_send_json_success(array(
            'message' => sprintf(__('%d WooCommerce orders imported successfully.', 'ai-order-tracker'), $imported_count),
            'reload' => true
        ));
    } else {
        wp_send_json_error(array('message' => __('No new WooCommerce orders found to import.', 'ai-order-tracker')));
    }
}

add_action('wp_ajax_aiot_fetch_woocommerce_orders', 'aiot_fetch_woocommerce_orders');

/**
 * AJAX handler for saving orders
 */
function aiot_save_order() {
    // Verify nonce
    if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
    }
    
    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
    }
    
    $order_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Get and sanitize order data
    $order_data = array(
        'tracking_id' => isset($_POST['tracking_id']) ? sanitize_text_field($_POST['tracking_id']) : aiot_generate_tracking_id(),
        'order_id' => isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '',
        'customer_email' => isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '',
        'customer_name' => isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '',
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'processing',
        'location' => isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '',
        'estimated_delivery' => isset($_POST['estimated_delivery']) ? sanitize_text_field($_POST['estimated_delivery']) : null,
        'carrier' => isset($_POST['carrier']) ? sanitize_text_field($_POST['carrier']) : 'standard',
        'weight' => isset($_POST['weight']) ? floatval($_POST['weight']) : 0,
    );
    
    // Validate required fields
    if (empty($order_data['tracking_id']) || empty($order_data['customer_email'])) {
        wp_send_json_error(array('message' => __('Tracking ID and Customer Email are required.', 'ai-order-tracker')));
    }
    
    if ($order_id > 0) {
        // Update existing order
        $result = AIOT_Database::update_order($order_id, $order_data);
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update order.', 'ai-order-tracker')));
        }
        $message = __('Order updated successfully.', 'ai-order-tracker');
    } else {
        // Create new order
        $order_data['progress'] = aiot_calculate_progress($order_data['status']);
        $result = AIOT_Database::create_order($order_data);
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to create order.', 'ai-order-tracker')));
        }
        $message = __('Order created successfully.', 'ai-order-tracker');
    }
    
    wp_send_json_success(array(
        'message' => $message,
        'reload' => true
    ));
}

add_action('wp_ajax_aiot_save_order', 'aiot_save_order');

/**
 * Add tracking meta box to WooCommerce order pages
 */
function aiot_add_woocommerce_order_meta_box() {
    if (class_exists('WooCommerce')) {
        add_meta_box(
            'aiot_order_tracking',
            __('Order Tracking', 'ai-order-tracker'),
            'aiot_woocommerce_order_meta_box_callback',
            'shop_order',
            'side',
            'default'
        );
    }
}

add_action('add_meta_boxes', 'aiot_add_woocommerce_order_meta_box');

/**
 * Callback for WooCommerce order tracking meta box
 */
function aiot_woocommerce_order_meta_box_callback($post) {
    $order_id = $post->ID;
    $tracking_order = AIOT_Database::get_order_by_order_id($order_id);
    
    wp_nonce_field('aiot_save_order_tracking', 'aiot_order_tracking_nonce');
    ?>
    <div class="aiot-woocommerce-tracking">
        <?php if ($tracking_order) : ?>
            <div class="aiot-tracking-info">
                <p>
                    <strong><?php _e('Tracking ID:', 'ai-order-tracker'); ?></strong><br>
                    <code><?php echo esc_html($tracking_order['tracking_id']); ?></code>
                </p>
                <p>
                    <strong><?php _e('Status:', 'ai-order-tracker'); ?></strong><br>
                    <span class="aiot-status-badge" style="background-color: <?php echo esc_attr(aiot_get_order_status($tracking_order['status'])['color']); ?>; color: white; padding: 2px 8px; border-radius: 3px;">
                        <?php echo esc_html(aiot_get_order_status($tracking_order['status'])['label']); ?>
                    </span>
                </p>
                <p>
                    <strong><?php _e('Progress:', 'ai-order-tracker'); ?></strong><br>
                    <?php echo esc_html($tracking_order['progress']); ?>%
                </p>
                <p>
                    <strong><?php _e('Location:', 'ai-order-tracker'); ?></strong><br>
                    <?php echo esc_html($tracking_order['location']); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(add_query_arg('tracking_id', $tracking_order['tracking_id'], get_permalink(get_option('aiot_tracking_page_id')))); ?>" class="button button-small" target="_blank">
                        <?php _e('View Tracking', 'ai-order-tracker'); ?>
                    </a>
                </p>
            </div>
        <?php else : ?>
            <div class="aiot-no-tracking">
                <p><?php _e('No tracking information available for this order.', 'ai-order-tracker'); ?></p>
                <button type="button" class="button button-small" id="aiot-create-tracking-<?php echo esc_attr($order_id); ?>">
                    <?php _e('Create Tracking', 'ai-order-tracker'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aiot-create-tracking-<?php echo esc_attr($order_id); ?>').on('click', function() {
            var button = $(this);
            var originalText = button.html();
            
            button.prop('disabled', true).html('<?php _e('Creating...', 'ai-order-tracker'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiot_create_wc_order_tracking',
                    order_id: <?php echo esc_js($order_id); ?>,
                    nonce: '<?php echo wp_create_nonce('aiot_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        button.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert('<?php _e('An error occurred. Please try again.', 'ai-order-tracker'); ?>');
                    button.prop('disabled', false).html(originalText);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for creating tracking for WooCommerce orders
 */
function aiot_create_wc_order_tracking() {
    // Verify nonce
    if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
    }
    
    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if ($order_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid order ID.', 'ai-order-tracker')));
    }
    
    // Get WooCommerce order
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(array('message' => __('Order not found.', 'ai-order-tracker')));
    }
    
    // Check if tracking already exists
    $existing_tracking = AIOT_Database::get_order_by_order_id($order_id);
    if ($existing_tracking) {
        wp_send_json_error(array('message' => __('Tracking already exists for this order.', 'ai-order-tracker')));
    }
    
    // Create tracking order
    $order_data = array(
        'tracking_id' => aiot_generate_tracking_id(),
        'order_id' => $order_id,
        'customer_id' => $order->get_customer_id(),
        'customer_email' => $order->get_billing_email(),
        'customer_name' => $order->get_formatted_billing_full_name(),
        'status' => 'processing',
        'location' => $order->get_billing_city() . ', ' . $order->get_billing_country(),
        'current_step' => 1,
        'progress' => 10,
        'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
        'carrier' => 'standard',
        'origin_address' => '',
        'destination_address' => $order->get_formatted_billing_address(),
        'weight' => 0,
        'dimensions' => '',
        'package_type' => '',
        'service_type' => '',
        'tracking_history' => '',
        'meta' => json_encode(array(
            'woocommerce_order_id' => $order_id,
            'woocommerce_total' => $order->get_total(),
            'woocommerce_currency' => $order->get_currency(),
        )),
    );
    
    $result = AIOT_Database::create_order($order_data);
    
    if ($result) {
        wp_send_json_success(array('message' => __('Tracking created successfully.', 'ai-order-tracker')));
    } else {
        wp_send_json_error(array('message' => __('Failed to create tracking.', 'ai-order-tracker')));
    }
}

add_action('wp_ajax_aiot_create_wc_order_tracking', 'aiot_create_wc_order_tracking');

/**
 * General section callback
 */
function aiot_general_section_callback() {
    echo '<p>' . __('Configure general tracking settings', 'ai-order-tracker') . '</p>';
}

/**
 * Display section callback
 */
function aiot_display_section_callback() {
    echo '<p>' . __('Customize the appearance of the tracking interface', 'ai-order-tracker') . '</p>';
}

/**
 * Email section callback
 */
function aiot_email_section_callback() {
    echo '<p>' . __('Configure email notification settings', 'ai-order-tracker') . '</p>';
}

/**
 * Advanced section callback
 */
function aiot_advanced_section_callback() {
    echo '<p>' . __('Advanced configuration options', 'ai-order-tracker') . '</p>';
}

/**
 * Checkbox field callback
 */
function aiot_checkbox_field($args) {
    $option = get_option($args['label_for']);
    $checked = isset($option) ? checked($option, 1, false) : '';
    ?>
    <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" value="1" <?php echo $checked; ?>>
    <?php if (isset($args['description'])) : ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php endif;
}

/**
 * Select field callback
 */
function aiot_select_field($args) {
    $option = get_option($args['label_for']);
    ?>
    <select id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($args['label_for']); ?>">
        <?php foreach ($args['options'] as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($option, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($args['description'])) : ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php endif;
}

/**
 * Color field callback
 */
function aiot_color_field($args) {
    $option = get_option($args['label_for'], $args['default'] ?? '#0073aa');
    ?>
    <input type="color" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" value="<?php echo esc_attr($option); ?>">
    <?php if (isset($args['description'])) : ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php endif;
}

/**
 * Get total orders count
 */
function aiot_get_total_orders() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        return 0;
    }
    
    return $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
}

/**
 * Get delivered orders count
 */
function aiot_get_delivered_orders() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        return 0;
    }
    
    return $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'delivered'");
}

/**
 * Get in transit orders count
 */
function aiot_get_in_transit_orders() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        return 0;
    }
    
    return $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status IN ('in_transit', 'out_for_delivery')");
}

/**
 * Get processing orders count
 */
function aiot_get_processing_orders() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        return 0;
    }
    
    return $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status IN ('processing', 'confirmed', 'packed', 'shipped')");
}

/**
 * Get courier options
 */
function aiot_get_carier_options() {
    $couriers = AIOT_Database::get_couriers(array('is_active' => true));
    $options = array('standard' => __('Standard', 'ai-order-tracker'));
    
    foreach ($couriers as $courier) {
        $options[$courier['slug']] = $courier['name'];
    }
    
    return $options;
}

/**
 * Render recent orders table
 */
function aiot_render_recent_orders_table() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        echo '<p>' . __('No orders found. Database tables have not been created yet.', 'ai-order-tracker') . '</p>';
        return;
    }
    
    $orders = $wpdb->get_results(
        "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 10",
        ARRAY_A
    );
    
    if (empty($orders)) {
        echo '<p>' . __('No orders found', 'ai-order-tracker') . '</p>';
        return;
    }
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Tracking ID', 'ai-order-tracker'); ?></th>
                <th><?php _e('Customer', 'ai-order-tracker'); ?></th>
                <th><?php _e('Status', 'ai-order-tracker'); ?></th>
                <th><?php _e('Progress', 'ai-order-tracker'); ?></th>
                <th><?php _e('Created', 'ai-order-tracker'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : ?>
                <tr>
                    <td><?php echo esc_html($order['tracking_id']); ?></td>
                    <td><?php echo esc_html($order['customer_name']); ?></td>
                    <td><?php echo esc_html($order['status']); ?></td>
                    <td><?php echo esc_html($order['progress']); ?>%</td>
                    <td><?php echo esc_html($order['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render orders table
 */
function aiot_render_orders_table() {
    // Placeholder for orders table
    echo '<p>' . __('Orders table will be rendered here', 'ai-order-tracker') . '</p>';
}

/**
 * Render zones table
 */
function aiot_render_zones_table() {
    // Placeholder for zones table
    echo '<p>' . __('Zones table will be rendered here', 'ai-order-tracker') . '</p>';
}

/**
 * Render couriers table
 */
function aiot_render_couriers_table() {
    // Placeholder for couriers table
    echo '<p>' . __('Couriers table will be rendered here', 'ai-order-tracker') . '</p>';
}

/**
 * Enqueue admin scripts and styles
 */
function aiot_admin_enqueue_scripts($hook) {
    // Only load on our admin pages
    if (strpos($hook, 'ai-order-tracker') === false) {
        return;
    }

    // Admin CSS
    wp_enqueue_style(
        'aiot-admin-css',
        AIOT_URL . 'admin/css/admin.css',
        array(),
        AIOT_VERSION
    );

    // Leaflet CSS for maps
    wp_enqueue_style(
        'leaflet',
        AIOT_URL . 'assets/libs/leaflet.css',
        array(),
        '1.7.1'
    );

    // Admin JavaScript
    wp_enqueue_script(
        'aiot-admin-js',
        AIOT_URL . 'admin/js/admin.js',
        array('jquery', 'wp-color-picker'),
        AIOT_VERSION,
        true
    );

    // Leaflet JS for maps
    wp_enqueue_script(
        'leaflet',
        AIOT_URL . 'assets/libs/leaflet.js',
        array(),
        '1.7.1',
        true
    );

    // Localize script
    wp_localize_script('aiot-admin-js', 'aiot_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aiot_admin_nonce'),
        'strings' => array(
            'confirm_delete' => __('Are you sure you want to delete this item?', 'ai-order-tracker'),
            'saving' => __('Saving...', 'ai-order-tracker'),
            'saved' => __('Saved', 'ai-order-tracker'),
            'error' => __('Error', 'ai-order-tracker'),
            'selected_locations' => __('Selected Locations', 'ai-order-tracker'),
            'field_required' => __('This field is required', 'ai-order-tracker'),
            'invalid_email' => __('Please enter a valid email address', 'ai-order-tracker'),
            'no_items_selected' => __('No items selected', 'ai-order-tracker'),
            'search' => __('Search...', 'ai-order-tracker'),
            'confirm_bulk_action' => __('Are you sure you want to perform this action on selected items?', 'ai-order-tracker'),
        )
    ));
}

/**
 * Render modals for admin pages
 */
function aiot_render_admin_modals() {
    ?>
    <!-- Order Modal -->
    <div id="aiot-order-modal" class="aiot-modal" style="display: none;">
        <div class="aiot-modal-content">
            <div class="aiot-modal-header">
                <h3><?php _e('Add New Order', 'ai-order-tracker'); ?></h3>
                <button class="aiot-modal-close">&times;</button>
            </div>
            <div class="aiot-modal-body">
                <form id="aiot-order-form" class="aiot-form aiot-ajax-form" data-action="aiot_save_order">
                    <div class="aiot-form-group">
                        <label for="aiot-order-id"><?php _e('Order ID', 'ai-order-tracker'); ?></label>
                        <input type="text" id="aiot-order-id" name="order_id" class="regular-text">
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-customer-name"><?php _e('Customer Name', 'ai-order-tracker'); ?></label>
                        <input type="text" id="aiot-customer-name" name="customer_name" class="regular-text" required>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-customer-email"><?php _e('Customer Email', 'ai-order-tracker'); ?></label>
                        <input type="email" id="aiot-customer-email" name="customer_email" class="regular-text" required>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-destination-address"><?php _e('Destination Address', 'ai-order-tracker'); ?></label>
                        <textarea id="aiot-destination-address" name="destination_address" rows="3" class="large-text" required></textarea>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-carrier"><?php _e('Carrier', 'ai-order-tracker'); ?></label>
                        <select id="aiot-carrier" name="carrier" class="regular-text">
                            <?php 
                            $couriers = aiot_get_carier_options();
                            foreach ($couriers as $key => $label) {
                                echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-weight"><?php _e('Weight (kg)', 'ai-order-tracker'); ?></label>
                        <input type="number" id="aiot-weight" name="weight" step="0.01" class="small-text">
                    </div>
                    <div class="aiot-form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Create Order', 'ai-order-tracker'); ?></button>
                        <button type="button" class="button aiot-modal-close"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Zone Modal -->
    <div id="aiot-zone-modal" class="aiot-modal" style="display: none;">
        <div class="aiot-modal-content aiot-modal-large">
            <div class="aiot-modal-header">
                <h3><?php _e('Add New Zone', 'ai-order-tracker'); ?></h3>
                <button class="aiot-modal-close">&times;</button>
            </div>
            <div class="aiot-modal-body">
                <form id="aiot-zone-form" class="aiot-form aiot-ajax-form" data-action="aiot_save_zone">
                    <div class="aiot-form-row">
                        <div class="aiot-form-group">
                            <label for="aiot-zone-name"><?php _e('Zone Name', 'ai-order-tracker'); ?></label>
                            <input type="text" id="aiot-zone-name" name="name" class="regular-text" required>
                        </div>
                        <div class="aiot-form-group">
                            <label for="aiot-zone-type"><?php _e('Zone Type', 'ai-order-tracker'); ?></label>
                            <select id="aiot-zone-type" name="type" class="regular-text">
                                <option value="country"><?php _e('Country', 'ai-order-tracker'); ?></option>
                                <option value="state"><?php _e('State', 'ai-order-tracker'); ?></option>
                                <option value="city"><?php _e('City', 'ai-order-tracker'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Map Selection -->
                    <div class="aiot-form-group">
                        <label><?php _e('Select Locations on Map', 'ai-order-tracker'); ?></label>
                        <div id="aiot-zone-map" class="aiot-map-container"></div>
                        <p class="description"><?php _e('Click on countries or states to add them to this zone', 'ai-order-tracker'); ?></p>
                    </div>

                    <div class="aiot-form-row">
                        <div class="aiot-form-group">
                            <label for="aiot-delivery-days"><?php _e('Delivery Days', 'ai-order-tracker'); ?></label>
                            <input type="number" id="aiot-delivery-days" name="delivery_days" min="1" class="small-text" required>
                        </div>
                        <div class="aiot-form-group">
                            <label for="aiot-delivery-cost"><?php _e('Delivery Cost', 'ai-order-tracker'); ?></label>
                            <input type="number" id="aiot-delivery-cost" name="delivery_cost" step="0.01" class="small-text">
                        </div>
                    </div>

                    <div class="aiot-form-group">
                        <label for="aiot-zone-description"><?php _e('Description', 'ai-order-tracker'); ?></label>
                        <textarea id="aiot-zone-description" name="description" rows="3" class="large-text"></textarea>
                    </div>

                    <div class="aiot-form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Create Zone', 'ai-order-tracker'); ?></button>
                        <button type="button" class="button aiot-modal-close"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Courier Modal -->
    <div id="aiot-courier-modal" class="aiot-modal" style="display: none;">
        <div class="aiot-modal-content">
            <div class="aiot-modal-header">
                <h3><?php _e('Add New Courier', 'ai-order-tracker'); ?></h3>
                <button class="aiot-modal-close">&times;</button>
            </div>
            <div class="aiot-modal-body">
                <form id="aiot-courier-form" class="aiot-form aiot-ajax-form" data-action="aiot_save_courier">
                    <div class="aiot-form-group">
                        <label for="aiot-courier-name"><?php _e('Courier Name', 'ai-order-tracker'); ?></label>
                        <input type="text" id="aiot-courier-name" name="name" class="regular-text" required>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-slug"><?php _e('Courier Slug', 'ai-order-tracker'); ?></label>
                        <input type="text" id="aiot-courier-slug" name="slug" class="regular-text" required>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-url"><?php _e('Tracking URL Pattern', 'ai-order-tracker'); ?></label>
                        <input type="url" id="aiot-courier-url" name="url_pattern" class="regular-text" placeholder="https://example.com/track/{tracking_id}">
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-api"><?php _e('API Endpoint', 'ai-order-tracker'); ?></label>
                        <input type="url" id="aiot-courier-api" name="api_endpoint" class="regular-text">
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-key"><?php _e('API Key', 'ai-order-tracker'); ?></label>
                        <input type="text" id="aiot-courier-key" name="api_key" class="regular-text">
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-description"><?php _e('Description', 'ai-order-tracker'); ?></label>
                        <textarea id="aiot-courier-description" name="description" rows="3" class="large-text"></textarea>
                    </div>
                    <div class="aiot-form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Create Courier', 'ai-order-tracker'); ?></button>
                        <button type="button" class="button aiot-modal-close"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add button click handlers
            $('#aiot-add-order').on('click', function() {
                $('#aiot-order-modal').fadeIn();
            });

            $('#aiot-add-zone').on('click', function() {
                $('#aiot-zone-modal').fadeIn();
                // Initialize map if not already initialized
                if (typeof aiotAdmin !== 'undefined' && aiotAdmin.zoneManager) {
                    aiotAdmin.zoneManager.initMap();
                }
            });

            $('#aiot-add-courier').on('click', function() {
                $('#aiot-courier-modal').fadeIn();
            });

            // Modal close handlers
            $('.aiot-modal-close').on('click', function() {
                $(this).closest('.aiot-modal').fadeOut();
            });

            $('.aiot-modal').on('click', function(e) {
                if ($(e.target).hasClass('aiot-modal')) {
                    $(this).fadeOut();
                }
            });
        });
    </script>
    <?php
}

// Hook to render modals on all admin pages
add_action('admin_footer', 'aiot_render_admin_modals');

add_action('admin_enqueue_scripts', 'aiot_admin_enqueue_scripts');