<?php
/**
 * City Manager Class for AI Order Tracker
 *
 * This class handles city data management for the simulated tracking system.
 * It provides functionality to get cities by country/state and generate
 * realistic tracking progress through different city hubs.
 *
 * @package AI_Order_Tracker
 * @since 1.0.0
 */

class AIOT_City_Manager {

    /**
     * Instance of this class
     *
     * @var AIOT_City_Manager
     */
    private static $instance = null;

    /**
     * City data
     *
     * @var array
     */
    private $cities = array();

    /**
     * Get instance of this class
     *
     * @return AIOT_City_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_cities();
    }

    /**
     * Load city data from JSON file
     */
    private function load_cities() {
        $file_path = AIOT_PATH . 'assets/geo/cities.json';
        
        if (file_exists($file_path)) {
            $json_data = file_get_contents($file_path);
            $data = json_decode($json_data, true);
            
            if ($data && isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (isset($feature['properties']) && isset($feature['geometry'])) {
                        $city = $feature['properties'];
                        $city['coordinates'] = $feature['geometry']['coordinates'];
                        $this->cities[] = $city;
                    }
                }
            }
        }
    }

    /**
     * Get all cities
     *
     * @return array
     */
    public function get_all_cities() {
        return $this->cities;
    }

    /**
     * Get cities by country code
     *
     * @param string $country_code
     * @return array
     */
    public function get_cities_by_country($country_code) {
        return array_filter($this->cities, function($city) use ($country_code) {
            return isset($city['country_code']) && $city['country_code'] === $country_code;
        });
    }

    /**
     * Get cities by country and state
     *
     * @param string $country_code
     * @param string $state
     * @return array
     */
    public function get_cities_by_country_state($country_code, $state) {
        return array_filter($this->cities, function($city) use ($country_code, $state) {
            return isset($city['country_code']) && 
                   isset($city['state']) && 
                   $city['country_code'] === $country_code && 
                   stripos($city['state'], $state) !== false;
        });
    }

    /**
     * Get major cities (hubs) for admin operations
     *
     * @param string $country_code Optional country filter
     * @return array
     */
    public function get_major_cities($country_code = null) {
        $major_cities = array_filter($this->cities, function($city) use ($country_code) {
            $is_major = isset($city['importance']) && $city['importance'] === 'major';
            $country_match = $country_code === null || 
                            (isset($city['country_code']) && $city['country_code'] === $country_code);
            return $is_major && $country_match;
        });
        
        return array_values($major_cities);
    }

    /**
     * Get regional cities for customer locations
     *
     * @param string $country_code
     * @param string $state
     * @return array
     */
    public function get_regional_cities($country_code, $state) {
        $regional_cities = array_filter($this->cities, function($city) use ($country_code, $state) {
            $is_regional = isset($city['importance']) && $city['importance'] === 'regional';
            $country_match = isset($city['country_code']) && $city['country_code'] === $country_code;
            $state_match = isset($city['state']) && stripos($city['state'], $state) !== false;
            
            return ($is_regional || $country_match) && $state_match;
        });
        
        return array_values($regional_cities);
    }

    /**
     * Get random cities for simulation
     *
     * @param string $country_code
     * @param int $limit
     * @return array
     */
    public function get_random_cities($country_code = null, $limit = 5) {
        $cities = $country_code ? $this->get_cities_by_country($country_code) : $this->cities;
        shuffle($cities);
        return array_slice($cities, 0, $limit);
    }

    /**
     * Generate tracking route for simulated delivery
     *
     * @param string $from_country_code
     * @param string $from_state
     * @param string $to_country_code
     * @param string $to_state
     * @param int $waypoints Number of waypoints (default: 3-5)
     * @return array
     */
    public function generate_tracking_route($from_country_code, $from_state, $to_country_code, $to_state, $waypoints = null) {
        if ($waypoints === null) {
            $waypoints = rand(3, 5);
        }

        $route = array();
        
        // Get origin city
        $origin_cities = $this->get_cities_by_country_state($from_country_code, $from_state);
        if (empty($origin_cities)) {
            // Fallback to any city in the country
            $origin_cities = $this->get_cities_by_country($from_country_code);
        }
        if (!empty($origin_cities)) {
            $route[] = array_values($origin_cities)[0];
        }

        // Get major hub cities for transit
        $hub_cities = $this->get_major_cities();
        
        // Add waypoints (major hubs)
        for ($i = 0; $i < $waypoints && count($route) < $waypoints + 1; $i++) {
            if (!empty($hub_cities)) {
                $random_hub = $hub_cities[array_rand($hub_cities)];
                
                // Avoid duplicate cities
                $duplicate = false;
                foreach ($route as $city) {
                    if ($city['name'] === $random_hub['name']) {
                        $duplicate = true;
                        break;
                    }
                }
                
                if (!$duplicate) {
                    $route[] = $random_hub;
                }
            }
        }

        // Get destination city
        $dest_cities = $this->get_cities_by_country_state($to_country_code, $to_state);
        if (empty($dest_cities)) {
            // Fallback to any city in the country
            $dest_cities = $this->get_cities_by_country($to_country_code);
        }
        if (!empty($dest_cities)) {
            $dest_city = array_values($dest_cities)[0];
            
            // Avoid duplicate destination
            $duplicate = false;
            foreach ($route as $city) {
                if ($city['name'] === $dest_city['name']) {
                    $duplicate = true;
                    break;
                }
            }
            
            if (!$duplicate) {
                $route[] = $dest_city;
            }
        }

        return $route;
    }

    /**
     * Generate simulated tracking progress
     *
     * @param array $route Cities in the route
     * @param int $total_steps Total tracking steps
     * @return array
     */
    public function generate_tracking_progress($route, $total_steps = 10) {
        $progress = array();
        $cities_count = count($route);
        
        if ($cities_count < 2) {
            return $progress;
        }

        for ($i = 0; $i < $total_steps; $i++) {
            $progress_ratio = $i / ($total_steps - 1);
            $city_index = min(floor($progress_ratio * ($cities_count - 1)), $cities_count - 2);
            
            $current_city = $route[$city_index];
            $next_city = $route[$city_index + 1];
            
            // Calculate intermediate position
            $inter_progress = ($progress_ratio * ($cities_count - 1)) - $city_index;
            
            $lat = $current_city['coordinates'][1] + 
                   ($next_city['coordinates'][1] - $current_city['coordinates'][1]) * $inter_progress;
            $lng = $current_city['coordinates'][0] + 
                   ($next_city['coordinates'][0] - $current_city['coordinates'][0]) * $inter_progress;
            
            $status = $this->get_tracking_status($i, $total_steps);
            
            $progress[] = array(
                'step' => $i + 1,
                'status' => $status,
                'location' => array(
                    'city' => $current_city['name'],
                    'state' => $current_city['state'],
                    'country' => $current_city['country'],
                    'country_code' => $current_city['country_code'],
                    'coordinates' => array($lng, $lat)
                ),
                'timestamp' => time() + ($i * 3600), // Simulated timestamps
                'message' => $this->get_tracking_message($status, $current_city, $next_city)
            );
        }

        return $progress;
    }

    /**
     * Get tracking status based on progress
     *
     * @param int $current_step
     * @param int $total_steps
     * @return string
     */
    private function get_tracking_status($current_step, $total_steps) {
        $progress_ratio = $current_step / ($total_steps - 1);
        
        if ($progress_ratio < 0.1) {
            return 'order_confirmed';
        } elseif ($progress_ratio < 0.2) {
            return 'order_packed';
        } elseif ($progress_ratio < 0.4) {
            return 'in_transit';
        } elseif ($progress_ratio < 0.6) {
            return 'arrived_hub';
        } elseif ($progress_ratio < 0.8) {
            return 'out_for_delivery';
        } elseif ($progress_ratio < 0.95) {
            return 'near_delivery';
        } else {
            return 'delivered';
        }
    }

    /**
     * Get tracking message based on status
     *
     * @param string $status
     * @param array $current_city
     * @param array $next_city
     * @return string
     */
    private function get_tracking_message($status, $current_city, $next_city) {
        $messages = array(
            'order_confirmed' => sprintf(
                __('Order confirmed and processing at %s hub', 'ai-order-tracker'),
                $current_city['name']
            ),
            'order_packed' => sprintf(
                __('Order packed and ready for shipment from %s', 'ai-order-tracker'),
                $current_city['name']
            ),
            'in_transit' => sprintf(
                __('Package in transit from %s to %s', 'ai-order-tracker'),
                $current_city['name'],
                $next_city['name']
            ),
            'arrived_hub' => sprintf(
                __('Package arrived at %s distribution hub', 'ai-order-tracker'),
                $current_city['name']
            ),
            'out_for_delivery' => sprintf(
                __('Package out for delivery in %s', 'ai-order-tracker'),
                $current_city['state']
            ),
            'near_delivery' => sprintf(
                __('Package near delivery location in %s', 'ai-order-tracker'),
                $current_city['city']
            ),
            'delivered' => sprintf(
                __('Package delivered successfully in %s', 'ai-order-tracker'),
                $current_city['name']
            )
        );

        return isset($messages[$status]) ? $messages[$status] : __('Package in transit', 'ai-order-tracker');
    }

    /**
     * Get city by name
     *
     * @param string $name
     * @return array|null
     */
    public function get_city_by_name($name) {
        foreach ($this->cities as $city) {
            if (isset($city['name']) && stripos($city['name'], $name) !== false) {
                return $city;
            }
        }
        return null;
    }

    /**
     * Search cities by name
     *
     * @param string $query
     * @return array
     */
    public function search_cities($query) {
        return array_filter($this->cities, function($city) use ($query) {
            return isset($city['name']) && stripos($city['name'], $query) !== false;
        });
    }

    /**
     * Get city statistics
     *
     * @return array
     */
    public function get_city_statistics() {
        $stats = array(
            'total_cities' => count($this->cities),
            'countries' => array(),
            'major_cities' => 0,
            'regional_cities' => 0
        );

        foreach ($this->cities as $city) {
            // Count by country
            if (isset($city['country_code'])) {
                if (!isset($stats['countries'][$city['country_code']])) {
                    $stats['countries'][$city['country_code']] = array(
                        'name' => $city['country'],
                        'cities' => 0
                    );
                }
                $stats['countries'][$city['country_code']]['cities']++;
            }

            // Count by importance
            if (isset($city['importance'])) {
                if ($city['importance'] === 'major') {
                    $stats['major_cities']++;
                } elseif ($city['importance'] === 'regional') {
                    $stats['regional_cities']++;
                }
            }
        }

        return $stats;
    }
}