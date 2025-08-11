<?php
/**
 * Real-time API class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Real_Time_API
 */
class AIOT_Real_Time_API {

    /**
     * API endpoints
     */
    private static $endpoints = array(
        'ups' => 'https://onlinetools.ups.com/ship/v1/track',
        'fedex' => 'https://apis.fedex.com/track/v1/trackingnumbers',
        'dhl' => 'https://api.dhl.com/track/shipments',
        'usps' => 'https://api.usps.com/track/v2/tracking',
    );

    /**
     * Track package with real-time API
     *
     * @param string $courier Courier name
     * @param string $tracking_id Tracking ID
     * @param array $options Additional options
     * @return array|false Tracking data or false on failure
     */
    public static function track_package($courier, $tracking_id, $options = array()) {
        $courier = strtolower($courier);
        
        if (!isset(self::$endpoints[$courier])) {
            return false;
        }

        $endpoint = self::$endpoints[$courier];
        $api_key = self::get_api_key($courier);
        
        if (!$api_key) {
            return false;
        }

        switch ($courier) {
            case 'ups':
                return self::track_ups($endpoint, $tracking_id, $api_key, $options);
            case 'fedex':
                return self::track_fedex($endpoint, $tracking_id, $api_key, $options);
            case 'dhl':
                return self::track_dhl($endpoint, $tracking_id, $api_key, $options);
            case 'usps':
                return self::track_usps($endpoint, $tracking_id, $api_key, $options);
            default:
                return false;
        }
    }

    /**
     * Track with UPS API
     *
     * @param string $endpoint API endpoint
     * @param string $tracking_id Tracking ID
     * @param string $api_key API key
     * @param array $options Additional options
     * @return array|false Tracking data
     */
    private static function track_ups($endpoint, $tracking_id, $api_key, $options) {
        $request_data = array(
            'trackingRequest' => array(
                'request' => array(
                    'requestOption' => '1',
                    'transactionReference' => array(
                        'customerContext' => 'AI Order Tracker',
                    ),
                ),
                'trackingNumber' => array(
                    array(
                        'trackingNumber' => $tracking_id,
                    ),
                ),
            ),
        );

        $response = self::make_api_request(
            $endpoint,
            $request_data,
            array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'X-Client-Id' => 'AI-Order-Tracker',
            )
        );

        if ($response === false) {
            return false;
        }

        return self::parse_ups_response($response);
    }

    /**
     * Track with FedEx API
     *
     * @param string $endpoint API endpoint
     * @param string $tracking_id Tracking ID
     * @param string $api_key API key
     * @param array $options Additional options
     * @return array|false Tracking data
     */
    private static function track_fedex($endpoint, $tracking_id, $api_key, $options) {
        $request_data = array(
            'includeDetailedScans' => true,
            'trackingInfo' => array(
                array(
                    'trackingNumberInfo' => array(
                        'trackingNumber' => $tracking_id,
                    ),
                ),
            ),
        );

        $response = self::make_api_request(
            $endpoint,
            $request_data,
            array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'X-locale' => 'en_US',
            )
        );

        if ($response === false) {
            return false;
        }

        return self::parse_fedex_response($response);
    }

    /**
     * Track with DHL API
     *
     * @param string $endpoint API endpoint
     * @param string $tracking_id Tracking ID
     * @param string $api_key API key
     * @param array $options Additional options
     * @return array|false Tracking data
     */
    private static function track_dhl($endpoint, $tracking_id, $api_key, $options) {
        $request_data = array(
            'trackingNumbers' => array($tracking_id),
            'service' => 'express',
        );

        $response = self::make_api_request(
            $endpoint,
            $request_data,
            array(
                'DHL-API-Key' => $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            )
        );

        if ($response === false) {
            return false;
        }

        return self::parse_dhl_response($response);
    }

    /**
     * Track with USPS API
     *
     * @param string $endpoint API endpoint
     * @param string $tracking_id Tracking ID
     * @param string $api_key API key
     * @param array $options Additional options
     * @return array|false Tracking data
     */
    private static function track_usps($endpoint, $tracking_id, $api_key, $options) {
        $request_data = array(
            'trackingNumbers' => array($tracking_id),
            'includePackageDetails' => true,
            'includeBarcodes' => true,
        );

        $response = self::make_api_request(
            $endpoint,
            $request_data,
            array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            )
        );

        if ($response === false) {
            return false;
        }

        return self::parse_usps_response($response);
    }

    /**
     * Make API request
     *
     * @param string $url API URL
     * @param array $data Request data
     * @param array $headers Request headers
     * @return array|false Response data
     */
    private static function make_api_request($url, $data, $headers = array()) {
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30,
            'user-agent' => 'AI-Order-Tracker/' . AIOT_VERSION,
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            AIOT_Helpers::log('API request failed: ' . $response->get_error_message(), 'error', array(
                'url' => $url,
                'data' => $data,
            ));
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            AIOT_Helpers::log('API request returned non-200 status', 'error', array(
                'url' => $url,
                'status_code' => $response_code,
                'response' => $response_body,
            ));
            return false;
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            AIOT_Helpers::log('Failed to parse API response', 'error', array(
                'url' => $url,
                'response' => $response_body,
            ));
            return false;
        }

        return $data;
    }

    /**
     * Parse UPS response
     *
     * @param array $response UPS response
     * @return array Parsed tracking data
     */
    private static function parse_ups_response($response) {
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
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
                
                // Get estimated delivery
                if (isset($response['trackResponse']['shipment'][0]['package'][0]['deliveryDate'][0]['date'])) {
                    $tracking_data['estimated_delivery'] = $response['trackResponse']['shipment'][0]['package'][0]['deliveryDate'][0]['date'];
                }
                
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
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
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
                
                // Get estimated delivery
                if (isset($response['output']['packageTracks'][0]['estimatedDeliveryTime'])) {
                    $tracking_data['estimated_delivery'] = $response['output']['packageTracks'][0]['estimatedDeliveryTime'];
                }
                
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
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
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
                
                // Get estimated delivery
                if (isset($response['shipments'][0]['estimatedDeliveryDate'])) {
                    $tracking_data['estimated_delivery'] = $response['shipments'][0]['estimatedDeliveryDate'];
                }
                
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
        $tracking_data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );

        if (isset($response['data']['trackResults'][0]['trackingEvents'])) {
            $events = $response['data']['trackResults'][0]['trackingEvents'];
            
            if (is_array($events)) {
                // Get latest event
                $latest = $events[0];
                
                $tracking_data['status'] = self::map_usps_status($latest['eventCode']);
                $tracking_data['location'] = self::format_usps_location($latest);
                $tracking_data['description'] = $latest['eventDescription'];
                
                // Get estimated delivery
                if (isset($response['data']['trackResults'][0]['expectedDeliveryDate'])) {
                    $tracking_data['estimated_delivery'] = $response['data']['trackResults'][0]['expectedDeliveryDate'];
                }
                
                // Parse all events
                foreach ($events as $event) {
                    $tracking_data['events'][] = array(
                        'date' => $event['eventDate'],
                        'time' => $event['eventTime'],
                        'status' => self::map_usps_status($event['eventCode']),
                        'location' => self::format_usps_location($event),
                        'description' => $event['eventDescription'],
                    );
                }
            }
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
            'I' => 'in_transit',    // In transit
            'D' => 'delivered',     // Delivered
            'X' => 'failed',        // Exception
            'P' => 'picked_up',     // Picked up
            'M' => 'manifest_pickup', // Manifest pickup
            'MP' => 'processing',   // Manifest pickup
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
     * @param string $code USPS event code
     * @return string Internal status
     */
    private static function map_usps_status($code) {
        $status_map = array(
            '01' => 'processing',    // Electronic notification
            '02' => 'picked_up',     // Arrived at facility
            '03' => 'in_transit',    // Departed facility
            '04' => 'out_for_delivery', // Out for delivery
            '05' => 'delivered',     // Delivered
            '06' => 'failed',        // Delivery exception
            '07' => 'returned',     // Returned
            '08' => 'processing',    // Shipment created
            '09' => 'processing',    // Shipment accepted
            '10' => 'delivered',     // Final delivery
        );

        return isset($status_map[$code]) ? $status_map[$code] : 'unknown';
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
     * Format USPS location
     *
     * @param array $event USPS event data
     * @return string Formatted location
     */
    private static function format_usps_location($event) {
        $location = array();
        
        if (isset($event['city'])) {
            $location[] = $event['city'];
        }
        
        if (isset($event['state'])) {
            $location[] = $event['state'];
        }
        
        if (isset($event['postalCode'])) {
            $location[] = $event['postalCode'];
        }
        
        return implode(', ', $location);
    }

    /**
     * Get API key for courier
     *
     * @param string $courier Courier name
     * @return string|false API key or false if not found
     */
    private static function get_api_key($courier) {
        $option_name = 'aiot_' . $courier . '_api_key';
        return get_option($option_name, false);
    }

    /**
     * Set API key for courier
     *
     * @param string $courier Courier name
     * @param string $api_key API key
     * @return bool True on success
     */
    public static function set_api_key($courier, $api_key) {
        $option_name = 'aiot_' . $courier . '_api_key';
        return update_option($option_name, $api_key);
    }

    /**
     * Test API connection
     *
     * @param string $courier Courier name
     * @param string $api_key API key
     * @return array Test result
     */
    public static function test_api_connection($courier, $api_key) {
        $test_tracking_id = self::get_test_tracking_id($courier);
        
        if (!$test_tracking_id) {
            return array(
                'success' => false,
                'message' => __('No test tracking ID available for this courier.', 'ai-order-tracker'),
            );
        }

        $result = self::track_package($courier, $test_tracking_id);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to connect to API. Please check your API key.', 'ai-order-tracker'),
            );
        }

        return array(
            'success' => true,
            'message' => __('API connection successful.', 'ai-order-tracker'),
            'data' => $result,
        );
    }

    /**
     * Get test tracking ID for courier
     *
     * @param string $courier Courier name
     * @return string|false Test tracking ID
     */
    private static function get_test_tracking_id($courier) {
        $test_ids = array(
            'ups' => '1Z9999W99999999999',
            'fedex' => '999999999999',
            'dhl' => '1234567890',
            'usps' => '9999999999999999999999',
        );

        return isset($test_ids[$courier]) ? $test_ids[$courier] : false;
    }

    /**
     * Get API rate limit status
     *
     * @param string $courier Courier name
     * @return array Rate limit status
     */
    public static function get_rate_limit_status($courier) {
        $option_name = 'aiot_' . $courier . '_rate_limit';
        $rate_limit_data = get_option($option_name, array(
            'requests' => 0,
            'reset_time' => 0,
            'limit' => 100,
        ));

        return $rate_limit_data;
    }

    /**
     * Update API rate limit
     *
     * @param string $courier Courier name
     * @param int $requests Number of requests
     * @param int $reset_time Reset time
     */
    private static function update_rate_limit($courier, $requests, $reset_time) {
        $option_name = 'aiot_' . $courier . '_rate_limit';
        update_option($option_name, array(
            'requests' => $requests,
            'reset_time' => $reset_time,
            'limit' => 100,
        ));
    }

    /**
     * Check if API rate limit is exceeded
     *
     * @param string $courier Courier name
     * @return bool True if rate limit exceeded
     */
    public static function is_rate_limit_exceeded($courier) {
        $rate_limit = self::get_rate_limit_status($courier);
        
        if ($rate_limit['reset_time'] > time()) {
            return $rate_limit['requests'] >= $rate_limit['limit'];
        }
        
        return false;
    }
}