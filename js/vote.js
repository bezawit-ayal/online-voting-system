// Enhanced Voting JavaScript with Multi-Election Support and Real-time Updates
class VoteManager {
    constructor() {
        this.selectedCandidate = null;
        this.confirmModal = null;
        this.updateInterval = null;
        this.currentElection = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadVotingInterface();
        this.startRealTimeUpdates();
    }

    bindEvents() {
        // Vote form submission
        const voteForm = document.getElementById('voteForm');
        if (voteForm) {
            voteForm.addEventListener('submit', (e) => this.handleVoteSubmission(e));
        }

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

        // Confirm vote button
        const confirmBtn = document.getElementById('confirmVoteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.confirmVote());
        }

        // Cancel vote button
        const cancelBtn = document.getElementById('cancelVoteBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.cancelVote());
        }

        // Close modal
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });
    }

    async loadVotingInterface() {
        const votingInterface = document.getElementById('votingInterface');

        try {
            this.showLoading();

            // Load elections first
            await this.loadElections();

            // Check voting status
            const statusResponse = await this.apiCall('vote_api.php?action=status');
            const statusData = await statusResponse.json();

            if (!statusData.success) {
                this.showErrorState(votingInterface, statusData.message);
                return;
            }

            if (statusData.has_voted) {
                this.showAlreadyVoted(votingInterface, statusData);
                return;
            }

            // Check if voting is open
            if (!statusData.voting_open) {
                this.showVotingClosed(votingInterface, statusData);
                return;
            }

            // Load candidates
            await this.loadCandidates();

            this.hideLoading();
        } catch (error) {
            this.showErrorState(votingInterface, 'Unable to load voting interface.');
            console.error(error);
            this.hideLoading();
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
            if (election.status === 'active') {
                const option = document.createElement('option');
                option.value = election.id;
                option.textContent = election.title;
                option.selected = true;
                this.currentElection = election.id;
                select.appendChild(option);
            }
        });
    }

    async loadCandidates() {
        const votingInterface = document.getElementById('votingInterface');

        try {
            let url = 'vote_api.php?action=candidates';
            if (this.currentElection) {
                url += `&election_id=${this.currentElection}`;
            }

            const response = await this.apiCall(url);
            const data = await response.json();

            if (!data.success) {
                this.showErrorState(votingInterface, data.message);
                return;
            }

            this.renderVotingForm(data.candidates);
            this.updateElectionInfo(data.election);
        } catch (error) {
            this.showErrorState(votingInterface, 'Unable to load candidates.');
            console.error(error);
        }
    }

    renderVotingForm(candidates) {
        const votingInterface = document.getElementById('votingInterface');

        if (candidates.length === 0) {
            votingInterface.innerHTML = `
                <div class="no-candidates">
                    <i class="fas fa-user-slash"></i>
                    <h3>No Candidates Available</h3>
                    <p>There are no candidates for this election yet.</p>
                </div>
            `;
            return;
        }

        votingInterface.innerHTML = `
            <div class="voting-form-container">
                <div class="voting-instructions">
                    <h2>Select Your Candidate</h2>
                    <p>Choose one candidate below. Your vote is confidential and can only be cast once.</p>
                </div>

                <div class="search-filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="candidateSearch" placeholder="Search candidates..." />
                    </div>
                </div>

                <form id="voteForm" class="voting-form">
                    <div class="candidates-grid">
                        ${candidates.map(candidate => `
                            <label class="candidate-card" for="candidate_${candidate.id}">
                                <input type="radio" id="candidate_${candidate.id}" name="candidate_id" value="${candidate.id}" required />
                                <div class="candidate-content">
                                    <div class="candidate-avatar">
                                        <img src="${candidate.image_url || 'assets/images/default-avatar.png'}" alt="${candidate.name}" onerror="this.src='https://via.placeholder.com/80x80/4F46E5/FFFFFF?text=${candidate.name.charAt(0)}'" />
                                    </div>
                                    <div class="candidate-info">
                                        <h3>${candidate.name}</h3>
                                        <p class="candidate-party">${candidate.party || 'Independent'}</p>
                                        <p class="candidate-description">${candidate.description || 'No description available'}</p>
                                        <div class="candidate-stats">
                                            <span class="stat"><i class="fas fa-vote-yea"></i> ${candidate.vote_count || 0} votes</span>
                                        </div>
                                    </div>
                                    <div class="candidate-radio">
                                        <div class="radio-indicator"></div>
                                    </div>
                                </div>
                            </label>
                        `).join('')}
                    </div>

                    <div class="vote-actions">
                        <button type="submit" class="btn primary large" id="castVoteBtn" disabled>
                            <i class="fas fa-vote-yea"></i>
                            Cast My Vote
                        </button>
                        <p class="vote-note">
                            <i class="fas fa-info-circle"></i>
                            By voting, you confirm that you are eligible to vote and this is your only vote.
                        </p>
                    </div>
                </form>
            </div>
        `;

        // Bind candidate selection events
        this.bindCandidateSelection();
    }

    bindCandidateSelection() {
        const candidateCards = document.querySelectorAll('.candidate-card');
        const castBtn = document.getElementById('castVoteBtn');

        candidateCards.forEach(card => {
            card.addEventListener('click', () => {
                // Remove selected class from all cards
                candidateCards.forEach(c => c.classList.remove('selected'));
                // Add selected class to clicked card
                card.classList.add('selected');
                // Enable submit button
                castBtn.disabled = false;
                this.selectedCandidate = card.querySelector('input').value;
            });
        });
    }

    updateElectionInfo(election) {
        const infoDiv = document.getElementById('electionInfo');
        if (!infoDiv || !election) return;

        infoDiv.innerHTML = `
            <div class="election-info-card">
                <h3>${election.title}</h3>
                <div class="election-meta">
                    <span class="meta-item">
                        <i class="fas fa-calendar"></i>
                        Ends: ${this.formatDateTime(election.end_date)}
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-users"></i>
                        ${election.total_voters || 0} voters
                    </span>
                </div>
            </div>
        `;

        // Start countdown timer
        this.startCountdown(election.end_date);
    }

    startCountdown(endDate) {
        const timerDiv = document.getElementById('votingTimer');
        if (!timerDiv) return;

        const updateTimer = () => {
            const now = new Date().getTime();
            const end = new Date(endDate).getTime();
            const distance = end - now;

            if (distance < 0) {
                timerDiv.innerHTML = '<span class="timer-ended">Voting has ended</span>';
                this.disableVoting();
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerDiv.innerHTML = `
                <div class="countdown">
                    <div class="countdown-item">
                        <span class="countdown-value">${hours}</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">${minutes}</span>
                        <span class="countdown-label">Min</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">${seconds}</span>
                        <span class="countdown-label">Sec</span>
                    </div>
                </div>
            `;
        };

        updateTimer();
        this.updateInterval = setInterval(updateTimer, 1000);
    }

    disableVoting() {
        const form = document.getElementById('voteForm');
        if (form) {
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => input.disabled = true);

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
        }

        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }

    async handleVoteSubmission(event) {
        event.preventDefault();

        if (!this.selectedCandidate) {
            this.showAlert('Please select a candidate before submitting your vote.', 'error');
            return;
        }

        // Show confirmation modal
        this.showConfirmationModal();
    }

    showConfirmationModal() {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;

        const candidateName = document.querySelector(`#candidate_${this.selectedCandidate}`).closest('.candidate-card').querySelector('h3').textContent;

        const candidateNameEl = document.getElementById('confirmCandidateName');
        if (candidateNameEl) {
            candidateNameEl.textContent = candidateName;
        }

        modal.classList.add('active');
    }

    async confirmVote() {
        const confirmBtn = document.getElementById('confirmVoteBtn');
        const originalText = confirmBtn.innerHTML;

        this.setLoadingState(confirmBtn, 'Submitting...');

        try {
            const response = await this.apiCall('vote_api.php?action=cast', 'POST', {
                candidate_id: this.selectedCandidate,
                election_id: this.currentElection
            });

            const data = await response.json();

            if (data.success) {
                this.closeModal();
                this.showVoteConfirmation(data);
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            this.showAlert('Unable to submit vote. Please try again.', 'error');
            console.error(error);
        } finally {
            this.resetLoadingState(confirmBtn, originalText);
        }
    }

    cancelVote() {
        this.closeModal();
    }

    closeModal() {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    showVoteConfirmation(data) {
        const votingInterface = document.getElementById('votingInterface');
        const confirmation = document.getElementById('voteConfirmation');

        if (votingInterface) votingInterface.classList.add('hidden');
        if (confirmation) {
            confirmation.classList.remove('hidden');

            // Update confirmation details
            const timestamp = document.getElementById('voteTimestamp');
            if (timestamp) {
                timestamp.textContent = new Date().toLocaleString();
            }

            const transactionId = document.getElementById('transactionId');
            if (transactionId && data.transaction_id) {
                transactionId.textContent = data.transaction_id;
            }
        }
    }

    showAlreadyVoted(container, data) {
        container.innerHTML = `
            <div class="already-voted">
                <div class="voted-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>You Have Already Voted</h2>
                <p>Thank you for participating! Your vote has been recorded and counted.</p>
                <div class="vote-stats">
                    <div class="stat">
                        <span class="stat-number">${data.total_candidates}</span>
                        <span class="stat-label">Candidates</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">${data.total_votes}</span>
                        <span class="stat-label">Total Votes</span>
                    </div>
                </div>
                <a href="results.php" class="btn primary">
                    <i class="fas fa-chart-bar"></i>
                    View Live Results
                </a>
            </div>
        `;
    }

    showVotingClosed(container, data) {
        container.innerHTML = `
            <div class="voting-closed">
                <div class="closed-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h2>Voting is Closed</h2>
                <p>${data.message || 'This election has ended. You can no longer cast your vote.'}</p>
                <a href="results.php" class="btn primary">
                    <i class="fas fa-chart-bar"></i>
                    View Results
                </a>
            </div>
        `;
    }

    showErrorState(container, message) {
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }

    changeElection(electionId) {
        this.currentElection = electionId;
        this.loadCandidates();
    }

    handleSearch(query) {
        const cards = document.querySelectorAll('.candidate-card');

        cards.forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const party = card.querySelector('.candidate-party').textContent.toLowerCase();
            const matches = !query || name.includes(query.toLowerCase()) || party.includes(query.toLowerCase());
            card.style.display = matches ? 'block' : 'none';
        });
    }

    startRealTimeUpdates() {
        // Update candidate vote counts periodically
        setInterval(() => {
            if (this.selectedCandidate) return; // Don't update while user is selecting
            this.loadCandidates();
        }, 30000);
    }

    // Utility methods
    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
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

    showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> ${message}`;

        const container = document.querySelector('.voting-container') || document.body;
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

// Initialize VoteManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.voteManager = new VoteManager();
});
                                <div class="candidate-info">
                                    <h3>${candidate.name}</h3>
                                    <p class="candidate-party">${candidate.party || 'Independent'}</p>
                                    <p class="candidate-description">${candidate.description}</p>
                                </div>
                                <div class="candidate-radio">
                                    <div class="radio-indicator"></div>
                                </div>
                            </div>
                        </label>
                    `).join('')}
                </div>

                <div class="vote-actions">
                    <button type="submit" class="btn primary large">
                        <i class="fas fa-vote-yea"></i>
                        Cast My Vote
                    </button>
                    <p class="vote-note">
                        <i class="fas fa-info-circle"></i>
                        By voting, you confirm that you are eligible to vote and this is your only vote.
                    </p>
                </div>
            </form>
        </div>
    `;

    // Add form submission handler
    document.getElementById('voteForm').addEventListener('submit', handleVoteSubmission);
}

async function handleVoteSubmission(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    const formData = new FormData(form);
    const candidateId = formData.get('candidate_id');

    if (!candidateId) {
        showAlert('Please select a candidate before submitting your vote.', 'error');
        return;
    }

    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Vote...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('vote_api.php?action=cast', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ candidate_id: candidateId }),
        });

        const data = await response.json();

        if (data.success) {
            showVoteConfirmation();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Unable to submit vote. Please try again.', 'error');
        console.error(error);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function showVoteConfirmation() {
    document.getElementById('votingInterface').classList.add('hidden');
    document.getElementById('voteConfirmation').classList.remove('hidden');

    // Set current time
    const now = new Date();
    document.getElementById('voteTime').textContent = now.toLocaleString();

    // Add celebration animation
    setTimeout(() => {
        document.getElementById('voteConfirmation').classList.add('animate-in');
    }, 100);
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i> ${message}`;

    const container = document.querySelector('.vote-content');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}