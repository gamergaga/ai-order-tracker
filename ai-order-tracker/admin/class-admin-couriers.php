<?php
/**
 * Admin couriers management class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Admin_Couriers
 */
class AIOT_Admin_Couriers {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_aiot_admin_get_couriers', array($this, 'get_couriers'));
        add_action('wp_ajax_aiot_admin_get_courier', array($this, 'get_courier'));
        add_action('wp_ajax_aiot_admin_create_courier', array($this, 'create_courier'));
        add_action('wp_ajax_aiot_admin_update_courier', array($this, 'update_courier'));
        add_action('wp_ajax_aiot_admin_delete_courier', array($this, 'delete_courier'));
        add_action('wp_ajax_aiot_admin_test_courier_api', array($this, 'test_courier_api'));
        add_action('wp_ajax_aiot_admin_import_default_couriers', array($this, 'import_default_couriers'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register courier API key settings
        register_setting('aiot_courier_settings', 'aiot_ups_api_key');
        register_setting('aiot_courier_settings', 'aiot_fedex_api_key');
        register_setting('aiot_courier_settings', 'aiot_dhl_api_key');
        register_setting('aiot_courier_settings', 'aiot_usps_api_key');
        
        // Add settings section
        add_settings_section(
            'aiot_courier_api_section',
            __('Courier API Keys', 'ai-order-tracker'),
            array($this, 'courier_api_section_callback'),
            'aiot_courier_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'aiot_ups_api_key',
            __('UPS API Key', 'ai-order-tracker'),
            array($this, 'api_key_field_callback'),
            'aiot_courier_settings',
            'aiot_courier_api_section',
            array(
                'label_for' => 'aiot_ups_api_key',
                'courier' => 'ups',
                'description' => __('Enter your UPS API key for real-time tracking', 'ai-order-tracker')
            )
        );
        
        add_settings_field(
            'aiot_fedex_api_key',
            __('FedEx API Key', 'ai-order-tracker'),
            array($this, 'api_key_field_callback'),
            'aiot_courier_settings',
            'aiot_courier_api_section',
            array(
                'label_for' => 'aiot_fedex_api_key',
                'courier' => 'fedex',
                'description' => __('Enter your FedEx API key for real-time tracking', 'ai-order-tracker')
            )
        );
        
        add_settings_field(
            'aiot_dhl_api_key',
            __('DHL API Key', 'ai-order-tracker'),
            array($this, 'api_key_field_callback'),
            'aiot_courier_settings',
            'aiot_courier_api_section',
            array(
                'label_for' => 'aiot_dhl_api_key',
                'courier' => 'dhl',
                'description' => __('Enter your DHL API key for real-time tracking', 'ai-order-tracker')
            )
        );
        
        add_settings_field(
            'aiot_usps_api_key',
            __('USPS API Key', 'ai-order-tracker'),
            array($this, 'api_key_field_callback'),
            'aiot_courier_settings',
            'aiot_courier_api_section',
            array(
                'label_for' => 'aiot_usps_api_key',
                'courier' => 'usps',
                'description' => __('Enter your USPS API key for real-time tracking', 'ai-order-tracker')
            )
        );
    }

    /**
     * Courier API section callback
     */
    public function courier_api_section_callback() {
        echo '<p>' . __('Configure API keys for real-time tracking with major courier services.', 'ai-order-tracker') . '</p>';
    }

    /**
     * API key field callback
     */
    public function api_key_field_callback($args) {
        $option_name = $args['label_for'];
        $value = get_option($option_name, '');
        $courier = $args['courier'];
        $description = $args['description'];
        
        echo '<input type="password" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . esc_html($description) . '</p>';
        
        // Add test button
        echo '<button type="button" class="button button-secondary" onclick="aiotAdmin.testCourierApi(\'' . esc_js($courier) . '\')">' . __('Test Connection', 'ai-order-tracker') . '</button>';
        echo '<span class="aiot-test-result" id="aiot-test-' . esc_attr($courier) . '"></span>';
    }

    /**
     * Get couriers
     */
    public function get_couriers() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $args = array(
            'is_active' => isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : null,
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC',
            'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 0,
        );
        
        $couriers = AIOT_Courier_Manager::get_couriers($args);
        
        wp_send_json_success(array('data' => $couriers));
    }

    /**
     * Get courier
     */
    public function get_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $courier_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($courier_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid courier ID.', 'ai-order-tracker')));
        }
        
        $courier = AIOT_Courier_Manager::get_courier($courier_id);
        
        if ($courier === false) {
            wp_send_json_error(array('message' => __('Courier not found.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array('data' => $courier));
    }

    /**
     * Create courier
     */
    public function create_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize courier data
        $courier_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'slug' => isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'url_pattern' => isset($_POST['url_pattern']) ? esc_url_raw($_POST['url_pattern']) : '',
            'api_endpoint' => isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : '',
            'api_key' => isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '',
            'tracking_format' => isset($_POST['tracking_format']) ? sanitize_text_field($_POST['tracking_format']) : 'standard',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'settings' => isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array(),
            'meta' => isset($_POST['meta']) ? json_decode(stripslashes($_POST['meta']), true) : array(),
        );
        
        // Validate required fields
        if (empty($courier_data['name']) || empty($courier_data['slug'])) {
            wp_send_json_error(array('message' => __('Name and slug are required.', 'ai-order-tracker')));
        }
        
        // Create courier
        $courier_id = AIOT_Courier_Manager::create_courier($courier_data);
        
        if ($courier_id === false) {
            wp_send_json_error(array('message' => __('Failed to create courier.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Courier created successfully.', 'ai-order-tracker'),
            'data' => array(
                'id' => $courier_id,
            )
        ));
    }

    /**
     * Update courier
     */
    public function update_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $courier_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($courier_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid courier ID.', 'ai-order-tracker')));
        }
        
        // Get and sanitize courier data
        $courier_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'slug' => isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'url_pattern' => isset($_POST['url_pattern']) ? esc_url_raw($_POST['url_pattern']) : '',
            'api_endpoint' => isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : '',
            'api_key' => isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '',
            'tracking_format' => isset($_POST['tracking_format']) ? sanitize_text_field($_POST['tracking_format']) : 'standard',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'settings' => isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array(),
            'meta' => isset($_POST['meta']) ? json_decode(stripslashes($_POST['meta']), true) : array(),
        );
        
        // Validate required fields
        if (empty($courier_data['name']) || empty($courier_data['slug'])) {
            wp_send_json_error(array('message' => __('Name and slug are required.', 'ai-order-tracker')));
        }
        
        // Update courier
        $result = AIOT_Courier_Manager::update_courier($courier_id, $courier_data);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update courier.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Courier updated successfully.', 'ai-order-tracker'),
            'data' => array(
                'id' => $courier_id,
            )
        ));
    }

    /**
     * Delete courier
     */
    public function delete_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $courier_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($courier_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid courier ID.', 'ai-order-tracker')));
        }
        
        // Delete courier
        $result = AIOT_Courier_Manager::delete_courier($courier_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete courier.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Courier deleted successfully.', 'ai-order-tracker'),
            'data' => array(
                'id' => $courier_id,
            )
        ));
    }

    /**
     * Test courier API
     */
    public function test_courier_api() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $courier = isset($_POST['courier']) ? sanitize_text_field($_POST['courier']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($courier) || empty($api_key)) {
            wp_send_json_error(array('message' => __('Courier and API key are required.', 'ai-order-tracker')));
        }
        
        // Test API connection
        $result = AIOT_Real_Time_API::test_api_connection($courier, $api_key);
        
        wp_send_json($result);
    }

    /**
     * Import default couriers
     */
    public function import_default_couriers() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Include default couriers file
        require_once AIOT_PATH . 'includes/default-couriers.php';
        
        // Import default couriers
        $result = aiot_install_default_couriers();
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Default couriers imported successfully.', 'ai-order-tracker')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to import default couriers.', 'ai-order-tracker')
            ));
        }
    }

    /**
     * Render couriers page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="aiot-admin-page">
                <!-- Toolbar -->
                <div class="aiot-toolbar">
                    <div class="aiot-toolbar-left">
                        <button type="button" class="button button-primary" id="aiot-add-courier">
                            <?php _e('Add New Courier', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-import-default-couriers">
                            <?php _e('Import Default Couriers', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-refresh-couriers">
                            <?php _e('Refresh', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                    <div class="aiot-toolbar-right">
                        <label>
                            <input type="checkbox" id="aiot-active-only" checked>
                            <?php _e('Active Only', 'ai-order-tracker'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Couriers Table -->
                <div class="aiot-card">
                    <div class="aiot-card-content">
                        <div id="aiot-couriers-table-container">
                            <!-- Table will be loaded via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Courier Modal -->
        <div id="aiot-courier-modal" class="aiot-modal">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2 id="aiot-modal-title"><?php _e('Add New Courier', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                <div class="aiot-modal-body">
                    <form id="aiot-courier-form">
                        <div class="aiot-form-group">
                            <label for="aiot-courier-name"><?php _e('Name', 'ai-order-tracker'); ?> *</label>
                            <input type="text" id="aiot-courier-name" name="name" required>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-slug"><?php _e('Slug', 'ai-order-tracker'); ?> *</label>
                            <input type="text" id="aiot-courier-slug" name="slug" required>
                            <p class="description"><?php _e('Unique identifier for the courier (e.g., ups, fedex, dhl)', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-description"><?php _e('Description', 'ai-order-tracker'); ?></label>
                            <textarea id="aiot-courier-description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-url-pattern"><?php _e('Tracking URL Pattern', 'ai-order-tracker'); ?></label>
                            <input type="text" id="aiot-courier-url-pattern" name="url_pattern">
                            <p class="description"><?php _e('Use {tracking_id} as placeholder (e.g., https://tracker.com/track?id={tracking_id})', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-api-endpoint"><?php _e('API Endpoint', 'ai-order-tracker'); ?></label>
                            <input type="text" id="aiot-courier-api-endpoint" name="api_endpoint">
                            <p class="description"><?php _e('API endpoint for real-time tracking', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-api-key"><?php _e('API Key', 'ai-order-tracker'); ?></label>
                            <input type="password" id="aiot-courier-api-key" name="api_key">
                            <p class="description"><?php _e('API key for real-time tracking', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-tracking-format"><?php _e('Tracking Format', 'ai-order-tracker'); ?></label>
                            <select id="aiot-courier-tracking-format" name="tracking_format">
                                <option value="standard"><?php _e('Standard', 'ai-order-tracker'); ?></option>
                                <option value="ups"><?php _e('UPS', 'ai-order-tracker'); ?></option>
                                <option value="fedex"><?php _e('FedEx', 'ai-order-tracker'); ?></option>
                                <option value="dhl"><?php _e('DHL', 'ai-order-tracker'); ?></option>
                                <option value="usps"><?php _e('USPS', 'ai-order-tracker'); ?></option>
                            </select>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label>
                                <input type="checkbox" id="aiot-courier-active" name="is_active" value="1" checked>
                                <?php _e('Active', 'ai-order-tracker'); ?>
                            </label>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-settings"><?php _e('Settings', 'ai-order-tracker'); ?></label>
                            <textarea id="aiot-courier-settings" name="settings" rows="5" placeholder="<?php esc_attr_e('Enter JSON format settings', 'ai-order-tracker'); ?>"></textarea>
                            <p class="description"><?php _e('Additional settings in JSON format', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-courier-meta"><?php _e('Meta Data', 'ai-order-tracker'); ?></label>
                            <textarea id="aiot-courier-meta" name="meta" rows="3" placeholder="<?php esc_attr_e('Enter JSON format meta data', 'ai-order-tracker'); ?>"></textarea>
                            <p class="description"><?php _e('Additional meta data in JSON format', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <input type="hidden" id="aiot-courier-id" name="id" value="0">
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Save Courier', 'ai-order-tracker'); ?>
                            </button>
                            <button type="button" class="button aiot-modal-cancel">
                                <?php _e('Cancel', 'ai-order-tracker'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize couriers management
            aiotAdmin.courierManager.init();
        });
        </script>
        <?php
    }
}