<?php
require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


$report_type = $_GET['report'] ?? 'overview';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); 
$date_to = $_GET['date_to'] ?? date('Y-m-d'); 
$format = $_GET['format'] ?? 'html';


$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'overview':
        $report_title = 'System Overview Report';
        
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Patients");
        $stmt->execute();
        $report_data['total_patients'] = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Doctors");
        $stmt->execute();
        $report_data['total_doctors'] = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Appointments WHERE appointment_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['appointments'] = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM SupportTickets WHERE created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['support_tickets'] = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM DisciplinaryRecords WHERE created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['disciplinary_records'] = $stmt->get_result()->fetch_assoc()['total'];
        break;
        
    case 'appointments':
        $report_title = 'Appointments Report';
        
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                p.full_name as patient_name,
                d.full_name as doctor_name,
                d.specialty
            FROM Appointments a
            JOIN Patients p ON a.patient_id = p.patient_id
            JOIN Doctors d ON a.doctor_id = d.doctor_id
            WHERE a.appointment_date BETWEEN ? AND ?
            ORDER BY a.appointment_date DESC, a.appointment_time ASC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['appointments'] = $stmt->get_result();
        break;
        
    case 'patients':
        $report_title = 'Patients Report';
        
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                COUNT(a.appointment_id) as total_appointments,
                MAX(a.appointment_date) as last_appointment
            FROM Patients p
            LEFT JOIN Appointments a ON p.patient_id = a.patient_id
            WHERE p.created_at BETWEEN ? AND ?
            GROUP BY p.patient_id
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['patients'] = $stmt->get_result();
        break;
        
    case 'doctors':
        $report_title = 'Doctors Report';
        
        $stmt = $conn->prepare("
            SELECT 
                d.*,
                COUNT(a.appointment_id) as total_appointments,
                COUNT(DISTINCT a.patient_id) as unique_patients
            FROM Doctors d
            LEFT JOIN Appointments a ON d.doctor_id = a.doctor_id AND a.appointment_date BETWEEN ? AND ?
            GROUP BY d.doctor_id
            ORDER BY d.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['doctors'] = $stmt->get_result();
        break;
        
    case 'support':
        $report_title = 'Support Tickets Report';
        
        $stmt = $conn->prepare("
            SELECT 
                t.*,
                p.full_name as patient_name,
                a.full_name as assigned_admin
            FROM SupportTickets t
            JOIN Patients p ON t.patient_id = p.patient_id
            LEFT JOIN Admins a ON t.assigned_to = a.admin_id
            WHERE t.created_at BETWEEN ? AND ?
            ORDER BY t.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['support_tickets'] = $stmt->get_result();
        break;
        
    case 'disciplinary':
        $report_title = 'Disciplinary Records Report';
        
        $stmt = $conn->prepare("
            SELECT 
                d.*,
                p.full_name as patient_name,
                a.full_name as recorded_by_name
            FROM DisciplinaryRecords d
            JOIN Patients p ON d.patient_id = p.patient_id
            LEFT JOIN Admins a ON d.recorded_by = a.admin_id
            WHERE d.created_at BETWEEN ? AND ?
            ORDER BY d.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['disciplinary_records'] = $stmt->get_result();
        break;
        
    case 'attendance':
        $report_title = 'Attendance Report';
        
        $stmt = $conn->prepare("
            SELECT 
                att.*,
                p.full_name as patient_name,
                d.full_name as doctor_name,
                a.appointment_date,
                a.appointment_time
            FROM Attendance att
            JOIN Appointments a ON att.appointment_id = a.appointment_id
            JOIN Patients p ON a.patient_id = p.patient_id
            JOIN Doctors d ON a.doctor_id = d.doctor_id
            WHERE att.check_in_time BETWEEN ? AND ?
            ORDER BY att.check_in_time DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data['attendance'] = $stmt->get_result();
        break;
}


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM Patients WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$new_patients = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM Appointments WHERE appointment_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM SupportTickets WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$total_tickets = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM DisciplinaryRecords WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$total_violations = $stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="./css/modern-style.css">
    <link href="https:
    <link rel="stylesheet" href="https:
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo-section">
                <img src="images/healthcare.png" alt="Pakenham Hospital" class="logo" style="width: 40px; height: 40px; object-fit: contain;">
                <h2>Pakenham Hospital</h2>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link">
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
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_disciplinary.php" class="nav-link">
                        <i class="fas fa-exclamation-triangle icon"></i>
                        <span>Disciplinary Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_reports.php" class="nav-link active">
                        <i class="fas fa-chart-bar icon"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Reports & Analytics</h1>
                    <p class="page-subtitle">Generate comprehensive reports and system analytics</p>
                </div>
                <a href="admin_dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            <!-- Report Controls -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Report Generator</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Report Type</label>
                                <select name="report" class="form-select" onchange="this.form.submit()">
                                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>System Overview</option>
                                    <option value="appointments" <?php echo $report_type === 'appointments' ? 'selected' : ''; ?>>Appointments</option>
                                    <option value="patients" <?php echo $report_type === 'patients' ? 'selected' : ''; ?>>Patients</option>
                                    <option value="doctors" <?php echo $report_type === 'doctors' ? 'selected' : ''; ?>>Doctors</option>
                                    <option value="support" <?php echo $report_type === 'support' ? 'selected' : ''; ?>>Support Tickets</option>
                                    <option value="disciplinary" <?php echo $report_type === 'disciplinary' ? 'selected' : ''; ?>>Disciplinary Records</option>
                                    <option value="attendance" <?php echo $report_type === 'attendance' ? 'selected' : ''; ?>>Attendance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-input" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-input" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Format</label>
                                <select name="format" class="form-select">
                                    <option value="html" <?php echo $format === 'html' ? 'selected' : ''; ?>>HTML View</option>
                                    <option value="pdf" <?php echo $format === 'pdf' ? 'selected' : ''; ?>>PDF Export</option>
                                    <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV Export</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-line"></i>
                                    Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Summary -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>New Patients</h3>
                        <div class="stat-number"><?php echo $new_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-user-plus"></i>
                            <?php echo date('M j', strtotime($date_from)); ?> - <?php echo date('M j', strtotime($date_to)); ?>
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
                        <h3>Appointments</h3>
                        <div class="stat-number"><?php echo $total_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar"></i>
                            Total scheduled
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
                        <h3>Support Tickets</h3>
                        <div class="stat-number"><?php echo $total_tickets; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-ticket-alt"></i>
                            Total requests
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
                        <h3>Violations</h3>
                        <div class="stat-number"><?php echo $total_violations; ?></div>
                        <div class="stat-change <?php echo $total_violations > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $total_violations > 0 ? 'exclamation' : 'check'; ?>"></i>
                            Disciplinary records
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo $report_title; ?></h2>
                    <div style="display: flex; gap: 1rem;">
                        <span style="color: var(--gray-600); font-size: 0.875rem;">
                            Period: <?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>
                        </span>
                        <button class="btn btn-outline btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($report_type === 'overview'): ?>
                        <!-- Overview Report -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">System Statistics</h3>
                                </div>
                                <div class="card-body">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                        <span>Total Patients:</span>
                                        <strong><?php echo $report_data['total_patients']; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                        <span>Total Doctors:</span>
                                        <strong><?php echo $report_data['total_doctors']; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                        <span>Appointments:</span>
                                        <strong><?php echo $report_data['appointments']; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                        <span>Support Tickets:</span>
                                        <strong><?php echo $report_data['support_tickets']; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>Disciplinary Records:</span>
                                        <strong><?php echo $report_data['disciplinary_records']; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($report_type === 'appointments'): ?>
                        <!-- Appointments Report -->
                        <?php if ($report_data['appointments']->num_rows > 0): ?>
                            <div class="table-container">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = $report_data['appointments']->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                                        <?php echo $appointment['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 3rem;">
                                <i class="fas fa-calendar-times" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                                <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Appointments Found</h3>
                                <p style="color: var(--gray-500);">No appointments found for the selected date range.</p>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($report_type === 'patients'): ?>
                        <!-- Patients Report -->
                        <?php if ($report_data['patients']->num_rows > 0): ?>
                            <div class="table-container">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Total Appointments</th>
                                            <th>Last Appointment</th>
                                            <th>Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($patient = $report_data['patients']->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="patient-info">
                                                        <div class="patient-avatar"><?php echo substr($patient['full_name'], 0, 2); ?></div>
                                                        <div class="patient-details">
                                                            <div class="patient-name"><?php echo htmlspecialchars($patient['full_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                                <td><?php echo $patient['total_appointments']; ?></td>
                                                <td>
                                                    <?php if ($patient['last_appointment']): ?>
                                                        <?php echo date('M j, Y', strtotime($patient['last_appointment'])); ?>
                                                    <?php else: ?>
                                                        <span style="color: var(--gray-500);">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 3rem;">
                                <i class="fas fa-users" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                                <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Patients Found</h3>
                                <p style="color: var(--gray-500);">No patients registered in the selected date range.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Other Reports -->
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-chart-bar" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Report Generated</h3>
                            <p style="color: var(--gray-500);"><?php echo $report_title; ?> for the selected period.</p>
                            <p style="color: var(--gray-500); font-size: 0.875rem;">
                                Report data would be displayed here based on the selected report type.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        
        document.querySelector('select[name="report"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>
