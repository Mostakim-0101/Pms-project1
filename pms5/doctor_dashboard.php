<?php
require_once 'includes/auth.php';


$auth->requireRole('Doctor', 'login.php');

$user = $auth->getCurrentUser();
$doctor_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['add_treatment'])) {
    $patient_id = intval($_POST['patient_id']);
    $diagnosis = trim($_POST['diagnosis']);
    $treatment = trim($_POST['treatment']);
    $notes = trim($_POST['notes']);
    $prescription = trim($_POST['prescription']);
    $follow_up_date = $_POST['follow_up_date'];
    
    if (empty($diagnosis) || empty($treatment)) {
        $message = 'Diagnosis and treatment are required fields.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO TreatmentHistory (patient_id, doctor_id, visit_date, diagnosis, treatment, notes, prescription, follow_up_date) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $patient_id, $doctor_id, $diagnosis, $treatment, $notes, $prescription, $follow_up_date);
            
            if ($stmt->execute()) {
                $message = 'Treatment record added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add treatment record.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'An error occurred while adding the treatment record.';
            $message_type = 'error';
        }
    }
}


$stmt = $conn->prepare("SELECT COUNT(*) as total_appointments FROM Appointments WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total_appointments'];

$stmt = $conn->prepare("SELECT COUNT(*) as today_appointments FROM Appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today_appointments'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_patients FROM Appointments WHERE doctor_id = ? GROUP BY patient_id");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_patients = $stmt->get_result()->num_rows;


$stmt = $conn->prepare("
    SELECT 
        a.*,
        p.full_name as patient_name,
        p.email as patient_email,
        att.status as attendance_status,
        att.check_in_time
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    LEFT JOIN Attendance att ON a.appointment_id = att.appointment_id
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_appointments_list = $stmt->get_result();


$stmt = $conn->prepare("
    SELECT DISTINCT
        p.patient_id,
        p.full_name,
        p.email,
        MAX(a.appointment_date) as last_visit
    FROM Patients p
    JOIN Appointments a ON p.patient_id = a.patient_id
    WHERE a.doctor_id = ?
    GROUP BY p.patient_id, p.full_name, p.email
    ORDER BY last_visit DESC
    LIMIT 10
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$recent_patients = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Pakenham Hospital</title>
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
                    <a href="doctor_dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_appointments.php" class="nav-link">
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

        
        <main class="main-content">
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Welcome, Dr. <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                    <p class="page-subtitle">Manage your patients and appointments</p>
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
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Today's Appointments</h3>
                        <div class="stat-number"><?php echo $today_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-day"></i>
                            Scheduled for today
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Appointments</h3>
                        <div class="stat-number"><?php echo $total_appointments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            All time appointments
                        </div>
                    </div>
                </div>

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
                            <i class="fas fa-user-friends"></i>
                            Unique patients
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Today's Appointments</h2>
                </div>
                <div class="card-body">
                    <?php if ($today_appointments_list->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Attendance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $today_appointments_list->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></strong>
                                            </td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($appointment['patient_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
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
                                                <button class="btn btn-primary btn-sm" onclick="openTreatmentModal(<?php echo $appointment['appointment_id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>', <?php echo $appointment['patient_id']; ?>)">
                                                    <i class="fas fa-file-medical"></i>
                                                    Add Treatment
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-calendar-times" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Appointments Today</h3>
                            <p style="color: var(--gray-500);">You have no appointments scheduled for today.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Patients</h2>
                </div>
                <div class="card-body">
                    <?php if ($recent_patients->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Email</th>
                                        <th>Last Visit</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($patient = $recent_patients->fetch_assoc()): ?>
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
                                            <td><?php echo date('M j, Y', strtotime($patient['last_visit'])); ?></td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="viewPatientHistory(<?php echo $patient['patient_id']; ?>)">
                                                    <i class="fas fa-history"></i>
                                                    View History
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-users" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Patients Yet</h3>
                            <p style="color: var(--gray-500);">You haven't seen any patients yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="treatmentModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Treatment Record</h3>
                <button class="modal-close" onclick="closeTreatmentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="treatmentForm">
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

        function viewPatientHistory(patientId) {
            
            alert('Patient history view would open for patient ID: ' + patientId);
        }

        
        document.getElementById('treatmentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTreatmentModal();
            }
        });
    </script>
</body>
</html>
