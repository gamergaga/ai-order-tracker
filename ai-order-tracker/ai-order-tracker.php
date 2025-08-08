<?php
/**
 * Plugin Name: AI Order Tracker
 * Plugin URI: https://yourwebsite.com/ai-order-tracker
 * Description: Professional order tracking system with realistic simulation, stylish UI, and comprehensive admin interface. Perfect for e-commerce sites looking to provide a premium tracking experience.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-order-tracker
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define plugin constants
if (!defined('AIOT_VERSION')) {
    define('AIOT_VERSION', '2.0.0');
}
if (!defined('AIOT_PATH')) {
    define('AIOT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('AIOT_URL')) {
    define('AIOT_URL', plugin_dir_url(__FILE__));
}
if (!defined('AIOT_BASENAME')) {
    define('AIOT_BASENAME', plugin_basename(__FILE__));
}
if (!defined('AIOT_MIN_PHP_VERSION')) {
    define('AIOT_MIN_PHP_VERSION', '7.4');
}
if (!defined('AIOT_MIN_WP_VERSION')) {
    define('AIOT_MIN_WP_VERSION', '5.8');
}

// Check if WooCommerce is active and handle HPOS compatibility
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // Declare compatibility with WooCommerce HPOS
    add_action('before_woocommerce_init', function() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });
}

// Check PHP version
if (version_compare(PHP_VERSION, AIOT_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', 'aiot_php_version_notice');
    return;
}

// Check WordPress version
if (version_compare(get_bloginfo('version'), AIOT_MIN_WP_VERSION, '<')) {
    add_action('admin_notices', 'aiot_wp_version_notice');
    return;
}

/**
 * Display PHP version notice
 */
function aiot_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: %s: PHP version */
                esc_html__('AI Order Tracker requires PHP %s or higher. Your current PHP version is %s.', 'ai-order-tracker'),
                '<strong>' . esc_html(AIOT_MIN_PHP_VERSION) . '</strong>',
                '<strong>' . esc_html(PHP_VERSION) . '</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Display WordPress version notice
 */
function aiot_wp_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: %s: WordPress version */
                esc_html__('AI Order Tracker requires WordPress %s or higher. Your current WordPress version is %s.', 'ai-order-tracker'),
                '<strong>' . esc_html(AIOT_MIN_WP_VERSION) . '</strong>',
                '<strong>' . esc_html(get_bloginfo('version')) . '</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

// Include required files
require_once AIOT_PATH . 'includes/class-courier-manager.php';
require_once AIOT_PATH . 'includes/class-cron.php';
require_once AIOT_PATH . 'includes/class-database.php';
require_once AIOT_PATH . 'includes/class-dependencies.php';
require_once AIOT_PATH . 'includes/class-helpers.php';
require_once AIOT_PATH . 'includes/class-real-time-api.php';
require_once AIOT_PATH . 'includes/class-real-time-tracking.php';
require_once AIOT_PATH . 'includes/class-security.php';
require_once AIOT_PATH . 'includes/class-tracking-engine.php';
require_once AIOT_PATH . 'includes/class-zone-manager.php';
require_once AIOT_PATH . 'includes/class-api.php';

// Admin files
require_once AIOT_PATH . 'admin/admin-init.php';
require_once AIOT_PATH . 'admin/class-admin-couriers.php';
require_once AIOT_PATH . 'admin/class-admin-settings.php';
require_once AIOT_PATH . 'admin/class-admin-zones.php';

// Public files
require_once AIOT_PATH . 'public/class-tracking-shortcode.php';

// Register activation hook
register_activation_hook(__FILE__, 'aiot_activate');

// Register deactivation hook
register_deactivation_hook(__FILE__, 'aiot_deactivate');

/**
 * Plugin activation function
 */
function aiot_activate() {
    // Create database tables
    aiot_create_tables();
    
    // Set default options
    aiot_set_default_options();
    
    // Initialize default couriers
    aiot_initialize_default_couriers();
    
    // Schedule cron jobs
    aiot_schedule_cron_jobs();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation function
 */
function aiot_deactivate() {
    // Clear scheduled cron jobs
    aiot_clear_cron_jobs();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Create database tables
 */
function aiot_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_prefix = $wpdb->prefix . 'aiot_';
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Orders table
    $orders_table = $table_prefix . 'orders';
    $sql = "CREATE TABLE IF NOT EXISTS $orders_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        tracking_id varchar(50) NOT NULL,
        order_id varchar(100) DEFAULT '',
        customer_id bigint(20) DEFAULT 0,
        customer_email varchar(100) DEFAULT '',
        customer_name varchar(100) DEFAULT '',
        status varchar(20) DEFAULT 'processing',
        location varchar(255) DEFAULT '',
        current_step tinyint(1) DEFAULT 0,
        progress int(3) DEFAULT 0,
        estimated_delivery date DEFAULT NULL,
        carrier varchar(50) DEFAULT '',
        carrier_url varchar(255) DEFAULT '',
        origin_address text DEFAULT '',
        destination_address text DEFAULT '',
        weight decimal(10,2) DEFAULT 0.00,
        dimensions varchar(100) DEFAULT '',
        package_type varchar(50) DEFAULT '',
        service_type varchar(50) DEFAULT '',
        tracking_history longtext DEFAULT NULL,
        meta longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY tracking_id (tracking_id),
        KEY status (status),
        KEY customer_id (customer_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Zones table
    $zones_table = $table_prefix . 'zones';
    $sql = "CREATE TABLE IF NOT EXISTS $zones_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        description text DEFAULT '',
        type varchar(20) DEFAULT 'country',
        coordinates longtext DEFAULT NULL,
        countries longtext DEFAULT NULL,
        states longtext DEFAULT NULL,
        cities longtext DEFAULT NULL,
        postal_codes longtext DEFAULT NULL,
        delivery_days int(3) DEFAULT 1,
        delivery_cost decimal(10,2) DEFAULT 0.00,
        is_active tinyint(1) DEFAULT 1,
        meta longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY name (name),
        KEY is_active (is_active)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Couriers table
    $couriers_table = $table_prefix . 'couriers';
    $sql = "CREATE TABLE IF NOT EXISTS $couriers_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(50) NOT NULL,
        description text DEFAULT '',
        url_pattern varchar(255) DEFAULT '',
        api_endpoint varchar(255) DEFAULT '',
        api_key varchar(255) DEFAULT '',
        tracking_format varchar(20) DEFAULT 'standard',
        is_active tinyint(1) DEFAULT 1,
        settings longtext DEFAULT NULL,
        meta longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug),
        KEY name (name),
        KEY is_active (is_active)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Tracking events table
    $events_table = $table_prefix . 'tracking_events';
    $sql = "CREATE TABLE IF NOT EXISTS $events_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        event_type varchar(50) NOT NULL,
        event_status varchar(50) NOT NULL,
        location varchar(255) DEFAULT '',
        description text DEFAULT '',
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        latitude decimal(10,8) DEFAULT NULL,
        longitude decimal(11,8) DEFAULT NULL,
        meta longtext DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY order_id (order_id),
        KEY event_type (event_type),
        KEY timestamp (timestamp)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Update database version
    update_option('aiot_db_version', AIOT_VERSION);
}

/**
 * Set default options
 */
function aiot_set_default_options() {
    $default_options = array(
        'version' => AIOT_VERSION,
        'install_date' => current_time('mysql'),
        'enable_tracking' => '1',
        'simulation_mode' => '1',
        'auto_update' => '1',
        'default_carrier' => 'standard',
        'cache_duration' => '3600',
        'debug_mode' => '0',
        'theme' => 'modern',
        'primary_color' => '#0073aa',
        'secondary_color' => '#28a745',
        'show_progress' => '1',
        'show_map' => '0',
        'show_timeline' => '1',
        'show_details' => '1',
        'animation_speed' => 'normal',
        'enable_notifications' => '1',
        'status_change_email' => '1',
        'delivery_email' => '1',
        'email_template' => 'default',
        'api_rate_limit' => '100',
        'request_timeout' => '30',
        'enable_cron' => '1',
        'log_level' => 'error',
    );
    
    foreach ($default_options as $option => $value) {
        if (false === get_option('aiot_' . $option)) {
            add_option('aiot_' . $option, $value);
        }
    }
}

/**
 * Schedule cron jobs
 */
function aiot_schedule_cron_jobs() {
    // Schedule daily cleanup
    if (!wp_next_scheduled('aiot_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'aiot_daily_cleanup');
    }
    
    // Schedule order status updates
    if (!wp_next_scheduled('aiot_update_order_status')) {
        wp_schedule_event(time(), 'hourly', 'aiot_update_order_status');
    }
}

/**
 * Initialize default couriers
 */
function aiot_initialize_default_couriers() {
    return AIOT_Courier_Manager::initialize_default_couriers();
}

/**
 * Clear cron jobs
 */
function aiot_clear_cron_jobs() {
    wp_clear_scheduled_hook('aiot_daily_cleanup');
    wp_clear_scheduled_hook('aiot_update_order_status');
}

// Initialize the plugin
function aiot_init_plugin() {
    // Load text domain
    load_plugin_textdomain('ai-order-tracker', false, dirname(AIOT_BASENAME) . '/languages');
    
    // Initialize admin
    if (is_admin()) {
        new AIOT_Admin_Couriers();
        new AIOT_Admin_Settings();
        new AIOT_Admin_Zones();
    }
    
    // Initialize public
    new AIOT_Tracking_Shortcode();
}

// Run the plugin
add_action('plugins_loaded', 'aiot_init_plugin');

// Add AJAX handlers
add_action('wp_ajax_aiot_save_order', 'aiot_ajax_save_order');
add_action('wp_ajax_aiot_save_zone', 'aiot_ajax_save_zone');
add_action('wp_ajax_aiot_save_courier', 'aiot_ajax_save_courier');

/**
 * AJAX save order
 */
function aiot_ajax_save_order() {
    // Verify nonce
    if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
    }

    // Get and sanitize form data
    $order_data = array(
        'order_id' => sanitize_text_field($_POST['order_id']),
        'customer_name' => sanitize_text_field($_POST['customer_name']),
        'customer_email' => sanitize_email($_POST['customer_email']),
        'destination_address' => sanitize_textarea_field($_POST['destination_address']),
        'carrier' => sanitize_text_field($_POST['carrier']),
        'weight' => floatval($_POST['weight']),
    );

    // Generate tracking ID
    $order_data['tracking_id'] = aiot_generate_tracking_id();

    // Create order
    $result = AIOT_Tracking_Engine::create_order_tracking($order_data);

    if ($result) {
        wp_send_json_success(array(
            'message' => __('Order created successfully.', 'ai-order-tracker'),
            'tracking_id' => $result,
            'reload' => true
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to create order.', 'ai-order-tracker')));
    }
}

/**
 * AJAX save zone
 */
function aiot_ajax_save_zone() {
    // Verify nonce
    if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
    }

    // Get and sanitize form data
    $zone_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'type' => sanitize_text_field($_POST['type']),
        'delivery_days' => intval($_POST['delivery_days']),
        'delivery_cost' => floatval($_POST['delivery_cost']),
        'description' => sanitize_textarea_field($_POST['description']),
        'countries' => isset($_POST['countries']) ? array_map('sanitize_text_field', $_POST['countries']) : array(),
        'states' => isset($_POST['states']) ? array_map('sanitize_text_field', $_POST['states']) : array(),
        'cities' => isset($_POST['cities']) ? array_map('sanitize_text_field', $_POST['cities']) : array(),
    );

    // Create zone
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_zones';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $zone_data['name'],
            'type' => $zone_data['type'],
            'delivery_days' => $zone_data['delivery_days'],
            'delivery_cost' => $zone_data['delivery_cost'],
            'description' => $zone_data['description'],
            'countries' => json_encode($zone_data['countries']),
            'states' => json_encode($zone_data['states']),
            'cities' => json_encode($zone_data['cities']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result) {
        wp_send_json_success(array(
            'message' => __('Zone created successfully.', 'ai-order-tracker'),
            'reload' => true
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to create zone.', 'ai-order-tracker')));
    }
}

/**
 * AJAX save courier
 */
function aiot_ajax_save_courier() {
    // Verify nonce
    if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
    }

    // Get and sanitize form data
    $courier_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'slug' => sanitize_title($_POST['slug']),
        'description' => sanitize_textarea_field($_POST['description']),
        'url_pattern' => esc_url_raw($_POST['url_pattern']),
        'api_endpoint' => esc_url_raw($_POST['api_endpoint']),
        'api_key' => sanitize_text_field($_POST['api_key']),
    );

    // Create courier
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_couriers';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $courier_data['name'],
            'slug' => $courier_data['slug'],
            'description' => $courier_data['description'],
            'url_pattern' => $courier_data['url_pattern'],
            'api_endpoint' => $courier_data['api_endpoint'],
            'api_key' => $courier_data['api_key'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result) {
        wp_send_json_success(array(
            'message' => __('Courier created successfully.', 'ai-order-tracker'),
            'reload' => true
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to create courier.', 'ai-order-tracker')));
    }
}

// Register REST API routes
add_action('rest_api_init', function () {
    register_rest_route('aiot/v1', '/verify-delivery', array(
        'methods' => 'POST',
        'callback' => 'aiot_rest_verify_delivery',
        'permission_callback' => function () {
            return true; // Allow public access for verification
        },
        'args' => array(
            'tracking_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && !empty($param);
                }
            ),
            'order_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && !empty($param);
                }
            )
        )
    ));
});

/**
 * REST API callback for delivery verification
 */
function aiot_rest_verify_delivery($request) {
    $tracking_id = sanitize_text_field($request['tracking_id']);
    $order_id = sanitize_text_field($request['order_id']);
    
    // Get order by tracking ID
    $order = AIOT_Database::get_order_by_tracking_id($tracking_id);
    
    if (!$order) {
        return new WP_Error(
            'order_not_found',
            __('Tracking ID not found.', 'ai-order-tracker'),
            array('status' => 404)
        );
    }
    
    // Verify order ID matches
    if ($order['order_id'] !== $order_id) {
        return new WP_Error(
            'order_mismatch',
            __('Order ID does not match.', 'ai-order-tracker'),
            array('status' => 400)
        );
    }
    
    // Check if order is already delivered
    if ($order['status'] === 'delivered') {
        return new WP_Error(
            'already_delivered',
            __('Order is already marked as delivered.', 'ai-order-tracker'),
            array('status' => 400)
        );
    }
    
    // Update order status to delivered
    $update_data = array(
        'status' => 'delivered',
        'progress' => 100,
        'current_step' => 7,
    );
    
    $result = AIOT_Database::update_order($order['id'], $update_data);
    
    if (!$result) {
        return new WP_Error(
            'update_failed',
            __('Failed to update order status.', 'ai-order-tracker'),
            array('status' => 500)
        );
    }
    
    // Add delivery verification event
    $event_data = array(
        'order_id' => $order['id'],
        'event_type' => 'status_update',
        'event_status' => 'delivered',
        'location' => $order['location'] || 'Customer Address',
        'description' => 'Package delivered successfully - verified by customer',
        'timestamp' => current_time('mysql'),
    );
    
    AIOT_Database::add_tracking_event($event_data);
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => __('Delivery verified successfully! Order status has been updated to delivered.', 'ai-order-tracker'),
        'data' => array(
            'tracking_id' => $tracking_id,
            'status' => 'delivered',
            'progress' => 100
        )
    ), 200);
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'aiot_activate');
register_deactivation_hook(__FILE__, 'aiot_deactivate');