// Enhanced Dashboard JavaScript with Real-time Analytics and Charts
class DashboardManager {
    constructor() {
        this.charts = {};
        this.updateInterval = null;
        this.currentElection = null;
        this.searchTimeout = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardData();
        this.startRealTimeUpdates();
        this.initializeCharts();
        this.loadUserPreferences();
    }

    bindEvents() {
        // Election selector
        const electionSelect = document.getElementById('electionSelect');
        if (electionSelect) {
            electionSelect.addEventListener('change', (e) => this.changeElection(e.target.value));
        }

        // Search and filter
        const searchInput = document.getElementById('candidateSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }

        const filterSelect = document.getElementById('candidateFilter');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => this.handleFilter(e.target.value));
        }

        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        // Export buttons
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.exportData(e.target.dataset.type));
        });

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Notification settings
        this.bindNotificationSettings();
    }

    async loadDashboardData() {
        try {
            this.showLoading();

            // Load elections
            await this.loadElections();

            // Load dashboard stats
            await this.loadStats();

            // Load recent activity
            await this.loadRecentActivity();

            // Load notifications
            await this.loadNotifications();

            this.hideLoading();
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showError('Failed to load dashboard data');
            this.hideLoading();
        }
    }

    async loadElections() {
        try {
            const response = await this.apiCall('vote_api.php?action=elections');
            const result = await response.json();

            if (result.success) {
                this.populateElectionSelector(result.elections);
                if (!this.currentElection && result.elections.length > 0) {
                    this.currentElection = result.elections[0].id;
                    this.loadElectionData(this.currentElection);
                }
            }
        } catch (error) {
            console.error('Error loading elections:', error);
        }
    }

    async loadStats() {
        try {
            const response = await this.apiCall('vote_api.php?action=dashboard_stats');
            const result = await response.json();

            if (result.success) {
                this.updateStats(result.stats);
                this.updateCharts(result.stats);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    async loadElectionData(electionId) {
        try {
            const response = await this.apiCall(`vote_api.php?action=election_data&election_id=${electionId}`);
            const result = await response.json();

            if (result.success) {
                this.updateElectionInfo(result.election);
                this.updateCandidates(result.candidates);
                this.updateVotingTimer(result.election);
            }
        } catch (error) {
            console.error('Error loading election data:', error);
        }
    }

    async loadRecentActivity() {
        try {
            const response = await this.apiCall('vote_api.php?action=recent_activity');
            const result = await response.json();

            if (result.success) {
                this.updateRecentActivity(result.activities);
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }

    async loadNotifications() {
        try {
            const response = await this.apiCall('vote_api.php?action=notifications');
            const result = await response.json();

            if (result.success) {
                this.updateNotifications(result.notifications);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    populateElectionSelector(elections) {
        const select = document.getElementById('electionSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Select Election</option>';

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

    updateStats(stats) {
        // Update stat cards
        this.updateStatCard('totalVotes', stats.total_votes || 0);
        this.updateStatCard('totalCandidates', stats.total_candidates || 0);
        this.updateStatCard('activeElections', stats.active_elections || 0);
        this.updateStatCard('registeredUsers', stats.registered_users || 0);

        // Update voting status
        this.updateVotingStatus(stats);
    }

    updateStatCard(cardId, value) {
        const card = document.getElementById(cardId);
        if (card) {
            const numberElement = card.querySelector('.stat-number');
            if (numberElement) {
                this.animateNumber(numberElement, value);
            }
        }
    }

    updateVotingStatus(stats) {
        const statusDiv = document.getElementById('votingStatus');
        if (!statusDiv) return;

        if (stats.has_voted) {
            statusDiv.innerHTML = `
                <div class="status-card voted">
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="status-content">
                        <h3>You Have Voted</h3>
                        <p>Thank you for participating in this election!</p>
                        <div class="status-meta">
                            <span class="vote-time">${stats.vote_time || 'Recently'}</span>
                        </div>
                        <a href="results.php" class="btn small">View Results</a>
                    </div>
                </div>
            `;
        } else if (stats.can_vote) {
            statusDiv.innerHTML = `
                <div class="status-card ready">
                    <div class="status-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="status-content">
                        <h3>Ready to Vote</h3>
                        <p>You are eligible to cast your vote.</p>
                        <a href="vote.php" class="btn small primary">Cast Vote Now</a>
                    </div>
                </div>
            `;
        } else {
            statusDiv.innerHTML = `
                <div class="status-card ineligible">
                    <div class="status-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="status-content">
                        <h3>Not Eligible</h3>
                        <p>You are not eligible to vote in this election.</p>
                    </div>
                </div>
            `;
        }
    }

    updateElectionInfo(election) {
        const infoDiv = document.getElementById('electionInfo');
        if (!infoDiv) return;

        const statusClass = election.status.toLowerCase();
        const statusIcon = {
            'active': 'play-circle',
            'upcoming': 'clock',
            'completed': 'check-circle',
            'paused': 'pause-circle'
        };

        infoDiv.innerHTML = `
            <div class="election-header">
                <h3>${election.title}</h3>
                <span class="election-status status-${statusClass}">
                    <i class="fas fa-${statusIcon[election.status] || 'question-circle'}"></i>
                    ${election.status.charAt(0).toUpperCase() + election.status.slice(1)}
                </span>
            </div>
            <div class="election-details">
                <div class="detail-item">
                    <i class="fas fa-calendar"></i>
                    <span>${this.formatDate(election.start_date)} - ${this.formatDate(election.end_date)}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-users"></i>
                    <span>${election.total_voters || 0} Eligible Voters</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-user-tie"></i>
                    <span>${election.candidate_count || 0} Candidates</span>
                </div>
            </div>
        `;
    }

    updateCandidates(candidates) {
        const container = document.getElementById('candidatesList');
        if (!container) return;

        container.innerHTML = '';

        if (candidates.length === 0) {
            container.innerHTML = '<div class="no-data">No candidates found</div>';
            return;
        }

        candidates.forEach(candidate => {
            const candidateCard = this.createCandidateCard(candidate);
            container.appendChild(candidateCard);
        });
    }

    createCandidateCard(candidate) {
        const card = document.createElement('div');
        card.className = 'candidate-card';
        card.dataset.candidateId = candidate.id;

        card.innerHTML = `
            <div class="candidate-header">
                <div class="candidate-avatar">
                    <img src="${candidate.photo || 'assets/images/default-avatar.png'}" alt="${candidate.name}">
                </div>
                <div class="candidate-info">
                    <h4>${candidate.name}</h4>
                    <p class="candidate-party">${candidate.party || 'Independent'}</p>
                </div>
            </div>
            <div class="candidate-stats">
                <div class="stat-item">
                    <span class="stat-label">Votes:</span>
                    <span class="stat-value">${candidate.vote_count || 0}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Percentage:</span>
                    <span class="stat-value">${candidate.percentage || 0}%</span>
                </div>
            </div>
            <div class="candidate-description">
                <p>${candidate.description || 'No description available'}</p>
            </div>
        `;

        return card;
    }

    updateVotingTimer(election) {
        const timerDiv = document.getElementById('votingTimer');
        if (!timerDiv) return;

        if (election.status === 'active') {
            this.startTimer(election.end_date);
        } else if (election.status === 'upcoming') {
            timerDiv.innerHTML = `
                <div class="timer-message">
                    <i class="fas fa-clock"></i>
                    <span>Election starts in ${this.getTimeUntil(election.start_date)}</span>
                </div>
            `;
        } else {
            timerDiv.innerHTML = `
                <div class="timer-message completed">
                    <i class="fas fa-check-circle"></i>
                    <span>Election has ended</span>
                </div>
            `;
        }
    }

    startTimer(endDate) {
        const timerDiv = document.getElementById('votingTimer');

        const updateTimer = () => {
            const now = new Date().getTime();
            const end = new Date(endDate).getTime();
            const distance = end - now;

            if (distance < 0) {
                timerDiv.innerHTML = `
                    <div class="timer-message completed">
                        <i class="fas fa-check-circle"></i>
                        <span>Election has ended</span>
                    </div>
                `;
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerDiv.innerHTML = `
                <div class="timer-display">
                    <div class="timer-item">
                        <span class="timer-value">${days}</span>
                        <span class="timer-label">Days</span>
                    </div>
                    <div class="timer-item">
                        <span class="timer-value">${hours}</span>
                        <span class="timer-label">Hours</span>
                    </div>
                    <div class="timer-item">
                        <span class="timer-value">${minutes}</span>
                        <span class="timer-label">Min</span>
                    </div>
                    <div class="timer-item">
                        <span class="timer-value">${seconds}</span>
                        <span class="timer-label">Sec</span>
                    </div>
                </div>
            `;
        };

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recentActivity');
        if (!container) return;

        container.innerHTML = '';

        if (activities.length === 0) {
            container.innerHTML = '<div class="no-data">No recent activity</div>';
            return;
        }

        activities.forEach(activity => {
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';

            activityItem.innerHTML = `
                <div class="activity-icon">
                    <i class="fas fa-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <p>${activity.description}</p>
                    <span class="activity-time">${this.formatTimeAgo(activity.timestamp)}</span>
                </div>
            `;

            container.appendChild(activityItem);
        });
    }

    updateNotifications(notifications) {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        const badge = document.getElementById('notificationBadge');
        if (badge) {
            const unreadCount = notifications.filter(n => !n.read).length;
            badge.textContent = unreadCount;
            badge.style.display = unreadCount > 0 ? 'block' : 'none';
        }

        container.innerHTML = '';

        if (notifications.length === 0) {
            container.innerHTML = '<div class="no-data">No notifications</div>';
            return;
        }

        notifications.slice(0, 10).forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = `notification-item ${!notification.read ? 'unread' : ''}`;

            notificationItem.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <p>${notification.message}</p>
                    <span class="notification-time">${this.formatTimeAgo(notification.created_at)}</span>
                </div>
                ${!notification.read ? '<div class="notification-unread-dot"></div>' : ''}
            `;

            container.appendChild(notificationItem);
        });
    }

    initializeCharts() {
        // Initialize Chart.js charts
        this.initVoteChart();
        this.initParticipationChart();
        this.initDemographicsChart();
    }

    initVoteChart() {
        const ctx = document.getElementById('voteChart');
        if (!ctx) return;

        this.charts.voteChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Votes',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    initParticipationChart() {
        const ctx = document.getElementById('participationChart');
        if (!ctx) return;

        this.charts.participationChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Voted', 'Not Voted'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(255, 99, 132, 0.5)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    initDemographicsChart() {
        const ctx = document.getElementById('demographicsChart');
        if (!ctx) return;

        this.charts.demographicsChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    updateCharts(stats) {
        if (stats.candidate_votes && this.charts.voteChart) {
            this.charts.voteChart.data.labels = stats.candidate_votes.map(c => c.name);
            this.charts.voteChart.data.datasets[0].data = stats.candidate_votes.map(c => c.votes);
            this.charts.voteChart.update();
        }

        if (this.charts.participationChart) {
            const voted = stats.total_votes || 0;
            const total = stats.total_voters || 1;
            const notVoted = total - voted;

            this.charts.participationChart.data.datasets[0].data = [voted, notVoted];
            this.charts.participationChart.update();
        }

        if (stats.demographics && this.charts.demographicsChart) {
            this.charts.demographicsChart.data.labels = Object.keys(stats.demographics);
            this.charts.demographicsChart.data.datasets[0].data = Object.values(stats.demographics);
            this.charts.demographicsChart.update();
        }
    }

    changeElection(electionId) {
        this.currentElection = electionId;
        this.loadElectionData(electionId);
    }

    handleSearch(query) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.filterCandidates(query, document.getElementById('candidateFilter').value);
        }, 300);
    }

    handleFilter(filter) {
        const query = document.getElementById('candidateSearch').value;
        this.filterCandidates(query, filter);
    }

    filterCandidates(query, filter) {
        const cards = document.querySelectorAll('.candidate-card');

        cards.forEach(card => {
            const name = card.querySelector('h4').textContent.toLowerCase();
            const party = card.querySelector('.candidate-party').textContent.toLowerCase();

            const matchesQuery = !query || name.includes(query.toLowerCase()) || party.includes(query.toLowerCase());
            const matchesFilter = !filter || party.includes(filter.toLowerCase());

            card.style.display = matchesQuery && matchesFilter ? 'block' : 'none';
        });
    }

    async refreshData() {
        const refreshBtn = document.getElementById('refreshBtn');
        const originalText = refreshBtn.innerHTML;

        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;

        await this.loadDashboardData();

        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;
    }

    async exportData(type) {
        try {
            const response = await this.apiCall(`vote_api.php?action=export_${type}`);
            const result = await response.json();

            if (result.success) {
                // Trigger download
                const link = document.createElement('a');
                link.href = result.file_url;
                link.download = result.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                this.showError('Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            this.showError('Export failed');
        }
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.innerHTML = newTheme === 'dark' ?
                '<i class="fas fa-sun"></i>' :
                '<i class="fas fa-moon"></i>';
        }
    }

    bindNotificationSettings() {
        const settings = document.querySelectorAll('.notification-setting input');
        settings.forEach(setting => {
            setting.addEventListener('change', (e) => this.updateNotificationSetting(e.target.name, e.target.checked));
        });
    }

    async updateNotificationSetting(setting, enabled) {
        try {
            await this.apiCall('vote_api.php?action=update_notification_setting', 'POST', {
                setting,
                enabled
            });
        } catch (error) {
            console.error('Error updating notification setting:', error);
        }
    }

    startRealTimeUpdates() {
        this.updateInterval = setInterval(() => {
            this.loadStats();
            this.loadRecentActivity();
            this.loadNotifications();
        }, 30000); // Update every 30 seconds
    }

    loadUserPreferences() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);

        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.innerHTML = theme === 'dark' ?
                '<i class="fas fa-sun"></i>' :
                '<i class="fas fa-moon"></i>';
        }
    }

    // Utility methods
    animateNumber(element, target) {
        const start = parseInt(element.textContent) || 0;
        const duration = 1000;
        const step = (target - start) / (duration / 16);
        let current = start;

        const animate = () => {
            current += step;
            if ((step > 0 && current >= target) || (step < 0 && current <= target)) {
                element.textContent = target;
            } else {
                element.textContent = Math.floor(current);
                requestAnimationFrame(animate);
            }
        };

        animate();
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (days > 0) return `${days} days ago`;
        if (hours > 0) return `${hours} hours ago`;
        if (minutes > 0) return `${minutes} minutes ago`;
        return 'Just now';
    }

    getTimeUntil(dateString) {
        const now = new Date();
        const target = new Date(dateString);
        const diff = target - now;

        if (diff <= 0) return 'Started';

        const days = Math.floor(diff / 86400000);
        const hours = Math.floor((diff % 86400000) / 3600000);

        if (days > 0) return `${days} days ${hours} hours`;
        return `${hours} hours`;
    }

    getActivityIcon(type) {
        const icons = {
            'vote': 'vote-yea',
            'login': 'sign-in-alt',
            'register': 'user-plus',
            'election_start': 'play-circle',
            'election_end': 'stop-circle'
        };
        return icons[type] || 'circle';
    }

    getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'warning': 'exclamation-triangle',
            'success': 'check-circle',
            'error': 'times-circle'
        };
        return icons[type] || 'bell';
    }

    showLoading() {
        const loader = document.getElementById('loadingOverlay');
        if (loader) loader.style.display = 'flex';
    }

    hideLoading() {
        const loader = document.getElementById('loadingOverlay');
        if (loader) loader.style.display = 'none';
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;

        const container = document.querySelector('.dashboard-container') || document.body;
        container.insertBefore(alert, container.firstChild);

        setTimeout(() => alert.remove(), 5000);
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

// Initialize DashboardManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.dashboardManager = new DashboardManager();
});