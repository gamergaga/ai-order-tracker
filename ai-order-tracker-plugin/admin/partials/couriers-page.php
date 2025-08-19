<?php
/**
 * Couriers management page template
 *
 * @package AI_Order_Tracker
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aiot-admin-page">
        <!-- Statistics Cards -->
        <div class="aiot-stats-grid">
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">📦</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Total Couriers', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-total-couriers">0</div>
                </div>
            </div>
            
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">✅</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Active Couriers', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-active-couriers">0</div>
                </div>
            </div>
            
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">🌐</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('With Tracking URLs', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-url-couriers">0</div>
                </div>
            </div>
            
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">📊</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Countries Covered', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-countries-covered">0</div>
                </div>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="aiot-toolbar">
            <div class="aiot-toolbar-left">
                <button type="button" class="button button-primary" id="aiot-add-courier">
                    <?php _e('Add New Courier', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-import-default-couriers">
                    <?php _e('Import Default Couriers', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-import-csv-couriers">
                    <?php _e('Import from CSV', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-export-couriers">
                    <?php _e('Export Couriers', 'ai-order-tracker'); ?>
                </button>
            </div>
            
            <div class="aiot-toolbar-right">
                <div class="aiot-search-box">
                    <input type="text" id="aiot-search-couriers" placeholder="<?php esc_attr_e('Search couriers...', 'ai-order-tracker'); ?>">
                    <span class="aiot-search-icon">🔍</span>
                </div>
                <select id="aiot-filter-status" class="aiot-filter-select">
                    <option value="all"><?php _e('All Status', 'ai-order-tracker'); ?></option>
                    <option value="active"><?php _e('Active', 'ai-order-tracker'); ?></option>
                    <option value="inactive"><?php _e('Inactive', 'ai-order-tracker'); ?></option>
                </select>
                <select id="aiot-filter-country" class="aiot-filter-select">
                    <option value="all"><?php _e('All Countries', 'ai-order-tracker'); ?></option>
                </select>
                <button type="button" class="button" id="aiot-refresh-couriers">
                    <?php _e('Refresh', 'ai-order-tracker'); ?>
                </button>
            </div>
        </div>
        
        <!-- Couriers Table -->
        <div class="aiot-card">
            <div class="aiot-card-header">
                <h2><?php _e('Couriers List', 'ai-order-tracker'); ?></h2>
                <div class="aiot-card-actions">
                    <span class="aiot-item-count">
                        <?php _e('Showing', 'ai-order-tracker'); ?> <span id="aiot-showing-count">0</span> <?php _e('of', 'ai-order-tracker'); ?> <span id="aiot-total-count">0</span> <?php _e('couriers', 'ai-order-tracker'); ?>
                    </span>
                </div>
            </div>
            
            <div class="aiot-table-container">
                <table class="wp-list-table widefat fixed striped aiot-couriers-table">
                    <thead>
                        <tr>
                            <th class="aiot-col-checkbox">
                                <input type="checkbox" id="aiot-select-all-couriers">
                            </th>
                            <th class="aiot-col-name sortable" data-sort="name">
                                <?php _e('Name', 'ai-order-tracker'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="aiot-col-slug sortable" data-sort="slug">
                                <?php _e('Slug', 'ai-order-tracker'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="aiot-col-country">
                                <?php _e('Country', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-url">
                                <?php _e('Tracking URL', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-status">
                                <?php _e('Status', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-actions">
                                <?php _e('Actions', 'ai-order-tracker'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="aiot-couriers-tbody">
                        <tr>
                            <td colspan="7" class="aiot-loading-row">
                                <div class="aiot-spinner"></div>
                                <p><?php _e('Loading couriers...', 'ai-order-tracker'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="aiot-pagination">
                <div class="aiot-pagination-info">
                    <?php _e('Page', 'ai-order-tracker'); ?> <span id="aiot-current-page">1</span> <?php _e('of', 'ai-order-tracker'); ?> <span id="aiot-total-pages">1</span>
                </div>
                <div class="aiot-pagination-controls">
                    <button type="button" class="button" id="aiot-prev-page" disabled>
                        <?php _e('Previous', 'ai-order-tracker'); ?>
                    </button>
                    <button type="button" class="button" id="aiot-next-page">
                        <?php _e('Next', 'ai-order-tracker'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Bulk Actions -->
        <div class="aiot-bulk-actions" style="display: none;">
            <div class="aiot-bulk-info">
                <span id="aiot-selected-count">0</span> <?php _e('couriers selected', 'ai-order-tracker'); ?>
            </div>
            <div class="aiot-bulk-buttons">
                <select id="aiot-bulk-action" class="aiot-bulk-select">
                    <option value=""><?php _e('Bulk Actions', 'ai-order-tracker'); ?></option>
                    <option value="activate"><?php _e('Activate', 'ai-order-tracker'); ?></option>
                    <option value="deactivate"><?php _e('Deactivate', 'ai-order-tracker'); ?></option>
                    <option value="delete"><?php _e('Delete', 'ai-order-tracker'); ?></option>
                </select>
                <button type="button" class="button" id="aiot-apply-bulk-action">
                    <?php _e('Apply', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-cancel-bulk-action">
                    <?php _e('Cancel', 'ai-order-tracker'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Courier Modal -->
<div id="aiot-courier-modal" class="aiot-modal">
    <div class="aiot-modal-content">
        <div class="aiot-modal-header">
            <h2 id="aiot-modal-title"><?php _e('Add New Courier', 'ai-order-tracker'); ?></h2>
            <button type="button" class="aiot-modal-close">&times;</button>
        </div>
        <form id="aiot-courier-form" method="post">
            <div class="aiot-form-body">
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-courier-name"><?php _e('Name', 'ai-order-tracker'); ?> *</label>
                        <input type="text" id="aiot-courier-name" name="name" required>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-slug"><?php _e('Slug', 'ai-order-tracker'); ?> *</label>
                        <input type="text" id="aiot-courier-slug" name="slug" required>
                        <p class="description"><?php _e('URL-friendly identifier', 'ai-order-tracker'); ?></p>
                    </div>
                </div>
                
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-courier-description"><?php _e('Description', 'ai-order-tracker'); ?></label>
                        <textarea id="aiot-courier-description" name="description" rows="3"></textarea>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-tracking-format"><?php _e('Tracking Format', 'ai-order-tracker'); ?></label>
                        <select id="aiot-courier-tracking-format" name="tracking_format">
                            <option value="standard"><?php _e('Standard', 'ai-order-tracker'); ?></option>
                            <option value="ups"><?php _e('UPS', 'ai-order-tracker'); ?></option>
                            <option value="fedex"><?php _e('FedEx', 'ai-order-tracker'); ?></option>
                            <option value="dhl"><?php _e('DHL', 'ai-order-tracker'); ?></option>
                            <option value="usps"><?php _e('USPS', 'ai-order-tracker'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-courier-url-pattern"><?php _e('URL Pattern', 'ai-order-tracker'); ?></label>
                        <input type="url" id="aiot-courier-url-pattern" name="url_pattern">
                        <p class="description"><?php _e('Use {tracking_id} as placeholder. Example: https://tracker.com/?id={tracking_id}', 'ai-order-tracker'); ?></p>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-api-endpoint"><?php _e('API Endpoint', 'ai-order-tracker'); ?></label>
                        <input type="url" id="aiot-courier-api-endpoint" name="api_endpoint">
                        <p class="description"><?php _e('Optional: API endpoint for real-time tracking', 'ai-order-tracker'); ?></p>
                    </div>
                </div>
                
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-courier-phone"><?php _e('Phone', 'ai-order-tracker'); ?></label>
                        <input type="tel" id="aiot-courier-phone" name="phone">
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-website"><?php _e('Website', 'ai-order-tracker'); ?></label>
                        <input type="url" id="aiot-courier-website" name="website">
                    </div>
                </div>
                
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-courier-country"><?php _e('Country', 'ai-order-tracker'); ?></label>
                        <select id="aiot-courier-country" name="country">
                            <option value=""><?php _e('Select Country', 'ai-order-tracker'); ?></option>
                            <?php
                            $countries = aiot_get_countries_list();
                            foreach ($countries as $code => $name) :
                            ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-courier-type"><?php _e('Type', 'ai-order-tracker'); ?></label>
                        <select id="aiot-courier-type" name="type">
                            <option value="express"><?php _e('Express', 'ai-order-tracker'); ?></option>
                            <option value="standard"><?php _e('Standard', 'ai-order-tracker'); ?></option>
                            <option value="economy"><?php _e('Economy', 'ai-order-tracker'); ?></option>
                            <option value="freight"><?php _e('Freight', 'ai-order-tracker'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="aiot-form-group">
                    <label for="aiot-courier-image"><?php _e('Logo URL', 'ai-order-tracker'); ?></label>
                    <input type="url" id="aiot-courier-image" name="image">
                    <p class="description"><?php _e('Optional: URL to courier logo image', 'ai-order-tracker'); ?></p>
                </div>
                
                <div class="aiot-form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" checked>
                        <?php _e('Active', 'ai-order-tracker'); ?>
                    </label>
                    <p class="description"><?php _e('Enable this courier for tracking', 'ai-order-tracker'); ?></p>
                </div>
                
                <div class="aiot-form-group">
                    <label for="aiot-courier-settings"><?php _e('Additional Settings', 'ai-order-tracker'); ?></label>
                    <textarea id="aiot-courier-settings" name="settings" rows="5" class="aiot-json-editor"></textarea>
                    <p class="description"><?php _e('JSON format for additional settings', 'ai-order-tracker'); ?></p>
                </div>
            </div>
            
            <div class="aiot-modal-footer">
                <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Courier', 'ai-order-tracker'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- CSV Import Modal -->
<div id="aiot-csv-import-modal" class="aiot-modal">
    <div class="aiot-modal-content aiot-modal-large">
        <div class="aiot-modal-header">
            <h2><?php _e('Import Couriers from CSV', 'ai-order-tracker'); ?></h2>
            <button type="button" class="aiot-modal-close">&times;</button>
        </div>
        <form id="aiot-csv-import-form" method="post" enctype="multipart/form-data">
            <div class="aiot-form-body">
                <div class="aiot-form-group">
                    <label for="aiot-csv-file"><?php _e('CSV File', 'ai-order-tracker'); ?> *</label>
                    <input type="file" id="aiot-csv-file" name="csv_file" accept=".csv" required>
                    <p class="description"><?php _e('Select a CSV file containing courier data', 'ai-order-tracker'); ?></p>
                </div>
                
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label>
                            <input type="checkbox" name="overwrite_existing" value="1">
                            <?php _e('Overwrite Existing Couriers', 'ai-order-tracker'); ?>
                        </label>
                        <p class="description"><?php _e('Update existing couriers with the same slug', 'ai-order-tracker'); ?></p>
                    </div>
                    <div class="aiot-form-group">
                        <label>
                            <input type="checkbox" name="skip_inactive" value="1" checked>
                            <?php _e('Skip Inactive Couriers', 'ai-order-tracker'); ?>
                        </label>
                        <p class="description"><?php _e('Skip couriers without valid tracking URLs', 'ai-order-tracker'); ?></p>
                    </div>
                </div>
                
                <div class="aiot-form-group">
                    <h3><?php _e('CSV Format Requirements', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-format-info">
                        <p><?php _e('Your CSV file should include the following columns:', 'ai-order-tracker'); ?></p>
                        <ul>
                            <li><strong>Name:</strong> <?php _e('Courier name (required)', 'ai-order-tracker'); ?></li>
                            <li><strong>Slug:</strong> <?php _e('URL-friendly identifier (required)', 'ai-order-tracker'); ?></li>
                            <li><strong>Phone:</strong> <?php _e('Contact phone number', 'ai-order-tracker'); ?></li>
                            <li><strong>Website:</strong> <?php _e('Company website URL', 'ai-order-tracker'); ?></li>
                            <li><strong>Type:</strong> <?php _e('Courier type (express/standard/economy)', 'ai-order-tracker'); ?></li>
                            <li><strong>Image:</strong> <?php _e('Logo URL (optional)', 'ai-order-tracker'); ?></li>
                            <li><strong>Country:</strong> <?php _e('Country code (US, UK, DE, etc.)', 'ai-order-tracker'); ?></li>
                            <li><strong>URL Pattern:</strong> <?php _e('Tracking URL with {tracking_id} placeholder', 'ai-order-tracker'); ?></li>
                            <li><strong>Local Name:</strong> <?php _e('Localized name (optional)', 'ai-order-tracker'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="aiot-modal-footer">
                <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Import Couriers', 'ai-order-tracker'); ?></button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize couriers page
    aiotAdminCouriers.init();
});
</script>