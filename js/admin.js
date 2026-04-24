// Enhanced Admin Panel JavaScript with Multi-Election Management and Advanced Features
class AdminManager {
    constructor() {
        this.currentTab = 'dashboard';
        this.currentElection = null;
        this.updateInterval = null;
        this.charts = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboard();
        this.startAutoRefresh();
    }

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
        });

        // Election selector
        const electionSelect = document.getElementById('electionSelect');
        if (electionSelect) {
            electionSelect.addEventListener('change', (e) => this.changeElection(e.target.value));
        }

        // Add candidate form
        const addCandidateForm = document.getElementById('addCandidateForm');
        if (addCandidateForm) {
            addCandidateForm.addEventListener('submit', (e) => this.handleAddCandidate(e));
        }

        // Add election form
        const addElectionForm = document.getElementById('addElectionForm');
        if (addElectionForm) {
            addElectionForm.addEventListener('submit', (e) => this.handleAddElection(e));
        }

        // Refresh buttons
        document.querySelectorAll('.refresh-btn').forEach(btn => {
            btn.addEventListener('click', () => this.refreshCurrentView());
        });

        // Export buttons
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.exportData(e.target.dataset.type));
        });

        // Search inputs
        document.querySelectorAll('.admin-search').forEach(input => {
            input.addEventListener('input', (e) => this.handleSearch(e.target.value));
        });

        // Modal close buttons
        document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
            btn.addEventListener('click', (e) => this.closeModal(e.target.closest('.modal')));
        });

        // Close modals on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeModal(modal);
            });
        });
    }

    async loadDashboard() {
        try {
            this.showLoading();

            // Load elections
            await this.loadElections();

            // Load stats
            await this.loadStats();

            // Load recent activity
            await this.loadRecentActivity();

            // Initialize charts
            this.initializeCharts();

            this.hideLoading();
        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Failed to load dashboard data');
            this.hideLoading();
        }
    }

    async loadElections() {
        try {
            const response = await this.apiCall('admin_api.php?action=elections');
            const result = await response.json();

            if (result.success) {
                this.populateElectionSelector(result.elections);
                this.updateElectionList(result.elections);
            }
        } catch (error) {
            console.error('Error loading elections:', error);
        }
    }

    populateElectionSelector(elections) {
        const select = document.getElementById('electionSelect');
        if (!select) return;

        select.innerHTML = '<option value="">All Elections</option>';

        elections.forEach(election => {
            const option = document.createElement('option');
            option.value = election.id;
            option.textContent = election.title;
            if (election.status === 'active') {
                option.selected = true;
                this.currentElection = election.id;
            }
            select.appendChild(option);
        });
    }

    updateElectionList(elections) {
        const container = document.getElementById('electionList');
        if (!container) return;

        container.innerHTML = elections.map(election => `
            <div class="election-card" data-election-id="${election.id}">
                <div class="election-header">
                    <h3>${election.title}</h3>
                    <span class="status-badge status-${election.status}">${election.status}</span>
                </div>
                <div class="election-details">
                    <div class="detail-item">
                        <i class="fas fa-calendar"></i>
                        <span>${this.formatDate(election.start_date)} - ${this.formatDate(election.end_date)}</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <span>${election.total_voters || 0} voters</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-vote-yea"></i>
                        <span>${election.total_votes || 0} votes</span>
                    </div>
                </div>
                <div class="election-actions">
                    <button class="btn small" onclick="adminManager.editElection(${election.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn small ${election.status === 'active' ? 'warning' : 'success'}" 
                        onclick="adminManager.toggleElectionStatus(${election.id}, '${election.status}')">
                        <i class="fas fa-${election.status === 'active' ? 'pause' : 'play'}"></i>
                        ${election.status === 'active' ? 'Pause' : 'Start'}
                    </button>
                    <button class="btn small danger" onclick="adminManager.deleteElection(${election.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    async loadStats() {
        try {
            let url = 'admin_api.php?action=stats';
            if (this.currentElection) {
                url += `&election_id=${this.currentElection}`;
            }

            const response = await this.apiCall(url);
            const result = await response.json();

            if (result.success) {
                this.updateStats(result.stats);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    updateStats(stats) {
        const statMappings = {
            'totalUsers': stats.total_users,
            'activeUsers': stats.active_users,
            'totalCandidates': stats.total_candidates,
            'totalVotes': stats.total_votes,
            'participationRate': `${stats.participation_rate}%`,
            'systemHealth': `${stats.system_health}%`,
            'failedLogins': stats.failed_logins,
            'suspiciousActivity': stats.suspicious_activity
        };

        Object.entries(statMappings).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });
    }

    async loadRecentActivity() {
        try {
            const response = await this.apiCall('admin_api.php?action=recent_activity');
            const result = await response.json();

            if (result.success) {
                this.updateActivityFeed(result.activities);
            }
        } catch (error) {
            console.error('Error loading activity:', error);
        }
    }

    updateActivityFeed(activities) {
        const container = document.getElementById('activityFeed');
        if (!container) return;

        if (activities.length === 0) {
            container.innerHTML = '<div class="no-data">No recent activity</div>';
            return;
        }

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <p>${activity.description}</p>
                    <span class="activity-time">${this.formatTimeAgo(activity.timestamp)}</span>
                </div>
            </div>
        `).join('');
    }

    initializeCharts() {
        // Initialize Chart.js charts for admin dashboard
        this.initVotesChart();
        this.initUsersChart();
    }

    initVotesChart() {
        const ctx = document.getElementById('adminVotesChart');
        if (!ctx) return;

        this.charts.votes = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Votes Over Time',
                    data: [],
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    initUsersChart() {
        const ctx = document.getElementById('adminUsersChart');
        if (!ctx) return;

        this.charts.users = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Pending'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    switchTab(tab) {
        this.currentTab = tab;

        // Update tab buttons
        document.querySelectorAll('.admin-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.tab === tab);
        });

        // Show/hide sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.toggle('active', section.id === `${tab}Section`);
        });

        // Load tab data
        this.loadTabData(tab);
    }

    async loadTabData(tab) {
        switch (tab) {
            case 'users':
                await this.loadUsers();
                break;
            case 'candidates':
                await this.loadCandidates();
                break;
            case 'elections':
                await this.loadElections();
                break;
            case 'audit':
                await this.loadAuditLogs();
                break;
            case 'backup':
                await this.loadBackupStatus();
                break;
        }
    }

    async loadUsers() {
        const userList = document.getElementById('userList');
        if (!userList) return;

        try {
            const response = await this.apiCall('admin_api.php?action=users');
            const result = await response.json();

            if (result.success) {
                this.renderUserList(result.users);
            } else {
                this.showError(userList, result.message);
            }
        } catch (error) {
            this.showError(userList, 'Failed to load users');
            console.error(error);
        }
    }

    renderUserList(users) {
        const userList = document.getElementById('userList');
        if (!userList) return;

        if (users.length === 0) {
            userList.innerHTML = '<div class="no-data">No users found</div>';
            return;
        }

        userList.innerHTML = `
            <div class="data-table">
                <div class="table-header">
                    <div>Username</div>
                    <div>Email</div>
                    <div>Full Name</div>
                    <div>Voter ID</div>
                    <div>Role</div>
                    <div>Status</div>
                    <div>Joined</div>
                    <div>Actions</div>
                </div>
                ${users.map(user => `
                    <div class="table-row">
                        <div>${user.username}</div>
                        <div>${user.email}</div>
                        <div>${user.full_name}</div>
                        <div>${user.voter_id || 'N/A'}</div>
                        <div><span class="badge ${user.is_admin ? 'admin' : 'user'}">${user.is_admin ? 'Admin' : 'User'}</span></div>
                        <div><span class="badge ${user.is_active ? 'success' : 'danger'}">${user.is_active ? 'Active' : 'Inactive'}</span></div>
                        <div>${this.formatDate(user.created_at)}</div>
                        <div class="actions">
                            <button class="btn small" onclick="adminManager.viewUserDetails(${user.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn small ${user.is_active ? 'warning' : 'success'}" 
                                onclick="adminManager.toggleUserStatus(${user.id}, ${user.is_active})">
                                <i class="fas fa-${user.is_active ? 'ban' : 'check'}"></i>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async loadCandidates() {
        const candidateList = document.getElementById('candidateList');
        if (!candidateList) return;

        try {
            let url = 'admin_api.php?action=candidates';
            if (this.currentElection) {
                url += `&election_id=${this.currentElection}`;
            }

            const response = await this.apiCall(url);
            const result = await response.json();

            if (result.success) {
                this.renderCandidateList(result.candidates);
            } else {
                this.showError(candidateList, result.message);
            }
        } catch (error) {
            this.showError(candidateList, 'Failed to load candidates');
            console.error(error);
        }
    }

    renderCandidateList(candidates) {
        const candidateList = document.getElementById('candidateList');
        if (!candidateList) return;

        if (candidates.length === 0) {
            candidateList.innerHTML = '<div class="no-data">No candidates found</div>';
            return;
        }

        candidateList.innerHTML = `
            <div class="candidate-grid">
                ${candidates.map(candidate => `
                    <div class="candidate-admin-card">
                        <div class="candidate-avatar">
                            <img src="${candidate.image_url || 'assets/images/default-avatar.png'}" 
                                alt="${candidate.name}" 
                                onerror="this.src='https://via.placeholder.com/80x80/4F46E5/FFFFFF?text=${candidate.name.charAt(0)}'" />
                        </div>
                        <div class="candidate-info">
                            <h4>${candidate.name}</h4>
                            <p class="party">${candidate.party || 'Independent'}</p>
                            <p class="description">${candidate.description || 'No description'}</p>
                            <div class="candidate-stats">
                                <span><i class="fas fa-vote-yea"></i> ${candidate.vote_count || 0} votes</span>
                            </div>
                        </div>
                        <div class="candidate-actions">
                            <button class="btn small" onclick="adminManager.editCandidate(${candidate.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn small danger" onclick="adminManager.deleteCandidate(${candidate.id}, '${candidate.name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async loadAuditLogs() {
        const auditList = document.getElementById('auditList');
        if (!auditList) return;

        try {
            const response = await this.apiCall('admin_api.php?action=audit_logs');
            const result = await response.json();

            if (result.success) {
                this.renderAuditLogs(result.logs);
            } else {
                this.showError(auditList, result.message);
            }
        } catch (error) {
            this.showError(auditList, 'Failed to load audit logs');
            console.error(error);
        }
    }

    renderAuditLogs(logs) {
        const auditList = document.getElementById('auditList');
        if (!auditList) return;

        if (logs.length === 0) {
            auditList.innerHTML = '<div class="no-data">No audit logs found</div>';
            return;
        }

        auditList.innerHTML = `
            <div class="data-table">
                <div class="table-header">
                    <div>Timestamp</div>
                    <div>User</div>
                    <div>Action</div>
                    <div>Details</div>
                    <div>IP Address</div>
                </div>
                ${logs.map(log => `
                    <div class="table-row">
                        <div>${this.formatDateTime(log.created_at)}</div>
                        <div>${log.username || 'System'}</div>
                        <div><span class="badge ${this.getActionBadgeClass(log.action)}">${log.action}</span></div>
                        <div>${log.details || '-'}</div>
                        <div>${log.ip_address || '-'}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async loadBackupStatus() {
        const backupStatus = document.getElementById('backupStatus');
        if (!backupStatus) return;

        try {
            const response = await this.apiCall('admin_api.php?action=backup_status');
            const result = await response.json();

            if (result.success) {
                this.renderBackupStatus(result.backups);
            }
        } catch (error) {
            console.error('Error loading backup status:', error);
        }
    }

    renderBackupStatus(backups) {
        const backupStatus = document.getElementById('backupStatus');
        if (!backupStatus) return;

        backupStatus.innerHTML = `
            <div class="backup-list">
                ${backups.map(backup => `
                    <div class="backup-item">
                        <div class="backup-info">
                            <h4>${backup.filename}</h4>
                            <p>Created: ${this.formatDateTime(backup.created_at)}</p>
                            <p>Size: ${this.formatFileSize(backup.size)}</p>
                        </div>
                        <div class="backup-actions">
                            <button class="btn small" onclick="adminManager.downloadBackup('${backup.filename}')">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <button class="btn small danger" onclick="adminManager.deleteBackup('${backup.filename}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // Action methods
    showAddCandidate() {
        this.openModal('addCandidateModal');
    }

    async handleAddCandidate(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        this.setLoadingState(submitBtn, 'Adding...');

        const formData = new FormData(form);
        const data = {
            name: formData.get('name'),
            party: formData.get('party'),
            description: formData.get('description'),
            image_url: formData.get('image_url'),
            election_id: this.currentElection
        };

        try {
            const response = await this.apiCall('admin_api.php?action=add_candidate', 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showAlert('Candidate added successfully!', 'success');
                form.reset();
                this.closeModal('addCandidateModal');
                await this.loadCandidates();
                await this.loadStats();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to add candidate', 'error');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async deleteCandidate(id, name) {
        if (!confirm(`Delete candidate "${name}"? This cannot be undone.`)) return;

        try {
            const response = await this.apiCall(`admin_api.php?action=delete_candidate&id=${id}`, 'DELETE');
            const result = await response.json();

            if (result.success) {
                this.showAlert('Candidate deleted', 'success');
                await this.loadCandidates();
                await this.loadStats();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to delete candidate', 'error');
            console.error(error);
        }
    }

    showAddElection() {
        this.openModal('addElectionModal');
    }

    async handleAddElection(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        this.setLoadingState(submitBtn, 'Creating...');

        const formData = new FormData(form);
        const data = {
            title: formData.get('title'),
            description: formData.get('description'),
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            election_type: formData.get('election_type')
        };

        try {
            const response = await this.apiCall('admin_api.php?action=add_election', 'POST', data);
            const result = await response.json();

            if (result.success) {
                this.showAlert('Election created successfully!', 'success');
                form.reset();
                this.closeModal('addElectionModal');
                await this.loadElections();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to create election', 'error');
            console.error(error);
        } finally {
            this.resetLoadingState(submitBtn, originalText);
        }
    }

    async toggleElectionStatus(electionId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'paused' : 'active';

        try {
            const response = await this.apiCall('admin_api.php?action=update_election_status', 'POST', {
                election_id: electionId,
                status: newStatus
            });
            const result = await response.json();

            if (result.success) {
                this.showAlert(`Election ${newStatus === 'active' ? 'started' : 'paused'}`, 'success');
                await this.loadElections();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to update election status', 'error');
            console.error(error);
        }
    }

    async deleteElection(electionId) {
        if (!confirm('Delete this election and all associated data? This cannot be undone.')) return;

        try {
            const response = await this.apiCall(`admin_api.php?action=delete_election&id=${electionId}`, 'DELETE');
            const result = await response.json();

            if (result.success) {
                this.showAlert('Election deleted', 'success');
                await this.loadElections();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to delete election', 'error');
            console.error(error);
        }
    }

    async toggleUserStatus(userId, currentStatus) {
        try {
            const response = await this.apiCall('admin_api.php?action=toggle_user_status', 'POST', {
                user_id: userId,
                is_active: !currentStatus
            });
            const result = await response.json();

            if (result.success) {
                this.showAlert(`User ${!currentStatus ? 'activated' : 'deactivated'}`, 'success');
                await this.loadUsers();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to update user status', 'error');
            console.error(error);
        }
    }

    async createBackup() {
        try {
            const response = await this.apiCall('admin_api.php?action=create_backup', 'POST');
            const result = await response.json();

            if (result.success) {
                this.showAlert('Backup created successfully!', 'success');
                await this.loadBackupStatus();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to create backup', 'error');
            console.error(error);
        }
    }

    async downloadBackup(filename) {
        try {
            const response = await this.apiCall(`admin_api.php?action=download_backup&file=${filename}`);
            const result = await response.json();

            if (result.success) {
                const link = document.createElement('a');
                link.href = result.url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        } catch (error) {
            this.showAlert('Failed to download backup', 'error');
            console.error(error);
        }
    }

    async deleteBackup(filename) {
        if (!confirm(`Delete backup "${filename}"?`)) return;

        try {
            const response = await this.apiCall(`admin_api.php?action=delete_backup&file=${filename}`, 'DELETE');
            const result = await response.json();

            if (result.success) {
                this.showAlert('Backup deleted', 'success');
                await this.loadBackupStatus();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to delete backup', 'error');
            console.error(error);
        }
    }

    async resetElection() {
        if (!confirm('Reset ALL election data? This will delete ALL votes and cannot be undone!')) return;

        try {
            const response = await this.apiCall('admin_api.php?action=reset_election', 'POST');
            const result = await response.json();

            if (result.success) {
                this.showAlert('Election reset successfully', 'success');
                await this.loadDashboard();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to reset election', 'error');
            console.error(error);
        }
    }

    changeElection(electionId) {
        this.currentElection = electionId;
        this.loadStats();
    }

    handleSearch(query) {
        const rows = document.querySelectorAll('.table-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query.toLowerCase()) ? 'flex' : 'none';
        });
    }

    refreshCurrentView() {
        this.loadTabData(this.currentTab);
    }

    async exportData(type) {
        try {
            const response = await this.apiCall(`admin_api.php?action=export_${type}`);
            const result = await response.json();

            if (result.success) {
                const link = document.createElement('a');
                link.href = result.url;
                link.download = result.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                this.showAlert('Export failed', 'error');
            }
        } catch (error) {
            this.showAlert('Export failed', 'error');
            console.error(error);
        }
    }

    startAutoRefresh() {
        this.updateInterval = setInterval(() => {
            if (this.currentTab === 'dashboard') {
                this.loadStats();
                this.loadRecentActivity();
            }
        }, 30000);
    }

    // Utility methods
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('active');
    }

    closeModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (modal) modal.classList.remove('active');
    }

    showLoading() {
        const loader = document.getElementById('loadingOverlay');
        if (loader) loader.style.display = 'flex';
    }

    hideLoading() {
        const loader = document.getElementById('loadingOverlay');
        if (loader) loader.style.display = 'none';
    }

    setLoadingState(button, text) {
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        button.disabled = true;
    }

    resetLoadingState(button, originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
    }

    showError(container, message) {
        container.innerHTML = `<div class="error-state"><i class="fas fa-exclamation-triangle"></i><p>${message}</p></div>`;
    }

    showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> ${message}`;

        const container = document.querySelector('.admin-container') || document.body;
        container.insertBefore(alert, container.firstChild);

        setTimeout(() => alert.remove(), 5000);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (days > 0) return `${days}d ago`;
        if (hours > 0) return `${hours}h ago`;
        if (minutes > 0) return `${minutes}m ago`;
        return 'Just now';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    getActivityIcon(type) {
        const icons = {
            'vote': 'vote-yea',
            'login': 'sign-in-alt',
            'logout': 'sign-out-alt',
            'register': 'user-plus',
            'election_start': 'play-circle',
            'election_end': 'stop-circle',
            'candidate_add': 'user-plus',
            'candidate_remove': 'user-minus'
        };
        return icons[type] || 'circle';
    }

    getActionBadgeClass(action) {
        const classes = {
            'login': 'success',
            'logout': 'warning',
            'vote': 'info',
            'register': 'success',
            'failed_login': 'danger',
            'admin_action': 'admin'
        };
        return classes[action] || 'default';
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

// Initialize AdminManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.adminManager = new AdminManager();
});