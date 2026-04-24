// Enhanced Results JavaScript with Real-time Analytics and Charts
class ResultsManager {
    constructor() {
        this.charts = {};
        this.updateInterval = null;
        this.currentElection = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadResults();
        this.startRealTimeUpdates();
    }

    bindEvents() {
        // Election selector
        const electionSelect = document.getElementById('electionSelect');
        if (electionSelect) {
            electionSelect.addEventListener('change', (e) => this.changeElection(e.target.value));
        }

        // Refresh button
        const refreshBtn = document.getElementById('refreshResults');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadResults(true));
        }

        // Export button
        const exportBtn = document.getElementById('exportResults');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportResults());
        }

        // View toggle
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleView(e.target.dataset.view));
        });
    }

    async loadResults(showRefresh = false) {
        const resultsArea = document.getElementById('resultsArea');
        const refreshBtn = document.getElementById('refreshResults');

        if (showRefresh && refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
        }

        try {
            this.showLoading();

            // Load elections first
            await this.loadElections();

            // Load results
            let url = 'results_api.php';
            if (this.currentElection) {
                url += `?election_id=${this.currentElection}`;
            }

            const response = await this.apiCall(url);
            const data = await response.json();

            if (!data.success) {
                this.showError(resultsArea, data.message);
                return;
            }

            this.renderResults(data.results, data.summary);
            this.updateSummary(data.summary);
            this.initializeCharts(data.results, data.summary);

            this.hideLoading();
        } catch (error) {
            this.showError(resultsArea, 'Unable to load results.');
            console.error(error);
            this.hideLoading();
        } finally {
            if (showRefresh && refreshBtn) {
                setTimeout(() => {
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Results';
                    refreshBtn.disabled = false;
                }, 1000);
            }
        }
    }

    async loadElections() {
        try {
            const response = await this.apiCall('vote_api.php?action=elections');
            const result = await response.json();

            if (result.success) {
                this.populateElectionSelector(result.elections);
            }
        } catch (error) {
            console.error('Error loading elections:', error);
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
            if (election.status === 'completed' || election.status === 'active') {
                if (!this.currentElection) {
                    this.currentElection = election.id;
                    option.selected = true;
                }
            }
            select.appendChild(option);
        });
    }

    renderResults(results, summary) {
        const resultsArea = document.getElementById('resultsArea');

        if (results.length === 0) {
            resultsArea.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-chart-bar"></i>
                    <h3>No Votes Cast Yet</h3>
                    <p>Election results will appear here once voting begins.</p>
                </div>
            `;
            return;
        }

        // Sort results by votes (descending)
        const sortedResults = [...results].sort((a, b) => b.votes - a.votes);
        const maxVotes = Math.max(...sortedResults.map(r => parseInt(r.votes)));

        const resultsHTML = `
            <div class="results-header">
                <h2>Election Results</h2>
                <div class="results-meta">
                    <span class="meta-item">
                        <i class="fas fa-clock"></i>
                        Last updated: ${new Date().toLocaleTimeString()}
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-sync-alt fa-spin"></i>
                        Auto-refreshing
                    </span>
                </div>
            </div>

            <div class="results-visualization">
                ${sortedResults.map((result, index) => {
                    const percentage = summary.total_votes > 0 ? ((result.votes / summary.total_votes) * 100).toFixed(1) : 0;
                    const barWidth = maxVotes > 0 ? (result.votes / maxVotes) * 100 : 0;
                    const isWinner = index === 0 && result.votes > 0;

                    return `
                        <div class="result-row ${isWinner ? 'leader' : ''}">
                            <div class="candidate-info">
                                <div class="candidate-rank ${isWinner ? 'winner' : ''}">${index + 1}</div>
                                <div class="candidate-avatar">
                                    <img src="${result.image_url || 'assets/images/default-avatar.png'}" alt="${result.name}" onerror="this.src='https://via.placeholder.com/50x50/4F46E5/FFFFFF?text=${result.name.charAt(0)}'" />
                                </div>
                                <div class="candidate-details">
                                    <h3>${result.name} ${isWinner ? '<i class="fas fa-trophy"></i>' : ''}</h3>
                                    <span class="candidate-party">${result.party || 'Independent'}</span>
                                </div>
                            </div>
                            <div class="vote-bar-container">
                                <div class="vote-bar" style="width: 0%" data-width="${barWidth}%"></div>
                                <div class="vote-count">
                                    <span class="votes">${result.votes.toLocaleString()}</span>
                                    <span class="percentage">${percentage}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>

            <div class="results-charts">
                <div class="chart-container">
                    <h3>Vote Distribution</h3>
                    <canvas id="resultsPieChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Participation</h3>
                    <canvas id="participationChart"></canvas>
                </div>
            </div>
        `;

        resultsArea.innerHTML = resultsHTML;

        // Animate bars after render
        setTimeout(() => {
            document.querySelectorAll('.vote-bar').forEach(bar => {
                bar.style.width = bar.dataset.width;
            });
        }, 100);
    }

    updateSummary(summary) {
        const elements = {
            'totalCandidates': summary.total_candidates,
            'totalVotes': summary.total_votes.toLocaleString(),
            'participationRate': `${summary.participation_rate}%`
        };

        Object.entries(elements).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });

        const summaryDiv = document.getElementById('resultsSummary');
        if (summaryDiv) summaryDiv.style.display = 'block';
    }

    initializeCharts(results, summary) {
        // Destroy existing charts
        Object.values(this.charts).forEach(chart => chart.destroy());
        this.charts = {};

        // Pie Chart
        const pieCtx = document.getElementById('resultsPieChart');
        if (pieCtx) {
            this.charts.pie = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: results.map(r => r.name),
                    datasets: [{
                        data: results.map(r => r.votes),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(6, 182, 212, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.raw / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.raw} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Participation Chart
        const partCtx = document.getElementById('participationChart');
        if (partCtx) {
            const voted = summary.total_votes;
            const eligible = summary.total_voters || summary.total_votes + 1;
            const notVoted = eligible - voted;

            this.charts.participation = new Chart(partCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Voted', 'Not Voted'],
                    datasets: [{
                        data: [voted, notVoted],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    changeElection(electionId) {
        this.currentElection = electionId;
        this.loadResults();
    }

    toggleView(view) {
        const resultsArea = document.getElementById('resultsArea');
        resultsArea.className = `results-view-${view}`;

        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
    }

    async exportResults() {
        try {
            const response = await this.apiCall('results_api.php?action=export');
            const result = await response.json();

            if (result.success) {
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

    startRealTimeUpdates() {
        this.updateInterval = setInterval(() => {
            this.loadResults();
        }, 30000); // Update every 30 seconds
    }

    // Utility methods
    showLoading() {
        const loader = document.getElementById('loadingOverlay');
        if (loader) loader.style.display = 'flex';
    }

    hideLoading() {
        const loader = document.getElementById('loadingOverlay');
        if (loader) loader.style.display = 'none';
    }

    showError(container, message) {
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }

    showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> ${message}`;

        const container = document.querySelector('.results-container') || document.body;
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

// Initialize ResultsManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.resultsManager = new ResultsManager();
});