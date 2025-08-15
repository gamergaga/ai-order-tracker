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
        loadZones(); // Load zones when page initializes
        loadAllCountries(); // Load all countries when page initializes
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
        
        // Export zones button
        $('#aiot-export-zones').on('click', function() {
            exportZones();
        });
        
        // Refresh zones button
        $('#aiot-refresh-zones').on('click', function() {
            loadZones();
        });
        
        // Search zones
        $('#aiot-search-zones').on('input', function() {
            filterZones();
        });
        
        // Filter zones
        $('#aiot-filter-status, #aiot-filter-type').on('change', function() {
            filterZones();
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
        
        // Zone type change
        $('#aiot-zone-type').on('change', function() {
            var zoneType = $(this).val();
            handleZoneTypeChange(zoneType);
        });
        
        // Country region change
        $('#aiot-zone-country-region').on('change', function() {
            var region = $(this).val();
            loadCountriesByRegion(region);
        });
        
        // Country change
        $('#aiot-zone-country').on('change', function() {
            var countryCode = $(this).val();
            loadStates(countryCode);
            updateMap();
        });
        
        // State change - update cities count automatically
        $('#aiot-zone-state').on('change', function() {
            updateCitiesCount();
            updateMap();
        });
        
        // Load all countries when page loads
        function loadAllCountries() {
            $.ajax({
                url: aiot_zones.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiot_load_countries',
                    nonce: aiot_zones.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var countries = response.data.countries;
                        var $countrySelect = $('#aiot-zone-country');
                        
                        $countrySelect.empty().append('<option value="">' + (aiot_zones_i18n?.select_country || 'Select Country') + '</option>');
                        
                        $.each(countries, function(index, country) {
                            $countrySelect.append('<option value="' + country.code + '">' + country.name + '</option>');
                        });
                        
                        console.log('Loaded ' + countries.length + ' countries');
                    }
                }
            });
        }
        
        // Country search functionality
        $('#aiot-country').parent().find('.search-input').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            filterDropdownOptions('#aiot-country', searchTerm);
        });
        
        // State change
        $('#aiot-state').on('change', function() {
            updateMap();
        });
        
        // State search functionality
        $('#aiot-state').parent().find('.search-input').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            filterDropdownOptions('#aiot-state', searchTerm);
        });
        
        // Cities search functionality
        $('#aiot-cities').parent().find('.search-input').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            filterDropdownOptions('#aiot-cities', searchTerm);
        });
        
        // Form submit
        $('#aiot-zone-form').on('submit', function(e) {
            e.preventDefault();
            saveZone();
        });
        
        // Cancel button
        $('#aiot-cancel-zone').on('click', function() {
            closeZoneModal();
        });
    }
    
    // Handle zone type change
    function handleZoneTypeChange(zoneType) {
        // For both country and state types, we show the country selection
        // The difference is that for state type, we also show state selection
        
        // Reset location fields
        resetLocationFields();
        
        // Enable country region selection for both types
        $('#aiot-zone-country-region').prop('disabled', false);
        
        // Enable state selection only for state type (but only after country is selected)
        if (zoneType === 'state') {
            // State selection will be enabled when a country is selected
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
        } else {
            // For country type, disable state selection
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
        }
    }
    
    // Load countries by region
    function loadCountriesByRegion(region) {
        var regions = {
            'northern': ['Canada', 'United States', 'United Kingdom', 'Germany', 'Poland', 'Russia', 'Norway', 'Sweden', 'Finland', 'Denmark', 'Iceland', 'Ireland', 'Netherlands', 'Belgium', 'France', 'Switzerland', 'Austria', 'Czech Republic', 'Slovakia', 'Hungary'],
            'southern': ['Australia', 'New Zealand', 'South Africa', 'Argentina', 'Chile', 'Brazil', 'Uruguay', 'Paraguay', 'Bolivia', 'Peru', 'Ecuador', 'Colombia', 'Venezuela', 'Guyana', 'Suriname', 'French Guiana', 'Spain', 'Portugal', 'Italy', 'Greece', 'Turkey', 'Israel', 'Egypt', 'Libya', 'Tunisia', 'Algeria', 'Morocco'],
            'eastern': ['China', 'Japan', 'South Korea', 'India', 'Thailand', 'Vietnam', 'Philippines', 'Indonesia', 'Malaysia', 'Singapore', 'Taiwan', 'Hong Kong', 'Macau', 'Cambodia', 'Laos', 'Myanmar', 'Bangladesh', 'Sri Lanka', 'Pakistan', 'Afghanistan', 'Iran', 'Iraq', 'Syria', 'Jordan', 'Lebanon', 'Saudi Arabia', 'Yemen', 'Oman', 'UAE', 'Qatar', 'Kuwait', 'Bahrain'],
            'western': ['United States', 'Canada', 'Mexico', 'Brazil', 'Argentina', 'Chile', 'Peru', 'Colombia', 'Venezuela', 'Ecuador', 'Bolivia', 'Paraguay', 'Uruguay', 'Guyana', 'Suriname', 'French Guiana', 'Costa Rica', 'Panama', 'Nicaragua', 'Honduras', 'El Salvador', 'Guatemala', 'Belize', 'Cuba', 'Jamaica', 'Haiti', 'Dominican Republic', 'Puerto Rico', 'Trinidad and Tobago', 'Barbados', 'Bahamas']
        };
        
        var countries = regions[region] || [];
        var $countrySelect = $('#aiot-zone-country');
        
        $countrySelect.empty().append('<option value="">' + (aiot_zones_i18n?.select_country || 'Select Country') + '</option>');
        
        $.each(countries, function(index, country) {
            $countrySelect.append('<option value="' + country + '">' + country + '</option>');
        });
        
        // Enable country select and search
        $countrySelect.prop('disabled', false);
        $('#aiot-zone-country-search').prop('disabled', false);
    }
    
    // Update cities count automatically when states are selected
    function updateCitiesCount() {
        var selectedStates = $('#aiot-zone-state').val() || [];
        var citiesCount = 0;
        
        if (selectedStates.length > 0) {
            // Estimate cities count (in a real implementation, you would fetch actual data)
            // For now, we'll use a rough estimate of 50 cities per state
            citiesCount = selectedStates.length * 50;
            
            $('#aiot-selected-cities-count').show();
            $('#aiot-selected-cities-count .count').text(citiesCount);
        } else {
            $('#aiot-selected-cities-count').hide();
        }
    }
    
    // Reset location fields
    function resetLocationFields() {
        $('#aiot-zone-country, #aiot-zone-state').val('');
        $('#aiot-zone-state, #aiot-zone-state-search').prop('disabled', true);
        $('#aiot-selected-cities-count').hide();
    }
    
    // Load states for country
    function loadStates(countryCode) {
        $('#aiot-state').empty().append('<option value="">' + (aiot_zones_i18n?.select_state || 'Select State') + '</option>');
        
        if (!countryCode) {
            $('#aiot-state').prop('disabled', true);
            $('#aiot-state').parent().find('.search-input').prop('disabled', true);
            return;
        }
        
        $('#aiot-state').prop('disabled', false);
        $('#aiot-state').parent().find('.search-input').prop('disabled', false);
        
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
                        $('#aiot-state').append('<option value="' + state.name + '">' + state.name + '</option>');
                    });
                }
            }
        });
    }
    
    // Filter dropdown options based on search term
    function filterDropdownOptions(dropdownSelector, searchTerm) {
        var $dropdown = $(dropdownSelector);
        var $options = $dropdown.find('option');
        
        $options.each(function() {
            var $option = $(this);
            var optionText = $option.text().toLowerCase();
            
            if (searchTerm === '' || optionText.includes(searchTerm)) {
                $option.show();
            } else {
                $option.hide();
            }
        });
        
        // If no options are visible, show a message
        var visibleOptions = $options.filter(':visible');
        if (visibleOptions.length === 0 && searchTerm !== '') {
            // If no options match, you could add a "No results found" option here
            // For now, we'll just hide all options
        }
    }
    
    // Reset location fields
    function resetLocationFields() {
        $('#aiot-zone-country-region, #aiot-zone-country, #aiot-zone-state').val('');
        $('#aiot-zone-country, #aiot-zone-country-search, #aiot-zone-state, #aiot-zone-state-search').prop('disabled', true);
        $('#aiot-selected-cities-count').hide();
    }
    
    // Add selected location
    function addSelectedLocation() {
        var zoneType = $('#aiot-zone-type').val();
        var locationText = '';
        var locationData = {};
        
        if (zoneType === 'country') {
            var countryRegion = $('#aiot-country-region').val();
            var country = $('#aiot-country').val();
            
            if (!country) {
                alert(aiot_zones_i18n?.country_required || 'Please select a country');
                return;
            }
            
            locationText = country + (countryRegion ? ' (' + countryRegion + ')' : '');
            locationData = {
                type: 'country',
                country: country,
                region: countryRegion
            };
        } else if (zoneType === 'state') {
            var stateRegion = $('#aiot-state-region').val();
            var state = $('#aiot-state').val();
            
            if (!state) {
                alert(aiot_zones_i18n?.state_required || 'Please select a state');
                return;
            }
            
            locationText = state + (stateRegion ? ' (' + stateRegion + ')' : '');
            locationData = {
                type: 'state',
                state: state,
                region: stateRegion
            };
        } else {
            alert(aiot_zones_i18n?.zone_type_required_first || 'Please select a zone type first');
            return;
        }
        
        // Add location tag
        addLocationTag(locationText, locationData);
        
        // Reset fields for next selection
        if (zoneType === 'country') {
            $('#aiot-country-region, #aiot-country').val('');
        } else if (zoneType === 'state') {
            $('#aiot-state-region, #aiot-state').val('');
        }
    }
    
    // Add location tag to selected locations
    function addLocationTag(text, data) {
        var $locationsList = $('#aiot-locations-list');
        var tagId = 'location-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        var $tag = $('<div class="location-tag" id="' + tagId + '">' +
            '<span>' + text + '</span>' +
            '<span class="remove-location" data-location-id="' + tagId + '">×</span>' +
            '</div>');
        
        // Store location data
        $tag.data('location-data', data);
        
        $locationsList.append($tag);
        
        // Update map
        updateMap();
    }
    
    // Remove location tag
    $(document).on('click', '.remove-location', function() {
        var $tag = $('#' + $(this).data('location-id'));
        $tag.remove();
        updateMap();
    });
    
    // Initialize map
    function initMap() {
        var mapContainer = document.getElementById('aiot-zone-map');
        if (!mapContainer) {
            console.log('Map container not found');
            return;
        }
        
        // Try to load Leaflet if available
        if (typeof L !== 'undefined') {
            // Initialize Leaflet map
            map = L.map(mapContainer).setView([aiot_zones.map_center_lat, aiot_zones.map_center_lng], aiot_zones.map_zoom);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            console.log('Leaflet map initialized successfully');
        } else {
            // Create a simple map placeholder
            mapContainer.innerHTML = '<div class="map-placeholder"><p>Select locations to see them on the map</p></div>';
            
            // Store map reference
            window.aiotMap = {
                container: mapContainer,
                markers: []
            };
            
            console.log('Simple map initialized successfully');
        }
    }
    
    // Update map based on selected locations
    function updateMap() {
        var locations = $('#aiot-locations-list .location-tag');
        
        // Handle Leaflet map
        if (map && typeof L !== 'undefined') {
            // Clear existing markers
            markers.forEach(function(marker) {
                map.removeLayer(marker);
            });
            markers = [];
            
            if (locations.length === 0) {
                return;
            }
            
            // Add markers for each location
            locations.each(function() {
                var locationData = $(this).data('location-data');
                var locationText = $(this).find('span:first').text();
                
                // For demo purposes, use some default coordinates
                // In a real implementation, you would geocode the location names
                var lat = aiot_zones.map_center_lat + (Math.random() - 0.5) * 10;
                var lng = aiot_zones.map_center_lng + (Math.random() - 0.5) * 10;
                
                var marker = L.marker([lat, lng]).addTo(map)
                    .bindPopup(locationText);
                
                markers.push(marker);
            });
            
            // Fit map to show all markers
            if (markers.length > 0) {
                var group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
            
            console.log('Leaflet map updated with ' + markers.length + ' markers');
        } 
        // Handle simple map
        else if (window.aiotMap) {
            var mapContainer = window.aiotMap.container;
            
            if (locations.length === 0) {
                mapContainer.innerHTML = '<div class="map-placeholder"><p>Select locations to see them on the map</p></div>';
                return;
            }
            
            // Create simple map display
            var mapHtml = '<div class="simple-map-display">';
            mapHtml += '<h4>Selected Locations (' + locations.length + ')</h4>';
            mapHtml += '<div class="locations-grid">';
            
            locations.each(function() {
                var locationData = $(this).data('location-data');
                var locationText = $(this).find('span:first').text();
                
                mapHtml += '<div class="map-location-item">';
                mapHtml += '<strong>' + locationText + '</strong>';
                mapHtml += '<div class="location-type">' + locationData.type + '</div>';
                if (locationData.region) {
                    mapHtml += '<div class="location-region">Region: ' + locationData.region + '</div>';
                }
                mapHtml += '</div>';
            });
            
            mapHtml += '</div>';
            mapHtml += '<div class="map-instructions">';
            mapHtml += '<p>This is a simplified map view. In a production environment, this would integrate with OpenStreetMap or Google Maps to show actual geographic locations.</p>';
            mapHtml += '</div>';
            mapHtml += '</div>';
            
            mapContainer.innerHTML = mapHtml;
            
            console.log('Simple map updated with ' + locations.length + ' locations');
        }
    }
    
    // Open zone modal
    function openZoneModal(zoneId) {
        currentZoneId = zoneId || 0;
        
        // Reset form
        $('#aiot-zone-form')[0].reset();
        $('#aiot-zone-id').val(currentZoneId);
        $('#aiot-zone-coordinates').val('');
        
        // Clear map markers and destroy existing map
        clearMapMarkers();
        if (map) {
            map.remove();
            map = null;
        }
        
        // Show form container instead of modal
        $('#aiot-zone-form-container').show();
        
        // Load zone data if editing
        if (currentZoneId > 0) {
            loadZoneData(currentZoneId);
        }
        
        // Initialize map after form is shown and visible
        setTimeout(function() {
            initMapWhenVisible();
        }, 200);
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
            setTimeout(initMapWhenVisible, 300);
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
        $('#aiot-zone-form-container').hide();
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
                    $('#aiot-zone-name').val(zone.name);
                    $('#aiot-zone-type').val(zone.type);
                    
                    // Handle delivery days range
                    var deliveryDays = JSON.parse(zone.delivery_days || '{}');
                    if (deliveryDays.min && deliveryDays.max) {
                        $('#aiot-zone-delivery-days-min').val(deliveryDays.min);
                        $('#aiot-zone-delivery-days-max').val(deliveryDays.max);
                    } else {
                        $('#aiot-zone-delivery-days-min').val(zone.delivery_days);
                        $('#aiot-zone-delivery-days-max').val(zone.delivery_days);
                    }
                    
                    // Handle processing days
                    var processingDays = JSON.parse(zone.processing_days || '{}');
                    if (processingDays.min && processingDays.max) {
                        $('#aiot-zone-processing-days').val(processingDays.min);
                    } else {
                        $('#aiot-zone-processing-days').val(zone.processing_days || 1);
                    }
                    
                    $('#aiot-zone-active').prop('checked', zone.is_active == 1);
                    
                    // Load location data
                    var countries = JSON.parse(zone.countries || '[]');
                    var states = JSON.parse(zone.states || '[]');
                    
                    if (countries.length > 0) {
                        $('#aiot-zone-country').val(countries[0]);
                        loadStates(countries[0]);
                    }
                    
                    if (states.length > 0) {
                        setTimeout(function() {
                            // Set multiple selected states
                            $('#aiot-zone-state').val(states);
                        }, 500);
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
        $('#aiot-zone-state').empty().append('<option value="">' + (aiot_zones_i18n?.select_state || 'Select State') + '</option>');
        
        if (!countryCode) {
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
            return;
        }
        
        $('#aiot-zone-state').prop('disabled', false);
        $('#aiot-zone-state-search').prop('disabled', false);
        
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
                        $('#aiot-zone-state').append('<option value="' + state.name + '">' + state.name + '</option>');
                    });
                }
            }
        });
    }
    
    // Filter dropdown options based on search term
    function filterDropdownOptions(dropdownSelector, searchTerm) {
        var $dropdown = $(dropdownSelector);
        var $options = $dropdown.find('option');
        
        $options.each(function() {
            var $option = $(this);
            var optionText = $option.text().toLowerCase();
            
            if (searchTerm === '' || optionText.includes(searchTerm)) {
                $option.show();
            } else {
                $option.hide();
            }
        });
        
        // If no options are visible, show a message
        var visibleOptions = $options.filter(':visible');
        if (visibleOptions.length === 0 && searchTerm !== '') {
            // If no options match, you could add a "No results found" option here
            // For now, we'll just hide all options
        }
    }
    
  
    
    // Update map based on selected locations
    function updateMap() {
        if (!map) {
            console.log('Map not available for update');
            return;
        }
        
        clearMapMarkers();
        
        var country = $('#aiot-zone-country').val();
        var states = $('#aiot-zone-state').val() || [];
        
        // If no location selected, reset map to default view
        if (!country && (!states || states.length === 0)) {
            map.setView([aiot_zones.map_center_lat, aiot_zones.map_center_lng], aiot_zones.map_zoom);
            $('#aiot-zone-coordinates').val(JSON.stringify([]));
            return;
        }
        
        // Convert single state to array for consistency
        var stateArray = Array.isArray(states) ? states : (states ? [states] : []);
        
        // Get coordinates for selected locations
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_zone_coordinates',
                country: country,
                state: stateArray.join(','), // Join multiple states with comma
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
                        $('#aiot-zone-coordinates').val(JSON.stringify(coordinates));
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
        // Get basic form data
        var zoneId = $('#aiot-zone-id').val();
        var zoneName = $('#aiot-zone-name').val();
        var zoneDescription = $('#aiot-zone-description').val();
        var zoneType = $('#aiot-zone-type').val();
        var deliveryDaysMin = $('#aiot-delivery-days-min').val();
        var deliveryDaysMax = $('#aiot-delivery-days-max').val();
        var processingDaysMin = $('#aiot-processing-days-min').val();
        var processingDaysMax = $('#aiot-processing-days-max').val();
        var zoneActive = $('#aiot-zone-active').val();
        
        // Validate required fields
        if (!zoneName) {
            alert(aiot_zones_i18n?.zone_name_required || 'Zone name is required');
            return;
        }
        
        if (!zoneType) {
            alert(aiot_zones_i18n?.zone_type_required || 'Zone type is required');
            return;
        }
        
        // Get selected locations
        var locations = [];
        $('#aiot-locations-list .location-tag').each(function() {
            var locationData = $(this).data('location-data');
            locations.push(locationData);
        });
        
        if (locations.length === 0) {
            alert(aiot_zones_i18n?.location_required || 'Please add at least one location');
            return;
        }
        
        // Prepare data for submission
        var zoneData = {
            action: 'aiot_save_zone',
            nonce: aiot_zones.nonce,
            zone_id: zoneId,
            zone_name: zoneName,
            zone_description: zoneDescription,
            zone_type: zoneType,
            delivery_days_min: deliveryDaysMin,
            delivery_days_max: deliveryDaysMax,
            processing_days_min: processingDaysMin,
            processing_days_max: processingDaysMax,
            zone_active: zoneActive,
            locations: locations
        };
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: zoneData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving zone:', error);
                alert('Error saving zone. Please try again.');
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
    
    // Export zones
    function exportZones() {
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_export_zones',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    var blob = new Blob([response.data.content], { type: 'application/json' });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'ai-order-tracker-zones-' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    alert(response.data.message);
                }
            }
        });
    }
    
    // Load zones
    function loadZones() {
        $('#aiot-zones-tbody').html('<tr><td colspan="7" class="aiot-loading-row"><div class="aiot-spinner"></div><p>Loading zones...</p></td></tr>');
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_zones',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderZones(response.data.zones);
                    updateStats(response.data.stats);
                } else {
                    $('#aiot-zones-tbody').html('<tr><td colspan="7" class="aiot-error-row">Error loading zones</td></tr>');
                }
            }
        });
    }
    
    // Filter zones
    function filterZones() {
        var searchTerm = $('#aiot-search-zones').val().toLowerCase();
        var statusFilter = $('#aiot-filter-status').val();
        var typeFilter = $('#aiot-filter-type').val();
        
        $('#aiot-zones-tbody tr').each(function() {
            var $row = $(this);
            var name = $row.find('.aiot-zone-name').text().toLowerCase();
            var status = $row.find('.aiot-zone-status').data('status');
            var type = $row.find('.aiot-zone-type').text().toLowerCase();
            
            var matchesSearch = searchTerm === '' || name.includes(searchTerm);
            var matchesStatus = statusFilter === 'all' || status === statusFilter;
            var matchesType = typeFilter === 'all' || type === typeFilter;
            
            if (matchesSearch && matchesStatus && matchesType) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        updateShowingCount();
    }
    
    // Render zones
    function renderZones(zones) {
        var tbody = $('#aiot-zones-tbody');
        tbody.empty();
        
        if (zones.length === 0) {
            tbody.append('<tr><td colspan="7" class="aiot-no-data">No zones found</td></tr>');
            return;
        }
        
        $.each(zones, function(index, zone) {
            var row = '<tr>' +
                '<td class="aiot-col-checkbox"><input type="checkbox" class="aiot-zone-checkbox" data-zone-id="' + zone.id + '"></td>' +
                '<td class="aiot-col-name"><span class="aiot-zone-name">' + zone.name + '</span></td>' +
                '<td class="aiot-col-type"><span class="aiot-zone-type">' + zone.type + '</span></td>' +
                '<td class="aiot-col-delivery">' + zone.delivery_days + '</td>' +
                '<td class="aiot-col-countries">' + zone.countries_count + '</td>' +
                '<td class="aiot-col-status"><span class="aiot-zone-status" data-status="' + (zone.is_active ? 'active' : 'inactive') + '">' + 
                (zone.is_active ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>') + '</td>' +
                '<td class="aiot-col-actions">' +
                '<button type="button" class="button aiot-edit-zone" data-zone-id="' + zone.id + '">Edit</button>' +
                '<button type="button" class="button aiot-delete-zone" data-zone-id="' + zone.id + '">Delete</button>' +
                '</td>' +
                '</tr>';
            tbody.append(row);
        });
        
        updateShowingCount();
    }
    
    // Update stats
    function updateStats(stats) {
        $('#aiot-total-zones').text(stats.total || 0);
        $('#aiot-active-zones').text(stats.active || 0);
        $('#aiot-countries-covered').text(stats.countries || 0);
        $('#aiot-avg-delivery-days').text(stats.avg_delivery_days || 0);
    }
    
    // Update showing count
    function updateShowingCount() {
        var totalRows = $('#aiot-zones-tbody tr').length;
        var visibleRows = $('#aiot-zones-tbody tr:visible').length;
        $('#aiot-showing-count').text(visibleRows);
        $('#aiot-total-count').text(totalRows);
    }
    
    // Initialize on document ready
    init();
});