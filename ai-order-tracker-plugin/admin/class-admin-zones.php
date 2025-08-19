<?php
/**
 * Admin Zones Management Class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

class AIOT_Admin_Zones {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_aiot_save_zone', array($this, 'ajax_save_zone'));
        add_action('wp_ajax_aiot_delete_zone', array($this, 'ajax_delete_zone'));
        add_action('wp_ajax_aiot_get_zone', array($this, 'ajax_get_zone'));
        add_action('wp_ajax_aiot_get_zones', array($this, 'ajax_get_zones'));
        add_action('wp_ajax_aiot_install_default_zones', array($this, 'ajax_install_default_zones'));
        add_action('wp_ajax_aiot_load_countries', array($this, 'ajax_load_countries'));
        add_action('wp_ajax_aiot_get_states_for_country', array($this, 'ajax_get_states_for_country'));
        add_action('wp_ajax_aiot_get_states_geojson', array($this, 'ajax_get_states_geojson'));
        add_action('wp_ajax_aiot_load_cities', array($this, 'ajax_load_cities'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on zones page
        if (strpos($hook, 'aiot-zones') === false) {
            return;
        }
        
        // Define constants if not already defined
        if (!defined('AIOT_URL')) {
            define('AIOT_URL', plugin_dir_url(dirname(__FILE__) . '/ai-order-tracker.php'));
        }
        if (!defined('AIOT_VERSION')) {
            define('AIOT_VERSION', '2.0.0');
        }
        
        // Enqueue Leaflet CSS and JS
        wp_enqueue_style('leaflet', AIOT_URL . 'assets/lib/leaflet/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet', AIOT_URL . 'assets/lib/leaflet/leaflet.js', array(), '1.9.4', true);
        
        // Enqueue admin styles and scripts
        wp_enqueue_style('aiot-admin-zones', AIOT_URL . 'admin/css/admin-zones.css', array('leaflet'), AIOT_VERSION);
        wp_enqueue_script('aiot-admin-zones', AIOT_URL . 'admin/js/admin-zones.js', array('jquery', 'leaflet'), AIOT_VERSION, true);
        
        // Localize script
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
            'loading' => __('Loading...', 'ai-order-tracker'),
            'no_states' => __('No states found', 'ai-order-tracker'),
            'error_loading' => __('Error loading states', 'ai-order-tracker'),
            'confirm_delete' => __('Are you sure you want to delete this zone?', 'ai-order-tracker'),
            'confirm_install' => __('Are you sure you want to install default zones?', 'ai-order-tracker'),
            'zone_name_required' => __('Zone name is required', 'ai-order-tracker'),
            'zone_type_required' => __('Zone type is required', 'ai-order-tracker'),
            'country_required' => __('Please select a country', 'ai-order-tracker'),
            'state_required' => __('Please select a state', 'ai-order-tracker'),
            'zone_type_required_first' => __('Please select a zone type first', 'ai-order-tracker')
        ));
    }
    
    /**
     * Render page
     */
    public function render_zones_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="aiot-admin-page">
                <!-- Statistics Cards -->
                <div class="aiot-stats-grid">
                    <div class="aiot-stat-card">
                        <div class="aiot-stat-icon">🌍</div>
                        <div class="aiot-stat-content">
                            <h3><?php _e('Total Zones', 'ai-order-tracker'); ?></h3>
                            <div class="aiot-stat-number" id="aiot-total-zones">0</div>
                        </div>
                    </div>
                    
                    <div class="aiot-stat-card">
                        <div class="aiot-stat-icon">✅</div>
                        <div class="aiot-stat-content">
                            <h3><?php _e('Active Zones', 'ai-order-tracker'); ?></h3>
                            <div class="aiot-stat-number" id="aiot-active-zones">0</div>
                        </div>
                    </div>
                    
                    <div class="aiot-stat-card">
                        <div class="aiot-stat-icon">📦</div>
                        <div class="aiot-stat-content">
                            <h3><?php _e('Countries Covered', 'ai-order-tracker'); ?></h3>
                            <div class="aiot-stat-number" id="aiot-countries-covered">0</div>
                        </div>
                    </div>
                    
                    <div class="aiot-stat-card">
                        <div class="aiot-stat-icon">🚚</div>
                        <div class="aiot-stat-content">
                            <h3><?php _e('Avg. Delivery Days', 'ai-order-tracker'); ?></h3>
                            <div class="aiot-stat-number" id="aiot-avg-delivery-days">0</div>
                        </div>
                    </div>
                </div>
                
                <!-- Toolbar -->
                <div class="aiot-toolbar">
                    <div class="aiot-toolbar-left">
                        <button type="button" class="button button-primary" id="aiot-add-zone">
                            <?php _e('Add New Zone', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-install-default-zones">
                            <?php _e('Install Default Zones', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-export-zones">
                            <?php _e('Export Zones', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                    
                    <div class="aiot-toolbar-right">
                        <div class="aiot-search-box">
                            <input type="text" id="aiot-search-zones" placeholder="<?php esc_attr_e('Search zones...', 'ai-order-tracker'); ?>">
                            <span class="aiot-search-icon">🔍</span>
                        </div>
                        <select id="aiot-filter-status" class="aiot-filter-select">
                            <option value="all"><?php _e('All Status', 'ai-order-tracker'); ?></option>
                            <option value="active"><?php _e('Active', 'ai-order-tracker'); ?></option>
                            <option value="inactive"><?php _e('Inactive', 'ai-order-tracker'); ?></option>
                        </select>
                        <select id="aiot-filter-type" class="aiot-filter-select">
                            <option value="all"><?php _e('All Types', 'ai-order-tracker'); ?></option>
                            <option value="country"><?php _e('Country', 'ai-order-tracker'); ?></option>
                            <option value="state"><?php _e('State', 'ai-order-tracker'); ?></option>
                            <option value="city"><?php _e('City', 'ai-order-tracker'); ?></option>
                        </select>
                        <button type="button" class="button" id="aiot-refresh-zones">
                            <?php _e('Refresh', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Zones Table -->
                <div class="aiot-card">
                    <div class="aiot-card-header">
                        <h2><?php _e('Zones List', 'ai-order-tracker'); ?></h2>
                        <div class="aiot-card-actions">
                            <span class="aiot-item-count">
                                <?php _e('Showing', 'ai-order-tracker'); ?> <span id="aiot-showing-count">0</span> <?php _e('of', 'ai-order-tracker'); ?> <span id="aiot-total-count">0</span> <?php _e('zones', 'ai-order-tracker'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="aiot-table-container">
                        <table class="wp-list-table widefat fixed striped aiot-zones-table">
                            <thead>
                                <tr>
                                    <th class="aiot-col-checkbox">
                                        <input type="checkbox" id="aiot-select-all-zones">
                                    </th>
                                    <th class="aiot-col-name sortable" data-sort="name">
                                        <?php _e('Zone Name', 'ai-order-tracker'); ?>
                                        <span class="sorting-indicator"></span>
                                    </th>
                                    <th class="aiot-col-type">
                                        <?php _e('Type', 'ai-order-tracker'); ?>
                                    </th>
                                    <th class="aiot-col-delivery">
                                        <?php _e('Delivery Days', 'ai-order-tracker'); ?>
                                    </th>
                                    <th class="aiot-col-countries">
                                        <?php _e('Countries', 'ai-order-tracker'); ?>
                                    </th>
                                    <th class="aiot-col-status">
                                        <?php _e('Status', 'ai-order-tracker'); ?>
                                    </th>
                                    <th class="aiot-col-actions">
                                        <?php _e('Actions', 'ai-order-tracker'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="aiot-zones-tbody">
                                <tr>
                                    <td colspan="7" class="aiot-loading-row">
                                        <div class="aiot-spinner"></div>
                                        <p><?php _e('Loading zones...', 'ai-order-tracker'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="aiot-pagination">
                        <div class="aiot-pagination-info">
                            <?php _e('Page', 'ai-order-tracker'); ?> <span id="aiot-current-page">1</span> <?php _e('of', 'ai-order-tracker'); ?> <span id="aiot-total-pages">1</span>
                        </div>
                        <div class="aiot-pagination-controls">
                            <button type="button" class="button" id="aiot-prev-page" disabled>
                                <?php _e('Previous', 'ai-order-tracker'); ?>
                            </button>
                            <button type="button" class="button" id="aiot-next-page">
                                <?php _e('Next', 'ai-order-tracker'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="aiot-bulk-actions" style="display: none;">
                    <div class="aiot-bulk-info">
                        <span id="aiot-selected-count">0</span> <?php _e('zones selected', 'ai-order-tracker'); ?>
                    </div>
                    <div class="aiot-bulk-buttons">
                        <select id="aiot-bulk-action" class="aiot-bulk-select">
                            <option value=""><?php _e('Bulk Actions', 'ai-order-tracker'); ?></option>
                            <option value="activate"><?php _e('Activate', 'ai-order-tracker'); ?></option>
                            <option value="deactivate"><?php _e('Deactivate', 'ai-order-tracker'); ?></option>
                            <option value="delete"><?php _e('Delete', 'ai-order-tracker'); ?></option>
                        </select>
                        <button type="button" class="button" id="aiot-apply-bulk-action">
                            <?php _e('Apply', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-cancel-bulk-action">
                            <?php _e('Cancel', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zone Modal -->
        <div id="aiot-zone-modal" class="aiot-modal">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2 id="aiot-modal-title"><?php _e('Add New Zone', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                
                <!-- Step 1: Zone Type Selection -->
                <div id="aiot-zone-type-step" class="aiot-modal-step">
                    <div class="aiot-step-content">
                        <div class="aiot-step-header">
                            <h3><?php _e('Choose Your Zone Type', 'ai-order-tracker'); ?></h3>
                            <p class="aiot-step-description"><?php _e('Select the type of delivery zone you want to create. This will determine how you define your delivery areas.', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-zone-type-options">
                            <div class="aiot-zone-type-card" data-type="country">
                                <div class="aiot-type-icon">🌍</div>
                                <div class="aiot-type-content">
                                    <h4><?php _e('Country-Based Zones', 'ai-order-tracker'); ?></h4>
                                    <p><?php _e('Create delivery zones based on entire countries. Perfect for international shipping with consistent delivery times across whole nations.', 'ai-order-tracker'); ?></p>
                                    <div class="aiot-type-features">
                                        <ul>
                                            <li>✓ Select entire countries</li>
                                            <li>✓ All cities included automatically</li>
                                            <li>✓ Simple management for international shipping</li>
                                            <li>✓ Consistent delivery times per country</li>
                                        </ul>
                                    </div>
                                    <button type="button" class="aiot-select-type-btn button button-primary" data-type="country">
                                        <?php _e('Select Country Zones', 'ai-order-tracker'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="aiot-zone-type-card" data-type="state">
                                <div class="aiot-type-icon">🏛️</div>
                                <div class="aiot-type-content">
                                    <h4><?php _e('State/Province-Based Zones', 'ai-order-tracker'); ?></h4>
                                    <p><?php _e('Create delivery zones based on states, provinces, or governates. Ideal for regional shipping with different delivery times within countries.', 'ai-order-tracker'); ?></p>
                                    <div class="aiot-type-features">
                                        <ul>
                                            <li>✓ Select specific states/provinces</li>
                                            <li>✓ All cities within states included</li>
                                            <li>✓ Granular control for regional shipping</li>
                                            <li>✓ Different delivery times per region</li>
                                        </ul>
                                    </div>
                                    <button type="button" class="aiot-select-type-btn button button-primary" data-type="state">
                                        <?php _e('Select State Zones', 'ai-order-tracker'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="aiot-step-navigation">
                            <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Zone Configuration -->
                <div id="aiot-zone-config-step" class="aiot-modal-step" style="display: none;">
                    <form id="aiot-zone-form" method="post">
                        <input type="hidden" id="aiot-zone-id" name="zone_id" value="0">
                        <?php wp_nonce_field('aiot_admin_nonce', 'aiot_nonce'); ?>
                        <input type="hidden" name="action" value="aiot_save_zone">
                        <div class="aiot-step-content">
                            <div class="aiot-step-header">
                                <h3 id="aiot-config-title"><?php _e('Configure Your Zone', 'ai-order-tracker'); ?></h3>
                                <p id="aiot-config-description" class="aiot-config-description"><?php _e('Configure your delivery zone settings.', 'ai-order-tracker'); ?></p>
                                <div class="aiot-selected-type-info">
                                    <span class="aiot-selected-type-label"><?php _e('Selected Type:', 'ai-order-tracker'); ?></span>
                                    <span id="aiot-selected-type-display" class="aiot-selected-type-value"></span>
                                    <button type="button" id="aiot-change-type-btn" class="button button-small"><?php _e('Change Type', 'ai-order-tracker'); ?></button>
                                </div>
                            </div>
                            
                            <div class="aiot-form-body">
                                <!-- Zone Map at the Top -->
                                <div class="aiot-form-group aiot-map-container">
                                    <label><?php _e('Zone Map', 'ai-order-tracker'); ?></label>
                                    <div id="aiot-zone-map" class="aiot-zone-map"></div>
                                    <input type="hidden" id="aiot-zone-coordinates" name="coordinates">
                                </div>
                                
                                <!-- Form Fields in 2x2 Grid -->
                                <div class="aiot-form-grid">
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-name"><?php _e('Zone Name', 'ai-order-tracker'); ?> *</label>
                                        <input type="text" id="aiot-zone-name" name="name" required>
                                    </div>
                                    
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-type"><?php _e('Zone Type', 'ai-order-tracker'); ?></label>
                                        <select id="aiot-zone-type" name="type" disabled>
                                            <option value="country"><?php _e('Country', 'ai-order-tracker'); ?></option>
                                            <option value="state"><?php _e('State/Province/Governate', 'ai-order-tracker'); ?></option>
                                        </select>
                                        <p class="description"><?php _e('Zone type was selected in the previous step', 'ai-order-tracker'); ?></p>
                                    </div>
                                    
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-delivery-days-min"><?php _e('Minimum Delivery Days', 'ai-order-tracker'); ?> *</label>
                                        <select id="aiot-zone-delivery-days-min" name="delivery_days_min" required>
                                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-delivery-days-max"><?php _e('Maximum Delivery Days', 'ai-order-tracker'); ?> *</label>
                                        <select id="aiot-zone-delivery-days-max" name="delivery_days_max" required>
                                            <?php for ($i = 1; $i <= 100; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Processing Days -->
                                <div class="aiot-form-row">
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-processing-days"><?php _e('Processing Days', 'ai-order-tracker'); ?></label>
                                        <select id="aiot-zone-processing-days" name="processing_days">
                                            <?php for ($i = 0; $i <= 20; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 1 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Location Selection -->
                                <div class="aiot-form-row">
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-country"><?php _e('Country', 'ai-order-tracker'); ?></label>
                                        <div class="aiot-select-with-search">
                                            <input type="text" id="aiot-zone-country-search" class="aiot-search-input" placeholder="<?php esc_attr_e('Search countries...', 'ai-order-tracker'); ?>" disabled>
                                            <select id="aiot-zone-country" name="country" disabled>
                                                <option value=""><?php _e('Select Country', 'ai-order-tracker'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="aiot-form-row">
                                    <div class="aiot-form-group">
                                        <label for="aiot-zone-state"><?php _e('State/Province/Governate', 'ai-order-tracker'); ?></label>
                                        <div class="aiot-select-with-search">
                                            <input type="text" id="aiot-zone-state-search" class="aiot-search-input" placeholder="<?php esc_attr_e('Search states...', 'ai-order-tracker'); ?>" disabled>
                                            <select id="aiot-zone-state" name="state[]" multiple disabled>
                                                <option value=""><?php _e('Select State', 'ai-order-tracker'); ?></option>
                                            </select>
                                        </div>
                                        <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple states', 'ai-order-tracker'); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Cities Selection (Automatic) -->
                                <div class="aiot-form-row">
                                    <div class="aiot-form-group">
                                        <label><?php _e('Cities', 'ai-order-tracker'); ?></label>
                                        <div class="aiot-cities-info">
                                            <p><?php _e('Cities will be automatically included when states are selected. All cities within the selected states will be part of this zone.', 'ai-order-tracker'); ?></p>
                                            <div id="aiot-selected-cities-count" class="aiot-selection-count" style="display: none;">
                                                <?php _e('Selected cities:', 'ai-order-tracker'); ?> <span class="count">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="aiot-form-group">
                                    <label>
                                        <input type="checkbox" name="is_active" value="1" checked>
                                        <?php _e('Active', 'ai-order-tracker'); ?>
                                    </label>
                                    <p class="description"><?php _e('Enable this zone for delivery', 'ai-order-tracker'); ?></p>
                                </div>
                            </div>
                            
                            <div class="aiot-modal-footer">
                                <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                                <button type="submit" class="button button-primary"><?php _e('Save Zone', 'ai-order-tracker'); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render page - Alias for render_zones_page()
     */
    public static function render_page() {
        $instance = new self();
        return $instance->render_zones_page();
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
        $zone_id = isset($_POST['zone_id']) ? intval($_POST['zone_id']) : 0;
        $zone_name = sanitize_text_field($_POST['name']);
        $zone_type = sanitize_text_field($_POST['type']);
        $delivery_days_min = intval($_POST['delivery_days_min']);
        $delivery_days_max = intval($_POST['delivery_days_max']);
        $processing_days = intval($_POST['processing_days']);
        $countries = isset($_POST['country']) ? (array) $_POST['country'] : array();
        $states = isset($_POST['state']) ? (array) $_POST['state'] : array();
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($zone_name)) {
            wp_send_json_error(array('message' => __('Zone name is required.', 'ai-order-tracker')));
        }
        
        if (empty($zone_type)) {
            wp_send_json_error(array('message' => __('Zone type is required.', 'ai-order-tracker')));
        }
        
        if (empty($countries)) {
            wp_send_json_error(array('message' => __('Please select at least one country.', 'ai-order-tracker')));
        }
        
        if ($zone_type === 'state' && empty($states)) {
            wp_send_json_error(array('message' => __('Please select at least one state.', 'ai-order-tracker')));
        }
        
        // Prepare data for saving
        $zone_data = array(
            'name' => $zone_name,
            'type' => $zone_type,
            'delivery_days' => json_encode(array('min' => $delivery_days_min, 'max' => $delivery_days_max)),
            'delivery_cost' => 0.00,
            'is_active' => $is_active,
            'countries' => $countries,
            'states' => $states,
            'cities' => array(), // Empty for now
            'description' => $zone_name . ' delivery zone',
            'coordinates' => '',
            'meta' => json_encode(array(
                'processing_days' => $processing_days
            ))
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
            $result = AIOT_Zone_Manager::create_zone($zone_data);
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
        
        // Calculate statistics
        $stats = array(
            'total' => count($zones),
            'active' => count(array_filter($zones, function($zone) { return $zone['is_active']; })),
            'countries' => 0,
            'avg_delivery_days' => 0
        );
        
        // Calculate countries covered and average delivery days
        $countries = array();
        $delivery_days = array();
        
        foreach ($zones as $zone) {
            $zone_countries = json_decode($zone['countries'], true);
            if (is_array($zone_countries)) {
                $countries = array_merge($countries, $zone_countries);
            }
            
            $zone_delivery_days = json_decode($zone['delivery_days'], true);
            if (is_array($zone_delivery_days) && isset($zone_delivery_days['max'])) {
                $delivery_days[] = $zone_delivery_days['max'];
            }
        }
        
        $stats['countries'] = count(array_unique($countries));
        $stats['avg_delivery_days'] = count($delivery_days) > 0 ? round(array_sum($delivery_days) / count($delivery_days)) : 0;
        
        wp_send_json_success(array(
            'zones' => $zones,
            'stats' => $stats
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
     * AJAX get states GeoJSON
     */
    public function ajax_get_states_geojson() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get GeoJSON file path
        $file_path = AIOT_PATH . 'assets/geo/states-world.geojson';
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            wp_send_json_success(array(
                'geojson' => $content
            ));
        } else {
            wp_send_json_error(array('message' => __('GeoJSON file not found.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX load cities
     */
    public function ajax_load_cities() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get cities data
        $cities = AIOT_Zone_Manager::get_cities_data();
        
        wp_send_json_success(array(
            'cities' => $cities
        ));
    }
}