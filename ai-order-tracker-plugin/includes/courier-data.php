<?php
/**
 * Courier Data Manager for AI Order Tracker
 * 
 * This file contains all courier information in an easily editable format.
 * Data is structured for easy export/import and modification.
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Get all courier data
 *
 * @return array All courier data
 */
function aiot_get_all_courier_data() {
    return array(
        // Major International Couriers
        array(
            'name' => 'UPS',
            'slug' => 'ups',
            'phone' => '+1-800-742-5877',
            'website' => 'https://www.ups.com',
            'type' => 'express',
            'image' => 'https://example.com/couriers/ups.png',
            'country' => 'US',
            'url_pattern' => 'https://wwwapps.ups.com/tracking/trackingDetails?tracknum={tracking_id}',
            'display_name' => 'UPS',
            'tracking_format' => '/^1Z[0-9A-Z]{16}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'United Parcel Service - Global package delivery company'
        ),
        array(
            'name' => 'FedEx',
            'slug' => 'fedex',
            'phone' => '+1-800-463-3339',
            'website' => 'https://www.fedex.com',
            'type' => 'express',
            'image' => 'https://example.com/couriers/fedex.png',
            'country' => 'US',
            'url_pattern' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_id}',
            'display_name' => 'FedEx',
            'tracking_format' => '/^[0-9]{12,14}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'Federal Express - Global courier delivery services'
        ),
        array(
            'name' => 'DHL',
            'slug' => 'dhl',
            'phone' => '+1-800-225-5345',
            'website' => 'https://www.dhl.com',
            'type' => 'express',
            'image' => 'https://example.com/couriers/dhl.png',
            'country' => 'DE',
            'url_pattern' => 'https://www.dhl.com/us-en/home/tracking/tracking-parcel.html?submit=1&tracking-id={tracking_id}',
            'display_name' => 'DHL',
            'tracking_format' => '/^[0-9]{10,11}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'DHL Express - International shipping and courier services'
        ),
        array(
            'name' => 'USPS',
            'slug' => 'usps',
            'phone' => '+1-800-275-8777',
            'website' => 'https://www.usps.com',
            'type' => 'globalpost',
            'image' => 'https://example.com/couriers/usps.png',
            'country' => 'US',
            'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
            'display_name' => 'USPS',
            'tracking_format' => '/^[0-9]{20,22}$/',
            'supports_international' => false,
            'supports_domestic' => true,
            'description' => 'United States Postal Service - Postal service in the United States'
        ),
        
        // European Couriers
        array(
            'name' => 'Royal Mail',
            'slug' => 'royal-mail',
            'phone' => '+44-3457-740-740',
            'website' => 'https://www.royalmail.com',
            'type' => 'globalpost',
            'image' => 'https://example.com/couriers/royal-mail.png',
            'country' => 'GB',
            'url_pattern' => 'https://www.royalmail.com/track-your-item?trackNumber={tracking_id}',
            'display_name' => 'Royal Mail',
            'tracking_format' => '/^[A-Z0-9]{9,13}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'Royal Mail - Postal service in the United Kingdom'
        ),
        array(
            'name' => 'DPD',
            'slug' => 'dpd',
            'phone' => '+44-330-333-3333',
            'website' => 'https://www.dpd.com',
            'type' => 'express',
            'image' => 'https://example.com/couriers/dpd.png',
            'country' => 'DE',
            'url_pattern' => 'https://www.dpd.com/tracking/{tracking_id}',
            'display_name' => 'DPD',
            'tracking_format' => '/^[0-9]{11,14}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'DPD - International parcel delivery company'
        ),
        array(
            'name' => 'Hermes',
            'slug' => 'hermes',
            'phone' => '+44-330-333-6556',
            'website' => 'https://www.myhermes.co.uk',
            'type' => 'globalpost',
            'image' => 'https://example.com/couriers/hermes.png',
            'country' => 'GB',
            'url_pattern' => 'https://www.myhermes.co.uk/tracking/results.html?trackingNumber={tracking_id}',
            'display_name' => 'Hermes',
            'tracking_format' => '/^[0-9]{16}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'Hermes - Parcel delivery company in Europe'
        ),
        
        // Asian Couriers
        array(
            'name' => 'SF Express',
            'slug' => 'sf-express',
            'phone' => '+86-400-811-1111',
            'website' => 'https://www.sf-express.com',
            'type' => 'express',
            'image' => 'https://example.com/couriers/sf-express.png',
            'country' => 'CN',
            'url_pattern' => 'https://www.sf-express.com/us/en/track/detail/{tracking_id}',
            'display_name' => 'SF Express',
            'tracking_format' => '/^[A-Z0-9]{12}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'SF Express - Leading courier company in China'
        ),
        array(
            'name' => 'Japan Post',
            'slug' => 'japan-post',
            'phone' => '+81-570-46-6111',
            'website' => 'https://www.japanpost.jp',
            'type' => 'globalpost',
            'image' => 'https://example.com/couriers/japan-post.png',
            'country' => 'JP',
            'url_pattern' => 'https://tracking.post.japanpost.jp/services/srv/search/direct?searchKind=S004&locale=en&reqCodeNo1={tracking_id}',
            'display_name' => 'Japan Post',
            'tracking_format' => '/^[A-Z0-9]{11,13}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'Japan Post - Postal service in Japan'
        ),
        
        // Oceanian Couriers
        array(
            'name' => 'Australia Post',
            'slug' => 'australia-post',
            'phone' => '+61-13-13-18',
            'website' => 'https://auspost.com.au',
            'type' => 'globalpost',
            'image' => 'https://example.com/couriers/australia-post.png',
            'country' => 'AU',
            'url_pattern' => 'https://auspost.com.au/mypost/track/#/details/{tracking_id}',
            'display_name' => 'Australia Post',
            'tracking_format' => '/^[A-Z0-9]{13,16}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'Australia Post - Postal service in Australia'
        ),
        
        // North American Couriers
        array(
            'name' => 'Canada Post',
            'slug' => 'canada-post',
            'phone' => '+1-866-607-6301',
            'website' => 'https://www.canadapost.ca',
            'type' => 'globalpost',
            'image' => 'https://example.com/couriers/canada-post.png',
            'country' => 'CA',
            'url_pattern' => 'https://www.canadapost.ca/trackweb/en#/search/{tracking_id}',
            'display_name' => 'Canada Post',
            'tracking_format' => '/^[A-Z0-9]{16}$/',
            'supports_international' => true,
            'supports_domestic' => true,
            'description' => 'Canada Post - Primary postal operator in Canada'
        ),
        
        // Additional couriers from CSV will be added here
        // This structure makes it easy to add, remove, or modify courier information
    );
}

/**
 * Get courier data by slug
 *
 * @param string $slug Courier slug
 * @return array|false Courier data or false if not found
 */
function aiot_get_courier_data_by_slug($slug) {
    $couriers = aiot_get_all_courier_data();
    
    foreach ($couriers as $courier) {
        if ($courier['slug'] === $slug) {
            return $courier;
        }
    }
    
    return false;
}

/**
 * Get couriers by country
 *
 * @param string $country Country code
 * @return array Couriers in the specified country
 */
function aiot_get_couriers_by_country($country) {
    $couriers = aiot_get_all_courier_data();
    $result = array();
    
    foreach ($couriers as $courier) {
        if ($courier['country'] === $country) {
            $result[] = $courier;
        }
    }
    
    return $result;
}

/**
 * Get couriers by type
 *
 * @param string $type Courier type (express, globalpost, etc.)
 * @return array Couriers of the specified type
 */
function aiot_get_couriers_by_type($type) {
    $couriers = aiot_get_all_courier_data();
    $result = array();
    
    foreach ($couriers as $courier) {
        if ($courier['type'] === $type) {
            $result[] = $courier;
        }
    }
    
    return $result;
}

/**
 * Search couriers by name
 *
 * @param string $query Search query
 * @return array Matching couriers
 */
function aiot_search_couriers($query) {
    $couriers = aiot_get_all_courier_data();
    $result = array();
    $query = strtolower($query);
    
    foreach ($couriers as $courier) {
        if (strpos(strtolower($courier['name']), $query) !== false || 
            strpos(strtolower($courier['display_name']), $query) !== false) {
            $result[] = $courier;
        }
    }
    
    return $result;
}

/**
 * Export courier data to CSV format
 *
 * @return string CSV data
 */
function aiot_export_couriers_to_csv() {
    $couriers = aiot_get_all_courier_data();
    $csv = "Name,Slug,Phone,Website,Type,Image,Country,URLPattern,DisplayName,TrackingFormat,SupportsInternational,SupportsDomestic,Description\n";
    
    foreach ($couriers as $courier) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
            $courier['name'],
            $courier['slug'],
            $courier['phone'],
            $courier['website'],
            $courier['type'],
            $courier['image'],
            $courier['country'],
            $courier['url_pattern'],
            $courier['display_name'],
            $courier['tracking_format'],
            $courier['supports_international'] ? 'Yes' : 'No',
            $courier['supports_domestic'] ? 'Yes' : 'No',
            $courier['description']
        );
    }
    
    return $csv;
}

/**
 * Import courier data from CSV
 *
 * @param string $csv_data CSV data
 * @return array Imported couriers
 */
function aiot_import_couriers_from_csv($csv_data) {
    $lines = explode("\n", $csv_data);
    $couriers = array();
    
    // Skip header
    array_shift($lines);
    
    foreach ($lines as $line) {
        if (empty(trim($line))) {
            continue;
        }
        
        $data = str_getcsv($line);
        
        if (count($data) >= 13) {
            $couriers[] = array(
                'name' => $data[0],
                'slug' => $data[1],
                'phone' => $data[2],
                'website' => $data[3],
                'type' => $data[4],
                'image' => $data[5],
                'country' => $data[6],
                'url_pattern' => $data[7],
                'display_name' => $data[8],
                'tracking_format' => $data[9],
                'supports_international' => strtolower($data[10]) === 'yes',
                'supports_domestic' => strtolower($data[11]) === 'yes',
                'description' => $data[12],
            );
        }
    }
    
    return $couriers;
}