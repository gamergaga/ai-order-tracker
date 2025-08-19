<?php
/**
 * Plugin functions
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Generate a unique tracking ID
 *
 * @param string $prefix Prefix for the tracking ID
 * @return string Unique tracking ID
 */
function aiot_generate_tracking_id($prefix = 'AIOT') {
    $random = mt_rand(10000, 99999);
    $timestamp = time();
    $hash = substr(md5($timestamp . $random), 0, 5);
    return strtoupper($prefix . '-' . $random . $hash);
}

/**
 * Get order status with human-readable label
 *
 * @param string $status Order status
 * @return array Status information
 */
function aiot_get_order_status($status) {
    $statuses = array(
        'processing' => array(
            'label' => __('Processing', 'ai-order-tracker'),
            'color' => '#ffc107',
            'icon' => '⚙️',
            'step' => 1,
        ),
        'confirmed' => array(
            'label' => __('Order Confirmed', 'ai-order-tracker'),
            'color' => '#17a2b8',
            'icon' => '✅',
            'step' => 2,
        ),
        'packed' => array(
            'label' => __('Packed', 'ai-order-tracker'),
            'color' => '#6f42c1',
            'icon' => '📦',
            'step' => 3,
        ),
        'shipped' => array(
            'label' => __('Shipped', 'ai-order-tracker'),
            'color' => '#007bff',
            'icon' => '🚚',
            'step' => 4,
        ),
        'in_transit' => array(
            'label' => __('In Transit', 'ai-order-tracker'),
            'color' => '#fd7e14',
            'icon' => '🛣️',
            'step' => 5,
        ),
        'out_for_delivery' => array(
            'label' => __('Out for Delivery', 'ai-order-tracker'),
            'color' => '#20c997',
            'icon' => '🏃',
            'step' => 6,
        ),
        'delivered' => array(
            'label' => __('Delivered', 'ai-order-tracker'),
            'color' => '#28a745',
            'icon' => '🎉',
            'step' => 7,
        ),
        'failed' => array(
            'label' => __('Delivery Failed', 'ai-order-tracker'),
            'color' => '#dc3545',
            'icon' => '❌',
            'step' => 0,
        ),
        'returned' => array(
            'label' => __('Returned', 'ai-order-tracker'),
            'color' => '#6c757d',
            'icon' => '🔄',
            'step' => 0,
        ),
    );

    return isset($statuses[$status]) ? $statuses[$status] : $statuses['processing'];
}

/**
 * Generate realistic tracking events for simulation
 *
 * @param string $tracking_id Tracking ID
 * @param string $status Current status
 * @return array Tracking events
 */
function aiot_generate_tracking_events($tracking_id, $status) {
    $events = array();
    $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
    
    $current_index = array_search($status, $statuses);
    if ($current_index === false) {
        $current_index = 0;
    }

    $locations = array(
        'Processing Center',
        'Distribution Hub',
        'Regional Facility',
        'Local Depot',
        'Delivery Station',
        'Customer Address'
    );

    $descriptions = array(
        'processing' => 'Order received and being processed',
        'confirmed' => 'Order confirmed and payment verified',
        'packed' => 'Package packed and ready for shipment',
        'shipped' => 'Package shipped from origin facility',
        'in_transit' => 'Package in transit to destination',
        'out_for_delivery' => 'Package out for final delivery',
        'delivered' => 'Package delivered successfully'
    );

    $base_time = time() - (86400 * $current_index); // Start from past

    for ($i = 0; $i <= $current_index; $i++) {
        $event_time = $base_time + (86400 * $i);
        $event_status = $statuses[$i];
        
        $events[] = array(
            'event_type' => 'status_update',
            'event_status' => $event_status,
            'location' => isset($locations[$i]) ? $locations[$i] : 'Unknown',
            'description' => isset($descriptions[$event_status]) ? $descriptions[$event_status] : 'Status updated',
            'timestamp' => date('Y-m-d H:i:s', $event_time),
            'latitude' => null,
            'longitude' => null,
        );
    }

    return $events;
}

/**
 * Calculate progress percentage based on status
 *
 * @param string $status Order status
 * @return int Progress percentage
 */
function aiot_calculate_progress($status) {
    $progress_map = array(
        'processing' => 10,
        'confirmed' => 20,
        'packed' => 35,
        'shipped' => 50,
        'in_transit' => 70,
        'out_for_delivery' => 90,
        'delivered' => 100,
        'failed' => 0,
        'returned' => 0,
    );

    return isset($progress_map[$status]) ? $progress_map[$status] : 0;
}

/**
 * Get estimated delivery date
 *
 * @param string $status Current status
 * @param int $days_from_now Days from now for delivery
 * @return string Estimated delivery date
 */
function aiot_get_estimated_delivery($status, $days_from_now = 3) {
    $statuses = array('processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered');
    $current_index = array_search($status, $statuses);
    
    if ($current_index === false) {
        $current_index = 0;
    }

    $remaining_steps = count($statuses) - $current_index - 1;
    $delivery_days = max(1, $remaining_steps);

    $delivery_date = new DateTime();
    $delivery_date->add(new DateInterval('P' . $delivery_days . 'D'));

    return $delivery_date->format('F j, Y');
}

/**
 * Sanitize and validate tracking ID
 *
 * @param string $tracking_id Tracking ID to validate
 * @return string|false Valid tracking ID or false
 */
function aiot_sanitize_tracking_id($tracking_id) {
    if (empty($tracking_id)) {
        return false;
    }

    // Remove any whitespace
    $tracking_id = trim($tracking_id);
    
    // Allow alphanumeric characters, hyphens, and underscores
    $tracking_id = preg_replace('/[^A-Za-z0-9\-_]/', '', $tracking_id);
    
    // Check minimum length
    if (strlen($tracking_id) < 5) {
        return false;
    }

    return $tracking_id;
}

/**
 * Get plugin settings
 *
 * @param string $section Settings section
 * @return array Settings
 */
function aiot_get_settings($section = 'general') {
    $option_name = 'aiot_' . $section . '_settings';
    $settings = get_option($option_name, array());
    
    return is_array($settings) ? $settings : array();
}

/**
 * Update plugin settings
 *
 * @param string $section Settings section
 * @param array $settings Settings to update
 * @return bool Success status
 */
function aiot_update_settings($section, $settings) {
    $option_name = 'aiot_' . $section . '_settings';
    return update_option($option_name, $settings);
}

/**
 * Get plugin template
 *
 * @param string $template Template name
 * @param array $args Template arguments
 * @return void
 */
function aiot_get_template($template, $args = array()) {
    $template_path = AIOT_PATH . 'public/templates/' . $template . '.php';
    
    if (file_exists($template_path)) {
        extract($args);
        include $template_path;
    }
}

/**
 * Log plugin activity
 *
 * @param string $message Log message
 * @param string $level Log level
 * @return void
 */
function aiot_log($message, $level = 'info') {
    $settings = aiot_get_settings('advanced');
    $log_level = isset($settings['log_level']) ? $settings['log_level'] : 'error';
    
    $levels = array('debug', 'info', 'warning', 'error');
    $current_level_index = array_search($level, $levels);
    $config_level_index = array_search($log_level, $levels);
    
    if ($current_level_index >= $config_level_index) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'],
        );
        
        // Store in database or file based on settings
        // For now, we'll use error log
        error_log(sprintf('[AIOT] %s: %s', strtoupper($level), $message));
    }
}

/**
 * Get client IP address
 *
 * @return string Client IP
 */
function aiot_get_client_ip() {
    $ip = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

/**
 * Check if WooCommerce is active
 *
 * @return bool
 */
function aiot_is_woocommerce_active() {
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

/**
 * Format date for display
 *
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function aiot_format_date($date, $format = 'F j, Y g:i A') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return date_i18n($format, $timestamp);
}

/**
 * Get timezone offset
 *
 * @return int Timezone offset in seconds
 */
function aiot_get_timezone_offset() {
    $timezone = get_option('timezone_string');
    if (empty($timezone)) {
        $offset = get_option('gmt_offset');
        return $offset * 3600;
    }
    
    try {
        $datetime = new DateTime('now', new DateTimeZone($timezone));
        return $datetime->getOffset();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Render orders table for admin interface
 */
function aiot_render_orders_table() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        echo '<p>' . __('No orders table found. Please reactivate the plugin.', 'ai-order-tracker') . '</p>';
        return;
    }
    
    // Get orders with pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $total_pages = ceil($total_orders / $per_page);
    
    ?>
    <div class="aiot-orders-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Tracking ID', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Order ID', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Customer', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Status', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Location', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Created', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Actions', 'ai-order-tracker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)) : ?>
                    <tr>
                        <td colspan="7"><?php _e('No orders found.', 'ai-order-tracker'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($orders as $order) : ?>
                        <?php
                        $status_info = aiot_get_order_status($order->status);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($order->tracking_id); ?></strong>
                            </td>
                            <td><?php echo esc_html($order->order_id); ?></td>
                            <td>
                                <?php if (!empty($order->customer_name)) : ?>
                                    <?php echo esc_html($order->customer_name); ?>
                                <?php elseif (!empty($order->customer_email)) : ?>
                                    <?php echo esc_html($order->customer_email); ?>
                                <?php else : ?>
                                    <?php _e('Guest', 'ai-order-tracker'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="aiot-status-badge" style="background-color: <?php echo esc_attr($status_info['color']); ?>; color: white;">
                                    <?php echo esc_html($status_info['label']); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($order->location); ?></td>
                            <td><?php echo aiot_format_date($order->created_at); ?></td>
                            <td>
                                <button class="button button-small aiot-view-order" data-tracking-id="<?php echo esc_attr($order->tracking_id); ?>">
                                    <?php _e('View', 'ai-order-tracker'); ?>
                                </button>
                                <button class="button button-small aiot-edit-order" data-tracking-id="<?php echo esc_attr($order->tracking_id); ?>">
                                    <?php _e('Edit', 'ai-order-tracker'); ?>
                                </button>
                                <button class="button button-small aiot-delete-order" data-tracking-id="<?php echo esc_attr($order->tracking_id); ?>">
                                    <?php _e('Delete', 'ai-order-tracker'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Order Modal -->
    <div id="aiot-add-order-modal" class="aiot-modal" style="display: none;">
        <div class="aiot-modal-content">
            <div class="aiot-modal-header">
                <h3><?php _e('Add New Order', 'ai-order-tracker'); ?></h3>
                <button class="aiot-modal-close">&times;</button>
            </div>
            <div class="aiot-modal-body">
                <form id="aiot-add-order-form">
                    <?php wp_nonce_field('aiot_add_order', 'aiot_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="aiot_order_id"><?php _e('Order ID', 'ai-order-tracker'); ?></label></th>
                            <td><input type="text" id="aiot_order_id" name="order_id" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_customer_name"><?php _e('Customer Name', 'ai-order-tracker'); ?></label></th>
                            <td><input type="text" id="aiot_customer_name" name="customer_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_customer_email"><?php _e('Customer Email', 'ai-order-tracker'); ?></label></th>
                            <td><input type="email" id="aiot_customer_email" name="customer_email" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_location"><?php _e('Location', 'ai-order-tracker'); ?></label></th>
                            <td><input type="text" id="aiot_location" name="location" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_status"><?php _e('Status', 'ai-order-tracker'); ?></label></th>
                            <td>
                                <select id="aiot_status" name="status" required>
                                    <option value="processing"><?php _e('Processing', 'ai-order-tracker'); ?></option>
                                    <option value="confirmed"><?php _e('Confirmed', 'ai-order-tracker'); ?></option>
                                    <option value="packed"><?php _e('Packed', 'ai-order-tracker'); ?></option>
                                    <option value="shipped"><?php _e('Shipped', 'ai-order-tracker'); ?></option>
                                    <option value="in_transit"><?php _e('In Transit', 'ai-order-tracker'); ?></option>
                                    <option value="out_for_delivery"><?php _e('Out for Delivery', 'ai-order-tracker'); ?></option>
                                    <option value="delivered"><?php _e('Delivered', 'ai-order-tracker'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aiot_carrier"><?php _e('Carrier', 'ai-order-tracker'); ?></label></th>
                            <td>
                                <select id="aiot_carrier" name="carrier">
                                    <option value="standard"><?php _e('Standard', 'ai-order-tracker'); ?></option>
                                    <?php
                                    $couriers = AIOT_Database::get_couriers(array('is_active' => true));
                                    foreach ($couriers as $courier) {
                                        echo '<option value="' . esc_attr($courier['slug']) . '">' . esc_html($courier['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aiot_estimated_delivery"><?php _e('Estimated Delivery', 'ai-order-tracker'); ?></label></th>
                            <td><input type="date" id="aiot_estimated_delivery" name="estimated_delivery" class="regular-text"></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Add Order', 'ai-order-tracker'); ?></button>
                        <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Order Modal -->
    <div id="aiot-view-order-modal" class="aiot-modal" style="display: none;">
        <div class="aiot-modal-content">
            <div class="aiot-modal-header">
                <h3><?php _e('Order Details', 'ai-order-tracker'); ?></h3>
                <button class="aiot-modal-close">&times;</button>
            </div>
            <div class="aiot-modal-body">
                <div id="aiot-order-details"></div>
            </div>
        </div>
    </div>
    
    <!-- Edit Order Modal -->
    <div id="aiot-edit-order-modal" class="aiot-modal" style="display: none;">
        <div class="aiot-modal-content">
            <div class="aiot-modal-header">
                <h3><?php _e('Edit Order', 'ai-order-tracker'); ?></h3>
                <button class="aiot-modal-close">&times;</button>
            </div>
            <div class="aiot-modal-body">
                <form id="aiot-edit-order-form">
                    <?php wp_nonce_field('aiot_edit_order', 'aiot_nonce'); ?>
                    <input type="hidden" id="aiot_edit_tracking_id" name="tracking_id">
                    <table class="form-table">
                        <tr>
                            <th><label for="aiot_edit_order_id"><?php _e('Order ID', 'ai-order-tracker'); ?></label></th>
                            <td><input type="text" id="aiot_edit_order_id" name="order_id" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_edit_customer_name"><?php _e('Customer Name', 'ai-order-tracker'); ?></label></th>
                            <td><input type="text" id="aiot_edit_customer_name" name="customer_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_edit_customer_email"><?php _e('Customer Email', 'ai-order-tracker'); ?></label></th>
                            <td><input type="email" id="aiot_edit_customer_email" name="customer_email" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_edit_location"><?php _e('Location', 'ai-order-tracker'); ?></label></th>
                            <td><input type="text" id="aiot_edit_location" name="location" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="aiot_edit_status"><?php _e('Status', 'ai-order-tracker'); ?></label></th>
                            <td>
                                <select id="aiot_edit_status" name="status" required>
                                    <option value="processing"><?php _e('Processing', 'ai-order-tracker'); ?></option>
                                    <option value="confirmed"><?php _e('Confirmed', 'ai-order-tracker'); ?></option>
                                    <option value="packed"><?php _e('Packed', 'ai-order-tracker'); ?></option>
                                    <option value="shipped"><?php _e('Shipped', 'ai-order-tracker'); ?></option>
                                    <option value="in_transit"><?php _e('In Transit', 'ai-order-tracker'); ?></option>
                                    <option value="out_for_delivery"><?php _e('Out for Delivery', 'ai-order-tracker'); ?></option>
                                    <option value="delivered"><?php _e('Delivered', 'ai-order-tracker'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aiot_edit_carrier"><?php _e('Carrier', 'ai-order-tracker'); ?></label></th>
                            <td>
                                <select id="aiot_edit_carrier" name="carrier">
                                    <option value="standard"><?php _e('Standard', 'ai-order-tracker'); ?></option>
                                    <?php
                                    $couriers = AIOT_Database::get_couriers(array('is_active' => true));
                                    foreach ($couriers as $courier) {
                                        echo '<option value="' . esc_attr($courier['slug']) . '">' . esc_html($courier['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aiot_edit_estimated_delivery"><?php _e('Estimated Delivery', 'ai-order-tracker'); ?></label></th>
                            <td><input type="date" id="aiot_edit_estimated_delivery" name="estimated_delivery" class="regular-text"></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Update Order', 'ai-order-tracker'); ?></button>
                        <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render recent orders table for dashboard
 */
function aiot_render_recent_orders_table() {
    global $wpdb;
    $table = AIOT_Database::get_table_name('orders');
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
        echo '<p>' . __('No orders table found. Please reactivate the plugin.', 'ai-order-tracker') . '</p>';
        return;
    }
    
    // Get recent orders
    $orders = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 5");
    
    if (empty($orders)) {
        echo '<p>' . __('No orders found.', 'ai-order-tracker') . '</p>';
        return;
    }
    
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Tracking ID', 'ai-order-tracker'); ?></th>
                <th><?php _e('Order ID', 'ai-order-tracker'); ?></th>
                <th><?php _e('Customer', 'ai-order-tracker'); ?></th>
                <th><?php _e('Status', 'ai-order-tracker'); ?></th>
                <th><?php _e('Created', 'ai-order-tracker'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : ?>
                <?php
                $status_info = aiot_get_order_status($order->status);
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($order->tracking_id); ?></strong>
                    </td>
                    <td><?php echo esc_html($order->order_id); ?></td>
                    <td>
                        <?php if (!empty($order->customer_name)) : ?>
                            <?php echo esc_html($order->customer_name); ?>
                        <?php elseif (!empty($order->customer_email)) : ?>
                            <?php echo esc_html($order->customer_email); ?>
                        <?php else : ?>
                            <?php _e('Guest', 'ai-order-tracker'); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="aiot-status-badge" style="background-color: <?php echo esc_attr($status_info['color']); ?>; color: white;">
                            <?php echo esc_html($status_info['label']); ?>
                        </span>
                    </td>
                    <td><?php echo aiot_format_date($order->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}