// Main JavaScript file for Online Voting System

// Global variables
let selectedVotes = {};

document.addEventListener('DOMContentLoaded', function () {
    // Initialize
    initializeVotingInterface();
    setupEventListeners();
});

// Initialize voting interface
function initializeVotingInterface() {
    const candidateCards = document.querySelectorAll('.candidate-card');

    candidateCards.forEach(card => {
        card.addEventListener('click', function () {
            const candidateId = this.getAttribute('data-candidate-id');
            const position = this.getAttribute('data-position');
            const candidateName = this.querySelector('.candidate-name').textContent;

            // Remove previous selection for this position
            const positionCards = document.querySelectorAll(`[data-position="${position}"]`);
            positionCards.forEach(c => c.classList.remove('selected'));

            // Add selection to current card
            this.classList.add('selected');

            // Store selection
            selectedVotes[position] = {
                candidateId: candidateId,
                candidateName: candidateName
            };

            updateSelectedDisplay();
        });
    });
}

// Setup event listeners
function setupEventListeners() {
    const submitBtn = document.getElementById('submitVotes');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitVotes);
    }

    const confirmBtn = document.getElementById('confirmVote');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmVote);
    }

    const cancelBtn = document.getElementById('cancelVote');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelVote);
    }
}

// Update selected display
function updateSelectedDisplay() {
    const selectedDiv = document.getElementById('selectedCandidates');
    if (!selectedDiv) return;

    let html = '<h3>Your Selections:</h3><ul>';

    for (let position in selectedVotes) {
        html += `<li><strong>${position}:</strong> ${selectedVotes[position].candidateName}</li>`;
    }

    html += '</ul>';
    selectedDiv.innerHTML = html;

    // Show/hide submit button based on selections
    const positions = document.querySelectorAll('[data-position]');
    const uniquePositions = new Set();
    positions.forEach(p => uniquePositions.add(p.getAttribute('data-position')));

    const submitBtn = document.getElementById('submitVotes');
    if (submitBtn) {
        if (selectedVotes.length === uniquePositions.size) {
            submitBtn.style.display = 'block';
        }
    }
}

// Submit votes
function submitVotes() {
    const positions = document.querySelectorAll('[data-position]');
    const uniquePositions = new Set();
    positions.forEach(p => uniquePositions.add(p.getAttribute('data-position')));

    if (Object.keys(selectedVotes).length !== uniquePositions.size) {
        showAlert('Please vote for all positions', 'error');
        return;
    }

    // Show confirmation modal
    showConfirmationModal();
}

// Show confirmation modal
function showConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (!modal) {
        createConfirmationModal();
    } else {
        modal.classList.add('show');
    }
}

// Create confirmation modal
function createConfirmationModal() {
    const modalHTML = `
        <div id="confirmationModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Confirm Your Votes</h2>
                    <button class="modal-close" onclick="closeModal('confirmationModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Please review your selections before confirming:</p>
                    <div id="confirmationList"></div>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Once you confirm your votes, you cannot change them.
                    </div>
                </div>
                <div class="modal-footer" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <button class="btn btn-secondary" onclick="closeModal('confirmationModal')">Cancel</button>
                    <button class="btn btn-success" onclick="confirmVote()">Confirm & Submit</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Populate confirmation list
    const confirmationList = document.getElementById('confirmationList');
    let html = '<ul style="margin: 1rem 0;">';

    for (let position in selectedVotes) {
        html += `<li><strong>${position}:</strong> ${selectedVotes[position].candidateName}</li>`;
    }

    html += '</ul>';
    confirmationList.innerHTML = html;

    document.getElementById('confirmationModal').classList.add('show');
}

// Confirm vote
function confirmVote() {
    const votes = [];

    for (let position in selectedVotes) {
        votes.push({
            position: position,
            candidateId: selectedVotes[position].candidateId
        });
    }

    // Send votes to server
    fetch('includes/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cast_vote',
            votes: votes
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('confirmationModal');
                showAlert('Votes submitted successfully!', 'success');

                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = 'voting_complete.php';
                }, 2000);
            } else {
                showAlert(data.message || 'Error submitting votes', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error submitting votes', 'error');
        });
}

// Cancel vote
function cancelVote() {
    closeModal('confirmationModal');
}

// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Show alert
function showAlert(message, type = 'info') {
    const alertHTML = `
        <div class="alert alert-${type}" style="margin-bottom: 1rem;">
            ${message}
        </div>
    `;

    const alertContainer = document.getElementById('alertContainer');
    if (alertContainer) {
        alertContainer.insertAdjacentHTML('beforeend', alertHTML);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alerts = alertContainer.querySelectorAll('.alert');
            if (alerts.length > 0) {
                alerts[0].remove();
            }
        }, 5000);
    }
}

// Validate form
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (input.value.trim() === '') {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });

    return isValid;
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Get query parameter
function getQueryParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Debounce function
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
}

// Animate progress bar
function animateProgressBar(bar, percentage) {
    let current = 0;
    const increment = percentage / 50;

    const timer = setInterval(() => {
        current += increment;
        if (current >= percentage) {
            current = percentage;
            clearInterval(timer);
        }
        bar.style.width = current + '%';
    }, 10);
}

// Initialize progress bars on page load
function initializeProgressBars() {
    const bars = document.querySelectorAll('.progress-fill');
    bars.forEach(bar => {
        const percentage = parseFloat(bar.getAttribute('data-percentage')) || 0;
        animateProgressBar(bar, percentage);
    });
}

// Export results as CSV
function exportResultsCSV() {
    const table = document.querySelector('.table');
    if (!table) return;

    let csv = [];

    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));

    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });

    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'voting_results.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Print page
function printPage() {
    window.print();
}

// Toggle sidebar (for mobile)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}


// Smooth scrolling for links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// FAQ toggle functionality
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function () {
        this.parentElement.classList.toggle('active');
    });
});

// Add scroll animation
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver(function (entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'slideInUp 0.6s ease forwards';
        }
    });
}, observerOptions);

document.querySelectorAll('.feature-card, .benefit-item, .step-card').forEach(el => {
    observer.observe(el);
});

// Add keyframe animation
const style = document.createElement('style');
style.textContent = `
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
document.head.appendChild(style);

document.addEventListener("DOMContentLoaded", function () {

    let selected = {};

    const cards = document.querySelectorAll(".candidate-card");
    const submitBtn = document.getElementById("submitVotes");

    cards.forEach(card => {
        card.addEventListener("click", function () {

            const id = this.getAttribute("data-candidate-id");
            const position = this.getAttribute("data-position");

            // remove previous selection for this position
            document.querySelectorAll(`[data-position="${position}"]`)
                .forEach(c => c.classList.remove("selected"));

            // select current
            this.classList.add("selected");

            // store selection
            selected[position] = id;

            updateUI();
        });
    });

    function updateUI() {
        const box = document.getElementById("selectedCandidates");

        let html = "<h3>Your Selections:</h3>";
        let hasSelection = false;

        for (let pos in selected) {
            hasSelection = true;
            html += `<p><strong>${pos}:</strong> Candidate ID ${selected[pos]}</p>`;
        }

        if (!hasSelection) {
            html += "<p style='color:#888;'>No candidates selected yet</p>";
        }

        box.innerHTML = html;

        // 👇 SHOW BUTTON ONLY IF USER SELECTED SOMETHING
        if (hasSelection) {
            submitBtn.style.display = "block";
        } else {
            submitBtn.style.display = "none";
        }
    }

    // =========================
    // SUBMIT VOTES
    // =========================
    submitBtn.addEventListener("click", function () {

        if (Object.keys(selected).length === 0) {
            alert("Please select at least one candidate!");
            return;
        }

        // send to server
        fetch("submit_vote.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(selected)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Vote submitted successfully!");
                    window.location.href = "voting_complete.php";
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                alert("Something went wrong!");
            });

    });

});

