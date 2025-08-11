<?php
/**
 * Simplified Admin Zones class for AI Order Tracker
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Admin_Zones
 */
class AIOT_Admin_Zones {
    
    /**
     * Constructor
     */
    public function __construct() {
        // add_action('admin_menu', array($this, 'add_admin_menu')); // Commented out - menu added centrally
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_aiot_save_zone', array($this, 'ajax_save_zone'));
        add_action('wp_ajax_aiot_delete_zone', array($this, 'ajax_delete_zone'));
        add_action('wp_ajax_aiot_get_zone', array($this, 'ajax_get_zone'));
        add_action('wp_ajax_aiot_get_zone_coordinates', array($this, 'ajax_get_zone_coordinates'));
        add_action('wp_ajax_aiot_get_states_for_country', array($this, 'ajax_get_states_for_country'));
        add_action('wp_ajax_aiot_get_cities_for_state', array($this, 'ajax_get_cities_for_state'));
        add_action('wp_ajax_aiot_install_default_zones', array($this, 'ajax_install_default_zones'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ai-order-tracker',
            __('Delivery Zones', 'ai-order-tracker'),
            __('Delivery Zones', 'ai-order-tracker'),
            'manage_options',
            'ai-order-tracker-zones',
            array($this, 'render_zones_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'ai-order-tracker_page_aiot-zones') {
            return;
        }
        
        // Enqueue Leaflet for maps
        wp_enqueue_style('leaflet', AIOT_URL . 'assets/libs/leaflet.css', array(), '1.7.1');
        wp_enqueue_script('leaflet', AIOT_URL . 'assets/libs/leaflet.js', array(), '1.7.1', true);
        
        // Enqueue admin styles
        wp_enqueue_style('aiot-admin-zones', AIOT_URL . 'admin/css/admin-zones.css', array(), AIOT_VERSION);
        
        // Enqueue admin scripts
        wp_enqueue_script('aiot-admin-zones', AIOT_URL . 'admin/js/admin-zones.js', array('jquery', 'leaflet'), AIOT_VERSION, true);
        
        // Localize script
        wp_localize_script('aiot-admin-zones', 'aiot_zones', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiot_admin_nonce'),
            'map_center_lat' => 39.8283,
            'map_center_lng' => -98.5795,
            'map_zoom' => 4,
        ));
        
        // Localize i18n strings
        wp_localize_script('aiot-admin-zones', 'aiot_zones_i18n', array(
            'select_state' => __('Select State', 'ai-order-tracker'),
            'select_cities' => __('Select Cities', 'ai-order-tracker'),
            'confirm_delete' => __('Are you sure you want to delete this zone?', 'ai-order-tracker'),
            'confirm_install' => __('Are you sure you want to install default zones?', 'ai-order-tracker'),
        ));
    }
    
    /**
     * Render zones page
     */
    public function render_zones_page() {
        ?>
        <div class="wrap aiot-admin-zones">
            <h1><?php echo esc_html__('Delivery Zones', 'ai-order-tracker'); ?></h1>
            
            <div class="aiot-admin-header">
                <div class="aiot-admin-stats">
                    <?php $stats = AIOT_Zone_Manager::get_zone_statistics(); ?>
                    <div class="stat-card">
                        <h3><?php echo esc_html($stats['total_zones']); ?></h3>
                        <p><?php echo esc_html__('Total Zones', 'ai-order-tracker'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo esc_html($stats['active_zones']); ?></h3>
                        <p><?php echo esc_html__('Active Zones', 'ai-order-tracker'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo esc_html($stats['average_delivery_days']); ?></h3>
                        <p><?php echo esc_html__('Avg. Delivery Days', 'ai-order-tracker'); ?></p>
                    </div>
                </div>
                
                <div class="aiot-admin-actions">
                    <button type="button" class="button button-primary" id="aiot-add-zone-btn">
                        <?php echo esc_html__('Add New Zone', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="button" id="aiot-install-default-zones-btn">
                        <?php echo esc_html__('Install Default Zones', 'ai-order-tracker'); ?>
                    </button>
                </div>
            </div>
            
            <div class="aiot-admin-content">
                <div class="aiot-zones-list">
                    <div class="aiot-zones-table-container">
                        <table class="wp-list-table widefat fixed striped aiot-zones-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Zone Name', 'ai-order-tracker'); ?></th>
                                    <th><?php echo esc_html__('Delivery Days Range', 'ai-order-tracker'); ?></th>
                                    <th><?php echo esc_html__('Processing Days Range', 'ai-order-tracker'); ?></th>
                                    <th><?php echo esc_html__('Countries', 'ai-order-tracker'); ?></th>
                                    <th><?php echo esc_html__('Status', 'ai-order-tracker'); ?></th>
                                    <th><?php echo esc_html__('Actions', 'ai-order-tracker'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $zones = AIOT_Zone_Manager::get_zones();
                                if (empty($zones)) {
                                    ?>
                                    <tr>
                                        <td colspan="7" class="aiot-no-zones">
                                            <?php echo esc_html__('No zones found. Click "Add New Zone" to create your first zone.', 'ai-order-tracker'); ?>
                                        </td>
                                    </tr>
                                    <?php
                                } else {
                                    foreach ($zones as $zone) {
                                        $countries = json_decode($zone['countries'], true);
                                        $country_count = is_array($countries) ? count($countries) : 0;
                                        ?>
                                        <tr data-zone-id="<?php echo esc_attr($zone['id']); ?>">
                                            <td>
                                                <strong><?php echo esc_html($zone['name']); ?></strong>
                                                <div class="row-description"><?php echo esc_html($zone['description']); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                $delivery_days = json_decode($zone['delivery_days'], true);
                                                if (is_array($delivery_days) && isset($delivery_days['min']) && isset($delivery_days['max'])) {
                                                    echo esc_html($delivery_days['min']) . ' - ' . esc_html($delivery_days['max']) . ' ' . esc_html__('days', 'ai-order-tracker');
                                                } else {
                                                    echo esc_html($zone['delivery_days']) . ' ' . esc_html__('days', 'ai-order-tracker');
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $processing_days = json_decode($zone['processing_days'], true);
                                                if (is_array($processing_days) && isset($processing_days['min']) && isset($processing_days['max'])) {
                                                    echo esc_html($processing_days['min']) . ' - ' . esc_html($processing_days['max']) . ' ' . esc_html__('days', 'ai-order-tracker');
                                                } else {
                                                    echo esc_html__('1 - 2', 'ai-order-tracker') . ' ' . esc_html__('days', 'ai-order-tracker');
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($country_count > 0): ?>
                                                    <span class="country-count"><?php echo esc_html($country_count); ?> <?php echo esc_html__('countries', 'ai-order-tracker'); ?></span>
                                                <?php else: ?>
                                                    <span class="no-countries"><?php echo esc_html__('No countries', 'ai-order-tracker'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $zone['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $zone['is_active'] ? esc_html__('Active', 'ai-order-tracker') : esc_html__('Inactive', 'ai-order-tracker'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="row-actions">
                                                    <button type="button" class="button-link aiot-edit-zone" data-zone-id="<?php echo esc_attr($zone['id']); ?>">
                                                        <?php echo esc_html__('Edit', 'ai-order-tracker'); ?>
                                                    </button>
                                                    <span class="sep">|</span>
                                                    <button type="button" class="button-link aiot-delete-zone" data-zone-id="<?php echo esc_attr($zone['id']); ?>">
                                                        <?php echo esc_html__('Delete', 'ai-order-tracker'); ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zone Modal -->
        <div id="aiot-zone-modal" class="aiot-modal" style="display: none;">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2><?php echo esc_html__('Add/Edit Zone', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                <div class="aiot-modal-body">
                    <form id="aiot-zone-form">
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="zone-name"><?php echo esc_html__('Zone Name', 'ai-order-tracker'); ?> *</label>
                                <input type="text" id="zone-name" name="name" required>
                            </div>
                            <div class="aiot-form-col">
                                <label for="zone-type"><?php echo esc_html__('Zone Type', 'ai-order-tracker'); ?></label>
                                <select id="zone-type" name="type">
                                    <option value="country"><?php echo esc_html__('Country', 'ai-order-tracker'); ?></option>
                                    <option value="state"><?php echo esc_html__('State', 'ai-order-tracker'); ?></option>
                                    <option value="city"><?php echo esc_html__('City', 'ai-order-tracker'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="zone-delivery-days-min"><?php echo esc_html__('Min Delivery Days', 'ai-order-tracker'); ?> *</label>
                                <input type="number" id="zone-delivery-days-min" name="delivery_days_min" min="1" max="30" required>
                            </div>
                            <div class="aiot-form-col">
                                <label for="zone-delivery-days-max"><?php echo esc_html__('Max Delivery Days', 'ai-order-tracker'); ?> *</label>
                                <input type="number" id="zone-delivery-days-max" name="delivery_days_max" min="1" max="30" required>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="zone-processing-days-min"><?php echo esc_html__('Min Processing Days', 'ai-order-tracker'); ?> *</label>
                                <input type="number" id="zone-processing-days-min" name="processing_days_min" min="0" max="30" required>
                            </div>
                            <div class="aiot-form-col">
                                <label for="zone-processing-days-max"><?php echo esc_html__('Max Processing Days', 'ai-order-tracker'); ?> *</label>
                                <input type="number" id="zone-processing-days-max" name="processing_days_max" min="0" max="30" required>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="zone-description"><?php echo esc_html__('Description', 'ai-order-tracker'); ?></label>
                                <textarea id="zone-description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="zone-country"><?php echo esc_html__('Country', 'ai-order-tracker'); ?></label>
                                <select id="zone-country" name="country">
                                    <option value=""><?php echo esc_html__('Select Country', 'ai-order-tracker'); ?></option>
                                    <?php
                                    $countries = AIOT_Zone_Manager::get_countries_data();
                                    foreach ($countries as $country) {
                                        echo '<option value="' . esc_attr($country['code']) . '">' . esc_html($country['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aiot-form-col">
                                <label for="zone-state"><?php echo esc_html__('State', 'ai-order-tracker'); ?></label>
                                <select id="zone-state" name="state" disabled>
                                    <option value=""><?php echo esc_html__('Select State', 'ai-order-tracker'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="zone-cities"><?php echo esc_html__('Major Cities', 'ai-order-tracker'); ?></label>
                                <select id="zone-cities" name="cities[]" multiple disabled>
                                    <option value=""><?php echo esc_html__('Select Cities', 'ai-order-tracker'); ?></option>
                                </select>
                                <p class="description"><?php echo esc_html__('Hold Ctrl/Cmd to select multiple cities', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label><?php echo esc_html__('Zone Map', 'ai-order-tracker'); ?></label>
                                <div id="aiot-zone-map" class="aiot-zone-map"></div>
                                <p class="description"><?php echo esc_html__('Map will auto-update based on selected locations', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label>
                                    <input type="checkbox" id="zone-active" name="is_active" value="1" checked>
                                    <?php echo esc_html__('Active', 'ai-order-tracker'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" id="zone-id" name="zone_id" value="">
                        <input type="hidden" id="zone-coordinates" name="coordinates" value="">
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Save Zone', 'ai-order-tracker'); ?>
                            </button>
                            <button type="button" class="button aiot-modal-close">
                                <?php echo esc_html__('Cancel', 'ai-order-tracker'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX save zone
     */
    public function ajax_save_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize form data
        $zone_id = intval($_POST['zone_id']);
        $delivery_days_min = intval($_POST['delivery_days_min']);
        $delivery_days_max = intval($_POST['delivery_days_max']);
        
        // Validate delivery days range
        if ($delivery_days_min < 1 || $delivery_days_max < 1 || $delivery_days_min > $delivery_days_max) {
            wp_send_json_error(array('message' => __('Invalid delivery days range.', 'ai-order-tracker')));
        }
        
        $zone_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'type' => sanitize_text_field($_POST['type']),
            'delivery_days' => json_encode(array('min' => $delivery_days_min, 'max' => $delivery_days_max)),
            'description' => sanitize_textarea_field($_POST['description']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'coordinates' => sanitize_text_field($_POST['coordinates']),
        );
        
        // Get location data
        $country = sanitize_text_field($_POST['country']);
        $state = sanitize_text_field($_POST['state']);
        $cities = isset($_POST['cities']) ? array_map('sanitize_text_field', $_POST['cities']) : array();
        
        // Set location arrays
        $zone_data['countries'] = !empty($country) ? array($country) : array();
        $zone_data['states'] = !empty($state) ? array($state) : array();
        $zone_data['cities'] = $cities;
        
        // Save zone
        if ($zone_id > 0) {
            // Update existing zone
            $result = AIOT_Zone_Manager::update_zone($zone_id, $zone_data);
        } else {
            // Create new zone
            $result = AIOT_Zone_Manager::create_zone($zone_data);
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Zone saved successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save zone.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX delete zone
     */
    public function ajax_delete_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get zone ID
        $zone_id = intval($_POST['zone_id']);
        
        if ($zone_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ai-order-tracker')));
        }
        
        // Delete zone
        $result = AIOT_Zone_Manager::delete_zone($zone_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Zone deleted successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete zone.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX get zone
     */
    public function ajax_get_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get zone ID
        $zone_id = intval($_POST['zone_id']);
        
        if ($zone_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ai-order-tracker')));
        }
        
        // Get zone data
        $zone = AIOT_Zone_Manager::get_zone($zone_id);
        
        if ($zone) {
            wp_send_json_success($zone);
        } else {
            wp_send_json_error(array('message' => __('Zone not found.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX get zone coordinates
     */
    public function ajax_get_zone_coordinates() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get location data
        $country = sanitize_text_field($_POST['country']);
        $state = sanitize_text_field($_POST['state']);
        $cities = isset($_POST['cities']) ? array_map('sanitize_text_field', $_POST['cities']) : array();
        
        // Get coordinates
        $coordinates = array();
        
        if (!empty($country)) {
            $coordinates[] = AIOT_Zone_Manager::get_country_coordinates($country);
        }
        
        if (!empty($state)) {
            $coordinates[] = AIOT_Zone_Manager::get_state_coordinates($state);
        }
        
        // Add some major city coordinates
        if (!empty($cities)) {
            $city_coords = array(
                'New York' => array(40.7128, -74.0060),
                'Los Angeles' => array(34.0522, -118.2437),
                'Chicago' => array(41.8781, -87.6298),
                'Houston' => array(29.7604, -95.3698),
                'Phoenix' => array(33.4484, -112.0740),
                'Philadelphia' => array(39.9526, -75.1652),
                'San Antonio' => array(29.4241, -98.4936),
                'San Diego' => array(32.7157, -117.1611),
                'Dallas' => array(32.7767, -96.7970),
                'San Jose' => array(37.3382, -121.8863),
            );
            
            foreach ($cities as $city) {
                if (isset($city_coords[$city])) {
                    $coordinates[] = $city_coords[$city];
                }
            }
        }
        
        wp_send_json_success(array(
            'coordinates' => $coordinates
        ));
    }
    
    /**
     * AJAX get states for country
     */
    public function ajax_get_states_for_country() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get country code
        $country = sanitize_text_field($_POST['country']);
        
        // Get states
        $states = AIOT_Zone_Manager::get_states_data($country);
        
        wp_send_json_success(array(
            'states' => $states
        ));
    }
    
    /**
     * AJAX get cities for state
     */
    public function ajax_get_cities_for_state() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get location data
        $country = sanitize_text_field($_POST['country']);
        $state = sanitize_text_field($_POST['state']);
        
        // Get cities
        $cities = AIOT_Zone_Manager::get_major_cities($country, $state);
        
        wp_send_json_success(array(
            'cities' => $cities
        ));
    }
    
    /**
     * AJAX install default zones
     */
    public function ajax_install_default_zones() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Install default zones
        $result = AIOT_Zone_Manager::install_default_zones();
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Default zones installed successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to install default zones.', 'ai-order-tracker')));
        }
    }
    
    /**
     * Render page - Alias for render_zones_page()
     */
    public static function render_page() {
        $instance = new self();
        return $instance->render_zones_page();
    }
}