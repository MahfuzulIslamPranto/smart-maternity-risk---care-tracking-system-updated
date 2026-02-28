// script.js
document.addEventListener('DOMContentLoaded', function() {
    // Navigation Logic
    const navButtons = document.querySelectorAll(".nav-btn");
    const sections = document.querySelectorAll(".section");
    
    navButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            const targetId = this.getAttribute("data-target");
            
            // Update active button
            navButtons.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            
            // Show target section
            sections.forEach(sec => sec.classList.add("hidden"));
            document.getElementById(targetId).classList.remove("hidden");
            
            // Load specific section data
            switch(targetId) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'motherManagement':
                    initMotherManagement();
                    break;
                case 'ancHistory':
                    initANCHistory();
                    break;
            }
        });
    });

    // Tab Management
    const tabBtns = document.querySelectorAll(".tab-btn");
    tabBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            const tabId = this.getAttribute("data-tab");
            
            // Update active tab
            tabBtns.forEach(t => t.classList.remove("active"));
            this.classList.add("active");
            
            // Show target tab content
            document.querySelectorAll(".tab-content").forEach(tc => {
                tc.classList.add("hidden");
            });
            document.getElementById(tabId).classList.remove("hidden");
        });
    });

    // Initialize date display
    updateCurrentDate();
    setInterval(updateCurrentDate, 60000);

    // Initialize dashboard
    loadDashboardData();
    initCharts();
});

function updateCurrentDate() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
}

function loadDashboardData() {
    // Fetch updated counts via AJAX
    fetch('ajax/get_counts.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('highRiskCount').textContent = data.highRisk || 0;
            document.getElementById('ancAlertCount').textContent = data.ancAlerts || 0;
            document.getElementById('emergencyCount').textContent = data.emergencies || 0;
            document.getElementById('safePregnancyCount').textContent = data.safePregnancies || 0;
        })
        .catch(error => console.error('Error loading dashboard:', error));
}

function initCharts() {
    const ctx = document.getElementById('riskChart').getContext('2d');
    
    // Fetch chart data
    fetch('ajax/get_chart_data.php')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['High Risk', 'Medium Risk', 'Low Risk'],
                    datasets: [{
                        data: [data.high, data.medium, data.low],
                        backgroundColor: [
                            '#e74c3c',
                            '#f39c12',
                            '#27ae60'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
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
        });
}

function calculateRiskPreview() {
    const age = parseInt(document.querySelector('input[name="age"]').value) || 25;
    const bp = document.querySelector('input[name="bp"]').value || '120/80';
    const sugar = parseFloat(document.querySelector('input[name="sugar"]').value) || 5.0;
    const hemoglobin = parseFloat(document.querySelector('input[name="hemoglobin"]').value) || 12.0;
    const weight = parseFloat(document.querySelector('input[name="weight"]').value) || 60.0;
    
    let riskScore = 0;
    
    // Age risk
    if (age < 18 || age > 35) riskScore += 20;
    if (age > 40) riskScore += 30;
    
    // Blood Pressure risk
    const bpParts = bp.split('/');
    const systolic = parseInt(bpParts[0]) || 120;
    const diastolic = parseInt(bpParts[1]) || 80;
    
    if (systolic > 140 || diastolic > 90) riskScore += 25;
    if (systolic > 160 || diastolic > 100) riskScore += 35;
    
    // Sugar level risk
    if (sugar > 7.0) riskScore += 20;
    if (sugar > 8.5) riskScore += 30;
    
    // Hemoglobin risk
    if (hemoglobin < 11.0) riskScore += 15;
    if (hemoglobin < 10.0) riskScore += 25;
    
    // Determine risk level
    let riskLevel = 'Low';
    if (riskScore >= 60) riskLevel = 'High';
    else if (riskScore >= 30) riskLevel = 'Medium';
    
    const riskElement = document.getElementById('calculatedRisk');
    riskElement.textContent = riskLevel;
    riskElement.className = riskLevel.toLowerCase();
}

function navigateTo(section) {
    document.querySelectorAll(".nav-btn").forEach(b => b.classList.remove("active"));
    document.querySelectorAll(".section").forEach(s => s.classList.add("hidden"));
    
    const targetBtn = document.querySelector(`[data-target="${section}"]`);
    if (targetBtn) targetBtn.classList.add("active");
    
    const targetSection = document.getElementById(section);
    if (targetSection) targetSection.classList.remove("hidden");
}

function viewHighRiskMothers() {
    navigateTo('motherManagement');
    document.querySelector('#filterRisk').value = 'High';
    filterMothers();
    document.querySelector('[data-tab="motherList"]').click();
}

function viewANCAlerts() {
    navigateTo('motherManagement');
    document.querySelector('#filterANC').value = 'urgent';
    filterANC();
    document.querySelector('[data-tab="ancSchedule"]').click();
}

function viewEmergencies() {
    alert('Showing mothers with delivery within 7 days');
    // This would filter the mother list
}

// Function to view safe pregnancies (Low + Medium risk)
function viewSafePregnancies() {
    // Navigate to mother management section
    navigateTo('motherManagement');
    
    // Activate the Mother List tab
    document.querySelector('[data-tab="motherList"]').click();
    
    // Set filter to show Low and Medium risk (excluding High)
    // Clear any existing single risk filter
    document.getElementById('filterRisk').value = '';
    
    // Call a custom filter function for safe pregnancies
    filterSafePregnancies();
}

// Custom filter for safe pregnancies (Low and Medium only)
function filterSafePregnancies() {
    const rows = document.querySelectorAll('#motherTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.classList.contains('no-data')) return;
        
        const risk = row.getAttribute('data-risk');
        
        // Show if risk is Low or Medium (case-insensitive)
        if (risk && (risk.toLowerCase() === 'low' || risk.toLowerCase() === 'medium')) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide empty state
    const noDataRow = document.querySelector('#motherTableBody .no-data');
    if (noDataRow) {
        noDataRow.style.display = visibleCount === 0 ? '' : 'none';
    }
    
    // Also update the filter dropdown to reflect that we're showing both
    document.getElementById('filterRisk').value = '';
}

// Modify the existing filterMothers() to handle single value (for other filters)
function filterMothers() {
    const riskFilter = document.getElementById('filterRisk').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#motherTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.classList.contains('no-data')) return;
        
        const risk = row.getAttribute('data-risk');
        const status = row.getAttribute('data-status');
        
        let show = true;
        
        // Apply risk filter (only if a specific value is selected)
        if (riskFilter && risk !== riskFilter) {
            show = false;
        }
        
        // Apply status filter
        if (statusFilter) {
            if (statusFilter === 'active' && status !== 'active') show = false;
            if (statusFilter === 'inactive' && status !== 'inactive') show = false;
        }
        
        if (show) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide empty state
    const noDataRow = document.querySelector('#motherTableBody .no-data');
    if (noDataRow) {
        noDataRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function filterANC() {
    const filterValue = document.getElementById('filterANC').value;
    const rows = document.querySelectorAll('#ancTableBody tr');
    const today = new Date();
    
    rows.forEach(row => {
        let show = true;
        
        if (filterValue === 'urgent') {
            const daysElement = row.querySelector('.days-badge');
            if (daysElement) {
                const days = parseInt(daysElement.textContent);
                show = days <= 3;
            }
        } else if (filterValue === 'thisWeek') {
            const daysElement = row.querySelector('.days-badge');
            if (daysElement) {
                const days = parseInt(daysElement.textContent);
                show = days <= 7;
            }
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function viewMotherProfile(motherId) {
    navigateTo('motherProfile');
    
    fetch(`ajax/get_mother_profile.php?id=${motherId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('profileContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('profileContent').innerHTML = '<div class="error-message">Error loading profile</div>';
        });
}

function editMother(motherId) {
    openUpdateProfileModal(motherId);
}

function deleteMother(motherId) {
    if (confirm('Are you sure you want to delete this mother record?')) {
        fetch(`ajax/delete_mother.php?id=${motherId}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Mother record deleted successfully');
                    location.reload();
                } else {
                    alert('Error deleting record');
                }
            });
    }
}

function notifyMother(motherId) {
    // Send notification to mother
    alert(`Notification sent to mother ID: ${motherId}`);
}

function rescheduleANC(motherId) {
    const newDate = prompt('Enter new ANC date (YYYY-MM-DD):');
    if (newDate) {
        // Update in database
        alert(`ANC rescheduled to: ${newDate}`);
    }
}

function initANCHistory() {
    document.getElementById('selectMotherANC').addEventListener('change', loadANCHistory);
    document.getElementById('ancDateFilter').addEventListener('change', loadANCHistory);
}

function loadANCHistory() {
    const motherId = document.getElementById('selectMotherANC').value;
    const dateFilter = document.getElementById('ancDateFilter').value;
    
    if (!motherId) {
        document.getElementById('ancHistoryBody').innerHTML = '<tr><td colspan="7" class="no-data">Select a mother to view ANC history</td></tr>';
        return;
    }
    
    let url = `ajax/get_anc_history.php?mother_id=${motherId}`;
    if (dateFilter) url += `&date=${dateFilter}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.length > 0) {
                data.forEach(record => {
                    html += `<tr>
                        <td>${record.checkup_date}</td>
                        <td>${record.bp}</td>
                        <td>${record.sugar}</td>
                        <td>${record.hemoglobin}</td>
                        <td>${record.weight} kg</td>
                        <td><span class="risk-badge ${record.risk_level.toLowerCase()}">${record.risk_level}</span></td>
                        <td>${record.notes || ''}</td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="7" class="no-data">No ANC records found</td></tr>';
            }
            document.getElementById('ancHistoryBody').innerHTML = html;
        });
}

function exportReport() {
    // Generate and download report
    alert('Report exported successfully!');
}

function sendNotifications() {
    // Send bulk notifications
    alert('Notifications sent to all upcoming ANC appointments');
}

function refreshDashboard() {
    loadDashboardData();
    alert('Dashboard data refreshed!');
}

// ANC History Functions
function loadANCHistory() {
    const motherId = document.getElementById('selectMotherANC').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    // Show loading
    document.getElementById('ancLoading').style.display = 'block';
    document.getElementById('ancNoData').style.display = 'none';
    document.getElementById('ancHistoryBody').innerHTML = '';
    
    // Build query string
    let query = `ajax/get_anc_history.php?`;
    const params = [];
    
    if (motherId) params.push(`mother_id=${motherId}`);
    if (dateFrom) params.push(`date_from=${dateFrom}`);
    if (dateTo) params.push(`date_to=${dateTo}`);
    
    const url = query + params.join('&');
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('ancLoading').style.display = 'none';
            
            if (data.success && data.data.length > 0) {
                renderANCTable(data.data);
            } else {
                document.getElementById('ancNoData').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading ANC history:', error);
            document.getElementById('ancLoading').style.display = 'none';
            document.getElementById('ancNoData').style.display = 'block';
            document.getElementById('ancNoData').innerHTML = '<p class="error">❌ Error loading ANC history. Please try again.</p>';
        });
}

function renderANCTable(records) {
    const tbody = document.getElementById('ancHistoryBody');
    let html = '';
    
    records.forEach(record => {
        // Determine row class based on status
        let rowClass = '';
        if (record.visit_status === 'urgent') rowClass = 'urgent-row';
        if (record.visit_status === 'overdue') rowClass = 'overdue-row';
        
        html += `
        <tr class="${rowClass}">
            <td>ANC${String(record.id).padStart(4, '0')}</td>
            <td>
                <strong>${record.mother_name}</strong><br>
                <small>Age: ${record.age}, Mobile: ${record.mobile_number}</small>
            </td>
            <td>
                ${record.checkup_date_formatted}<br>
                <small>${record.visit_status_text}</small>
            </td>
            <td>${record.bp}</td>
            <td>${record.sugar}</td>
            <td>${record.hemoglobin}</td>
            <td>${record.weight} kg</td>
            <td>
                <span class="risk-badge ${record.risk_class}">
                    ${record.risk_level}
                </span>
            </td>
            <td>
                ${record.next_checkup_formatted}<br>
                <small>(${record.days_until_next} days)</small>
            </td>
            <td>
                <div class="notes-preview">
                    ${record.notes ? record.notes.substring(0, 50) + '...' : 'No notes'}
                </div>
            </td>
            <td>
                <button class="action-btn view" onclick="viewANCDetails(${record.id})" title="View Details">
                    👁️
                </button>
                <button class="action-btn edit" onclick="editANCRecord(${record.id})" title="Edit">
                    ✏️
                </button>
                <button class="action-btn delete" onclick="deleteANCRecord(${record.id})" title="Delete">
                    🗑️
                </button>
            </td>
        </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Handle ANC form submission
document.getElementById('addANCForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveANCRecord();
});

function saveANCRecord() {
    const formData = new FormData();
    formData.append('mother_id', document.getElementById('ancMotherId').value);
    formData.append('checkup_date', document.getElementById('ancCheckupDate').value);
    formData.append('bp', document.getElementById('ancBP').value);
    formData.append('sugar', document.getElementById('ancSugar').value);
    formData.append('hemoglobin', document.getElementById('ancHb').value);
    formData.append('weight', document.getElementById('ancWeight').value);
    formData.append('risk_level', document.getElementById('ancRiskLevel').value);
    formData.append('next_checkup_date', document.getElementById('ancNextDate').value);
    formData.append('notes', document.getElementById('ancNotes').value);
    
    // Show loading
    const saveBtn = document.querySelector('#addANCForm button[type="submit"]');
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML = '⏳ Saving...';
    saveBtn.disabled = true;
    
    fetch('ajax/save_anc_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ANC record saved successfully!');
            clearANCForm();
            loadANCHistory(); // Refresh the table
            
            // Update dashboard counts
            refreshDashboard();
        } else {
            alert('❌ Error: ' + (data.error || 'Failed to save record'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error. Please check your connection.');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function clearANCForm() {
    document.getElementById('ancMotherId').value = '';
    document.getElementById('ancCheckupDate').value = '<?php echo date("Y-m-d"); ?>';
    document.getElementById('ancBP').value = '';
    document.getElementById('ancSugar').value = '';
    document.getElementById('ancHb').value = '';
    document.getElementById('ancWeight').value = '';
    document.getElementById('ancRiskLevel').value = 'Low';
    document.getElementById('ancNextDate').value = '<?php echo date("Y-m-d", strtotime("+30 days")); ?>';
    document.getElementById('ancNotes').value = '';
}

function resetANCFilter() {
    document.getElementById('selectMotherANC').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    loadANCHistory();
}

function deleteANCRecord(recordId) {
    if (confirm('Are you sure you want to delete this ANC record? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('record_id', recordId);
        
        fetch('ajax/delete_anc_record.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ANC record deleted successfully!');
                loadANCHistory(); // Refresh the table
            } else {
                alert('❌ Error: ' + (data.error || 'Failed to delete record'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Network error. Please try again.');
        });
    }
}

function viewANCDetails(recordId) {
    // You can implement a modal or separate page to view details
    alert(`Viewing details for ANC record #${recordId}\n\nThis would show complete details in a modal.`);
}

function editANCRecord(recordId) {
    // You can implement edit functionality
    alert(`Edit ANC record #${recordId}\n\nThis would load the record data into the form for editing.`);
}

// Initialize ANC History when section is shown
document.addEventListener('DOMContentLoaded', function() {
    const ancSection = document.getElementById('ancHistory');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (!ancSection.classList.contains('hidden')) {
                // Load ANC history when section becomes visible
                loadANCHistory();
            }
        });
    });
    
    observer.observe(ancSection, { attributes: true, attributeFilter: ['class'] });
    
    // Also load on page load if ANC section is active
    if (!ancSection.classList.contains('hidden')) {
        loadANCHistory();
    }
});

// Mother Management Functions
let currentMotherId = null;

function confirmDeleteMother(motherId) {
    currentMotherId = motherId;
    
    // Fetch mother details to show in confirmation
    fetch(`ajax/get_mother_details.php?id=${motherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const detailsDiv = document.getElementById('deleteMotherDetails');
                detailsDiv.innerHTML = `
                    <div class="detail-item">
                        <strong>Name:</strong> ${data.mother_name}
                    </div>
                    <div class="detail-item">
                        <strong>Age:</strong> ${data.age} years
                    </div>
                    <div class="detail-item">
                        <strong>Mobile:</strong> ${data.mobile_number}
                    </div>
                    <div class="detail-item">
                        <strong>Risk Level:</strong> <span class="risk-badge ${data.overall_risk.toLowerCase()}">
                            ${data.overall_risk}
                        </span>
                    </div>
                `;
                
                // Show modal
                openModal('deleteModal');
            } else {
                alert('Error loading mother details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
}

// Handle delete confirmation
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!currentMotherId) return;
    
    deleteMother(currentMotherId);
});

function deleteMother(motherId) {
    const formData = new FormData();
    formData.append('mother_id', motherId);
    
    // Show loading
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = deleteBtn.textContent;
    deleteBtn.innerHTML = '⏳ Deleting...';
    deleteBtn.disabled = true;
    
    fetch('ajax/delete_mother.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`tr[data-id="${motherId}"]`);
            if (row) {
                row.remove();
            }
            
            // Show success message
            alert(`✅ ${data.message}`);
            
            // Close modal
            closeModal('deleteModal');
            
            // Check if table is empty
            const tableBody = document.getElementById('motherTableBody');
            if (tableBody.children.length === 1 && tableBody.children[0].querySelector('.no-data')) {
                // Show empty state
                const emptyRow = tableBody.querySelector('.no-data');
                emptyRow.style.display = '';
            }
            
            // Refresh ANC schedule if needed
            if (document.getElementById('ancSchedule').classList.contains('active')) {
                loadANCSchedule();
            }
            
        } else {
            alert(`❌ Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error. Please try again.');
    })
    .finally(() => {
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
        currentMotherId = null;
    });
}

function deactivateMother(motherId) {
    if (!confirm('Are you sure you want to deactivate this mother? She will no longer appear in active lists.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('mother_id', motherId);
    formData.append('action', 'deactivate');
    
    updateMotherStatus(formData);
}

function activateMother(motherId) {
    const formData = new FormData();
    formData.append('mother_id', motherId);
    formData.append('action', 'activate');
    
    updateMotherStatus(formData);
}

function updateMotherStatus(formData) {
    fetch('ajax/update_mother_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✅ ${data.message}`);
            // Refresh the page or update specific row
            location.reload();
        } else {
            alert(`❌ Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error. Please try again.');
    });
}

// Filter functions
function searchMothers() {
    const searchTerm = document.getElementById('searchMother').value.toLowerCase();
    const rows = document.querySelectorAll('#motherTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.classList.contains('no-data')) return;
        
        const text = row.textContent.toLowerCase();
        const name = row.querySelector('.mother-name strong').textContent.toLowerCase();
        const nid = row.querySelector('.nid').textContent.toLowerCase();
        
        if (text.includes(searchTerm) || name.includes(searchTerm) || nid.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide empty state
    const noDataRow = document.querySelector('#motherTableBody .no-data');
    if (noDataRow) {
        noDataRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function filterMothers() {
    const riskFilter = document.getElementById('filterRisk').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#motherTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.classList.contains('no-data')) return;
        
        const risk = row.getAttribute('data-risk');
        const status = row.getAttribute('data-status');
        
        let show = true;
        
        // Apply risk filter
        if (riskFilter && risk !== riskFilter) {
            show = false;
        }
        
        // Apply status filter
        if (statusFilter) {
            if (statusFilter === 'active' && status !== 'active') show = false;
            if (statusFilter === 'inactive' && status !== 'inactive') show = false;
        }
        
        if (show) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide empty state
    const noDataRow = document.querySelector('#motherTableBody .no-data');
    if (noDataRow) {
        noDataRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

// ANC Schedule functions
function filterANCSchedule() {
    const filterValue = document.getElementById('scheduleFilter').value;
    const cards = document.querySelectorAll('.schedule-card');
    const today = new Date();
    
    cards.forEach(card => {
        const urgency = card.getAttribute('data-urgency');
        let show = true;
        
        switch (filterValue) {
            case 'today':
                show = urgency === 'today';
                break;
            case 'tomorrow':
                show = urgency === 'urgent'; // Assuming tomorrow is urgent
                break;
            case 'week':
                show = urgency === 'today' || urgency === 'urgent' || urgency === 'soon';
                break;
            case 'overdue':
                show = urgency === 'overdue';
                break;
            default:
                show = true;
        }
        
        card.style.display = show ? 'flex' : 'none';
    });
}

function loadANCSchedule() {
    // This would reload schedule data via AJAX
    fetch('ajax/get_anc_schedule.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderANCSchedule(data.schedule);
            }
        })
        .catch(error => console.error('Error loading schedule:', error));
}

function notifyMother(motherId) {
    // Get mother details
    fetch(`ajax/get_mother_details.php?id=${motherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = `Dear ${data.mother_name}, this is a reminder for your ANC appointment. Please visit the clinic on your scheduled date.`;
                
                // In a real app, you would send SMS/email here
                // For demo, we'll show a prompt
                const phoneNumber = prompt('Enter phone number to send SMS:', data.mobile_number);
                if (phoneNumber) {
                    alert(`📱 SMS notification sent to ${phoneNumber}\n\nMessage: ${message}`);
                    
                    // Log the notification
                    logNotification(motherId, 'sms', message);
                }
            }
        });
}

function rescheduleANC(motherId) {
    const newDate = prompt('Enter new ANC date (YYYY-MM-DD):');
    if (newDate) {
        const formData = new FormData();
        formData.append('mother_id', motherId);
        formData.append('action', 'reschedule_anc');
        formData.append('new_date', newDate);
        
        fetch('ajax/update_mother_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ANC appointment rescheduled successfully');
                // Refresh schedule
                loadANCSchedule();
                // Also refresh mother list
                location.reload();
            } else {
                alert(`❌ Error: ${data.error}`);
            }
        });
    }
}

function markAsVisited(motherId) {
    if (confirm('Mark this ANC appointment as completed?')) {
        // Update next ANC date to today and schedule next one
        const nextDate = prompt('Enter next ANC date (YYYY-MM-DD):', 
                              new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
        
        if (nextDate) {
            // In a real app, you would:
            // 1. Update current ANC record
            // 2. Create a new ANC record for today's visit
            // 3. Update next appointment date
            
            alert('✅ Marked as visited and next appointment scheduled');
            loadANCSchedule();
        }
    }
}

function exportANCSchedule() {
    // Create CSV data
    const headers = ['Mother Name', 'Mobile', 'Next Visit', 'Days Remaining', 'Risk Level'];
    const rows = [];
    
    document.querySelectorAll('.schedule-card').forEach(card => {
        if (card.style.display !== 'none') {
            const name = card.querySelector('h4').textContent;
            const mobile = card.querySelector('.info-item:nth-child(4) .value').textContent;
            const nextVisit = card.querySelector('.info-item:nth-child(1) .value').textContent;
            const daysRemaining = card.querySelector('.urgency-badge').textContent;
            const risk = card.querySelector('.risk-badge').textContent;
            
            rows.push([name, mobile, nextVisit, daysRemaining, risk]);
        }
    });
    
    // Convert to CSV
    let csvContent = headers.join(',') + '\n';
    rows.forEach(row => {
        csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    // Download
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ANC_Schedule_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    
    alert('✅ Schedule exported successfully!');
}

// Add New Mother
function addNewMother() {
    // Navigate to registration section
    navigateTo('register');
}

function addANCForMother(motherId) {
    // Navigate to ANC History and pre-select the mother
    navigateTo('ancHistory');
    setTimeout(() => {
        document.getElementById('selectMotherANC').value = motherId;
        loadANCHistory();
        // Scroll to add form
        document.getElementById('addANCForm').scrollIntoView({ behavior: 'smooth' });
    }, 500);
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentMotherId = null;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
};

// Log notification (for demo)
function logNotification(motherId, type, message) {
    const logs = JSON.parse(localStorage.getItem('notification_logs') || '[]');
    logs.push({
        mother_id: motherId,
        type: type,
        message: message,
        timestamp: new Date().toISOString()
    });
    localStorage.setItem('notification_logs', JSON.stringify(logs));
}

// Mother Profile Functions
// Mother Profile Functions
let currentProfileMotherId = null;

function viewMotherProfile(motherId) {
    // Navigate to mother profile section
    navigateTo('motherProfile');
    
    // Load the profile after a short delay
    setTimeout(() => {
        loadMotherProfile(motherId);
    }, 100);
}

function loadMotherProfile(motherId) {
    if (!motherId) {
        // Show default state
        document.getElementById('noProfile').style.display = 'block';
        document.getElementById('profileLoading').style.display = 'none';
        document.getElementById('profileContent').style.display = 'none';
        return;
    }
    
    currentProfileMotherId = motherId;
    
    // Show loading, hide other states
    document.getElementById('noProfile').style.display = 'none';
    document.getElementById('profileLoading').style.display = 'block';
    document.getElementById('profileContent').style.display = 'none';
    
    // Fetch mother profile
    fetch(`ajax/get_simple_profile.php?id=${motherId}`)
        .then(response => response.json())
        .then(data => {
            // Hide loading
            document.getElementById('profileLoading').style.display = 'none';
            
            if (data.success) {
                // Render the profile
                renderMotherProfile(data);
            } else {
                // Show error
                document.getElementById('profileContent').innerHTML = `
                    <div class="error-message">
                        ❌ ${data.error || 'Error loading profile'}
                        <button class="btn-secondary" onclick="navigateTo('motherManagement')">
                            Go Back
                        </button>
                    </div>
                `;
                document.getElementById('profileContent').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading profile:', error);
            document.getElementById('profileLoading').style.display = 'none';
            document.getElementById('profileContent').innerHTML = `
                <div class="error-message">
                    ❌ Network error. Please try again.
                    <button class="btn-secondary" onclick="navigateTo('motherManagement')">
                        Go Back
                    </button>
                </div>
            `;
            document.getElementById('profileContent').style.display = 'block';
        });
}

function renderMotherProfile(data) {
    const mother = data.mother;
    const latestANC = data.latest_anc;
    const ancHistory = data.anc_history;
    const ancCount = data.anc_count;
    
    // Calculate pregnancy progress
    const progressPercent = Math.min(100, Math.round((mother.pregnancy_weeks / 40) * 100));
    
    // Determine risk color
    const riskColor = mother.overall_risk === 'High' ? 'danger' : 
                     mother.overall_risk === 'Medium' ? 'warning' : 'success';
    
    // Determine status
    const status = mother.is_active ? 'Active' : 'Inactive';
    const statusColor = mother.is_active ? 'success' : 'secondary';
    
    // Format dates
    const regDate = mother.registration_date ? new Date(mother.registration_date).toLocaleDateString() : 'N/A';
    const deliveryDate = mother.delivery_date ? new Date(mother.delivery_date).toLocaleDateString() : 'Not set';
    
    // Create HTML
    let html = `
    <div class="profile-view">
        <!-- Basic Info Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    👩
                </div>
                <div class="profile-info">
                    <h2>${mother.mother_name}</h2>
                    <p class="profile-meta">
                        ID: M${String(mother.id).padStart(4, '0')} | 
                        Age: ${mother.age} years | 
                        Blood Group: ${mother.blood_group || 'Not specified'}
                    </p>
                </div>
            </div>
            
            <div class="profile-details">
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="label">Mobile:</span>
                        <span class="value">${mother.mobile_number}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">NID:</span>
                        <span class="value">${mother.nid_number || 'Not provided'}</span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="label">Address:</span>
                        <span class="value">${mother.address || 'Not provided'}</span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="label">Registered:</span>
                        <span class="value">${regDate}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Status:</span>
                        <span class="value status-badge ${statusColor}">${status}</span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="label">Risk Level:</span>
                        <span class="value risk-badge ${riskColor}">${mother.overall_risk}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">ANC Visits:</span>
                        <span class="value">${ancCount}</span>
                    </div>
                </div>
                
                ${mother.complication ? `
                <div class="warning-box">
                    <strong>⚠️ Complications:</strong> ${mother.complication}
                </div>
                ` : ''}
            </div>
        </div>
        
        <!-- Medical Parameters Card -->
        <div class="medical-card">
            <h3>📊 Latest Medical Parameters</h3>
            ${latestANC ? `
            <div class="medical-grid">
                <div class="medical-item">
                    <span class="label">Blood Pressure:</span>
                    <span class="value ${getBPClass(latestANC.bp)}">${latestANC.bp}</span>
                </div>
                <div class="medical-item">
                    <span class="label">Sugar Level:</span>
                    <span class="value ${getSugarClass(latestANC.sugar)}">${latestANC.sugar} mmol/L</span>
                </div>
                <div class="medical-item">
                    <span class="label">Hemoglobin:</span>
                    <span class="value ${getHemoClass(latestANC.hemoglobin)}">${latestANC.hemoglobin} g/dL</span>
                </div>
                <div class="medical-item">
                    <span class="label">Weight:</span>
                    <span class="value">${latestANC.weight} kg</span>
                </div>
                <div class="medical-item">
                    <span class="label">Last Checkup:</span>
                    <span class="value">${latestANC.checkup_date ? new Date(latestANC.checkup_date).toLocaleDateString() : 'N/A'}</span>
                </div>
                <div class="medical-item">
                    <span class="label">Next Checkup:</span>
                    <span class="value">${latestANC.next_checkup_date ? new Date(latestANC.next_checkup_date).toLocaleDateString() : 'Not set'}</span>
                </div>
            </div>
            ` : `
            <div class="no-data">
                <p>No ANC records found</p>
            </div>
            `}
        </div>
        
        <!-- Pregnancy Progress -->
        <div class="progress-card">
            <h3>🤰 Pregnancy Progress</h3>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${progressPercent}%"></div>
            </div>
            <div class="progress-info">
                <span>Week ${mother.pregnancy_weeks || 0} of 40</span>
                <span>${progressPercent}% complete</span>
            </div>
            <div class="delivery-info">
                <span class="label">Expected Delivery:</span>
                <span class="value">${deliveryDate}</span>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-card">
            <h3>⚡ Quick Actions</h3>
            <div class="action-grid">
                <button class="action-btn primary" onclick="openUpdateProfileModal(${mother.id})">
                    ✏️ Update Profile
                </button>
                <button class="action-btn success" onclick="openAddANCModal(${mother.id})">
                    📋 Add ANC Record
                </button>
                ${mother.is_active ? `
                <button class="action-btn warning" onclick="openMarkDeliveryModal(${mother.id})">
                    👶 Mark as Delivered
                </button>
                ` : ''}
                <button class="action-btn info" onclick="printProfile(${mother.id})">
                    🖨️ Print Profile
                </button>
                <button class="action-btn secondary" onclick="sendReminder(${mother.id})">
                    📱 Send Reminder
                </button>
            </div>
        </div>
        
        <!-- ANC History -->
        ${ancHistory.length > 0 ? `
        <div class="history-card">
            <h3>📋 Recent ANC History</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>BP</th>
                            <th>Sugar</th>
                            <th>Hb</th>
                            <th>Weight</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${ancHistory.map(anc => `
                        <tr>
                            <td>${anc.checkup_date_formatted}</td>
                            <td class="${getBPClass(anc.bp)}">${anc.bp}</td>
                            <td class="${getSugarClass(anc.sugar)}">${anc.sugar}</td>
                            <td class="${getHemoClass(anc.hemoglobin)}">${anc.hemoglobin}</td>
                            <td>${anc.weight} kg</td>
                            <td>
                                <span class="risk-badge ${anc.risk_level.toLowerCase()}">
                                    ${anc.risk_level}
                                </span>
                            </td>
                        </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            ${ancCount > 5 ? `
            <div class="view-all">
                <button class="btn-secondary" onclick="viewAllANCRecords(${mother.id})">
                    View All ${ancCount} Records
                </button>
            </div>
            ` : ''}
        </div>
        ` : ''}
    </div>
    `;
    
    // Update the content
    document.getElementById('profileContent').innerHTML = html;
    document.getElementById('profileContent').style.display = 'block';
}

// Helper functions for medical parameter classes
function getBPClass(bp) {
    if (!bp) return '';
    const parts = bp.split('/');
    const systolic = parseInt(parts[0]) || 120;
    if (systolic > 140) return 'danger';
    if (systolic > 130) return 'warning';
    return 'normal';
}

function getSugarClass(sugar) {
    if (!sugar) return '';
    if (sugar > 7.0) return 'danger';
    if (sugar > 5.6) return 'warning';
    return 'normal';
}

function getHemoClass(hemoglobin) {
    if (!hemoglobin) return '';
    if (hemoglobin < 10.0) return 'danger';
    if (hemoglobin < 11.0) return 'warning';
    return 'normal';
}

// Simple modal functions (to be implemented)
function openUpdateProfileModal(motherId) {
    alert(`Update profile for mother ID: ${motherId}\n\nThis would open a modal to edit profile.`);
}

function openAddANCModal(motherId) {
    alert(`Add ANC for mother ID: ${motherId}\n\nThis would open a modal to add ANC record.`);
}

function openMarkDeliveryModal(motherId) {
    alert(`Mark delivery for mother ID: ${motherId}\n\nThis would open a modal to mark delivery.`);
}

function printProfile(motherId) {
    window.print();
}

function sendReminder(motherId) {
    alert(`Sending reminder to mother ID: ${motherId}`);
}

function viewAllANCRecords(motherId) {
    // Navigate to ANC History with this mother selected
    navigateTo('ancHistory');
    setTimeout(() => {
        document.getElementById('selectMotherANC').value = motherId;
        loadANCHistory();
    }, 500);
}


// Login Form Handling (for login.html)
if (document.getElementById('loginForm')) {
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        // Show loading
        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Logging in...';
        submitBtn.disabled = true;
        
        // Send login request
        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.text())
        .then(data => {
            console.log('Login response:', data);
            
            if (data.trim() === 'success') {
                // Login successful, redirect to index.php
                window.location.href = 'index.php';
            } else {
                // Show error
                alert('Login failed: ' + data);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            alert('Network error. Please try again.');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });
}

// Predict Emergencies Functions
function viewEmergencies() {
    navigateTo('predictEmergencies');
    loadEmergencies();
}

function loadEmergencies() {
    // Show loading state
    const container = document.getElementById('emergencyCardsContainer');
    container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Loading emergency cases...</p></div>';
    
    fetch('ajax/get_emergencies.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.emergencies.length > 0) {
                renderEmergencyCards(data.emergencies);
            } else {
                renderNoEmergencies();
            }
        })
        .catch(error => {
            console.error('Error loading emergencies:', error);
            container.innerHTML = '<div class="error-message">❌ Error loading emergency cases. Please try again.</div>';
        });
}

function renderEmergencyCards(emergencies) {
    const container = document.getElementById('emergencyCardsContainer');
    let html = '';
    
    emergencies.forEach(emergency => {
        const urgencyLevel = emergency.urgency_level || 'medium';
        const emergencyTypes = emergency.emergency_types || [];
        const urgencyText = emergencyTypes.join(', ');
        
        html += `
        <div class="emergency-card" data-urgency="${urgencyLevel}">
            <div class="emergency-card-header">
                <div class="emergency-title">
                    <h4>${escapeHtml(emergency.mother_name)}</h4>
                    <span class="emergency-id">M${String(emergency.id).padStart(4, '0')}</span>
                </div>
                <span class="emergency-badge ${urgencyLevel}">
                    ${urgencyLevel.toUpperCase()} PRIORITY
                </span>
            </div>
            
            <div class="emergency-card-body">
                <div class="emergency-info">
                    <div class="info-row">
                        <span class="info-label">📱 Mobile:</span>
                        <span class="info-value">${emergency.mobile_number}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📍 Address:</span>
                        <span class="info-value">${emergency.address ? escapeHtml(emergency.address.substring(0, 50)) + '...' : 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">⚠️ Emergency Type:</span>
                        <span class="info-value emergency-types">${urgencyText}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📊 Latest Readings:</span>
                        <span class="info-value readings">
                            BP: ${emergency.bp || 'N/A'} | 
                            Sugar: ${emergency.sugar || 'N/A'} | 
                            Hb: ${emergency.hemoglobin || 'N/A'}
                        </span>
                    </div>
                </div>
                
                <div class="emergency-actions">
                    <button class="action-btn view" onclick="viewEmergencyProfile(${emergency.id})">
                        👁️ View Details
                    </button>
                    <button class="action-btn notify" onclick="sendEmergencyAlert(${emergency.id})">
                        📱 Send Alert
                    </button>
                    <button class="action-btn call" onclick="callMother('${emergency.mobile_number}')">
                        📞 Call Now
                    </button>
                    <button class="action-btn resolve" onclick="markEmergencyResolved(${emergency.id})">
                        ✅ Mark Resolved
                    </button>
                </div>
            </div>
        </div>
        `;
    });
    
    container.innerHTML = html;
}

function renderNoEmergencies() {
    const container = document.getElementById('emergencyCardsContainer');
    container.innerHTML = `
        <div class="no-emergencies">
            <div class="empty-state">
                <span>🎉 No Emergency Cases Found</span>
                <p>All mothers are currently stable. Check back regularly for updates.</p>
            </div>
        </div>
    `;
}

function viewEmergencyProfile(motherId) {
    viewMotherProfile(motherId);
}

function sendEmergencyAlert(motherId) {
    // Get mother details
    fetch(`ajax/get_mother_details.php?id=${motherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = `🚨 EMERGENCY ALERT: Dear ${data.mother_name}, please visit the hospital immediately. Your condition requires urgent attention. Contact: Hospital Emergency - 999`;
                const phoneNumber = data.mobile_number;
                
                if (confirm(`Send emergency alert to ${data.mother_name} (${phoneNumber})?`)) {
                    // In real app, integrate with SMS gateway
                    // For demo, we'll show a success message
                    alert(`📢 EMERGENCY ALERT SENT!\n\nTo: ${data.mother_name}\nPhone: ${phoneNumber}\n\nMessage:\n${message}`);
                    
                    // Log the alert
                    logEmergencyAlert(motherId, message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending alert. Please try again.');
        });
}

function callMother(phoneNumber) {
    if (confirm(`Call ${phoneNumber}?`)) {
        // In a real app, this would initiate a phone call
        // For demo, we'll simulate it
        alert(`📞 Calling ${phoneNumber}...\n\nNote: In a real application, this would initiate a phone call.`);
        
        // You can also use tel: link for mobile devices
        // window.location.href = `tel:${phoneNumber}`;
    }
}

function markEmergencyResolved(motherId) {
    if (confirm('Mark this emergency case as resolved?')) {
        const notes = prompt('Add resolution notes (optional):');
        
        fetch('ajax/mark_emergency_resolved.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `mother_id=${motherId}&notes=${encodeURIComponent(notes || '')}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Emergency case marked as resolved');
                loadEmergencies(); // Refresh the list
                loadDashboardData(); // Update dashboard counts
            } else {
                alert('❌ Error: ' + (data.error || 'Failed to update'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }
}

function sendBulkAlerts() {
    const selected = getSelectedRecipients();
    if (selected.length === 0) {
        alert('Please select at least one recipient from the list below.');
        return;
    }
    
    const message = prompt('Enter the message to send to all selected recipients:');
    if (!message || message.trim() === '') {
        alert('Message cannot be empty');
        return;
    }
    
    if (confirm(`Send this message to ${selected.length} mother(s)?\n\n${message}`)) {
        // Simulate sending
        alert(`📢 Bulk message sent to ${selected.length} recipients!\n\nIn a real application, this would send SMS to all selected mothers.`);
        
        // Log the bulk message
        logBulkAlert(selected.length, message);
    }
}

function exportEmergencyList() {
    // Create CSV data
    const headers = ['ID', 'Name', 'Mobile', 'Emergency Type', 'Priority', 'BP', 'Sugar', 'Hb', 'Delivery Date'];
    const rows = [];
    
    document.querySelectorAll('.emergency-card').forEach(card => {
        const id = card.querySelector('.emergency-id').textContent;
        const name = card.querySelector('h4').textContent;
        const mobile = card.querySelector('.info-row:nth-child(1) .info-value').textContent;
        const emergencyType = card.querySelector('.emergency-types').textContent;
        const priority = card.querySelector('.emergency-badge').textContent;
        const readings = card.querySelector('.readings').textContent;
        
        // Extract readings
        const bp = readings.match(/BP:\s*([^|]+)/)?.[1]?.trim() || 'N/A';
        const sugar = readings.match(/Sugar:\s*([^|]+)/)?.[1]?.trim() || 'N/A';
        const hb = readings.match(/Hb:\s*([^|]+)/)?.[1]?.trim() || 'N/A';
        
        rows.push([id, name, mobile, emergencyType, priority, bp, sugar, hb]);
    });
    
    // Convert to CSV
    let csvContent = headers.join(',') + '\n';
    rows.forEach(row => {
        csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    // Download
    downloadCSV(csvContent, `Emergency_List_${new Date().toISOString().split('T')[0]}.csv`);
    alert('✅ Emergency list exported successfully!');
}

function useTemplate(templateType) {
    const templates = {
        'urgent': '🚨 EMERGENCY ALERT: Please visit the hospital immediately for urgent checkup. Your condition requires immediate attention.',
        'delivery_soon': '📅 DELIVERY REMINDER: Your delivery is scheduled soon. Please prepare for hospital admission and contact us for assistance.',
        'followup': '🩺 FOLLOW-UP REQUIRED: Your recent tests show abnormal results. Please schedule a follow-up appointment immediately.',
        'medication': '💊 MEDICATION REMINDER: Ensure you\'re taking prescribed medications regularly. Contact us for any side effects.'
    };
    
    document.getElementById('customMessage').value = templates[templateType] || '';
    updateCharCount();
}

function selectAllRecipients() {
    const select = document.getElementById('emergencyRecipients');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = true;
    }
}

function clearRecipients() {
    const select = document.getElementById('emergencyRecipients');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = false;
    }
}

function getSelectedRecipients() {
    const select = document.getElementById('emergencyRecipients');
    const selected = [];
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].selected) {
            selected.push({
                id: select.options[i].value,
                name: select.options[i].text
            });
        }
    }
    return selected;
}

function updateCharCount() {
    const message = document.getElementById('customMessage').value;
    document.getElementById('charCount').textContent = message.length;
    
    // Color code based on length
    const charCount = document.getElementById('charCount');
    if (message.length > 160) {
        charCount.style.color = '#dc3545';
    } else if (message.length > 140) {
        charCount.style.color = '#fd7e14';
    } else {
        charCount.style.color = '#28a745';
    }
}

function testMessage() {
    const message = document.getElementById('customMessage').value;
    if (!message.trim()) {
        alert('Please enter a message first');
        return;
    }
    
    alert(`📝 MESSAGE PREVIEW:\n\n${message}\n\nLength: ${message.length} characters\nSMS Count: ${Math.ceil(message.length / 160)}`);
}

function sendCustomMessage() {
    const selected = getSelectedRecipients();
    const message = document.getElementById('customMessage').value.trim();
    const sendSMS = document.getElementById('sendSMS').checked;
    const sendCall = document.getElementById('sendCall').checked;
    const sendEmail = document.getElementById('sendEmail').checked;
    
    if (selected.length === 0) {
        alert('Please select at least one recipient');
        return;
    }
    
    if (!message) {
        alert('Please enter a message');
        return;
    }
    
    if (!sendSMS && !sendCall && !sendEmail) {
        alert('Please select at least one delivery method');
        return;
    }
    
    const methods = [];
    if (sendSMS) methods.push('SMS');
    if (sendCall) methods.push('Voice Call');
    if (sendEmail) methods.push('Email');
    
    const confirmMessage = `Send message via ${methods.join(', ')} to ${selected.length} recipient(s)?\n\nRecipients:\n${selected.map(r => r.name).join('\n')}\n\nMessage (${message.length} chars):\n${message}`;
    
    if (confirm(confirmMessage)) {
        // Simulate sending
        const sentMethods = [];
        if (sendSMS) sentMethods.push('📱 SMS sent');
        if (sendCall) sentMethods.push('📞 Voice call simulated');
        if (sendEmail) sentMethods.push('✉️ Email sent');
        
        alert(`✅ MESSAGE SENT!\n\n${sentMethods.join('\n')}\n\nTo: ${selected.length} recipient(s)`);
        
        // Clear form
        document.getElementById('customMessage').value = '';
        updateCharCount();
    }
}

function downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function logEmergencyAlert(motherId, message) {
    const logs = JSON.parse(localStorage.getItem('emergency_logs') || '[]');
    logs.push({
        mother_id: motherId,
        type: 'emergency_alert',
        message: message,
        timestamp: new Date().toISOString(),
        status: 'sent'
    });
    localStorage.setItem('emergency_logs', JSON.stringify(logs));
}

function logBulkAlert(recipientCount, message) {
    const logs = JSON.parse(localStorage.getItem('emergency_logs') || '[]');
    logs.push({
        type: 'bulk_alert',
        recipient_count: recipientCount,
        message: message,
        timestamp: new Date().toISOString(),
        status: 'sent'
    });
    localStorage.setItem('emergency_logs', JSON.stringify(logs));
}

// Initialize character count on page load and when typing
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('customMessage');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
    }
    
    // Also add navigation for the new section
    const navButtons = document.querySelectorAll(".nav-btn");
    navButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            const targetId = this.getAttribute("data-target");
            if (targetId === 'predictEmergencies') {
                loadEmergencies();
            }
        });
    });
});

// Open Update Profile Modal and load current data
function openUpdateProfileModal(motherId) {
    // Fetch current mother data
    fetch(`ajax/get_mother_details.php?id=${motherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('update_mother_id').value = motherId;
                document.getElementById('update_mother_name').value = data.mother_name || '';
                document.getElementById('update_age').value = data.age || '';
                document.getElementById('update_blood_group').value = data.blood_group || '';
                document.getElementById('update_mobile').value = data.mobile_number || '';
                document.getElementById('update_nid').value = data.nid_number || '';
                document.getElementById('update_address').value = data.address || '';
                document.getElementById('update_delivery_date').value = data.delivery_date || '';
                document.getElementById('update_weeks').value = data.pregnancy_weeks || '';
                document.getElementById('update_complication').value = data.complication || '';
                document.getElementById('update_risk').value = data.overall_risk || 'Low';
                document.getElementById('update_is_active').checked = data.is_active == 1;
                openModal('updateProfileModal');
            } else {
                alert('Error loading mother data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load mother data');
        });
}

// Submit update profile form
function submitUpdateProfile() {
    const form = document.getElementById('updateProfileForm');
    const formData = new FormData(form);
    
    // Show loading
    const btn = document.querySelector('#updateProfileModal .btn-primary');
    const originalText = btn.textContent;
    btn.innerHTML = '⏳ Updating...';
    btn.disabled = true;
    
    fetch('ajax/update_mother_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Profile updated successfully');
            closeModal('updateProfileModal');
            // Reload the profile view
            loadMotherProfile(currentProfileMotherId);
        } else {
            alert('❌ Error: ' + (data.error || 'Update failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error. Please try again.');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Open Add ANC Modal
function openAddANCModal(motherId) {
    document.getElementById('anc_mother_id').value = motherId;
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('anc_checkup_date').value = today;
    const nextMonth = new Date();
    nextMonth.setDate(nextMonth.getDate() + 30);
    document.getElementById('anc_next_date').value = nextMonth.toISOString().split('T')[0];
    // Clear other fields
    document.getElementById('anc_bp').value = '';
    document.getElementById('anc_sugar').value = '';
    document.getElementById('anc_hemoglobin').value = '';
    document.getElementById('anc_weight').value = '';
    document.getElementById('anc_risk').value = 'auto';
    document.getElementById('anc_notes').value = '';
    openModal('addANCModal');
}

// Submit Add ANC form
function submitAddANC() {
    const form = document.getElementById('addANCFormModal');
    const formData = new FormData(form);
    
    const btn = document.querySelector('#addANCModal .btn-primary');
    const originalText = btn.textContent;
    btn.innerHTML = '⏳ Saving...';
    btn.disabled = true;
    
    fetch('ajax/save_anc_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ANC record saved successfully');
            closeModal('addANCModal');
            // Refresh profile to show new ANC
            loadMotherProfile(currentProfileMotherId);
            // Also refresh ANC history in ANC section if open
            if (!document.getElementById('ancHistory').classList.contains('hidden')) {
                loadANCHistory();
            }
        } else {
            alert('❌ Error: ' + (data.error || 'Save failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error. Please try again.');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Open Mark Delivery Modal
function openMarkDeliveryModal(motherId) {
    document.getElementById('delivery_mother_id').value = motherId;
    // Set default delivery date to today
    document.getElementById('delivery_date').value = new Date().toISOString().split('T')[0];
    // Clear other fields
    document.getElementById('delivery_type').value = '';
    document.getElementById('baby_weight').value = '';
    document.getElementById('baby_gender').value = '';
    document.getElementById('baby_length').value = '';
    document.getElementById('apgar_score').value = '';
    document.getElementById('mother_condition').value = 'Good';
    document.getElementById('baby_condition').value = 'Healthy';
    document.getElementById('delivery_complications').value = '';
    document.getElementById('delivery_notes').value = '';
    openModal('markDeliveryModal');
}

// Submit Mark Delivery form
function submitMarkDelivery() {
    const form = document.getElementById('markDeliveryForm');
    const formData = new FormData(form);
    
    const btn = document.querySelector('#markDeliveryModal .btn-primary');
    const originalText = btn.textContent;
    btn.innerHTML = '⏳ Saving...';
    btn.disabled = true;
    
    fetch('ajax/mark_delivery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Delivery recorded successfully');
            closeModal('markDeliveryModal');
            // Reload profile (mother will become inactive)
            loadMotherProfile(currentProfileMotherId);
            // Refresh dashboard counts
            refreshDashboard();
        } else {
            alert('❌ Error: ' + (data.error || 'Save failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error. Please try again.');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Print Profile
function printProfile(motherId) {
    // Hide sidebar, topbar, etc. using a print stylesheet or just window.print()
    // For simplicity, just call print
    window.print();
    // You could also open a new window with printable version
}

// Open Reminder Modal
function sendReminder(motherId) {
    document.getElementById('reminder_mother_id').value = motherId;
    document.getElementById('reminder_message').value = '';
    openModal('reminderModal');
}

// Use template in reminder
function useReminderTemplate() {
    const template = document.getElementById('reminder_template').value;
    if (template) {
        document.getElementById('reminder_message').value = template;
    }
}

// Send reminder message
function sendReminderMessage() {
    const motherId = document.getElementById('reminder_mother_id').value;
    const message = document.getElementById('reminder_message').value.trim();
    const sendSMS = document.querySelector('input[name="method_sms"]').checked;
    const sendCall = document.querySelector('input[name="method_call"]').checked;
    
    if (!message) {
        alert('Please enter a message');
        return;
    }
    
    if (!sendSMS && !sendCall) {
        alert('Please select at least one delivery method');
        return;
    }
    
    // Simulate sending
    let methods = [];
    if (sendSMS) methods.push('SMS');
    if (sendCall) methods.push('Voice Call');
    
    alert(`📱 Reminder sent via ${methods.join(' and ')} to mother ID ${motherId}\n\nMessage: ${message}`);
    
    // Log the reminder (optional)
    logNotification(motherId, methods.join(','), message);
    
    closeModal('reminderModal');
}

// Make sure closeModal function exists (should already be there)
// If not, add:
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}