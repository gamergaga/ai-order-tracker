jQuery(document).ready(function($) {
    'use strict';
    
    // Variables
    var currentCourierId = 0;
    
    // Initialize
    function init() {
        bindEvents();
    }
    
    // Bind events
    function bindEvents() {
        // Add courier button
        $('#aiot-add-courier-btn').on('click', function() {
            openCourierModal();
        });
        
        // Import couriers button
        $('#aiot-import-couriers-btn').on('click', function() {
            openImportModal();
        });
        
        // Export couriers button
        $('#aiot-export-couriers-btn').on('click', function() {
            exportCouriers();
        });
        
        // Sample CSV button
        $('#aiot-sample-csv-btn').on('click', function() {
            downloadSampleCSV();
        });
        
        // Edit courier buttons
        $(document).on('click', '.aiot-edit-courier', function() {
            var courierId = $(this).data('courier-id');
            editCourier(courierId);
        });
        
        // Toggle courier buttons
        $(document).on('click', '.aiot-toggle-courier', function() {
            var courierId = $(this).data('courier-id');
            toggleCourier(courierId);
        });
        
        // Delete courier buttons
        $(document).on('click', '.aiot-delete-courier', function() {
            var courierId = $(this).data('courier-id');
            deleteCourier(courierId);
        });
        
        // Modal close buttons
        $('.aiot-modal-close').on('click', function() {
            $(this).closest('.aiot-modal').hide();
        });
        
        // Select all checkbox
        $('#aiot-select-all').on('change', function() {
            $('.aiot-courier-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // Individual checkboxes
        $(document).on('change', '.aiot-courier-checkbox', function() {
            updateSelectAllCheckbox();
        });
        
        // Bulk action apply
        $('#aiot-apply-bulk').on('click', function() {
            applyBulkAction();
        });
        
        // Form submits
        $('#aiot-courier-form').on('submit', function(e) {
            e.preventDefault();
            saveCourier();
        });
        
        $('#aiot-import-form').on('submit', function(e) {
            e.preventDefault();
            importCouriers();
        });
        
        // Auto-generate slug from name
        $('#courier-name').on('input', function() {
            var name = $(this).val();
            var slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            $('#courier-slug').val(slug);
        });
    }
    
    // Open courier modal
    function openCourierModal(courierId) {
        currentCourierId = courierId || 0;
        
        // Reset form
        $('#aiot-courier-form')[0].reset();
        $('#courier-id').val(currentCourierId);
        
        // Set modal title
        var title = currentCourierId > 0 ? 'Edit Courier' : 'Add New Courier';
        $('#aiot-courier-modal .aiot-modal-header h2').text(title);
        
        // Load courier data if editing
        if (currentCourierId > 0) {
            loadCourierData(currentCourierId);
        }
        
        // Show modal
        $('#aiot-courier-modal').show();
    }
    
    // Open import modal
    function openImportModal() {
        $('#aiot-import-form')[0].reset();
        $('#aiot-import-modal').show();
    }
    
    // Load courier data
    function loadCourierData(courierId) {
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_courier',
                courier_id: courierId,
                nonce: aiot_couriers.nonce
            },
            success: function(response) {
                if (response.success) {
                    var courier = response.data;
                    
                    // Populate form
                    $('#courier-name').val(courier.name);
                    $('#courier-slug').val(courier.slug);
                    $('#courier-url-pattern').val(courier.url_pattern);
                    $('#courier-active').prop('checked', courier.is_active == 1);
                    
                    // Load settings
                    var settings = JSON.parse(courier.settings || '{}');
                    $('#courier-phone').val(settings.phone || '');
                    $('#courier-website').val(settings.website || '');
                    $('#courier-country').val(settings.country || '');
                    $('#courier-type').val(settings.type || 'express');
                    $('#courier-display-name').val(settings.display_name || '');
                }
            }
        });
    }
    
    // Save courier
    function saveCourier() {
        var formData = $('#aiot-courier-form').serialize();
        
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: formData + '&action=aiot_save_courier&nonce=' + aiot_couriers.nonce,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Edit courier
    function editCourier(courierId) {
        openCourierModal(courierId);
    }
    
    // Toggle courier
    function toggleCourier(courierId) {
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_toggle_courier',
                courier_id: courierId,
                nonce: aiot_couriers.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Delete courier
    function deleteCourier(courierId) {
        if (!confirm(aiot_couriers.confirm_delete)) {
            return;
        }
        
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_delete_courier',
                courier_id: courierId,
                nonce: aiot_couriers.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Import couriers
    function importCouriers() {
        var formData = new FormData($('#aiot-import-form')[0]);
        
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var message = response.data.message;
                    if (response.data.imported > 0) {
                        message += '\\n\\nImported: ' + response.data.imported;
                    }
                    if (response.data.updated > 0) {
                        message += '\\nUpdated: ' + response.data.updated;
                    }
                    if (response.data.skipped > 0) {
                        message += '\\nSkipped: ' + response.data.skipped;
                    }
                    if (response.data.errors.length > 0) {
                        message += '\\n\\nErrors:\\n' + response.data.errors.join('\\n');
                    }
                    
                    alert(message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Export couriers
    function exportCouriers() {
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_export_couriers',
                nonce: aiot_couriers.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    var link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    alert('Couriers exported successfully: ' + response.data.count + ' couriers');
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Download sample CSV
    function downloadSampleCSV() {
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_sample_csv',
                nonce: aiot_couriers.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    var blob = new Blob([response.data.sample_csv], { type: 'text/csv' });
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'couriers-sample.csv';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Update select all checkbox
    function updateSelectAllCheckbox() {
        var allChecked = $('.aiot-courier-checkbox').length === $('.aiot-courier-checkbox:checked').length;
        $('#aiot-select-all').prop('checked', allChecked);
    }
    
    // Apply bulk action
    function applyBulkAction() {
        var bulkAction = $('#aiot-bulk-action').val();
        var selectedCouriers = $('.aiot-courier-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!bulkAction || selectedCouriers.length === 0) {
            alert('Please select a bulk action and at least one courier.');
            return;
        }
        
        if (!confirm(aiot_couriers.confirm_bulk)) {
            return;
        }
        
        $.ajax({
            url: aiot_couriers.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_bulk_action_couriers',
                bulk_action: bulkAction,
                courier_ids: selectedCouriers,
                nonce: aiot_couriers.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Initialize on document ready
    init();
});