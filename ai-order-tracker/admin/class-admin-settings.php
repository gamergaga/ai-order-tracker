<?php
/**
 * Admin settings class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Admin_Settings
 */
class AIOT_Admin_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_aiot_admin_get_settings', array($this, 'get_settings'));
        add_action('wp_ajax_aiot_admin_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_aiot_admin_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_aiot_admin_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_aiot_admin_reset_settings', array($this, 'reset_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings group
        register_setting('aiot_settings_group', 'aiot_general_settings');
        register_setting('aiot_settings_group', 'aiot_display_settings');
        register_setting('aiot_settings_group', 'aiot_email_settings');
        register_setting('aiot_settings_group', 'aiot_advanced_settings');

        // Add settings sections
        add_settings_section(
            'aiot_general_section',
            __('General Settings', 'ai-order-tracker'),
            array($this, 'general_section_callback'),
            'aiot_settings'
        );

        add_settings_section(
            'aiot_display_section',
            __('Display Settings', 'ai-order-tracker'),
            array($this, 'display_section_callback'),
            'aiot_settings'
        );

        add_settings_section(
            'aiot_email_section',
            __('Email Settings', 'ai-order-tracker'),
            array($this, 'email_section_callback'),
            'aiot_settings'
        );

        add_settings_section(
            'aiot_advanced_section',
            __('Advanced Settings', 'ai-order-tracker'),
            array($this, 'advanced_section_callback'),
            'aiot_settings'
        );

        // General settings fields
        add_settings_field(
            'aiot_enable_tracking',
            __('Enable Tracking', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_enable_tracking',
                'description' => __('Enable order tracking functionality', 'ai-order-tracker'),
                'section' => 'general'
            )
        );

        add_settings_field(
            'aiot_simulation_mode',
            __('Simulation Mode', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_simulation_mode',
                'description' => __('Enable realistic tracking simulation when real data is not available', 'ai-order-tracker'),
                'section' => 'general'
            )
        );

        add_settings_field(
            'aiot_auto_update',
            __('Auto Update', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_auto_update',
                'description' => __('Automatically update tracking status', 'ai-order-tracker'),
                'section' => 'general'
            )
        );

        add_settings_field(
            'aiot_default_carrier',
            __('Default Carrier', 'ai-order-tracker'),
            array($this, 'select_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_default_carrier',
                'options' => $this->get_carrier_options(),
                'description' => __('Default carrier service for new orders', 'ai-order-tracker'),
                'section' => 'general'
            )
        );

        add_settings_field(
            'aiot_cache_duration',
            __('Cache Duration', 'ai-order-tracker'),
            array($this, 'number_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_cache_duration',
                'description' => __('Cache duration in seconds (0 to disable caching)', 'ai-order-tracker'),
                'section' => 'general',
                'min' => 0,
                'max' => 86400,
                'step' => 60
            )
        );

        add_settings_field(
            'aiot_tracking_id_prefix',
            __('Tracking ID Prefix', 'ai-order-tracker'),
            array($this, 'text_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_tracking_id_prefix',
                'description' => __('Prefix for generated tracking IDs (e.g., TRACK, AWB, ORDER)', 'ai-order-tracker'),
                'section' => 'general',
                'placeholder' => __('Enter prefix (optional)', 'ai-order-tracker')
            )
        );

        add_settings_field(
            'aiot_tracking_id_format',
            __('Tracking ID Format', 'ai-order-tracker'),
            array($this, 'select_field_callback'),
            'aiot_settings',
            'aiot_general_section',
            array(
                'label_for' => 'aiot_tracking_id_format',
                'options' => array(
                    'alphanumeric' => __('Alphanumeric (ABC123)', 'ai-order-tracker'),
                    'numeric' => __('Numeric Only (123456)', 'ai-order-tracker'),
                    'custom' => __('Custom Format', 'ai-order-tracker')
                ),
                'description' => __('Format for generated tracking IDs', 'ai-order-tracker'),
                'section' => 'general'
            )
        );

        // Display settings fields
        add_settings_field(
            'aiot_theme',
            __('Theme', 'ai-order-tracker'),
            array($this, 'select_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_theme',
                'options' => array(
                    'modern' => __('Modern', 'ai-order-tracker'),
                    'classic' => __('Classic', 'ai-order-tracker'),
                    'minimal' => __('Minimal', 'ai-order-tracker')
                ),
                'description' => __('Select tracking page theme', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_primary_color',
            __('Primary Color', 'ai-order-tracker'),
            array($this, 'color_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_primary_color',
                'description' => __('Primary color for the tracking interface', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_secondary_color',
            __('Secondary Color', 'ai-order-tracker'),
            array($this, 'color_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_secondary_color',
                'description' => __('Secondary color for the tracking interface', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_show_progress',
            __('Show Progress', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_show_progress',
                'description' => __('Show progress bar and steps', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_show_map',
            __('Show Map', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_show_map',
                'description' => __('Show delivery route map', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_show_timeline',
            __('Show Timeline', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_show_timeline',
                'description' => __('Show package journey timeline', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_show_details',
            __('Show Details', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_show_details',
                'description' => __('Show order details section', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        add_settings_field(
            'aiot_animation_speed',
            __('Animation Speed', 'ai-order-tracker'),
            array($this, 'select_field_callback'),
            'aiot_settings',
            'aiot_display_section',
            array(
                'label_for' => 'aiot_animation_speed',
                'options' => array(
                    'slow' => __('Slow', 'ai-order-tracker'),
                    'normal' => __('Normal', 'ai-order-tracker'),
                    'fast' => __('Fast', 'ai-order-tracker')
                ),
                'description' => __('Animation speed for tracking interface', 'ai-order-tracker'),
                'section' => 'display'
            )
        );

        // Email settings fields
        add_settings_field(
            'aiot_enable_notifications',
            __('Enable Notifications', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_email_section',
            array(
                'label_for' => 'aiot_enable_notifications',
                'description' => __('Enable email notifications', 'ai-order-tracker'),
                'section' => 'email'
            )
        );

        add_settings_field(
            'aiot_status_change_email',
            __('Status Change Email', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_email_section',
            array(
                'label_for' => 'aiot_status_change_email',
                'description' => __('Send email when order status changes', 'ai-order-tracker'),
                'section' => 'email'
            )
        );

        add_settings_field(
            'aiot_delivery_email',
            __('Delivery Email', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_email_section',
            array(
                'label_for' => 'aiot_delivery_email',
                'description' => __('Send delivery reminder emails', 'ai-order-tracker'),
                'section' => 'email'
            )
        );

        add_settings_field(
            'aiot_email_template',
            __('Email Template', 'ai-order-tracker'),
            array($this, 'select_field_callback'),
            'aiot_settings',
            'aiot_email_section',
            array(
                'label_for' => 'aiot_email_template',
                'options' => array(
                    'default' => __('Default', 'ai-order-tracker'),
                    'modern' => __('Modern', 'ai-order-tracker'),
                    'minimal' => __('Minimal', 'ai-order-tracker')
                ),
                'description' => __('Email template style', 'ai-order-tracker'),
                'section' => 'email'
            )
        );

        // Advanced settings fields
        add_settings_field(
            'aiot_api_rate_limit',
            __('API Rate Limit', 'ai-order-tracker'),
            array($this, 'number_field_callback'),
            'aiot_settings',
            'aiot_advanced_section',
            array(
                'label_for' => 'aiot_api_rate_limit',
                'description' => __('API requests per hour (0 for unlimited)', 'ai-order-tracker'),
                'section' => 'advanced',
                'min' => 0,
                'max' => 1000,
                'step' => 10
            )
        );

        add_settings_field(
            'aiot_request_timeout',
            __('Request Timeout', 'ai-order-tracker'),
            array($this, 'number_field_callback'),
            'aiot_settings',
            'aiot_advanced_section',
            array(
                'label_for' => 'aiot_request_timeout',
                'description' => __('API request timeout in seconds', 'ai-order-tracker'),
                'section' => 'advanced',
                'min' => 1,
                'max' => 120,
                'step' => 1
            )
        );

        add_settings_field(
            'aiot_enable_cron',
            __('Enable Cron Jobs', 'ai-order-tracker'),
            array($this, 'checkbox_field_callback'),
            'aiot_settings',
            'aiot_advanced_section',
            array(
                'label_for' => 'aiot_enable_cron',
                'description' => __('Enable scheduled cron jobs', 'ai-order-tracker'),
                'section' => 'advanced'
            )
        );

        add_settings_field(
            'aiot_log_level',
            __('Log Level', 'ai-order-tracker'),
            array($this, 'select_field_callback'),
            'aiot_settings',
            'aiot_advanced_section',
            array(
                'label_for' => 'aiot_log_level',
                'options' => array(
                    'error' => __('Error Only', 'ai-order-tracker'),
                    'warning' => __('Warning and Error', 'ai-order-tracker'),
                    'info' => __('Info, Warning and Error', 'ai-order-tracker'),
                    'debug' => __('Debug, Info, Warning and Error', 'ai-order-tracker')
                ),
                'description' => __('Logging level for debugging', 'ai-order-tracker'),
                'section' => 'advanced'
            )
        );

        add_settings_field(
            'aiot_data_retention_days',
            __('Data Retention', 'ai-order-tracker'),
            array($this, 'number_field_callback'),
            'aiot_settings',
            'aiot_advanced_section',
            array(
                'label_for' => 'aiot_data_retention_days',
                'description' => __('Days to keep tracking data (0 to keep forever)', 'ai-order-tracker'),
                'section' => 'advanced',
                'min' => 0,
                'max' => 365,
                'step' => 1
            )
        );
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general tracking settings', 'ai-order-tracker') . '</p>';
    }

    /**
     * Display section callback
     */
    public function display_section_callback() {
        echo '<p>' . __('Customize the appearance of the tracking interface', 'ai-order-tracker') . '</p>';
    }

    /**
     * Email section callback
     */
    public function email_section_callback() {
        echo '<p>' . __('Configure email notification settings', 'ai-order-tracker') . '</p>';
    }

    /**
     * Advanced section callback
     */
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced configuration options', 'ai-order-tracker') . '</p>';
    }

    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $section = $args['section'];
        $settings = get_option('aiot_' . $section . '_settings', array());
        $value = isset($settings[$args['label_for']]) ? $settings[$args['label_for']] : 0;
        $checked = checked($value, 1, false);
        
        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="aiot_' . $section . '_settings[' . esc_attr($args['label_for']) . ']" value="1" ' . $checked . '>';
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    /**
     * Select field callback
     */
    public function select_field_callback($args) {
        $section = $args['section'];
        $settings = get_option('aiot_' . $section . '_settings', array());
        $value = isset($settings[$args['label_for']]) ? $settings[$args['label_for']] : '';
        
        echo '<select id="' . esc_attr($args['label_for']) . '" name="aiot_' . $section . '_settings[' . esc_attr($args['label_for']) . ']">';
        
        foreach ($args['options'] as $key => $label) {
            $selected = selected($value, $key, false);
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    /**
     * Text field callback
     */
    public function text_field_callback($args) {
        $section = $args['section'];
        $settings = get_option('aiot_' . $section . '_settings', array());
        $value = isset($settings[$args['label_for']]) ? $settings[$args['label_for']] : '';
        $placeholder = isset($args['placeholder']) ? 'placeholder="' . esc_attr($args['placeholder']) . '"' : '';
        
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="aiot_' . $section . '_settings[' . esc_attr($args['label_for']) . ']" value="' . esc_attr($value) . '" class="regular-text" ' . $placeholder . '>';
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    /**
     * Number field callback
     */
    public function number_field_callback($args) {
        $section = $args['section'];
        $settings = get_option('aiot_' . $section . '_settings', array());
        $value = isset($settings[$args['label_for']]) ? $settings[$args['label_for']] : '';
        $min = isset($args['min']) ? 'min="' . intval($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . intval($args['max']) . '"' : '';
        $step = isset($args['step']) ? 'step="' . intval($args['step']) . '"' : '';
        
        echo '<input type="number" id="' . esc_attr($args['label_for']) . '" name="aiot_' . $section . '_settings[' . esc_attr($args['label_for']) . ']" value="' . esc_attr($value) . '" class="small-text" ' . $min . ' ' . $max . ' ' . $step . '>';
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    /**
     * Color field callback
     */
    public function color_field_callback($args) {
        $section = $args['section'];
        $settings = get_option('aiot_' . $section . '_settings', array());
        $value = isset($settings[$args['label_for']]) ? $settings[$args['label_for']] : '';
        
        echo '<input type="color" id="' . esc_attr($args['label_for']) . '" name="aiot_' . $section . '_settings[' . esc_attr($args['label_for']) . ']" value="' . esc_attr($value) . '" class="aiot-color-picker">';
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    /**
     * Get courier options
     */
    private function get_carrier_options() {
        $couriers = AIOT_Courier_Manager::get_couriers(array('is_active' => true));
        $options = array('standard' => __('Standard', 'ai-order-tracker'));
        
        foreach ($couriers as $courier) {
            $options[$courier['slug']] = $courier['name'];
        }
        
        return $options;
    }

    /**
     * Get settings
     */
    public function get_settings() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $settings = array(
            'general' => get_option('aiot_general_settings', array()),
            'display' => get_option('aiot_display_settings', array()),
            'email' => get_option('aiot_email_settings', array()),
            'advanced' => get_option('aiot_advanced_settings', array()),
        );
        
        wp_send_json_success(array('data' => $settings));
    }

    /**
     * Save settings
     */
    public function save_settings() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize settings
        $general_settings = isset($_POST['general']) ? $this->sanitize_settings($_POST['general'], 'general') : array();
        $display_settings = isset($_POST['display']) ? $this->sanitize_settings($_POST['display'], 'display') : array();
        $email_settings = isset($_POST['email']) ? $this->sanitize_settings($_POST['email'], 'email') : array();
        $advanced_settings = isset($_POST['advanced']) ? $this->sanitize_settings($_POST['advanced'], 'advanced') : array();
        
        // Update settings
        update_option('aiot_general_settings', $general_settings);
        update_option('aiot_display_settings', $display_settings);
        update_option('aiot_email_settings', $email_settings);
        update_option('aiot_advanced_settings', $advanced_settings);
        
        wp_send_json_success(array('message' => __('Settings saved successfully.', 'ai-order-tracker')));
    }

    /**
     * Sanitize settings
     */
    private function sanitize_settings($settings, $section) {
        $sanitized = array();
        
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'enable_tracking':
                case 'simulation_mode':
                case 'auto_update':
                case 'show_progress':
                case 'show_map':
                case 'show_timeline':
                case 'show_details':
                case 'enable_notifications':
                case 'status_change_email':
                case 'delivery_email':
                case 'enable_cron':
                    $sanitized[$key] = (bool) $value;
                    break;
                    
                case 'cache_duration':
                case 'api_rate_limit':
                case 'request_timeout':
                case 'data_retention_days':
                    $sanitized[$key] = max(0, intval($value));
                    break;
                    
                case 'primary_color':
                case 'secondary_color':
                    $sanitized[$key] = sanitize_hex_color($value);
                    break;
                    
                case 'theme':
                case 'default_carrier':
                case 'animation_speed':
                case 'email_template':
                case 'log_level':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Export settings
     */
    public function export_settings() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $settings = array(
            'general' => get_option('aiot_general_settings', array()),
            'display' => get_option('aiot_display_settings', array()),
            'email' => get_option('aiot_email_settings', array()),
            'advanced' => get_option('aiot_advanced_settings', array()),
            'version' => AIOT_VERSION,
            'export_date' => current_time('mysql'),
        );
        
        $filename = 'aiot-settings-export-' . date('Y-m-d') . '.json';
        
        wp_send_json_success(array(
            'message' => __('Settings exported successfully.', 'ai-order-tracker'),
            'data' => array(
                'filename' => $filename,
                'settings' => $settings,
            )
        ));
    }

    /**
     * Import settings
     */
    public function import_settings() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        if (!isset($_FILES['settings_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'ai-order-tracker')));
        }
        
        $file = $_FILES['settings_file'];
        
        // Check file type
        $file_type = wp_check_filetype_and_ext($file['name']);
        if ($file_type['ext'] !== 'json') {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload a JSON file.', 'ai-order-tracker')));
        }
        
        // Read file content
        $content = file_get_contents($file['tmp_name']);
        $settings = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid JSON file.', 'ai-order-tracker')));
        }
        
        // Validate settings structure
        $required_sections = array('general', 'display', 'email', 'advanced');
        foreach ($required_sections as $section) {
            if (!isset($settings[$section])) {
                wp_send_json_error(array('message' => sprintf(__('Invalid settings file. Missing section: %s', 'ai-order-tracker'), $section)));
            }
        }
        
        // Sanitize and update settings
        update_option('aiot_general_settings', $this->sanitize_settings($settings['general'], 'general'));
        update_option('aiot_display_settings', $this->sanitize_settings($settings['display'], 'display'));
        update_option('aiot_email_settings', $this->sanitize_settings($settings['email'], 'email'));
        update_option('aiot_advanced_settings', $this->sanitize_settings($settings['advanced'], 'advanced'));
        
        wp_send_json_success(array('message' => __('Settings imported successfully.', 'ai-order-tracker')));
    }

    /**
     * Reset settings
     */
    public function reset_settings() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Reset to default settings
        update_option('aiot_general_settings', $this->get_default_settings('general'));
        update_option('aiot_display_settings', $this->get_default_settings('display'));
        update_option('aiot_email_settings', $this->get_default_settings('email'));
        update_option('aiot_advanced_settings', $this->get_default_settings('advanced'));
        
        wp_send_json_success(array('message' => __('Settings reset to defaults.', 'ai-order-tracker')));
    }

    /**
     * Get default settings
     */
    private function get_default_settings($section) {
        $defaults = array(
            'general' => array(
                'enable_tracking' => 1,
                'simulation_mode' => 1,
                'auto_update' => 1,
                'default_carrier' => 'standard',
                'cache_duration' => 3600,
            ),
            'display' => array(
                'theme' => 'modern',
                'primary_color' => '#0073aa',
                'secondary_color' => '#28a745',
                'show_progress' => 1,
                'show_map' => 0,
                'show_timeline' => 1,
                'show_details' => 1,
                'animation_speed' => 'normal',
            ),
            'email' => array(
                'enable_notifications' => 1,
                'status_change_email' => 1,
                'delivery_email' => 1,
                'email_template' => 'default',
            ),
            'advanced' => array(
                'api_rate_limit' => 100,
                'request_timeout' => 30,
                'enable_cron' => 1,
                'log_level' => 'error',
                'data_retention_days' => 90,
            ),
        );
        
        return isset($defaults[$section]) ? $defaults[$section] : array();
    }

    /**
     * Render settings page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="aiot-admin-page">
                <!-- Settings Tabs -->
                <div class="aiot-tabs">
                    <button type="button" class="aiot-tab-button active" data-tab="general">
                        <?php _e('General', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="aiot-tab-button" data-tab="display">
                        <?php _e('Display', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="aiot-tab-button" data-tab="email">
                        <?php _e('Email', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="aiot-tab-button" data-tab="advanced">
                        <?php _e('Advanced', 'ai-order-tracker'); ?>
                    </button>
                </div>
                
                <!-- Settings Form -->
                <form id="aiot-settings-form" method="post" action="options.php">
                    <?php
                    settings_fields('aiot_settings_group');
                    do_settings_sections('aiot_settings');
                    ?>
                    
                    <div class="aiot-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Settings', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-export-settings">
                            <?php _e('Export Settings', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-import-settings">
                            <?php _e('Import Settings', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-reset-settings">
                            <?php _e('Reset Settings', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Import Modal -->
        <div id="aiot-import-modal" class="aiot-modal">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2><?php _e('Import Settings', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                <div class="aiot-modal-body">
                    <form id="aiot-import-form" enctype="multipart/form-data">
                        <div class="aiot-form-group">
                            <label for="aiot-settings-file"><?php _e('Settings File', 'ai-order-tracker'); ?></label>
                            <input type="file" id="aiot-settings-file" name="settings_file" accept=".json" required>
                            <p class="description"><?php _e('Select a JSON settings file to import', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Import Settings', 'ai-order-tracker'); ?>
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
            // Initialize settings management
            aiotAdmin.settingsManager.init();
        });
        </script>
        <?php
    }
}