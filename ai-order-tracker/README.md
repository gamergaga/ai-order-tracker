# AI Order Tracker - WordPress Plugin

A professional order tracking system for WordPress with realistic simulation, stylish UI, and comprehensive admin interface. Perfect for e-commerce sites looking to provide a premium tracking experience.

## Features

### Core Functionality
- **Real-time Order Tracking**: Track orders in real-time with automatic status updates
- **Multiple Courier Support**: Support for major international couriers (UPS, FedEx, DHL, USPS, etc.)
- **Direct Tracking**: Get tracking information directly from courier websites without requiring API keys
- **Hybrid Tracking System**: Combines simulated tracking with real courier tracking
- **WooCommerce Integration**: Seamless integration with WooCommerce orders
- **Custom Tracking IDs**: Generate custom tracking IDs with various formats

### User Interface
- **Responsive Design**: Works perfectly on all devices
- **Interactive Tracking Page**: Beautiful tracking interface with progress indicators
- **Real-time Updates**: Live tracking updates without page refresh
- **Multiple Themes**: Choose from different tracking themes
- **Animations**: Smooth animations for tracking progress
- **Map Integration**: Show delivery route on map (when available)

### Admin Features
- **Comprehensive Admin Panel**: Full-featured admin interface
- **Courier Management**: Add, edit, and manage courier services
- **Zone Management**: Create delivery zones with custom settings
- **Order Management**: View and manage all tracked orders
- **Import/Export**: Import courier data from CSV, export for backup
- **Settings Control**: Fine-tune all plugin settings

### Technical Features
- **No API Required**: Works without external API keys
- **Web Scraping**: Intelligent web scraping for real tracking data
- **Caching System**: Efficient caching for better performance
- **Security First**: Built with security best practices
- **SEO Friendly**: Optimized for search engines
- **Translation Ready**: Full WPML and multilingual support

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin
5. Configure settings in AI Order Tracker → Settings

## Quick Start

1. **Configure Settings**: Go to AI Order Tracker → Settings and configure your preferences
2. **Add Couriers**: The plugin automatically imports major couriers. Add more if needed.
3. **Create Tracking**: Use the tracking shortcode to add tracking to your pages
4. **Track Orders**: Enter tracking ID and select courier to track orders

## Shortcodes

### Basic Tracking
```
[aiot_tracking]
```

### Simple Tracking
```
[aiot_simple_tracking]
```

### Tracking with Default Courier
```
[aiot_tracking courier="ups"]
```

## Courier Support

The plugin supports tracking for major couriers including:
- UPS (United Parcel Service)
- FedEx (Federal Express)
- DHL Express
- USPS (United States Postal Service)
- Royal Mail (UK)
- Canada Post
- Australia Post
- Japan Post
- DPD
- Hermes
- And many more...

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher (optional, for integration)

## Security & Compliance

### Security Features
- **Nonce Protection**: All AJAX requests protected with nonces
- **Input Validation**: All user inputs are validated and sanitized
- **Capability Checks**: Proper user capability verification
- **SQL Injection Prevention**: Prepared statements for all database operations
- **XSS Prevention**: Output escaping and content sanitization

### CodeCanyon Compliance
- **Original Code**: 100% original code, no copyrighted material
- **Proper Licensing**: GPL-2.0+ compliant license
- **Documentation**: Comprehensive documentation included
- **Support**: Professional support included
- **Updates**: Regular updates and improvements
- **Privacy**: No user data collected or shared

### Data Privacy
- **No External Dependencies**: Works without external services
- **Local Processing**: All tracking data processed locally
- **No Data Collection**: Does not collect or store user data
- **GDPR Compliant**: Fully compliant with GDPR regulations

## Customization

### Themes
The plugin includes multiple themes that can be customized:
- Modern Theme
- Classic Theme
- Minimal Theme
- Dark Theme

### Custom CSS
You can add custom CSS to override default styles:
```css
.aiot-tracking-container {
    /* Your custom styles */
}
```

### Custom JavaScript
Add custom JavaScript using the provided hooks:
```javascript
jQuery(document).on('aiotTrackingLoaded', function() {
    // Your custom code
});
```

## API & Hooks

### Actions
- `aiot_before_tracking_display`: Before tracking results are displayed
- `aiot_after_tracking_display`: After tracking results are displayed
- `aiot_tracking_status_updated`: When tracking status is updated

### Filters
- `aiot_tracking_result`: Filter tracking results
- `aiot_courier_list`: Filter courier list
- `aiot_tracking_url`: Filter tracking URLs

## Troubleshooting

### Common Issues

**Tracking not working**
- Check if courier is supported
- Verify tracking ID format
- Ensure website can access external URLs

**Couriers not showing**
- Check courier settings in admin
- Verify courier is active
- Re-import courier data

**Style issues**
- Check theme compatibility
- Clear browser cache
- Check for CSS conflicts

### Debug Mode
Enable debug mode in settings to see detailed error messages.

## Support

For support, please use the official support channels:
- Documentation: Included with plugin
- Support Forum: Available on CodeCanyon
- Email Support: Contact through CodeCanyon profile

## Changelog

### Version 2.0.0
- Added direct tracking functionality
- Improved courier management
- Enhanced security features
- Added new tracking themes
- Better WooCommerce integration
- Performance improvements

### Version 1.0.0
- Initial release
- Basic tracking functionality
- Admin interface
- Shortcode support

## License

This plugin is licensed under the GPL-2.0+ license.

## Credits

Developed by [Your Name]
Icons and graphics created specifically for this plugin
All courier data is publicly available information

## Disclaimer

This plugin uses web scraping to gather tracking information from courier websites. While we strive to maintain compatibility, courier websites may change their structure without notice, which could affect tracking functionality. We recommend using official courier APIs when available for mission-critical applications.