<?php
/**
 * Default couriers data for AI Order Tracker
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;

/**
 * Get default couriers data
 *
 * @return array Default couriers data
 */
function aiot_get_default_couriers() {
    return array(
        array(
            'name' => 'UPS',
            'slug' => 'ups',
            'description' => 'United Parcel Service - Global package delivery company',
            'url_pattern' => 'https://wwwapps.ups.com/tracking/trackingDetails?tracknum={tracking_id}',
            'api_endpoint' => 'https://onlinetools.ups.com/track/v1/details',
            'tracking_format' => 'ups',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => true,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '18',
                'tracking_format' => '/^1Z[0-9A-Z]{16}$/'
            ))
        ),
        array(
            'name' => 'FedEx',
            'slug' => 'fedex',
            'description' => 'Federal Express - Global courier delivery services',
            'url_pattern' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_id}',
            'api_endpoint' => 'https://apis.fedex.com/track/v1/trackingnumbers',
            'tracking_format' => 'fedex',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => true,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-14',
                'tracking_format' => '/^[0-9]{12,14}$/'
            ))
        ),
        array(
            'name' => 'DHL',
            'slug' => 'dhl',
            'description' => 'DHL Express - International shipping and courier services',
            'url_pattern' => 'https://www.dhl.com/us-en/home/tracking/tracking-parcel.html?submit=1&tracking-id={tracking_id}',
            'api_endpoint' => 'https://api.dhl.com/track/shipments',
            'tracking_format' => 'dhl',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => true,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '10-11',
                'tracking_format' => '/^[0-9]{10,11}$/'
            ))
        ),
        array(
            'name' => 'USPS',
            'slug' => 'usps',
            'description' => 'United States Postal Service - Postal service in the United States',
            'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
            'api_endpoint' => 'https://secure.shippingapis.com/ShippingAPI.dll',
            'tracking_format' => 'usps',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => true,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-22',
                'tracking_format' => '/^[0-9]{20,22}$/'
            ))
        ),
        array(
            'name' => 'Amazon Logistics',
            'slug' => 'amazon-logistics',
            'description' => 'Amazon\'s own delivery service for Amazon packages',
            'url_pattern' => 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package?_encoding=UTF8&itemId={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{10,20}$/'
            ))
        ),
        array(
            'name' => 'OnTrac',
            'slug' => 'ontrac',
            'description' => 'OnTrac - Regional package delivery company in the United States',
            'url_pattern' => 'https://www.ontrac.com/trackingdetail.aspx?trackingnumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[A-Z0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => 'Lasership',
            'slug' => 'lasership',
            'description' => 'Lasership - Regional package delivery company',
            'url_pattern' => 'https://www.lasership.com/track/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[A-Z0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => 'Canada Post',
            'slug' => 'canada-post',
            'description' => 'Canada Post - Primary postal operator in Canada',
            'url_pattern' => 'https://www.canadapost.ca/trackweb/en#/search/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '16',
                'tracking_format' => '/^[A-Z0-9]{16}$/'
            ))
        ),
        array(
            'name' => 'Royal Mail',
            'slug' => 'royal-mail',
            'description' => 'Royal Mail - Postal service in the United Kingdom',
            'url_pattern' => 'https://www.royalmail.com/track-your-item?trackNumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '9-13',
                'tracking_format' => '/^[A-Z0-9]{9,13}$/'
            ))
        ),
        array(
            'name' => 'DPD',
            'slug' => 'dpd',
            'description' => 'DPD - International parcel delivery company',
            'url_pattern' => 'https://www.dpd.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-14',
                'tracking_format' => '/^[0-9]{11,14}$/'
            ))
        ),
        array(
            'name' => 'Hermes',
            'slug' => 'hermes',
            'description' => 'Hermes - Parcel delivery company in Europe',
            'url_pattern' => 'https://www.myhermes.co.uk/tracking/results.html?trackingNumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '16',
                'tracking_format' => '/^[0-9]{16}$/'
            ))
        ),
        array(
            'name' => 'TNT',
            'slug' => 'tnt',
            'description' => 'TNT Express - International express delivery services',
            'url_pattern' => 'https://www.tnt.com/express/en_us/site/tracking.html?searchType=con&cons={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '9-15',
                'tracking_format' => '/^[A-Z0-9]{9,15}$/'
            ))
        ),
        array(
            'name' => 'Aramex',
            'slug' => 'aramex',
            'description' => 'Aramex - International logistics and transportation solutions',
            'url_pattern' => 'https://www.aramex.com/track/results?ShipmentNumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '10-12',
                'tracking_format' => '/^[0-9]{10,12}$/'
            ))
        ),
        array(
            'name' => 'SF Express',
            'slug' => 'sf-express',
            'description' => 'SF Express - Leading courier company in China',
            'url_pattern' => 'https://www.sf-express.com/us/en/track/detail/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12',
                'tracking_format' => '/^[A-Z0-9]{12}$/'
            ))
        ),
        array(
            'name' => 'YunExpress',
            'slug' => 'yunexpress',
            'description' => 'YunExpress - Cross-border e-commerce logistics solutions',
            'url_pattern' => 'https://www.yuntrack.com/en/track/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '16-20',
                'tracking_format' => '/^[A-Z0-9]{16,20}$/'
            ))
        ),
        array(
            'name' => 'PostNL',
            'slug' => 'postnl',
            'description' => 'PostNL - Postal and parcel service in the Netherlands',
            'url_pattern' => 'https://jouw.postnl.nl/track-en-trace/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '15',
                'tracking_format' => '/^[A-Z0-9]{15}$/'
            ))
        ),
        array(
            'name' => 'Australia Post',
            'slug' => 'australia-post',
            'description' => 'Australia Post - Postal service in Australia',
            'url_pattern' => 'https://auspost.com.au/mypost/track/#/details/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '13-16',
                'tracking_format' => '/^[A-Z0-9]{13,16}$/'
            ))
        ),
        array(
            'name' => 'Japan Post',
            'slug' => 'japan-post',
            'description' => 'Japan Post - Postal service in Japan',
            'url_pattern' => 'https://tracking.post.japanpost.jp/services/srv/search/direct?searchKind=S004&locale=en&reqCodeNo1={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-13',
                'tracking_format' => '/^[A-Z0-9]{11,13}$/'
            ))
        ),
        array(
            'name' => 'Deutsche Post',
            'slug' => 'deutsche-post',
            'description' => 'Deutsche Post - Postal service in Germany',
            'url_pattern' => 'https://www.deutschepost.de/sendung/simpleQueryResult.html?form.sendungsnummer={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-20',
                'tracking_format' => '/^[A-Z0-9]{11,20}$/'
            ))
        ),
        array(
            'name' => 'La Poste',
            'slug' => 'la-poste',
            'description' => 'La Poste - Postal service in France',
            'url_pattern' => 'https://www.laposte.fr/outils/suivre-vos-envois?code={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '13-15',
                'tracking_format' => '/^[A-Z0-9]{13,15}$/'
            ))
        ),
        array(
            'name' => 'Correos',
            'slug' => 'correos',
            'description' => 'Correos - Postal service in Spain',
            'url_pattern' => 'https://www.correos.es/ss/SiteLocater/parcelSearch?texto={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '13-23',
                'tracking_format' => '/^[A-Z0-9]{13,23}$/'
            ))
        ),
        array(
            'name' => 'Poste Italiane',
            'slug' => 'poste-italiane',
            'description' => 'Poste Italiane - Postal service in Italy',
            'url_pattern' => 'https://www.poste.it/cerca/invio.html?mpcode={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-20',
                'tracking_format' => '/^[A-Z0-9]{11,20}$/'
            ))
        ),
        array(
            'name' => 'Swiss Post',
            'slug' => 'swiss-post',
            'description' => 'Swiss Post - Postal service in Switzerland',
            'url_pattern' => 'https://www.post.ch/en/sending/identify-item?item_id={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '8-10',
                'tracking_format' => '/^[A-Z0-9]{8,10}$/'
            ))
        ),
        array(
            'name' => 'PostNord',
            'slug' => 'postnord',
            'description' => 'PostNord - Postal service in Nordic countries',
            'url_pattern' => 'https://www.postnord.com/en/track-and-trace?trackId={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '13-15',
                'tracking_format' => '/^[A-Z0-9]{13,15}$/'
            ))
        ),
        array(
            'name' => 'GLS',
            'slug' => 'gls',
            'description' => 'GLS - General Logistics Systems parcel delivery',
            'url_pattern' => 'https://gls-group.com/EU/en/parcel-tracking?match={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-12',
                'tracking_format' => '/^[0-9]{11,12}$/'
            ))
        ),
        array(
            'name' => 'DPDHL',
            'slug' => 'dpdhl',
            'description' => 'DPDHL - Deutsche Post DHL Group',
            'url_pattern' => 'https://www.dpdhl.com/en/parcel-tracking?match={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-20',
                'tracking_format' => '/^[A-Z0-9]{12,20}$/'
            ))
        ),
        array(
            'name' => 'EMS',
            'slug' => 'ems',
            'description' => 'EMS - Express Mail Service international postal service',
            'url_pattern' => 'https://www.ems.post/en/global-tracking/track?trackid={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '13',
                'tracking_format' => '/^[A-Z0-9]{13}$/'
            ))
        ),
        array(
            'name' => 'UPS Mail Innovations',
            'slug' => 'ups-mail-innovations',
            'description' => 'UPS Mail Innovations - Hybrid mail service',
            'url_pattern' => 'https://wwwapps.ups.com/mi/tracking?tracknum={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-26',
                'tracking_format' => '/^[A-Z0-9]{20,26}$/'
            ))
        ),
        array(
            'name' => 'FedEx SmartPost',
            'slug' => 'fedex-smartpost',
            'description' => 'FedEx SmartPost - Hybrid mail service',
            'url_pattern' => 'https://www.fedex.com/smartpost/?trknbr={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-22',
                'tracking_format' => '/^[0-9]{20,22}$/'
            ))
        ),
        array(
            'name' => 'DHL eCommerce',
            'slug' => 'dhl-ecommerce',
            'description' => 'DHL eCommerce - International e-commerce solutions',
            'url_pattern' => 'https://webtrack.dhlglobalmail.com/?trackingnumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-20',
                'tracking_format' => '/^[A-Z0-9]{11,20}$/'
            ))
        ),
        array(
            'name' => 'USPS Priority Mail',
            'slug' => 'usps-priority-mail',
            'description' => 'USPS Priority Mail - Expedited mail service',
            'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-22',
                'tracking_format' => '/^[0-9]{20,22}$/'
            ))
        ),
        array(
            'name' => 'USPS First Class Mail',
            'slug' => 'usps-first-class-mail',
            'description' => 'USPS First Class Mail - Standard mail service',
            'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-22',
                'tracking_format' => '/^[0-9]{20,22}$/'
            ))
        ),
        array(
            'name' => 'USPS Media Mail',
            'slug' => 'usps-media-mail',
            'description' => 'USPS Media Mail - Media shipping service',
            'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-22',
                'tracking_format' => '/^[0-9]{20,22}$/'
            ))
        ),
        array(
            'name' => 'USPS Parcel Select',
            'slug' => 'usps-parcel-select',
            'description' => 'USPS Parcel Select - Ground shipping service',
            'url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '20-22',
                'tracking_format' => '/^[0-9]{20,22}$/'
            ))
        ),
        array(
            'name' => 'UPS Ground',
            'slug' => 'ups-ground',
            'description' => 'UPS Ground - Ground shipping service',
            'url_pattern' => 'https://wwwapps.ups.com/tracking/trackingDetails?tracknum={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '18',
                'tracking_format' => '/^1Z[0-9A-Z]{16}$/'
            ))
        ),
        array(
            'name' => 'UPS Next Day Air',
            'slug' => 'ups-next-day-air',
            'description' => 'UPS Next Day Air - Overnight shipping service',
            'url_pattern' => 'https://wwwapps.ups.com/tracking/trackingDetails?tracknum={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '18',
                'tracking_format' => '/^1Z[0-9A-Z]{16}$/'
            ))
        ),
        array(
            'name' => 'UPS 2nd Day Air',
            'slug' => 'ups-2nd-day-air',
            'description' => 'UPS 2nd Day Air - 2-day shipping service',
            'url_pattern' => 'https://wwwapps.ups.com/tracking/trackingDetails?tracknum={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '18',
                'tracking_format' => '/^1Z[0-9A-Z]{16}$/'
            ))
        ),
        array(
            'name' => 'FedEx Ground',
            'slug' => 'fedex-ground',
            'description' => 'FedEx Ground - Ground shipping service',
            'url_pattern' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-14',
                'tracking_format' => '/^[0-9]{12,14}$/'
            ))
        ),
        array(
            'name' => 'FedEx Express',
            'slug' => 'fedex-express',
            'description' => 'FedEx Express - Express shipping service',
            'url_pattern' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-14',
                'tracking_format' => '/^[0-9]{12,14}$/'
            ))
        ),
        array(
            'name' => 'FedEx Home Delivery',
            'slug' => 'fedex-home-delivery',
            'description' => 'FedEx Home Delivery - Residential delivery service',
            'url_pattern' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-14',
                'tracking_format' => '/^[0-9]{12,14}$/'
            ))
        ),
        array(
            'name' => 'DHL Express Worldwide',
            'slug' => 'dhl-express-worldwide',
            'description' => 'DHL Express Worldwide - International express service',
            'url_pattern' => 'https://www.dhl.com/us-en/home/tracking/tracking-parcel.html?submit=1&tracking-id={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '10-11',
                'tracking_format' => '/^[0-9]{10,11}$/'
            ))
        ),
        array(
            'name' => 'DHL Express Domestic',
            'slug' => 'dhl-express-domestic',
            'description' => 'DHL Express Domestic - Domestic express service',
            'url_pattern' => 'https://www.dhl.com/us-en/home/tracking/tracking-parcel.html?submit=1&tracking-id={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '10-11',
                'tracking_format' => '/^[0-9]{10,11}$/'
            ))
        ),
        array(
            'name' => 'DHL eCommerce Asia',
            'slug' => 'dhl-ecommerce-asia',
            'description' => 'DHL eCommerce Asia - Asian e-commerce service',
            'url_pattern' => 'https://webtrack.dhlglobalmail.com/?trackingnumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '11-20',
                'tracking_format' => '/^[A-Z0-9]{11,20}$/'
            ))
        ),
        array(
            'name' => 'DHL eCommerce Americas',
            'slug' => 'dhl-ecommerce-americas',
            'description' => 'DHL eCommerce Americas - Americas e-commerce service',
            'url_pattern' => 'https://webtrack.dhlglobalmail.com/?trackingnumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-20',
                'tracking_format' => '/^[A-Z0-9]{11,20}$/'
            ))
        ),
        array(
            'name' => 'DHL eCommerce Europe',
            'slug' => 'dhl-ecommerce-europe',
            'description' => 'DHL eCommerce Europe - European e-commerce service',
            'url_pattern' => 'https://webtrack.dhlglobalmail.com/?trackingnumber={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '11-20',
                'tracking_format' => '/^[A-Z0-9]{11,20}$/'
            ))
        ),
        array(
            'name' => 'Amazon Logistics US',
            'slug' => 'amazon-logistics-us',
            'description' => 'Amazon Logistics US - US delivery service',
            'url_pattern' => 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package?_encoding=UTF8&itemId={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{10,20}$/'
            ))
        ),
        array(
            'name' => 'Amazon Logistics EU',
            'slug' => 'amazon-logistics-eu',
            'description' => 'Amazon Logistics EU - European delivery service',
            'url_pattern' => 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package?_encoding=UTF8&itemId={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{10,20}$/'
            ))
        ),
        array(
            'name' => 'Amazon Logistics Asia',
            'slug' => 'amazon-logistics-asia',
            'description' => 'Amazon Logistics Asia - Asian delivery service',
            'url_pattern' => 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package?_encoding=UTF8&itemId={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{10,20}$/'
            ))
        ),
        array(
            'name' => 'Local Delivery',
            'slug' => 'local-delivery',
            'description' => 'Local Delivery - Local delivery service',
            'url_pattern' => '',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{5,20}$/'
            ))
        ),
        array(
            'name' => 'Standard Shipping',
            'slug' => 'standard-shipping',
            'description' => 'Standard Shipping - Standard shipping service',
            'url_pattern' => '',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{5,20}$/'
            ))
        ),
        array(
            'name' => 'Express Shipping',
            'slug' => 'express-shipping',
            'description' => 'Express Shipping - Express shipping service',
            'url_pattern' => '',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{5,20}$/'
            ))
        ),
        array(
            'name' => 'Economy Shipping',
            'slug' => 'economy-shipping',
            'description' => 'Economy Shipping - Economy shipping service',
            'url_pattern' => '',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => 'variable',
                'tracking_format' => '/^[A-Z0-9]{5,20}$/'
            ))
        ),
        array(
            'name' => 'Blue Dart',
            'slug' => 'blue-dart',
            'description' => 'Blue Dart - Premier courier and integrated express package distribution company in India',
            'url_pattern' => 'https://www.bluedart.com/servlet/RoutingServlet?handler=tnt&action=track&trackno={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '9-11',
                'tracking_format' => '/^[0-9]{9,11}$/'
            ))
        ),
        array(
            'name' => 'Delhivery',
            'slug' => 'delhivery',
            'description' => 'Delhivery - Largest logistics and supply chain company in India',
            'url_pattern' => 'https://track.delhivery.com/awb/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-14',
                'tracking_format' => '/^[0-9]{12,14}$/'
            ))
        ),
        array(
            'name' => 'XpressBees',
            'slug' => 'xpressbees',
            'description' => 'XpressBees - E-commerce logistics solutions provider',
            'url_pattern' => 'https://www.xpressbees.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '10-15',
                'tracking_format' => '/^[A-Z0-9]{10,15}$/'
            ))
        ),
        array(
            'name' => 'Ecom Express',
            'slug' => 'ecom-express',
            'description' => 'Ecom Express - End-to-end logistics solutions for e-commerce',
            'url_pattern' => 'https://www.ecomexpress.in/tracking/?awb={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => 'Shadowfax',
            'slug' => 'shadowfax',
            'description' => 'Shadowfax - Technology-driven logistics platform',
            'url_pattern' => 'https://shadowfax.in/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '10-12',
                'tracking_format' => '/^[A-Z0-9]{10,12}$/'
            ))
        ),
        array(
            'name' => 'Wow Express',
            'slug' => 'wow-express',
            'description' => 'Wow Express - E-commerce logistics company',
            'url_pattern' => 'https://www.wowexpress.in/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '10-14',
                'tracking_format' => '/^[A-Z0-9]{10,14}$/'
            ))
        ),
        array(
            'name' => 'Pickrr',
            'slug' => 'pickrr',
            'description' => 'Pickrr - AI-powered logistics platform',
            'url_pattern' => 'https://pickrr.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => false,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[A-Z0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => 'Ninja Van',
            'slug' => 'ninja-van',
            'description' => 'Ninja Van - Last-mile logistics provider in Southeast Asia',
            'url_pattern' => 'https://www.ninjavan.co/en/tracking?id={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-20',
                'tracking_format' => '/^[A-Z0-9]{12,20}$/'
            ))
        ),
        array(
            'name' => 'J&T Express',
            'slug' => 'jt-express',
            'description' => 'J&T Express - Express delivery service in Southeast Asia',
            'url_pattern' => 'https://www.jtexpress.my/tracking?trackingNo={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[A-Z0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => 'LBC Express',
            'slug' => 'lbc-express',
            'description' => 'LBC Express - Courier service in the Philippines',
            'url_pattern' => 'https://lbcexpress.com/tracking/?tracking-number={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => '2GO Express',
            'slug' => '2go-express',
            'description' => '2GO Express - Logistics and courier service in the Philippines',
            'url_pattern' => 'https://www.2go.com.ph/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '10-12',
                'tracking_format' => '/^[A-Z0-9]{10,12}$/'
            ))
        ),
        array(
            'name' => 'Kerry Express',
            'slug' => 'kerry-express',
            'description' => 'Kerry Express - Express delivery service in Thailand',
            'url_pattern' => 'https://www.kerryexpress.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-15',
                'tracking_format' => '/^[A-Z0-9]{12,15}$/'
            ))
        ),
        array(
            'name' => 'Flash Express',
            'slug' => 'flash-express',
            'description' => 'Flash Express - Express delivery service',
            'url_pattern' => 'https://www.flashexpress.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '12-18',
                'tracking_format' => '/^[A-Z0-9]{12,18}$/'
            ))
        ),
        array(
            'name' => 'Best Inc',
            'slug' => 'best-inc',
            'description' => 'Best Inc - Chinese logistics company',
            'url_pattern' => 'https://www.best-inc.cn/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => true,
                'tracking_length' => '10-15',
                'tracking_format' => '/^[A-Z0-9]{10,15}$/'
            ))
        ),
        array(
            'name' => '4PX Express',
            'slug' => '4px-express',
            'description' => '4PX Express - Cross-border e-commerce logistics',
            'url_pattern' => 'https://www.4px.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '15-20',
                'tracking_format' => '/^[A-Z0-9]{15,20}$/'
            ))
        ),
        array(
            'name' => 'Yanwen Express',
            'slug' => 'yanwen-express',
            'description' => 'Yanwen Express - Cross-border e-commerce logistics',
            'url_pattern' => 'https://t.17track.net/en#nums={tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '15-20',
                'tracking_format' => '/^[A-Z0-9]{15,20}$/'
            ))
        ),
        array(
            'name' => 'SunYou',
            'slug' => 'sunyou',
            'description' => 'SunYou - Cross-border e-commerce logistics',
            'url_pattern' => 'https://www.sunyou.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '15-20',
                'tracking_format' => '/^[A-Z0-9]{15,20}$/'
            ))
        ),
        array(
            'name' => 'UBI Smart Parcel',
            'slug' => 'ubi-smart-parcel',
            'description' => 'UBI Smart Parcel - Cross-border e-commerce logistics',
            'url_pattern' => 'https://www.ubi-global.com/tracking/{tracking_id}',
            'tracking_format' => 'standard',
            'is_active' => 1,
            'settings' => json_encode(array(
                'requires_api_key' => false,
                'supports_international' => true,
                'supports_domestic' => false,
                'tracking_length' => '15-20',
                'tracking_format' => '/^[A-Z0-9]{15,20}$/'
            ))
        )
    );
}

/**
 * Install default couriers
 *
 * @return bool True if successful, false otherwise
 */
function aiot_install_default_couriers() {
    $default_couriers = aiot_get_default_couriers();
    $installed_count = 0;
    
    foreach ($default_couriers as $courier_data) {
        // Check if courier already exists
        $existing = AIOT_Courier_Manager::get_courier_by_slug($courier_data['slug']);
        
        if (!$existing) {
            $courier_id = AIOT_Courier_Manager::create_courier($courier_data);
            if ($courier_id) {
                $installed_count++;
            }
        }
    }
    
    return $installed_count > 0;
}