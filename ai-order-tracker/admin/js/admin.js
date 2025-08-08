/**
 * AI Order Tracker Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        aiotAdmin.init();
    });

    // Main admin object
    var aiotAdmin = {
        init: function() {
            this.initModals();
            this.initForms();
            this.initTables();
            this.initColorPickers();
            this.initTooltips();
            this.initConfirmations();
            this.initAjax();
        },

        initModals: function() {
            // Modal open/close functionality
            $(document).on('click', '.aiot-modal-open', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                $('#' + modalId).fadeIn();
            });

            $(document).on('click', '.aiot-modal-close', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            $(document).on('click', '.aiot-modal', function(e) {
                if ($(e.target).hasClass('aiot-modal')) {
                    $(this).fadeOut();
                }
            });
        },

        initForms: function() {
            // Form validation
            $(document).on('submit', '.aiot-form', function(e) {
                var form = $(this);
                var isValid = true;

                // Remove previous error states
                form.find('.aiot-form-error').remove();
                form.find('.aiot-field-error').removeClass('aiot-field-error');

                // Validate required fields
                form.find('[required]').each(function() {
                    var field = $(this);
                    if (!field.val().trim()) {
                        field.addClass('aiot-field-error');
                        field.after('<span class="aiot-form-error">' + aiot_admin.strings.field_required + '</span>');
                        isValid = false;
                    }
                });

                // Validate email fields
                form.find('input[type="email"]').each(function() {
                    var field = $(this);
                    var email = field.val().trim();
                    if (email && !aiotAdmin.isValidEmail(email)) {
                        field.addClass('aiot-field-error');
                        field.after('<span class="aiot-form-error">' + aiot_admin.strings.invalid_email + '</span>');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });

            // Clear error on input
            $(document).on('input', '.aiot-field-error', function() {
                $(this).removeClass('aiot-field-error');
                $(this).next('.aiot-form-error').remove();
            });
        },

        initTables: function() {
            // Initialize DataTables if available
            if ($.fn.DataTable) {
                $('.aiot-data-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: aiot_admin.strings.search
                    }
                });
            }

            // Table row actions
            $(document).on('click', '.aiot-row-action', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                var rowId = $(this).data('id');
                
                switch (action) {
                    case 'edit':
                        aiotAdmin.editRow(rowId);
                        break;
                    case 'delete':
                        aiotAdmin.deleteRow(rowId);
                        break;
                    case 'view':
                        aiotAdmin.viewRow(rowId);
                        break;
                }
            });

            // Bulk actions
            $(document).on('change', '.aiot-bulk-action', function() {
                var action = $(this).val();
                if (action) {
                    aiotAdmin.handleBulkAction(action);
                }
            });
        },

        initColorPickers: function() {
            // Initialize color pickers
            $('.aiot-color-picker').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    $(this).val(color);
                    aiotAdmin.updatePreviewColor(color);
                }
            });
        },

        initTooltips: function() {
            // Initialize tooltips
            $('.aiot-tooltip').tooltip({
                position: {
                    my: 'center bottom-10',
                    at: 'center top'
                },
                tooltipClass: 'aiot-tooltip-content'
            });
        },

        initConfirmations: function() {
            // Confirmation dialogs
            $(document).on('click', '.aiot-confirm', function(e) {
                var message = $(this).data('confirm') || aiot_admin.strings.confirm_delete;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        initAjax: function() {
            // AJAX form submissions
            $(document).on('submit', '.aiot-ajax-form', function(e) {
                e.preventDefault();
                aiotAdmin.submitAjaxForm($(this));
            });

            // AJAX actions
            $(document).on('click', '.aiot-ajax-action', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                var data = $(this).data('data') || {};
                aiotAdmin.performAjaxAction(action, data);
            });
        },

        submitAjaxForm: function(form) {
            var formData = form.serialize();
            var action = form.data('action');
            var button = form.find('button[type="submit"]');
            var originalText = button.html();

            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> ' + aiot_admin.strings.saving);

            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + aiot_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        performAjaxAction: function(action, data) {
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: aiot_admin.nonce,
                    data: data
                },
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                        if (response.data.reload) {
                            location.reload();
                        }
                        if (response.data.callback) {
                            window[response.data.callback](response.data);
                        }
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                }
            });
        },

        editRow: function(rowId) {
            // Open edit modal
            $('#aiot-edit-modal').data('id', rowId).fadeIn();
            
            // Load row data
            aiotAdmin.performAjaxAction('aiot_get_row_data', { id: rowId });
        },

        deleteRow: function(rowId) {
            if (confirm(aiot_admin.strings.confirm_delete)) {
                aiotAdmin.performAjaxAction('aiot_delete_row', { id: rowId });
            }
        },

        viewRow: function(rowId) {
            // Open view modal
            $('#aiot-view-modal').data('id', rowId).fadeIn();
            
            // Load row data
            aiotAdmin.performAjaxAction('aiot_get_row_data', { id: rowId });
        },

        handleBulkAction: function(action) {
            var selectedIds = [];
            $('.aiot-bulk-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                aiotAdmin.showNotice('warning', aiot_admin.strings.no_items_selected);
                return;
            }

            if (confirm(aiot_admin.strings.confirm_bulk_action)) {
                aiotAdmin.performAjaxAction('aiot_bulk_action', {
                    action: action,
                    ids: selectedIds
                });
            }
        },

        updatePreviewColor: function(color) {
            // Update preview elements with new color
            $('.aiot-preview-primary').css('background-color', color);
        },

        showNotice: function(type, message) {
            var notice = $('<div class="aiot-notice aiot-notice-' + type + '">' + message + '</div>');
            
            // Insert notice at the top of the page
            $('.wrap h1').after(notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        formatNumber: function(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString();
        },

        debounce: function(func, wait) {
            var timeout;
            return function executedFunction(...args) {
                var later = function() {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Extend strings object
    if (typeof aiot_admin.strings === 'undefined') {
        aiot_admin.strings = {
            confirm_delete: 'Are you sure you want to delete this item?',
            confirm_bulk_action: 'Are you sure you want to perform this action on selected items?',
            saving: 'Saving...',
            saved: 'Saved',
            loading: 'Loading...',
            error: 'An error occurred. Please try again.',
            search: 'Search...',
            field_required: 'This field is required',
            invalid_email: 'Please enter a valid email address',
            no_items_selected: 'No items selected'
        };
    }

    // Initialize order management
    aiotAdmin.orderManager = {
        init: function() {
            this.initOrderList();
            this.initOrderForm();
            this.initModals();
            this.initWooCommerceIntegration();
        },

        initModals: function() {
            var self = this;
            
            // Add order button
            $(document).on('click', '#aiot-add-order', function(e) {
                e.preventDefault();
                self.openOrderModal();
            });

            // Fetch WooCommerce orders button
            $(document).on('click', '#aiot-fetch-woocommerce-orders', function(e) {
                e.preventDefault();
                self.fetchWooCommerceOrders();
            });

            // Modal close buttons
            $(document).on('click', '.aiot-modal-close', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            $(document).on('click', '.aiot-modal-cancel', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            // Modal background click
            $(document).on('click', '.aiot-modal', function(e) {
                if ($(e.target).hasClass('aiot-modal')) {
                    $(this).fadeOut();
                }
            });

            // Form submission
            $(document).on('submit', '#aiot-order-form', function(e) {
                e.preventDefault();
                self.saveOrder($(this));
            });
        },

        openOrderModal: function(orderId) {
            var modal = $('#aiot-order-modal');
            var title = $('#aiot-modal-title');
            
            if (orderId) {
                title.text('<?php _e('Edit Order', 'ai-order-tracker'); ?>');
                this.loadOrderData(orderId);
            } else {
                title.text('<?php _e('Add New Order', 'ai-order-tracker'); ?>');
                this.resetOrderForm();
            }
            
            modal.fadeIn();
        },

        resetOrderForm: function() {
            $('#aiot-order-form')[0].reset();
            $('#aiot-order-id').val('0');
        },

        fetchWooCommerceOrders: function() {
            var button = $('#aiot-fetch-woocommerce-orders');
            var originalText = button.html();
            
            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> <?php _e('Fetching...', 'ai-order-tracker'); ?>');
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_fetch_woocommerce_orders',
                    nonce: aiot_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        saveOrder: function(form) {
            var formData = form.serialize();
            var button = form.find('button[type="submit"]');
            var originalText = button.html();

            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> <?php _e('Saving...', 'ai-order-tracker'); ?>');

            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: formData + '&action=aiot_save_order&nonce=' + aiot_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        }
    };

    // Initialize zone management
    aiotAdmin.zoneManager = {
        init: function() {
            this.initZoneList();
            this.initZoneForm();
            this.initModals();
            this.initTagInputs();
            this.map = null;
            this.markers = [];
        },

        initModals: function() {
            var self = this;
            
            // Add zone button
            $(document).on('click', '#aiot-add-zone', function(e) {
                e.preventDefault();
                self.openZoneModal();
            });

            // Edit zone button
            $(document).on('click', '.aiot-zone-edit', function(e) {
                e.preventDefault();
                var zoneId = $(this).data('id');
                self.editZone(zoneId);
            });

            // Delete zone button
            $(document).on('click', '.aiot-zone-delete', function(e) {
                e.preventDefault();
                var zoneId = $(this).data('id');
                self.deleteZone(zoneId);
            });

            // Modal close buttons
            $(document).on('click', '.aiot-modal-close', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            $(document).on('click', '.aiot-modal-cancel', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            // Modal background click
            $(document).on('click', '.aiot-modal', function(e) {
                if ($(e.target).hasClass('aiot-modal')) {
                    $(this).fadeOut();
                }
            });

            // Form submission
            $(document).on('submit', '#aiot-zone-form', function(e) {
                e.preventDefault();
                self.saveZone($(this));
            });

            // Refresh button
            $(document).on('click', '#aiot-refresh-zones', function(e) {
                e.preventDefault();
                self.loadZones();
            });

            // Active only checkbox
            $(document).on('change', '#aiot-active-only', function(e) {
                self.loadZones();
            });
        },

        openZoneModal: function(zoneId) {
            var modal = $('#aiot-zone-modal');
            var title = $('#aiot-modal-title');
            
            if (zoneId) {
                title.text('<?php _e('Edit Zone', 'ai-order-tracker'); ?>');
                this.loadZoneData(zoneId);
            } else {
                title.text('<?php _e('Add New Zone', 'ai-order-tracker'); ?>');
                this.resetZoneForm();
            }
            
            modal.fadeIn();
            
            // Initialize map after modal is shown
            setTimeout(function() {
                aiotAdmin.zoneManager.initMap();
            }, 300);
        },

        resetZoneForm: function() {
            $('#aiot-zone-form')[0].reset();
            $('#aiot-zone-id').val('0');
            $('.aiot-tag-list').empty();
            this.clearMap();
        },

        initMap: function() {
            if (typeof L === 'undefined' || $('#aiot-zone-map').length === 0) {
                return;
            }

            // Clear existing map
            if (this.map) {
                this.map.remove();
            }

            // Initialize map
            this.map = L.map('aiot-zone-map').setView([20, 0], 2);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            // Hide loading message
            $('.aiot-map-loading').hide();

            // Add click event
            var self = this;
            this.map.on('click', function(e) {
                self.handleMapClick(e);
            });
        },

        handleMapClick: function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            
            // Add marker
            this.addMapMarker(lat, lng);
            
            // Update coordinates field
            this.updateCoordinatesField(lat, lng);
        },

        addMapMarker: function(lat, lng) {
            if (!this.map) return;

            var marker = L.marker([lat, lng]).addTo(this.map);
            marker.bindPopup('<?php _e('Zone Boundary', 'ai-order-tracker'); ?>').openPopup();
            
            this.markers.push(marker);
        },

        updateCoordinatesField: function(lat, lng) {
            var coordinatesField = $('#aiot-zone-coordinates');
            var coordinates = JSON.parse(coordinatesField.val() || '[]');
            
            coordinates.push({ lat: lat, lng: lng });
            
            coordinatesField.val(JSON.stringify(coordinates));
        },

        clearMap: function() {
            if (this.map) {
                this.markers.forEach(function(marker) {
                    this.map.removeLayer(marker);
                }, this);
                this.markers = [];
            }
            $('#aiot-zone-coordinates').val('');
        },

        initTagInputs: function() {
            var self = this;
            
            // Countries tag input
            this.initTagInput('aiot-zone-countries', 'countries');
            
            // States tag input
            this.initTagInput('aiot-zone-states', 'states');
            
            // Cities tag input
            this.initTagInput('aiot-zone-cities', 'cities');
        },

        initTagInput: function(inputId, fieldName) {
            var self = this;
            var input = $('#' + inputId + '-input');
            var tagsContainer = $('#' + inputId + '-tags');
            var hiddenField = $('#' + inputId);

            input.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    var value = $(this).val().trim();
                    if (value) {
                        self.addTag(value, tagsContainer, hiddenField);
                        $(this).val('');
                    }
                }
            });

            // Remove tag on click
            tagsContainer.on('click', '.aiot-tag-remove', function() {
                $(this).closest('.aiot-tag').remove();
                self.updateHiddenField(tagsContainer, hiddenField);
            });
        },

        addTag: function(value, container, hiddenField) {
            var tag = $('<span class="aiot-tag">' + value + '<button type="button" class="aiot-tag-remove">&times;</button></span>');
            container.append(tag);
            this.updateHiddenField(container, hiddenField);
        },

        updateHiddenField: function(container, hiddenField) {
            var tags = [];
            container.find('.aiot-tag').each(function() {
                tags.push($(this).text().replace('×', '').trim());
            });
            hiddenField.val(JSON.stringify(tags));
        },

        loadZones: function() {
            var self = this;
            var activeOnly = $('#aiot-active-only').is(':checked');
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'GET',
                data: {
                    action: 'aiot_admin_get_zones',
                    nonce: aiot_admin.nonce,
                    active_only: activeOnly
                },
                success: function(response) {
                    if (response.success) {
                        self.renderZones(response.data);
                    }
                }
            });
        },

        renderZones: function(zones) {
            var container = $('#aiot-zones-container');
            var html = '';
            
            if (zones.length === 0) {
                html = '<div class="aiot-no-zones"><?php _e('No zones found.', 'ai-order-tracker'); ?></div>';
            } else {
                html = '<div class="aiot-zones-grid">';
                zones.forEach(function(zone) {
                    html += self.renderZoneCard(zone);
                });
                html += '</div>';
            }
            
            container.html(html);
        },

        renderZoneCard: function(zone) {
            var html = '<div class="aiot-zone-card ' + (zone.is_active ? 'active' : 'inactive') + '">';
            html += '<div class="aiot-zone-card-header">';
            html += '<h3>' + zone.name + '</h3>';
            html += '<div class="aiot-zone-actions">';
            html += '<button type="button" class="button aiot-zone-edit" data-id="' + zone.id + '"><?php _e('Edit', 'ai-order-tracker'); ?></button>';
            html += '<button type="button" class="button aiot-zone-delete" data-id="' + zone.id + '"><?php _e('Delete', 'ai-order-tracker'); ?></button>';
            html += '</div>';
            html += '</div>';
            html += '<div class="aiot-zone-card-body">';
            html += '<div class="aiot-zone-info">';
            html += '<div class="aiot-zone-info-item">';
            html += '<strong><?php _e('Type:', 'ai-order-tracker'); ?></strong> ' + zone.type;
            html += '</div>';
            html += '<div class="aiot-zone-info-item">';
            html += '<strong><?php _e('Delivery Days:', 'ai-order-tracker'); ?></strong> ' + zone.delivery_days;
            html += '</div>';
            html += '<div class="aiot-zone-info-item">';
            html += '<strong><?php _e('Delivery Cost:', 'ai-order-tracker'); ?></strong> $' + zone.delivery_cost;
            html += '</div>';
            html += '<div class="aiot-zone-info-item">';
            html += '<strong><?php _e('Status:', 'ai-order-tracker'); ?></strong> ' + (zone.is_active ? '<?php _e('Active', 'ai-order-tracker'); ?>' : '<?php _e('Inactive', 'ai-order-tracker'); ?>');
            html += '</div>';
            html += '</div>';
            if (zone.description) {
                html += '<div class="aiot-zone-description">';
                html += '<p>' + zone.description + '</p>';
                html += '</div>';
            }
            html += '</div>';
            html += '</div>';
            
            return html;
        },

        loadZoneData: function(zoneId) {
            var self = this;
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_admin_get_zone',
                    nonce: aiot_admin.nonce,
                    id: zoneId
                },
                success: function(response) {
                    if (response.success) {
                        self.populateZoneForm(response.data);
                    }
                }
            });
        },

        populateZoneForm: function(zone) {
            $('#aiot-zone-id').val(zone.id);
            $('#aiot-zone-name').val(zone.name);
            $('#aiot-zone-description').val(zone.description);
            $('#aiot-zone-type').val(zone.type);
            $('#aiot-zone-delivery-days').val(zone.delivery_days);
            $('#aiot-zone-delivery-cost').val(zone.delivery_cost);
            $('#aiot-zone-active').prop('checked', zone.is_active);
            
            // Populate tags
            this.populateTags('aiot-zone-countries', zone.countries);
            this.populateTags('aiot-zone-states', zone.states);
            this.populateTags('aiot-zone-cities', zone.cities);
            
            // Set coordinates
            if (zone.coordinates) {
                $('#aiot-zone-coordinates').val(JSON.stringify(zone.coordinates));
            }
        },

        populateTags: function(fieldId, values) {
            var container = $('#' + fieldId + '-tags');
            var hiddenField = $('#' + fieldId);
            
            container.empty();
            
            if (values && Array.isArray(values)) {
                values.forEach(function(value) {
                    var tag = $('<span class="aiot-tag">' + value + '<button type="button" class="aiot-tag-remove">&times;</button></span>');
                    container.append(tag);
                });
            }
            
            hiddenField.val(JSON.stringify(values || []));
        },

        saveZone: function(form) {
            var self = this;
            var button = form.find('button[type="submit"]');
            var originalText = button.html();
            
            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> <?php _e('Saving...', 'ai-order-tracker'); ?>');
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: form.serialize() + '&action=' + ($('#aiot-zone-id').val() === '0' ? 'aiot_admin_create_zone' : 'aiot_admin_update_zone') + '&nonce=' + aiot_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        $('#aiot-zone-modal').fadeOut();
                        self.loadZones();
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    aiotAdmin.showNotice('error', '<?php _e('An error occurred. Please try again.', 'ai-order-tracker'); ?>');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        editZone: function(zoneId) {
            this.openZoneModal(zoneId);
        },

        deleteZone: function(zoneId) {
            if (confirm('<?php _e('Are you sure you want to delete this zone?', 'ai-order-tracker'); ?>')) {
                var self = this;
                
                $.ajax({
                    url: aiot_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aiot_admin_delete_zone',
                        nonce: aiot_admin.nonce,
                        id: zoneId
                    },
                    success: function(response) {
                        if (response.success) {
                            aiotAdmin.showNotice('success', response.data.message);
                            self.loadZones();
                        } else {
                            aiotAdmin.showNotice('error', response.data.message);
                        }
                    }
                });
            }
        }
    };

    // Initialize courier management
    aiotAdmin.courierManager = {
        init: function() {
            this.initCourierList();
            this.initCourierForm();
            this.initModals();
        },

        initModals: function() {
            var self = this;
            
            // Add courier button
            $(document).on('click', '#aiot-add-courier', function(e) {
                e.preventDefault();
                self.openCourierModal();
            });

            // Import default couriers button
            $(document).on('click', '#aiot-import-default-couriers', function(e) {
                e.preventDefault();
                self.importDefaultCouriers();
            });

            // Edit courier button
            $(document).on('click', '.aiot-courier-edit', function(e) {
                e.preventDefault();
                var courierId = $(this).data('id');
                self.editCourier(courierId);
            });

            // Delete courier button
            $(document).on('click', '.aiot-courier-delete', function(e) {
                e.preventDefault();
                var courierId = $(this).data('id');
                self.deleteCourier(courierId);
            });

            // Test API button
            $(document).on('click', '.aiot-test-api', function(e) {
                e.preventDefault();
                var courier = $(this).data('courier');
                self.testCourierApi(courier);
            });

            // Modal close buttons
            $(document).on('click', '.aiot-modal-close', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            $(document).on('click', '.aiot-modal-cancel', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            // Modal background click
            $(document).on('click', '.aiot-modal', function(e) {
                if ($(e.target).hasClass('aiot-modal')) {
                    $(this).fadeOut();
                }
            });

            // Form submission
            $(document).on('submit', '#aiot-courier-form', function(e) {
                e.preventDefault();
                self.saveCourier($(this));
            });

            // Refresh button
            $(document).on('click', '#aiot-refresh-couriers', function(e) {
                e.preventDefault();
                self.loadCouriers();
            });

            // Active only checkbox
            $(document).on('change', '#aiot-active-only', function(e) {
                self.loadCouriers();
            });
        },

        openCourierModal: function(courierId) {
            var modal = $('#aiot-courier-modal');
            var title = $('#aiot-modal-title');
            
            if (courierId) {
                title.text('<?php _e('Edit Courier', 'ai-order-tracker'); ?>');
                this.loadCourierData(courierId);
            } else {
                title.text('<?php _e('Add New Courier', 'ai-order-tracker'); ?>');
                this.resetCourierForm();
            }
            
            modal.fadeIn();
        },

        resetCourierForm: function() {
            $('#aiot-courier-form')[0].reset();
            $('#aiot-courier-id').val('0');
        },

        loadCouriers: function() {
            var self = this;
            var activeOnly = $('#aiot-active-only').is(':checked');
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'GET',
                data: {
                    action: 'aiot_admin_get_couriers',
                    nonce: aiot_admin.nonce,
                    active_only: activeOnly
                },
                success: function(response) {
                    if (response.success) {
                        self.renderCouriers(response.data);
                    }
                }
            });
        },

        renderCouriers: function(couriers) {
            var container = $('#aiot-couriers-table-container');
            var html = '';
            
            if (couriers.length === 0) {
                html = '<div class="aiot-no-couriers"><?php _e('No couriers found.', 'ai-order-tracker'); ?></div>';
            } else {
                html = '<div class="aiot-couriers-grid">';
                couriers.forEach(function(courier) {
                    html += self.renderCourierCard(courier);
                });
                html += '</div>';
            }
            
            container.html(html);
        },

        renderCourierCard: function(courier) {
            var html = '<div class="aiot-courier-card ' + (courier.is_active ? 'active' : 'inactive') + '">';
            html += '<div class="aiot-courier-card-header">';
            html += '<div class="aiot-courier-info">';
            html += '<h3>' + courier.name + '</h3>';
            html += '<span class="aiot-courier-slug">' + courier.slug + '</span>';
            html += '</div>';
            html += '<div class="aiot-courier-actions">';
            html += '<button type="button" class="button aiot-courier-edit" data-id="' + courier.id + '"><?php _e('Edit', 'ai-order-tracker'); ?></button>';
            html += '<button type="button" class="button aiot-courier-delete" data-id="' + courier.id + '"><?php _e('Delete', 'ai-order-tracker'); ?></button>';
            html += '</div>';
            html += '</div>';
            html += '<div class="aiot-courier-card-body">';
            if (courier.description) {
                html += '<div class="aiot-courier-description">';
                html += '<p>' + courier.description + '</p>';
                html += '</div>';
            }
            html += '<div class="aiot-courier-details">';
            html += '<div class="aiot-courier-detail">';
            html += '<strong><?php _e('URL Pattern:', 'ai-order-tracker'); ?></strong> ';
            html += courier.url_pattern ? '<a href="' + courier.url_pattern.replace('{tracking_id}', '123456789') + '" target="_blank">' + courier.url_pattern + '</a>' : '<?php _e('N/A', 'ai-order-tracker'); ?>';
            html += '</div>';
            html += '<div class="aiot-courier-detail">';
            html += '<strong><?php _e('API Endpoint:', 'ai-order-tracker'); ?></strong> ' + (courier.api_endpoint || '<?php _e('N/A', 'ai-order-tracker'); ?>');
            html += '</div>';
            html += '<div class="aiot-courier-detail">';
            html += '<strong><?php _e('Tracking Format:', 'ai-order-tracker'); ?></strong> ' + courier.tracking_format;
            html += '</div>';
            html += '<div class="aiot-courier-detail">';
            html += '<strong><?php _e('Status:', 'ai-order-tracker'); ?></strong> ' + (courier.is_active ? '<?php _e('Active', 'ai-order-tracker'); ?>' : '<?php _e('Inactive', 'ai-order-tracker'); ?>');
            html += '</div>';
            html += '</div>';
            
            // Add test API button if API endpoint is available
            if (courier.api_endpoint) {
                html += '<div class="aiot-courier-test">';
                html += '<button type="button" class="button button-secondary aiot-test-api" data-courier="' + courier.slug + '"><?php _e('Test API Connection', 'ai-order-tracker'); ?></button>';
                html += '<span class="aiot-test-result" id="aiot-test-' + courier.slug + '"></span>';
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
            
            return html;
        },

        loadCourierData: function(courierId) {
            var self = this;
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_admin_get_courier',
                    nonce: aiot_admin.nonce,
                    id: courierId
                },
                success: function(response) {
                    if (response.success) {
                        self.populateCourierForm(response.data);
                    }
                }
            });
        },

        populateCourierForm: function(courier) {
            $('#aiot-courier-id').val(courier.id);
            $('#aiot-courier-name').val(courier.name);
            $('#aiot-courier-slug').val(courier.slug);
            $('#aiot-courier-description').val(courier.description);
            $('#aiot-courier-url-pattern').val(courier.url_pattern);
            $('#aiot-courier-api-endpoint').val(courier.api_endpoint);
            $('#aiot-courier-api-key').val(courier.api_key);
            $('#aiot-courier-tracking-format').val(courier.tracking_format);
            $('#aiot-courier-active').prop('checked', courier.is_active);
            
            if (courier.settings) {
                $('#aiot-courier-settings').val(JSON.stringify(courier.settings, null, 2));
            }
            
            if (courier.meta) {
                $('#aiot-courier-meta').val(JSON.stringify(courier.meta, null, 2));
            }
        },

        saveCourier: function(form) {
            var self = this;
            var button = form.find('button[type="submit"]');
            var originalText = button.html();
            
            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> <?php _e('Saving...', 'ai-order-tracker'); ?>');
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: form.serialize() + '&action=' + ($('#aiot-courier-id').val() === '0' ? 'aiot_admin_create_courier' : 'aiot_admin_update_courier') + '&nonce=' + aiot_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        $('#aiot-courier-modal').fadeOut();
                        self.loadCouriers();
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    aiotAdmin.showNotice('error', '<?php _e('An error occurred. Please try again.', 'ai-order-tracker'); ?>');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        editCourier: function(courierId) {
            this.openCourierModal(courierId);
        },

        deleteCourier: function(courierId) {
            if (confirm('<?php _e('Are you sure you want to delete this courier?', 'ai-order-tracker'); ?>')) {
                var self = this;
                
                $.ajax({
                    url: aiot_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aiot_admin_delete_courier',
                        nonce: aiot_admin.nonce,
                        id: courierId
                    },
                    success: function(response) {
                        if (response.success) {
                            aiotAdmin.showNotice('success', response.data.message);
                            self.loadCouriers();
                        } else {
                            aiotAdmin.showNotice('error', response.data.message);
                        }
                    }
                });
            }
        },

        testCourierApi: function(courier) {
            var self = this;
            var resultContainer = $('#aiot-test-' + courier);
            
            resultContainer.html('<span class="aiot-loading"></span> <?php _e('Testing...', 'ai-order-tracker'); ?>');
            
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_admin_test_courier_api',
                    nonce: aiot_admin.nonce,
                    courier: courier
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.html('<span class="aiot-test-success"><?php _e('Connection successful!', 'ai-order-tracker'); ?></span>');
                    } else {
                        resultContainer.html('<span class="aiot-test-error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    resultContainer.html('<span class="aiot-test-error"><?php _e('Connection failed.', 'ai-order-tracker'); ?></span>');
                }
            });
        },

        importDefaultCouriers: function() {
            if (confirm('<?php _e('This will import 50+ default couriers. Continue?', 'ai-order-tracker'); ?>')) {
                var self = this;
                
                $.ajax({
                    url: aiot_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aiot_admin_import_default_couriers',
                        nonce: aiot_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            aiotAdmin.showNotice('success', response.data.message);
                            self.loadCouriers();
                        } else {
                            aiotAdmin.showNotice('error', response.data.message);
                        }
                    },
                    error: function() {
                        aiotAdmin.showNotice('error', '<?php _e('An error occurred. Please try again.', 'ai-order-tracker'); ?>');
                    }
                });
            }
        },

        initCourierForm: function() {
            // Auto-generate slug from name
            $('#aiot-courier-name').on('input', function() {
                var name = $(this).val();
                var slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                $('#aiot-courier-slug').val(slug);
            });
        },

        initCourierList: function() {
            // Load couriers on page load
            this.loadCouriers();
        }
    };

    // Test API connection function
    aiotAdmin.testCourierApi = function(courier, apiKey) {
        $.ajax({
            url: aiot_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_admin_test_courier_api',
                nonce: aiot_admin.nonce,
                courier: courier,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $('#aiot-test-' + courier).html('<span class="aiot-test-success"><?php _e('Connection successful!', 'ai-order-tracker'); ?></span>');
                } else {
                    $('#aiot-test-' + courier).html('<span class="aiot-test-error">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('#aiot-test-' + courier).html('<span class="aiot-test-error"><?php _e('Connection failed.', 'ai-order-tracker'); ?></span>');
            }
        });
    };

    // Initialize order management
    aiotAdmin.orderManager = {
        init: function() {
            this.initOrderForm();
            this.initOrderList();
            this.initTrackingSimulation();
        },

        initOrderForm: function() {
            $(document).on('submit', '#aiot-order-form', function(e) {
                e.preventDefault();
                aiotAdmin.submitAjaxForm($(this));
            });
        },

        initOrderList: function() {
            $(document).on('click', '.aiot-order-edit', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');
                aiotAdmin.editRow(orderId);
            });

            $(document).on('click', '.aiot-order-delete', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');
                aiotAdmin.deleteRow(orderId);
            });

            $(document).on('click', '.aiot-order-view', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');
                aiotAdmin.viewRow(orderId);
            });
        },

        initTrackingSimulation: function() {
            $(document).on('click', '.aiot-simulate-tracking', function(e) {
                e.preventDefault();
                var trackingId = $(this).data('tracking-id');
                aiotAdmin.performAjaxAction('aiot_simulate_tracking', { tracking_id: trackingId });
            });
        }
    };

    // Initialize settings management
    aiotAdmin.settingsManager = {
        init: function() {
            this.initSettingsForm();
            this.initImportExport();
        },

        initSettingsForm: function() {
            $(document).on('submit', '#aiot-settings-form', function(e) {
                e.preventDefault();
                aiotAdmin.submitAjaxForm($(this));
            });
        },

        initImportExport: function() {
            $(document).on('click', '.aiot-export-settings', function(e) {
                e.preventDefault();
                aiotAdmin.performAjaxAction('aiot_export_settings', {});
            });

            $(document).on('change', '.aiot-import-file', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('action', 'aiot_import_settings');
                    formData.append('nonce', aiot_admin.nonce);

                    $.ajax({
                        url: aiot_admin.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                aiotAdmin.showNotice('success', response.data.message);
                                if (response.data.reload) {
                                    location.reload();
                                }
                            } else {
                                aiotAdmin.showNotice('error', response.data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            aiotAdmin.showNotice('error', aiot_admin.strings.error);
                        }
                    });
                }
            });
        }
    };

    // Initialize order management
    aiotAdmin.orderManager = {
        init: function() {
            this.initOrderList();
            this.initOrderForm();
            this.initModals();
        },

        initModals: function() {
            var self = this;
            
            // Add order button
            $(document).on('click', '#aiot-add-order', function(e) {
                e.preventDefault();
                self.openAddOrderModal();
            });

            // View order button
            $(document).on('click', '.aiot-view-order', function(e) {
                e.preventDefault();
                var trackingId = $(this).data('tracking-id');
                self.viewOrder(trackingId);
            });

            // Edit order button
            $(document).on('click', '.aiot-edit-order', function(e) {
                e.preventDefault();
                var trackingId = $(this).data('tracking-id');
                self.editOrder(trackingId);
            });

            // Delete order button
            $(document).on('click', '.aiot-delete-order', function(e) {
                e.preventDefault();
                var trackingId = $(this).data('tracking-id');
                self.deleteOrder(trackingId);
            });

            // Modal close buttons
            $(document).on('click', '.aiot-modal-close', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            $(document).on('click', '.aiot-modal-cancel', function(e) {
                e.preventDefault();
                $(this).closest('.aiot-modal').fadeOut();
            });

            // Modal background click
            $(document).on('click', '.aiot-modal', function(e) {
                if ($(e.target).hasClass('aiot-modal')) {
                    $(this).fadeOut();
                }
            });

            // Form submissions
            $(document).on('submit', '#aiot-add-order-form', function(e) {
                e.preventDefault();
                self.addOrder($(this));
            });

            $(document).on('submit', '#aiot-edit-order-form', function(e) {
                e.preventDefault();
                self.updateOrder($(this));
            });
        },

        openAddOrderModal: function() {
            var modal = $('#aiot-add-order-modal');
            this.resetOrderForm();
            modal.fadeIn();
        },

        resetOrderForm: function() {
            $('#aiot-add-order-form')[0].reset();
        },

        addOrder: function(form) {
            var formData = form.serialize();
            var button = form.find('button[type="submit"]');
            var originalText = button.html();

            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> ' + aiot_admin.strings.saving);

            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: formData + '&action=aiot_add_order&nonce=' + aiot_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        $('#aiot-add-order-modal').fadeOut();
                        location.reload();
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        viewOrder: function(trackingId) {
            var modal = $('#aiot-view-order-modal');
            var detailsContainer = $('#aiot-order-details');
            
            // Show loading
            detailsContainer.html('<div class="aiot-loading">' + aiot_admin.strings.loading + '</div>');
            modal.fadeIn();

            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_get_order_details',
                    nonce: aiot_admin.nonce,
                    tracking_id: trackingId
                },
                success: function(response) {
                    if (response.success) {
                        detailsContainer.html(response.data.html);
                    } else {
                        detailsContainer.html('<div class="aiot-error">' + response.data.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    detailsContainer.html('<div class="aiot-error">' + aiot_admin.strings.error + '</div>');
                }
            });
        },

        editOrder: function(trackingId) {
            var modal = $('#aiot-edit-order-modal');
            
            // Load order data
            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_get_order_data',
                    nonce: aiot_admin.nonce,
                    tracking_id: trackingId
                },
                success: function(response) {
                    if (response.success) {
                        // Populate form
                        var order = response.data.order;
                        $('#aiot_edit_tracking_id').val(order.tracking_id);
                        $('#aiot_edit_order_id').val(order.order_id);
                        $('#aiot_edit_customer_name').val(order.customer_name);
                        $('#aiot_edit_customer_email').val(order.customer_email);
                        $('#aiot_edit_location').val(order.location);
                        $('#aiot_edit_status').val(order.status);
                        $('#aiot_edit_carrier').val(order.carrier);
                        $('#aiot_edit_estimated_delivery').val(order.estimated_delivery);
                        
                        modal.fadeIn();
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                }
            });
        },

        updateOrder: function(form) {
            var formData = form.serialize();
            var button = form.find('button[type="submit"]');
            var originalText = button.html();

            // Show loading state
            button.prop('disabled', true).html('<span class="aiot-loading"></span> ' + aiot_admin.strings.saving);

            $.ajax({
                url: aiot_admin.ajax_url,
                type: 'POST',
                data: formData + '&action=aiot_update_order&nonce=' + aiot_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        aiotAdmin.showNotice('success', response.data.message);
                        $('#aiot-edit-order-modal').fadeOut();
                        location.reload();
                    } else {
                        aiotAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    aiotAdmin.showNotice('error', aiot_admin.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        deleteOrder: function(trackingId) {
            if (confirm(aiot_admin.strings.confirm_delete)) {
                $.ajax({
                    url: aiot_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aiot_delete_order',
                        nonce: aiot_admin.nonce,
                        tracking_id: trackingId
                    },
                    success: function(response) {
                        if (response.success) {
                            aiotAdmin.showNotice('success', response.data.message);
                            location.reload();
                        } else {
                            aiotAdmin.showNotice('error', response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        aiotAdmin.showNotice('error', aiot_admin.strings.error);
                    }
                });
            }
        },

        initOrderList: function() {
            // Initialize order list functionality
            this.loadOrders();
        },

        initOrderForm: function() {
            // Initialize order form functionality
        },

        loadOrders: function() {
            // Load orders list via AJAX if needed
        }
    };

    // Initialize analytics
    aiotAdmin.analytics = {
        init: function() {
            this.initCharts();
            this.initDateRange();
        },

        initCharts: function() {
            // Initialize charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.initOrdersChart();
                this.initStatusChart();
            }
        },

        initOrdersChart: function() {
            var ctx = document.getElementById('aiot-orders-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: aiot_admin.strings.orders,
                            data: [],
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },

        initStatusChart: function() {
            var ctx = document.getElementById('aiot-status-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            data: [],
                            backgroundColor: [
                                '#ffc107',
                                '#17a2b8',
                                '#6f42c1',
                                '#007bff',
                                '#fd7e14',
                                '#20c997',
                                '#28a745'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        initDateRange: function() {
            $(document).on('change', '.aiot-date-range', function() {
                var range = $(this).val();
                aiotAdmin.performAjaxAction('aiot_update_analytics', { range: range });
            });
        }
    };

    // Initialize managers when DOM is ready
    $(document).ready(function() {
        aiotAdmin.zoneManager.init();
        aiotAdmin.courierManager.init();
        aiotAdmin.orderManager.init();
        aiotAdmin.settingsManager.init();
        aiotAdmin.analytics.init();
    });

})(jQuery);