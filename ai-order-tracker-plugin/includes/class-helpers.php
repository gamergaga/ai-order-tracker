<?php
/**
 * Helper functions class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Helpers
 */
class AIOT_Helpers {

    /**
     * Generate unique tracking ID
     *
     * @param string $prefix Prefix for tracking ID
     * @return string Unique tracking ID
     */
    public static function generate_tracking_id($prefix = 'AIOT') {
        $random = mt_rand(10000, 99999);
        $timestamp = time();
        $hash = substr(md5($timestamp . $random), 0, 5);
        return strtoupper($prefix . '-' . $random . $hash);
    }

    /**
     * Format date for display
     *
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function format_date($date, $format = 'F j, Y') {
        if (empty($date)) {
            return '';
        }

        $timestamp = strtotime($date);
        
        if ($timestamp === false) {
            return $date;
        }

        return date($format, $timestamp);
    }

    /**
     * Format date time for display
     *
     * @param string $date_time Date time string
     * @param string $format Date format
     * @return string Formatted date time
     */
    public static function format_date_time($date_time, $format = 'F j, Y g:i A') {
        if (empty($date_time)) {
            return '';
        }

        $timestamp = strtotime($date_time);
        
        if ($timestamp === false) {
            return $date_time;
        }

        return date($format, $timestamp);
    }

    /**
     * Format currency
     *
     * @param float $amount Amount
     * @param string $currency Currency code
     * @return string Formatted currency
     */
    public static function format_currency($amount, $currency = 'USD') {
        $amount = floatval($amount);
        
        $symbol = '$';
        switch ($currency) {
            case 'EUR':
                $symbol = '€';
                break;
            case 'GBP':
                $symbol = '£';
                break;
            case 'JPY':
                $symbol = '¥';
                break;
            case 'CAD':
                $symbol = 'C$';
                break;
            case 'AUD':
                $symbol = 'A$';
                break;
        }
        
        return $symbol . number_format($amount, 2);
    }

    /**
     * Format weight
     *
     * @param float $weight Weight
     * @param string $unit Weight unit
     * @return string Formatted weight
     */
    public static function format_weight($weight, $unit = 'kg') {
        $weight = floatval($weight);
        
        if ($weight < 1 && $unit === 'kg') {
            return round($weight * 1000) . ' g';
        }
        
        return round($weight, 2) . ' ' . $unit;
    }

    /**
     * Format dimensions
     *
     * @param array $dimensions Dimensions array (length, width, height)
     * @param string $unit Unit
     * @return string Formatted dimensions
     */
    public static function format_dimensions($dimensions, $unit = 'cm') {
        if (!is_array($dimensions) || count($dimensions) < 3) {
            return '';
        }

        $length = floatval($dimensions[0]);
        $width = floatval($dimensions[1]);
        $height = floatval($dimensions[2]);

        return $length . ' × ' . $width . ' × ' . $height . ' ' . $unit;
    }

    /**
     * Format phone number
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    public static function format_phone($phone) {
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        } elseif (strlen($phone) === 11) {
            return '+' . substr($phone, 0, 1) . ' (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7);
        }
        
        return $phone;
    }

    /**
     * Format address
     *
     * @param array $address Address components
     * @return string Formatted address
     */
    public static function format_address($address) {
        if (!is_array($address)) {
            return $address;
        }

        $parts = array();
        
        if (!empty($address['address1'])) {
            $parts[] = $address['address1'];
        }
        
        if (!empty($address['address2'])) {
            $parts[] = $address['address2'];
        }
        
        $city_line = array();
        if (!empty($address['city'])) {
            $city_line[] = $address['city'];
        }
        
        if (!empty($address['state'])) {
            $city_line[] = $address['state'];
        }
        
        if (!empty($address['postal_code'])) {
            $city_line[] = $address['postal_code'];
        }
        
        if (!empty($city_line)) {
            $parts[] = implode(', ', $city_line);
        }
        
        if (!empty($address['country'])) {
            $parts[] = $address['country'];
        }
        
        return implode("\n", $parts);
    }

    /**
     * Calculate distance between two coordinates
     *
     * @param float $lat1 Latitude 1
     * @param float $lng1 Longitude 1
     * @param float $lat2 Latitude 2
     * @param float $lng2 Longitude 2
     * @param string $unit Unit (km, mi)
     * @return float Distance
     */
    public static function calculate_distance($lat1, $lng1, $lat2, $lng2, $unit = 'km') {
        $earth_radius = ($unit === 'mi') ? 3959 : 6371;
        
        $lat_diff = deg2rad($lat2 - $lat1);
        $lng_diff = deg2rad($lng2 - $lng1);
        
        $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lng_diff / 2) * sin($lng_diff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
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
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Get user agent
     *
     * @return string User agent
     */
    public static function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * Get browser info
     *
     * @return array Browser information
     */
    public static function get_browser_info() {
        $user_agent = self::get_user_agent();
        
        $browser = 'Unknown';
        $version = '';
        
        if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
            $browser = 'Internet Explorer';
            if (preg_match('/MSIE ([0-9]+\.[0-9]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
            if (preg_match('/Firefox\/([0-9]+\.[0-9]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $browser = 'Chrome';
            if (preg_match('/Chrome\/([0-9]+\.[0-9]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $browser = 'Safari';
            if (preg_match('/Version\/([0-9]+\.[0-9]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $browser = 'Opera';
            if (preg_match('/Opera\/([0-9]+\.[0-9]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        }
        
        return array(
            'name' => $browser,
            'version' => $version,
            'user_agent' => $user_agent,
        );
    }

    /**
     * Get operating system info
     *
     * @return array OS information
     */
    public static function get_os_info() {
        $user_agent = self::get_user_agent();
        
        $os = 'Unknown';
        $version = '';
        
        if (preg_match('/Windows/i', $user_agent)) {
            $os = 'Windows';
            if (preg_match('/Windows NT ([0-9]+\.[0-9]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Mac/i', $user_agent)) {
            $os = 'Mac OS';
            if (preg_match('/Mac OS X ([0-9_]+)/i', $user_agent, $matches)) {
                $version = str_replace('_', '.', $matches[1]);
            }
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $os = 'Android';
            if (preg_match('/Android ([0-9\.]+)/i', $user_agent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/iPhone|iPad|iPod/i', $user_agent)) {
            $os = 'iOS';
            if (preg_match('/OS ([0-9_]+)/i', $user_agent, $matches)) {
                $version = str_replace('_', '.', $matches[1]);
            }
        }
        
        return array(
            'name' => $os,
            'version' => $version,
        );
    }

    /**
     * Is mobile device
     *
     * @return bool True if mobile device
     */
    public static function is_mobile() {
        $user_agent = self::get_user_agent();
        
        $mobile_agents = array(
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry',
            'Windows Phone', 'webOS', 'Palm', 'Symbian', 'Kindle'
        );
        
        foreach ($mobile_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Is tablet device
     *
     * @return bool True if tablet device
     */
    public static function is_tablet() {
        $user_agent = self::get_user_agent();
        
        $tablet_agents = array('iPad', 'Android 3', 'Tablet');
        
        foreach ($tablet_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get device type
     *
     * @return string Device type
     */
    public static function get_device_type() {
        if (self::is_tablet()) {
            return 'tablet';
        } elseif (self::is_mobile()) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    /**
     * Generate random string
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
     * Generate random number
     *
     * @param int $min Minimum number
     * @param int $max Maximum number
     * @return int Random number
     */
    public static function generate_random_number($min = 0, $max = 999999) {
        return mt_rand($min, $max);
    }

    /**
     * Hash password
     *
     * @param string $password Password
     * @return string Hashed password
     */
    public static function hash_password($password) {
        return wp_hash_password($password);
    }

    /**
     * Verify password
     *
     * @param string $password Password
     * @param string $hash Hash
     * @return bool True if password matches
     */
    public static function verify_password($password, $hash) {
        return wp_check_password($password, $hash);
    }

    /**
     * Send email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @param array $headers Email headers
     * @param array $attachments Email attachments
     * @return bool True if email sent successfully
     */
    public static function send_email($to, $subject, $message, $headers = array(), $attachments = array()) {
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>',
        );
        
        $headers = array_merge($default_headers, $headers);
        
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    /**
     * Get email template
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered template
     */
    public static function get_email_template($template, $data = array()) {
        $template_path = AIOT_PATH . 'public/templates/emails/' . $template . '.php';
        
        if (!file_exists($template_path)) {
            return '';
        }
        
        extract($data);
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Log message
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array $context Additional context
     */
    public static function log($message, $level = 'info', $context = array()) {
        if (!get_option('aiot_debug_mode', false)) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
            'user_agent' => self::get_user_agent(),
        );

        error_log('AIOT Log: ' . json_encode($log_entry));
    }

    /**
     * Get memory usage
     *
     * @return array Memory usage information
     */
    public static function get_memory_usage() {
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        return array(
            'current' => $memory_usage,
            'peak' => $memory_peak,
            'current_formatted' => self::format_bytes($memory_usage),
            'peak_formatted' => self::format_bytes($memory_peak),
        );
    }

    /**
     * Format bytes
     *
     * @param int $bytes Bytes
     * @param int $precision Precision
     * @return string Formatted bytes
     */
    public static function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get execution time
     *
     * @param float $start_time Start time
     * @return float Execution time
     */
    public static function get_execution_time($start_time) {
        return microtime(true) - $start_time;
    }

    /**
     * Is valid email
     *
     * @param string $email Email address
     * @return bool True if valid email
     */
    public static function is_valid_email($email) {
        return is_email($email);
    }

    /**
     * Is valid phone
     *
     * @param string $phone Phone number
     * @return bool True if valid phone
     */
    public static function is_valid_phone($phone) {
        $phone = preg_replace('/[^\d]/', '', $phone);
        return strlen($phone) >= 10;
    }

    /**
     * Is valid URL
     *
     * @param string $url URL
     * @return bool True if valid URL
     */
    public static function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Sanitize input
     *
     * @param string $input Input string
     * @param string $type Input type
     * @return string Sanitized input
     */
    public static function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'html':
                return wp_kses_post($input);
            case 'filename':
                return sanitize_file_name($input);
            default:
                return sanitize_text_field($input);
        }
    }

    /**
     * Escape output
     *
     * @param string $output Output string
     * @param string $context Context
     * @return string Escaped output
     */
    public static function escape_output($output, $context = 'html') {
        switch ($context) {
            case 'html':
                return esc_html($output);
            case 'attr':
                return esc_attr($output);
            case 'js':
                return esc_js($output);
            case 'url':
                return esc_url($output);
            default:
                return esc_html($output);
        }
    }

    /**
     * Truncate text
     *
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix
     * @return string Truncated text
     */
    public static function truncate_text($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Convert array to CSV
     *
     * @param array $data Data array
     * @param string $delimiter Delimiter
     * @return string CSV string
     */
    public static function array_to_csv($data, $delimiter = ',') {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row, $delimiter);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Convert CSV to array
     *
     * @param string $csv CSV string
     * @param string $delimiter Delimiter
     * @return array Data array
     */
    public static function csv_to_array($csv, $delimiter = ',') {
        if (empty($csv)) {
            return array();
        }
        
        $lines = explode("\n", $csv);
        $data = array();
        
        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter);
            if (!empty($row)) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
}