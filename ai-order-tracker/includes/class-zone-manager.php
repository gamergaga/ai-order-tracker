<?php
/**
 * Zone manager class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Zone_Manager
 */
class AIOT_Zone_Manager {

    /**
     * Get all zones
     *
     * @param array $args Query arguments
     * @return array Zones
     */
    public static function get_zones($args = array()) {
        return AIOT_Database::get_zones($args);
    }

    /**
     * Get zone by ID
     *
     * @param int $zone_id Zone ID
     * @return array|false Zone data or false if not found
     */
    public static function get_zone($zone_id) {
        return AIOT_Database::get_zone($zone_id);
    }

    /**
     * Get zone by name
     *
     * @param string $name Zone name
     * @return array|false Zone data or false if not found
     */
    public static function get_zone_by_name($name) {
        $zones = self::get_zones(array('is_active' => true));
        
        foreach ($zones as $zone) {
            if ($zone['name'] === $name) {
                return $zone;
            }
        }
        
        return false;
    }

    /**
     * Create new zone
     *
     * @param array $data Zone data
     * @return int|false Zone ID or false on failure
     */
    public static function create_zone($data) {
        global $wpdb;
        
        $table = AIOT_Database::get_table_name('zones');
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'type' => 'country',
            'coordinates' => '',
            'countries' => '',
            'states' => '',
            'cities' => '',
            'postal_codes' => '',
            'delivery_days' => 1,
            'delivery_cost' => 0.00,
            'is_active' => 1,
            'meta' => '',
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['delivery_days'])) {
            return false;
        }
        
        // Sanitize data
        $data['name'] = sanitize_text_field($data['name']);
        $data['description'] = sanitize_textarea_field($data['description']);
        $data['type'] = sanitize_text_field($data['type']);
        $data['delivery_days'] = intval($data['delivery_days']);
        $data['delivery_cost'] = floatval($data['delivery_cost']);
        $data['is_active'] = intval($data['is_active']);
        
        // Serialize array fields
        $array_fields = array('countries', 'states', 'cities', 'postal_codes', 'coordinates', 'meta');
        
        foreach ($array_fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = wp_json_encode($data[$field]);
            }
        }
        
        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update zone
     *
     * @param int $zone_id Zone ID
     * @param array $data Zone data
     * @return bool True on success
     */
    public static function update_zone($zone_id, $data) {
        global $wpdb;
        
        $table = AIOT_Database::get_table_name('zones');
        
        // Sanitize data
        $data['name'] = sanitize_text_field($data['name']);
        $data['description'] = sanitize_textarea_field($data['description']);
        $data['type'] = sanitize_text_field($data['type']);
        $data['delivery_days'] = intval($data['delivery_days']);
        $data['delivery_cost'] = floatval($data['delivery_cost']);
        $data['is_active'] = intval($data['is_active']);
        
        // Serialize array fields
        $array_fields = array('countries', 'states', 'cities', 'postal_codes', 'coordinates', 'meta');
        
        foreach ($array_fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = wp_json_encode($data[$field]);
            }
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $zone_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Delete zone
     *
     * @param int $zone_id Zone ID
     * @return bool True on success
     */
    public static function delete_zone($zone_id) {
        global $wpdb;
        
        $table = AIOT_Database::get_table_name('zones');
        
        $result = $wpdb->delete(
            $table,
            array('id' => $zone_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Get zone for address
     *
     * @param string $address Address
     * @return array|false Zone data or false if not found
     */
    public static function get_zone_for_address($address) {
        $zones = self::get_zones(array('is_active' => true));
        
        foreach ($zones as $zone) {
            if (self::address_matches_zone($address, $zone)) {
                return $zone;
            }
        }
        
        return false;
    }

    /**
     * Check if address matches zone
     *
     * @param string $address Address
     * @param array $zone Zone data
     * @return bool True if matches
     */
    public static function address_matches_zone($address, $zone) {
        $address_lower = strtolower($address);
        
        // Check countries
        $countries = json_decode($zone['countries'], true);
        if (is_array($countries) && !empty($countries)) {
            foreach ($countries as $country) {
                if (stripos($address_lower, strtolower($country)) !== false) {
                    return true;
                }
            }
        }
        
        // Check states
        $states = json_decode($zone['states'], true);
        if (is_array($states) && !empty($states)) {
            foreach ($states as $state) {
                if (stripos($address_lower, strtolower($state)) !== false) {
                    return true;
                }
            }
        }
        
        // Check cities
        $cities = json_decode($zone['cities'], true);
        if (is_array($cities) && !empty($cities)) {
            foreach ($cities as $city) {
                if (stripos($address_lower, strtolower($city)) !== false) {
                    return true;
                }
            }
        }
        
        // Check postal codes
        $postal_codes = json_decode($zone['postal_codes'], true);
        if (is_array($postal_codes) && !empty($postal_codes)) {
            foreach ($postal_codes as $postal_code) {
                if (stripos($address_lower, strtolower($postal_code)) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get delivery time for address
     *
     * @param string $address Address
     * @return array Delivery information
     */
    public static function get_delivery_time($address) {
        $zone = self::get_zone_for_address($address);
        
        if ($zone) {
            return array(
                'zone_id' => $zone['id'],
                'zone_name' => $zone['name'],
                'delivery_days' => $zone['delivery_days'],
                'delivery_cost' => $zone['delivery_cost'],
                'estimated_delivery' => self::calculate_estimated_delivery($zone['delivery_days']),
            );
        }
        
        // Return default values
        return array(
            'zone_id' => 0,
            'zone_name' => 'Default',
            'delivery_days' => get_option('aiot_default_delivery_days', 3),
            'delivery_cost' => get_option('aiot_default_delivery_cost', 0.00),
            'estimated_delivery' => self::calculate_estimated_delivery(get_option('aiot_default_delivery_days', 3)),
        );
    }

    /**
     * Calculate estimated delivery date
     *
     * @param int $days Number of days
     * @return string Estimated delivery date
     */
    public static function calculate_estimated_delivery($days) {
        $delivery_date = new DateTime();
        $delivery_date->add(new DateInterval('P' . $days . 'D'));
        
        return $delivery_date->format('Y-m-d');
    }

    /**
     * Get zone statistics
     *
     * @return array Zone statistics
     */
    public static function get_zone_statistics() {
        $zones = self::get_zones();
        $stats = array(
            'total_zones' => count($zones),
            'active_zones' => 0,
            'average_delivery_days' => 0,
            'zone_distribution' => array(),
        );
        
        $total_days = 0;
        $active_count = 0;
        
        foreach ($zones as $zone) {
            if ($zone['is_active']) {
                $stats['active_zones']++;
                $total_days += $zone['delivery_days'];
                $active_count++;
            }
            
            // Group by delivery days
            $days_range = self::get_days_range($zone['delivery_days']);
            if (!isset($stats['zone_distribution'][$days_range])) {
                $stats['zone_distribution'][$days_range] = 0;
            }
            $stats['zone_distribution'][$days_range]++;
        }
        
        if ($active_count > 0) {
            $stats['average_delivery_days'] = round($total_days / $active_count, 1);
        }
        
        return $stats;
    }

    /**
     * Get days range for statistics
     *
     * @param int $days Number of days
     * @return string Range label
     */
    private static function get_days_range($days) {
        if ($days <= 1) {
            return '1 day';
        } elseif ($days <= 3) {
            return '1-3 days';
        } elseif ($days <= 7) {
            return '3-7 days';
        } elseif ($days <= 14) {
            return '7-14 days';
        } else {
            return '14+ days';
        }
    }

    /**
     * Get countries data
     *
     * @return array Countries data
     */
    public static function get_countries_data() {
        $file_path = AIOT_PATH . 'assets/geo/countries.json';
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        return array();
    }

    /**
     * Get states data for country
     *
     * @param string $country Country code
     * @return array States data
     */
    public static function get_states_data($country) {
        $file_path = AIOT_PATH . 'assets/geo/states-' . strtolower($country) . '.json';
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        return array();
    }

    /**
     * Get cities data for state
     *
     * @param string $country Country code
     * @param string $state State code
     * @return array Cities data
     */
    public static function get_cities_data($country, $state) {
        $file_path = AIOT_PATH . 'assets/geo/cities-' . strtolower($country) . '-' . strtolower($state) . '.json';
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        return array();
    }

    /**
     * Get default zones
     *
     * @return array Default zones
     */
    public static function get_default_zones() {
        return array(
            array(
                'name' => 'North Zone',
                'description' => 'Northern states with good connectivity',
                'type' => 'country',
                'countries' => array('US', 'CA'),
                'states' => array('Washington', 'Oregon', 'Idaho', 'Montana', 'North Dakota', 'Minnesota', 'Wisconsin', 'Michigan', 'New York', 'Vermont', 'New Hampshire', 'Maine', 'Ontario', 'Quebec'),
                'delivery_days' => 3,
                'delivery_cost' => 5.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'South Zone',
                'description' => 'Southern states with moderate connectivity',
                'type' => 'country',
                'countries' => array('US', 'MX'),
                'states' => array('California', 'Nevada', 'Arizona', 'New Mexico', 'Texas', 'Louisiana', 'Mississippi', 'Alabama', 'Florida', 'Georgia', 'South Carolina', 'North Carolina', 'Tennessee', 'Arkansas', 'Oklahoma'),
                'delivery_days' => 4,
                'delivery_cost' => 6.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'East Zone',
                'description' => 'Eastern states with excellent connectivity',
                'type' => 'country',
                'countries' => array('US'),
                'states' => array('Maine', 'New Hampshire', 'Vermont', 'Massachusetts', 'Rhode Island', 'Connecticut', 'New York', 'New Jersey', 'Pennsylvania', 'Delaware', 'Maryland', 'Virginia', 'West Virginia'),
                'delivery_days' => 2,
                'delivery_cost' => 4.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'West Zone',
                'description' => 'Western states with variable connectivity',
                'type' => 'country',
                'countries' => array('US'),
                'states' => array('California', 'Oregon', 'Washington', 'Nevada', 'Idaho', 'Montana', 'Wyoming', 'Utah', 'Colorado', 'Arizona', 'New Mexico'),
                'delivery_days' => 5,
                'delivery_cost' => 7.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'Central Zone',
                'description' => 'Central states with good connectivity',
                'type' => 'country',
                'countries' => array('US'),
                'states' => array('North Dakota', 'South Dakota', 'Nebraska', 'Kansas', 'Minnesota', 'Iowa', 'Missouri', 'Wisconsin', 'Illinois', 'Indiana', 'Michigan', 'Ohio', 'Kentucky', 'Tennessee'),
                'delivery_days' => 3,
                'delivery_cost' => 5.99,
                'is_active' => 1,
            ),
        );
    }

    /**
     * Install default zones
     *
     * @return bool True on success
     */
    public static function install_default_zones() {
        $default_zones = self::get_default_zones();
        
        foreach ($default_zones as $zone) {
            // Check if zone already exists
            $existing = self::get_zone_by_name($zone['name']);
            
            if (!$existing) {
                self::create_zone($zone);
            }
        }
        
        return true;
    }

    /**
     * Export zones data
     *
     * @return string JSON data
     */
    public static function export_zones() {
        $zones = self::get_zones();
        
        // Remove sensitive data and format for export
        $export_data = array();
        
        foreach ($zones as $zone) {
            $export_zone = array(
                'name' => $zone['name'],
                'description' => $zone['description'],
                'type' => $zone['type'],
                'countries' => json_decode($zone['countries'], true),
                'states' => json_decode($zone['states'], true),
                'cities' => json_decode($zone['cities'], true),
                'postal_codes' => json_decode($zone['postal_codes'], true),
                'delivery_days' => $zone['delivery_days'],
                'delivery_cost' => $zone['delivery_cost'],
                'is_active' => $zone['is_active'],
            );
            
            $export_data[] = $export_zone;
        }
        
        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }

    /**
     * Import zones data
     *
     * @param string $json_data JSON data
     * @return bool True on success
     */
    public static function import_zones($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        foreach ($data as $zone_data) {
            // Check if zone already exists
            $existing = self::get_zone_by_name($zone_data['name']);
            
            if ($existing) {
                // Update existing zone
                self::update_zone($existing['id'], $zone_data);
            } else {
                // Create new zone
                self::create_zone($zone_data);
            }
        }
        
        return true;
    }

    /**
     * Get zone coordinates
     *
     * @param int $zone_id Zone ID
     * @return array|false Coordinates or false if not found
     */
    public static function get_zone_coordinates($zone_id) {
        $zone = self::get_zone($zone_id);
        
        if (!$zone) {
            return false;
        }
        
        $coordinates = json_decode($zone['coordinates'], true);
        
        if (is_array($coordinates) && !empty($coordinates)) {
            return $coordinates;
        }
        
        // Generate approximate coordinates based on zone data
        return self::generate_zone_coordinates($zone);
    }

    /**
     * Generate approximate coordinates for zone
     *
     * @param array $zone Zone data
     * @return array Coordinates
     */
    private static function generate_zone_coordinates($zone) {
        // This is a simplified coordinate generation
        // In a real implementation, you would use a geocoding service
        
        $coordinates = array();
        
        $countries = json_decode($zone['countries'], true);
        $states = json_decode($zone['states'], true);
        $cities = json_decode($zone['cities'], true);
        
        // Generate coordinates based on location data
        if (is_array($cities) && !empty($cities)) {
            // Use first city as center
            $coordinates[] = array(
                'lat' => 40.7128, // Default to NYC
                'lng' => -74.0060,
                'name' => $cities[0],
            );
        } elseif (is_array($states) && !empty($states)) {
            // Use first state as center
            $coordinates[] = array(
                'lat' => 39.8283, // Default to US center
                'lng' => -98.5795,
                'name' => $states[0],
            );
        } elseif (is_array($countries) && !empty($countries)) {
            // Use first country as center
            $coordinates[] = array(
                'lat' => 39.8283, // Default to US center
                'lng' => -98.5795,
                'name' => $countries[0],
            );
        }
        
        return $coordinates;
    }
}