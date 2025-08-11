/**
 * Simple tracking JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle form submission
        $('#aiot-tracking-form').on('submit', function(e) {
            e.preventDefault();
            trackOrder();
        });

        // Handle back button
        $('#aiot-back-button').on('click', function() {
            showForm();
        });
    });

    function trackOrder() {
        var trackingId = $('#aiot-tracking-id').val().trim();
        
        if (!trackingId) {
            showError(aiot_simple_tracking.strings.invalid_id);
            return;
        }

        // Show loading state
        setLoading(true);

        // Make AJAX request
        $.ajax({
            url: aiot_simple_tracking.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_track_order',
                nonce: aiot_simple_tracking.nonce,
                tracking_id: trackingId
            },
            success: function(response) {
                if (response.success) {
                    showResults(response.data);
                } else {
                    showError(response.data.message || aiot_simple_tracking.strings.not_found);
                }
            },
            error: function() {
                showError(aiot_simple_tracking.strings.error);
            },
            complete: function() {
                setLoading(false);
            }
        });
    }

    function showResults(data) {
        // Hide form and show results
        $('.aiot-tracking-form').hide();
        $('#aiot-tracking-results').show();
        $('#aiot-tracking-error').hide();

        // Build results HTML
        var html = '';

        // Order Summary
        html += '<div class="aiot-order-summary">';
        html += '<div class="aiot-summary-grid">';
        html += '<div class="aiot-summary-item">';
        html += '<label>' + aiot_simple_tracking.strings.tracking_id + '</label>';
        html += '<span>' + data.tracking_id + '</span>';
        html += '</div>';
        html += '<div class="aiot-summary-item">';
        html += '<label>Order ID</label>';
        html += '<span>' + (data.order_id || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="aiot-summary-item">';
        html += '<label>Status</label>';
        html += '<span class="aiot-status-badge" style="background-color: ' + data.status_info.color + '">';
        html += data.status_info.label;
        html += '</span>';
        html += '</div>';
        html += '<div class="aiot-summary-item">';
        html += '<label>Carrier</label>';
        html += '<span>' + (data.carrier || 'Standard') + '</span>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        // Progress Bar
        html += '<div class="aiot-progress-section">';
        html += '<div class="aiot-progress-header">';
        html += '<h3>Delivery Progress</h3>';
        html += '<span class="aiot-progress-percentage">' + data.progress + '%</span>';
        html += '</div>';
        html += '<div class="aiot-progress-container">';
        html += '<div class="aiot-progress-bar">';
        html += '<div class="aiot-progress-fill" style="width: ' + data.progress + '%; background-color: ' + data.status_info.color + '"></div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        // Status Description
        html += '<div class="aiot-status-description">';
        html += '<p>' + data.status_info.description + '</p>';
        html += '</div>';

        // Estimated Delivery
        if (data.estimated_delivery) {
            html += '<div class="aiot-delivery-info">';
            html += '<div class="aiot-delivery-card">';
            html += '<div class="aiot-delivery-icon">📅</div>';
            html += '<div class="aiot-delivery-details">';
            html += '<h4>Estimated Delivery</h4>';
            html += '<p class="aiot-delivery-date">' + formatDate(data.estimated_delivery) + '</p>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        // Current Location
        if (data.location) {
            html += '<div class="aiot-location-section">';
            html += '<h3>Current Location</h3>';
            html += '<div class="aiot-location-info">';
            html += '<div class="aiot-location-icon">📍</div>';
            html += '<div class="aiot-location-details">';
            html += '<p class="aiot-location-text">' + data.location + '</p>';
            html += '<p class="aiot-location-time">Last updated: ' + formatDateTime(data.updated_at) + '</p>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        // Tracking Timeline
        if (data.tracking_events && data.tracking_events.length > 0) {
            html += '<div class="aiot-timeline-section">';
            html += '<h3>Package Journey</h3>';
            html += '<div class="aiot-timeline">';
            
            $.each(data.tracking_events, function(index, event) {
                html += '<div class="aiot-timeline-item">';
                html += '<div class="aiot-timeline-marker">';
                html += '<div class="aiot-marker-icon">📦</div>';
                if (index < data.tracking_events.length - 1) {
                    html += '<div class="aiot-marker-line"></div>';
                }
                html += '</div>';
                html += '<div class="aiot-timeline-content">';
                html += '<div class="aiot-timeline-header">';
                html += '<div class="aiot-timeline-status">' + event.event_status + '</div>';
                html += '<div class="aiot-timeline-time">' + formatDateTime(event.timestamp) + '</div>';
                html += '</div>';
                if (event.location) {
                    html += '<div class="aiot-timeline-location">' + event.location + '</div>';
                }
                if (event.description) {
                    html += '<div class="aiot-timeline-description">' + event.description + '</div>';
                }
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '</div>';
        }

        // Customer Information
        if (data.customer_name || data.customer_email) {
            html += '<div class="aiot-customer-info">';
            html += '<h3>Customer Information</h3>';
            html += '<div class="aiot-customer-details">';
            if (data.customer_name) {
                html += '<div class="aiot-customer-item">';
                html += '<label>Name</label>';
                html += '<span>' + data.customer_name + '</span>';
                html += '</div>';
            }
            if (data.customer_email) {
                html += '<div class="aiot-customer-item">';
                html += '<label>Email</label>';
                html += '<span>' + data.customer_email + '</span>';
                html += '</div>';
            }
            html += '</div>';
            html += '</div>';
        }

        $('#aiot-tracking-content').html(html);
    }

    function showForm() {
        $('#aiot-tracking-results').hide();
        $('#aiot-tracking-form').show();
        $('#aiot-tracking-id').val('');
        $('#aiot-tracking-error').hide();
    }

    function showError(message) {
        $('#aiot-tracking-error').text(message).show();
    }

    function setLoading(loading) {
        var button = $('.aiot-tracking-button');
        if (loading) {
            button.prop('disabled', true).html('<span class="aiot-loading"></span> Loading...');
        } else {
            button.prop('disabled', false).text('Track Order');
        }
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

})(jQuery);