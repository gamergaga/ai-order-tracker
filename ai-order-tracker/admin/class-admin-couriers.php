<?php
/**
 * Simplified Admin Couriers class for AI Order Tracker
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
        // add_action('admin_menu', array($this, 'add_admin_menu')); // Commented out - menu added centrally
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_aiot_save_courier', array($this, 'ajax_save_courier'));
        add_action('wp_ajax_aiot_delete_courier', array($this, 'ajax_delete_courier'));
        add_action('wp_ajax_aiot_toggle_courier', array($this, 'ajax_toggle_courier'));
        add_action('wp_ajax_aiot_bulk_action_couriers', array($this, 'ajax_bulk_action_couriers'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ai-order-tracker',
            __('Couriers', 'ai-order-tracker'),
            __('Couriers', 'ai-order-tracker'),
            'manage_options',
            'ai-order-tracker-couriers',
            array($this, 'render_couriers_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'ai-order-tracker_page_aiot-couriers') {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style('aiot-admin-couriers', AIOT_URL . 'admin/css/admin-couriers.css', array(), AIOT_VERSION);
        
        // Enqueue admin scripts
        wp_enqueue_script('aiot-admin-couriers', AIOT_URL . 'admin/js/admin-couriers.js', array('jquery'), AIOT_VERSION, true);
        
        // Localize script
        wp_localize_script('aiot-admin-couriers', 'aiot_couriers', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiot_admin_nonce'),
            'confirm_delete' => __('Are you sure you want to delete this courier?', 'ai-order-tracker'),
            'confirm_bulk' => __('Are you sure you want to perform this bulk action?', 'ai-order-tracker'),
        ));
    }
    
    /**
     * Render couriers page
     */
    public function render_couriers_page() {
        // Get courier statistics
        $importer = new AIOT_Courier_Importer();
        $stats = $importer->get_import_stats();
        
        // Get search and filter parameters
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : 'all';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        
        // Get couriers with filtering
        $couriers = $this->get_couriers($search, $status, $country, $paged, $per_page);
        $total_couriers = $this->get_couriers_count($search, $status, $country);
        $total_pages = ceil($total_couriers / $per_page);
        
        // Get unique countries for filter
        $countries = $this->get_unique_countries();
        
        ?>
        <div class="wrap aiot-admin-couriers">
            <h1><?php echo esc_html__('Couriers Management', 'ai-order-tracker'); ?></h1>
            
            <div class="aiot-admin-header">
                <div class="aiot-admin-stats">
                    <div class="stat-card">
                        <h3><?php echo esc_html($stats['total']); ?></h3>
                        <p><?php echo esc_html__('Total Couriers', 'ai-order-tracker'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo esc_html($stats['active']); ?></h3>
                        <p><?php echo esc_html__('Active', 'ai-order-tracker'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo esc_html($stats['with_urls']); ?></h3>
                        <p><?php echo esc_html__('With URLs', 'ai-order-tracker'); ?></p>
                    </div>
                </div>
                
                <div class="aiot-admin-actions">
                    <button type="button" class="button button-primary" id="aiot-add-courier-btn">
                        <?php echo esc_html__('Add Courier', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="button" id="aiot-import-couriers-btn">
                        <?php echo esc_html__('Import CSV', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="button" id="aiot-export-couriers-btn">
                        <?php echo esc_html__('Export CSV', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="button" id="aiot-sample-csv-btn">
                        <?php echo esc_html__('Download Sample', 'ai-order-tracker'); ?>
                    </button>
                </div>
            </div>
            
            <div class="aiot-admin-content">
                <!-- Search and Filter -->
                <div class="aiot-search-filter">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="aiot-couriers">
                        
                        <div class="search-filter-row">
                            <div class="search-group">
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo esc_attr($search); ?>"
                                       placeholder="<?php echo esc_html__('Search couriers...', 'ai-order-tracker'); ?>"
                                       class="regular-text">
                            </div>
                            
                            <div class="filter-group">
                                <select name="status">
                                    <option value="all" <?php selected($status, 'all'); ?>>
                                        <?php echo esc_html__('All Status', 'ai-order-tracker'); ?>
                                    </option>
                                    <option value="active" <?php selected($status, 'active'); ?>>
                                        <?php echo esc_html__('Active', 'ai-order-tracker'); ?>
                                    </option>
                                    <option value="inactive" <?php selected($status, 'inactive'); ?>>
                                        <?php echo esc_html__('Inactive', 'ai-order-tracker'); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="country">
                                    <option value="all" <?php selected($country, 'all'); ?>>
                                        <?php echo esc_html__('All Countries', 'ai-order-tracker'); ?>
                                    </option>
                                    <?php foreach ($countries as $country_code => $country_name): ?>
                                        <option value="<?php echo esc_attr($country_code); ?>" <?php selected($country, $country_code); ?>>
                                            <?php echo esc_html($country_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="button">
                                    <?php echo esc_html__('Filter', 'ai-order-tracker'); ?>
                                </button>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=aiot-couriers')); ?>" class="button">
                                    <?php echo esc_html__('Reset', 'ai-order-tracker'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Bulk Actions -->
                <div class="aiot-bulk-actions">
                    <form method="post" id="aiot-bulk-form">
                        <select name="bulk_action" id="aiot-bulk-action">
                            <option value=""><?php echo esc_html__('Bulk Actions', 'ai-order-tracker'); ?></option>
                            <option value="activate"><?php echo esc_html__('Activate', 'ai-order-tracker'); ?></option>
                            <option value="deactivate"><?php echo esc_html__('Deactivate', 'ai-order-tracker'); ?></option>
                            <option value="delete"><?php echo esc_html__('Delete', 'ai-order-tracker'); ?></option>
                        </select>
                        <button type="button" class="button" id="aiot-apply-bulk">
                            <?php echo esc_html__('Apply', 'ai-order-tracker'); ?>
                        </button>
                    </form>
                </div>
                
                <!-- Couriers Table -->
                <div class="aiot-couriers-table-container">
                    <table class="wp-list-table widefat fixed striped aiot-couriers-table">
                        <thead>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" id="aiot-select-all">
                                </th>
                                <th><?php echo esc_html__('Courier', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Country', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('URL Pattern', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Status', 'ai-order-tracker'); ?></th>
                                <th><?php echo esc_html__('Actions', 'ai-order-tracker'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($couriers)): ?>
                                <tr>
                                    <td colspan="6" class="aiot-no-couriers">
                                        <?php echo esc_html__('No couriers found.', 'ai-order-tracker'); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($couriers as $courier): ?>
                                    <tr data-courier-id="<?php echo esc_attr($courier['id']); ?>">
                                        <td>
                                            <input type="checkbox" class="aiot-courier-checkbox" value="<?php echo esc_attr($courier['id']); ?>">
                                        </td>
                                        <td>
                                            <div class="courier-info">
                                                <strong><?php echo esc_html($courier['name']); ?></strong>
                                                <div class="courier-slug"><?php echo esc_html($courier['slug']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $settings = json_decode($courier['settings'], true);
                                            $country = isset($settings['country']) ? $settings['country'] : '';
                                            echo esc_html($country);
                                            ?>
                                        </td>
                                        <td>
                                            <div class="url-pattern">
                                                <?php if (!empty($courier['url_pattern'])): ?>
                                                    <code><?php echo esc_html($courier['url_pattern']); ?></code>
                                                <?php else: ?>
                                                    <span class="no-url"><?php echo esc_html__('No URL', 'ai-order-tracker'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $courier['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $courier['is_active'] ? esc_html__('Active', 'ai-order-tracker') : esc_html__('Inactive', 'ai-order-tracker'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <button type="button" class="button-link aiot-edit-courier" data-courier-id="<?php echo esc_attr($courier['id']); ?>">
                                                    <?php echo esc_html__('Edit', 'ai-order-tracker'); ?>
                                                </button>
                                                <span class="sep">|</span>
                                                <button type="button" class="button-link aiot-toggle-courier" data-courier-id="<?php echo esc_attr($courier['id']); ?>">
                                                    <?php echo $courier['is_active'] ? esc_html__('Deactivate', 'ai-order-tracker') : esc_html__('Activate', 'ai-order-tracker'); ?>
                                                </button>
                                                <span class="sep">|</span>
                                                <button type="button" class="button-link aiot-delete-courier" data-courier-id="<?php echo esc_attr($courier['id']); ?>">
                                                    <?php echo esc_html__('Delete', 'ai-order-tracker'); ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="aiot-pagination">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $paged,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Courier Modal -->
        <div id="aiot-courier-modal" class="aiot-modal" style="display: none;">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2><?php echo esc_html__('Add/Edit Courier', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                <div class="aiot-modal-body">
                    <form id="aiot-courier-form">
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="courier-name"><?php echo esc_html__('Courier Name', 'ai-order-tracker'); ?> *</label>
                                <input type="text" id="courier-name" name="name" required>
                            </div>
                            <div class="aiot-form-col">
                                <label for="courier-slug"><?php echo esc_html__('Slug', 'ai-order-tracker'); ?> *</label>
                                <input type="text" id="courier-slug" name="slug" required>
                                <p class="description"><?php echo esc_html__('Unique identifier (e.g., fedex, ups)', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="courier-phone"><?php echo esc_html__('Phone', 'ai-order-tracker'); ?></label>
                                <input type="text" id="courier-phone" name="phone">
                            </div>
                            <div class="aiot-form-col">
                                <label for="courier-website"><?php echo esc_html__('Website', 'ai-order-tracker'); ?></label>
                                <input type="url" id="courier-website" name="website">
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="courier-country"><?php echo esc_html__('Country', 'ai-order-tracker'); ?></label>
                                <input type="text" id="courier-country" name="country">
                                <p class="description"><?php echo esc_html__('2-letter country code (e.g., US, CA, GB)', 'ai-order-tracker'); ?></p>
                            </div>
                            <div class="aiot-form-col">
                                <label for="courier-type"><?php echo esc_html__('Type', 'ai-order-tracker'); ?></label>
                                <select id="courier-type" name="type">
                                    <option value="express"><?php echo esc_html__('Express', 'ai-order-tracker'); ?></option>
                                    <option value="globalpost"><?php echo esc_html__('Global Post', 'ai-order-tracker'); ?></option>
                                    <option value="regional"><?php echo esc_html__('Regional', 'ai-order-tracker'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="courier-url-pattern"><?php echo esc_html__('URL Pattern', 'ai-order-tracker'); ?></label>
                                <input type="text" id="courier-url-pattern" name="url_pattern">
                                <p class="description"><?php echo esc_html__('Use {tracking_id} as placeholder (e.g., https://example.com/track?id={tracking_id})', 'ai-order-tracker'); ?></p>
                            </div>
                            <div class="aiot-form-col">
                                <label for="courier-display-name"><?php echo esc_html__('Display Name', 'ai-order-tracker'); ?></label>
                                <input type="text" id="courier-display-name" name="display_name">
                                <p class="description"><?php echo esc_html__('Name to show to customers (optional)', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label>
                                    <input type="checkbox" id="courier-active" name="is_active" value="1" checked>
                                    <?php echo esc_html__('Active', 'ai-order-tracker'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" id="courier-id" name="courier_id" value="">
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Save Courier', 'ai-order-tracker'); ?>
                            </button>
                            <button type="button" class="button aiot-modal-close">
                                <?php echo esc_html__('Cancel', 'ai-order-tracker'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Import Modal -->
        <div id="aiot-import-modal" class="aiot-modal" style="display: none;">
            <div class="aiot-modal-content">
                <div class="aiot-modal-header">
                    <h2><?php echo esc_html__('Import Couriers', 'ai-order-tracker'); ?></h2>
                    <button type="button" class="aiot-modal-close">&times;</button>
                </div>
                <div class="aiot-modal-body">
                    <form id="aiot-import-form" enctype="multipart/form-data">
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label for="courier-file"><?php echo esc_html__('CSV File', 'ai-order-tracker'); ?> *</label>
                                <input type="file" id="courier-file" name="courier_file" accept=".csv" required>
                                <p class="description"><?php echo esc_html__('Select a CSV file with courier data', 'ai-order-tracker'); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiot-form-row">
                            <div class="aiot-form-col">
                                <label>
                                    <input type="checkbox" id="overwrite-existing" name="overwrite" value="1">
                                    <?php echo esc_html__('Overwrite existing couriers', 'ai-order-tracker'); ?>
                                </label>
                            </div>
                            <div class="aiot-form-col">
                                <label>
                                    <input type="checkbox" id="skip-inactive" name="skip_inactive" value="1">
                                    <?php echo esc_html__('Skip inactive couriers', 'ai-order-tracker'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="aiot-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Import Couriers', 'ai-order-tracker'); ?>
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
     * Get couriers with filtering
     */
    private function get_couriers($search = '', $status = 'all', $country = 'all', $paged = 1, $per_page = 20) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $where = array();
        $prepare = array();
        
        // Search filter
        if (!empty($search)) {
            $where[] = '(name LIKE %s OR slug LIKE %s OR description LIKE %s)';
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $prepare[] = $search_like;
            $prepare[] = $search_like;
            $prepare[] = $search_like;
        }
        
        // Status filter
        if ($status === 'active') {
            $where[] = 'is_active = %d';
            $prepare[] = 1;
        } elseif ($status === 'inactive') {
            $where[] = 'is_active = %d';
            $prepare[] = 0;
        }
        
        // Country filter
        if ($country !== 'all') {
            $where[] = 'settings LIKE %s';
            $prepare[] = '%' . $wpdb->esc_like($country) . '%';
        }
        
        // Build query
        $sql = "SELECT * FROM $couriers_table";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY name ASC';
        
        // Add pagination
        $offset = ($paged - 1) * $per_page;
        $sql .= ' LIMIT %d OFFSET %d';
        $prepare[] = $per_page;
        $prepare[] = $offset;
        
        if (!empty($prepare)) {
            $couriers = $wpdb->get_results($wpdb->prepare($sql, $prepare), ARRAY_A);
        } else {
            $couriers = $wpdb->get_results($sql, ARRAY_A);
        }
        
        return $couriers;
    }
    
    /**
     * Get couriers count with filtering
     */
    private function get_couriers_count($search = '', $status = 'all', $country = 'all') {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $where = array();
        $prepare = array();
        
        // Search filter
        if (!empty($search)) {
            $where[] = '(name LIKE %s OR slug LIKE %s OR description LIKE %s)';
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $prepare[] = $search_like;
            $prepare[] = $search_like;
            $prepare[] = $search_like;
        }
        
        // Status filter
        if ($status === 'active') {
            $where[] = 'is_active = %d';
            $prepare[] = 1;
        } elseif ($status === 'inactive') {
            $where[] = 'is_active = %d';
            $prepare[] = 0;
        }
        
        // Country filter
        if ($country !== 'all') {
            $where[] = 'settings LIKE %s';
            $prepare[] = '%' . $wpdb->esc_like($country) . '%';
        }
        
        // Build query
        $sql = "SELECT COUNT(*) FROM $couriers_table";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        if (!empty($prepare)) {
            return $wpdb->get_var($wpdb->prepare($sql, $prepare));
        } else {
            return $wpdb->get_var($sql);
        }
    }
    
    /**
     * Get unique countries
     */
    private function get_unique_countries() {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $countries = array();
        $results = $wpdb->get_results("SELECT settings FROM $couriers_table WHERE settings != ''", ARRAY_A);
        
        foreach ($results as $result) {
            $settings = json_decode($result['settings'], true);
            if (isset($settings['country']) && !empty($settings['country'])) {
                $countries[$settings['country']] = $this->get_country_name($settings['country']);
            }
        }
        
        asort($countries);
        return $countries;
    }
    
    /**
     * Get country name from code
     */
    private function get_country_name($code) {
        $countries = array(
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'AU' => 'Australia',
            'JP' => 'Japan',
            'CN' => 'China',
            'IN' => 'India',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'RU' => 'Russia',
            'ZA' => 'South Africa',
            'EG' => 'Egypt',
            'AE' => 'UAE',
            'SA' => 'Saudi Arabia',
        );
        
        return isset($countries[$code]) ? $countries[$code] : $code;
    }
    
    /**
     * AJAX save courier
     */
    public function ajax_save_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get and sanitize form data
        $courier_id = intval($_POST['courier_id']);
        $courier_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_text_field($_POST['slug']),
            'phone' => sanitize_text_field($_POST['phone']),
            'website' => esc_url_raw($_POST['website']),
            'country' => sanitize_text_field($_POST['country']),
            'type' => sanitize_text_field($_POST['type']),
            'url_pattern' => sanitize_text_field($_POST['url_pattern']),
            'display_name' => sanitize_text_field($_POST['display_name']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        );
        
        // Save courier
        if ($courier_id > 0) {
            // Update existing courier
            $result = $this->update_courier($courier_id, $courier_data);
        } else {
            // Create new courier
            $result = $this->create_courier($courier_data);
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Courier saved successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save courier.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX delete courier
     */
    public function ajax_delete_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get courier ID
        $courier_id = intval($_POST['courier_id']);
        
        if ($courier_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid courier ID.', 'ai-order-tracker')));
        }
        
        // Delete courier
        $result = $this->delete_courier($courier_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Courier deleted successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete courier.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX toggle courier
     */
    public function ajax_toggle_courier() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get courier ID
        $courier_id = intval($_POST['courier_id']);
        
        if ($courier_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid courier ID.', 'ai-order-tracker')));
        }
        
        // Toggle courier status
        $result = $this->toggle_courier_status($courier_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Courier status updated successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update courier status.', 'ai-order-tracker')));
        }
    }
    
    /**
     * AJAX bulk action
     */
    public function ajax_bulk_action_couriers() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Get bulk action and courier IDs
        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $courier_ids = array_map('intval', $_POST['courier_ids']);
        
        if (empty($bulk_action) || empty($courier_ids)) {
            wp_send_json_error(array('message' => __('Invalid bulk action.', 'ai-order-tracker')));
        }
        
        // Perform bulk action
        $result = $this->perform_bulk_action($bulk_action, $courier_ids);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Bulk action completed successfully.', 'ai-order-tracker'),
                'reload' => true
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to complete bulk action.', 'ai-order-tracker')));
        }
    }
    
    /**
     * Create courier
     */
    private function create_courier($data) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Prepare settings
        $settings = array(
            'phone' => $data['phone'],
            'website' => $data['website'],
            'country' => $data['country'],
            'type' => $data['type'],
            'display_name' => $data['display_name'],
        );
        
        $result = $wpdb->insert(
            $couriers_table,
            array(
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => sprintf(__('Courier service for %s', 'ai-order-tracker'), $data['name']),
                'url_pattern' => $data['url_pattern'],
                'tracking_format' => 'standard',
                'is_active' => $data['is_active'],
                'settings' => wp_json_encode($settings),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update courier
     */
    private function update_courier($courier_id, $data) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Get existing settings
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT settings FROM $couriers_table WHERE id = %d",
            $courier_id
        ), ARRAY_A);
        
        $settings = $existing ? json_decode($existing['settings'], true) : array();
        
        // Update settings
        $settings['phone'] = $data['phone'];
        $settings['website'] = $data['website'];
        $settings['country'] = $data['country'];
        $settings['type'] = $data['type'];
        $settings['display_name'] = $data['display_name'];
        
        $result = $wpdb->update(
            $couriers_table,
            array(
                'name' => $data['name'],
                'url_pattern' => $data['url_pattern'],
                'is_active' => $data['is_active'],
                'settings' => wp_json_encode($settings),
            ),
            array('id' => $courier_id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete courier
     */
    private function delete_courier($courier_id) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $result = $wpdb->delete(
            $couriers_table,
            array('id' => $courier_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Toggle courier status
     */
    private function toggle_courier_status($courier_id) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Get current status
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $couriers_table WHERE id = %d",
            $courier_id
        ));
        
        // Toggle status
        $new_status = $current ? 0 : 1;
        
        $result = $wpdb->update(
            $couriers_table,
            array('is_active' => $new_status),
            array('id' => $courier_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Perform bulk action
     */
    private function perform_bulk_action($action, $courier_ids) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $placeholders = implode(',', array_fill(0, count($courier_ids), '%d'));
        
        switch ($action) {
            case 'activate':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $couriers_table SET is_active = 1 WHERE id IN ($placeholders)",
                    $courier_ids
                ));
                break;
                
            case 'deactivate':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $couriers_table SET is_active = 0 WHERE id IN ($placeholders)",
                    $courier_ids
                ));
                break;
                
            case 'delete':
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $couriers_table WHERE id IN ($placeholders)",
                    $courier_ids
                ));
                break;
                
            default:
                return false;
        }
        
        return $result !== false;
    }
    
    /**
     * Render page - Alias for render_couriers_page()
     */
    public function render_page() {
        return $this->render_couriers_page();
    }
}