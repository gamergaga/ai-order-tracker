<?php
/**
 * Admin zones management class
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
        add_action('wp_ajax_aiot_admin_get_zones', array($this, 'get_zones'));
        add_action('wp_ajax_aiot_admin_get_zone', array($this, 'get_zone'));
        add_action('wp_ajax_aiot_admin_create_zone', array($this, 'create_zone'));
        add_action('wp_ajax_aiot_admin_update_zone', array($this, 'update_zone'));
        add_action('wp_ajax_aiot_admin_delete_zone', array($this, 'delete_zone'));
        add_action('wp_ajax_aiot_admin_get_geo_data', array($this, 'get_geo_data'));
        add_action('wp_ajax_aiot_admin_import_zones', array($this, 'import_zones'));
        add_action('wp_ajax_aiot_admin_export_zones', array($this, 'export_zones'));
    }

    /**
     * Get zones
     */
    public function get_zones() {
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
        
        $zones = AIOT_Zone_Manager::get_zones($args);
        
        wp_send_json_success(array('data' => $zones));
    }

    /**
     * Get zone
     */
    public function get_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $zone_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($zone_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ai-order-tracker')));
        }
        
        $zone = AIOT_Zone_Manager::get_zone($zone_id);
        
        if ($zone === false) {
            wp_send_json_error(array('message' => __('Zone not found.', 'ai-order-tracker')));
        }
        
        // Decode JSON fields
        $zone['countries'] = json_decode($zone['countries'], true);
        $zone['states'] = json_decode($zone['states'], true);
        $zone['cities'] = json_decode($zone['cities'], true);
        $zone['postal_codes'] = json_decode($zone['postal_codes'], true);
        $zone['coordinates'] = json_decode($zone['coordinates'], true);
        $zone['meta'] = json_decode($zone['meta'], true);
        
        wp_send_json_success(array('data' => $zone));
    }

    /**
     * Create zone
     */
    public function create_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize zone data
        $zone_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'country',
            'countries' => isset($_POST['countries']) ? json_decode(stripslashes($_POST['countries']), true) : array(),
            'states' => isset($_POST['states']) ? json_decode(stripslashes($_POST['states']), true) : array(),
            'cities' => isset($_POST['cities']) ? json_decode(stripslashes($_POST['cities']), true) : array(),
            'postal_codes' => isset($_POST['postal_codes']) ? json_decode(stripslashes($_POST['postal_codes']), true) : array(),
            'coordinates' => isset($_POST['coordinates']) ? json_decode(stripslashes($_POST['coordinates']), true) : array(),
            'delivery_days' => isset($_POST['delivery_days']) ? intval($_POST['delivery_days']) : 1,
            'delivery_cost' => isset($_POST['delivery_cost']) ? floatval($_POST['delivery_cost']) : 0.00,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta' => isset($_POST['meta']) ? json_decode(stripslashes($_POST['meta']), true) : array(),
        );
        
        // Validate required fields
        if (empty($zone_data['name']) || empty($zone_data['delivery_days'])) {
            wp_send_json_error(array('message' => __('Name and delivery days are required.', 'ai-order-tracker')));
        }
        
        // Create zone
        $zone_id = AIOT_Zone_Manager::create_zone($zone_data);
        
        if ($zone_id === false) {
            wp_send_json_error(array('message' => __('Failed to create zone.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Zone created successfully.', 'ai-order-tracker'),
            'data' => array(
                'id' => $zone_id,
            )
        ));
    }

    /**
     * Update zone
     */
    public function update_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $zone_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($zone_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ai-order-tracker')));
        }
        
        // Get and sanitize zone data
        $zone_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'country',
            'countries' => isset($_POST['countries']) ? json_decode(stripslashes($_POST['countries']), true) : array(),
            'states' => isset($_POST['states']) ? json_decode(stripslashes($_POST['states']), true) : array(),
            'cities' => isset($_POST['cities']) ? json_decode(stripslashes($_POST['cities']), true) : array(),
            'postal_codes' => isset($_POST['postal_codes']) ? json_decode(stripslashes($_POST['postal_codes']), true) : array(),
            'coordinates' => isset($_POST['coordinates']) ? json_decode(stripslashes($_POST['coordinates']), true) : array(),
            'delivery_days' => isset($_POST['delivery_days']) ? intval($_POST['delivery_days']) : 1,
            'delivery_cost' => isset($_POST['delivery_cost']) ? floatval($_POST['delivery_cost']) : 0.00,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta' => isset($_POST['meta']) ? json_decode(stripslashes($_POST['meta']), true) : array(),
        );
        
        // Validate required fields
        if (empty($zone_data['name']) || empty($zone_data['delivery_days'])) {
            wp_send_json_error(array('message' => __('Name and delivery days are required.', 'ai-order-tracker')));
        }
        
        // Update zone
        $result = AIOT_Zone_Manager::update_zone($zone_id, $zone_data);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update zone.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Zone updated successfully.', 'ai-order-tracker'),
            'data' => array(
                'id' => $zone_id,
            )
        ));
    }

    /**
     * Delete zone
     */
    public function delete_zone() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $zone_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($zone_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ai-order-tracker')));
        }
        
        // Delete zone
        $result = AIOT_Zone_Manager::delete_zone($zone_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete zone.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Zone deleted successfully.', 'ai-order-tracker'),
            'data' => array(
                'id' => $zone_id,
            )
        ));
    }

    /**
     * Get geo data
     */
    public function get_geo_data() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'countries';
        $parent = isset($_GET['parent']) ? sanitize_text_field($_GET['parent']) : '';
        
        $data = array();
        
        switch ($type) {
            case 'countries':
                $data = AIOT_Zone_Manager::get_countries_data();
                break;
                
            case 'states':
                $data = AIOT_Zone_Manager::get_states_data($parent);
                break;
                
            case 'cities':
                $country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
                $data = AIOT_Zone_Manager::get_cities_data($country, $parent);
                break;
        }
        
        wp_send_json_success(array('data' => $data));
    }

    /**
     * Import zones
     */
    public function import_zones() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        if (!isset($_FILES['zones_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'ai-order-tracker')));
        }
        
        $file = $_FILES['zones_file'];
        
        // Check file type
        $file_type = wp_check_filetype_and_ext($file['name']);
        if ($file_type['ext'] !== 'json') {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload a JSON file.', 'ai-order-tracker')));
        }
        
        // Read file content
        $content = file_get_contents($file['tmp_name']);
        $zones_data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid JSON file.', 'ai-order-tracker')));
        }
        
        // Import zones
        $result = AIOT_Zone_Manager::import_zones($content);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to import zones.', 'ai-order-tracker')));
        }
        
        wp_send_json_success(array('message' => __('Zones imported successfully.', 'ai-order-tracker')));
    }

    /**
     * Export zones
     */
    public function export_zones() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        $zones_data = AIOT_Zone_Manager::export_zones();
        
        $filename = 'aiot-zones-export-' . date('Y-m-d') . '.json';
        
        wp_send_json_success(array(
            'message' => __('Zones exported successfully.', 'ai-order-tracker'),
            'data' => array(
                'filename' => $filename,
                'zones' => $zones_data,
            )
        ));
    }

    /**
     * Render zones page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="aiot-admin-page">
                <!-- Toolbar -->
                <div class="aiot-toolbar">
                    <div class="aiot-toolbar-left">
                        <button type="button" class="button button-primary" id="aiot-add-zone">
                            <?php _e('Add New Zone', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-refresh-zones">
                            <?php _e('Refresh', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-import-zones">
                            <?php _e('Import Zones', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button" id="aiot-export-zones">
                            <?php _e('Export Zones', 'ai-order-tracker'); ?>
                        </button>
                    </div>
                    <div class="aiot-toolbar-right">
                        <label>
                            <input type="checkbox" id="aiot-active-only" checked>
                            <?php _e('Active Only', 'ai-order-tracker'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Zones Grid -->
                <div class="aiot-zones-grid" id="aiot-zones-container">
                    <!-- Zones will be loaded via JavaScript -->
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
                <div class="aiot-modal-body">
                    <form id="aiot-zone-form">
                        <div class="aiot-form-group">
                            <label for="aiot-zone-name"><?php _e('Zone Name', 'ai-order-tracker'); ?> *</label>
                            <input type="text" id="aiot-zone-name" name="name" required>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-description"><?php _e('Description', 'ai-order-tracker'); ?></label>
                            <textarea id="aiot-zone-description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-type"><?php _e('Zone Type', 'ai-order-tracker'); ?></label>
                            <select id="aiot-zone-type" name="type">
                                <option value="country"><?php _e('Country', 'ai-order-tracker'); ?></option>
                                <option value="state"><?php _e('State', 'ai-order-tracker'); ?></option>
                                <option value="city"><?php _e('City', 'ai-order-tracker'); ?></option>
                                <option value="postal"><?php _e('Postal Code', 'ai-order-tracker'); ?></option>
                            </select>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-countries"><?php _e('Countries', 'ai-order-tracker'); ?></label>
                            <div class="aiot-tag-input-container">
                                <input type="text" id="aiot-zone-countries-input" placeholder="<?php esc_attr_e('Add countries...', 'ai-order-tracker'); ?>">
                                <div id="aiot-zone-countries-tags" class="aiot-tag-list"></div>
                                <input type="hidden" id="aiot-zone-countries" name="countries" value="">
                            </div>
                            <p class="description"><?php _e('Add countries that belong to this zone', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-states"><?php _e('States', 'ai-order-tracker'); ?></label>
                            <div class="aiot-tag-input-container">
                                <input type="text" id="aiot-zone-states-input" placeholder="<?php esc_attr_e('Add states...', 'ai-order-tracker'); ?>">
                                <div id="aiot-zone-states-tags" class="aiot-tag-list"></div>
                                <input type="hidden" id="aiot-zone-states" name="states" value="">
                            </div>
                            <p class="description"><?php _e('Add states that belong to this zone', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-cities"><?php _e('Cities', 'ai-order-tracker'); ?></label>
                            <div class="aiot-tag-input-container">
                                <input type="text" id="aiot-zone-cities-input" placeholder="<?php esc_attr_e('Add cities...', 'ai-order-tracker'); ?>">
                                <div id="aiot-zone-cities-tags" class="aiot-tag-list"></div>
                                <input type="hidden" id="aiot-zone-cities" name="cities" value="">
                            </div>
                            <p class="description"><?php _e('Add cities that belong to this zone', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-postal-codes"><?php _e('Postal Codes', 'ai-order-tracker'); ?></label>
                            <div class="aiot-tag-input-container">
                                <input type="text" id="aiot-zone-postal-codes-input" placeholder="<?php esc_attr_e('Add postal codes...', 'ai-order-tracker'); ?>">
                                <div id="aiot-zone-postal-codes-tags" class="aiot-tag-list"></div>
                                <input type="hidden" id="aiot-zone-postal-codes" name="postal_codes" value="">
                            </div>
                            <p class="description"><?php _e('Add postal codes that belong to this zone', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-group">
                                <label for="aiot-zone-delivery-days"><?php _e('Delivery Days', 'ai-order-tracker'); ?> *</label>
                                <input type="number" id="aiot-zone-delivery-days" name="delivery_days" min="1" max="30" required>
                                <p class="description"><?php _e('Number of days for delivery to this zone', 'ai-order-tracker'); ?></p>
                            </div>
                            
                            <div class="aiot-form-group">
                                <label for="aiot-zone-processing-days"><?php _e('Processing Days', 'ai-order-tracker'); ?></label>
                                <input type="number" id="aiot-zone-processing-days" name="processing_days" min="0" max="10" value="1">
                                <p class="description"><?php _e('Number of days for processing orders in this zone', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-group">
                                <label for="aiot-zone-delivery-cost"><?php _e('Delivery Cost', 'ai-order-tracker'); ?></label>
                                <input type="number" id="aiot-zone-delivery-cost" name="delivery_cost" min="0" step="0.01">
                                <p class="description"><?php _e('Delivery cost for this zone', 'ai-order-tracker'); ?></p>
                            </div>
                            
                            <div class="aiot-form-group">
                                <label>
                                    <input type="checkbox" id="aiot-zone-active" name="is_active" value="1" checked>
                                    <?php _e('Active', 'ai-order-tracker'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label><?php _e('Zone Map', 'ai-order-tracker'); ?></label>
                            <div class="aiot-zone-map-container">
                                <div class="aiot-map-header">
                                    <span class="aiot-map-title">🗺️</span>
                                    <span class="aiot-map-loading"><?php _e('Loading interactive map...', 'ai-order-tracker'); ?></span>
                                </div>
                                <div id="aiot-zone-map" class="aiot-zone-map" style="height: 300px; width: 100%;"></div>
                                <p class="description"><?php _e('Click on the map to define your zone boundaries. The coordinates will be automatically saved.', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label><?php _e('Coverage Areas', 'ai-order-tracker'); ?></label>
                            <div class="aiot-coverage-areas">
                                <div class="aiot-coverage-section">
                                    <h4><?php _e('Countries', 'ai-order-tracker'); ?></h4>
                                    <div class="aiot-tag-input-container">
                                        <input type="text" id="aiot-zone-countries-input" placeholder="<?php esc_attr_e('Add countries...', 'ai-order-tracker'); ?>">
                                        <div id="aiot-zone-countries-tags" class="aiot-tag-list"></div>
                                        <input type="hidden" id="aiot-zone-countries" name="countries" value="">
                                    </div>
                                </div>
                                
                                <div class="aiot-coverage-section">
                                    <h4><?php _e('States/Provinces', 'ai-order-tracker'); ?></h4>
                                    <div class="aiot-tag-input-container">
                                        <input type="text" id="aiot-zone-states-input" placeholder="<?php esc_attr_e('Add states...', 'ai-order-tracker'); ?>">
                                        <div id="aiot-zone-states-tags" class="aiot-tag-list"></div>
                                        <input type="hidden" id="aiot-zone-states" name="states" value="">
                                    </div>
                                </div>
                                
                                <div class="aiot-coverage-section">
                                    <h4><?php _e('Cities', 'ai-order-tracker'); ?></h4>
                                    <div class="aiot-tag-input-container">
                                        <input type="text" id="aiot-zone-cities-input" placeholder="<?php esc_attr_e('Add cities...', 'ai-order-tracker'); ?>">
                                        <div id="aiot-zone-cities-tags" class="aiot-tag-list"></div>
                                        <input type="hidden" id="aiot-zone-cities" name="cities" value="">
                                    </div>
                                </div>
                            </div>
                            <p class="description"><?php _e('Select geographic areas that belong to this zone', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-coordinates"><?php _e('Coordinates', 'ai-order-tracker'); ?></label>
                            <textarea id="aiot-zone-coordinates" name="coordinates" rows="3" placeholder="<?php esc_attr_e('Enter JSON format coordinates', 'ai-order-tracker'); ?>"></textarea>
                            <p class="description"><?php _e('Zone coordinates in JSON format (auto-generated from map)', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-group">
                            <label for="aiot-zone-meta"><?php _e('Meta Data', 'ai-order-tracker'); ?></label>
                            <textarea id="aiot-zone-meta" name="meta" rows="3" placeholder="<?php esc_attr_e('Enter JSON format meta data', 'ai-order-tracker'); ?>"></textarea>
                            <p class="description"><?php _e('Additional meta data in JSON format', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <input type="hidden" id="aiot-zone-id" name="id" value="0">
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Save Zone', 'ai-order-tracker'); ?>
                            </button>
                            <button type="button" class="button aiot-modal-cancel">
                                <?php _e('Cancel', 'ai-order-tracker'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Import Modal -->
        <div id="aiot-import-zones-modal" class="aiot-modal">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2><?php _e('Import Zones', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                <div class="aiot-modal-body">
                    <form id="aiot-import-zones-form" enctype="multipart/form-data">
                        <div class="aiot-form-group">
                            <label for="aiot-zones-file"><?php _e('Zones File', 'ai-order-tracker'); ?></label>
                            <input type="file" id="aiot-zones-file" name="zones_file" accept=".json" required>
                            <p class="description"><?php _e('Select a JSON zones file to import', 'ai-order-tracker'); ?></p>
                        </div>
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Import Zones', 'ai-order-tracker'); ?>
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
            // Initialize zones management
            aiotAdmin.zoneManager.init();
        });
        </script>
        <?php
    }
}