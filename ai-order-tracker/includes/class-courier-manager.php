<?php
/**
 * Courier manager class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Courier_Manager
 */
class AIOT_Courier_Manager {

    /**
     * Get all couriers
     *
     * @param array $args Query arguments
     * @return array Couriers
     */
    public static function get_couriers($args = array()) {
        return AIOT_Database::get_couriers($args);
    }

    /**
     * Get courier by ID
     *
     * @param int $courier_id Courier ID
     * @return array|false Courier data or false if not found
     */
    public static function get_courier($courier_id) {
        return AIOT_Database::get_courier($courier_id);
    }

    /**
     * Get courier by slug
     *
     * @param string $slug Courier slug
     * @return array|false Courier data or false if not found
     */
    public static function get_courier_by_slug($slug) {
        $couriers = self::get_couriers(array('is_active' => true));
        
        foreach ($couriers as $courier) {
            if ($courier['slug'] === $slug) {
                return $courier;
            }
        }
        
        return false;
    }

    /**
     * Create new courier
     *
     * @param array $data Courier data
     * @return int|false Courier ID or false on failure
     */
    public static function create_courier($data) {
        global $wpdb;
        
        $table = AIOT_Database::get_table_name('couriers');
        
        $defaults = array(
            'name' => '',
            'slug' => '',
            'description' => '',
            'url_pattern' => '',
            'api_endpoint' => '',
            'api_key' => '',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => '',
            'meta' => '',
            'phone' => '',
            'website' => '',
            'type' => '',
            'image' => '',
            'country' => '',
            'display_name' => '',
            'supports_international' => 0,
            'supports_domestic' => 1,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        // Generate display name if not provided
        if (empty($data['display_name'])) {
            $data['display_name'] = $data['name'];
        }
        
        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            return false;
        }
        
        // Sanitize data
        $data = array_map('sanitize_text_field', $data);
        
        // Convert boolean values
        $data['supports_international'] = $data['supports_international'] ? 1 : 0;
        $data['supports_domestic'] = $data['supports_domestic'] ? 1 : 0;
        
        // Check if slug already exists
        $existing = self::get_courier_by_slug($data['slug']);
        if ($existing) {
            return false;
        }
        
        // Serialize meta if it's an array
        if (isset($data['meta']) && is_array($data['meta'])) {
            $data['meta'] = wp_json_encode($data['meta']);
        }
        
        // Prepare format array for $wpdb->insert
        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', 
                       '%s', '%s', '%s', '%s', '%s', '%d', '%d');
        
        $result = $wpdb->insert(
            $table,
            $data,
            $format
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update courier
     *
     * @param int $courier_id Courier ID
     * @param array $data Courier data
     * @return bool True on success
     */
    public static function update_courier($courier_id, $data) {
        global $wpdb;
        
        $table = AIOT_Database::get_table_name('couriers');
        
        // Sanitize data
        $data = array_map('sanitize_text_field', $data);
        
        // Convert boolean values
        if (isset($data['supports_international'])) {
            $data['supports_international'] = $data['supports_international'] ? 1 : 0;
        }
        if (isset($data['supports_domestic'])) {
            $data['supports_domestic'] = $data['supports_domestic'] ? 1 : 0;
        }
        
        // Serialize meta if it's an array
        if (isset($data['meta']) && is_array($data['meta'])) {
            $data['meta'] = wp_json_encode($data['meta']);
        }
        
        // Prepare format arrays
        $format = array();
        foreach (array_keys($data) as $key) {
            switch ($key) {
                case 'is_active':
                case 'supports_international':
                case 'supports_domestic':
                    $format[] = '%d';
                    break;
                default:
                    $format[] = '%s';
            }
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $courier_id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Delete courier
     *
     * @param int $courier_id Courier ID
     * @return bool True on success
     */
    public static function delete_courier($courier_id) {
        global $wpdb;
        
        $table = AIOT_Database::get_table_name('couriers');
        
        $result = $wpdb->delete(
            $table,
            array('id' => $courier_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Initialize default couriers from courier data file
     *
     * @return bool True on success
     */
    public static function initialize_default_couriers() {
        $couriers_data = aiot_get_all_courier_data();
        
        if (empty($couriers_data)) {
            return false;
        }
        
        $imported_count = 0;
        
        foreach ($couriers_data as $courier_data) {
            // Check if courier already exists
            $existing = self::get_courier_by_slug($courier_data['slug']);
            
            if (!$existing) {
                // Create new courier
                $result = self::create_courier($courier_data);
                if ($result) {
                    $imported_count++;
                }
            }
        }
        
        return $imported_count > 0;
    }

    /**
     * Parse couriers CSV file
     *
     * @param string $csv_file CSV file path
     * @return array Couriers data
     */
    private static function parse_couriers_csv($csv_file) {
        $couriers = array();
        
        if (($handle = fopen($csv_file, 'r')) !== false) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 13) {
                    $couriers[] = array(
                        'name' => sanitize_text_field($data[0]),
                        'slug' => sanitize_title($data[1]),
                        'phone' => sanitize_text_field($data[2]),
                        'website' => sanitize_text_field($data[3]),
                        'type' => sanitize_text_field($data[4]),
                        'image' => sanitize_text_field($data[5]),
                        'country' => sanitize_text_field($data[6]),
                        'url_pattern' => sanitize_text_field($data[7]),
                        'display_name' => sanitize_text_field($data[8]),
                        'tracking_format' => sanitize_text_field($data[9]),
                        'supports_international' => strtolower($data[10]) === 'yes',
                        'supports_domestic' => strtolower($data[11]) === 'yes',
                        'description' => sanitize_text_field($data[12]),
                        'api_endpoint' => '',
                        'api_key' => '',
                        'is_active' => 1,
                        'settings' => '',
                        'meta' => '',
                    );
                }
            }
            
            fclose($handle);
        }
        
        return $couriers;
    }

    /**
     * Get courier tracking URL (free version - just returns the URL pattern)
     *
     * @param string $courier_slug Courier slug
     * @param string $tracking_id Tracking ID
     * @return string|false Tracking URL or false if not found
     */
    public static function get_tracking_url($courier_slug, $tracking_id) {
        $courier = self::get_courier_by_slug($courier_slug);
        
        if (!$courier || empty($courier['url_pattern'])) {
            return false;
        }
        
        // Replace tracking ID placeholder
        $url = str_replace('{tracking_id}', $tracking_id, $courier['url_pattern']);
        
        return esc_url($url);
    }

    /**
     * Get all available couriers for selection
     *
     * @return array Couriers list
     */
    public static function get_couriers_for_selection() {
        $couriers = self::get_couriers(array('is_active' => true));
        $selection = array();
        
        foreach ($couriers as $courier) {
            $selection[] = array(
                'id' => $courier['id'],
                'name' => $courier['name'],
                'slug' => $courier['slug'],
                'image' => $courier['image'],
                'url_pattern' => $courier['url_pattern'],
                'display_name' => $courier['display_name'],
                'country' => $courier['country'],
                'type' => $courier['type'],
            );
        }
        
        return $selection;
    }

    /**
     * Track package with courier API
     *
     * @param string $courier_slug Courier slug
     * @param string $tracking_id Tracking ID
     * @return array|false Tracking data or false on failure
     */
    public static function track_with_courier($courier_slug, $tracking_id) {
        $courier = self::get_courier_by_slug($courier_slug);
        
        if (!$courier || empty($courier['api_endpoint'])) {
            return false;
        }
        
        // Prepare API request
        $api_url = $courier['api_endpoint'];
        $api_key = $courier['api_key'];
        
        // Build request parameters
        $params = array(
            'tracking_id' => $tracking_id,
        );
        
        // Add courier-specific parameters from individual columns
        $additional_params = array(
            'phone' => $courier['phone'],
            'website' => $courier['website'],
            'type' => $courier['type'],
            'image' => $courier['image'],
            'country' => $courier['country'],
            'display_name' => $courier['display_name'],
        );
        
        $params = array_merge($params, $additional_params);
        
        // Make API request
        $response = self::make_api_request($api_url, $params, $api_key);
        
        if ($response === false) {
            return false;
        }
        
        // Parse response based on courier format
        return self::parse_courier_response($response, $courier['tracking_format']);
    }

    /**
     * Make API request
     *
     * @param string $url API URL
     * @param array $params Request parameters
     * @param string $api_key API key
     * @return array|false Response data or false on failure
     */
    private static function make_api_request($url, $params, $api_key = '') {
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Order-Tracker/' . AIOT_VERSION,
            ),
        );
        
        // Add API key if provided
        if (!empty($api_key)) {
            $args['headers']['Authorization'] = 'Bearer ' . $api_key;
        }
        
        // Add parameters to URL
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data;
    }

    /**
     * Parse courier response
     *
     * @param array $response API response
     * @param string $format Response format
     * @return array Parsed tracking data
     */
    private static function parse_courier_response($response, $format) {
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'events' => array(),
        );
        
        switch ($format) {
            case 'ups':
                $tracking_data = self::parse_ups_response($response);
                break;
            case 'fedex':
                $tracking_data = self::parse_fedex_response($response);
                break;
            case 'dhl':
                $tracking_data = self::parse_dhl_response($response);
                break;
            case 'usps':
                $tracking_data = self::parse_usps_response($response);
                break;
            case 'standard':
            default:
                $tracking_data = self::parse_standard_response($response);
                break;
        }
        
        return $tracking_data;
    }

    /**
     * Parse UPS response
     *
     * @param array $response UPS response
     * @return array Parsed tracking data
     */
    private static function parse_ups_response($response) {
        // Simplified UPS response parsing
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'events' => array(),
        );
        
        if (isset($response['trackResponse']['shipment'][0]['package'][0]['activity'])) {
            $activities = $response['trackResponse']['shipment'][0]['package'][0]['activity'];
            
            if (is_array($activities)) {
                // Get latest activity
                $latest = $activities[0];
                
                $tracking_data['status'] = self::map_ups_status($latest['status']['code']);
                $tracking_data['location'] = self::format_ups_location($latest);
                $tracking_data['description'] = $latest['status']['description'];
                
                // Parse all events
                foreach ($activities as $activity) {
                    $tracking_data['events'][] = array(
                        'date' => $activity['date'],
                        'time' => $activity['time'],
                        'status' => self::map_ups_status($activity['status']['code']),
                        'location' => self::format_ups_location($activity),
                        'description' => $activity['status']['description'],
                    );
                }
            }
        }
        
        return $tracking_data;
    }

    /**
     * Parse FedEx response
     *
     * @param array $response FedEx response
     * @return array Parsed tracking data
     */
    private static function parse_fedex_response($response) {
        // Simplified FedEx response parsing
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'events' => array(),
        );
        
        if (isset($response['output']['packageTracks'][0]['scanEvents'])) {
            $events = $response['output']['packageTracks'][0]['scanEvents'];
            
            if (is_array($events)) {
                // Get latest event
                $latest = $events[0];
                
                $tracking_data['status'] = self::map_fedex_status($latest['eventDescription']);
                $tracking_data['location'] = self::format_fedex_location($latest);
                $tracking_data['description'] = $latest['eventDescription'];
                
                // Parse all events
                foreach ($events as $event) {
                    $tracking_data['events'][] = array(
                        'date' => $event['date'],
                        'time' => $event['time'],
                        'status' => self::map_fedex_status($event['eventDescription']),
                        'location' => self::format_fedex_location($event),
                        'description' => $event['eventDescription'],
                    );
                }
            }
        }
        
        return $tracking_data;
    }

    /**
     * Parse DHL response
     *
     * @param array $response DHL response
     * @return array Parsed tracking data
     */
    private static function parse_dhl_response($response) {
        // Simplified DHL response parsing
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'events' => array(),
        );
        
        if (isset($response['shipments'][0]['events'])) {
            $events = $response['shipments'][0]['events'];
            
            if (is_array($events)) {
                // Get latest event
                $latest = $events[0];
                
                $tracking_data['status'] = self::map_dhl_status($latest['description']);
                $tracking_data['location'] = self::format_dhl_location($latest);
                $tracking_data['description'] = $latest['description'];
                
                // Parse all events
                foreach ($events as $event) {
                    $tracking_data['events'][] = array(
                        'date' => $event['date'],
                        'time' => $event['time'],
                        'status' => self::map_dhl_status($event['description']),
                        'location' => self::format_dhl_location($event),
                        'description' => $event['description'],
                    );
                }
            }
        }
        
        return $tracking_data;
    }

    /**
     * Parse USPS response
     *
     * @param array $response USPS response
     * @return array Parsed tracking data
     */
    private static function parse_usps_response($response) {
        // Simplified USPS response parsing
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'events' => array(),
        );
        
        if (isset($response['TrackResponse']['TrackInfo']['TrackSummary'])) {
            $summary = $response['TrackResponse']['TrackInfo']['TrackSummary'];
            
            $tracking_data['status'] = self::map_usps_status($summary['Event']);
            $tracking_data['location'] = $summary['EventCity'] . ', ' . $summary['EventState'];
            $tracking_data['description'] = $summary['Event'];
        }
        
        if (isset($response['TrackResponse']['TrackInfo']['TrackDetail'])) {
            $details = $response['TrackResponse']['TrackInfo']['TrackDetail'];
            
            if (is_array($details)) {
                foreach ($details as $detail) {
                    $tracking_data['events'][] = array(
                        'date' => $detail['EventDate'],
                        'time' => $detail['EventTime'],
                        'status' => self::map_usps_status($detail['Event']),
                        'location' => $detail['EventCity'] . ', ' . $detail['EventState'],
                        'description' => $detail['Event'],
                    );
                }
            }
        }
        
        return $tracking_data;
    }

    /**
     * Parse standard response
     *
     * @param array $response Standard response
     * @return array Parsed tracking data
     */
    private static function parse_standard_response($response) {
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'events' => array(),
        );
        
        if (isset($response['status'])) {
            $tracking_data['status'] = $response['status'];
        }
        
        if (isset($response['location'])) {
            $tracking_data['location'] = $response['location'];
        }
        
        if (isset($response['description'])) {
            $tracking_data['description'] = $response['description'];
        }
        
        if (isset($response['events']) && is_array($response['events'])) {
            $tracking_data['events'] = $response['events'];
        }
        
        return $tracking_data;
    }

    /**
     * Map UPS status code to internal status
     *
     * @param string $code UPS status code
     * @return string Internal status
     */
    private static function map_ups_status($code) {
        $status_map = array(
            'I' => 'processing',    // In transit
            'D' => 'delivered',     // Delivered
            'X' => 'exception',    // Exception
            'P' => 'picked_up',     // Picked up
            'M' => 'manifest_pickup', // Manifest pickup
        );
        
        return isset($status_map[$code]) ? $status_map[$code] : 'unknown';
    }

    /**
     * Map FedEx status to internal status
     *
     * @param string $description FedEx status description
     * @return string Internal status
     */
    private static function map_fedex_status($description) {
        $description = strtolower($description);
        
        if (strpos($description, 'delivered') !== false) {
            return 'delivered';
        } elseif (strpos($description, 'out for delivery') !== false) {
            return 'out_for_delivery';
        } elseif (strpos($description, 'in transit') !== false) {
            return 'in_transit';
        } elseif (strpos($description, 'picked up') !== false) {
            return 'shipped';
        } else {
            return 'processing';
        }
    }

    /**
     * Map DHL status to internal status
     *
     * @param string $description DHL status description
     * @return string Internal status
     */
    private static function map_dhl_status($description) {
        $description = strtolower($description);
        
        if (strpos($description, 'delivered') !== false) {
            return 'delivered';
        } elseif (strpos($description, 'out for delivery') !== false) {
            return 'out_for_delivery';
        } elseif (strpos($description, 'in transit') !== false) {
            return 'in_transit';
        } elseif (strpos($description, 'picked up') !== false) {
            return 'shipped';
        } else {
            return 'processing';
        }
    }

    /**
     * Map USPS status to internal status
     *
     * @param string $event USPS event
     * @return string Internal status
     */
    private static function map_usps_status($event) {
        $event = strtolower($event);
        
        if (strpos($event, 'delivered') !== false) {
            return 'delivered';
        } elseif (strpos($event, 'out for delivery') !== false) {
            return 'out_for_delivery';
        } elseif (strpos($event, 'in transit') !== false) {
            return 'in_transit';
        } elseif (strpos($event, 'accepted') !== false) {
            return 'shipped';
        } else {
            return 'processing';
        }
    }

    /**
     * Format UPS location
     *
     * @param array $activity UPS activity data
     * @return string Formatted location
     */
    private static function format_ups_location($activity) {
        $location = array();
        
        if (isset($activity['location']['city'])) {
            $location[] = $activity['location']['city'];
        }
        
        if (isset($activity['location']['stateProvinceCode'])) {
            $location[] = $activity['location']['stateProvinceCode'];
        }
        
        if (isset($activity['location']['countryCode'])) {
            $location[] = $activity['location']['countryCode'];
        }
        
        return implode(', ', $location);
    }

    /**
     * Format FedEx location
     *
     * @param array $event FedEx event data
     * @return string Formatted location
     */
    private static function format_fedex_location($event) {
        $location = array();
        
        if (isset($event['city'])) {
            $location[] = $event['city'];
        }
        
        if (isset($event['stateOrProvinceCode'])) {
            $location[] = $event['stateOrProvinceCode'];
        }
        
        if (isset($event['countryCode'])) {
            $location[] = $event['countryCode'];
        }
        
        return implode(', ', $location);
    }

    /**
     * Format DHL location
     *
     * @param array $event DHL event data
     * @return string Formatted location
     */
    private static function format_dhl_location($event) {
        $location = array();
        
        if (isset($event['location']['address']['addressLocality'])) {
            $location[] = $event['location']['address']['addressLocality'];
        }
        
        if (isset($event['location']['address']['addressRegion'])) {
            $location[] = $event['location']['address']['addressRegion'];
        }
        
        if (isset($event['location']['address']['addressCountry'])) {
            $location[] = $event['location']['address']['addressCountry'];
        }
        
        return implode(', ', $location);
    }

    /**
     * Get default couriers
     *
     * @return array Default couriers
     */
    public static function get_default_couriers() {
        return array(
            array(
                'name' => 'UPS',
                'slug' => 'ups',
                'description' => 'United Parcel Service',
                'url_pattern' => 'https://www.ups.com/track?tracknum={tracking_id}',
                'tracking_format' => 'ups',
                'is_active' => 1,
            ),
            array(
                'name' => 'FedEx',
                'slug' => 'fedex',
                'description' => 'Federal Express',
                'url_pattern' => 'https://www.fedex.com/fedextrack/?tracknumbers={tracking_id}',
                'tracking_format' => 'fedex',
                'is_active' => 1,
            ),
            array(
                'name' => 'DHL',
                'slug' => 'dhl',
                'description' => 'DHL Express',
                'url_pattern' => 'https://www.dhl.com/en/express/tracking.shtml?brand=DHL&AWB={tracking_id}',
                'tracking_format' => 'dhl',
                'is_active' => 1,
            ),
            array(
                'name' => 'USPS',
                'slug' => 'usps',
                'description' => 'United States Postal Service',
                'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
                'tracking_format' => 'usps',
                'is_active' => 1,
            ),
            array(
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'Standard Delivery',
                'url_pattern' => '',
                'tracking_format' => 'standard',
                'is_active' => 1,
            ),
        );
    }

    /**
     * Install default couriers
     *
     * @return bool True on success
     */
    public static function install_default_couriers() {
        $default_couriers = self::get_default_couriers();
        
        foreach ($default_couriers as $courier) {
            // Check if courier already exists
            $existing = self::get_courier_by_slug($courier['slug']);
            
            if (!$existing) {
                self::create_courier($courier);
            }
        }
        
        return true;
    }
}