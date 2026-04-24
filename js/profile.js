// Enhanced Profile JavaScript with Notification Settings and Preferences
class ProfileManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadUserData();
        this.loadPreferences();
    }

    bindEvents() {
        // Profile form
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileUpdate(e));
        }

        // Password form
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => this.handlePasswordChange(e));
        }

        // Notification settings
        document.querySelectorAll('.notification-setting input').forEach(input => {
            input.addEventListener('change', (e) => this.updateNotificationSetting(e.target.name, e.target.checked));
        });

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Language selector
        const languageSelect = document.getElementById('languageSelect');
        if (languageSelect) {
            languageSelect.addEventListener('change', (e) => this.changeLanguage(e.target.value));
        }

        // Delete account button
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', () => this.confirmDeleteAccount());
        }

        // Avatar upload
        const avatarInput = document.getElementById('avatarInput');
        if (avatarInput) {
            avatarInput.addEventListener('change', (e) => this.handleAvatarUpload(e));
        }

        // Input validation
        this.bindInputValidation();
    }

    async loadUserData() {
        try {
            const response = await this.apiCall('profile_api.php?action=get');
            const result = await response.json();

            if (result.success) {
                this.populateProfileForm(result.user);
            }
        } catch (error) {
            console.error('Error loading user data:', error);
        }
    }

    populateProfileForm(user) {
        // Update profile header
        const headerName = document.querySelector('.profile-info h1');
        if (headerName) headerName.textContent = user.full_name;

        const headerEmail = document.querySelector('.profile-info .email');
        if (headerEmail) headerEmail.textContent = user.email;

        // Update avatar
        const avatarImg = document.querySelector('.profile-avatar img');
        if (avatarImg && user.avatar) {
            avatarImg.src = user.avatar;
        }

        // Populate form fields
        const fields = ['full_name', 'username', 'email', 'phone', 'date_of_birth', 'address'];
        fields.forEach(field => {
            const input = document.querySelector(`#profileForm input[name="${field}"]`);
            if (input && user[field]) {
                input.value = user[field];
            }
        });
    }

    async loadPreferences() {
        try {
            const response = await this.apiCall('profile_api.php?action=preferences');
            const result = await response.json();

            if (result.success) {
                this.applyPreferences(result.preferences);
            }
        } catch (error) {
            console.error('Error loading preferences:', error);
        }
    }

    applyPreferences(prefs) {
        // Apply theme
        document.documentElement.setAttribute('data-theme', prefs.theme || 'light');

        // Apply language
        const languageSelect = document.getElementById('languageSelect');
        if (languageSelect && prefs.language) {
            languageSelect.value = prefs.language;
        }

        // Apply notification settings
        const notificationSettings = ['email_notifications', 'sms_notifications', 'election_reminders', 'results_updates'];
        notificationSettings.forEach(setting => {
            const input = document.querySelector(`.notification-setting input[name="${setting}"]`);
            if (input) {
                input.checked = prefs[setting] ?? true;
            }
        });
    }

    async handleProfileUpdate(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        this.setLoadingState(submitBtn, 'Updating...');

        const formData = new FormData(form);
        const data = {
            full_name: formData.get('full_name'),
            username: formData.get('username'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            date_of_birth: formData.get('date_of_birth'),
            address: formData.get('address')
        };

        try {
            const response = await this.apiCall('profile_api.php?action=update', 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showAlert('Profile updated successfully!', 'success');
                // Update displayed name
                const headerName = document.querySelector('.profile-info h1');
                if (headerName) headerName.textContent = data.full_name;
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to update profile', 'error');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async handlePasswordChange(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        const formData = new FormData(form);
        const data = {
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password'),
            confirm_password: formData.get('confirm_password')
        };

        // Validate passwords match
        if (data.new_password !== data.confirm_password) {
            this.showAlert('New passwords do not match', 'error');
            return;
        }

        if (data.new_password.length < 8) {
            this.showAlert('Password must be at least 8 characters', 'error');
            return;
        }

        this.setLoadingState(submitBtn, 'Changing...');

        try {
            const response = await this.apiCall('profile_api.php?action=password', 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showAlert('Password changed successfully!', 'success');
                form.reset();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to change password', 'error');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async updateNotificationSetting(setting, enabled) {
        try {
            await this.apiCall('profile_api.php?action=update_notification', 'POST', {
                setting,
                enabled
            });
            this.showAlert('Notification settings updated', 'success');
        } catch (error) {
            console.error('Error updating notification setting:', error);
            this.showAlert('Failed to update settings', 'error');
        }
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        this.savePreference('theme', newTheme);
    }

    async changeLanguage(language) {
        try {
            await this.apiCall('profile_api.php?action=update_language', 'POST', { language });
            this.showAlert('Language updated. Refresh to apply changes.', 'success');
        } catch (error) {
            console.error('Error changing language:', error);
            this.showAlert('Failed to change language', 'error');
        }
    }

    async handleAvatarUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            this.showAlert('Please upload a valid image file (JPEG, PNG, or GIF)', 'error');
            return;
        }

        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            this.showAlert('Image size must be less than 2MB', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            const response = await fetch('profile_api.php?action=upload_avatar', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Update avatar display
                const avatarImg = document.querySelector('.profile-avatar img');
                if (avatarImg) {
                    avatarImg.src = result.avatar_url + '?t=' + new Date().getTime();
                }
                this.showAlert('Avatar updated successfully!', 'success');
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to upload avatar', 'error');
            console.error(error);
        }
    }

    async confirmDeleteAccount() {
        if (!confirm('Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.')) {
            return;
        }

        const confirmation = prompt('Type DELETE to confirm account deletion:');
        if (confirmation !== 'DELETE') {
            this.showAlert('Account deletion cancelled', 'warning');
            return;
        }

        try {
            const response = await this.apiCall('profile_api.php?action=delete_account', 'POST');
            const result = await response.json();

            if (result.success) {
                this.showAlert('Account deleted. Redirecting...', 'success');
                setTimeout(() => window.location.href = 'index.html', 2000);
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to delete account', 'error');
            console.error(error);
        }
    }

    async savePreference(key, value) {
        try {
            await this.apiCall('profile_api.php?action=save_preference', 'POST', { key, value });
        } catch (error) {
            console.error('Error saving preference:', error);
        }
    }

    bindInputValidation() {
        const inputs = document.querySelectorAll('#profileForm input, #passwordForm input');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateInput(input));
            input.addEventListener('input', () => this.validateInput(input));
        });
    }

    validateInput(input) {
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
            case 'new_password':
                if (input.value.length < 8) {
                    isValid = false;
                    message = 'Password must be at least 8 characters';
                } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(input.value)) {
                    isValid = false;
                    message = 'Password must contain uppercase, lowercase, and number';
                }
                break;
        }

        if (errorDiv) {
            errorDiv.textContent = message;
            input.parentElement.classList.toggle('error', !isValid);
        }

        return isValid;
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
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i> ${message}`;

        const container = document.querySelector('.profile-content') || document.body;
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

// Initialize ProfileManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.profileManager = new ProfileManager();
});