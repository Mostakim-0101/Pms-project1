<?php 
require_once 'includes/auth.php';


$auth->requireRole('Patient', 'login.php');

$user = $auth->getCurrentUser();
$patient_id = $user['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Pakenham Hospital</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link href="https:
    <link rel="stylesheet" href="https:
</head>
<body>
    <div class="dashboard-container">
        
        <nav class="sidebar">
            <div class="logo-section">
                <div class="logo">🏥</div>
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
                    <a href="my_appointments.php" class="nav-link active">
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
                    <h1 class="page-title">My Appointments</h1>
                    <p class="page-subtitle">View and manage your scheduled appointments</p>
                </div>
                <div class="flex gap-4">
                    <a href="book_appointment.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Book New Appointment
                    </a>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Appointment History</h2>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $conn->prepare("SELECT a.*, d.full_name, d.specialty 
                            FROM Appointments a 
                            JOIN Doctors d ON a.doctor_id = d.doctor_id 
                            WHERE a.patient_id = ?
                            ORDER BY a.appointment_date DESC, a.appointment_time DESC");
                    $stmt->bind_param("i", $patient_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if($result->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialty</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($row['full_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo htmlspecialchars($row['specialty']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($row['appointment_date'])); ?></td>
                                            <td><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if($row['status'] === 'Scheduled' || $row['status'] === 'Confirmed'): ?>
                                                        <button class="btn-icon" title="Cancel Appointment">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
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
                            <p style="color: var(--gray-500); margin-bottom: 2rem;">You haven't booked any appointments yet.</p>
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
