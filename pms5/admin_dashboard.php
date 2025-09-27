<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['add_disciplinary'])) {
    $patient_id = intval($_POST['patient_id']);
    $violation_type = trim($_POST['violation_type']);
    $description = trim($_POST['description']);
    $severity = $_POST['severity'];
    $action_taken = trim($_POST['action_taken']);
    
    if (empty($violation_type) || empty($description)) {
        $message = 'Violation type and description are required fields.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO DisciplinaryRecords (patient_id, violation_type, description, severity, action_taken, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $patient_id, $violation_type, $description, $severity, $action_taken, $admin_id);
            
            if ($stmt->execute()) {
                $message = 'Disciplinary record added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add disciplinary record.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'An error occurred while adding the disciplinary record.';
            $message_type = 'error';
        }
    }
}


try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Patients");
    $stmt->execute();
    $total_patients = $stmt->get_result()->fetch_assoc()['total'];
} catch (Exception $e) {
    $total_patients = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Doctors");
    $stmt->execute();
    $total_doctors = $stmt->get_result()->fetch_assoc()['total'];
} catch (Exception $e) {
    $total_doctors = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Appointments WHERE appointment_date >= CURDATE()");
    $stmt->execute();
    $today_appointments = $stmt->get_result()->fetch_assoc()['total'];
} catch (Exception $e) {
    $today_appointments = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM SupportTickets WHERE status IN ('Open', 'In_Progress')");
    $stmt->execute();
    $open_tickets = $stmt->get_result()->fetch_assoc()['total'];
} catch (Exception $e) {
    $open_tickets = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM DisciplinaryRecords WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $recent_violations = $stmt->get_result()->fetch_assoc()['total'];
} catch (Exception $e) {
    $recent_violations = 0;
}


$stmt = $conn->prepare("
    SELECT 
        'Appointment' as type,
        a.appointment_id as id,
        CONCAT('Appointment with ', COALESCE(d.full_name, 'Unknown Doctor')) as description,
        COALESCE(p.full_name, 'Unknown Patient') as patient_name,
        a.created_at as timestamp
    FROM Appointments a
    LEFT JOIN Patients p ON a.patient_id = p.patient_id
    LEFT JOIN Doctors d ON a.doctor_id = d.doctor_id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_activity = $stmt->get_result();


$stmt = $conn->prepare("
    SELECT 
        t.*,
        p.full_name as patient_name,
        p.email as patient_email
    FROM SupportTickets t
    JOIN Patients p ON t.patient_id = p.patient_id
    WHERE t.status IN ('Open', 'In_Progress')
    ORDER BY t.priority DESC, t.created_at ASC
    LIMIT 10
");
$stmt->execute();
$open_tickets_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pakenham Hospital</title>
    <link rel="stylesheet" href="./css/modern-style.css">
    <link href="https:
    <link rel="stylesheet" href="https:
</head>
<body>
    <div class="dashboard-container">
        
        <nav class="sidebar">
            <div class="logo-section">
                <img src="images/healthcare.png" alt="Pakenham Hospital" class="logo" style="width: 40px; height: 40px; object-fit: contain;">
                <h2>Pakenham Hospital</h2>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_patients.php" class="nav-link">
                        <i class="fas fa-users icon"></i>
                        <span>Manage Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_doctors.php" class="nav-link">
                        <i class="fas fa-user-md icon"></i>
                        <span>Manage Doctors</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_appointments.php" class="nav-link">
                        <i class="fas fa-calendar-alt icon"></i>
                        <span>All Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_support.php" class="nav-link">
                        <i class="fas fa-headset icon"></i>
                        <span>Support Tickets</span>
                        <?php if($open_tickets > 0): ?>
                            <span class="badge"><?php echo $open_tickets; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_disciplinary.php" class="nav-link">
                        <i class="fas fa-exclamation-triangle icon"></i>
                        <span>Disciplinary Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_reports.php" class="nav-link">
                        <i class="fas fa-chart-bar icon"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </nav>

        
        <main class="main-content">
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">System administration and management</p>
                </div>
                <a href="logout.php" class="btn btn-error">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>

            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Patients</h3>
                        <div class="stat-number"><?php echo $total_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-user-plus"></i>
                            Registered patients
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Doctors</h3>
                        <div class="stat-number"><?php echo $total_doctors; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-stethoscope"></i>
                            Healthcare professionals
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Today's Appointments</h3>
                        <div class="stat-number"><?php echo $today_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-day"></i>
                            Scheduled today
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Open Tickets</h3>
                        <div class="stat-number"><?php echo $open_tickets; ?></div>
                        <div class="stat-change <?php echo $open_tickets > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $open_tickets > 0 ? 'exclamation' : 'check'; ?>"></i>
                            Pending resolution
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Recent Violations</h3>
                        <div class="stat-number"><?php echo $recent_violations; ?></div>
                        <div class="stat-change <?php echo $recent_violations > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $recent_violations > 0 ? 'exclamation' : 'check'; ?>"></i>
                            Last 30 days
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Open Support Tickets</h2>
                </div>
                <div class="card-body">
                    <?php if ($open_tickets_list->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Patient</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ticket = $open_tickets_list->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $ticket['ticket_id']; ?></strong></td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($ticket['patient_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($ticket['patient_name']); ?></div>
                                                        <div class="patient-email"><?php echo htmlspecialchars($ticket['patient_email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($ticket['category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($ticket['priority']); ?>">
                                                    <?php echo $ticket['priority']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '', $ticket['status'])); ?>">
                                                    <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" onclick="viewTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Open Tickets</h3>
                            <p style="color: var(--gray-500);">All support tickets have been resolved!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent System Activity</h2>
                </div>
                <div class="card-body">
                    <?php if ($recent_activity->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Patient</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($activity['type']); ?>">
                                                    <?php echo $activity['type']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['patient_name']); ?></td>
                                            <td>
                                                <?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-history" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Recent Activity</h3>
                            <p style="color: var(--gray-500);">No system activity in the last 7 days.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <button class="btn btn-primary btn-full" onclick="openDisciplinaryModal()">
                            <i class="fas fa-exclamation-triangle"></i>
                            Add Disciplinary Record
                        </button>
                        <a href="admin_patients.php" class="btn btn-outline btn-full">
                            <i class="fas fa-user-plus"></i>
                            Add New Patient
                        </a>
                        <a href="admin_doctors.php" class="btn btn-outline btn-full">
                            <i class="fas fa-user-md"></i>
                            Add New Doctor
                        </a>
                        <a href="admin_reports.php" class="btn btn-outline btn-full">
                            <i class="fas fa-chart-bar"></i>
                            Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="disciplinaryModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Disciplinary Record</h3>
                <button class="modal-close" onclick="closeDisciplinaryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="disciplinaryForm">
                    <div class="form-group">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Select patient...</option>
                            <?php
                            $stmt = $conn->prepare("SELECT patient_id, full_name FROM Patients ORDER BY full_name");
                            $stmt->execute();
                            $patients = $stmt->get_result();
                            while($patient = $patients->fetch_assoc()) {
                                echo "<option value='{$patient['patient_id']}'>{$patient['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Violation Type *</label>
                        <input type="text" name="violation_type" class="form-input" placeholder="e.g., Late arrival, No-show, Inappropriate behavior" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-textarea" rows="3" placeholder="Detailed description of the violation..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Action Taken</label>
                        <textarea name="action_taken" class="form-textarea" rows="2" placeholder="Action taken in response to the violation..."></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="add_disciplinary" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Save Disciplinary Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDisciplinaryModal() {
            document.getElementById('disciplinaryModal').classList.add('active');
        }

        function closeDisciplinaryModal() {
            document.getElementById('disciplinaryModal').classList.remove('active');
            document.getElementById('disciplinaryForm').reset();
        }

        function viewTicket(ticketId) {
            
            alert('Ticket details view would open for ticket ID: ' + ticketId);
        }

        
        document.getElementById('disciplinaryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDisciplinaryModal();
            }
        });
    </script>
</body>
</html>
