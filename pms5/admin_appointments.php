<?php
require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['update_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE Appointments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE appointment_id = ?");
        $stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($stmt->execute()) {
            $message = 'Appointment status updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update appointment status.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while updating the appointment.';
        $message_type = 'error';
    }
}


if (isset($_POST['delete_appointment'])) {
    $appointment_id = intval($_POST['appointment_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM Appointments WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            $message = 'Appointment deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete appointment.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while deleting the appointment.';
        $message_type = 'error';
    }
}


$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$doctor_filter = $_GET['doctor'] ?? '';


$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR d.full_name LIKE ? OR p.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($date_filter)) {
    $where_conditions[] = "a.appointment_date = ?";
    $params[] = $date_filter;
    $param_types .= "s";
}

if (!empty($doctor_filter)) {
    $where_conditions[] = "a.doctor_id = ?";
    $params[] = $doctor_filter;
    $param_types .= "i";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";


$query = "
    SELECT 
        a.*,
        p.full_name as patient_name,
        p.email as patient_email,
        p.phone as patient_phone,
        d.full_name as doctor_name,
        d.specialty as doctor_specialty,
        (SELECT COUNT(*) FROM TreatmentHistory WHERE appointment_id = a.appointment_id) as has_treatment
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    JOIN Doctors d ON a.doctor_id = d.doctor_id
    $where_clause
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM Appointments");
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as today 
    FROM Appointments 
    WHERE appointment_date = CURDATE() AND status IN ('Scheduled', 'Confirmed')
");
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as completed 
    FROM Appointments 
    WHERE status = 'Completed'
");
$stmt->execute();
$completed_appointments = $stmt->get_result()->fetch_assoc()['completed'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as cancelled 
    FROM Appointments 
    WHERE status = 'Cancelled'
");
$stmt->execute();
$cancelled_appointments = $stmt->get_result()->fetch_assoc()['cancelled'];


$stmt = $conn->prepare("SELECT doctor_id, full_name, specialty FROM Doctors ORDER BY full_name");
$stmt->execute();
$doctors = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - Admin Dashboard</title>
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
                    <a href="admin_appointments.php" class="nav-link active">
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
                    <h1 class="page-title">All Appointments</h1>
                    <p class="page-subtitle">Manage and monitor all scheduled appointments</p>
                </div>
                <a href="admin_dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
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
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Appointments</h3>
                        <div class="stat-number"><?php echo $total_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-history"></i>
                            All time appointments
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Today's Appointments</h3>
                        <div class="stat-number"><?php echo $today_appointments; ?></div>
                        <div class="stat-change <?php echo $today_appointments > 0 ? 'positive' : 'neutral'; ?>">
                            <i class="fas fa-<?php echo $today_appointments > 0 ? 'check' : 'clock'; ?>"></i>
                            Scheduled today
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Completed</h3>
                        <div class="stat-number"><?php echo $completed_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-user-check"></i>
                            Successfully completed
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Cancelled</h3>
                        <div class="stat-number"><?php echo $cancelled_appointments; ?></div>
                        <div class="stat-change negative">
                            <i class="fas fa-ban"></i>
                            Cancelled appointments
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filter Appointments</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-input" placeholder="Search patient or doctor..." value="<?php echo htmlspecialchars($search); ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Scheduled" <?php echo $status_filter === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="Confirmed" <?php echo $status_filter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="No-Show" <?php echo $status_filter === 'No-Show' ? 'selected' : ''; ?>>No-Show</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-input" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Doctor</label>
                                <select name="doctor" class="form-select">
                                    <option value="">All Doctors</option>
                                    <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                        <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo $doctor_filter == $doctor['doctor_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($doctor['full_name']); ?> (<?php echo htmlspecialchars($doctor['specialty']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1; text-align: right;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="admin_appointments.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i>
                                    Clear All
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Appointments List</h2>
                </div>
                <div class="card-body">
                    <?php if ($appointments->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Treatment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $appointment['appointment_id']; ?></strong></td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($appointment['patient_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                                        <div class="patient-email"><?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($appointment['doctor_name']); ?></strong>
                                                    <div style="font-size: 0.875rem; color: var(--gray-600);"><?php echo htmlspecialchars($appointment['doctor_specialty']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></strong>
                                                    <div style="font-size: 0.875rem; color: var(--gray-600);"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('-', '', $appointment['status'])); ?>">
                                                    <?php echo $appointment['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($appointment['has_treatment'] > 0): ?>
                                                    <span class="status-badge status-completed">
                                                        <i class="fas fa-check"></i> Recorded
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">
                                                        <i class="fas fa-clock"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="viewAppointmentDetails(<?php echo $appointment['appointment_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                        <select name="status" class="form-select btn-sm" onchange="this.form.submit()" style="margin-right: 0.5rem;">
                                                            <option value="">Change Status</option>
                                                            <option value="Scheduled">Scheduled</option>
                                                            <option value="Confirmed">Confirmed</option>
                                                            <option value="Completed">Completed</option>
                                                            <option value="Cancelled">Cancelled</option>
                                                            <option value="No-Show">No-Show</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this appointment? This action cannot be undone.');">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                        <button type="submit" name="delete_appointment" class="btn btn-error btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
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
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($search) || !empty($status_filter) || !empty($date_filter) || !empty($doctor_filter)): ?>
                                    No appointments match your filter criteria.
                                <?php else: ?>
                                    No appointments are scheduled in the system yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="appointmentModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Appointment Details</h3>
                <button class="modal-close" onclick="closeAppointmentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="appointmentModalContent">
                
            </div>
        </div>
    </div>

    <script>
        function viewAppointmentDetails(appointmentId) {
            document.getElementById('appointmentModalContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>Appointment Details</h4>
                    <p><em>Appointment details would be loaded here for appointment ID: ${appointmentId}</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Complete patient information</li>
                        <li>Doctor information</li>
                        <li>Appointment notes and history</li>
                        <li>Treatment records if available</li>
                        <li>Any feedback or follow-up required</li>
                    </ul>
                </div>
            `;
            document.getElementById('appointmentModal').classList.add('active');
        }

        function closeAppointmentModal() {
            document.getElementById('appointmentModal').classList.remove('active');
        }

        
        document.getElementById('appointmentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAppointmentModal();
            }
        });
    </script>
</body>
</html>