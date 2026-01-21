<?php
// index.php - Start session and check login
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Now include database connection
require_once 'db.php';

// Rest of your existing code...

// Get counts for dashboard
$highRiskCount = 0;
$ancAlertCount = 0;
$emergencyCount = 0;
$safePregnancyCount = 0;

// High Risk count
$highRiskQuery = "SELECT COUNT(*) as count FROM pregnancy_registration WHERE overall_risk = 'High' AND is_active = TRUE";
$result = $conn->query($highRiskQuery);
if ($result) $highRiskCount = $result->fetch_assoc()['count'];

// ANC Alert count (next visit within 8 days)
$ancAlertQuery = "SELECT COUNT(DISTINCT pr.id) as count 
                  FROM pregnancy_registration pr 
                  LEFT JOIN anc_checkup ac ON pr.id = ac.mother_id 
                  WHERE (ac.next_checkup_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 8 DAY) 
                  OR pr.next_anc_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 8 DAY))
                  AND pr.is_active = TRUE";
$result = $conn->query($ancAlertQuery);
if ($result) $ancAlertCount = $result->fetch_assoc()['count'];

// Emergency count (delivery within 7 days)
$emergencyQuery = "SELECT COUNT(*) as count 
                   FROM pregnancy_registration 
                   WHERE delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                   AND is_active = TRUE";
$result = $conn->query($emergencyQuery);
if ($result) $emergencyCount = $result->fetch_assoc()['count'];

// Safe Pregnancy count (Low + Medium risk)
$safePregnancyQuery = "SELECT COUNT(*) as count 
                       FROM pregnancy_registration 
                       WHERE overall_risk IN ('Low', 'Medium') 
                       AND is_active = TRUE";
$result = $conn->query($safePregnancyQuery);
if ($result) $safePregnancyCount = $result->fetch_assoc()['count'];

// Get recent alerts
$recentAlertsQuery = "SELECT pr.*, ac.bp, ac.sugar, ac.hemoglobin 
                      FROM pregnancy_registration pr 
                      LEFT JOIN anc_checkup ac ON pr.id = ac.mother_id 
                      WHERE pr.overall_risk = 'High' 
                      AND pr.is_active = TRUE 
                      ORDER BY ac.checkup_date DESC 
                      LIMIT 5";
$recentAlerts = $conn->query($recentAlertsQuery);
// ... rest of your existing code continues ...

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Maternity Risk & Care Tracking System</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2 class="logo">Smart Maternity System</h2>
            <div class="user-info">
                <span>👩‍⚕️ Welcome, <?php echo $_SESSION['username'] ?? 'Nurse'; ?></span>
            </div>
            <ul>
                <li class="nav-btn active" data-target="dashboard">📊 Dashboard</li>
                <li class="nav-btn" data-target="register">➕ Register New Pregnancy</li>
                <li class="nav-btn" data-target="motherManagement">👩 Mother Management</li>
                <li class="nav-btn" data-target="ancHistory">📋 ANC History</li>
                <li class="nav-btn" data-target="motherProfile">👤 Mother Profile</li>
                <li class="nav-btn" data-target="deliveryHistory">👶 Delivery History</li>
                <li><a href="logout.php" class="logout-btn">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <header class="topbar">
                <h1>Smart Maternity Risk & Care Tracking System
                    <small class="small-title">Preventing Risk Before It Becomes Emergency</small>
                </h1>
                <div class="header-actions">
                    <span id="currentDate"></span>
                    <button onclick="refreshDashboard()" class="refresh-btn">🔄 Refresh</button>
                </div>
            </header>

            <!-- Dashboard Section -->
            <div id="dashboard" class="section">
                <div class="dashboard-container">
                    <h2>📊 Dashboard Overview</h2>
                    
                    <!-- Stat Cards -->
                    <div class="cards">
                        <div class="card danger HRisk" onclick="viewHighRiskMothers()">
                            <h3>🚨 High Risk Mother</h3>
                            <h1 id="highRiskCount"><?php echo $highRiskCount; ?></h1>
                            <p>Active high-risk cases</p>
                        </div>

                        <div class="card anc_alerts" onclick="viewANCAlerts()">
                            <h3>⏰ ANC Notify Alerts</h3>
                            <h1 id="ancAlertCount"><?php echo $ancAlertCount; ?></h1>
                            <p>Next ANC within 8 days</p>
                        </div>

                        <div class="card danger predict-emergency" onclick="viewEmergencies()">
                            <h3>⚠️ Predict Emergencies</h3>
                            <h1 id="emergencyCount"><?php echo $emergencyCount; ?></h1>
                            <p>Delivery within 7 days</p>
                        </div>

                        <div class="card safe_preg" onclick="viewSafePregnancies()">
                            <h3>✅ Safe Pregnancies</h3>
                            <h1 id="safePregnancyCount"><?php echo $safePregnancyCount; ?></h1>
                            <p>Low & Medium risk cases</p>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-row">
                        <div class="panel">
                            <h3>🔔 Recent Alerts</h3>
                            <div class="alert-list">
                                <?php if ($recentAlerts && $recentAlerts->num_rows > 0): ?>
                                    <?php while($alert = $recentAlerts->fetch_assoc()): ?>
                                        <div class="alert-item">
                                            <?php
                                            $alerts = [];
                                            if ($alert['bp'] && strpos($alert['bp'], '150/') !== false) $alerts[] = "⚠️ High BP";
                                            if ($alert['sugar'] > 7.0) $alerts[] = "⚠️ High Sugar";
                                            if ($alert['hemoglobin'] < 11.0) $alerts[] = "⚠️ Low Hemoglobin";
                                            ?>
                                            <div class="alert-content">
                                                <strong>Mother ID: <?php echo $alert['id']; ?></strong> - 
                                                <?php echo $alert['mother_name']; ?>
                                                <?php if (!empty($alerts)): ?>
                                                    <br><small><?php echo implode(', ', $alerts); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <button class="view-btn" onclick="viewMotherProfile(<?php echo $alert['id']; ?>)">View</button>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="no-alerts">No recent alerts</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>⚡ Quick Actions</h3>
                            <button class="dash-btn" onclick="navigateTo('register')">➕ Register Pregnancy</button>
                            <button class="dash-btn" onclick="navigateTo('motherManagement')">👩 Mother List</button>
                            <button class="dash-btn" onclick="navigateTo('ancHistory')">📋 ANC History</button>
                            <button class="dash-btn" onclick="exportReport()">📊 Export Report</button>
                            <button class="dash-btn" onclick="sendNotifications()">📱 Send Notifications</button>
                        </div>
                    </div>

                    <!-- Risk Distribution Chart -->
                    <div class="panel full-width">
                        <h3>📈 Risk Distribution</h3>
                        <canvas id="riskChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Register New Pregnancy Section -->
            <div id="register" class="section hidden">
                <h2>➕ Register New Pregnancy</h2>
                <?php
                if (isset($_POST['save_pregnancy'])) {
                    $mother_name = mysqli_real_escape_string($conn, $_POST['mother_name']);
                    $age = (int)$_POST['age'];
                    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
                    $nid = mysqli_real_escape_string($conn, $_POST['nid']);
                    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
                    $address = mysqli_real_escape_string($conn, $_POST['address']);
                    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
                    $weight = (float)$_POST['weight'];
                    $sugar = (float)$_POST['sugar'];
                    $hemoglobin = (float)$_POST['hemoglobin'];
                    $complication = mysqli_real_escape_string($conn, $_POST['complication']);
                    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
                    
                    // Calculate risk
                    $overall_risk = calculateRisk($age, $bp, $sugar, $hemoglobin, $weight);
                    
                    // Calculate next ANC date (default 30 days from today)
                    $next_anc_date = date('Y-m-d', strtotime('+30 days'));
                    
                    $sql = "INSERT INTO pregnancy_registration 
                            (mother_name, age, blood_group, nid_number, mobile_number, address,
                             blood_pressure, weight, sugar_level, hemoglobin, complication,
                             overall_risk, delivery_date, next_anc_date, pregnancy_weeks)
                            VALUES 
                            ('$mother_name', '$age', '$blood_group', '$nid', '$mobile', '$address',
                             '$bp', '$weight', '$sugar', '$hemoglobin', '$complication',
                             '$overall_risk', '$delivery_date', '$next_anc_date', '12')";
                    
                    if ($conn->query($sql)) {
                        $mother_id = $conn->insert_id;
                        
                        // Insert initial ANC record
                        $anc_sql = "INSERT INTO anc_checkup 
                                    (mother_id, checkup_date, bp, sugar, hemoglobin, weight, risk_level, next_checkup_date, days_to_next_checkup)
                                    VALUES 
                                    ('$mother_id', CURDATE(), '$bp', '$sugar', '$hemoglobin', '$weight', '$overall_risk', '$next_anc_date', 30)";
                        $conn->query($anc_sql);
                        
                        echo '<div class="success-message">✅ Pregnancy registered successfully! Risk Level: ' . $overall_risk . '</div>';
                    } else {
                        echo '<div class="error-message">❌ Error: ' . $conn->error . '</div>';
                    }
                }
                ?>
                <form method="POST" class="pregnancy-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>👩 Mother's Name *</label>
                            <input type="text" name="mother_name" required placeholder="Enter full name">
                        </div>
                        <div class="form-group">
                            <label>🎂 Age *</label>
                            <select name="age" required>
                                <option value="">Select Age</option>
                                <?php for ($i = 15; $i <= 50; $i++) echo "<option value='$i'>$i years</option>"; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>🩸 Blood Group</label>
                            <select name="blood_group">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>🆔 NID Number</label>
                            <input type="text" name="nid" placeholder="National ID">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>📱 Mobile Number *</label>
                            <input type="text" name="mobile" required placeholder="01XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>📅 Expected Delivery Date</label>
                            <input type="date" name="delivery_date">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>🏠 Address</label>
                        <textarea name="address" rows="2" placeholder="Full address"></textarea>
                    </div>

                    <h3>📊 Medical Parameters</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>💓 Blood Pressure (mm/Hg)</label>
                            <input type="text" name="bp" placeholder="120/80" onchange="calculateRiskPreview()">
                        </div>
                        <div class="form-group">
                            <label>⚖️ Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" placeholder="e.g., 55.5" onchange="calculateRiskPreview()">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>🍬 Sugar Level (mmol/L)</label>
                            <input type="number" step="0.1" name="sugar" placeholder="e.g., 5.2" onchange="calculateRiskPreview()">
                        </div>
                        <div class="form-group">
                            <label>🩸 Hemoglobin (g/dL)</label>
                            <input type="number" step="0.1" name="hemoglobin" placeholder="e.g., 12.5" onchange="calculateRiskPreview()">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>⚠️ Complication / Medical History</label>
                        <textarea name="complication" rows="3" placeholder="Any existing complications..."></textarea>
                    </div>

                    <div class="risk-preview" id="riskPreview">
                        Risk Level: <span id="calculatedRisk">Not calculated yet</span>
                    </div>

                    <button type="submit" name="save_pregnancy" class="btn-save">💾 Save Pregnancy Record</button>
                </form>
            </div>

            <!-- Mother Management Section -->
            <!-- Mother Management Section -->
<div id="motherManagement" class="section hidden">
    <h2>👩 Mother Management</h2>
    
    <div class="management-container">
        <!-- Tabs -->
        <div class="management-tabs">
            <button class="tab-btn active" data-tab="motherList">📋 Mother List</button>
            <button class="tab-btn" data-tab="ancSchedule">📅 ANC Schedule</button>
        </div>

        <!-- Mother List Tab -->
        <div id="motherList" class="tab-content">
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" id="searchMother" placeholder="🔍 Search by name, ID, mobile..." 
                           onkeyup="searchMothers()" class="search-input">
                    <div class="filter-group">
                        <select id="filterRisk" onchange="filterMothers()" class="filter-select">
                            <option value="">All Risks</option>
                            <option value="High">High Risk</option>
                            <option value="Medium">Medium Risk</option>
                            <option value="Low">Low Risk</option>
                        </select>
                        <select id="filterStatus" onchange="filterMothers()" class="filter-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <button class="btn-add" onclick="addNewMother()">➕ Add New Mother</button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table" id="mothersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Mobile</th>
                            <th>Blood Group</th>
                            <th>Weeks</th>
                            <th>Last ANC</th>
                            <th>Next ANC</th>
                            <th>Risk Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="motherTableBody">
                        <?php
                        // Fetch all mothers with their latest ANC info
                        $mothersQuery = "SELECT 
                                            pr.*,
                                            (SELECT MAX(checkup_date) FROM anc_checkup WHERE mother_id = pr.id) as last_anc,
                                            (SELECT MAX(next_checkup_date) FROM anc_checkup WHERE mother_id = pr.id) as next_anc
                                        FROM pregnancy_registration pr
                                        ORDER BY pr.id DESC";
                        $mothersResult = $conn->query($mothersQuery);
                        
                        if ($mothersResult->num_rows > 0):
                            while($mother = $mothersResult->fetch_assoc()):
                                // Calculate pregnancy weeks (simplified)
                                $reg_date = new DateTime($mother['registration_date']);
                                $now = new DateTime();
                                $weeks_passed = $reg_date->diff($now)->days / 7;
                                $pregnancy_weeks = min(40, floor($mother['pregnancy_weeks'] + $weeks_passed));
                                
                                // Get latest ANC record for parameters
                                $latestANC = $conn->query("SELECT * FROM anc_checkup WHERE mother_id = {$mother['id']} ORDER BY checkup_date DESC LIMIT 1");
                                $ancData = $latestANC->fetch_assoc();
                                
                                // Determine status
                                $status = $mother['is_active'] ? 'Active' : 'Inactive';
                                $status_class = $mother['is_active'] ? 'active' : 'inactive';
                                
                                // Format dates
                                $last_anc_formatted = $mother['last_anc'] ? date('d M', strtotime($mother['last_anc'])) : 'Never';
                                $next_anc_formatted = $mother['next_anc'] ? date('d M', strtotime($mother['next_anc'])) : 'Not set';
                        ?>
                        <tr data-id="<?php echo $mother['id']; ?>" 
                            data-risk="<?php echo $mother['overall_risk']; ?>" 
                            data-status="<?php echo $status_class; ?>">
                            <td>M<?php echo str_pad($mother['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="mother-name">
                                <strong><?php echo htmlspecialchars($mother['mother_name']); ?></strong><br>
                                <small class="nid">NID: <?php echo $mother['nid_number'] ?: 'N/A'; ?></small>
                            </td>
                            <td><?php echo $mother['age']; ?> yrs</td>
                            <td><?php echo $mother['mobile_number']; ?></td>
                            <td><?php echo $mother['blood_group']; ?></td>
                            <td><?php echo $pregnancy_weeks; ?>w</td>
                            <td><?php echo $last_anc_formatted; ?></td>
                            <td><?php echo $next_anc_formatted; ?></td>
                            <td>
                                <span class="risk-badge <?php echo strtolower($mother['overall_risk']); ?>">
                                    <?php echo $mother['overall_risk']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button class="action-btn view" onclick="viewMotherProfile(<?php echo $mother['id']; ?>)" 
                                        title="View Profile">👁️</button>
                                <button class="action-btn edit" onclick="editMother(<?php echo $mother['id']; ?>)" 
                                        title="Edit">✏️</button>
                                <button class="action-btn delete" onclick="confirmDeleteMother(<?php echo $mother['id']; ?>)" 
                                        title="Delete">🗑️</button>
                                <button class="action-btn anc" onclick="addANCForMother(<?php echo $mother['id']; ?>)" 
                                        title="Add ANC">📋</button>
                            </td>
                        </tr>
                        <?php endwhile; 
                        else: ?>
                        <tr>
                            <td colspan="11" class="no-data">
                                <div class="empty-state">
                                    <span>📭 No mothers found</span>
                                    <p>Click "Add New Mother" to register a pregnancy</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination (optional) -->
            <div class="pagination">
                <button class="page-btn" disabled>« Previous</button>
                <span class="page-info">Page 1 of 1</span>
                <button class="page-btn" disabled>Next »</button>
            </div>
        </div>

        <!-- ANC Schedule Tab -->
        <div id="ancSchedule" class="tab-content hidden">
            <div class="schedule-header">
                <h3>📅 Upcoming ANC Appointments</h3>
                <div class="schedule-filters">
                    <select id="scheduleFilter" onchange="filterANCSchedule()" class="filter-select">
                        <option value="all">All Upcoming</option>
                        <option value="today">Today</option>
                        <option value="tomorrow">Tomorrow</option>
                        <option value="week">This Week</option>
                        <option value="overdue">Overdue</option>
                    </select>
                    <button class="btn-export" onclick="exportANCSchedule()">📤 Export Schedule</button>
                </div>
            </div>
            
            <div class="schedule-grid">
                <?php
                // Fetch upcoming ANC appointments
                $scheduleQuery = "SELECT 
                                    pr.id as mother_id,
                                    pr.mother_name,
                                    pr.mobile_number,
                                    pr.overall_risk,
                                    ac.checkup_date as last_visit,
                                    ac.next_checkup_date as next_visit,
                                    ac.risk_level as anc_risk,
                                    DATEDIFF(ac.next_checkup_date, CURDATE()) as days_remaining
                                  FROM anc_checkup ac
                                  JOIN pregnancy_registration pr ON ac.mother_id = pr.id
                                  WHERE ac.next_checkup_date IS NOT NULL
                                  AND pr.is_active = 1
                                  ORDER BY ac.next_checkup_date ASC
                                  LIMIT 50";
                
                $scheduleResult = $conn->query($scheduleQuery);
                
                if ($scheduleResult->num_rows > 0):
                    while($appointment = $scheduleResult->fetch_assoc()):
                        $days_remaining = $appointment['days_remaining'];
                        
                        // Determine urgency
                        if ($days_remaining < 0) {
                            $urgency = 'overdue';
                            $urgency_text = 'Overdue by ' . abs($days_remaining) . ' days';
                            $badge_class = 'overdue';
                        } elseif ($days_remaining == 0) {
                            $urgency = 'today';
                            $urgency_text = 'Today';
                            $badge_class = 'urgent';
                        } elseif ($days_remaining <= 3) {
                            $urgency = 'urgent';
                            $urgency_text = 'In ' . $days_remaining . ' days';
                            $badge_class = 'warning';
                        } elseif ($days_remaining <= 7) {
                            $urgency = 'soon';
                            $urgency_text = 'In ' . $days_remaining . ' days';
                            $badge_class = 'upcoming';
                        } else {
                            $urgency = 'future';
                            $urgency_text = 'In ' . $days_remaining . ' days';
                            $badge_class = 'future';
                        }
                        
                        // Format dates
                        $next_visit = date('d M, Y', strtotime($appointment['next_visit']));
                        $last_visit = $appointment['last_visit'] ? date('d M, Y', strtotime($appointment['last_visit'])) : 'Never';
                ?>
                <div class="schedule-card" data-urgency="<?php echo $urgency; ?>">
                    <div class="schedule-card-header">
                        <h4><?php echo htmlspecialchars($appointment['mother_name']); ?></h4>
                        <span class="urgency-badge <?php echo $badge_class; ?>">
                            <?php echo $urgency_text; ?>
                        </span>
                    </div>
                    
                    <div class="schedule-card-body">
                        <div class="schedule-info">
                            <div class="info-item">
                                <span class="label">Next Visit:</span>
                                <span class="value"><?php echo $next_visit; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Last Visit:</span>
                                <span class="value"><?php echo $last_visit; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Risk Level:</span>
                                <span class="value">
                                    <span class="risk-badge <?php echo strtolower($appointment['overall_risk']); ?>">
                                        <?php echo $appointment['overall_risk']; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="label">Mobile:</span>
                                <span class="value"><?php echo $appointment['mobile_number']; ?></span>
                            </div>
                        </div>
                        
                        <div class="schedule-actions">
                            <button class="btn-action small" 
                                    onclick="viewMotherProfile(<?php echo $appointment['mother_id']; ?>)">
                                👤 Profile
                            </button>
                            <button class="btn-action small primary" 
                                    onclick="notifyMother(<?php echo $appointment['mother_id']; ?>)">
                                📱 Notify
                            </button>
                            <button class="btn-action small secondary" 
                                    onclick="rescheduleANC(<?php echo $appointment['mother_id']; ?>)">
                                📅 Reschedule
                            </button>
                            <button class="btn-action small success" 
                                    onclick="markAsVisited(<?php echo $appointment['mother_id']; ?>)">
                                ✅ Visited
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; 
                else: ?>
                <div class="no-schedule">
                    <div class="empty-state">
                        <span>📅 No upcoming ANC appointments</span>
                        <p>Add ANC records for mothers to see their schedule here</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistics -->
            <div class="schedule-stats">
                <div class="stat-card small">
                    <h4>Today</h4>
                    <?php
                    $todayCount = $conn->query("SELECT COUNT(*) as count FROM anc_checkup WHERE next_checkup_date = CURDATE()")->fetch_assoc()['count'];
                    ?>
                    <div class="stat-number"><?php echo $todayCount; ?></div>
                </div>
                <div class="stat-card small">
                    <h4>This Week</h4>
                    <?php
                    $weekCount = $conn->query("SELECT COUNT(*) as count FROM anc_checkup WHERE next_checkup_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
                    ?>
                    <div class="stat-number"><?php echo $weekCount; ?></div>
                </div>
                <div class="stat-card small">
                    <h4>Overdue</h4>
                    <?php
                    $overdueCount = $conn->query("SELECT COUNT(*) as count FROM anc_checkup WHERE next_checkup_date < CURDATE()")->fetch_assoc()['count'];
                    ?>
                    <div class="stat-number overdue"><?php echo $overdueCount; ?></div>
                </div>
                <div class="stat-card small">
                    <h4>Total Active</h4>
                    <?php
                    $totalActive = $conn->query("SELECT COUNT(*) as count FROM pregnancy_registration WHERE is_active = 1")->fetch_assoc()['count'];
                    ?>
                    <div class="stat-number"><?php echo $totalActive; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Delete Mother Record</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this mother's record?</p>
                <p class="warning-text">⚠️ This action cannot be undone. All ANC history and related data will be permanently deleted.</p>
                <div class="mother-details" id="deleteMotherDetails">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn-danger" id="confirmDeleteBtn">Yes, Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

            <!-- ANC History Section -->
            <!-- ANC History Section -->
<div id="ancHistory" class="section hidden">
    <h2>📋 ANC History</h2>
    
    <div class="anc-management-container">
        <!-- Search and Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="selectMotherANC">Select Mother:</label>
                    <select id="selectMotherANC" class="form-control">
                        <option value="">-- All Mothers --</option>
                        <?php
                        $mothers = $conn->query("SELECT id, mother_name, nid_number FROM pregnancy_registration WHERE is_active = TRUE ORDER BY mother_name");
                        while($m = $mothers->fetch_assoc()):
                        ?>
                        <option value="<?php echo $m['id']; ?>">
                            <?php echo $m['mother_name']; ?> (NID: <?php echo $m['nid_number'] ?? 'N/A'; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="dateFrom">From Date:</label>
                    <input type="date" id="dateFrom" class="form-control">
                </div>
                
                <div class="filter-group">
                    <label for="dateTo">To Date:</label>
                    <input type="date" id="dateTo" class="form-control">
                </div>
                
                <button class="btn-primary" onclick="loadANCHistory()">🔍 Filter</button>
                <button class="btn-secondary" onclick="resetANCFilter()">🔄 Reset</button>
            </div>
        </div>
        
        <!-- Add New ANC Record -->
        <div class="add-anc-section">
            <h3>➕ Add New ANC Record</h3>
            <form id="addANCForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Mother *</label>
                        <select id="ancMotherId" required class="form-control">
                            <option value="">Select Mother</option>
                            <?php
                            $activeMothers = $conn->query("SELECT id, mother_name FROM pregnancy_registration WHERE is_active = TRUE ORDER BY mother_name");
                            while($m = $activeMothers->fetch_assoc()):
                            ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo $m['mother_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Checkup Date *</label>
                        <input type="date" id="ancCheckupDate" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Pressure (e.g., 120/80) *</label>
                        <input type="text" id="ancBP" required class="form-control" placeholder="120/80">
                    </div>
                    
                    <div class="form-group">
                        <label>Sugar Level (mmol/L) *</label>
                        <input type="number" step="0.1" id="ancSugar" required class="form-control" placeholder="5.2">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Hemoglobin (g/dL) *</label>
                        <input type="number" step="0.1" id="ancHb" required class="form-control" placeholder="12.5">
                    </div>
                    
                    <div class="form-group">
                        <label>Weight (kg) *</label>
                        <input type="number" step="0.1" id="ancWeight" required class="form-control" placeholder="60.5">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Next Checkup Date *</label>
                        <input type="date" id="ancNextDate" required class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Risk Level</label>
                        <select id="ancRiskLevel" class="form-control">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes / Observations</label>
                    <textarea id="ancNotes" rows="3" class="form-control" placeholder="Any observations, complications, or notes..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="clearANCForm()">❌ Clear</button>
                    <button type="submit" class="btn-primary">💾 Save ANC Record</button>
                </div>
            </form>
        </div>
        
        <!-- ANC History Table -->
        <div class="anc-history-table">
            <h3>ANC History Records</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mother Name</th>
                            <th>Checkup Date</th>
                            <th>BP</th>
                            <th>Sugar</th>
                            <th>Hb</th>
                            <th>Weight</th>
                            <th>Risk</th>
                            <th>Next Visit</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ancHistoryBody">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
                <div id="ancLoading" style="text-align: center; padding: 20px;">
                    <div class="loading-spinner"></div>
                    <p>Loading ANC history...</p>
                </div>
                <div id="ancNoData" style="display: none; text-align: center; padding: 20px;">
                    <p>📭 No ANC records found. Add your first ANC record above.</p>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Mother Profile Section -->
            <!-- Mother Profile Section -->
<!-- Mother Profile Section -->
<div id="motherProfile" class="section hidden">
    <h2>👤 Mother Profile</h2>
    
    <div class="profile-container">
        <!-- Default state - select a mother -->
        <div class="no-profile" id="noProfile">
            <div class="empty-state">
                <span>👤 Select a Mother</span>
                <p>Choose a mother from the Mother Management list to view her profile</p>
                <button class="btn-primary" onclick="navigateTo('motherManagement')">
                    📋 Go to Mother Management
                </button>
            </div>
        </div>
        
        <!-- Loading state (hidden by default) -->
        <div class="loading-state" id="profileLoading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Loading mother profile...</p>
        </div>
        
        <!-- Profile content will be loaded here -->
        <div id="profileContent" style="display: none;"></div>
    </div>
</div>

            <!-- Delivery History Section -->
            <div id="deliveryHistory" class="section hidden">
                <h2>👶 Delivery History / Report</h2>
                <div class="delivery-stats">
                    <div class="stat-card">
                        <h3>Total Deliveries</h3>
                        <?php
                        $totalDeliveries = $conn->query("SELECT COUNT(*) as count FROM delivery_history")->fetch_assoc()['count'];
                        ?>
                        <h1><?php echo $totalDeliveries; ?></h1>
                    </div>
                    <div class="stat-card">
                        <h3>Normal Deliveries</h3>
                        <?php
                        $normalDeliveries = $conn->query("SELECT COUNT(*) as count FROM delivery_history WHERE delivery_type = 'Normal'")->fetch_assoc()['count'];
                        ?>
                        <h1><?php echo $normalDeliveries; ?></h1>
                    </div>
                    <div class="stat-card">
                        <h3>C-Sections</h3>
                        <?php
                        $cSections = $conn->query("SELECT COUNT(*) as count FROM delivery_history WHERE delivery_type = 'C-Section'")->fetch_assoc()['count'];
                        ?>
                        <h1><?php echo $cSections; ?></h1>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mother</th>
                            <th>Delivery Date</th>
                            <th>Type</th>
                            <th>Baby Weight</th>
                            <th>Baby Gender</th>
                            <th>Complications</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $deliveryQuery = "SELECT dh.*, pr.mother_name 
                                         FROM delivery_history dh 
                                         JOIN pregnancy_registration pr ON dh.mother_id = pr.id 
                                         ORDER BY dh.delivery_date DESC";
                        $deliveryResult = $conn->query($deliveryQuery);
                        
                        while($delivery = $deliveryResult->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $delivery['mother_name']; ?></td>
                            <td><?php echo date('d M, Y', strtotime($delivery['delivery_date'])); ?></td>
                            <td><span class="delivery-type <?php echo strtolower($delivery['delivery_type']); ?>"><?php echo $delivery['delivery_type']; ?></span></td>
                            <td><?php echo $delivery['baby_weight']; ?> kg</td>
                            <td><?php echo $delivery['baby_gender']; ?></td>
                            <td><?php echo $delivery['complications'] ?: 'None'; ?></td>
                            <td><span class="status-badge <?php echo $delivery['mother_condition']; ?>"><?php echo $delivery['mother_condition']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="add-delivery-form">
                    <h3>➕ Add Delivery Record</h3>
                    <form method="POST" action="save_delivery.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Mother</label>
                                <select name="mother_id" required>
                                    <option value="">Select Mother</option>
                                    <?php
                                    $activeMothers = $conn->query("SELECT id, mother_name FROM pregnancy_registration WHERE is_active = TRUE AND delivery_date IS NOT NULL");
                                    while($m = $activeMothers->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo $m['mother_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Delivery Date</label>
                                <input type="date" name="delivery_date" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Delivery Type</label>
                                <select name="delivery_type" required>
                                    <option value="Normal">Normal</option>
                                    <option value="C-Section">C-Section</option>
                                    <option value="Assisted">Assisted</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Baby Weight (kg)</label>
                                <input type="number" step="0.01" name="baby_weight" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Baby Gender</label>
                                <select name="baby_gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Mother Condition</label>
                                <select name="mother_condition" required>
                                    <option value="Good">Good</option>
                                    <option value="Stable">Stable</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Complications</label>
                            <textarea name="complications" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn-save">💾 Save Delivery Record</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>