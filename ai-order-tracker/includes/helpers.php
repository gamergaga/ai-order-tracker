<?php
/**
 * Helper functions for AI Order Tracker
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Get countries list
 *
 * @return array Countries list
 */
function aiot_get_countries_list() {
    return array(
        'AF' => __('Afghanistan', 'ai-order-tracker'),
        'AL' => __('Albania', 'ai-order-tracker'),
        'DZ' => __('Algeria', 'ai-order-tracker'),
        'AS' => __('American Samoa', 'ai-order-tracker'),
        'AD' => __('Andorra', 'ai-order-tracker'),
        'AO' => __('Angola', 'ai-order-tracker'),
        'AI' => __('Anguilla', 'ai-order-tracker'),
        'AQ' => __('Antarctica', 'ai-order-tracker'),
        'AG' => __('Antigua and Barbuda', 'ai-order-tracker'),
        'AR' => __('Argentina', 'ai-order-tracker'),
        'AM' => __('Armenia', 'ai-order-tracker'),
        'AW' => __('Aruba', 'ai-order-tracker'),
        'AU' => __('Australia', 'ai-order-tracker'),
        'AT' => __('Austria', 'ai-order-tracker'),
        'AZ' => __('Azerbaijan', 'ai-order-tracker'),
        'BS' => __('Bahamas', 'ai-order-tracker'),
        'BH' => __('Bahrain', 'ai-order-tracker'),
        'BD' => __('Bangladesh', 'ai-order-tracker'),
        'BB' => __('Barbados', 'ai-order-tracker'),
        'BY' => __('Belarus', 'ai-order-tracker'),
        'BE' => __('Belgium', 'ai-order-tracker'),
        'BZ' => __('Belize', 'ai-order-tracker'),
        'BJ' => __('Benin', 'ai-order-tracker'),
        'BM' => __('Bermuda', 'ai-order-tracker'),
        'BT' => __('Bhutan', 'ai-order-tracker'),
        'BO' => __('Bolivia', 'ai-order-tracker'),
        'BQ' => __('Bonaire, Sint Eustatius and Saba', 'ai-order-tracker'),
        'BA' => __('Bosnia and Herzegovina', 'ai-order-tracker'),
        'BW' => __('Botswana', 'ai-order-tracker'),
        'BV' => __('Bouvet Island', 'ai-order-tracker'),
        'BR' => __('Brazil', 'ai-order-tracker'),
        'IO' => __('British Indian Ocean Territory', 'ai-order-tracker'),
        'BN' => __('Brunei Darussalam', 'ai-order-tracker'),
        'BG' => __('Bulgaria', 'ai-order-tracker'),
        'BF' => __('Burkina Faso', 'ai-order-tracker'),
        'BI' => __('Burundi', 'ai-order-tracker'),
        'CV' => __('Cabo Verde', 'ai-order-tracker'),
        'KH' => __('Cambodia', 'ai-order-tracker'),
        'CM' => __('Cameroon', 'ai-order-tracker'),
        'CA' => __('Canada', 'ai-order-tracker'),
        'KY' => __('Cayman Islands', 'ai-order-tracker'),
        'CF' => __('Central African Republic', 'ai-order-tracker'),
        'TD' => __('Chad', 'ai-order-tracker'),
        'CL' => __('Chile', 'ai-order-tracker'),
        'CN' => __('China', 'ai-order-tracker'),
        'CX' => __('Christmas Island', 'ai-order-tracker'),
        'CC' => __('Cocos (Keeling) Islands', 'ai-order-tracker'),
        'CO' => __('Colombia', 'ai-order-tracker'),
        'KM' => __('Comoros', 'ai-order-tracker'),
        'CG' => __('Congo', 'ai-order-tracker'),
        'CD' => __('Congo, Democratic Republic of the', 'ai-order-tracker'),
        'CK' => __('Cook Islands', 'ai-order-tracker'),
        'CR' => __('Costa Rica', 'ai-order-tracker'),
        'CI' => __('Côte d\'Ivoire', 'ai-order-tracker'),
        'HR' => __('Croatia', 'ai-order-tracker'),
        'CU' => __('Cuba', 'ai-order-tracker'),
        'CW' => __('Curaçao', 'ai-order-tracker'),
        'CY' => __('Cyprus', 'ai-order-tracker'),
        'CZ' => __('Czechia', 'ai-order-tracker'),
        'DK' => __('Denmark', 'ai-order-tracker'),
        'DJ' => __('Djibouti', 'ai-order-tracker'),
        'DM' => __('Dominica', 'ai-order-tracker'),
        'DO' => __('Dominican Republic', 'ai-order-tracker'),
        'EC' => __('Ecuador', 'ai-order-tracker'),
        'EG' => __('Egypt', 'ai-order-tracker'),
        'SV' => __('El Salvador', 'ai-order-tracker'),
        'GQ' => __('Equatorial Guinea', 'ai-order-tracker'),
        'ER' => __('Eritrea', 'ai-order-tracker'),
        'EE' => __('Estonia', 'ai-order-tracker'),
        'SZ' => __('Eswatini', 'ai-order-tracker'),
        'ET' => __('Ethiopia', 'ai-order-tracker'),
        'FK' => __('Falkland Islands (Malvinas)', 'ai-order-tracker'),
        'FO' => __('Faroe Islands', 'ai-order-tracker'),
        'FJ' => __('Fiji', 'ai-order-tracker'),
        'FI' => __('Finland', 'ai-order-tracker'),
        'FR' => __('France', 'ai-order-tracker'),
        'GF' => __('French Guiana', 'ai-order-tracker'),
        'PF' => __('French Polynesia', 'ai-order-tracker'),
        'TF' => __('French Southern Territories', 'ai-order-tracker'),
        'GA' => __('Gabon', 'ai-order-tracker'),
        'GM' => __('Gambia', 'ai-order-tracker'),
        'GE' => __('Georgia', 'ai-order-tracker'),
        'DE' => __('Germany', 'ai-order-tracker'),
        'GH' => __('Ghana', 'ai-order-tracker'),
        'GI' => __('Gibraltar', 'ai-order-tracker'),
        'GR' => __('Greece', 'ai-order-tracker'),
        'GL' => __('Greenland', 'ai-order-tracker'),
        'GD' => __('Grenada', 'ai-order-tracker'),
        'GP' => __('Guadeloupe', 'ai-order-tracker'),
        'GU' => __('Guam', 'ai-order-tracker'),
        'GT' => __('Guatemala', 'ai-order-tracker'),
        'GG' => __('Guernsey', 'ai-order-tracker'),
        'GN' => __('Guinea', 'ai-order-tracker'),
        'GW' => __('Guinea-Bissau', 'ai-order-tracker'),
        'GY' => __('Guyana', 'ai-order-tracker'),
        'HT' => __('Haiti', 'ai-order-tracker'),
        'HM' => __('Heard Island and McDonald Islands', 'ai-order-tracker'),
        'VA' => __('Holy See', 'ai-order-tracker'),
        'HN' => __('Honduras', 'ai-order-tracker'),
        'HK' => __('Hong Kong', 'ai-order-tracker'),
        'HU' => __('Hungary', 'ai-order-tracker'),
        'IS' => __('Iceland', 'ai-order-tracker'),
        'IN' => __('India', 'ai-order-tracker'),
        'ID' => __('Indonesia', 'ai-order-tracker'),
        'IR' => __('Iran, Islamic Republic of', 'ai-order-tracker'),
        'IQ' => __('Iraq', 'ai-order-tracker'),
        'IE' => __('Ireland', 'ai-order-tracker'),
        'IM' => __('Isle of Man', 'ai-order-tracker'),
        'IL' => __('Israel', 'ai-order-tracker'),
        'IT' => __('Italy', 'ai-order-tracker'),
        'JM' => __('Jamaica', 'ai-order-tracker'),
        'JP' => __('Japan', 'ai-order-tracker'),
        'JE' => __('Jersey', 'ai-order-tracker'),
        'JO' => __('Jordan', 'ai-order-tracker'),
        'KZ' => __('Kazakhstan', 'ai-order-tracker'),
        'KE' => __('Kenya', 'ai-order-tracker'),
        'KI' => __('Kiribati', 'ai-order-tracker'),
        'KP' => __('Korea, Democratic People\'s Republic of', 'ai-order-tracker'),
        'KR' => __('Korea, Republic of', 'ai-order-tracker'),
        'KW' => __('Kuwait', 'ai-order-tracker'),
        'KG' => __('Kyrgyzstan', 'ai-order-tracker'),
        'LA' => __('Lao People\'s Democratic Republic', 'ai-order-tracker'),
        'LV' => __('Latvia', 'ai-order-tracker'),
        'LB' => __('Lebanon', 'ai-order-tracker'),
        'LS' => __('Lesotho', 'ai-order-tracker'),
        'LR' => __('Liberia', 'ai-order-tracker'),
        'LY' => __('Libya', 'ai-order-tracker'),
        'LI' => __('Liechtenstein', 'ai-order-tracker'),
        'LT' => __('Lithuania', 'ai-order-tracker'),
        'LU' => __('Luxembourg', 'ai-order-tracker'),
        'MO' => __('Macao', 'ai-order-tracker'),
        'MG' => __('Madagascar', 'ai-order-tracker'),
        'MW' => __('Malawi', 'ai-order-tracker'),
        'MY' => __('Malaysia', 'ai-order-tracker'),
        'MV' => __('Maldives', 'ai-order-tracker'),
        'ML' => __('Mali', 'ai-order-tracker'),
        'MT' => __('Malta', 'ai-order-tracker'),
        'MH' => __('Marshall Islands', 'ai-order-tracker'),
        'MQ' => __('Martinique', 'ai-order-tracker'),
        'MR' => __('Mauritania', 'ai-order-tracker'),
        'MU' => __('Mauritius', 'ai-order-tracker'),
        'YT' => __('Mayotte', 'ai-order-tracker'),
        'MX' => __('Mexico', 'ai-order-tracker'),
        'FM' => __('Micronesia, Federated States of', 'ai-order-tracker'),
        'MD' => __('Moldova, Republic of', 'ai-order-tracker'),
        'MC' => __('Monaco', 'ai-order-tracker'),
        'MN' => __('Mongolia', 'ai-order-tracker'),
        'ME' => __('Montenegro', 'ai-order-tracker'),
        'MS' => __('Montserrat', 'ai-order-tracker'),
        'MA' => __('Morocco', 'ai-order-tracker'),
        'MZ' => __('Mozambique', 'ai-order-tracker'),
        'MM' => __('Myanmar', 'ai-order-tracker'),
        'NA' => __('Namibia', 'ai-order-tracker'),
        'NR' => __('Nauru', 'ai-order-tracker'),
        'NP' => __('Nepal', 'ai-order-tracker'),
        'NL' => __('Netherlands', 'ai-order-tracker'),
        'NC' => __('New Caledonia', 'ai-order-tracker'),
        'NZ' => __('New Zealand', 'ai-order-tracker'),
        'NI' => __('Nicaragua', 'ai-order-tracker'),
        'NE' => __('Niger', 'ai-order-tracker'),
        'NG' => __('Nigeria', 'ai-order-tracker'),
        'NU' => __('Niue', 'ai-order-tracker'),
        'NF' => __('Norfolk Island', 'ai-order-tracker'),
        'MK' => __('North Macedonia', 'ai-order-tracker'),
        'MP' => __('Northern Mariana Islands', 'ai-order-tracker'),
        'NO' => __('Norway', 'ai-order-tracker'),
        'OM' => __('Oman', 'ai-order-tracker'),
        'PK' => __('Pakistan', 'ai-order-tracker'),
        'PW' => __('Palau', 'ai-order-tracker'),
        'PS' => __('Palestine, State of', 'ai-order-tracker'),
        'PA' => __('Panama', 'ai-order-tracker'),
        'PG' => __('Papua New Guinea', 'ai-order-tracker'),
        'PY' => __('Paraguay', 'ai-order-tracker'),
        'PE' => __('Peru', 'ai-order-tracker'),
        'PH' => __('Philippines', 'ai-order-tracker'),
        'PN' => __('Pitcairn', 'ai-order-tracker'),
        'PL' => __('Poland', 'ai-order-tracker'),
        'PT' => __('Portugal', 'ai-order-tracker'),
        'PR' => __('Puerto Rico', 'ai-order-tracker'),
        'QA' => __('Qatar', 'ai-order-tracker'),
        'RE' => __('Réunion', 'ai-order-tracker'),
        'RO' => __('Romania', 'ai-order-tracker'),
        'RU' => __('Russian Federation', 'ai-order-tracker'),
        'RW' => __('Rwanda', 'ai-order-tracker'),
        'BL' => __('Saint Barthélemy', 'ai-order-tracker'),
        'SH' => __('Saint Helena, Ascension and Tristan da Cunha', 'ai-order-tracker'),
        'KN' => __('Saint Kitts and Nevis', 'ai-order-tracker'),
        'LC' => __('Saint Lucia', 'ai-order-tracker'),
        'MF' => __('Saint Martin (French part)', 'ai-order-tracker'),
        'PM' => __('Saint Pierre and Miquelon', 'ai-order-tracker'),
        'VC' => __('Saint Vincent and the Grenadines', 'ai-order-tracker'),
        'WS' => __('Samoa', 'ai-order-tracker'),
        'SM' => __('San Marino', 'ai-order-tracker'),
        'ST' => __('Sao Tome and Principe', 'ai-order-tracker'),
        'SA' => __('Saudi Arabia', 'ai-order-tracker'),
        'SN' => __('Senegal', 'ai-order-tracker'),
        'RS' => __('Serbia', 'ai-order-tracker'),
        'SC' => __('Seychelles', 'ai-order-tracker'),
        'SL' => __('Sierra Leone', 'ai-order-tracker'),
        'SG' => __('Singapore', 'ai-order-tracker'),
        'SX' => __('Sint Maarten (Dutch part)', 'ai-order-tracker'),
        'SK' => __('Slovakia', 'ai-order-tracker'),
        'SI' => __('Slovenia', 'ai-order-tracker'),
        'SB' => __('Solomon Islands', 'ai-order-tracker'),
        'SO' => __('Somalia', 'ai-order-tracker'),
        'ZA' => __('South Africa', 'ai-order-tracker'),
        'GS' => __('South Georgia and the South Sandwich Islands', 'ai-order-tracker'),
        'SS' => __('South Sudan', 'ai-order-tracker'),
        'ES' => __('Spain', 'ai-order-tracker'),
        'LK' => __('Sri Lanka', 'ai-order-tracker'),
        'SD' => __('Sudan', 'ai-order-tracker'),
        'SR' => __('Suriname', 'ai-order-tracker'),
        'SJ' => __('Svalbard and Jan Mayen', 'ai-order-tracker'),
        'SE' => __('Sweden', 'ai-order-tracker'),
        'CH' => __('Switzerland', 'ai-order-tracker'),
        'SY' => __('Syrian Arab Republic', 'ai-order-tracker'),
        'TW' => __('Taiwan, Province of China', 'ai-order-tracker'),
        'TJ' => __('Tajikistan', 'ai-order-tracker'),
        'TZ' => __('Tanzania, United Republic of', 'ai-order-tracker'),
        'TH' => __('Thailand', 'ai-order-tracker'),
        'TL' => __('Timor-Leste', 'ai-order-tracker'),
        'TG' => __('Togo', 'ai-order-tracker'),
        'TK' => __('Tokelau', 'ai-order-tracker'),
        'TO' => __('Tonga', 'ai-order-tracker'),
        'TT' => __('Trinidad and Tobago', 'ai-order-tracker'),
        'TN' => __('Tunisia', 'ai-order-tracker'),
        'TR' => __('Turkey', 'ai-order-tracker'),
        'TM' => __('Turkmenistan', 'ai-order-tracker'),
        'TC' => __('Turks and Caicos Islands', 'ai-order-tracker'),
        'TV' => __('Tuvalu', 'ai-order-tracker'),
        'UG' => __('Uganda', 'ai-order-tracker'),
        'UA' => __('Ukraine', 'ai-order-tracker'),
        'AE' => __('United Arab Emirates', 'ai-order-tracker'),
        'GB' => __('United Kingdom of Great Britain and Northern Ireland', 'ai-order-tracker'),
        'UM' => __('United States Minor Outlying Islands', 'ai-order-tracker'),
        'US' => __('United States of America', 'ai-order-tracker'),
        'UY' => __('Uruguay', 'ai-order-tracker'),
        'UZ' => __('Uzbekistan', 'ai-order-tracker'),
        'VU' => __('Vanuatu', 'ai-order-tracker'),
        'VE' => __('Venezuela, Bolivarian Republic of', 'ai-order-tracker'),
        'VN' => __('Viet Nam', 'ai-order-tracker'),
        'VG' => __('Virgin Islands, British', 'ai-order-tracker'),
        'VI' => __('Virgin Islands, U.S.', 'ai-order-tracker'),
        'WF' => __('Wallis and Futuna', 'ai-order-tracker'),
        'EH' => __('Western Sahara', 'ai-order-tracker'),
        'YE' => __('Yemen', 'ai-order-tracker'),
        'ZM' => __('Zambia', 'ai-order-tracker'),
        'ZW' => __('Zimbabwe', 'ai-order-tracker'),
    );
}

/**
 * Generate tracking ID
 *
 * @return string Tracking ID
 */
function aiot_generate_tracking_id() {
    $prefix = 'AIOT';
    $random = mt_rand(100000, 999999);
    $timestamp = time();
    
    return $prefix . $random . substr($timestamp, -6);
}

/**
 * Format tracking history
 *
 * @param array $history Tracking history
 * @return array Formatted history
 */
function aiot_format_tracking_history($history) {
    if (empty($history) || !is_array($history)) {
        return array();
    }
    
    $formatted = array();
    
    foreach ($history as $event) {
        $formatted[] = array(
            'timestamp' => isset($event['timestamp']) ? $event['timestamp'] : current_time('mysql'),
            'status' => isset($event['status']) ? $event['status'] : 'unknown',
            'location' => isset($event['location']) ? $event['location'] : '',
            'description' => isset($event['description']) ? $event['description'] : '',
        );
    }
    
    return $formatted;
}

/**
 * Get tracking status text
 *
 * @param string $status Status key
 * @return string Status text
 */
function aiot_get_tracking_status_text($status) {
    $statuses = array(
        'order_confirmed' => __('Order Confirmed', 'ai-order-tracker'),
        'order_processed' => __('Order Processed', 'ai-order-tracker'),
        'in_transit' => __('In Transit', 'ai-order-tracker'),
        'out_for_delivery' => __('Out for Delivery', 'ai-order-tracker'),
        'delivered' => __('Delivered', 'ai-order-tracker'),
        'processing' => __('Processing', 'ai-order-tracker'),
        'shipped' => __('Shipped', 'ai-order-tracker'),
        'exception' => __('Exception', 'ai-order-tracker'),
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * Get tracking progress percentage
 *
 * @param string $status Status key
 * @return int Progress percentage
 */
function aiot_get_tracking_progress($status) {
    $progress = array(
        'order_confirmed' => 0,
        'order_processed' => 25,
        'in_transit' => 50,
        'out_for_delivery' => 75,
        'delivered' => 100,
        'processing' => 10,
        'confirmed' => 20,
        'packed' => 30,
        'shipped' => 60,
        'exception' => 0,
    );
    
    return isset($progress[$status]) ? $progress[$status] : 0;
}

/**
 * Get total orders count
 *
 * @return int Total orders
 */
function aiot_get_total_orders() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_orders';
    
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

/**
 * Get delivered orders count
 *
 * @return int Delivered orders
 */
function aiot_get_delivered_orders() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_orders';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE status = %s",
        'delivered'
    ));
}

/**
 * Get in transit orders count
 *
 * @return int In transit orders
 */
function aiot_get_in_transit_orders() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_orders';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE status IN (%s, %s)",
        'in_transit', 'shipped'
    ));
}

/**
 * Get processing orders count
 *
 * @return int Processing orders
 */
function aiot_get_processing_orders() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_orders';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE status IN (%s, %s, %s)",
        'processing', 'confirmed', 'packed'
    ));
}

/**
 * Get courier options
 *
 * @return array Courier options
 */
function aiot_get_carier_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_couriers';
    
    $couriers = $wpdb->get_results(
        "SELECT slug, name FROM $table_name WHERE is_active = 1 ORDER BY name ASC",
        ARRAY_A
    );
    
    $options = array('standard' => __('Standard', 'ai-order-tracker'));
    
    foreach ($couriers as $courier) {
        $options[$courier['slug']] = $courier['name'];
    }
    
    return $options;
}

/**
 * Render recent orders table
 */
function aiot_render_recent_orders_table() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'aiot_orders';
    
    $orders = $wpdb->get_results(
        "SELECT * FROM $orders_table ORDER BY created_at DESC LIMIT 10",
        ARRAY_A
    );
    
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
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo esc_html($order['tracking_id']); ?></td>
                <td><?php echo esc_html($order['order_id'] ?: 'N/A'); ?></td>
                <td><?php echo esc_html($order['customer_name'] ?: 'N/A'); ?></td>
                <td>
                    <span class="aiot-status-badge aiot-status-<?php echo esc_attr($order['status']); ?>">
                        <?php echo esc_html(aiot_get_tracking_status_text($order['status'])); ?>
                    </span>
                </td>
                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order['created_at']))); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render orders table
 */
function aiot_render_orders_table() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'aiot_orders';
    
    $orders = $wpdb->get_results(
        "SELECT * FROM $orders_table ORDER BY created_at DESC",
        ARRAY_A
    );
    
    if (empty($orders)) {
        echo '<p>' . __('No orders found.', 'ai-order-tracker') . '</p>';
        return;
    }
    
    ?>
    <div class="aiot-table-wrapper">
        <table class="aiot-table">
            <thead>
                <tr>
                    <th><?php _e('Tracking ID', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Order ID', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Customer', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Status', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Progress', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Created', 'ai-order-tracker'); ?></th>
                    <th><?php _e('Actions', 'ai-order-tracker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo esc_html($order['tracking_id']); ?></td>
                    <td><?php echo esc_html($order['order_id'] ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($order['customer_name'] ?: 'N/A'); ?></td>
                    <td>
                        <span class="aiot-status-badge aiot-status-<?php echo esc_attr($order['status']); ?>">
                            <?php echo esc_html(aiot_get_tracking_status_text($order['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <div class="aiot-progress-bar">
                            <div class="aiot-progress-fill" style="width: <?php echo esc_attr($order['progress']); ?>%"></div>
                        </div>
                        <small><?php echo esc_html($order['progress']); ?>%</small>
                    </td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order['created_at']))); ?></td>
                    <td>
                        <button type="button" class="button aiot-row-action" data-action="edit" data-id="<?php echo esc_attr($order['id']); ?>">
                            <?php _e('Edit', 'ai-order-tracker'); ?>
                        </button>
                        <button type="button" class="button aiot-row-action" data-action="view" data-id="<?php echo esc_attr($order['id']); ?>">
                            <?php _e('View', 'ai-order-tracker'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Sanitize tracking ID
 *
 * @param string $tracking_id Tracking ID
 * @return string Sanitized tracking ID
 */
function aiot_sanitize_tracking_id($tracking_id) {
    return preg_replace('/[^A-Z0-9]/i', '', strtoupper($tracking_id));
}

/**
 * Validate tracking ID format
 *
 * @param string $tracking_id Tracking ID
 * @return bool Is valid
 */
function aiot_validate_tracking_id($tracking_id) {
    return preg_match('/^[A-Z0-9]{8,20}$/i', $tracking_id) === 1;
}

/**
 * Get courier options for select dropdown
 *
 * @return array Courier options
 */
function aiot_get_courier_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_couriers';
    
    $couriers = $wpdb->get_results(
        "SELECT name, slug FROM $table_name WHERE is_active = 1 ORDER BY name"
    );
    
    $options = array();
    foreach ($couriers as $courier) {
        $options[$courier->slug] = $courier->name;
    }
    
    return $options;
}

/**
 * Get order statistics
 *
 * @return array Order statistics
 */
function aiot_get_order_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aiot_orders';
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $delivered = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'delivered'");
    $in_transit = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'in_transit'");
    $processing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'processing'");
    
    return array(
        'total' => intval($total),
        'delivered' => intval($delivered),
        'in_transit' => intval($in_transit),
        'processing' => intval($processing),
    );
}