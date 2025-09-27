<?php
require_once 'includes/auth.php';


$auth->requireRole('Doctor', 'login.php');

$user = $auth->getCurrentUser();
$doctor_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['update_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE Appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        
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


$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';


$where_conditions = ["a.doctor_id = ?"];
$params = [$doctor_id];
$param_types = "i";

if (!empty($filter_date)) {
    $where_conditions[] = "a.appointment_date = ?";
    $params[] = $filter_date;
    $param_types .= "s";
}

if (!empty($filter_status)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);


$stmt = $conn->prepare("
    SELECT 
        a.*,
        p.full_name as patient_name,
        p.email as patient_email,
        p.phone as patient_phone,
        att.status as attendance_status,
        att.check_in_time
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    LEFT JOIN Attendance att ON a.appointment_id = att.appointment_id
    WHERE $where_clause
    ORDER BY a.appointment_date DESC, a.appointment_time ASC
");
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM Appointments WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as today FROM Appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today'];

$stmt = $conn->prepare("SELECT COUNT(*) as upcoming FROM Appointments WHERE doctor_id = ? AND appointment_date > CURDATE() AND status IN ('Scheduled', 'Confirmed')");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_assoc()['upcoming'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Doctor Dashboard</title>
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
                    <a href="doctor_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_appointments.php" class="nav-link active">
                        <i class="fas fa-calendar-check icon"></i>
                        <span>My Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_patients.php" class="nav-link">
                        <i class="fas fa-users icon"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_treatment.php" class="nav-link">
                        <i class="fas fa-file-medical icon"></i>
                        <span>Treatment Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_support.php" class="nav-link">
                        <i class="fas fa-headset icon"></i>
                        <span>Support Tickets</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">My Appointments</h1>
                    <p class="page-subtitle">Manage your patient appointments</p>
                </div>
                <a href="doctor_dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            <!-- Message Display -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Appointments</h3>
                        <div class="stat-number"><?php echo $total_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            All time
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
                        <div class="stat-change positive">
                            <i class="fas fa-clock"></i>
                            Scheduled today
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Upcoming</h3>
                        <div class="stat-number"><?php echo $upcoming_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            Future appointments
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filter Appointments</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-input" value="<?php echo htmlspecialchars($filter_date); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Scheduled" <?php echo $filter_status === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="Confirmed" <?php echo $filter_status === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Completed" <?php echo $filter_status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $filter_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="doctor_appointments.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Appointments List -->
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
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Attendance</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></strong><br>
                                                <small style="color: var(--gray-600);"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($appointment['patient_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.875rem;">
                                                    <div><?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                                                    <div style="color: var(--gray-600);"><?php echo htmlspecialchars($appointment['patient_phone']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                                    <?php echo $appointment['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($appointment['attendance_status']): ?>
                                                    <span class="status-badge status-<?php echo strtolower($appointment['attendance_status']); ?>">
                                                        <?php echo $appointment['attendance_status']; ?>
                                                    </span>
                                                    <?php if ($appointment['check_in_time']): ?>
                                                        <br><small style="color: var(--gray-600);">
                                                            <?php echo date('g:i A', strtotime($appointment['check_in_time'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="status-badge status-not_checked_in">Not Checked In</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($appointment['notes']): ?>
                                                    <div style="max-width: 200px; font-size: 0.875rem;">
                                                        <?php echo htmlspecialchars($appointment['notes']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500); font-size: 0.875rem;">No notes</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="openTreatmentModal(<?php echo $appointment['appointment_id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>', <?php echo $appointment['patient_id']; ?>)">
                                                        <i class="fas fa-file-medical"></i>
                                                        Treatment
                                                    </button>
                                                    <button class="btn btn-outline btn-sm" onclick="openStatusModal(<?php echo $appointment['appointment_id']; ?>, '<?php echo $appointment['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                        Status
                                                    </button>
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
                            <p style="color: var(--gray-500);">No appointments match your current filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Treatment Modal -->
    <div class="modal-overlay" id="treatmentModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Treatment Record</h3>
                <button class="modal-close" onclick="closeTreatmentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="doctor_dashboard.php" id="treatmentForm">
                    <input type="hidden" name="patient_id" id="modal_patient_id">
                    <input type="hidden" name="appointment_id" id="modal_appointment_id">
                    
                    <div class="form-group">
                        <label class="form-label">Patient</label>
                        <input type="text" id="modal_patient_name" class="form-input" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Diagnosis *</label>
                        <textarea name="diagnosis" class="form-textarea" rows="3" placeholder="Enter diagnosis..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Treatment *</label>
                        <textarea name="treatment" class="form-textarea" rows="3" placeholder="Enter treatment details..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-textarea" rows="2" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prescription</label>
                        <textarea name="prescription" class="form-textarea" rows="2" placeholder="Prescription details..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-input">
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="add_treatment" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Save Treatment Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal-overlay" id="statusModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Update Appointment Status</h3>
                <button class="modal-close" onclick="closeStatusModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="statusForm">
                    <input type="hidden" name="appointment_id" id="status_appointment_id">
                    
                    <div class="form-group">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="update_status" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTreatmentModal(appointmentId, patientName, patientId) {
            document.getElementById('modal_appointment_id').value = appointmentId;
            document.getElementById('modal_patient_id').value = patientId;
            document.getElementById('modal_patient_name').value = patientName;
            document.getElementById('treatmentModal').classList.add('active');
        }

        function closeTreatmentModal() {
            document.getElementById('treatmentModal').classList.remove('active');
            document.getElementById('treatmentForm').reset();
        }

        function openStatusModal(appointmentId, currentStatus) {
            document.getElementById('status_appointment_id').value = appointmentId;
            document.querySelector('#statusForm select[name="status"]').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        
        document.getElementById('treatmentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTreatmentModal();
            }
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>
</html>
