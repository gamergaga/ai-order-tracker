<?php
/**
 * Simple functionality test for AI Order Tracker
 * 
 * This file contains basic tests to verify the plugin functionality.
 * Run this file to test the plugin installation and basic features.
 *
 * @package AI_Order_Tracker
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Class AIOT_Functionality_Test
 */
class AIOT_Functionality_Test {
    
    /**
     * Run all tests
     *
     * @return array Test results
     */
    public function run_tests() {
        $results = array();
        
        // Test 1: Plugin Constants
        $results['constants'] = $this->test_plugin_constants();
        
        // Test 2: Database Tables
        $results['database'] = $this->test_database_tables();
        
        // Test 3: Courier Data
        $results['courier_data'] = $this->test_courier_data();
        
        // Test 4: Courier Manager
        $results['courier_manager'] = $this->test_courier_manager();
        
        // Test 5: Tracking Engine
        $results['tracking_engine'] = $this->test_tracking_engine();
        
        // Test 6: Direct Tracking
        $results['direct_tracking'] = $this->test_direct_tracking();
        
        // Test 7: Security
        $results['security'] = $this->test_security();
        
        return $results;
    }
    
    /**
     * Test plugin constants
     *
     * @return array Test result
     */
    private function test_plugin_constants() {
        $result = array(
            'passed' => true,
            'message' => 'Plugin constants test passed',
            'details' => array()
        );
        
        $constants = array(
            'AIOT_VERSION',
            'AIOT_PATH',
            'AIOT_URL',
            'AIOT_BASENAME',
            'AIOT_MIN_PHP_VERSION',
            'AIOT_MIN_WP_VERSION'
        );
        
        foreach ($constants as $constant) {
            if (!defined($constant)) {
                $result['passed'] = false;
                $result['message'] = "Constant $constant is not defined";
                break;
            }
            $result['details'][$constant] = constant($constant);
        }
        
        return $result;
    }
    
    /**
     * Test database tables
     *
     * @return array Test result
     */
    private function test_database_tables() {
        global $wpdb;
        
        $result = array(
            'passed' => true,
            'message' => 'Database tables test passed',
            'details' => array()
        );
        
        $tables = array(
            'aiot_orders',
            'aiot_zones',
            'aiot_couriers',
            'aiot_tracking_events'
        );
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if (!$table_exists) {
                $result['passed'] = false;
                $result['message'] = "Table $table_name does not exist";
                break;
            }
            
            $result['details'][$table] = $table_exists ? 'exists' : 'missing';
        }
        
        return $result;
    }
    
    /**
     * Test courier data
     *
     * @return array Test result
     */
    private function test_courier_data() {
        $result = array(
            'passed' => true,
            'message' => 'Courier data test passed',
            'details' => array()
        );
        
        // Test if courier data function exists
        if (!function_exists('aiot_get_all_courier_data')) {
            $result['passed'] = false;
            $result['message'] = 'Courier data function not found';
            return $result;
        }
        
        // Test getting all courier data
        $couriers = aiot_get_all_courier_data();
        if (empty($couriers)) {
            $result['passed'] = false;
            $result['message'] = 'No courier data found';
            return $result;
        }
        
        $result['details']['total_couriers'] = count($couriers);
        
        // Test getting courier by slug
        $test_courier = aiot_get_courier_data_by_slug('ups');
        if (!$test_courier) {
            $result['passed'] = false;
            $result['message'] = 'Could not get test courier (UPS)';
            return $result;
        }
        
        $result['details']['test_courier'] = $test_courier['name'];
        
        return $result;
    }
    
    /**
     * Test courier manager
     *
     * @return array Test result
     */
    private function test_courier_manager() {
        $result = array(
            'passed' => true,
            'message' => 'Courier manager test passed',
            'details' => array()
        );
        
        // Test if courier manager class exists
        if (!class_exists('AIOT_Courier_Manager')) {
            $result['passed'] = false;
            $result['message'] = 'Courier manager class not found';
            return $result;
        }
        
        // Test getting couriers
        $couriers = AIOT_Courier_Manager::get_couriers();
        $result['details']['database_couriers'] = count($couriers);
        
        // Test getting courier by slug
        $courier = AIOT_Courier_Manager::get_courier_by_slug('ups');
        if (!$courier) {
            $result['passed'] = false;
            $result['message'] = 'Could not get courier from database';
            return $result;
        }
        
        $result['details']['database_courier_name'] = $courier['name'];
        
        return $result;
    }
    
    /**
     * Test tracking engine
     *
     * @return array Test result
     */
    private function test_tracking_engine() {
        $result = array(
            'passed' => true,
            'message' => 'Tracking engine test passed',
            'details' => array()
        );
        
        // Test if tracking engine class exists
        if (!class_exists('AIOT_Tracking_Engine')) {
            $result['passed'] = false;
            $result['message'] = 'Tracking engine class not found';
            return $result;
        }
        
        // Test tracking ID generation
        $tracking_id = AIOT_Tracking_Engine::generate_tracking_id();
        if (empty($tracking_id)) {
            $result['passed'] = false;
            $result['message'] = 'Could not generate tracking ID';
            return $result;
        }
        
        $result['details']['generated_tracking_id'] = $tracking_id;
        
        // Test status mapping
        $status_info = AIOT_Tracking_Engine::get_order_status('delivered');
        if (!$status_info || $status_info['label'] !== 'Delivered') {
            $result['passed'] = false;
            $result['message'] = 'Status mapping not working correctly';
            return $result;
        }
        
        $result['details']['status_mapping'] = $status_info['label'];
        
        return $result;
    }
    
    /**
     * Test direct tracking
     *
     * @return array Test result
     */
    private function test_direct_tracking() {
        $result = array(
            'passed' => true,
            'message' => 'Direct tracking test passed',
            'details' => array()
        );
        
        // Test if direct tracking class exists
        if (!class_exists('AIOT_Direct_Tracking')) {
            $result['passed'] = false;
            $result['message'] = 'Direct tracking class not found';
            return $result;
        }
        
        // Create test instance
        $direct_tracking = new AIOT_Direct_Tracking();
        
        // Test getting supported couriers
        $supported_couriers = $direct_tracking->get_supported_couriers();
        if (empty($supported_couriers)) {
            $result['passed'] = false;
            $result['message'] = 'No supported couriers found';
            return $result;
        }
        
        $result['details']['supported_couriers'] = count($supported_couriers);
        
        // Test tracking support check
        $is_supported = $direct_tracking->is_tracking_supported('ups');
        if (!$is_supported) {
            $result['passed'] = false;
            $result['message'] = 'Tracking support check failed';
            return $result;
        }
        
        $result['details']['ups_support'] = $is_supported ? 'supported' : 'not supported';
        
        return $result;
    }
    
    /**
     * Test security
     *
     * @return array Test result
     */
    private function test_security() {
        $result = array(
            'passed' => true,
            'message' => 'Security test passed',
            'details' => array()
        );
        
        // Test if security class exists
        if (!class_exists('AIOT_Security')) {
            $result['passed'] = false;
            $result['message'] = 'Security class not found';
            return $result;
        }
        
        // Test tracking ID sanitization
        $malicious_id = '<script>alert("xss");</script>12345';
        $sanitized_id = AIOT_Security::sanitize_tracking_id($malicious_id);
        
        if ($sanitized_id !== '12345') {
            $result['passed'] = false;
            $result['message'] = 'Tracking ID sanitization failed';
            return $result;
        }
        
        $result['details']['sanitization_test'] = 'passed';
        
        return $result;
    }
    
    /**
     * Display test results
     *
     * @param array $results Test results
     */
    public function display_results($results) {
        echo '<div class="aiot-test-results">';
        echo '<h2>AI Order Tracker - Functionality Test Results</h2>';
        
        $all_passed = true;
        
        foreach ($results as $test_name => $result) {
            $status_class = $result['passed'] ? 'passed' : 'failed';
            $status_icon = $result['passed'] ? '✅' : '❌';
            
            echo '<div class="test-item ' . $status_class . '">';
            echo '<h3>' . $status_icon . ' ' . ucfirst(str_replace('_', ' ', $test_name)) . '</h3>';
            echo '<p><strong>Status:</strong> ' . ($result['passed'] ? 'PASSED' : 'FAILED') . '</p>';
            echo '<p><strong>Message:</strong> ' . esc_html($result['message']) . '</p>';
            
            if (!empty($result['details'])) {
                echo '<h4>Details:</h4>';
                echo '<ul>';
                foreach ($result['details'] as $key => $value) {
                    echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html(is_array($value) ? print_r($value, true) : $value) . '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
            
            if (!$result['passed']) {
                $all_passed = false;
            }
        }
        
        echo '<div class="test-summary ' . ($all_passed ? 'passed' : 'failed') . '">';
        echo '<h2>' . ($all_passed ? '✅ All Tests Passed!' : '❌ Some Tests Failed!') . '</h2>';
        echo '<p>' . ($all_passed ? 'The plugin is working correctly.' : 'Please check the failed tests and fix the issues.') . '</p>';
        echo '</div>';
        
        echo '</div>';
    }
}

// Run tests if this file is accessed directly
if (isset($_GET['aiot_test']) && $_GET['aiot_test'] === 'true') {
    // Check if user has admin privileges
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    // Run tests
    $test = new AIOT_Functionality_Test();
    $results = $test->run_tests();
    
    // Display results
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>AI Order Tracker - Functionality Test</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .aiot-test-results {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .test-item {
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .test-item.passed {
                border-color: #28a745;
                background-color: #f8fff9;
            }
            .test-item.failed {
                border-color: #dc3545;
                background-color: #fff8f8;
            }
            .test-summary {
                margin-top: 30px;
                padding: 20px;
                border-radius: 5px;
                text-align: center;
            }
            .test-summary.passed {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .test-summary.failed {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            h2 {
                margin-top: 0;
                color: #333;
            }
            h3 {
                margin-top: 0;
                color: #555;
            }
            h4 {
                color: #666;
                margin-bottom: 10px;
            }
            ul {
                margin: 0;
                padding-left: 20px;
            }
            li {
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <?php $test->display_results($results); ?>
    </body>
    </html>
    <?php
    exit;
}