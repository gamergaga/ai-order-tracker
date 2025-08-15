<?php
/**
 * Tracking shortcode class
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Class AIOT_Tracking_Shortcode
 */
class AIOT_Tracking_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('aiot_tracking', array($this, 'render_tracking_form'));
        add_shortcode('aiot_tracker', array($this, 'render_tracking_form'));
    }

    /**
     * Render tracking form
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_tracking_form($atts) {
        // Redirect to simple tracking shortcode to avoid Vue.js issues
        $simple_tracking = new AIOT_Simple_Tracking_Shortcode();
        return $simple_tracking->render_simple_tracking($atts);
    }
}