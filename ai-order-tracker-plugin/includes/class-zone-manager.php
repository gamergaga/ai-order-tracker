<?php
/**
 * Simplified Zone Manager class for AI Order Tracker
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
            'delivery_days' => 3,
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
        
        // Handle delivery_days - it could be an integer or a JSON object
        if (isset($data['delivery_days'])) {
            if (is_array($data['delivery_days'])) {
                $data['delivery_days'] = wp_json_encode($data['delivery_days']);
            } elseif (is_numeric($data['delivery_days'])) {
                // Convert to JSON object for consistency
                $data['delivery_days'] = wp_json_encode(array('min' => intval($data['delivery_days']), 'max' => intval($data['delivery_days'])));
            }
        }
        
        $data['delivery_cost'] = floatval($data['delivery_cost']);
        $data['is_active'] = intval($data['is_active']);
        
        // Serialize array fields
        $array_fields = array('countries', 'states', 'cities', 'coordinates', 'meta');
        
        foreach ($array_fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = wp_json_encode($data[$field]);
            }
        }
        
        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%s')  // Updated format array
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
        
        // Handle delivery_days - it could be an integer or a JSON object
        if (isset($data['delivery_days'])) {
            if (is_array($data['delivery_days'])) {
                $data['delivery_days'] = wp_json_encode($data['delivery_days']);
            } elseif (is_numeric($data['delivery_days'])) {
                // Convert to JSON object for consistency
                $data['delivery_days'] = wp_json_encode(array('min' => intval($data['delivery_days']), 'max' => intval($data['delivery_days'])));
            }
        }
        
        $data['delivery_cost'] = floatval($data['delivery_cost']);
        $data['is_active'] = intval($data['is_active']);
        
        // Serialize array fields
        $array_fields = array('countries', 'states', 'cities', 'coordinates', 'meta');
        
        foreach ($array_fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = wp_json_encode($data[$field]);
            }
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $zone_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%s'),  // Updated format array
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
     * Get zone for address (simplified)
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
     * Check if address matches zone (simplified)
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
                // Transform data to expected format
                $countries = array();
                foreach ($data as $country) {
                    $countries[] = array(
                        'code' => $country['cca2'],
                        'name' => $country['name']['common'],
                        'latlng' => $country['latlng']
                    );
                }
                return $countries;
            }
        }
        
        return array();
    }

    /**
     * Get states for country (simplified version)
     *
     * @param string $country Country code
     * @return array States data
     */
    public static function get_states_for_country($country) {
        return self::get_states_data($country);
    }

    /**
     * Get states data for country
     *
     * @param string $country Country code
     * @return array States data
     */
    public static function get_states_data($country) {
        $file_path = AIOT_PATH . 'assets/geo/states-world.geojson';
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['features'])) {
                $states = array();
                foreach ($data['features'] as $feature) {
                    if (isset($feature['properties']['shapeGroup']) && isset($feature['properties']['name'])) {
                        // Convert 2-letter country code to 3-letter for matching with shapeGroup
                        $country_code_3 = self::convert_country_code($country);
                        if ($feature['properties']['shapeGroup'] === $country_code_3) {
                            $states[] = array(
                                'name' => $feature['properties']['name'],
                                'coordinates' => $feature['geometry']['coordinates']
                            );
                        }
                    }
                }
                return $states;
            }
        }
        
        return array();
    }
    
    /**
     * Convert 2-letter country code to 3-letter
     *
     * @param string $code2 2-letter country code
     * @return string 3-letter country code
     */
    private static function convert_country_code($code2) {
        $mapping = array(
            'US' => 'USA',
            'AF' => 'AFG',
            'AL' => 'ALB',
            'DZ' => 'DZA',
            'AS' => 'ASM',
            'AD' => 'AND',
            'AO' => 'AGO',
            'AI' => 'AIA',
            'AQ' => 'ATA',
            'AG' => 'ATG',
            'AR' => 'ARG',
            'AM' => 'ARM',
            'AW' => 'ABW',
            'AU' => 'AUS',
            'AT' => 'AUT',
            'AZ' => 'AZE',
            'BS' => 'BHS',
            'BH' => 'BHR',
            'BD' => 'BGD',
            'BB' => 'BRB',
            'BY' => 'BLR',
            'BE' => 'BEL',
            'BZ' => 'BLZ',
            'BJ' => 'BEN',
            'BM' => 'BMU',
            'BT' => 'BTN',
            'BO' => 'BOL',
            'BQ' => 'BES',
            'BA' => 'BIH',
            'BW' => 'BWA',
            'BR' => 'BRA',
            'IO' => 'IOT',
            'BN' => 'BRN',
            'BG' => 'BGR',
            'BF' => 'BFA',
            'BI' => 'BDI',
            'CV' => 'CPV',
            'KH' => 'KHM',
            'CM' => 'CMR',
            'CA' => 'CAN',
            'KY' => 'CYM',
            'CF' => 'CAF',
            'TD' => 'TCD',
            'CL' => 'CHL',
            'CN' => 'CHN',
            'CX' => 'CXR',
            'CC' => 'CCK',
            'CO' => 'COL',
            'KM' => 'COM',
            'CG' => 'COG',
            'CD' => 'COD',
            'CK' => 'COK',
            'CR' => 'CRI',
            'CI' => 'CIV',
            'HR' => 'HRV',
            'CU' => 'CUB',
            'CW' => 'CUW',
            'CY' => 'CYP',
            'CZ' => 'CZE',
            'DK' => 'DNK',
            'DJ' => 'DJI',
            'DM' => 'DMA',
            'DO' => 'DOM',
            'EC' => 'ECU',
            'EG' => 'EGY',
            'SV' => 'SLV',
            'GQ' => 'GNQ',
            'ER' => 'ERI',
            'EE' => 'EST',
            'SZ' => 'SWZ',
            'ET' => 'ETH',
            'FK' => 'FLK',
            'FO' => 'FRO',
            'FJ' => 'FJI',
            'FI' => 'FIN',
            'FR' => 'FRA',
            'GF' => 'GUF',
            'PF' => 'PYF',
            'TF' => 'ATF',
            'GA' => 'GAB',
            'GM' => 'GMB',
            'GE' => 'GEO',
            'DE' => 'DEU',
            'GH' => 'GHA',
            'GI' => 'GIB',
            'GR' => 'GRC',
            'GL' => 'GRL',
            'GD' => 'GRD',
            'GP' => 'GLP',
            'GU' => 'GUM',
            'GT' => 'GTM',
            'GG' => 'GGY',
            'GN' => 'GIN',
            'GW' => 'GNB',
            'GY' => 'GUY',
            'HT' => 'HTI',
            'HM' => 'HMD',
            'VA' => 'VAT',
            'HN' => 'HND',
            'HK' => 'HKG',
            'HU' => 'HUN',
            'IS' => 'ISL',
            'IN' => 'IND',
            'ID' => 'IDN',
            'IR' => 'IRN',
            'IQ' => 'IRQ',
            'IE' => 'IRL',
            'IM' => 'IMN',
            'IL' => 'ISR',
            'IT' => 'ITA',
            'JM' => 'JAM',
            'JP' => 'JPN',
            'JE' => 'JEY',
            'JO' => 'JOR',
            'KZ' => 'KAZ',
            'KE' => 'KEN',
            'KI' => 'KIR',
            'KP' => 'PRK',
            'KR' => 'KOR',
            'KW' => 'KWT',
            'KG' => 'KGZ',
            'LA' => 'LAO',
            'LV' => 'LVA',
            'LB' => 'LBN',
            'LS' => 'LSO',
            'LR' => 'LBR',
            'LY' => 'LBY',
            'LI' => 'LIE',
            'LT' => 'LTU',
            'LU' => 'LUX',
            'MO' => 'MAC',
            'MK' => 'MKD',
            'MG' => 'MDG',
            'MW' => 'MWI',
            'MY' => 'MYS',
            'MV' => 'MDV',
            'ML' => 'MLI',
            'MT' => 'MLT',
            'MH' => 'MHL',
            'MQ' => 'MTQ',
            'MR' => 'MRT',
            'MU' => 'MUS',
            'YT' => 'MYT',
            'MX' => 'MEX',
            'FM' => 'FSM',
            'MD' => 'MDA',
            'MC' => 'MCO',
            'MN' => 'MNG',
            'ME' => 'MNE',
            'MS' => 'MSR',
            'MA' => 'MAR',
            'MZ' => 'MOZ',
            'MM' => 'MMR',
            'NA' => 'NAM',
            'NR' => 'NRU',
            'NP' => 'NPL',
            'NL' => 'NLD',
            'NC' => 'NCL',
            'NZ' => 'NZL',
            'NI' => 'NIC',
            'NE' => 'NER',
            'NG' => 'NGA',
            'NU' => 'NIU',
            'NF' => 'NFK',
            'MP' => 'MNP',
            'NO' => 'NOR',
            'OM' => 'OMN',
            'PK' => 'PAK',
            'PW' => 'PLW',
            'PS' => 'PSE',
            'PA' => 'PAN',
            'PG' => 'PNG',
            'PY' => 'PRY',
            'PE' => 'PER',
            'PH' => 'PHL',
            'PN' => 'PCN',
            'PL' => 'POL',
            'PT' => 'PRT',
            'PR' => 'PRI',
            'QA' => 'QAT',
            'RE' => 'REU',
            'RO' => 'ROU',
            'RU' => 'RUS',
            'RW' => 'RWA',
            'BL' => 'BLM',
            'SH' => 'SHN',
            'KN' => 'KNA',
            'LC' => 'LCA',
            'MF' => 'MAF',
            'PM' => 'SPM',
            'VC' => 'VCT',
            'WS' => 'WSM',
            'SM' => 'SMR',
            'ST' => 'STP',
            'SA' => 'SAU',
            'SN' => 'SEN',
            'RS' => 'SRB',
            'SC' => 'SYC',
            'SL' => 'SLE',
            'SG' => 'SGP',
            'SX' => 'SXM',
            'SK' => 'SVK',
            'SI' => 'SVN',
            'SB' => 'SLB',
            'SO' => 'SOM',
            'ZA' => 'ZAF',
            'GS' => 'SGS',
            'SS' => 'SSD',
            'ES' => 'ESP',
            'LK' => 'LKA',
            'SD' => 'SDN',
            'SR' => 'SUR',
            'SJ' => 'SJM',
            'SE' => 'SWE',
            'CH' => 'CHE',
            'SY' => 'SYR',
            'TW' => 'TWN',
            'TJ' => 'TJK',
            'TZ' => 'TZA',
            'TH' => 'THA',
            'TL' => 'TLS',
            'TG' => 'TGO',
            'TK' => 'TKL',
            'TO' => 'TON',
            'TT' => 'TTO',
            'TN' => 'TUN',
            'TR' => 'TUR',
            'TM' => 'TKM',
            'TC' => 'TCA',
            'TV' => 'TUV',
            'UG' => 'UGA',
            'UA' => 'UKR',
            'AE' => 'ARE',
            'GB' => 'GBR',
            'UM' => 'UMI',
            'US' => 'USA',
            'UY' => 'URY',
            'UZ' => 'UZB',
            'VU' => 'VUT',
            'VE' => 'VEN',
            'VN' => 'VNM',
            'VG' => 'VGB',
            'VI' => 'VIR',
            'WF' => 'WLF',
            'EH' => 'ESH',
            'YE' => 'YEM',
            'ZM' => 'ZMB',
            'ZW' => 'ZWE'
        );
        
        return isset($mapping[$code2]) ? $mapping[$code2] : $code2;
    }

    /**
     * Get major cities for state
     *
     * @param string $country Country code
     * @param string $state State name
     * @return array Cities data
     */
    public static function get_major_cities($country, $state) {
        $file_path = AIOT_PATH . 'assets/geo/states-world.geojson';
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (isset($feature['properties']['admin']) && 
                        isset($feature['properties']['iso_a2']) && 
                        isset($feature['properties']['cities'])) {
                        
                        if ($feature['properties']['iso_a2'] === $country && 
                            $feature['properties']['admin'] === $state) {
                            return $feature['properties']['cities'];
                        }
                    }
                }
            }
        }
        
        // Fallback to simplified major cities data
        $major_cities = array(
            'US' => array(
                'California' => array('Los Angeles', 'San Francisco', 'San Diego', 'San Jose', 'Sacramento'),
                'Texas' => array('Houston', 'San Antonio', 'Dallas', 'Austin', 'Fort Worth'),
                'Florida' => array('Miami', 'Orlando', 'Tampa', 'Jacksonville', 'St. Petersburg'),
                'New York' => array('New York City', 'Buffalo', 'Rochester', 'Albany', 'Syracuse'),
                'Illinois' => array('Chicago', 'Aurora', 'Rockford', 'Naperville', 'Joliet'),
            ),
            'CA' => array(
                'Ontario' => array('Toronto', 'Ottawa', 'Hamilton', 'Kitchener', 'London'),
                'Quebec' => array('Montreal', 'Quebec City', 'Laval', 'Gatineau', 'Longueuil'),
                'British Columbia' => array('Vancouver', 'Victoria', 'Surrey', 'Burnaby', 'Richmond'),
            ),
            'GB' => array(
                'England' => array('London', 'Manchester', 'Birmingham', 'Liverpool', 'Leeds'),
                'Scotland' => array('Glasgow', 'Edinburgh', 'Aberdeen', 'Dundee', 'Inverness'),
                'Wales' => array('Cardiff', 'Swansea', 'Newport', 'Wrexham', 'Barry'),
            ),
            'AU' => array(
                'New South Wales' => array('Sydney', 'Newcastle', 'Wollongong', 'Central Coast', 'Maitland'),
                'Victoria' => array('Melbourne', 'Geelong', 'Ballarat', 'Bendigo', 'Melton'),
                'Queensland' => array('Brisbane', 'Gold Coast', 'Sunshine Coast', 'Cairns', 'Townsville'),
            ),
        );
        
        return isset($major_cities[$country][$state]) ? $major_cities[$country][$state] : array();
    }

    /**
     * Get coordinates for location
     *
     * @param string $country Country code
     * @param string $state State name
     * @param array $cities Cities array
     * @return array Coordinates
     */
    public static function get_coordinates_for_location($country, $state, $cities = array()) {
        $coordinates = array();
        
        // If no location selected, return empty array
        if (empty($country) && empty($state)) {
            return $coordinates;
        }
        
        // Get country center coordinates from countries.json
        if (!empty($country) && empty($state)) {
            $countries = self::get_countries_data();
            foreach ($countries as $c) {
                if ($c['code'] === $country && isset($c['latlng'])) {
                    $coordinates[] = $c['latlng'];
                    break;
                }
            }
            return $coordinates;
        }
        
        // Get state coordinates from states-world.geojson
        if (!empty($state)) {
            $file_path = AIOT_PATH . 'assets/geo/states-world.geojson';
            
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $data = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['features'])) {
                    $country_code_3 = self::convert_country_code($country);
                    
                    foreach ($data['features'] as $feature) {
                        if (isset($feature['properties']['shapeGroup']) && 
                            isset($feature['properties']['name']) && 
                            isset($feature['geometry']['coordinates'])) {
                            
                            // Match by country and state
                            if ($feature['properties']['shapeGroup'] === $country_code_3 && 
                                $feature['properties']['name'] === $state) {
                                
                                // Get center point from polygon coordinates
                                $coords = $feature['geometry']['coordinates'];
                                
                                // Calculate center of polygon (simplified approach)
                                if (isset($coords[0][0])) {
                                    $lat_sum = 0;
                                    $lng_sum = 0;
                                    $count = 0;
                                    
                                    // Handle different coordinate formats
                                    if (is_array($coords[0][0][0])) {
                                        // Multi-polygon
                                        foreach ($coords[0] as $polygon) {
                                            foreach ($polygon as $point) {
                                                $lat_sum += $point[1];
                                                $lng_sum += $point[0];
                                                $count++;
                                            }
                                        }
                                    } else {
                                        // Single polygon
                                        foreach ($coords[0] as $point) {
                                            $lat_sum += $point[1];
                                            $lng_sum += $point[0];
                                            $count++;
                                        }
                                    }
                                    
                                    if ($count > 0) {
                                        $coordinates[] = array($lat_sum / $count, $lng_sum / $count);
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        return $coordinates;
    }

    /**
     * Get default zones
     *
     * @return array Default zones
     */
    public static function get_default_zones() {
        return array(
            array(
                'name' => 'North America',
                'description' => 'United States and Canada',
                'type' => 'country',
                'countries' => array('US', 'CA'),
                'states' => array(),
                'cities' => array(),
                'delivery_days' => 3,
                'delivery_cost' => 5.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'Europe',
                'description' => 'European Union and United Kingdom',
                'type' => 'country',
                'countries' => array('GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'SE', 'DK', 'FI', 'IE', 'PT', 'GR', 'PL', 'CZ', 'HU', 'RO', 'BG', 'HR', 'SI', 'SK', 'LT', 'LV', 'EE', 'MT', 'CY', 'LU'),
                'states' => array(),
                'cities' => array(),
                'delivery_days' => 5,
                'delivery_cost' => 8.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'Asia Pacific',
                'description' => 'Asia and Pacific regions',
                'type' => 'country',
                'countries' => array('AU', 'NZ', 'JP', 'KR', 'CN', 'SG', 'MY', 'TH', 'PH', 'ID', 'VN', 'IN', 'PK', 'BD', 'LK', 'NP', 'MM', 'KH', 'BN', 'LA', 'MN'),
                'states' => array(),
                'cities' => array(),
                'delivery_days' => 7,
                'delivery_cost' => 12.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'Latin America',
                'description' => 'Central and South America',
                'type' => 'country',
                'countries' => array('MX', 'BR', 'AR', 'CL', 'PE', 'CO', 'VE', 'EC', 'BO', 'PY', 'UY', 'GY', 'SR'),
                'states' => array(),
                'cities' => array(),
                'delivery_days' => 8,
                'delivery_cost' => 15.99,
                'is_active' => 1,
            ),
            array(
                'name' => 'Middle East & Africa',
                'description' => 'Middle Eastern and African countries',
                'type' => 'country',
                'countries' => array('AE', 'SA', 'QA', 'KW', 'BH', 'OM', 'YE', 'EG', 'ZA', 'NG', 'KE', 'GH', 'ET', 'TZ', 'UG', 'ZW', 'ZM', 'MW', 'MZ', 'AO', 'BW', 'NA'),
                'states' => array(),
                'cities' => array(),
                'delivery_days' => 10,
                'delivery_cost' => 18.99,
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
     * Get cities data
     *
     * @return array Cities data
     */
    public static function get_cities_data() {
        $file_path = AIOT_PATH . 'assets/geo/cities.json';
        
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
     * @return array|WP_Error Import result
     */
    public static function import_zones($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON data.', 'ai-order-tracker'));
        }
        
        if (!is_array($data)) {
            return new WP_Error('invalid_format', __('Invalid data format.', 'ai-order-tracker'));
        }
        
        $imported = 0;
        $errors = array();
        
        foreach ($data as $zone_data) {
            // Validate required fields
            if (empty($zone_data['name']) || empty($zone_data['delivery_days'])) {
                $errors[] = sprintf(__('Missing required fields for zone: %s', 'ai-order-tracker'), $zone_data['name']);
                continue;
            }
            
            // Check if zone already exists
            $existing = self::get_zone_by_name($zone_data['name']);
            
            if ($existing) {
                // Update existing zone
                $result = self::update_zone($existing['id'], $zone_data);
            } else {
                // Create new zone
                $result = self::create_zone($zone_data);
            }
            
            if ($result) {
                $imported++;
            } else {
                $errors[] = sprintf(__('Failed to import zone: %s', 'ai-order-tracker'), $zone_data['name']);
            }
        }
        
        return array(
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($data),
        );
    }

    /**
     * Get zone coordinates for map
     *
     * @param int $zone_id Zone ID
     * @return array Coordinates data
     */
    public static function get_zone_coordinates($zone_id) {
        $zone = self::get_zone($zone_id);
        
        if (!$zone) {
            return array();
        }
        
        $coordinates = json_decode($zone['coordinates'], true);
        
        if (empty($coordinates)) {
            // Generate default coordinates based on countries/states
            $countries = json_decode($zone['countries'], true);
            $states = json_decode($zone['states'], true);
            
            if (!empty($countries)) {
                $coordinates = self::get_country_coordinates($countries[0]);
            } elseif (!empty($states)) {
                $coordinates = self::get_state_coordinates($states[0]);
            }
        }
        
        return $coordinates;
    }

    /**
     * Get country coordinates
     *
     * @param string $country Country code
     * @return array Coordinates
     */
    private static function get_country_coordinates($country) {
        $country_coords = array(
            'US' => array(39.8283, -98.5795),
            'CA' => array(56.1304, -106.3468),
            'GB' => array(55.3781, -3.4360),
            'DE' => array(51.1657, 10.4515),
            'FR' => array(46.2276, 2.2137),
            'IT' => array(41.8719, 12.5674),
            'ES' => array(40.4637, -3.7492),
            'AU' => array(-25.2744, 133.7751),
            'JP' => array(36.2048, 138.2529),
            'CN' => array(35.8617, 104.1954),
            'IN' => array(20.5937, 78.9629),
            'BR' => array(-14.2350, -51.9253),
            'MX' => array(23.6345, -102.5528),
            'RU' => array(61.5240, 105.3188),
            'ZA' => array(-30.5595, 22.9375),
            'EG' => array(26.8206, 30.8025),
            'AE' => array(23.4241, 53.8478),
            'SA' => array(23.8859, 45.0792),
        );
        
        return isset($country_coords[$country]) ? $country_coords[$country] : array(0, 0);
    }

    /**
     * Get state coordinates
     *
     * @param string $state State name
     * @return array Coordinates
     */
    private static function get_state_coordinates($state) {
        $state_coords = array(
            'California' => array(36.7783, -119.4179),
            'Texas' => array(31.9686, -99.9018),
            'Florida' => array(27.7663, -82.6403),
            'New York' => array(43.2994, -74.2179),
            'Ontario' => array(51.2538, -85.3232),
            'Quebec' => array(52.9399, -73.5491),
            'England' => array(52.3555, -1.1743),
            'Scotland' => array(56.4907, -4.2026),
        );
        
        return isset($state_coords[$state]) ? $state_coords[$state] : array(0, 0);
    }
}