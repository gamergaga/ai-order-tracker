/**
 * AI Order Tracker Progress Animations
 */

(function($) {
    'use strict';

    // Animation controller
    class ProgressAnimations {
        constructor() {
            this.animations = new Map();
            this.lottieAnimations = new Map();
            this.observer = null;
            this.init();
        }

        init() {
            this.setupIntersectionObserver();
            this.initializeLottieAnimations();
            this.setupProgressAnimations();
            this.setupTimelineAnimations();
            this.setupMapAnimations();
        }

        setupIntersectionObserver() {
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.animateElement(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });
            }
        }

        animateElement(element) {
            const animationType = element.dataset.animation;
            
            switch (animationType) {
                case 'progress':
                    this.animateProgressBar(element);
                    break;
                case 'counter':
                    this.animateCounter(element);
                    break;
                case 'fade-in':
                    this.animateFadeIn(element);
                    break;
                case 'slide-in':
                    this.animateSlideIn(element);
                    break;
                case 'scale':
                    this.animateScale(element);
                    break;
                case 'pulse':
                    this.animatePulse(element);
                    break;
                case 'lottie':
                    this.playLottieAnimation(element);
                    break;
            }
        }

        animateProgressBar(progressBar) {
            const targetWidth = progressBar.dataset.target || '0';
            const duration = parseInt(progressBar.dataset.duration || '1000');
            const easing = progressBar.dataset.easing || 'easeOutCubic';
            
            // Reset width
            progressBar.style.width = '0%';
            
            // Animate to target width
            this.animateValue({
                element: progressBar,
                start: 0,
                end: parseFloat(targetWidth),
                duration: duration,
                easing: easing,
                onUpdate: (value) => {
                    progressBar.style.width = value + '%';
                }
            });
        }

        animateCounter(counter) {
            const target = parseFloat(counter.dataset.target || '0');
            const duration = parseInt(counter.dataset.duration || '1000');
            const decimals = parseInt(counter.dataset.decimals || '0');
            const prefix = counter.dataset.prefix || '';
            const suffix = counter.dataset.suffix || '';
            
            this.animateValue({
                element: counter,
                start: 0,
                end: target,
                duration: duration,
                onUpdate: (value) => {
                    counter.textContent = prefix + value.toFixed(decimals) + suffix;
                }
            });
        }

        animateFadeIn(element) {
            const duration = parseInt(element.dataset.duration || '600');
            const delay = parseInt(element.dataset.delay || '0');
            
            setTimeout(() => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = `opacity ${duration}ms ease, transform ${duration}ms ease`;
                
                // Force reflow
                element.offsetHeight;
                
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, delay);
        }

        animateSlideIn(element) {
            const duration = parseInt(element.dataset.duration || '600');
            const delay = parseInt(element.dataset.delay || '0');
            const direction = element.dataset.direction || 'left';
            
            setTimeout(() => {
                let transform = 'translateX(-100%)';
                if (direction === 'right') transform = 'translateX(100%)';
                if (direction === 'up') transform = 'translateY(-100%)';
                if (direction === 'down') transform = 'translateY(100%)';
                
                element.style.transform = transform;
                element.style.opacity = '0';
                element.style.transition = `opacity ${duration}ms ease, transform ${duration}ms ease`;
                
                // Force reflow
                element.offsetHeight;
                
                element.style.transform = 'translateX(0) translateY(0)';
                element.style.opacity = '1';
            }, delay);
        }

        animateScale(element) {
            const duration = parseInt(element.dataset.duration || '600');
            const delay = parseInt(element.dataset.delay || '0');
            const scale = parseFloat(element.dataset.scale || '1.1');
            
            setTimeout(() => {
                element.style.transform = 'scale(0.8)';
                element.style.opacity = '0';
                element.style.transition = `opacity ${duration}ms ease, transform ${duration}ms ease`;
                
                // Force reflow
                element.offsetHeight;
                
                element.style.transform = `scale(${scale})`;
                element.style.opacity = '1';
            }, delay);
        }

        animatePulse(element) {
            const duration = parseInt(element.dataset.duration || '1000');
            const delay = parseInt(element.dataset.delay || '0');
            
            setTimeout(() => {
                element.style.animation = `aiot-pulse ${duration}ms ease-in-out infinite`;
            }, delay);
        }

        initializeLottieAnimations() {
            // Check if Lottie is available
            if (typeof lottie === 'undefined') {
                console.warn('Lottie is not loaded');
                return;
            }

            // Initialize all Lottie animations
            document.querySelectorAll('[data-lottie]').forEach(container => {
                const animationPath = container.dataset.lottie;
                const animationName = container.dataset.name || 'animation_' + Math.random().toString(36).substr(2, 9);
                
                const animation = lottie.loadAnimation({
                    container: container,
                    renderer: 'svg',
                    loop: container.dataset.loop !== 'false',
                    autoplay: container.dataset.autoplay !== 'false',
                    path: animationPath,
                    name: animationName
                });
                
                this.lottieAnimations.set(animationName, animation);
                
                // Set up intersection observer for auto-play
                if (this.observer && container.dataset.autoplay === 'lazy') {
                    this.observer.observe(container);
                }
            });
        }

        playLottieAnimation(container) {
            const animationName = container.dataset.name;
            const animation = this.lottieAnimations.get(animationName);
            
            if (animation) {
                animation.play();
            }
        }

        setupProgressAnimations() {
            // Animate progress bars when they come into view
            document.querySelectorAll('.aiot-progress-fill').forEach(progressBar => {
                progressBar.dataset.animation = 'progress';
                progressBar.dataset.target = progressBar.style.width || '0%';
                
                if (this.observer) {
                    this.observer.observe(progressBar);
                }
            });

            // Animate counters
            document.querySelectorAll('.aiot-counter').forEach(counter => {
                counter.dataset.animation = 'counter';
                
                if (this.observer) {
                    this.observer.observe(counter);
                }
            });
        }

        setupTimelineAnimations() {
            // Animate timeline items
            document.querySelectorAll('.aiot-timeline-item').forEach((item, index) => {
                item.dataset.animation = 'fade-in';
                item.dataset.delay = (index * 100).toString();
                
                if (this.observer) {
                    this.observer.observe(item);
                }
            });

            // Animate timeline markers
            document.querySelectorAll('.aiot-timeline-marker').forEach((marker, index) => {
                marker.dataset.animation = 'scale';
                marker.dataset.delay = (index * 150).toString();
                marker.dataset.scale = '1.2';
                
                if (this.observer) {
                    this.observer.observe(marker);
                }
            });
        }

        setupMapAnimations() {
            // Animate map markers when they appear
            document.querySelectorAll('.aiot-map-marker').forEach((marker, index) => {
                marker.dataset.animation = 'scale';
                marker.dataset.delay = (index * 200).toString();
                marker.dataset.scale = '1.3';
                
                if (this.observer) {
                    this.observer.observe(marker);
                }
            });
        }

        animateValue(options) {
            const {
                element,
                start,
                end,
                duration = 1000,
                easing = 'linear',
                onUpdate = () => {},
                onComplete = () => {}
            } = options;

            const startTime = performance.now();
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const easedProgress = this.applyEasing(progress, easing);
                const value = start + (end - start) * easedProgress;
                
                onUpdate(value);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    onComplete();
                }
            };
            
            requestAnimationFrame(animate);
        }

        applyEasing(progress, easing) {
            switch (easing) {
                case 'easeInQuad':
                    return progress * progress;
                case 'easeOutQuad':
                    return progress * (2 - progress);
                case 'easeInOutQuad':
                    return progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress;
                case 'easeInCubic':
                    return progress * progress * progress;
                case 'easeOutCubic':
                    return (--progress) * progress * progress + 1;
                case 'easeInOutCubic':
                    return progress < 0.5 ? 4 * progress * progress * progress : (progress - 1) * (2 * progress - 2) * (2 * progress - 2) + 1;
                case 'easeInQuart':
                    return progress * progress * progress * progress;
                case 'easeOutQuart':
                    return 1 - (--progress) * progress * progress * progress;
                case 'easeInOutQuart':
                    return progress < 0.5 ? 8 * progress * progress * progress * progress : 1 - 8 * (--progress) * progress * progress * progress;
                case 'easeInQuint':
                    return progress * progress * progress * progress * progress;
                case 'easeOutQuint':
                    return 1 + (--progress) * progress * progress * progress * progress;
                case 'easeInOutQuint':
                    return progress < 0.5 ? 16 * progress * progress * progress * progress * progress : 1 + 16 * (--progress) * progress * progress * progress * progress;
                case 'easeInSine':
                    return 1 - Math.cos(progress * Math.PI / 2);
                case 'easeOutSine':
                    return Math.sin(progress * Math.PI / 2);
                case 'easeInOutSine':
                    return -(Math.cos(Math.PI * progress) - 1) / 2;
                case 'easeInExpo':
                    return progress === 0 ? 0 : Math.pow(2, 10 * progress - 10);
                case 'easeOutExpo':
                    return progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                case 'easeInOutExpo':
                    return progress === 0 ? 0 : progress === 1 ? 1 : progress < 0.5 ? Math.pow(2, 20 * progress - 10) / 2 : (2 - Math.pow(2, -20 * progress + 10)) / 2;
                case 'easeInCirc':
                    return 1 - Math.sqrt(1 - Math.pow(progress, 2));
                case 'easeOutCirc':
                    return Math.sqrt(1 - Math.pow(progress - 1, 2));
                case 'easeInOutCirc':
                    return progress < 0.5 ? (1 - Math.sqrt(1 - Math.pow(2 * progress, 2))) / 2 : (Math.sqrt(1 - Math.pow(-2 * progress + 2, 2)) + 1) / 2;
                case 'linear':
                default:
                    return progress;
            }
        }

        // Public methods
        observe(element) {
            if (this.observer) {
                this.observer.observe(element);
            }
        }

        unobserve(element) {
            if (this.observer) {
                this.observer.unobserve(element);
            }
        }

        disconnect() {
            if (this.observer) {
                this.observer.disconnect();
            }
        }

        playAnimation(elementName) {
            const element = document.querySelector(`[data-animation-name="${elementName}"]`);
            if (element) {
                this.animateElement(element);
            }
        }

        playLottie(animationName) {
            const animation = this.lottieAnimations.get(animationName);
            if (animation) {
                animation.play();
            }
        }

        pauseLottie(animationName) {
            const animation = this.lottieAnimations.get(animationName);
            if (animation) {
                animation.pause();
            }
        }

        stopLottie(animationName) {
            const animation = this.lottieAnimations.get(animationName);
            if (animation) {
                animation.stop();
            }
        }
    }

    // Initialize animations when DOM is ready
    $(document).ready(function() {
        // Initialize progress animations
        window.aiotAnimations = new ProgressAnimations();

        // Add custom CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes aiot-pulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
                100% {
                    transform: scale(1);
                }
            }

            @keyframes aiot-bounce {
                0%, 20%, 53%, 80%, 100% {
                    transform: translate3d(0, 0, 0);
                }
                40%, 43% {
                    transform: translate3d(0, -30px, 0);
                }
                70% {
                    transform: translate3d(0, -15px, 0);
                }
                90% {
                    transform: translate3d(0, -4px, 0);
                }
            }

            @keyframes aiot-flash {
                0%, 50%, 100% {
                    opacity: 1;
                }
                25%, 75% {
                    opacity: 0;
                }
            }

            @keyframes aiot-rubberBand {
                0% {
                    transform: scale3d(1, 1, 1);
                }
                30% {
                    transform: scale3d(1.25, 0.75, 1);
                }
                40% {
                    transform: scale3d(0.75, 1.25, 1);
                }
                50% {
                    transform: scale3d(1.15, 0.85, 1);
                }
                65% {
                    transform: scale3d(0.95, 1.05, 1);
                }
                75% {
                    transform: scale3d(1.05, 0.95, 1);
                }
                100% {
                    transform: scale3d(1, 1, 1);
                }
            }

            @keyframes aiot-shakeX {
                0%, 100% {
                    transform: translate3d(0, 0, 0);
                }
                10%, 30%, 50%, 70%, 90% {
                    transform: translate3d(-10px, 0, 0);
                }
                20%, 40%, 60%, 80% {
                    transform: translate3d(10px, 0, 0);
                }
            }

            @keyframes aiot-shakeY {
                0%, 100% {
                    transform: translate3d(0, 0, 0);
                }
                10%, 30%, 50%, 70%, 90% {
                    transform: translate3d(0, -10px, 0);
                }
                20%, 40%, 60%, 80% {
                    transform: translate3d(0, 10px, 0);
                }
            }

            @keyframes aiot-headShake {
                0% {
                    transform: translateX(0);
                }
                6.5% {
                    transform: translateX(-6px) rotateY(-9deg);
                }
                18.5% {
                    transform: translateX(5px) rotateY(7deg);
                }
                31.5% {
                    transform: translateX(-3px) rotateY(-5deg);
                }
                43.5% {
                    transform: translateX(2px) rotateY(3deg);
                }
                50% {
                    transform: translateX(0);
                }
            }

            @keyframes aiot-swing {
                15% {
                    transform: translateX(5px);
                }
                30% {
                    transform: translateX(-5px);
                }
                50% {
                    transform: translateX(3px);
                }
                65% {
                    transform: translateX(-3px);
                }
                80% {
                    transform: translateX(2px);
                }
                100% {
                    transform: translateX(0);
                }
            }

            @keyframes aiot-tada {
                0% {
                    transform: scale3d(1, 1, 1);
                }
                10%, 20% {
                    transform: scale3d(0.9, 0.9, 0.9) rotate3d(0, 0, 1, -3deg);
                }
                30%, 50%, 70%, 90% {
                    transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
                }
                40%, 60%, 80% {
                    transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
                }
                100% {
                    transform: scale3d(1, 1, 1);
                }
            }

            @keyframes aiot-wobble {
                0% {
                    transform: translate3d(0, 0, 0);
                }
                15% {
                    transform: translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
                }
                30% {
                    transform: translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
                }
                45% {
                    transform: translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
                }
                60% {
                    transform: translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
                }
                75% {
                    transform: translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
                }
                100% {
                    transform: translate3d(0, 0, 0);
                }
            }

            @keyframes aiot-jello {
                0%, 11.1%, 100% {
                    transform: translate3d(0, 0, 0);
                }
                22.2% {
                    transform: skewX(-12.5deg) skewY(-12.5deg);
                }
                33.3% {
                    transform: skewX(6.25deg) skewY(6.25deg);
                }
                44.4% {
                    transform: skewX(-3.125deg) skewY(-3.125deg);
                }
                55.5% {
                    transform: skewX(1.5625deg) skewY(1.5625deg);
                }
                66.6% {
                    transform: skewX(-0.78125deg) skewY(-0.78125deg);
                }
                77.7% {
                    transform: skewX(0.390625deg) skewY(0.390625deg);
                }
                88.8% {
                    transform: skewX(-0.1953125deg) skewY(-0.1953125deg);
                }
            }

            @keyframes aiot-heartBeat {
                0% {
                    transform: scale(1);
                }
                14% {
                    transform: scale(1.3);
                }
                28% {
                    transform: scale(1);
                }
                42% {
                    simulation: scale(1.3);
                }
                70% {
                    transform: scale(1);
                }
            }
        `;
        document.head.appendChild(style);
    });

    // Export to global scope
    window.aiotProgressAnimations = ProgressAnimations;

})(jQuery);