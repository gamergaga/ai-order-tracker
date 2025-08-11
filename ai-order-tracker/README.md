# AI Order Tracker

A professional WordPress plugin for order tracking with realistic simulation, stylish UI, and comprehensive admin interface. Perfect for e-commerce sites looking to provide a premium tracking experience.

## Features

### Core Functionality
- **Dual Tracking System**: Supports both fake and real tracking data
- **Realistic Simulation**: Generates believable tracking progress automatically
- **Real-time Updates**: Live tracking status updates
- **Zone-based Delivery**: Configure delivery timeframes by country, state, and city
- **Courier Management**: Support for multiple courier services
- **Stylish UI**: Modern, responsive interface similar to major e-commerce platforms

### Admin Features
- **Comprehensive Dashboard**: Complete overview of all tracking data
- **Zone Management**: Configure delivery zones with custom timeframes
- **Courier Management**: Add, edit, and manage courier services
- **Settings Panel**: Extensive configuration options
- **Real-time API**: Integration with external tracking APIs
- **Security**: Nonce validation and data sanitization

### Frontend Features
- **Tracking Form**: User-friendly tracking interface
- **Progress Visualization**: Animated progress indicators
- **Timeline Display**: Detailed package journey timeline
- **Responsive Design**: Works on all devices
- **Lottie Animations**: Smooth animations for better UX
- **Interactive Maps**: Location tracking visualization

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin
5. Configure settings in AI Order Tracker → Settings

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- WooCommerce 5.0 or higher (optional)

## Usage

### Shortcode
Use the following shortcode to display the tracking form:
```
[aiot_tracking]
```

### Template Function
```php
<?php echo do_shortcode('[aiot_tracking]'); ?>
```

### Admin Configuration
1. Go to AI Order Tracker → Settings to configure general options
2. Use AI Order Tracker → Zones to set up delivery zones
3. Manage couriers in AI Order Tracker → Couriers
4. Configure tracking behavior in the settings panel

## Database Tables

The plugin creates the following database tables:
- `wp_aiot_orders`: Order tracking data
- `wp_aiot_zones`: Delivery zone configuration
- `wp_aiot_couriers`: Courier service information
- `wp_aiot_tracking_events`: Tracking event history

## Security

- All user input is sanitized and validated
- Nonce verification for all forms
- Role-based access control
- SQL injection prevention
- XSS protection
- CSRF protection

## Performance

- Efficient database queries
- Caching system for improved performance
- Optimized asset loading
- Minimal server overhead

## Support

For support and documentation, please visit:
[Plugin Support Page](https://yourwebsite.com/support)

## Changelog

### 2.0.0
- Initial release
- Complete tracking system
- Admin interface
- Frontend tracking form
- Zone management
- Courier management
- Security enhancements

## License

This plugin is licensed under the GPL-2.0-or-later license.

## CodeCanyon Compliance

This plugin is designed to meet all CodeCanyon requirements:
- Proper code documentation
- Security best practices
- WordPress coding standards
- Performance optimization
- Cross-browser compatibility
- Mobile responsiveness
- Accessibility compliance
- Regular updates and support