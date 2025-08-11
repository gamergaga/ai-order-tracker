jQuery(document).ready(function($) {
    'use strict';
    
    // Variables
    var map = null;
    var markers = [];
    var currentZoneId = 0;
    
    // Initialize
    function init() {
        bindEvents();
        initMap();
    }
    
    // Bind events
    function bindEvents() {
        // Add zone button
        $('#aiot-add-zone-btn').on('click', function() {
            openZoneModal();
        });
        
        // Install default zones button
        $('#aiot-install-default-zones-btn').on('click', function() {
            installDefaultZones();
        });
        
        // Edit zone buttons
        $(document).on('click', '.aiot-edit-zone', function() {
            var zoneId = $(this).data('zone-id');
            editZone(zoneId);
        });
        
        // Delete zone buttons
        $(document).on('click', '.aiot-delete-zone', function() {
            var zoneId = $(this).data('zone-id');
            deleteZone(zoneId);
        });
        
        // Modal close buttons
        $('.aiot-modal-close').on('click', function() {
            closeZoneModal();
        });
        
        // Country change
        $('#zone-country').on('change', function() {
            var countryCode = $(this).val();
            loadStates(countryCode);
            updateMap();
        });
        
        // State change
        $('#zone-state').on('change', function() {
            var countryCode = $('#zone-country').val();
            var stateName = $(this).val();
            loadCities(countryCode, stateName);
            updateMap();
        });
        
        // Cities change
        $('#zone-cities').on('change', function() {
            updateMap();
        });
        
        // Form submit
        $('#aiot-zone-form').on('submit', function(e) {
            e.preventDefault();
            saveZone();
        });
    }
    
    // Initialize map
    function initMap() {
        if (typeof L === 'undefined') {
            console.log('Leaflet not loaded, retrying...');
            setTimeout(initMap, 1000);
            return;
        }
        
        // Check if map container exists
        var mapContainer = document.getElementById('aiot-zone-map');
        if (!mapContainer) {
            console.log('Map container not found');
            return;
        }
        
        // Check if map is already initialized
        if (map) {
            console.log('Map already initialized, invalidating size...');
            map.invalidateSize();
            return;
        }
        
        try {
            // Create map with explicit options
            map = L.map('aiot-zone-map', {
                center: [aiot_zones.map_center_lat, aiot_zones.map_center_lng],
                zoom: aiot_zones.map_zoom,
                zoomControl: true,
                attributionControl: true
            });
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Invalidate size to ensure proper rendering
            setTimeout(function() {
                if (map) {
                    map.invalidateSize();
                    console.log('Map initialized successfully');
                }
            }, 100);
            
        } catch (error) {
            console.error('Error initializing map:', error);
            // Try to recover by removing any partially initialized map
            if (map) {
                map.remove();
                map = null;
            }
        }
    }
    
    // Open zone modal
    function openZoneModal(zoneId) {
        currentZoneId = zoneId || 0;
        
        // Reset form
        $('#aiot-zone-form')[0].reset();
        $('#zone-id').val(currentZoneId);
        $('#zone-coordinates').val('');
        
        // Clear map markers and destroy existing map
        clearMapMarkers();
        if (map) {
            map.remove();
            map = null;
        }
        
        // Set modal title
        var title = currentZoneId > 0 ? 'Edit Zone' : 'Add New Zone';
        $('#aiot-zone-modal .aiot-modal-header h2').text(title);
        
        // Load zone data if editing
        if (currentZoneId > 0) {
            loadZoneData(currentZoneId);
        }
        
        // Show modal
        $('#aiot-zone-modal').show();
        
        // Initialize map after modal is shown and visible
        setTimeout(function() {
            initMapWhenVisible();
        }, 100);
    }
    
    // Initialize map when container is visible
    function initMapWhenVisible() {
        var mapContainer = document.getElementById('aiot-zone-map');
        if (!mapContainer) {
            console.log('Map container not found');
            return;
        }
        
        // Check if container is visible
        var rect = mapContainer.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) {
            console.log('Map container not visible, retrying...');
            setTimeout(initMapWhenVisible, 200);
            return;
        }
        
        // Check if Leaflet is loaded
        if (typeof L === 'undefined') {
            console.log('Leaflet not loaded, retrying...');
            setTimeout(initMapWhenVisible, 500);
            return;
        }
        
        // Now initialize the map
        initMap();
    }
    
    // Close zone modal
    function closeZoneModal() {
        $('#aiot-zone-modal').hide();
        currentZoneId = 0;
    }
    
    // Load zone data
    function loadZoneData(zoneId) {
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_zone',
                zone_id: zoneId,
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    var zone = response.data;
                    
                    // Populate form
                    $('#zone-name').val(zone.name);
                    $('#zone-type').val(zone.type);
                    
                    // Handle delivery days range
                    var deliveryDays = JSON.parse(zone.delivery_days || '{}');
                    if (deliveryDays.min && deliveryDays.max) {
                        $('#zone-delivery-days-min').val(deliveryDays.min);
                        $('#zone-delivery-days-max').val(deliveryDays.max);
                    } else {
                        $('#zone-delivery-days-min').val(zone.delivery_days);
                        $('#zone-delivery-days-max').val(zone.delivery_days);
                    }
                    
                    // Handle processing days range
                    var processingDays = JSON.parse(zone.processing_days || '{}');
                    if (processingDays.min && processingDays.max) {
                        $('#zone-processing-days-min').val(processingDays.min);
                        $('#zone-processing-days-max').val(processingDays.max);
                    } else {
                        $('#zone-processing-days-min').val(1);
                        $('#zone-processing-days-max').val(2);
                    }
                    
                    $('#zone-description').val(zone.description);
                    $('#zone-active').prop('checked', zone.is_active == 1);
                    
                    // Load location data
                    var countries = JSON.parse(zone.countries || '[]');
                    var states = JSON.parse(zone.states || '[]');
                    var cities = JSON.parse(zone.cities || '[]');
                    
                    if (countries.length > 0) {
                        $('#zone-country').val(countries[0]);
                        loadStates(countries[0]);
                    }
                    
                    if (states.length > 0) {
                        setTimeout(function() {
                            $('#zone-state').val(states[0]);
                            loadCities(countries[0], states[0]);
                        }, 500);
                    }
                    
                    if (cities.length > 0) {
                        setTimeout(function() {
                            $('#zone-cities').val(cities);
                        }, 1000);
                    }
                    
                    // Update map
                    setTimeout(function() {
                        updateMap();
                    }, 1500);
                }
            }
        });
    }
    
    // Load states for country
    function loadStates(countryCode) {
        $('#zone-state').empty().append('<option value="">' + (aiot_zones_i18n?.select_state || 'Select State') + '</option>');
        $('#zone-cities').empty().append('<option value="">' + (aiot_zones_i18n?.select_cities || 'Select Cities') + '</option>').prop('disabled', true);
        
        if (!countryCode) {
            $('#zone-state').prop('disabled', true);
            return;
        }
        
        $('#zone-state').prop('disabled', false);
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_states_for_country',
                country: countryCode,
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    var states = response.data.states;
                    $.each(states, function(index, state) {
                        $('#zone-state').append('<option value="' + state.name + '">' + state.name + '</option>');
                    });
                }
            }
        });
    }
    
    // Load cities for state
    function loadCities(countryCode, stateName) {
        $('#zone-cities').empty().append('<option value="">' + (aiot_zones_i18n?.select_cities || 'Select Cities') + '</option>');
        
        if (!countryCode || !stateName) {
            $('#zone-cities').prop('disabled', true);
            return;
        }
        
        $('#zone-cities').prop('disabled', false);
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_cities_for_state',
                country: countryCode,
                state: stateName,
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    var cities = response.data.cities;
                    $.each(cities, function(index, city) {
                        $('#zone-cities').append('<option value="' + city + '">' + city + '</option>');
                    });
                }
            }
        });
    }
    
    // Update map based on selected locations
    function updateMap() {
        if (!map) {
            console.log('Map not available for update');
            return;
        }
        
        clearMapMarkers();
        
        var country = $('#zone-country').val();
        var state = $('#zone-state').val();
        var cities = $('#zone-cities').val() || [];
        
        // If no location selected, reset map to default view
        if (!country && !state && cities.length === 0) {
            map.setView([aiot_zones.map_center_lat, aiot_zones.map_center_lng], aiot_zones.map_zoom);
            $('#zone-coordinates').val(JSON.stringify([]));
            return;
        }
        
        // Get coordinates for selected locations
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_zone_coordinates',
                country: country,
                state: state,
                cities: cities,
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    var coordinates = response.data.coordinates;
                    var bounds = [];
                    
                    // Add markers for each coordinate
                    $.each(coordinates, function(index, coord) {
                        if (coord && coord.length === 2) {
                            try {
                                var marker = L.marker([coord[0], coord[1]]).addTo(map);
                                markers.push(marker);
                                bounds.push([coord[0], coord[1]]);
                            } catch (error) {
                                console.error('Error adding marker:', error);
                            }
                        }
                    });
                    
                    // Fit map to show all markers or default to location
                    setTimeout(function() {
                        if (bounds.length > 0) {
                            try {
                                map.fitBounds(bounds, { padding: [20, 20] });
                            } catch (error) {
                                console.error('Error fitting bounds:', error);
                                // Fallback to first marker
                                map.setView(bounds[0], 8);
                            }
                        } else if (country) {
                            // Default to country center
                            var countryCoords = getCountryCenter(country);
                            if (countryCoords) {
                                map.setView([countryCoords[0], countryCoords[1]], 5);
                            }
                        }
                        
                        // Store coordinates in form
                        $('#zone-coordinates').val(JSON.stringify(coordinates));
                    }, 100);
                } else {
                    console.error('Failed to get coordinates:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error getting coordinates:', error);
            }
        });
    }
    
    // Clear map markers
    function clearMapMarkers() {
        if (!map) {
            markers = [];
            return;
        }
        
        $.each(markers, function(index, marker) {
            try {
                if (marker && map.hasLayer(marker)) {
                    map.removeLayer(marker);
                }
            } catch (error) {
                console.error('Error removing marker:', error);
            }
        });
        markers = [];
    }
    
    // Get country center coordinates
    function getCountryCenter(countryCode) {
        var countryCenters = {
            'US': [39.8283, -98.5795],
            'CA': [56.1304, -106.3468],
            'GB': [55.3781, -3.4360],
            'DE': [51.1657, 10.4515],
            'FR': [46.2276, 2.2137],
            'IT': [41.8719, 12.5674],
            'ES': [40.4637, -3.7492],
            'AU': [-25.2744, 133.7751],
            'JP': [36.2048, 138.2529],
            'CN': [35.8617, 104.1954],
            'IN': [20.5937, 78.9629],
            'BR': [-14.2350, -51.9253],
            'MX': [23.6345, -102.5528],
            'RU': [61.5240, 105.3188],
            'ZA': [-30.5595, 22.9375],
            'EG': [26.8206, 30.8025],
            'AE': [23.4241, 53.8478],
            'SA': [23.8859, 45.0792],
        };
        
        return countryCenters[countryCode] || null;
    }
    
    // Save zone
    function saveZone() {
        var formData = $('#aiot-zone-form').serialize();
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: formData + '&action=aiot_save_zone&nonce=' + aiot_zones.nonce,
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
    
    // Delete zone
    function deleteZone(zoneId) {
        if (!confirm((aiot_zones_i18n?.confirm_delete) || 'Are you sure you want to delete this zone?')) {
            return;
        }
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_delete_zone',
                zone_id: zoneId,
                nonce: aiot_zones.nonce
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
    
    // Install default zones
    function installDefaultZones() {
        if (!confirm((aiot_zones_i18n?.confirm_install) || 'Are you sure you want to install default zones?')) {
            return;
        }
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_install_default_zones',
                nonce: aiot_zones.nonce
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
    
    // Edit zone
    function editZone(zoneId) {
        openZoneModal(zoneId);
    }
    
    // Initialize on document ready
    init();
});