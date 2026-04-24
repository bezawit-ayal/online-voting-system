// Enhanced Authentication JavaScript with Advanced Features
class AuthManager {
    constructor() {
        this.currentUser = null;
        this.sessionCheckInterval = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.startSessionCheck();
        this.loadUserPreferences();
    }

    bindEvents() {
        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Password reset form
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => this.handlePasswordReset(e));
        }

        // OTP verification form
        const otpForm = document.getElementById('otpForm');
        if (otpForm) {
            otpForm.addEventListener('submit', (e) => this.handleOTPVerification(e));
        }

        // Logout buttons
        document.querySelectorAll('.logout-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleLogout(e));
        });

        // Password visibility toggles
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => this.togglePasswordVisibility(e));
        });

        // Resend buttons
        document.querySelectorAll('.resend-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.resendCode(e));
        });

        // Input validation
        this.bindInputValidation();
    }

    bindInputValidation() {
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
                this.validateInput();
            });

            input.addEventListener('input', function() {
                this.validateInput();
            });
        });
    }

    async handleRegister(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        if (!this.validateRegisterForm()) {
            return;
        }

        this.setLoadingState(submitBtn, 'Creating Account...');

        const formData = new FormData(form);
        const data = {
            full_name: formData.get('full_name'),
            username: formData.get('username'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            password: formData.get('password'),
            confirm_password: formData.get('confirm_password'),
            date_of_birth: formData.get('date_of_birth'),
            address: formData.get('address')
        };

        try {
            const response = await this.apiCall('auth_api.php?action=register', 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showSuccess('Account created successfully! Please check your email for verification.');
                if (result.user_id) {
                    // Show email verification step
                    this.showEmailVerificationStep(result.user_id);
                } else {
                    setTimeout(() => window.location.href = 'login.php', 2000);
                }
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Registration failed. Please try again.');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async handleLogin(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        this.setLoadingState(submitBtn, 'Signing In...');

        const formData = new FormData(form);
        const data = {
            email: formData.get('email') || formData.get('username'),
            password: formData.get('password')
        };

        try {
            const response = await this.apiCall('auth_api.php?action=login', 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showSuccess('Login successful! Redirecting...');
                setTimeout(() => window.location.href = 'dashboard.php', 1500);
            } else {
                this.showError(result.message);
                this.handleLoginFailure();
            }
        } catch (error) {
            this.showError('Login failed. Please try again.');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async handlePasswordReset(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        this.setLoadingState(submitBtn, 'Sending Reset Link...');

        const email = form.querySelector('input[name="email"]').value;

        try {
            const response = await this.apiCall('auth_api.php?action=send_password_reset', 'POST', { email });
            const result = await response.json();

            this.showSuccess(result.message || 'If the email exists, a reset link has been sent.');
            form.reset();
        } catch (error) {
            this.showError('Failed to send reset email. Please try again.');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async handleOTPVerification(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        this.setLoadingState(submitBtn, 'Verifying...');

        const otp = form.querySelector('input[name="otp"]').value;

        try {
            const response = await this.apiCall('auth_api.php?action=verify_otp', 'POST', { otp });
            const result = await response.json();

            if (result.success) {
                this.showSuccess('OTP verified successfully!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('OTP verification failed. Please try again.');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async handleLogout(event) {
        event.preventDefault();

        try {
            const response = await this.apiCall('auth_api.php?action=logout', 'POST');
            const result = await response.json();

            if (result.success) {
                this.showSuccess('Logged out successfully!');
                setTimeout(() => window.location.href = 'index.html', 1000);
            }
        } catch (error) {
            // Force logout on client side
            window.location.href = 'index.html';
        }
    }

    async resendCode(event) {
        event.preventDefault();

        const btn = event.target;
        const originalText = btn.innerHTML;
        const type = btn.dataset.type;

        this.setLoadingState(btn, 'Sending...');

        try {
            let endpoint, data = {};

            if (type === 'email') {
                endpoint = 'auth_api.php?action=send_email_verification';
            } else if (type === 'otp') {
                endpoint = 'auth_api.php?action=send_otp';
            }

            const response = await this.apiCall(endpoint, 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showSuccess(`${type.toUpperCase()} sent successfully!`);
                this.startResendCooldown(btn);
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError(`Failed to resend ${type}. Please try again.`);
            console.error(error);
        } finally {
            this.resetLoadingState(btn, originalText);
        }
    }

    showEmailVerificationStep(userId) {
        const verificationHtml = `
            <div class="verification-step">
                <h3><i class="fas fa-envelope"></i> Check Your Email</h3>
                <p>We've sent a verification link to your email address. Please click the link to verify your account.</p>
                <div class="verification-actions">
                    <button class="btn secondary resend-btn" data-type="email">
                        <i class="fas fa-redo"></i> Resend Email
                    </button>
                    <a href="login.php" class="btn primary">Continue to Login</a>
                </div>
            </div>
        `;

        const form = document.querySelector('.auth-form');
        form.innerHTML = verificationHtml;
        this.bindEvents(); // Re-bind events for new elements
    }

    validateInput() {
        const input = this;
        const errorDiv = input.parentElement.querySelector('.input-error');
        let isValid = true;
        let message = '';

        switch (input.name) {
            case 'full_name':
                if (input.value.trim().length < 2) {
                    isValid = false;
                    message = 'Full name must be at least 2 characters';
                }
                break;
            case 'username':
                if (input.value.trim().length < 3) {
                    isValid = false;
                    message = 'Username must be at least 3 characters';
                } else if (!/^[a-zA-Z0-9_]+$/.test(input.value)) {
                    isValid = false;
                    message = 'Username can only contain letters, numbers, and underscores';
                }
                break;
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                    isValid = false;
                    message = 'Please enter a valid email address';
                }
                break;
            case 'phone':
                if (input.value && !/^\+?[1-9]\d{1,14}$/.test(input.value.replace(/\s/g, ''))) {
                    isValid = false;
                    message = 'Please enter a valid phone number';
                }
                break;
            case 'password':
                if (input.value.length < 8) {
                    isValid = false;
                    message = 'Password must be at least 8 characters';
                } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(input.value)) {
                    isValid = false;
                    message = 'Password must contain uppercase, lowercase, and number';
                }
                break;
            case 'confirm_password':
                const password = document.querySelector('input[name="password"]');
                if (input.value !== password.value) {
                    isValid = false;
                    message = 'Passwords do not match';
                }
                break;
            case 'otp':
                if (!/^\d{6}$/.test(input.value)) {
                    isValid = false;
                    message = 'Please enter a valid 6-digit OTP';
                }
                break;
        }

        if (errorDiv) {
            if (!isValid) {
                errorDiv.textContent = message;
                input.parentElement.classList.add('error');
            } else {
                errorDiv.textContent = '';
                input.parentElement.classList.remove('error');
            }
        }

        return isValid;
    }

    validateRegisterForm() {
        const inputs = document.querySelectorAll('#registerForm input[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateInput.call(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    togglePasswordVisibility(event) {
        const toggle = event.target;
        const input = toggle.parentElement.querySelector('input');
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;

        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    }

    handleLoginFailure() {
        // Implement progressive delay or captcha after multiple failures
        const attempts = parseInt(localStorage.getItem('loginAttempts') || 0) + 1;
        localStorage.setItem('loginAttempts', attempts);

        if (attempts >= 3) {
            // Show captcha or implement delay
            this.showCaptcha();
        }
    }

    showCaptcha() {
        // Implement captcha logic here
        console.log('Showing captcha after multiple failed attempts');
    }

    startResendCooldown(button) {
        let cooldown = 60;
        button.disabled = true;

        const interval = setInterval(() => {
            button.innerHTML = `<i class="fas fa-clock"></i> Resend in ${cooldown}s`;
            cooldown--;

            if (cooldown < 0) {
                clearInterval(interval);
                button.innerHTML = '<i class="fas fa-redo"></i> Resend';
                button.disabled = false;
            }
        }, 1000);
    }

    startSessionCheck() {
        // Check session every 5 minutes
        this.sessionCheckInterval = setInterval(async () => {
            try {
                const response = await this.apiCall('auth_api.php?action=check_session', 'GET');
                const result = await response.json();

                if (!result.valid) {
                    this.showWarning('Your session has expired. Please login again.');
                    setTimeout(() => window.location.href = 'login.php', 3000);
                }
            } catch (error) {
                console.error('Session check failed:', error);
            }
        }, 5 * 60 * 1000); // 5 minutes
    }

    loadUserPreferences() {
        // Load user preferences for theme, etc.
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    }

    setLoadingState(button, text) {
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        button.disabled = true;
    }

    resetLoadingState(button, originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
    }

    showError(message) {
        this.showAlert(message, 'error');
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showWarning(message) {
        this.showAlert(message, 'warning');
    }

    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;

        const icons = {
            error: 'exclamation-triangle',
            success: 'check-circle',
            warning: 'exclamation-circle'
        };

        alertDiv.innerHTML = `<i class="fas fa-${icons[type]}"></i> ${message}`;

        const container = document.querySelector('.auth-container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
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

// Initialize AuthManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.authManager = new AuthManager();
});
