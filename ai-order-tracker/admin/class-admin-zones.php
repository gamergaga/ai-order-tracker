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
        add_action('wp_ajax_aiot_get_zones', array($this, 'ajax_get_zones'));
        add_action('wp_ajax_aiot_install_default_zones', array($this, 'ajax_install_default_zones'));
        
        // Add AJAX handler for loading countries
        add_action('wp_ajax_aiot_load_countries', array($this, 'ajax_load_countries'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on zones page
        if (strpos($hook, 'aiot-zones') === false) {
            return;
        }
        
        // Check if constants are defined, if not, define them with fallbacks
        if (!defined('AIOT_URL')) {
            define('AIOT_URL', plugin_dir_url(dirname(__FILE__) . '/ai-order-tracker.php'));
        }
        if (!defined('AIOT_VERSION')) {
            define('AIOT_VERSION', '2.0.0');
        }
        
        wp_enqueue_style('aiot-admin-zones', AIOT_URL . 'admin/css/admin-zones.css', array(), AIOT_VERSION);
        wp_enqueue_script('aiot-admin-zones', AIOT_URL . 'admin/js/admin-zones.js', array('jquery'), AIOT_VERSION, true);
        
        wp_localize_script('aiot-admin-zones', 'aiot_zones', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiot_admin_nonce'),
            'map_center_lat' => 20.0,
            'map_center_lng' => 0.0,
            'map_zoom' => 2
        ));
        
        wp_localize_script('aiot-admin-zones', 'aiot_zones_i18n', array(
            'select_country' => __('Select Country', 'ai-order-tracker'),
            'select_state' => __('Select State', 'ai-order-tracker'),
            'select_region' => __('Select Region', 'ai-order-tracker'),
            'confirm_delete' => __('Are you sure you want to delete this zone?', 'ai-order-tracker'),
            'confirm_install' => __('Are you sure you want to install default zones?', 'ai-order-tracker'),
            'zone_name_required' => __('Zone name is required', 'ai-order-tracker'),
            'zone_type_required' => __('Zone type is required', 'ai-order-tracker'),
            'location_required' => __('Please add at least one location', 'ai-order-tracker'),
            'country_required' => __('Please select a country', 'ai-order-tracker'),
            'state_required' => __('Please select a state', 'ai-order-tracker'),
            'zone_type_required_first' => __('Please select a zone type first', 'ai-order-tracker')
        ));
    }
    
    /**
     * Render zones page
     */
    public function render_zones_page() {
        ?>
        <div class="wrap aiot-admin-zones">
            <h1><?php echo esc_html__('Delivery Zones', 'ai-order-tracker'); ?></h1>
            
            <div class="aiot-zones-actions">
                <button type="button" class="button button-primary" id="aiot-add-zone-btn">
                    <?php echo esc_html__('Add New Zone', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-install-default-zones-btn">
                    <?php echo esc_html__('Install Default Zones', 'ai-order-tracker'); ?>
                </button>
            </div>
            
            <div class="aiot-zones-container">
                <div class="aiot-zones-list">
                    <table class="wp-list-table widefat fixed striped aiot-zones-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Zone Name', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Delivery Days Range', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Processing Days Range', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Countries', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Cities', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Status', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Actions', 'ai-order-tracker'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="aiot-zones-tbody">
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
                                            $delivery_days = !empty($zone['delivery_days']) ? json_decode($zone['delivery_days'], true) : null;
                                            if (is_array($delivery_days) && isset($delivery_days['min']) && isset($delivery_days['max'])) {
                                                echo esc_html($delivery_days['min']) . ' - ' . esc_html($delivery_days['max']) . ' ' . esc_html__('days', 'ai-order-tracker');
                                            } else {
                                                echo esc_html(!empty($zone['delivery_days']) ? $zone['delivery_days'] : '1') . ' ' . esc_html__('days', 'ai-order-tracker');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $processing_days = !empty($zone['processing_days']) ? json_decode($zone['processing_days'], true) : null;
                                            if (is_array($processing_days) && isset($processing_days['min']) && isset($processing_days['max'])) {
                                                echo esc_html($processing_days['min']) . ' - ' . esc_html($processing_days['max']) . ' ' . esc_html__('days', 'ai-order-tracker');
                                            } else {
                                                echo esc_html(!empty($zone['processing_days']) ? $zone['processing_days'] : '1') . ' ' . esc_html__('days', 'ai-order-tracker');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($country_count > 0) {
                                                echo esc_html($country_count) . ' ' . _n('country', 'countries', $country_count, 'ai-order-tracker');
                                                
                                                // Show first few country names
                                                $country_names = array();
                                                foreach (array_slice($countries, 0, 3) as $country) {
                                                    $country_names[] = esc_html($country);
                                                }
                                                
                                                if (!empty($country_names)) {
                                                    echo '<div class="zone-details">' . implode(', ', $country_names);
                                                    if ($country_count > 3) {
                                                        echo ' ' . sprintf(__('and %d more', 'ai-order-tracker'), $country_count - 3);
                                                    }
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo esc_html__('No countries selected', 'ai-order-tracker');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $cities = !empty($zone['cities']) ? json_decode($zone['cities'], true) : array();
                                            $city_count = is_array($cities) ? count($cities) : 0;
                                            
                                            if ($city_count > 0) {
                                                echo esc_html($city_count) . ' ' . _n('city', 'cities', $city_count, 'ai-order-tracker');
                                                
                                                // Show first few city names
                                                $city_names = array();
                                                foreach (array_slice($cities, 0, 3) as $city) {
                                                    $city_names[] = esc_html($city);
                                                }
                                                
                                                if (!empty($city_names)) {
                                                    echo '<div class="zone-details">' . implode(', ', $city_names);
                                                    if ($city_count > 3) {
                                                        echo ' ' . sprintf(__('and %d more', 'ai-order-tracker'), $city_count - 3);
                                                    }
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo esc_html__('All cities', 'ai-order-tracker');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="zone-status <?php echo $zone['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $zone['is_active'] ? esc_html__('Active', 'ai-order-tracker') : esc_html__('Inactive', 'ai-order-tracker'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="zone-actions">
                                                <button type="button" class="button button-small edit-zone" data-zone-id="<?php echo esc_attr($zone['id']); ?>">
                                                    <?php echo esc_html__('Edit', 'ai-order-tracker'); ?>
                                                </button>
                                                <button type="button" class="button button-small delete-zone" data-zone-id="<?php echo esc_attr($zone['id']); ?>">
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
                
                <div class="aiot-zone-form-container" id="aiot-zone-form-container" style="display: none;">
                    <h2><?php echo esc_html__('Add/Edit Zone', 'ai-order-tracker'); ?></h2>
                    
                    <form id="aiot-zone-form" method="post">
                        <input type="hidden" name="zone_id" id="aiot-zone-id" value="">
                        <input type="hidden" name="action" value="aiot_save_zone">
                        <?php wp_nonce_field('aiot_admin_nonce', 'aiot_nonce'); ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="aiot-zone-name"><?php echo esc_html__('Zone Name', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                <input type="text" name="zone_name" id="aiot-zone-name" class="regular-text" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="aiot-zone-description"><?php echo esc_html__('Description', 'ai-order-tracker'); ?></label>
                                <input type="text" name="zone_description" id="aiot-zone-description" class="regular-text">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="aiot-delivery-days-min"><?php echo esc_html__('Min Delivery Days', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                <input type="number" name="delivery_days_min" id="aiot-delivery-days-min" min="1" value="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="aiot-delivery-days-max"><?php echo esc_html__('Max Delivery Days', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                <input type="number" name="delivery_days_max" id="aiot-delivery-days-max" min="1" value="3" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="aiot-processing-days-min"><?php echo esc_html__('Min Processing Days', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                <input type="number" name="processing_days_min" id="aiot-processing-days-min" min="0" value="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="aiot-processing-days-max"><?php echo esc_html__('Max Processing Days', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                <input type="number" name="processing_days_max" id="aiot-processing-days-max" min="0" value="1" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="aiot-zone-type"><?php echo esc_html__('Zone Type', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                <select name="zone_type" id="aiot-zone-type" required>
                                    <option value=""><?php echo esc_html__('Select Zone Type', 'ai-order-tracker'); ?></option>
                                    <option value="country"><?php echo esc_html__('Country', 'ai-order-tracker'); ?></option>
                                    <option value="state"><?php echo esc_html__('State/Province/Governorate', 'ai-order-tracker'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="aiot-zone-active"><?php echo esc_html__('Status', 'ai-order-tracker'); ?></label>
                                <select name="zone_active" id="aiot-zone-active">
                                    <option value="1"><?php echo esc_html__('Active', 'ai-order-tracker'); ?></option>
                                    <option value="0"><?php echo esc_html__('Inactive', 'ai-order-tracker'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label><?php echo esc_html__('Location', 'ai-order-tracker'); ?> <span class="required">*</span></label>
                                
                                <div class="location-selector">
                                    <div class="location-row">
                                        <div class="location-group zone-type-country" style="display: none;">
                                            <label for="aiot-country-region"><?php echo esc_html__('Country Region', 'ai-order-tracker'); ?></label>
                                            <select name="country_region" id="aiot-country-region" class="country-region-select">
                                                <option value=""><?php echo esc_html__('Select Region', 'ai-order-tracker'); ?></option>
                                                <option value="northern"><?php echo esc_html__('Northern Countries', 'ai-order-tracker'); ?></option>
                                                <option value="southern"><?php echo esc_html__('Southern Countries', 'ai-order-tracker'); ?></option>
                                                <option value="eastern"><?php echo esc_html__('Eastern Countries', 'ai-order-tracker'); ?></option>
                                                <option value="western"><?php echo esc_html__('Western Countries', 'ai-order-tracker'); ?></option>
                                            </select>
                                        </div>
                                        
                                        <div class="location-group zone-type-state" style="display: none;">
                                            <label for="aiot-state-region"><?php echo esc_html__('State/Province Region', 'ai-order-tracker'); ?></label>
                                            <select name="state_region" id="aiot-state-region" class="state-region-select">
                                                <option value=""><?php echo esc_html__('Select Region', 'ai-order-tracker'); ?></option>
                                                <option value="northern"><?php echo esc_html__('Northern States', 'ai-order-tracker'); ?></option>
                                                <option value="southern"><?php echo esc_html__('Southern States', 'ai-order-tracker'); ?></option>
                                                <option value="eastern"><?php echo esc_html__('Eastern States', 'ai-order-tracker'); ?></option>
                                                <option value="western"><?php echo esc_html__('Western States', 'ai-order-tracker'); ?></option>
                                            </select>
                                        </div>
                                        
                                        <div class="location-group">
                                            <label for="aiot-country"><?php echo esc_html__('Country', 'ai-order-tracker'); ?></label>
                                            <div class="select-with-search">
                                                <input type="text" class="search-input" placeholder="<?php echo esc_html__('Search countries...', 'ai-order-tracker'); ?>">
                                                <select name="country" id="aiot-country" class="country-select" required>
                                                    <option value=""><?php echo esc_html__('Select Country', 'ai-order-tracker'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="location-group">
                                            <label for="aiot-state"><?php echo esc_html__('State/Province', 'ai-order-tracker'); ?></label>
                                            <div class="select-with-search">
                                                <input type="text" class="search-input" placeholder="<?php echo esc_html__('Search states...', 'ai-order-tracker'); ?>">
                                                <select name="state" id="aiot-state" class="state-select" disabled>
                                                    <option value=""><?php echo esc_html__('Select State', 'ai-order-tracker'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="location-group">
                                            <label for="aiot-cities"><?php echo esc_html__('Cities', 'ai-order-tracker'); ?></label>
                                            <div class="select-with-search multiple-select">
                                                <input type="text" class="search-input" placeholder="<?php echo esc_html__('Search cities...', 'ai-order-tracker'); ?>">
                                                <select name="cities[]" id="aiot-cities" class="cities-select" multiple disabled>
                                                    <option value=""><?php echo esc_html__('Select Cities', 'ai-order-tracker'); ?></option>
                                                </select>
                                                <p class="description"><?php echo esc_html__('Leave empty to include all cities in the selected state', 'ai-order-tracker'); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="location-group">
                                            <label>&nbsp;</label>
                                            <button type="button" class="button" id="aiot-add-location">
                                                <?php echo esc_html__('Add Location', 'ai-order-tracker'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="selected-locations" id="aiot-selected-locations">
                                    <h4><?php echo esc_html__('Selected Locations', 'ai-order-tracker'); ?></h4>
                                    <div class="locations-list" id="aiot-locations-list">
                                        <!-- Selected locations will be displayed here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label><?php echo esc_html__('Map', 'ai-order-tracker'); ?></label>
                                <div id="aiot-zone-map" class="aiot-zone-map-container">
                                    <div class="map-placeholder">
                                        <p><?php echo esc_html__('Select locations to see them on the map', 'ai-order-tracker'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <input type="submit" name="submit" id="aiot-save-zone" class="button button-primary" value="<?php echo esc_html__('Save Zone', 'ai-order-tracker'); ?>">
                                <button type="button" class="button" id="aiot-cancel-zone">
                                    <?php echo esc_html__('Cancel', 'ai-order-tracker'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Zone Modal Template -->
        <div id="aiot-zone-modal" style="display:none;">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h3><?php echo esc_html__('Zone Details', 'ai-order-tracker'); ?></h3>
                    <span class="aiot-modal-close">&times;</span>
                </div>
                <div class="aiot-modal-body">
                    <!-- Zone details will be loaded here -->
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
        if (!check_ajax_referer('aiot_admin_nonce', 'aiot_nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize form data
        $zone_id = isset($_POST['zone_id']) ? intval($_POST['zone_id']) : 0;
        $zone_name = sanitize_text_field($_POST['zone_name']);
        $zone_description = sanitize_text_field($_POST['zone_description']);
        $zone_type = sanitize_text_field($_POST['zone_type']);
        $delivery_days_min = intval($_POST['delivery_days_min']);
        $delivery_days_max = intval($_POST['delivery_days_max']);
        $processing_days_min = intval($_POST['processing_days_min']);
        $processing_days_max = intval($_POST['processing_days_max']);
        $zone_active = isset($_POST['zone_active']) ? 1 : 0;
        $locations = isset($_POST['locations']) ? (array) $_POST['locations'] : array();
        
        // Validate required fields
        if (empty($zone_name)) {
            wp_send_json_error(array('message' => __('Zone name is required.', 'ai-order-tracker')));
        }
        
        if (empty($zone_type)) {
            wp_send_json_error(array('message' => __('Zone type is required.', 'ai-order-tracker')));
        }
        
        if (empty($locations)) {
            wp_send_json_error(array('message' => __('At least one location must be selected.', 'ai-order-tracker')));
        }
        
        // Prepare location data
        $countries = array();
        $states = array();
        $regions = array();
        
        foreach ($locations as $location) {
            if (isset($location['type'])) {
                if ($location['type'] === 'country' && isset($location['country'])) {
                    $countries[] = $location['country'];
                    if (isset($location['region'])) {
                        $regions[] = $location['region'];
                    }
                } elseif ($location['type'] === 'state' && isset($location['state'])) {
                    $states[] = $location['state'];
                    if (isset($location['region'])) {
                        $regions[] = $location['region'];
                    }
                }
            }
        }
        
        // Prepare data for saving
        $zone_data = array(
            'name' => $zone_name,
            'description' => $zone_description,
            'type' => $zone_type,
            'delivery_days' => json_encode(array('min' => $delivery_days_min, 'max' => $delivery_days_max)),
            'processing_days' => json_encode(array('min' => $processing_days_min, 'max' => $processing_days_max)),
            'is_active' => $zone_active,
            'countries' => json_encode($countries),
            'states' => json_encode($states),
            'regions' => json_encode($regions),
            'cities' => json_encode(array()) // Empty for now, can be extended later
        );
        
        // Save zone
        if ($zone_id > 0) {
            // Update existing zone
            $result = AIOT_Zone_Manager::update_zone($zone_id, $zone_data);
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Zone updated successfully.', 'ai-order-tracker'),
                    'reload' => true
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to update zone.', 'ai-order-tracker')));
            }
        } else {
            // Create new zone
            $result = AIOT_Zone_Manager::add_zone($zone_data);
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Zone created successfully.', 'ai-order-tracker'),
                    'reload' => true
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to create zone.', 'ai-order-tracker')));
            }
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
        $zone_id = isset($_POST['zone_id']) ? intval($_POST['zone_id']) : 0;
        
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
        $zone_id = isset($_POST['zone_id']) ? intval($_POST['zone_id']) : 0;
        
        if ($zone_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ai-order-tracker')));
        }
        
        // Get zone data
        $zone = AIOT_Zone_Manager::get_zone($zone_id);
        
        if ($zone) {
            wp_send_json_success(array('zone' => $zone));
        } else {
            wp_send_json_error(array('message' => __('Zone not found.', 'ai-order-tracker')));
        }
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
        $country_code = sanitize_text_field($_POST['country']);
        
        if (empty($country_code)) {
            wp_send_json_error(array('message' => __('Country code is required.', 'ai-order-tracker')));
        }
        
        // Get states
        $states = AIOT_Zone_Manager::get_states_for_country($country_code);
        
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
        
        if (empty($country) || empty($state)) {
            wp_send_json_error(array('message' => __('Country and state are required.', 'ai-order-tracker')));
        }
        
        // Get cities
        $cities = AIOT_Zone_Manager::get_cities_for_state($country, $state);
        
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
        $cities = isset($_POST['cities']) ? (array) $_POST['cities'] : array();
        
        // Get coordinates
        $coordinates = AIOT_Zone_Manager::get_coordinates_for_location($country, $state, $cities);
        
        wp_send_json_success(array(
            'coordinates' => $coordinates
        ));
    }
    
    /**
     * AJAX load countries
     */
    public function ajax_load_countries() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get countries data
        $countries = AIOT_Zone_Manager::get_countries_data();
        
        wp_send_json_success(array(
            'countries' => $countries
        ));
    }
    
    /**
     * Render page - Alias for render_zones_page()
     */
    public static function render_page() {
        $instance = new self();
        return $instance->render_zones_page();
    }

    /**
     * AJAX get zones
     */
    public function ajax_get_zones() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get zones
        $zones = AIOT_Zone_Manager::get_zones();
        $stats = AIOT_Zone_Manager::get_zone_statistics();
        
        wp_send_json_success(array(
            'zones' => $zones,
            'stats' => $stats
        ));
    }
}