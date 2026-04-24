// Enhanced Landing Page JavaScript with Animations and Interactive Features
class LandingManager {
    constructor() {
        this.observer = null;
        this.init();
    }

    init() {
        this.initAnimations();
        this.loadStats();
        this.initSmoothScrolling();
        this.initMobileMenu();
        this.initCountdown();
        this.initNewsletterForm();
        this.initFeatureCards();
    }

    initAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    // Unobserve after animation to prevent re-triggering
                    this.observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all sections and cards
        document.querySelectorAll('.section, .about-card, .feature-card, .stat-card, .testimonial-card').forEach(el => {
            this.observer.observe(el);
        });

        // Floating elements animation
        this.animateFloatingElements();

        // Hero section parallax effect
        this.initParallax();
    }

    animateFloatingElements() {
        const elements = document.querySelectorAll('.floating-card');
        elements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.5}s`;
        });
    }

    initParallax() {
        const heroSection = document.querySelector('.hero');
        if (!heroSection) return;

        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroBackground = heroSection.querySelector('.hero-background');
            if (heroBackground) {
                heroBackground.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    }

    async loadStats() {
        try {
            const [usersResponse, votesResponse, electionsResponse] = await Promise.all([
                this.apiCall('stats_api.php?action=users'),
                this.apiCall('results_api.php?action=summary'),
                this.apiCall('stats_api.php?action=elections')
            ]);

            const usersData = await usersResponse.json();
            const votesData = await votesResponse.json();
            const electionsData = await electionsResponse.json();

            if (usersData.success) {
                this.animateNumber('totalUsers', usersData.count);
            }

            if (votesData.success) {
                const totalVotes = votesData.summary?.total_votes || 0;
                this.animateNumber('totalVotes', totalVotes);
            }

            if (electionsData.success) {
                this.animateNumber('totalElections', electionsData.count);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    animateNumber(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const startValue = 0;
        const duration = 2000;
        const startTime = performance.now();

        const update = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function (easeOutQuart)
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.floor(startValue + (targetValue - startValue) * easeOutQuart);

            element.textContent = currentValue.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        };

        requestAnimationFrame(update);
    }

    initSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    initMobileMenu() {
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        if (hamburger && navLinks) {
            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                hamburger.classList.toggle('active');
            });

            // Close menu when clicking a link
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    hamburger.classList.remove('active');
                });
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
                    navLinks.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            });
        }
    }

    initCountdown() {
        const countdownElement = document.getElementById('countdown');
        if (!countdownElement) return;

        // Get next election date (you can set this dynamically)
        const nextElectionDate = new Date();
        nextElectionDate.setDate(nextElectionDate.getDate() + 30); // Default: 30 days from now

        const updateCountdown = () => {
            const now = new Date();
            const diff = nextElectionDate - now;

            if (diff <= 0) {
                countdownElement.innerHTML = '<p>Election in progress!</p>';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            countdownElement.innerHTML = `
                <div class="countdown-item">
                    <span class="countdown-number">${days}</span>
                    <span class="countdown-label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${hours}</span>
                    <span class="countdown-label">Hours</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${minutes}</span>
                    <span class="countdown-label">Minutes</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${seconds}</span>
                    <span class="countdown-label">Seconds</span>
                </div>
            `;
        };

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    initNewsletterForm() {
        const newsletterForm = document.getElementById('newsletterForm');
        if (!newsletterForm) return;

        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = newsletterForm.querySelector('input[type="email"]').value;
            const submitBtn = newsletterForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            this.setLoadingState(submitBtn, 'Subscribing...');

            try {
                const response = await this.apiCall('newsletter_api.php?action=subscribe', 'POST', { email });
                const result = await response.json();

                if (result.success) {
                    this.showAlert('Successfully subscribed to newsletter!', 'success');
                    newsletterForm.reset();
                } else {
                    this.showAlert(result.message, 'error');
                }
            } catch (error) {
                this.showAlert('Failed to subscribe', 'error');
                console.error(error);
            } finally {
                this.resetLoadingState(submitBtn, originalText);
            }
        });
    }

    initFeatureCards() {
        // Add hover effects to feature cards
        const featureCards = document.querySelectorAll('.feature-card');
        featureCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('hovered');
            });
            card.addEventListener('mouseleave', () => {
                card.classList.remove('hovered');
            });
        });

        // Add click handlers for feature links
        featureCards.forEach(card => {
            const link = card.querySelector('a');
            if (link) {
                link.addEventListener('click', (e) => {
                    // Add loading effect
                    e.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                });
            }
        });
    }

    // Utility methods
    setLoadingState(button, text) {
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        button.disabled = true;
    }

    resetLoadingState(button, originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
    }

    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> ${message}`;

        const container = document.querySelector('.landing-container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => alertDiv.remove(), 5000);
    }

    async apiCall(endpoint, method = 'GET', data = null) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        return fetch(endpoint, config);
    }
}

// Initialize LandingManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.landingManager = new LandingManager();
});