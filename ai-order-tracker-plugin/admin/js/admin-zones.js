jQuery(document).ready(function($) {
    'use strict';
    
    // Variables
    var currentZoneId = 0;
    
    // Initialize
    function init() {
        bindEvents();
        initMap();
        loadZones();
        loadAllCountries();
    }
    
    // Bind events
    function bindEvents() {
        // Add zone button
        $('#aiot-add-zone').on('click', function() {
            openZoneModal();
        });
        
        // Install default zones button
        $('#aiot-install-default-zones').on('click', function() {
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
        
        // Edit zone buttons - using event delegation
        $(document).on('click', '.aiot-edit-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var zoneId = $(this).data('zone-id');
            console.log('Edit button clicked for zone ID:', zoneId);
            editZone(zoneId);
        });
        
        // Delete zone buttons - using event delegation
        $(document).on('click', '.aiot-delete-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var zoneId = $(this).data('zone-id');
            console.log('Delete button clicked for zone ID:', zoneId);
            deleteZone(zoneId);
        });
        
        // Modal close buttons
        $('.aiot-modal-close').on('click', function() {
            closeZoneModal();
        });
        
        // Zone type selection buttons
        $('.aiot-select-type-btn').on('click', function() {
            var zoneType = $(this).data('type');
            selectZoneTypeAndProceed(zoneType);
        });
        
        // Zone type card selection
        $('.aiot-zone-type-card').on('click', function() {
            var zoneType = $(this).data('type');
            // Remove active class from all cards
            $('.aiot-zone-type-card').removeClass('active');
            // Add active class to selected card
            $(this).addClass('active');
        });
        
        // Back to type selection button
        $('.aiot-back-to-type').on('click', function() {
            showZoneTypeStep();
        });
        
        // Change type button
        $('#aiot-change-type-btn').on('click', function() {
            showZoneTypeStep();
        });
        
        // Zone type change (for backward compatibility)
        $('#aiot-zone-type').on('change', function() {
            var zoneType = $(this).val();
            handleZoneTypeChange(zoneType);
        });
        
        // Country change
        $('#aiot-zone-country').on('change', function() {
            var selectedCountries = $(this).val() || [];
            var zoneType = $('#aiot-zone-type').val();
            
            console.log('Countries changed to:', selectedCountries, 'Zone type:', zoneType);
            
            if (zoneType === 'state') {
                // For state type, load states for the selected countries
                if (selectedCountries.length > 0) {
                    // For now, load states for the first selected country
                    // This could be extended to handle multiple countries in the future
                    loadStates(selectedCountries[0]);
                    // Update map to show states for the selected country
                    setTimeout(function() {
                        showStateSelectionOnMap();
                    }, 100);
                }
            } else if (zoneType === 'country') {
                // For country type, update map to show country selection
                showCountrySelectionOnMap();
            }
            
            updateMap();
        });
        
        // State change - update cities count automatically
        $('#aiot-zone-state').on('change', function() {
            var selectedStates = $(this).val() || [];
            var selectedCountries = $('#aiot-zone-country').val() || [];
            console.log('States changed:', selectedStates, 'Countries:', selectedCountries);
            
            updateCitiesCount();
            updateMap();
            
            // Update map info with selected states count
            if (selectedCountries.length > 0 && selectedStates.length > 0) {
                updateMapInfo(selectedStates.join(', '), 'state', selectedStates.length);
            }
        });
        
        // Country search functionality
        $('#aiot-zone-country-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            filterDropdownOptions('#aiot-zone-country', searchTerm);
        });
        
        // State search functionality
        $('#aiot-zone-state-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            filterDropdownOptions('#aiot-zone-state', searchTerm);
        });
        
        // Form submit
        $('#aiot-zone-form').on('submit', function(e) {
            e.preventDefault();
            saveZone();
        });
        
        // Cancel button
        $('.aiot-modal-cancel').on('click', function() {
            closeZoneModal();
        });
    }
    
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
                    
                    // Enable country select and search
                    $countrySelect.prop('disabled', false);
                    $('#aiot-zone-country-search').prop('disabled', false);
                    
                    console.log('Loaded ' + countries.length + ' countries');
                } else {
                    console.error('Failed to load countries:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error while loading countries:', error);
            }
        });
    }
    
    // Load states for country
    function loadStates(countryCode) {
        if (!countryCode) {
            $('#aiot-zone-state').empty().append('<option value="">' + (aiot_zones_i18n?.select_state || 'Select State') + '</option>');
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
            return;
        }
        
        // Show loading indicator
        $('#aiot-zone-state').empty().append('<option value="">' + (aiot_zones_i18n?.loading || 'Loading...') + '</option>');
        $('#aiot-zone-state').prop('disabled', true);
        $('#aiot-zone-state-search').prop('disabled', true);
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_states_for_country',
                nonce: aiot_zones.nonce,
                country: countryCode
            },
            success: function(response) {
                if (response.success) {
                    var states = response.data.states;
                    var $stateSelect = $('#aiot-zone-state');
                    
                    $stateSelect.empty().append('<option value="">' + (aiot_zones_i18n?.select_state || 'Select State') + '</option>');
                    
                    $.each(states, function(index, state) {
                        $stateSelect.append('<option value="' + state.name + '">' + state.name + '</option>');
                    });
                    
                    // Enable state select and search
                    $stateSelect.prop('disabled', false);
                    $('#aiot-zone-state-search').prop('disabled', false);
                    
                    console.log('Loaded ' + states.length + ' states for country ' + countryCode);
                } else {
                    console.error('Failed to load states:', response.data.message);
                    $('#aiot-zone-state').empty().append('<option value="">' + (aiot_zones_i18n?.no_states || 'No states found') + '</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error while loading states:', error);
                $('#aiot-zone-state').empty().append('<option value="">' + (aiot_zones_i18n?.error_loading || 'Error loading states') + '</option>');
            }
        });
    }
    
    // Handle zone type change
    function handleZoneTypeChange(zoneType) {
        console.log('Zone type changed to:', zoneType);
        
        // Reset location fields
        resetLocationFields();
        
        if (zoneType === 'country') {
            // For country type, enable country selection and disable state selection
            $('#aiot-zone-country').prop('disabled', false);
            $('#aiot-zone-country-search').prop('disabled', false);
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
            
            // Show country selection interface on map
            showCountrySelectionOnMap();
        } else if (zoneType === 'state') {
            // For state type, enable country selection and state will be enabled after country is selected
            $('#aiot-zone-country').prop('disabled', false);
            $('#aiot-zone-country-search').prop('disabled', false);
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
            
            // Show state selection interface on map
            showStateSelectionOnMap();
        }
        
        // Update map display
        updateMap();
    }
    
    // Show country selection interface on map
    function showCountrySelectionOnMap() {
        var map = window.aiotMap;
        if (!map) return;
        
        // Clear existing layers except the base tile layer
        if (window.aiotMapLayers) {
            if (window.aiotMapLayers.countries) {
                map.removeLayer(window.aiotMapLayers.countries);
            }
            if (window.aiotMapLayers.states) {
                map.removeLayer(window.aiotMapLayers.states);
            }
            if (window.aiotMapLayers.cities) {
                map.removeLayer(window.aiotMapLayers.cities);
            }
        }
        
        // Load and show countries for selection
        loadCountriesForSelection();
    }
    
    // Show state selection interface on map
    function showStateSelectionOnMap() {
        var map = window.aiotMap;
        if (!map) return;
        
        // Clear existing layers except the base tile layer
        if (window.aiotMapLayers) {
            if (window.aiotMapLayers.countries) {
                map.removeLayer(window.aiotMapLayers.countries);
            }
            if (window.aiotMapLayers.states) {
                map.removeLayer(window.aiotMapLayers.states);
            }
            if (window.aiotMapLayers.cities) {
                map.removeLayer(window.aiotMapLayers.cities);
            }
        }
        
        // Load and show states for selection
        loadStatesForSelection();
    }
    
    // Two-step modal process functions
    function selectZoneTypeAndProceed(zoneType) {
        console.log('Selected zone type:', zoneType);
        
        // Set the zone type in the form
        $('#aiot-zone-type').val(zoneType);
        
        // Update the selected type display
        var typeDisplay = zoneType === 'country' ? 'Country' : 'State/Province';
        $('#aiot-selected-type-display').text(typeDisplay);
        
        // Update the configuration title and description
        var configTitle = zoneType === 'country' ? 
            'Configure Your Country-Based Zone' : 
            'Configure Your State/Province-Based Zone';
        $('#aiot-config-title').text(configTitle);
        
        var configDescription = zoneType === 'country' ?
            'Select countries for your delivery zone. All cities within selected countries will be included.' :
            'Select states/provinces for your delivery zone. All cities within selected states will be included.';
        $('#aiot-config-description').text(configDescription);
        
        // Show the configuration step
        showZoneConfigStep();
        
        // Initialize the zone type handling after a short delay
        setTimeout(function() {
            handleZoneTypeChange(zoneType);
        }, 100);
    }
    
    function showZoneTypeStep() {
        // Hide config step and show type step
        $('#aiot-zone-config-step').hide();
        $('#aiot-zone-type-step').show();
        
        // Update modal title
        $('#aiot-modal-title').text('Add New Zone');
        
        // Reset any active selections
        $('.aiot-zone-type-card').removeClass('active');
    }
    
    function showZoneConfigStep() {
        // Hide type step and show config step
        $('#aiot-zone-type-step').hide();
        $('#aiot-zone-config-step').show();
        
        // Update modal title
        var zoneType = $('#aiot-zone-type').val();
        var modalTitle = zoneType === 'country' ? 'Configure Country Zone' : 'Configure State/Province Zone';
        $('#aiot-modal-title').text(modalTitle);
        
        // Initialize map if not already initialized
        if (!window.aiotMap) {
            setTimeout(function() {
                initMap();
            }, 100);
        }
    }
    
    // Load countries for selection
    function loadCountriesForSelection() {
        var map = window.aiotMap;
        if (!map) return;
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_load_countries',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success && response.data.countries) {
                    displayCountriesForSelection(response.data.countries);
                }
            },
            error: function() {
                console.error('Failed to load countries for selection');
            }
        });
    }
    
    // Display countries for selection
    function displayCountriesForSelection(countries) {
        var map = window.aiotMap;
        if (!map) return;
        
        // Create a layer group for selectable countries
        var countriesLayer = L.layerGroup().addTo(map);
        window.aiotMapLayers.countries = countriesLayer;
        
        // Add clickable markers for each country
        countries.forEach(function(country) {
            if (country.latlng && country.latlng.length >= 2) {
                var marker = L.marker([country.latlng[0], country.latlng[1]], {
                    title: country.name,
                    countryData: country
                });
                
                // Add popup with country selection
                marker.bindPopup(`
                    <div style="text-align: center;">
                        <h4>${country.name}</h4>
                        <p><strong>Code:</strong> ${country.code}</p>
                        <button type="button" class="button button-small" onclick="selectCountryForZone('${country.code}', '${country.name}')">
                            Select Country
                        </button>
                    </div>
                `);
                
                // Make marker clickable for selection
                marker.on('click', function() {
                    selectCountryForZone(country.code, country.name);
                });
                
                countriesLayer.addLayer(marker);
            }
        });
        
        console.log('Added ' + countries.length + ' countries for selection');
    }
    
    // Load states for selection
    function loadStatesForSelection() {
        var map = window.aiotMap;
        if (!map) return;
        
        // First get the selected country
        var selectedCountry = $('#aiot-zone-country').val();
        
        if (!selectedCountry) {
            console.log('No country selected for state selection');
            return;
        }
        
        // Load states data via AJAX
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_states_geojson',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success && response.data.geojson) {
                    try {
                        var geojsonData = JSON.parse(response.data.geojson);
                        displayStatesForSelection(geojsonData, selectedCountry);
                    } catch (e) {
                        console.error('Error parsing GeoJSON:', e);
                    }
                }
            },
            error: function() {
                console.error('Failed to load states GeoJSON');
            }
        });
    }
    
    // Display states for selection
    function displayStatesForSelection(geojsonData, selectedCountry) {
        var map = window.aiotMap;
        if (!map) return;
        
        // Convert country code to 3-letter format for matching
        var countryCode3 = convertCountryCode(selectedCountry);
        
        // Create GeoJSON layer for states
        var statesLayer = L.geoJSON(geojsonData, {
            style: {
                color: '#3388ff',
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.3
            },
            filter: function(feature) {
                // Only show states for the selected country
                return feature.properties && feature.properties.shapeGroup === countryCode3;
            },
            onEachFeature: function(feature, layer) {
                if (feature.properties && feature.properties.name) {
                    var popupContent = `
                        <div style="text-align: center;">
                            <h4>${feature.properties.name}</h4>
                            <p><strong>Country:</strong> ${selectedCountry}</p>
                            <button type="button" class="button button-small" onclick="selectStateForZone('${feature.properties.name}', '${selectedCountry}')">
                                Select State
                            </button>
                        </div>
                    `;
                    layer.bindPopup(popupContent);
                    
                    // Make state clickable for selection
                    layer.on('click', function() {
                        selectStateForZone(feature.properties.name, selectedCountry);
                    });
                }
            }
        }).addTo(map);
        
        window.aiotMapLayers.states = statesLayer;
        
        // Fit map to show the selected country's states
        if (statesLayer.getLayers().length > 0) {
            map.fitBounds(statesLayer.getBounds());
        }
        
        console.log('States for country ' + selectedCountry + ' loaded for selection');
    }
    
    // Convert country code to 3-letter format
    function convertCountryCode(code2) {
        var mapping = {
            'US': 'USA', 'CA': 'CAN', 'MX': 'MEX', 'GB': 'GBR', 'FR': 'FRA',
            'DE': 'DEU', 'IT': 'ITA', 'ES': 'ESP', 'AU': 'AUS', 'JP': 'JPN',
            'CN': 'CHN', 'IN': 'IND', 'BR': 'BRA', 'RU': 'RUS', 'ZA': 'ZAF',
            'AR': 'ARG', 'NL': 'NLD', 'BE': 'BEL', 'CH': 'CHE', 'AT': 'AUT',
            'SE': 'SWE', 'NO': 'NOR', 'DK': 'DNK', 'FI': 'FIN', 'PL': 'POL',
            'CZ': 'CZE', 'HU': 'HUN', 'GR': 'GRC', 'PT': 'PRT', 'IE': 'IRL',
            'TR': 'TUR', 'IL': 'ISR', 'SA': 'SAU', 'AE': 'ARE', 'EG': 'EGY',
            'NG': 'NGA', 'KE': 'KEN', 'ZA': 'ZAF', 'MA': 'MAR', 'DZ': 'DZA',
            'TH': 'THA', 'VN': 'VNM', 'PH': 'PHL', 'MY': 'MYS', 'SG': 'SGP',
            'ID': 'IDN', 'PK': 'PAK', 'BD': 'BGD', 'LK': 'LKA', 'MM': 'MMR',
            'KR': 'KOR', 'KP': 'PRK', 'TW': 'TWN', 'HK': 'HKG', 'MO': 'MAC',
            'NZ': 'NZL', 'FJ': 'FJI', 'PG': 'PNG', 'SB': 'SLB', 'VU': 'VUT',
            'CL': 'CHL', 'PE': 'PER', 'CO': 'COL', 'VE': 'VEN', 'EC': 'ECU',
            'BO': 'BOL', 'PY': 'PRY', 'UY': 'URY', 'GY': 'GUY', 'SR': 'SUR',
            'GF': 'GUF', 'CU': 'CUB', 'HT': 'HTI', 'DO': 'DOM', 'JM': 'JAM',
            'TT': 'TTO', 'BB': 'BRB', 'LC': 'LCA', 'VC': 'VCT', 'GD': 'GRD',
            'AG': 'ATG', 'BS': 'BHS', 'BZ': 'BLZ', 'GT': 'GTM', 'SV': 'SLV',
            'HN': 'HND', 'NI': 'NIC', 'CR': 'CRI', 'PA': 'PAN'
        };
        return mapping[code2] || code2;
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
    
    // Initialize map
    function initMap() {
        var mapContainer = document.getElementById('aiot-zone-map');
        if (!mapContainer) {
            console.log('Map container not found');
            return;
        }
        
        // Initialize Leaflet map
        if (typeof L !== 'undefined') {
            createLeafletMap(mapContainer);
        } else {
            console.error('Leaflet library not loaded');
            // Fallback to simple map if Leaflet is not available
            createSimpleMap(mapContainer);
        }
    }
    
    // Create Leaflet map
    function createLeafletMap(container) {
        // Clear any existing content
        container.innerHTML = '';
        
        // Initialize the map
        var map = L.map(container).setView([20.0, 0.0], 2);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);
        
        // Store map reference globally for later use
        window.aiotMap = map;
        window.aiotMapLayers = {};
        
        // Load geo data
        loadGeoData();
        
        console.log('Leaflet map created successfully');
    }
    
    // Load geographical data
    function loadGeoData() {
        var map = window.aiotMap;
        if (!map) return;
        
        // Load countries data
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_load_countries',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success && response.data.countries) {
                    // Don't display countries on initial load - wait for zone type selection
                    console.log('Countries data loaded, waiting for zone type selection');
                }
            },
            error: function() {
                console.error('Failed to load countries data');
            }
        });
        
        // Load states data (GeoJSON) - but don't display initially
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_states_geojson',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success && response.data.geojson) {
                    console.log('States GeoJSON loaded, waiting for zone type selection');
                }
            },
            error: function() {
                console.error('Failed to load states GeoJSON');
            }
        });
        
        // Note: Cities data loading removed to eliminate blue pins
        console.log('Map initialized - waiting for zone type selection');
    }
    
    // Display countries on map (deprecated - use displayCountriesForSelection instead)
    function displayCountriesOnMap(countries) {
        console.log('displayCountriesOnMap is deprecated - use displayCountriesForSelection instead');
        displayCountriesForSelection(countries);
    }
    
    // Load states GeoJSON data (deprecated - use loadStatesForSelection instead)
    function loadStatesGeoJSON() {
        console.log('loadStatesGeoJSON is deprecated - use loadStatesForSelection instead');
    }
    
    // Display states on map (deprecated - use displayStatesForSelection instead)
    function displayStatesOnMap(geojsonData) {
        console.log('displayStatesOnMap is deprecated - use displayStatesForSelection instead');
    }
    
    // Load cities data (removed to eliminate blue pins)
    function loadCitiesData() {
        console.log('Cities data loading removed to eliminate blue pins');
    }
    
    // Display cities on map (removed to eliminate blue pins)
    function displayCitiesOnMap(cities) {
        console.log('Cities display removed to eliminate blue pins');
    }
    
    // Global functions for map interactions
    window.selectCountryForZone = function(countryCode, countryName) {
        console.log('Selected country:', countryCode, countryName);
        
        var zoneType = $('#aiot-zone-type').val();
        
        // Update form fields
        var currentCountries = $('#aiot-zone-country').val() || [];
        
        // Add the new country if not already selected
        if (!currentCountries.includes(countryCode)) {
            currentCountries.push(countryCode);
            $('#aiot-zone-country').val(currentCountries);
        }
        
        $('#aiot-zone-type').val('country');
        
        // Clear state selection when switching to country type
        $('#aiot-zone-state').val([]);
        $('#aiot-zone-state').prop('disabled', true);
        $('#aiot-zone-state-search').prop('disabled', true);
        
        // Show success message
        alert('Country "' + countryName + '" added to zone!');
        
        // Update map info
        updateMapInfo(currentCountries.join(', '), 'country', currentCountries.length);
        
        // Update map display
        showCountrySelectionOnMap();
    };
    
    window.selectStateForZone = function(stateName, countryCode) {
        console.log('Selected state:', stateName, countryCode);
        
        var zoneType = $('#aiot-zone-type').val();
        
        // Update form fields
        var currentCountries = $('#aiot-zone-country').val() || [];
        
        // Add the country if not already selected
        if (!currentCountries.includes(countryCode)) {
            currentCountries.push(countryCode);
            $('#aiot-zone-country').val(currentCountries);
        }
        
        $('#aiot-zone-type').val('state');
        
        // Load states for the country
        loadStates(countryCode);
        
        setTimeout(function() {
            // Get currently selected states
            var currentStates = $('#aiot-zone-state').val() || [];
            
            // Add the new state if not already selected
            if (!currentStates.includes(stateName)) {
                currentStates.push(stateName);
                $('#aiot-zone-state').val(currentStates);
            }
            
            // Update cities count
            updateCitiesCount();
            
            // Show success message
            alert('State "' + stateName + '" added to zone!');
            
            // Update map info
            updateMapInfo(stateName, 'state', currentStates.length);
        }, 500);
    };
    
    window.selectCityForZone = function(cityName, countryCode, stateName) {
        console.log('Selected city:', cityName, countryCode, stateName);
        
        var zoneType = $('#aiot-zone-type').val();
        
        // Update form fields
        var currentCountries = $('#aiot-zone-country').val() || [];
        
        // Add the country if not already selected
        if (!currentCountries.includes(countryCode)) {
            currentCountries.push(countryCode);
            $('#aiot-zone-country').val(currentCountries);
        }
        
        $('#aiot-zone-type').val('city');
        
        // Load states for the country
        loadStates(countryCode);
        
        setTimeout(function() {
            if (stateName) {
                $('#aiot-zone-state').val([stateName]);
            }
            
            // Show success message
            alert('City "' + cityName + '" added to zone!');
            
            // Update map info
            updateMapInfo(cityName, 'city', 1);
        }, 500);
    };
    
    // Update map info display
    function updateMapInfo(name, type, count) {
        if ($('#aiot-map-zone-name').length) {
            $('#aiot-map-zone-name').text(name);
            $('#aiot-map-zone-type').text(type);
            $('#aiot-map-locations').text(count + ' selected');
        }
    }
    
    // Highlight country on map
    function highlightCountry(countryCode) {
        // This function can be enhanced to highlight country boundaries
        console.log('Highlighting country:', countryCode);
    }
    
    // Create simple working map
    function createSimpleMap(container) {
        // Clear any existing content
        container.innerHTML = '';
        
        // Create a fast-loading simple map
        var mapHtml = '<div class="aiot-simple-map">' +
            '<div class="aiot-map-header">' +
            '<h4>🌍 Zone Location Map</h4>' +
            '<div class="aiot-map-controls">' +
            '<button type="button" class="button button-small" id="aiot-map-refresh">🔄</button>' +
            '<button type="button" class="button button-small" id="aiot-map-clear">🗑️</button>' +
            '</div>' +
            '</div>' +
            '<div class="aiot-map-content">' +
            '<div class="aiot-map-visual">' +
            '<div class="aiot-world-map" id="aiot-world-map">' +
            '<div class="aiot-map-marker" id="aiot-main-marker" style="display: none;">📍</div>' +
            '<div class="aiot-country-list" id="aiot-country-list"></div>' +
            '</div>' +
            '</div>' +
            '<div class="aiot-map-info">' +
            '<div class="aiot-location-summary">' +
                '<div class="aiot-summary-item">' +
                    '<span class="aiot-label">Zone:</span>' +
                    '<span class="aiot-value" id="aiot-map-zone-name">Not set</span>' +
                '</div>' +
                '<div class="aiot-summary-item">' +
                    '<span class="aiot-label">Type:</span>' +
                    '<span class="aiot-value" id="aiot-map-zone-type">Not set</span>' +
                '</div>' +
                '<div class="aiot-summary-item">' +
                    '<span class="aiot-label">Locations:</span>' +
                    '<span class="aiot-value" id="aiot-map-locations">None</span>' +
                '</div>' +
            '</div>' +
            '<div class="aiot-map-actions">' +
                '<button type="button" class="button button-primary" id="aiot-add-from-map">Add Selected to Zone</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="aiot-map-instructions">' +
            '<p><strong>Quick Start:</strong> Select countries below to add them to your zone. Click "Add Selected to Zone" when done.</p>' +
            '</div>' +
            '</div>';
        
        container.innerHTML = mapHtml;
        
        // Load countries immediately for the map
        loadCountriesForMap();
        
        console.log('Simple map created successfully');
    }
    
    // Load countries for the map
    function loadCountriesForMap() {
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
                    renderCountryList(countries);
                }
            },
            error: function() {
                console.error('Failed to load countries for map');
            }
        });
    }
    
    // Render country list for selection
    function renderCountryList(countries) {
        var $countryList = $('#aiot-country-list');
        $countryList.empty();
        
        // Create clickable country items
        var countriesHtml = '<div class="aiot-countries-grid">';
        countries.forEach(function(country) {
            countriesHtml += '<div class="aiot-country-item" data-country-code="' + country.code + '" data-country-name="' + country.name + '">' +
                '<span class="aiot-country-checkbox">□</span>' +
                '<span class="aiot-country-name">' + country.name + '</span>' +
                '</div>';
        });
        countriesHtml += '</div>';
        
        $countryList.html(countriesHtml);
        
        // Bind country selection events
        bindCountrySelectionEvents();
    }
    
    // Bind country selection events
    function bindCountrySelectionEvents() {
        // Country item click
        $(document).on('click', '.aiot-country-item', function() {
            var $item = $(this);
            var $checkbox = $item.find('.aiot-country-checkbox');
            
            if ($item.hasClass('selected')) {
                $item.removeClass('selected');
                $checkbox.text('□');
            } else {
                $item.addClass('selected');
                $checkbox.text('☑');
            }
            
            updateMapSelection();
        });
        
        // Add from map button
        $(document).on('click', '#aiot-add-from-map', function() {
            addSelectedCountriesToZone();
        });
        
        // Map refresh button
        $(document).on('click', '#aiot-map-refresh', function() {
            loadCountriesForMap();
        });
        
        // Map clear button
        $(document).on('click', '#aiot-map-clear', function() {
            clearMapSelection();
        });
    }
    
    // Update map selection display
    function updateMapSelection() {
        var selectedCountries = $('.aiot-country-item.selected');
        var selectedCount = selectedCountries.length;
        
        if (selectedCount > 0) {
            $('#aiot-main-marker').show();
            $('#aiot-map-locations').text(selectedCount + ' countries selected');
            
            // Show selected country names
            var countryNames = [];
            selectedCountries.each(function() {
                countryNames.push($(this).data('country-name'));
            });
            
            if (countryNames.length <= 3) {
                $('#aiot-map-zone-name').text(countryNames.join(', '));
            } else {
                $('#aiot-map-zone-name').text(countryNames.slice(0, 3).join(', ') + ' +' + (countryNames.length - 3));
            }
        } else {
            $('#aiot-main-marker').hide();
            $('#aiot-map-locations').text('None');
            $('#aiot-map-zone-name').text('Not set');
        }
    }
    
    // Add selected countries to zone
    function addSelectedCountriesToZone() {
        var selectedCountries = $('.aiot-country-item.selected');
        
        if (selectedCountries.length === 0) {
            alert('Please select at least one country.');
            return;
        }
        
        // Get selected country data
        var countries = [];
        var countryNames = [];
        
        selectedCountries.each(function() {
            var $item = $(this);
            countries.push($item.data('country-code'));
            countryNames.push($item.data('country-name'));
        });
        
        // Update the form fields
        $('#aiot-zone-country').val(countries[0]); // Set first country as primary
        $('#aiot-zone-type').val('country'); // Set type to country
        
        // Show success message
        alert('Added ' + countries.length + ' countries to zone: ' + countryNames.join(', '));
        
        // Close the map or switch back to form view
        $('#aiot-zone-modal').show();
        
        console.log('Added countries to zone:', countries);
    }
    
    // Clear map selection
    function clearMapSelection() {
        $('.aiot-country-item').removeClass('selected');
        $('.aiot-country-checkbox').text('□');
        $('#aiot-main-marker').hide();
        $('#aiot-map-locations').text('None');
        $('#aiot-map-zone-name').text('Not set');
    }
    
    // Update map display
    function updateMap() {
        var selectedCountries = $('#aiot-zone-country').val() || [];
        var selectedStates = $('#aiot-zone-state').val() || [];
        var zoneType = $('#aiot-zone-type').val();
        
        // Update map info if the map exists
        if ($('#aiot-map-zone-name').length) {
            if (zoneType === 'country') {
                $('#aiot-map-zone-name').text(selectedCountries.length > 0 ? selectedCountries.join(', ') : 'Not set');
                $('#aiot-map-zone-type').text('country');
                $('#aiot-map-locations').text(selectedCountries.length + ' countries selected');
            } else if (zoneType === 'state') {
                $('#aiot-map-zone-name').text(selectedStates.length > 0 ? selectedStates.join(', ') : 'Not set');
                $('#aiot-map-zone-type').text('state');
                $('#aiot-map-locations').text(selectedStates.length + ' states selected');
            } else {
                $('#aiot-map-zone-name').text('Not set');
                $('#aiot-map-zone-type').text('Not set');
                $('#aiot-map-locations').text('None');
            }
        }
        
        console.log('Map updated - Countries:', selectedCountries.length, 'States:', selectedStates.length, 'Type:', zoneType);
    }
    
    // Open zone modal
    function openZoneModal(zoneId) {
        currentZoneId = zoneId || 0;
        
        // Reset form
        $('#aiot-zone-form')[0].reset();
        $('#aiot-zone-id').val(currentZoneId);
        $('#aiot-zone-coordinates').val('');
        
        // Show modal
        $('#aiot-zone-modal').show();
        
        // Load zone data if editing
        if (currentZoneId > 0) {
            loadZoneData(currentZoneId);
        } else {
            // For new zone, show zone type selection step first
            showZoneTypeStep();
            
            // Initialize map in the background (hidden)
            setTimeout(function() {
                initMap();
            }, 100);
        }
    }
    
    // Close zone modal
    function closeZoneModal() {
        // Hide modal
        $('#aiot-zone-modal').hide();
        
        // Reset modal state
        setTimeout(function() {
            // Show zone type step and hide config step
            showZoneTypeStep();
            
            // Reset form
            $('#aiot-zone-form')[0].reset();
            $('#aiot-zone-id').val(0);
            $('#aiot-zone-coordinates').val('');
            
            // Remove active class from zone type cards
            $('.aiot-zone-type-card').removeClass('active');
            
            // Reset zone type selection
            $('#aiot-zone-type').val('');
            $('#aiot-selected-type-display').text('');
            
            // Clear location selections
            $('#aiot-zone-country').val('');
            $('#aiot-zone-state').val('');
            
            // Disable location fields
            $('#aiot-zone-country').prop('disabled', true);
            $('#aiot-zone-country-search').prop('disabled', true);
            $('#aiot-zone-state').prop('disabled', true);
            $('#aiot-zone-state-search').prop('disabled', true);
            
            // Clear map if exists
            if (window.aiotMap) {
                // Clear existing layers
                if (window.aiotMapLayers) {
                    if (window.aiotMapLayers.countries) {
                        window.aiotMap.removeLayer(window.aiotMapLayers.countries);
                    }
                    if (window.aiotMapLayers.states) {
                        window.aiotMap.removeLayer(window.aiotMapLayers.states);
                    }
                    if (window.aiotMapLayers.cities) {
                        window.aiotMap.removeLayer(window.aiotMapLayers.cities);
                    }
                }
            }
        }, 300);
    }
    
    // Load zone data for editing
    function loadZoneData(zoneId) {
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_get_zone',
                nonce: aiot_zones.nonce,
                zone_id: zoneId
            },
            success: function(response) {
                if (response.success) {
                    var zone = response.data.zone;
                    
                    // Fill form fields
                    $('#aiot-zone-name').val(zone.name);
                    $('#aiot-zone-type').val(zone.type);
                    
                    // Set delivery days
                    if (zone.delivery_days) {
                        var deliveryDays = typeof zone.delivery_days === 'string' ? JSON.parse(zone.delivery_days) : zone.delivery_days;
                        if (deliveryDays && typeof deliveryDays === 'object') {
                            $('#aiot-zone-delivery-days-min').val(deliveryDays.min || 1);
                            $('#aiot-zone-delivery-days-max').val(deliveryDays.max || 1);
                        }
                    }
                    
                    // Set processing days
                    if (zone.meta) {
                        try {
                            var meta = typeof zone.meta === 'string' ? JSON.parse(zone.meta) : zone.meta;
                            if (meta && meta.processing_days) {
                                $('#aiot-zone-processing-days').val(meta.processing_days);
                            }
                        } catch (e) {
                            console.error('Error parsing meta data:', e);
                        }
                    }
                    
                    // Load countries and states
                    if (zone.countries) {
                        var countries = typeof zone.countries === 'string' ? JSON.parse(zone.countries) : zone.countries;
                        $('#aiot-zone-country').val(countries);
                        
                        // Handle zone type change after setting countries
                        setTimeout(function() {
                            handleZoneTypeChange(zone.type);
                            
                            if (zone.type === 'state' && zone.states) {
                                var states = typeof zone.states === 'string' ? JSON.parse(zone.states) : zone.states;
                                setTimeout(function() {
                                    $('#aiot-zone-state').val(states);
                                    updateCitiesCount();
                                }, 500);
                            }
                        }, 100);
                    }
                    
                    // Set active status
                    $('input[name="is_active"]').prop('checked', zone.is_active == 1);
                    
                    // Update the selected type display for editing
                    var typeDisplay = zone.type === 'country' ? 'Country' : 'State/Province';
                    $('#aiot-selected-type-display').text(typeDisplay);
                    
                    // Show the configuration step for editing (skip zone type selection)
                    showZoneConfigStep();
                    
                    // Initialize map after loading data and handle zone type
                    setTimeout(function() {
                        initMap();
                        setTimeout(function() {
                            handleZoneTypeChange(zone.type);
                        }, 200);
                    }, 500);
                } else {
                    console.error('Failed to load zone data:', response.data.message);
                    alert('Failed to load zone data. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load zone data:', error);
                alert('Failed to load zone data. Please try again.');
            }
        });
    }
    
    // Save zone
    function saveZone() {
        // Get form data
        var zoneId = $('#aiot-zone-id').val();
        var zoneName = $('#aiot-zone-name').val();
        var zoneType = $('#aiot-zone-type').val();
        var deliveryDaysMin = $('#aiot-zone-delivery-days-min').val();
        var deliveryDaysMax = $('#aiot-zone-delivery-days-max').val();
        var processingDays = $('#aiot-zone-processing-days').val();
        var countries = $('#aiot-zone-country').val() || [];
        var states = $('#aiot-zone-state').val() || [];
        var isActive = $('input[name="is_active"]').is(':checked') ? 1 : 0;
        
        // Validate required fields
        if (!zoneName) {
            alert(aiot_zones_i18n?.zone_name_required || 'Zone name is required');
            return;
        }
        
        if (!zoneType) {
            alert(aiot_zones_i18n?.zone_type_required || 'Zone type is required');
            return;
        }
        
        if (!countries || countries.length === 0) {
            alert(aiot_zones_i18n?.country_required || 'Please select at least one country');
            return;
        }
        
        if (zoneType === 'state' && (!states || states.length === 0)) {
            alert(aiot_zones_i18n?.state_required || 'Please select at least one state');
            return;
        }
        
        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'aiot_save_zone');
        formData.append('nonce', aiot_zones.nonce);
        formData.append('zone_id', zoneId);
        formData.append('name', zoneName);
        formData.append('type', zoneType);
        formData.append('delivery_days_min', deliveryDaysMin);
        formData.append('delivery_days_max', deliveryDaysMax);
        formData.append('processing_days', processingDays);
        
        // Add countries
        for (var i = 0; i < countries.length; i++) {
            formData.append('country[]', countries[i]);
        }
        
        // Add states
        if (states && states.length > 0) {
            for (var i = 0; i < states.length; i++) {
                formData.append('state[]', states[i]);
            }
        }
        
        formData.append('is_active', isActive);
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.data.message || 'Zone saved successfully!');
                    
                    // Close modal
                    closeZoneModal();
                    
                    // Reload zones list
                    loadZones();
                } else {
                    // Show error message
                    alert(response.data.message || 'Failed to save zone. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to save zone:', error);
                alert('Failed to save zone. Please try again.');
            }
        });
    }
    
    // Delete zone
    function deleteZone(zoneId) {
        if (!confirm(aiot_zones_i18n?.confirm_delete || 'Are you sure you want to delete this zone?')) {
            return;
        }
        
        $.ajax({
            url: aiot_zones.ajax_url,
            type: 'POST',
            data: {
                action: 'aiot_delete_zone',
                nonce: aiot_zones.nonce,
                zone_id: zoneId
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.data.message || 'Zone deleted successfully!');
                    
                    // Reload zones list
                    loadZones();
                } else {
                    // Show error message
                    alert(response.data.message || 'Failed to delete zone. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to delete zone:', error);
                alert('Failed to delete zone. Please try again.');
            }
        });
    }
    
    // Install default zones
    function installDefaultZones() {
        if (!confirm(aiot_zones_i18n?.confirm_install || 'Are you sure you want to install default zones?')) {
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
                    // Show success message
                    alert(response.data.message || 'Default zones installed successfully!');
                    
                    // Reload zones list
                    loadZones();
                } else {
                    // Show error message
                    alert(response.data.message || 'Failed to install default zones. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to install default zones:', error);
                alert('Failed to install default zones. Please try again.');
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
                action: 'aiot_get_zones',
                nonce: aiot_zones.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data.zones, null, 2));
                    var downloadAnchorNode = document.createElement('a');
                    downloadAnchorNode.setAttribute("href", dataStr);
                    downloadAnchorNode.setAttribute("download", "zones_export.json");
                    document.body.appendChild(downloadAnchorNode); // required for firefox
                    downloadAnchorNode.click();
                    downloadAnchorNode.remove();
                } else {
                    alert(response.data.message || 'Failed to export zones. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to export zones:', error);
                alert('Failed to export zones. Please try again.');
            }
        });
    }
    
    // Load zones
    function loadZones() {
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
                    console.error('Failed to load zones:', response.data.message);
                    $('#aiot-zones-tbody').html('<tr><td colspan="7" class="aiot-error">Failed to load zones. Please try again.</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load zones:', error);
                $('#aiot-zones-tbody').html('<tr><td colspan="7" class="aiot-error">Failed to load zones. Please try again.</td></tr>');
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
            var type = $row.find('.aiot-zone-type').text();
            var status = $row.find('.aiot-zone-status').data('status');
            
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
            // Parse delivery days
            var deliveryDaysText = '1';
            if (zone.delivery_days) {
                try {
                    var deliveryDays = typeof zone.delivery_days === 'string' ? JSON.parse(zone.delivery_days) : zone.delivery_days;
                    if (deliveryDays && typeof deliveryDays === 'object') {
                        if (deliveryDays.min && deliveryDays.max) {
                            deliveryDaysText = deliveryDays.min + ' - ' + deliveryDays.max;
                        } else if (deliveryDays.min) {
                            deliveryDaysText = deliveryDays.min;
                        } else if (deliveryDays.max) {
                            deliveryDaysText = deliveryDays.max;
                        }
                    } else if (deliveryDays && typeof deliveryDays === 'number') {
                        deliveryDaysText = deliveryDays.toString();
                    } else if (deliveryDays && typeof deliveryDays === 'string') {
                        deliveryDaysText = deliveryDays;
                    }
                } catch (e) {
                    console.error('Error parsing delivery days:', e);
                    deliveryDaysText = '1';
                }
            }
            
            // Parse countries count and names
            var countriesCount = 0;
            var countriesText = 'None';
            if (zone.countries) {
                try {
                    var countries = typeof zone.countries === 'string' ? JSON.parse(zone.countries) : zone.countries;
                    countriesCount = Array.isArray(countries) ? countries.length : 0;
                    
                    if (countriesCount > 0) {
                        if (countriesCount <= 3) {
                            countriesText = countries.join(', ');
                        } else {
                            countriesText = countries.slice(0, 3).join(', ') + ' +' + (countriesCount - 3);
                        }
                    }
                } catch (e) {
                    console.error('Error parsing countries:', e);
                }
            }
            
            var row = '<tr>' +
                '<td class="aiot-col-checkbox"><input type="checkbox" class="aiot-zone-checkbox" data-zone-id="' + zone.id + '"></td>' +
                '<td class="aiot-col-name"><span class="aiot-zone-name">' + (zone.name || '') + '</span></td>' +
                '<td class="aiot-col-type"><span class="aiot-zone-type">' + (zone.type || '') + '</span></td>' +
                '<td class="aiot-col-delivery">' + deliveryDaysText + '</td>' +
                '<td class="aiot-col-countries" title="' + countriesText + '">' + countriesText + '</td>' +
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