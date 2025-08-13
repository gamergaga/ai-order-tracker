<?php
/**
 * Simplified Courier Importer class for AI Order Tracker
 *
 * Handles simplified CSV import of couriers with automatic validation
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

class AIOT_Courier_Importer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_aiot_import_couriers', array($this, 'ajax_import_couriers'));
        add_action('wp_ajax_aiot_export_couriers', array($this, 'ajax_export_couriers'));
        add_action('wp_ajax_aiot_clear_couriers', array($this, 'ajax_clear_couriers'));
    }
    
    /**
     * AJAX handler for importing couriers
     */
    public function ajax_import_couriers() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['courier_file']) || empty($_FILES['courier_file']['tmp_name'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'ai-order-tracker')));
        }
        
        // Get import options
        $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === '1';
        $skip_inactive = isset($_POST['skip_inactive']) && $_POST['skip_inactive'] === '1';
        
        // Process the import
        $result = $this->import_from_file($_FILES['courier_file']['tmp_name'], $overwrite, $skip_inactive);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for exporting couriers
     */
    public function ajax_export_couriers() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Export couriers
        $result = $this->export_couriers();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for clearing couriers
     */
    public function ajax_clear_couriers() {
        // Verify nonce
        if (!check_ajax_referer('aiot_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ai-order-tracker')));
        }
        
        // Clear couriers
        $result = $this->clear_couriers();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Import couriers from CSV file
     *
     * @param string $file_path Path to CSV file
     * @param bool $overwrite Whether to overwrite existing couriers
     * @param bool $skip_inactive Whether to skip inactive couriers
     * @return array|WP_Error Import result
     */
    public function import_from_file($file_path, $overwrite = false, $skip_inactive = false) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('File not found.', 'ai-order-tracker'));
        }
        
        // Open CSV file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_open_failed', __('Failed to open file.', 'ai-order-tracker'));
        }
        
        // Read header
        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return new WP_Error('invalid_csv', __('Invalid CSV format.', 'ai-order-tracker'));
        }
        
        // Normalize header
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);
        
        // Required columns
        $required_columns = array('name', 'slug', 'urlpattern');
        foreach ($required_columns as $column) {
            if (!in_array($column, $header)) {
                fclose($handle);
                return new WP_Error('missing_column', sprintf(__('Missing required column: %s', 'ai-order-tracker'), $column));
            }
        }
        
        // Get column indices
        $indices = array(
            'name' => array_search('name', $header),
            'slug' => array_search('slug', $header),
            'phone' => array_search('phone', $header),
            'website' => array_search('website', $header),
            'type' => array_search('type', $header),
            'image' => array_search('image', $header),
            'country' => array_search('country', $header),
            'urlpattern' => array_search('urlpattern', $header),
            'displayname' => array_search('displayname', $header),
        );
        
        // Initialize counters
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        // Process rows
        $row_number = 1; // Start from 1 (header is row 0)
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty($row) || empty($row[$indices['name']])) {
                continue;
            }
            
            // Extract data
            $courier_data = array(
                'name' => sanitize_text_field($row[$indices['name']]),
                'slug' => sanitize_text_field($row[$indices['slug']]),
                'phone' => isset($row[$indices['phone']]) ? sanitize_text_field($row[$indices['phone']]) : '',
                'website' => isset($row[$indices['website']]) ? esc_url_raw($row[$indices['website']]) : '',
                'type' => isset($row[$indices['type']]) ? sanitize_text_field($row[$indices['type']]) : 'express',
                'image' => isset($row[$indices['image']]) ? esc_url_raw($row[$indices['image']]) : '',
                'country' => isset($row[$indices['country']]) ? sanitize_text_field($row[$indices['country']]) : '',
                'url_pattern' => sanitize_text_field($row[$indices['urlpattern']]),
                'display_name' => isset($row[$indices['displayname']]) ? sanitize_text_field($row[$indices['displayname']]) : '',
            );
            
            // Validate required fields
            if (empty($courier_data['name']) || empty($courier_data['slug']) || empty($courier_data['url_pattern'])) {
                $errors[] = sprintf(__('Row %d: Missing required fields', 'ai-order-tracker'), $row_number);
                $skipped++;
                continue;
            }
            
            // Validate slug
            if (!preg_match('/^[a-z0-9-]+$/', $courier_data['slug'])) {
                $errors[] = sprintf(__('Row %d: Invalid slug format for %s', 'ai-order-tracker'), $row_number, $courier_data['name']);
                $skipped++;
                continue;
            }
            
            // Check if courier exists
            $existing_courier = $this->get_courier_by_slug($courier_data['slug']);
            
            if ($existing_courier) {
                if (!$overwrite) {
                    $skipped++;
                    continue;
                }
                
                // Update existing courier
                $result = $this->update_courier($existing_courier['id'], $courier_data);
                if ($result) {
                    $updated++;
                } else {
                    $errors[] = sprintf(__('Row %d: Failed to update %s', 'ai-order-tracker'), $row_number, $courier_data['name']);
                    $skipped++;
                }
            } else {
                // Create new courier
                $result = $this->create_courier($courier_data);
                if ($result) {
                    $imported++;
                } else {
                    $errors[] = sprintf(__('Row %d: Failed to create %s', 'ai-order-tracker'), $row_number, $courier_data['name']);
                    $skipped++;
                }
            }
        }
        
        fclose($handle);
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => $imported + $updated + $skipped,
        );
    }
    
    /**
     * Export couriers to CSV
     *
     * @return array|WP_Error Export result
     */
    public function export_couriers() {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Get all couriers
        $couriers = $wpdb->get_results("SELECT * FROM $couriers_table ORDER BY name", ARRAY_A);
        
        if (empty($couriers)) {
            return new WP_Error('no_couriers', __('No couriers to export.', 'ai-order-tracker'));
        }
        
        // Create CSV content
        $csv_content = "Name,Slug,Phone,Website,Type,Image,Country,URLPattern,DisplayName\n";
        
        foreach ($couriers as $courier) {
            $settings = json_decode($courier['settings'], true);
            
            $row = array(
                $courier['name'],
                $courier['slug'],
                isset($settings['phone']) ? $settings['phone'] : '',
                isset($settings['website']) ? $settings['website'] : '',
                isset($settings['type']) ? $settings['type'] : 'express',
                isset($settings['image']) ? $settings['image'] : '',
                isset($settings['country']) ? $settings['country'] : '',
                $courier['url_pattern'],
                isset($settings['display_name']) ? $settings['display_name'] : $courier['name'],
            );
            
            // Escape and join
            $csv_row = implode(',', array_map(array($this, 'escape_csv_field'), $row));
            $csv_content .= $csv_row . "\n";
        }
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'aiot-couriers-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($filepath, $csv_content) === false) {
            return new WP_Error('export_failed', __('Failed to create export file.', 'ai-order-tracker'));
        }
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => $upload_dir['url'] . '/' . $filename,
            'count' => count($couriers),
        );
    }
    
    /**
     * Clear all couriers
     *
     * @return array|WP_Error Clear result
     */
    public function clear_couriers() {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Get count before deletion
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table");
        
        // Delete all couriers
        $result = $wpdb->query("TRUNCATE TABLE $couriers_table");
        
        if ($result === false) {
            return new WP_Error('clear_failed', __('Failed to clear couriers.', 'ai-order-tracker'));
        }
        
        return array(
            'cleared' => $count,
        );
    }
    
    /**
     * Get courier by slug
     *
     * @param string $slug Courier slug
     * @return array|false Courier data or false if not found
     */
    private function get_courier_by_slug($slug) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $couriers_table WHERE slug = %s",
            $slug
        ), ARRAY_A);
    }
    
    /**
     * Create new courier
     *
     * @param array $data Courier data
     * @return bool True on success
     */
    private function create_courier($data) {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        // Prepare settings
        $settings = array(
            'phone' => $data['phone'],
            'website' => $data['website'],
            'type' => $data['type'],
            'image' => $data['image'],
            'country' => $data['country'],
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
                'is_active' => 1,
                'settings' => wp_json_encode($settings),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update existing courier
     *
     * @param int $courier_id Courier ID
     * @param array $data Courier data
     * @return bool True on success
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
        $settings['type'] = $data['type'];
        $settings['image'] = $data['image'];
        $settings['country'] = $data['country'];
        $settings['display_name'] = $data['display_name'];
        
        $result = $wpdb->update(
            $couriers_table,
            array(
                'name' => $data['name'],
                'url_pattern' => $data['url_pattern'],
                'settings' => wp_json_encode($settings),
            ),
            array('id' => $courier_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Escape CSV field
     *
     * @param string $field Field value
     * @return string Escaped field
     */
    private function escape_csv_field($field) {
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
    
    /**
     * Get import statistics
     *
     * @return array Import statistics
     */
    public function get_import_stats() {
        global $wpdb;
        $couriers_table = $wpdb->prefix . 'aiot_couriers';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table");
        $active = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table WHERE is_active = 1");
        $with_urls = $wpdb->get_var("SELECT COUNT(*) FROM $couriers_table WHERE url_pattern != ''");
        
        return array(
            'total' => intval($total),
            'active' => intval($active),
            'with_urls' => intval($with_urls),
        );
    }
    
    /**
     * Get sample CSV data
     *
     * @return string Sample CSV content
     */
    public function get_sample_csv() {
        return "Name,Slug,Phone,Website,Type,Image,Country,URLPattern,DisplayName\n" .
               "DHL Express,dhl-express,1-800-225-5345,https://www.dhl.com,express,https://cdn.dhl.com/content/dam/dhl/global/core/images/logos/dhl-logo.svg,DE,https://www.dhl.com/en/express/tracking.html?brand=DHL&AWB={tracking_id},DHL Express\n" .
               "FedEx Express,fedex-express,1.800.247.4747,https://www.fedex.com,express,https://www.fedex.com/etc/designs/fedex/common/images/fedex-logo.svg,US,https://www.fedex.com/fedextrack/?trknbr={tracking_id},FedEx Express\n" .
               "UPS Express,ups-express,+1 800 742 5877,https://www.ups.com,express,https://www.ups.com/assets/resources/images/ups-logo.svg,US,https://www.ups.com/track?loc=en_US&tracknum={tracking_id}&requester=WT/,UPS Express\n";
    }
}