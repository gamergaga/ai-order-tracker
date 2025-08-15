<?php
/**
 * Security management class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Security
 */
class AIOT_Security {

    /**
     * Verify nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Action name
     * @return bool True if nonce is valid
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Create nonce
     *
     * @param string $action Action name
     * @return string Nonce value
     */
    public static function create_nonce($action) {
        return wp_create_nonce($action);
    }

    /**
     * Sanitize tracking ID
     *
     * @param string $tracking_id Tracking ID
     * @return string|false Sanitized tracking ID or false if invalid
     */
    public static function sanitize_tracking_id($tracking_id) {
        if (empty($tracking_id)) {
            return false;
        }

        $tracking_id = trim($tracking_id);
        
        // Remove any whitespace
        $tracking_id = preg_replace('/\s+/', '', $tracking_id);
        
        // Allow alphanumeric characters, hyphens, and underscores
        $tracking_id = preg_replace('/[^A-Za-z0-9\-_]/', '', $tracking_id);
        
        // Check minimum length
        if (strlen($tracking_id) < 5) {
            return false;
        }

        return $tracking_id;
    }

    /**
     * Sanitize email
     *
     * @param string $email Email address
     * @return string|false Sanitized email or false if invalid
     */
    public static function sanitize_email($email) {
        $email = sanitize_email($email);
        return is_email($email) ? $email : false;
    }

    /**
     * Sanitize phone number
     *
     * @param string $phone Phone number
     * @return string Sanitized phone number
     */
    public static function sanitize_phone($phone) {
        $phone = preg_replace('/[^\d\+\-\(\)\s]/', '', $phone);
        return trim($phone);
    }

    /**
     * Sanitize address
     *
     * @param string $address Address
     * @return string Sanitized address
     */
    public static function sanitize_address($address) {
        return sanitize_textarea_field($address);
    }

    /**
     * Sanitize URL
     *
     * @param string $url URL
     * @return string|false Sanitized URL or false if invalid
     */
    public static function sanitize_url($url) {
        return esc_url_raw($url);
    }

    /**
     * Validate user capabilities
     *
     * @param string $capability Required capability
     * @return bool True if user has capability
     */
    public static function user_can($capability) {
        return current_user_can($capability);
    }

    /**
     * Check if request is AJAX
     *
     * @return bool True if AJAX request
     */
    public static function is_ajax_request() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Check if request is REST API
     *
     * @return bool True if REST API request
     */
    public static function is_rest_request() {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Validate and sanitize array data
     *
     * @param array $data Data to sanitize
     * @param array $rules Sanitization rules
     * @return array Sanitized data
     */
    public static function sanitize_array($data, $rules) {
        $sanitized = array();
        
        foreach ($rules as $key => $rule) {
            if (isset($data[$key])) {
                switch ($rule) {
                    case 'text':
                        $sanitized[$key] = sanitize_text_field($data[$key]);
                        break;
                    case 'textarea':
                        $sanitized[$key] = sanitize_textarea_field($data[$key]);
                        break;
                    case 'email':
                        $sanitized[$key] = self::sanitize_email($data[$key]);
                        break;
                    case 'url':
                        $sanitized[$key] = self::sanitize_url($data[$key]);
                        break;
                    case 'phone':
                        $sanitized[$key] = self::sanitize_phone($data[$key]);
                        break;
                    case 'address':
                        $sanitized[$key] = self::sanitize_address($data[$key]);
                        break;
                    case 'tracking_id':
                        $sanitized[$key] = self::sanitize_tracking_id($data[$key]);
                        break;
                    case 'int':
                        $sanitized[$key] = intval($data[$key]);
                        break;
                    case 'float':
                        $sanitized[$key] = floatval($data[$key]);
                        break;
                    case 'bool':
                        $sanitized[$key] = (bool) $data[$key];
                        break;
                    case 'html':
                        $sanitized[$key] = wp_kses_post($data[$key]);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($data[$key]);
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Generate secure random string
     *
     * @param int $length String length
     * @return string Random string
     */
    public static function generate_random_string($length = 32) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        
        return $random_string;
    }

    /**
     * Hash sensitive data
     *
     * @param string $data Data to hash
     * @return string Hashed data
     */
    public static function hash_data($data) {
        return wp_hash($data);
    }

    /**
     * Verify hashed data
     *
     * @param string $data Original data
     * @param string $hash Hash to verify against
     * @return bool True if hash matches
     */
    public static function verify_hash($data, $hash) {
        return wp_hash($data) === $hash;
    }

    /**
     * Log security event
     *
     * @param string $event Event type
     * @param string $message Event message
     * @param array $context Additional context
     */
    public static function log_event($event, $message, $context = array()) {
        if (!get_option('aiot_debug_mode', false)) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        );

        error_log('AIOT Security: ' . json_encode($log_entry));
    }

    /**
     * Get client IP address
     *
     * @return string Client IP
     */
    public static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
            }
        }

        return '127.0.0.1';
    }

    /**
     * Validate API key
     *
     * @param string $api_key API key to validate
     * @return bool True if valid
     */
    public static function validate_api_key($api_key) {
        $stored_key = get_option('aiot_api_key', '');
        return hash_equals($stored_key, $api_key);
    }

    /**
     * Rate limit check
     *
     * @param string $action Action to check
     * @param int $limit Rate limit
     * @param int $window Time window in seconds
     * @return bool True if within limit
     */
    public static function rate_limit_check($action, $limit = 100, $window = 3600) {
        $transient_key = 'aiot_rate_limit_' . $action . '_' . self::get_client_ip();
        $current_count = get_transient($transient_key);

        if ($current_count === false) {
            set_transient($transient_key, 1, $window);
            return true;
        }

        if ($current_count >= $limit) {
            return false;
        }

        set_transient($transient_key, $current_count + 1, $window);
        return true;
    }

    /**
     * Sanitize JSON input
     *
     * @param string $json JSON string
     * @return array|false Decoded and sanitized array or false on failure
     */
    public static function sanitize_json_input($json) {
        $data = json_decode(stripslashes($json), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $data;
    }

    /**
     * Escape output for HTML
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public static function escape_html($text) {
        return esc_html($text);
    }

    /**
     * Escape output for HTML attributes
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public static function escape_attr($text) {
        return esc_attr($text);
    }

    /**
     * Escape output for JavaScript
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public static function escape_js($text) {
        return esc_js($text);
    }
}