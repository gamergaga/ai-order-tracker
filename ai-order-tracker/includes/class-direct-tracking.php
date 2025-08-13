<?php
/**
 * Direct Tracking class for AI Order Tracker
 * 
 * Handles real tracking by fetching data directly from courier websites
 * without requiring API keys or external services.
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Direct_Tracking
 */
class AIOT_Direct_Tracking {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_aiot_direct_tracking', array($this, 'ajax_direct_tracking'));
        add_action('wp_ajax_nopriv_aiot_direct_tracking', array($this, 'ajax_direct_tracking'));
    }
    
    /**
     * AJAX handler for direct tracking
     */
    public function ajax_direct_tracking() {
        // Verify nonce
        if (!check_ajax_referer('aiot_tracking_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ai-order-tracker')));
        }
        
        // Get and sanitize input
        $tracking_id = sanitize_text_field($_POST['tracking_id']);
        $courier_slug = sanitize_text_field($_POST['courier']);
        
        if (empty($tracking_id) || empty($courier_slug)) {
            wp_send_json_error(array('message' => __('Tracking ID and Courier are required.', 'ai-order-tracker')));
        }
        
        // Get tracking data
        $result = $this->track_package($tracking_id, $courier_slug);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Track package using direct web scraping
     *
     * @param string $tracking_id Tracking ID
     * @param string $courier_slug Courier slug
     * @return array|WP_Error Tracking data
     */
    public function track_package($tracking_id, $courier_slug) {
        // Get courier data
        $courier_data = aiot_get_courier_data_by_slug($courier_slug);
        
        if (!$courier_data) {
            return new WP_Error('courier_not_found', __('Courier not found.', 'ai-order-tracker'));
        }
        
        // Generate tracking URL
        $tracking_url = $this->generate_tracking_url($courier_data, $tracking_id);
        
        if (empty($tracking_url)) {
            return new WP_Error('invalid_url', __('Invalid tracking URL.', 'ai-order-tracker'));
        }
        
        // Fetch tracking page
        $page_content = $this->fetch_tracking_page($tracking_url);
        
        if (is_wp_error($page_content)) {
            return $page_content;
        }
        
        // Parse tracking data based on courier
        $tracking_data = $this->parse_tracking_data($page_content, $courier_slug);
        
        if (is_wp_error($tracking_data)) {
            return $tracking_data;
        }
        
        // Add additional information
        $tracking_data['tracking_id'] = $tracking_id;
        $tracking_data['courier'] = $courier_data['name'];
        $tracking_data['courier_slug'] = $courier_slug;
        $tracking_data['tracking_url'] = $tracking_url;
        $tracking_data['last_updated'] = current_time('mysql');
        
        return $tracking_data;
    }
    
    /**
     * Generate tracking URL
     *
     * @param array $courier_data Courier data
     * @param string $tracking_id Tracking ID
     * @return string Tracking URL
     */
    private function generate_tracking_url($courier_data, $tracking_id) {
        if (empty($courier_data['url_pattern'])) {
            return '';
        }
        
        // Replace tracking ID placeholder
        $url = str_replace('{tracking_id}', $tracking_id, $courier_data['url_pattern']);
        
        return esc_url($url);
    }
    
    /**
     * Fetch tracking page content
     *
     * @param string $url Tracking URL
     * @return string|WP_Error Page content
     */
    private function fetch_tracking_page($url) {
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            ),
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return new WP_Error('http_error', sprintf(__('HTTP error: %s', 'ai-order-tracker'), $status_code));
        }
        
        return $body;
    }
    
    /**
     * Parse tracking data from page content
     *
     * @param string $content Page content
     * @param string $courier_slug Courier slug
     * @return array|WP_Error Tracking data
     */
    private function parse_tracking_data($content, $courier_slug) {
        switch ($courier_slug) {
            case 'ups':
                return $this->parse_ups_data($content);
            case 'fedex':
                return $this->parse_fedex_data($content);
            case 'dhl':
                return $this->parse_dhl_data($content);
            case 'usps':
                return $this->parse_usps_data($content);
            case 'royal-mail':
                return $this->parse_royal_mail_data($content);
            case 'dpd':
                return $this->parse_dpd_data($content);
            case 'canada-post':
                return $this->parse_canada_post_data($content);
            case 'australia-post':
                return $this->parse_australia_post_data($content);
            default:
                return $this->parse_generic_data($content);
        }
    }
    
    /**
     * Parse UPS tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_ups_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<div class="status_text">([^<]+)<\/div>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<div class="location">([^<]+)<\/div>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="progress-info">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="est-delivery">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<div class="tracking-event">.*?<div class="date">([^<]+)<\/div>.*?<div class="time">([^<]+)<\/div>.*?<div class="location">([^<]+)<\/div>.*?<div class="description">([^<]+)<\/div>.*?<\/div>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse FedEx tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_fedex_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<span class="tracking-status">([^<]+)<\/span>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<span class="tracking-location">([^<]+)<\/span>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="tracking-description">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="estimated-delivery">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<tr class="tracking-event">.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<\/tr>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse DHL tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_dhl_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<div class="delivery-status">([^<]+)<\/div>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<div class="delivery-location">([^<]+)<\/div>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="delivery-description">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="estimated-delivery-date">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<div class="checkpoint">.*?<div class="date">([^<]+)<\/div>.*?<div class="time">([^<]+)<\/div>.*?<div class="location">([^<]+)<\/div>.*?<div class="description">([^<]+)<\/div>.*?<\/div>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse USPS tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_usps_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<span class="tracking-status">([^<]+)<\/span>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<span class="tracking-location">([^<]+)<\/span>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="tracking-summary">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="expected-delivery">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<tr class="tracking-history-row">.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<\/tr>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse Royal Mail tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_royal_mail_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<div class="tracking-status">([^<]+)<\/div>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<div class="tracking-location">([^<]+)<\/div>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="tracking-message">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="expected-date">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<div class="event">.*?<div class="date">([^<]+)<\/div>.*?<div class="time">([^<]+)<\/div>.*?<div class="location">([^<]+)<\/div>.*?<div class="status">([^<]+)<\/div>.*?<\/div>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse DPD tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_dpd_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<div class="parcel-status">([^<]+)<\/div>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<div class="parcel-location">([^<]+)<\/div>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="parcel-description">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="delivery-date">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<div class="tracking-event">.*?<div class="event-date">([^<]+)<\/div>.*?<div class="event-time">([^<]+)<\/div>.*?<div class="event-location">([^<]+)<\/div>.*?<div class="event-description">([^<]+)<\/div>.*?<\/div>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse Canada Post tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_canada_post_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<div class="tracking-status">([^<]+)<\/div>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<div class="tracking-location">([^<]+)<\/div>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="tracking-message">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="expected-delivery">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<div class="tracking-event">.*?<div class="event-date">([^<]+)<\/div>.*?<div class="event-time">([^<]+)<\/div>.*?<div class="event-location">([^<]+)<\/div>.*?<div class="event-description">([^<]+)<\/div>.*?<\/div>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse Australia Post tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_australia_post_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Look for status information
        if (preg_match('/<div class="tracking-status">([^<]+)<\/div>/i', $content, $matches)) {
            $data['status'] = $this->normalize_status(trim($matches[1]));
        }
        
        // Look for location
        if (preg_match('/<div class="tracking-location">([^<]+)<\/div>/i', $content, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // Look for description
        if (preg_match('/<div class="tracking-message">([^<]+)<\/div>/i', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }
        
        // Look for estimated delivery
        if (preg_match('/<div class="expected-delivery">([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Parse tracking events
        if (preg_match_all('/<div class="tracking-event">.*?<div class="event-date">([^<]+)<\/div>.*?<div class="event-time">([^<]+)<\/div>.*?<div class="event-location">([^<]+)<\/div>.*?<div class="event-description">([^<]+)<\/div>.*?<\/div>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['events'][] = array(
                    'date' => trim($match[1]),
                    'time' => trim($match[2]),
                    'location' => trim($match[3]),
                    'description' => trim($match[4]),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Parse generic tracking data
     *
     * @param string $content Page content
     * @return array Tracking data
     */
    private function parse_generic_data($content) {
        $data = array(
            'status' => 'unknown',
            'location' => '',
            'description' => '',
            'estimated_delivery' => '',
            'events' => array(),
        );
        
        // Generic patterns that might work for multiple couriers
        $patterns = array(
            'status' => array(
                '/<div[^>]*class="[^"]*status[^"]*"[^>]*>([^<]+)<\/div>/i',
                '/<span[^>]*class="[^"]*status[^"]*"[^>]*>([^<]+)<\/span>/i',
                '/<h[^>][^>]*>([^<]*status[^<]*)<\/h[^>]>/i',
            ),
            'location' => array(
                '/<div[^>]*class="[^"]*location[^"]*"[^>]*>([^<]+)<\/div>/i',
                '/<span[^>]*class="[^"]*location[^"]*"[^>]*>([^<]+)<\/span>/i',
            ),
            'description' => array(
                '/<div[^>]*class="[^"]*description[^"]*"[^>]*>([^<]+)<\/div>/i',
                '/<div[^>]*class="[^"]*message[^"]*"[^>]*>([^<]+)<\/div>/i',
                '/<p[^>]*class="[^"]*tracking[^"]*"[^>]*>([^<]+)<\/p>/i',
            ),
        );
        
        // Try each pattern
        foreach ($patterns['status'] as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $data['status'] = $this->normalize_status(trim($matches[1]));
                break;
            }
        }
        
        foreach ($patterns['location'] as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $data['location'] = trim($matches[1]);
                break;
            }
        }
        
        foreach ($patterns['description'] as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $data['description'] = trim($matches[1]);
                break;
            }
        }
        
        // Look for estimated delivery
        if (preg_match('/<div[^>]*class="[^"]*delivery[^"]*"[^>]*>([^<]+)<\/div>/i', $content, $matches)) {
            $data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Try to find tracking events in tables
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $rows)) {
            foreach ($rows[1] as $row) {
                if (preg_match_all('/<td[^>]*>([^<]*)<\/td>/i', $row, $cells)) {
                    if (count($cells[1]) >= 4) {
                        $data['events'][] = array(
                            'date' => trim($cells[1][0]),
                            'time' => trim($cells[1][1]),
                            'location' => trim($cells[1][2]),
                            'description' => trim($cells[1][3]),
                        );
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Normalize status to standard format
     *
     * @param string $status Raw status
     * @return string Normalized status
     */
    private function normalize_status($status) {
        $status = strtolower($status);
        
        // Map common status variations to standard ones
        $status_map = array(
            'delivered' => array('delivered', 'delivery confirmed', 'package delivered'),
            'out_for_delivery' => array('out for delivery', 'out for delivery', 'ofd', 'final delivery'),
            'in_transit' => array('in transit', 'transit', 'on the way', 'in route'),
            'shipped' => array('shipped', 'dispatched', 'picked up', 'collected'),
            'processing' => array('processing', 'order processed', 'label created'),
            'exception' => array('exception', 'delay', 'issue', 'problem'),
        );
        
        foreach ($status_map as $standard_status => $variations) {
            foreach ($variations as $variation) {
                if (strpos($status, $variation) !== false) {
                    return $standard_status;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get supported couriers
     *
     * @return array Supported couriers
     */
    public function get_supported_couriers() {
        $couriers = aiot_get_all_courier_data();
        $supported = array();
        
        foreach ($couriers as $courier) {
            if (!empty($courier['url_pattern'])) {
                $supported[] = array(
                    'slug' => $courier['slug'],
                    'name' => $courier['name'],
                    'display_name' => $courier['display_name'],
                    'country' => $courier['country'],
                );
            }
        }
        
        return $supported;
    }
    
    /**
     * Check if tracking is supported for a courier
     *
     * @param string $courier_slug Courier slug
     * @return bool Is supported
     */
    public function is_tracking_supported($courier_slug) {
        $courier_data = aiot_get_courier_data_by_slug($courier_slug);
        return $courier_data && !empty($courier_data['url_pattern']);
    }
}