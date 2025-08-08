/**
 * AI Order Tracker Vue App
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if Vue is available
    if (typeof Vue === 'undefined') {
        console.error('Vue.js is not loaded');
        return;
    }

    // Check if app container exists
    const appContainer = document.getElementById('aiot-tracking-app');
    if (!appContainer) {
        console.error('Tracking app container not found');
        return;
    }

    // Initialize Vue app
    const { createApp } = Vue;
    
    const app = createApp({
        data() {
            return {
                trackingId: '',
                trackingInfo: null,
                loading: false,
                error: '',
                progressSteps: [
                    { status: 'processing', label: aiot_public.strings.processing, icon: '⚙️', active: false, completed: false },
                    { status: 'confirmed', label: aiot_public.strings.confirmed, icon: '✅', active: false, completed: false },
                    { status: 'packed', label: aiot_public.strings.packed, icon: '📦', active: false, completed: false },
                    { status: 'shipped', label: aiot_public.strings.shipped, icon: '🚚', active: false, completed: false },
                    { status: 'in_transit', label: aiot_public.strings.in_transit, icon: '🚛', active: false, completed: false },
                    { status: 'out_for_delivery', label: aiot_public.strings.out_for_delivery, icon: '🏃', active: false, completed: false },
                    { status: 'delivered', label: aiot_public.strings.delivered, icon: '🎉', active: false, completed: false }
                ],
                settings: aiot_public.settings,
                atts: aiot_public.settings
            };
        },
        methods: {
            async trackOrder() {
                if (!this.trackingId.trim()) {
                    this.error = aiot_public.strings.invalid_id;
                    return;
                }

                this.loading = true;
                this.error = '';
                this.trackingInfo = null;

                try {
                    const response = await fetch(aiot_public.api_url + 'track/' + encodeURIComponent(this.trackingId), {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': aiot_public.nonce
                        }
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.trackingInfo = data.data;
                        this.updateProgressSteps();
                    } else {
                        this.error = data.message || aiot_public.strings.not_found;
                    }
                } catch (error) {
                    this.loading = false;
                    this.error = aiot_public.strings.error;
                    console.error('Tracking error:', error);
                } finally {
                    this.loading = false;
                }
            },
            resetForm() {
                this.trackingId = '';
                this.trackingInfo = null;
                this.error = '';
            },
            updateProgressSteps() {
                if (!this.trackingInfo) return;

                this.progressSteps.forEach(step => {
                    step.active = step.status === this.trackingInfo.status;
                    step.completed = this.getStepNumber(step.status) < this.getStepNumber(this.trackingInfo.status);
                });
            },
            getStepNumber(status) {
                const steps = ['processing', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'];
                return steps.indexOf(status);
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
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            getAnimationUrl(status) {
                const animations = {
                    'processing': aiot_public.plugin_url + 'assets/animations/processing.json',
                    'confirmed': aiot_public.plugin_url + 'assets/animations/order-confirmed.json',
                    'packed': aiot_public.plugin_url + 'assets/animations/order-packed.json',
                    'shipped': aiot_public.plugin_url + 'assets/animations/in-transit.json',
                    'in_transit': aiot_public.plugin_url + 'assets/animations/in-transit.json',
                    'out_for_delivery': aiot_public.plugin_url + 'assets/animations/out-for-delivery.json',
                    'delivered': aiot_public.plugin_url + 'assets/animations/arrived-hub.json'
                };
                return animations[status] || animations.processing;
            },
            getAnimationSpeed() {
                const speeds = {
                    'slow': 0.5,
                    'normal': 1,
                    'fast': 1.5
                };
                return speeds[this.settings.animation_speed] || speeds.normal;
            },
            verifyDelivery() {
                const orderId = prompt('<?php _e('Please enter your order ID to verify delivery:', 'ai-order-tracker'); ?>');
                if (!orderId) return;
                
                const trackingId = this.trackingInfo.tracking_id;
                
                // Show loading state
                const button = document.querySelector('.aiot-verify-delivery-button');
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = '<?php _e('Verifying...', 'ai-order-tracker'); ?>';
                
                fetch(aiot_public.api_url + 'verify-delivery', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': aiot_public.nonce
                    },
                    body: JSON.stringify({
                        tracking_id: trackingId,
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update tracking info to delivered status
                        this.trackingInfo.status = 'delivered';
                        this.trackingInfo.progress = 100;
                        this.trackingInfo.status_info = {
                            label: aiot_public.strings.delivered,
                            color: '#28a745'
                        };
                        
                        // Add delivery event to timeline
                        const deliveryEvent = {
                            event_type: 'status_update',
                            event_status: 'delivered',
                            location: this.trackingInfo.location || 'Customer Address',
                            description: 'Package delivered successfully - verified by customer',
                            timestamp: new Date().toISOString(),
                            latitude: null,
                            longitude: null
                        };
                        
                        if (!this.trackingInfo.tracking_events) {
                            this.trackingInfo.tracking_events = [];
                        }
                        this.trackingInfo.tracking_events.push(deliveryEvent);
                        
                        // Update progress steps
                        this.updateProgressSteps();
                        
                        alert(data.data.message);
                    } else {
                        alert(data.data.message || '<?php _e('Verification failed. Please check your order ID and try again.', 'ai-order-tracker'); ?>');
                    }
                })
                .catch(error => {
                    console.error('Verification error:', error);
                    alert('<?php _e('An error occurred during verification. Please try again.', 'ai-order-tracker'); ?>');
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = originalText;
                });
            }
        },
        mounted() {
            // Auto-track if tracking ID is in URL
            const urlParams = new URLSearchParams(window.location.search);
            const trackingId = urlParams.get('tracking_id');
            if (trackingId) {
                this.trackingId = trackingId;
                this.trackOrder();
            }
        }
    });

    // Mount the app
    app.mount('#aiot-tracking-app');
});