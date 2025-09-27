<?php
require_once 'includes/auth.php';


$auth->requireRole('Patient', 'login.php');

$user = $auth->getCurrentUser();
$patient_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['check_in'])) {
    $appointment_id = intval($_POST['appointment_id']);
    
    try {
        
        $stmt = $conn->prepare("SELECT attendance_id FROM Attendance WHERE patient_id = ? AND appointment_id = ?");
        $stmt->bind_param("ii", $patient_id, $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'You have already checked in for this appointment.';
            $message_type = 'warning';
        } else {
            
            $stmt = $conn->prepare("INSERT INTO Attendance (patient_id, appointment_id, attendance_method, status) VALUES (?, ?, 'Online', 'Present')");
            $stmt->bind_param("ii", $patient_id, $appointment_id);
            
            if ($stmt->execute()) {
                $message = 'Check-in successful! You have been marked as present.';
                $message_type = 'success';
            } else {
                $message = 'Check-in failed. Please try again.';
                $message_type = 'error';
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred during check-in.';
        $message_type = 'error';
    }
}


$stmt = $conn->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status as appointment_status,
        d.full_name as doctor_name,
        d.specialty,
        att.status as attendance_status,
        att.check_in_time,
        att.attendance_method
    FROM Appointments a
    JOIN Doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN Attendance att ON a.appointment_id = att.appointment_id AND att.patient_id = ?
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->bind_param("ii", $patient_id, $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Pakenham Hospital</title>
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
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="book_appointment.php" class="nav-link">
                        <i class="fas fa-calendar-plus icon"></i>
                        <span>Book Appointment</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_appointments.php" class="nav-link">
                        <i class="fas fa-calendar-check icon"></i>
                        <span>My Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="treatment_history.php" class="nav-link">
                        <i class="fas fa-file-medical icon"></i>
                        <span>Treatment History</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="waitlist.php" class="nav-link">
                        <i class="fas fa-clock icon"></i>
                        <span>Waitlist</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link active">
                        <i class="fas fa-user-check icon"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="support.php" class="nav-link">
                        <i class="fas fa-headset icon"></i>
                        <span>Support</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="disciplinary.php" class="nav-link">
                        <i class="fas fa-exclamation-triangle icon"></i>
                        <span>Disciplinary Records</span>
                    </a>
                </li>
            </ul>
        </nav>

        
        <main class="main-content">
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Attendance Tracking</h1>
                    <p class="page-subtitle">Check in for your appointments and view attendance history</p>
                </div>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Present</h3>
                        <div class="stat-number">
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Attendance WHERE patient_id = ? AND status = 'Present'");
                            $stmt->bind_param("i", $patient_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            echo $result->fetch_assoc()['count'];
                            ?>
                        </div>
                        <div class="stat-change positive">
                            <i class="fas fa-check"></i>
                            Total check-ins
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Absent</h3>
                        <div class="stat-number">
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Attendance WHERE patient_id = ? AND status = 'Absent'");
                            $stmt->bind_param("i", $patient_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            echo $result->fetch_assoc()['count'];
                            ?>
                        </div>
                        <div class="stat-change negative">
                            <i class="fas fa-times"></i>
                            Missed appointments
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Upcoming</h3>
                        <div class="stat-number">
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('Scheduled', 'Confirmed')");
                            $stmt->bind_param("i", $patient_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            echo $result->fetch_assoc()['count'];
                            ?>
                        </div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar"></i>
                            Future appointments
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Appointment Attendance</h2>
                </div>
                <div class="card-body">
                    <?php if ($appointments->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialty</th>
                                        <th>Date & Time</th>
                                        <th>Appointment Status</th>
                                        <th>Attendance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($appointment['doctor_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo htmlspecialchars($appointment['specialty']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?><br>
                                                <small style="color: var(--gray-600);"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($appointment['appointment_status']); ?>">
                                                    <?php echo $appointment['appointment_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($appointment['attendance_status']): ?>
                                                    <span class="status-badge status-<?php echo strtolower($appointment['attendance_status']); ?>">
                                                        <?php echo $appointment['attendance_status']; ?>
                                                    </span>
                                                    <?php if ($appointment['check_in_time']): ?>
                                                        <br><small style="color: var(--gray-600);">
                                                            Checked in: <?php echo date('M j, g:i A', strtotime($appointment['check_in_time'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="status-badge status-not_checked_in">Not Checked In</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $can_check_in = !$appointment['attendance_status'] && 
                                                             $appointment['appointment_date'] == date('Y-m-d') && 
                                                             $appointment['appointment_status'] === 'Scheduled';
                                                ?>
                                                <?php if ($can_check_in): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                        <button type="submit" name="check_in" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i>
                                                            Check In
                                                        </button>
                                                    </form>
                                                <?php elseif ($appointment['attendance_status'] === 'Present'): ?>
                                                    <span style="color: var(--success); font-size: 0.875rem;">
                                                        <i class="fas fa-check-circle"></i> Checked In
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500); font-size: 0.875rem;">
                                                        <i class="fas fa-clock"></i> Not Available
                                                    </span>
                                                <?php endif; ?>
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
                            <p style="color: var(--gray-500); margin-bottom: 2rem;">You don't have any appointments yet.</p>
                            <a href="book_appointment.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i>
                                Book Your First Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
