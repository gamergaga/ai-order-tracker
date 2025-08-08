<?php
/**
 * Dependencies management class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Dependencies
 */
class AIOT_Dependencies {

    /**
     * Check plugin dependencies
     *
     * @return array Dependency status
     */
    public static function check_dependencies() {
        $dependencies = array(
            'php_version' => self::check_php_version(),
            'wp_version' => self::check_wp_version(),
            'woocommerce' => self::check_woocommerce(),
            'extensions' => self::check_extensions(),
        );

        return $dependencies;
    }

    /**
     * Check PHP version
     *
     * @return array PHP version status
     */
    private static function check_php_version() {
        $required_version = AIOT_MIN_PHP_VERSION;
        $current_version = PHP_VERSION;
        
        return array(
            'required' => $required_version,
            'current' => $current_version,
            'compatible' => version_compare($current_version, $required_version, '>='),
            'message' => sprintf(
                __('PHP %s or higher is required. You are running %s.', 'ai-order-tracker'),
                $required_version,
                $current_version
            ),
        );
    }

    /**
     * Check WordPress version
     *
     * @return array WordPress version status
     */
    private static function check_wp_version() {
        $required_version = AIOT_MIN_WP_VERSION;
        $current_version = get_bloginfo('version');
        
        return array(
            'required' => $required_version,
            'current' => $current_version,
            'compatible' => version_compare($current_version, $required_version, '>='),
            'message' => sprintf(
                __('WordPress %s or higher is required. You are running %s.', 'ai-order-tracker'),
                $required_version,
                $current_version
            ),
        );
    }

    /**
     * Check WooCommerce
     *
     * @return array WooCommerce status
     */
    private static function check_woocommerce() {
        $is_active = class_exists('WooCommerce');
        $version = $is_active ? WC()->version : '0';
        $required_version = '5.0';
        
        return array(
            'required' => $required_version,
            'current' => $version,
            'installed' => $is_active,
            'compatible' => $is_active && version_compare($version, $required_version, '>='),
            'message' => $is_active ? 
                sprintf(
                    __('WooCommerce %s or higher is required. You are running %s.', 'ai-order-tracker'),
                    $required_version,
                    $version
                ) :
                __('WooCommerce is not installed or activated.', 'ai-order-tracker'),
        );
    }

    /**
     * Check required PHP extensions
     *
     * @return array Extensions status
     */
    private static function check_extensions() {
        $required_extensions = array(
            'curl' => array(
                'name' => 'cURL',
                'description' => __('Required for external API calls', 'ai-order-tracker'),
            ),
            'json' => array(
                'name' => 'JSON',
                'description' => __('Required for data handling', 'ai-order-tracker'),
            ),
            'mbstring' => array(
                'name' => 'Multibyte String',
                'description' => __('Required for character encoding', 'ai-order-tracker'),
            ),
            'xml' => array(
                'name' => 'XML',
                'description' => __('Required for data parsing', 'ai-order-tracker'),
            ),
            'zip' => array(
                'name' => 'Zip',
                'description' => __('Required for file compression', 'ai-order-tracker'),
            ),
            'gd' => array(
                'name' => 'GD',
                'description' => __('Required for image processing', 'ai-order-tracker'),
            ),
        );

        $extensions_status = array();
        
        foreach ($required_extensions as $extension => $info) {
            $is_loaded = extension_loaded($extension);
            
            $extensions_status[$extension] = array(
                'name' => $info['name'],
                'description' => $info['description'],
                'loaded' => $is_loaded,
                'message' => $is_loaded ? 
                    sprintf(__('%s is installed and enabled.', 'ai-order-tracker'), $info['name']) :
                    sprintf(__('%s is not installed or enabled.', 'ai-order-tracker'), $info['name']),
            );
        }

        return $extensions_status;
    }

    /**
     * Check if all dependencies are met
     *
     * @return bool True if all dependencies are met
     */
    public static function are_dependencies_met() {
        $dependencies = self::check_dependencies();
        
        // Check PHP version
        if (!$dependencies['php_version']['compatible']) {
            return false;
        }
        
        // Check WordPress version
        if (!$dependencies['wp_version']['compatible']) {
            return false;
        }
        
        // Check extensions
        foreach ($dependencies['extensions'] as $extension) {
            if (!$extension['loaded']) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get dependency notice
     *
     * @return string HTML notice
     */
    public static function get_dependency_notice() {
        $dependencies = self::check_dependencies();
        $notices = array();
        
        // PHP version notice
        if (!$dependencies['php_version']['compatible']) {
            $notices[] = $dependencies['php_version']['message'];
        }
        
        // WordPress version notice
        if (!$dependencies['wp_version']['compatible']) {
            $notices[] = $dependencies['wp_version']['message'];
        }
        
        // Extensions notices
        foreach ($dependencies['extensions'] as $extension) {
            if (!$extension['loaded']) {
                $notices[] = $extension['message'];
            }
        }
        
        if (empty($notices)) {
            return '';
        }
        
        $notice_html = '<div class="notice notice-error">';
        $notice_html .= '<h3>' . __('AI Order Tracker - Dependencies Not Met', 'ai-order-tracker') . '</h3>';
        $notice_html .= '<ul>';
        
        foreach ($notices as $notice) {
            $notice_html .= '<li>' . esc_html($notice) . '</li>';
        }
        
        $notice_html .= '</ul>';
        $notice_html .= '</div>';
        
        return $notice_html;
    }

    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_frontend_scripts() {
        // Only load if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'aiot_tracking')) {
            self::enqueue_vue_js();
            self::enqueue_lottie_js();
            self::enqueue_leaflet_js();
        }
    }

    /**
     * Enqueue Vue.js
     */
    private static function enqueue_vue_js() {
        $vue_version = '3.0.0';
        $vue_handle = 'vue';
        
        // Check if Vue is already enqueued
        if (!wp_script_is($vue_handle, 'enqueued')) {
            // Try to load from CDN first
            $cdn_url = 'https://unpkg.com/vue@' . $vue_version . '/dist/vue.global.prod.js';
            
            // Check if CDN is accessible
            $response = wp_remote_head($cdn_url);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                wp_enqueue_script(
                    $vue_handle,
                    $cdn_url,
                    array(),
                    $vue_version,
                    true
                );
            } else {
                // Fall back to local file
                $local_path = AIOT_URL . 'assets/libs/vue.global.prod.js';
                
                if (file_exists(AIOT_PATH . 'assets/libs/vue.global.prod.js')) {
                    wp_enqueue_script(
                        $vue_handle,
                        $local_path,
                        array(),
                        $vue_version,
                        true
                    );
                }
            }
        }
    }

    /**
     * Enqueue Lottie.js
     */
    private static function enqueue_lottie_js() {
        $lottie_version = '5.7.0';
        $lottie_handle = 'lottie-player';
        
        if (!wp_script_is($lottie_handle, 'enqueued')) {
            // Try to load from CDN first
            $cdn_url = 'https://unpkg.com/@lottiefiles/lottie-player@' . $lottie_version . '/dist/lottie-player.js';
            
            // Check if CDN is accessible
            $response = wp_remote_head($cdn_url);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                wp_enqueue_script(
                    $lottie_handle,
                    $cdn_url,
                    array(),
                    $lottie_version,
                    true
                );
            } else {
                // Fall back to local file
                $local_path = AIOT_URL . 'assets/libs/lottie-player.js';
                
                if (file_exists(AIOT_PATH . 'assets/libs/lottie-player.js')) {
                    wp_enqueue_script(
                        $lottie_handle,
                        $local_path,
                        array(),
                        $lottie_version,
                        true
                    );
                }
            }
        }
    }

    /**
     * Enqueue Leaflet.js
     */
    private static function enqueue_leaflet_js() {
        $leaflet_version = '1.7.1';
        $leaflet_handle = 'leaflet';
        
        if (!wp_script_is($leaflet_handle, 'enqueued')) {
            // Try to load from CDN first
            $cdn_url = 'https://unpkg.com/leaflet@' . $leaflet_version . '/dist/leaflet.js';
            $css_url = 'https://unpkg.com/leaflet@' . $leaflet_version . '/dist/leaflet.css';
            
            // Check if CDN is accessible
            $response = wp_remote_head($cdn_url);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                wp_enqueue_script(
                    $leaflet_handle,
                    $cdn_url,
                    array(),
                    $leaflet_version,
                    true
                );
                
                wp_enqueue_style(
                    $leaflet_handle . '-css',
                    $css_url,
                    array(),
                    $leaflet_version
                );
            } else {
                // Fall back to local files
                $js_path = AIOT_URL . 'assets/libs/leaflet.js';
                $css_path = AIOT_URL . 'assets/libs/leaflet.css';
                
                if (file_exists(AIOT_PATH . 'assets/libs/leaflet.js')) {
                    wp_enqueue_script(
                        $leaflet_handle,
                        $js_path,
                        array(),
                        $leaflet_version,
                        true
                    );
                }
                
                if (file_exists(AIOT_PATH . 'assets/libs/leaflet.css')) {
                    wp_enqueue_style(
                        $leaflet_handle . '-css',
                        $css_path,
                        array(),
                        $leaflet_version
                    );
                }
            }
        }
    }

    /**
     * Load text domain
     */
    public static function load_text_domain() {
        load_plugin_textdomain(
            'ai-order-tracker',
            false,
            dirname(AIOT_BASENAME) . '/languages/'
        );
    }

    /**
     * Check if required files exist
     *
     * @return array Files status
     */
    public static function check_required_files() {
        $required_files = array(
            'main_plugin' => AIOT_PATH . 'ai-order-tracker.php',
            'uninstall' => AIOT_PATH . 'uninstall.php',
            'database' => AIOT_PATH . 'includes/class-database.php',
            'security' => AIOT_PATH . 'includes/class-security.php',
            'tracking_engine' => AIOT_PATH . 'includes/class-tracking-engine.php',
            'admin_init' => AIOT_PATH . 'admin/admin-init.php',
            'admin_css' => AIOT_PATH . 'admin/css/admin.css',
            'admin_js' => AIOT_PATH . 'admin/js/admin.js',
            'public_css' => AIOT_PATH . 'public/css/public.css',
            'public_js' => AIOT_PATH . 'public/js/public.js',
            'shortcode' => AIOT_PATH . 'public/class-tracking-shortcode.php',
        );

        $files_status = array();
        
        foreach ($required_files as $key => $file_path) {
            $files_status[$key] = array(
                'path' => $file_path,
                'exists' => file_exists($file_path),
                'readable' => file_exists($file_path) && is_readable($file_path),
            );
        }

        return $files_status;
    }

    /**
     * Check directory permissions
     *
     * @return array Directories status
     */
    public static function check_directory_permissions() {
        $directories = array(
            'assets' => AIOT_PATH . 'assets',
            'assets_animations' => AIOT_PATH . 'assets/animations',
            'assets_geo' => AIOT_PATH . 'assets/geo',
            'assets_libs' => AIOT_PATH . 'assets/libs',
            'admin' => AIOT_PATH . 'admin',
            'admin_css' => AIOT_PATH . 'admin/css',
            'admin_js' => AIOT_PATH . 'admin/js',
            'includes' => AIOT_PATH . 'includes',
            'public' => AIOT_PATH . 'public',
            'public_css' => AIOT_PATH . 'public/css',
            'public_js' => AIOT_PATH . 'public/js',
            'public_templates' => AIOT_PATH . 'public/templates',
            'languages' => AIOT_PATH . 'languages',
        );

        $directories_status = array();
        
        foreach ($directories as $key => $dir_path) {
            $directories_status[$key] = array(
                'path' => $dir_path,
                'exists' => file_exists($dir_path),
                'is_dir' => file_exists($dir_path) && is_dir($dir_path),
                'writable' => file_exists($dir_path) && is_writable($dir_path),
                'permissions' => file_exists($dir_path) ? substr(sprintf('%o', fileperms($dir_path)), -4) : 'N/A',
            );
        }

        return $directories_status;
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public static function get_system_info() {
        global $wpdb;
        
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'wc_version' => class_exists('WooCommerce') ? WC()->version : 'Not installed',
            'plugin_version' => AIOT_VERSION,
            'theme' => get_template(),
            'active_plugins' => get_option('active_plugins'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
            'os' => PHP_OS,
            'browser_info' => AIOT_Helpers::get_browser_info(),
            'device_type' => AIOT_Helpers::get_device_type(),
        );
    }

    /**
     * Check plugin compatibility
     *
     * @return array Compatibility status
     */
    public static function check_plugin_compatibility() {
        $active_plugins = get_option('active_plugins');
        $compatible_plugins = array();
        $incompatible_plugins = array();
        
        // List of known conflicting plugins
        $conflicting_plugins = array(
            'other-order-tracker/other-order-tracker.php',
            'competing-shipping-plugin/competing-shipping-plugin.php',
        );
        
        foreach ($active_plugins as $plugin) {
            if (in_array($plugin, $conflicting_plugins)) {
                $incompatible_plugins[] = $plugin;
            } else {
                $compatible_plugins[] = $plugin;
            }
        }
        
        return array(
            'compatible' => $compatible_plugins,
            'incompatible' => $incompatible_plugins,
            'has_conflicts' => !empty($incompatible_plugins),
        );
    }
}