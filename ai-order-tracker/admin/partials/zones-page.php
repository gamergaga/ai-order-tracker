<?php
/**
 * Zones management page template
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
                <div class="aiot-stat-icon">🌍</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Total Zones', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-total-zones">0</div>
                </div>
            </div>
            
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">✅</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Active Zones', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-active-zones">0</div>
                </div>
            </div>
            
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">📦</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Countries Covered', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-countries-covered">0</div>
                </div>
            </div>
            
            <div class="aiot-stat-card">
                <div class="aiot-stat-icon">🚚</div>
                <div class="aiot-stat-content">
                    <h3><?php _e('Avg. Delivery Days', 'ai-order-tracker'); ?></h3>
                    <div class="aiot-stat-number" id="aiot-avg-delivery-days">0</div>
                </div>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="aiot-toolbar">
            <div class="aiot-toolbar-left">
                <button type="button" class="button button-primary" id="aiot-add-zone">
                    <?php _e('Add New Zone', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-install-default-zones">
                    <?php _e('Install Default Zones', 'ai-order-tracker'); ?>
                </button>
                <button type="button" class="button" id="aiot-export-zones">
                    <?php _e('Export Zones', 'ai-order-tracker'); ?>
                </button>
            </div>
            
            <div class="aiot-toolbar-right">
                <div class="aiot-search-box">
                    <input type="text" id="aiot-search-zones" placeholder="<?php esc_attr_e('Search zones...', 'ai-order-tracker'); ?>">
                    <span class="aiot-search-icon">🔍</span>
                </div>
                <select id="aiot-filter-status" class="aiot-filter-select">
                    <option value="all"><?php _e('All Status', 'ai-order-tracker'); ?></option>
                    <option value="active"><?php _e('Active', 'ai-order-tracker'); ?></option>
                    <option value="inactive"><?php _e('Inactive', 'ai-order-tracker'); ?></option>
                </select>
                <select id="aiot-filter-type" class="aiot-filter-select">
                    <option value="all"><?php _e('All Types', 'ai-order-tracker'); ?></option>
                    <option value="country"><?php _e('Country', 'ai-order-tracker'); ?></option>
                    <option value="state"><?php _e('State', 'ai-order-tracker'); ?></option>
                    <option value="city"><?php _e('City', 'ai-order-tracker'); ?></option>
                </select>
                <button type="button" class="button" id="aiot-refresh-zones">
                    <?php _e('Refresh', 'ai-order-tracker'); ?>
                </button>
            </div>
        </div>
        
        <!-- Zones Table -->
        <div class="aiot-card">
            <div class="aiot-card-header">
                <h2><?php _e('Zones List', 'ai-order-tracker'); ?></h2>
                <div class="aiot-card-actions">
                    <span class="aiot-item-count">
                        <?php _e('Showing', 'ai-order-tracker'); ?> <span id="aiot-showing-count">0</span> <?php _e('of', 'ai-order-tracker'); ?> <span id="aiot-total-count">0</span> <?php _e('zones', 'ai-order-tracker'); ?>
                    </span>
                </div>
            </div>
            
            <div class="aiot-table-container">
                <table class="wp-list-table widefat fixed striped aiot-zones-table">
                    <thead>
                        <tr>
                            <th class="aiot-col-checkbox">
                                <input type="checkbox" id="aiot-select-all-zones">
                            </th>
                            <th class="aiot-col-name sortable" data-sort="name">
                                <?php _e('Zone Name', 'ai-order-tracker'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="aiot-col-type">
                                <?php _e('Type', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-delivery">
                                <?php _e('Delivery Days', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-countries">
                                <?php _e('Countries', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-status">
                                <?php _e('Status', 'ai-order-tracker'); ?>
                            </th>
                            <th class="aiot-col-actions">
                                <?php _e('Actions', 'ai-order-tracker'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="aiot-zones-tbody">
                        <tr>
                            <td colspan="7" class="aiot-loading-row">
                                <div class="aiot-spinner"></div>
                                <p><?php _e('Loading zones...', 'ai-order-tracker'); ?></p>
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
                <span id="aiot-selected-count">0</span> <?php _e('zones selected', 'ai-order-tracker'); ?>
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

<!-- Zone Modal -->
<div id="aiot-zone-modal" class="aiot-modal">
    <div class="aiot-modal-content">
        <div class="aiot-modal-header">
            <h2 id="aiot-modal-title"><?php _e('Add New Zone', 'ai-order-tracker'); ?></h2>
            <button type="button" class="aiot-modal-close">&times;</button>
        </div>
        <form id="aiot-zone-form" method="post">
            <div class="aiot-form-body">
                <!-- Zone Map at the Top -->
                <div class="aiot-form-group aiot-map-container">
                    <label><?php _e('Zone Map', 'ai-order-tracker'); ?></label>
                    <div id="aiot-zone-map" class="aiot-zone-map"></div>
                    <input type="hidden" id="aiot-zone-coordinates" name="coordinates">
                </div>
                
                <!-- Form Fields in 2x2 Grid -->
                <div class="aiot-form-grid">
                    <div class="aiot-form-group">
                        <label for="aiot-zone-name"><?php _e('Zone Name', 'ai-order-tracker'); ?> *</label>
                        <input type="text" id="aiot-zone-name" name="name" required>
                    </div>
                    
                    <div class="aiot-form-group">
                        <label for="aiot-zone-type"><?php _e('Zone Type', 'ai-order-tracker'); ?></label>
                        <select id="aiot-zone-type" name="type">
                            <option value="country"><?php _e('Country', 'ai-order-tracker'); ?></option>
                            <option value="state"><?php _e('State/Province/Governate', 'ai-order-tracker'); ?></option>
                        </select>
                    </div>
                    
                    <div class="aiot-form-group">
                        <label for="aiot-zone-delivery-days-min"><?php _e('Minimum Delivery Days', 'ai-order-tracker'); ?> *</label>
                        <select id="aiot-zone-delivery-days-min" name="delivery_days_min" required>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="aiot-form-group">
                        <label for="aiot-zone-delivery-days-max"><?php _e('Maximum Delivery Days', 'ai-order-tracker'); ?> *</label>
                        <select id="aiot-zone-delivery-days-max" name="delivery_days_max" required>
                            <?php for ($i = 1; $i <= 100; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Processing Days -->
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-zone-processing-days"><?php _e('Processing Days', 'ai-order-tracker'); ?></label>
                        <select id="aiot-zone-processing-days" name="processing_days">
                            <?php for ($i = 0; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 1 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Location Selection -->
                <div class="aiot-form-row">
                    <div class="aiot-form-group">
                        <label for="aiot-zone-country"><?php _e('Country', 'ai-order-tracker'); ?></label>
                        <select id="aiot-zone-country" name="country">
                            <option value=""><?php _e('Select Country', 'ai-order-tracker'); ?></option>
                            <?php
                            $countries = AIOT_Zone_Manager::get_countries_data();
                            foreach ($countries as $country) {
                                echo '<option value="' . esc_attr($country['code']) . '">' . esc_html($country['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="aiot-form-group">
                        <label for="aiot-zone-state"><?php _e('State/Province/Governate', 'ai-order-tracker'); ?></label>
                        <select id="aiot-zone-state" name="state" disabled>
                            <option value=""><?php _e('Select State', 'ai-order-tracker'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="aiot-form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" checked>
                        <?php _e('Active', 'ai-order-tracker'); ?>
                    </label>
                    <p class="description"><?php _e('Enable this zone for delivery', 'ai-order-tracker'); ?></p>
                </div>
                
                <input type="hidden" id="aiot-zone-id" name="zone_id" value="0">
            </div>
            
            <div class="aiot-modal-footer">
                <button type="button" class="button aiot-modal-cancel"><?php _e('Cancel', 'ai-order-tracker'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Zone', 'ai-order-tracker'); ?></button>
            </div>
        </form>
    </div>
</div>