/**
 * AI Order Tracker Public JavaScript
 */

(function($) {
    'use strict';

    // Initialize Vue app when DOM is ready
    $(document).ready(function() {
        initTrackingApp();
    });

    function initTrackingApp() {
        // Check if Vue is available
        if (typeof Vue === 'undefined') {
            console.error('Vue.js is not loaded');
            return;
        }

        // Create Vue app
        const { createApp } = Vue;

        const app = createApp({
            data() {
                return {
                    trackingId: '',
                    trackingInfo: null,
                    loading: false,
                    error: null,
                    settings: aiot_public.settings,
                    autoUpdateInterval: null,
                    map: null,
                    mapInitialized: false
                };
            },
            computed: {
                progressSteps() {
                    if (!this.trackingInfo) return [];
                    
                    const steps = [
                        { status: 'processing', label: 'Processing', icon: '⚙️' },
                        { status: 'confirmed', label: 'Confirmed', icon: '✅' },
                        { status: 'packed', label: 'Packed', icon: '📦' },
                        { status: 'shipped', label: 'Shipped', icon: '🚚' },
                        { status: 'in_transit', label: 'In Transit', icon: '🚛' },
                        { status: 'out_for_delivery', label: 'Out for Delivery', icon: '🏃' },
                        { status: 'delivered', label: 'Delivered', icon: '🎉' }
                    ];

                    const currentStep = this.trackingInfo.current_step || 0;
                    
                    return steps.map((step, index) => ({
                        ...step,
                        active: index + 1 === currentStep,
                        completed: index + 1 < currentStep
                    }));
                }
            },
            methods: {
                async trackOrder() {
                    if (!this.trackingId.trim()) {
                        this.error = aiot_public.strings.invalid_id;
                        return;
                    }

                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await this.makeRequest('aiot_track_order', {
                            tracking_id: this.trackingId
                        });

                        if (response.success) {
                            this.trackingInfo = response.data;
                            this.error = null;
                            
                            // Initialize map if enabled
                            if (this.settings.show_map && !this.mapInitialized) {
                                this.$nextTick(() => {
                                    this.initializeMap();
                                });
                            }
                            
                            // Start auto-update if enabled
                            if (this.settings.auto_update && this.trackingInfo.status !== 'delivered') {
                                this.startAutoUpdate();
                            }
                        } else {
                            this.error = response.data.message || aiot_public.strings.error;
                        }
                    } catch (error) {
                        console.error('Tracking error:', error);
                        this.error = aiot_public.strings.error;
                    } finally {
                        this.loading = false;
                    }
                },

                async makeRequest(action, data = {}) {
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('nonce', aiot_public.nonce);
                    
                    Object.keys(data).forEach(key => {
                        formData.append(key, data[key]);
                    });

                    const response = await fetch(aiot_public.ajax_url, {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return await response.json();
                },

                resetForm() {
                    this.trackingId = '';
                    this.trackingInfo = null;
                    this.error = null;
                    this.stopAutoUpdate();
                    
                    if (this.map) {
                        this.map.remove();
                        this.map = null;
                        this.mapInitialized = false;
                    }
                },

                formatDate(dateString) {
                    if (!dateString) return aiot_public.strings.not_found;
                    
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                formatDateTime(dateString) {
                    if (!dateString) return aiot_public.strings.not_found;
                    
                    const date = new Date(dateString);
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                initializeMap() {
                    if (typeof L === 'undefined') {
                        console.warn('Leaflet is not loaded');
                        return;
                    }

                    // Check if map container exists
                    const mapContainer = document.getElementById('aiot-tracking-map');
                    if (!mapContainer) return;

                    // Initialize map
                    this.map = L.map('aiot-tracking-map').setView([20, 0], 2);

                    // Add tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(this.map);

                    // Add route markers if location data is available
                    if (this.trackingInfo.origin_address && this.trackingInfo.destination_address) {
                        this.addRouteMarkers();
                    }

                    this.mapInitialized = true;
                },

                addRouteMarkers() {
                    if (!this.map || !this.trackingInfo) return;

                    // Geocoding service would be needed here
                    // For now, we'll use placeholder coordinates
                    
                    const originCoords = [40.7128, -74.0060]; // New York
                    const destinationCoords = [34.0522, -118.2437]; // Los Angeles
                    const currentCoords = this.trackingInfo.location ? 
                        [37.7749, -122.4194] : null; // San Francisco

                    // Add origin marker
                    L.marker(originCoords)
                        .addTo(this.map)
                        .bindPopup('<b>Origin</b><br>' + this.trackingInfo.origin_address);

                    // Add destination marker
                    L.marker(destinationCoords)
                        .addTo(this.map)
                        .bindPopup('<b>Destination</b><br>' + this.trackingInfo.destination_address);

                    // Add current location marker if available
                    if (currentCoords) {
                        L.marker(currentCoords)
                            .addTo(this.map)
                            .bindPopup('<b>Current Location</b><br>' + this.trackingInfo.location);
                    }

                    // Fit map to show all markers
                    const group = new L.featureGroup([
                        L.marker(originCoords),
                        L.marker(destinationCoords)
                    ]);

                    if (currentCoords) {
                        group.addLayer(L.marker(currentCoords));
                    }

                    this.map.fitBounds(group.getBounds().pad(0.1));
                },

                startAutoUpdate() {
                    if (this.autoUpdateInterval) {
                        clearInterval(this.autoUpdateInterval);
                    }

                    this.autoUpdateInterval = setInterval(async () => {
                        if (!this.trackingId || !this.trackingInfo) return;

                        try {
                            const response = await this.makeRequest('aiot_track_order', {
                                tracking_id: this.trackingId
                            });

                            if (response.success) {
                                const oldStatus = this.trackingInfo.status;
                                this.trackingInfo = response.data;
                                
                                // Show notification if status changed
                                if (oldStatus !== this.trackingInfo.status) {
                                    this.showStatusNotification(this.trackingInfo.status);
                                }

                                // Stop auto-update if delivered
                                if (this.trackingInfo.status === 'delivered') {
                                    this.stopAutoUpdate();
                                }
                            }
                        } catch (error) {
                            console.error('Auto-update error:', error);
                        }
                    }, 30000); // Update every 30 seconds
                },

                stopAutoUpdate() {
                    if (this.autoUpdateInterval) {
                        clearInterval(this.autoUpdateInterval);
                        this.autoUpdateInterval = null;
                    }
                },

                showStatusNotification(status) {
                    const statusInfo = this.trackingInfo.status_info;
                    const message = `Order status updated to: ${statusInfo.label}`;
                    
                    // Show browser notification if permitted
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Order Update', {
                            body: message,
                            icon: '/favicon.ico'
                        });
                    }

                    // Show in-app notification
                    this.showNotification(message, 'success');
                },

                showNotification(message, type = 'info') {
                    const notification = document.createElement('div');
                    notification.className = `aiot-notification aiot-notification-${type}`;
                    notification.textContent = message;
                    
                    document.body.appendChild(notification);
                    
                    // Animate in
                    setTimeout(() => {
                        notification.classList.add('aiot-notification-show');
                    }, 100);
                    
                    // Remove after 5 seconds
                    setTimeout(() => {
                        notification.classList.remove('aiot-notification-show');
                        setTimeout(() => {
                            document.body.removeChild(notification);
                        }, 300);
                    }, 5000);
                },

                // Utility methods
                debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                },

                throttle(func, limit) {
                    let inThrottle;
                    return function() {
                        const args = arguments;
                        const context = this;
                        if (!inThrottle) {
                            func.apply(context, args);
                            inThrottle = true;
                            setTimeout(() => inThrottle = false, limit);
                        }
                    };
                }
            },
            mounted() {
                // Request notification permission
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }

                // Handle keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    // Ctrl/Cmd + K to focus tracking input
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        const input = document.querySelector('.aiot-tracking-input');
                        if (input) {
                            input.focus();
                        }
                    }
                    
                    // Escape to reset form
                    if (e.key === 'Escape' && this.trackingInfo) {
                        this.resetForm();
                    }
                });

                // Handle visibility change
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.stopAutoUpdate();
                    } else if (this.trackingInfo && this.settings.auto_update) {
                        this.startAutoUpdate();
                    }
                });
            },
            beforeUnmount() {
                this.stopAutoUpdate();
                
                if (this.map) {
                    this.map.remove();
                }
            }
        });

        // Mount the app
        app.mount('#aiot-tracking-app');
    }

    // Utility functions
    window.aiotUtils = {
        formatNumber(number) {
            return new Intl.NumberFormat().format(number);
        },

        formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        formatDistance(distance) {
            if (distance < 1000) {
                return `${Math.round(distance)} m`;
            } else {
                return `${(distance / 1000).toFixed(1)} km`;
            }
        },

        formatWeight(weight) {
            if (weight < 1) {
                return `${Math.round(weight * 1000)} g`;
            } else {
                return `${weight.toFixed(2)} kg`;
            }
        },

        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        isValidPhone(phone) {
            const regex = /^[\d\s\-\+\(\)]+$/;
            return regex.test(phone) && phone.replace(/\D/g, '').length >= 10;
        },

        sanitizeInput(input) {
            const div = document.createElement('div');
            div.textContent = input;
            return div.innerHTML;
        },

        copyToClipboard(text) {
            return navigator.clipboard.writeText(text).then(() => {
                return true;
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                return false;
            });
        },

        shareContent(data) {
            if (navigator.share) {
                return navigator.share(data);
            } else {
                return Promise.reject(new Error('Web Share API not supported'));
            }
        },

        animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = Math.round(current);
            }, 16);
        },

        isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        loadStyle(href) {
            return new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.onload = resolve;
                link.onerror = reject;
                document.head.appendChild(link);
            });
        }
    };

    // Add notification styles dynamically
    const notificationStyles = document.createElement('style');
    notificationStyles.textContent = `
        .aiot-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 300px;
            font-size: 14px;
            line-height: 1.4;
        }

        .aiot-notification.aiot-notification-show {
            transform: translateX(0);
        }

        .aiot-notification.aiot-notification-success {
            background: #28a745;
        }

        .aiot-notification.aiot-notification-error {
            background: #dc3545;
        }

        .aiot-notification.aiot-notification-warning {
            background: #ffc107;
            color: #333;
        }

        .aiot-notification.aiot-notification-info {
            background: #17a2b8;
        }

        @media (max-width: 768px) {
            .aiot-notification {
                right: 10px;
                left: 10px;
                max-width: none;
                transform: translateY(-100px);
            }

            .aiot-notification.aiot-notification-show {
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(notificationStyles);

})(jQuery);