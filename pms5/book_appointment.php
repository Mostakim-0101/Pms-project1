<?php 
require_once 'includes/auth.php';


$auth->requireRole('Patient', 'login.php');

$user = $auth->getCurrentUser();
$patient_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['book'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    try {
        
        $stmt = $conn->prepare("SELECT appointment_id FROM Appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
        $stmt->bind_param("iss", $doctor_id, $date, $time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'This appointment slot is already booked. Please choose another time.';
            $message_type = 'error';
        } else {
            
            $stmt = $conn->prepare("INSERT INTO Appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'Scheduled')");
            $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time);
            
            if ($stmt->execute()) {
                $message = 'Appointment booked successfully! You can view it in "My Appointments".';
                $message_type = 'success';
            } else {
                $message = 'Failed to book appointment. Please try again.';
                $message_type = 'error';
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred while booking your appointment.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Pakenham Hospital</title>
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
                    <a href="book_appointment.php" class="nav-link active">
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
                    <h1 class="page-title">Book New Appointment</h1>
                    <p class="page-subtitle">Schedule your healthcare appointment with our specialists</p>
                </div>
                <a href="index.php" class="btn btn-outline">
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

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Appointment Details</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Select Doctor</label>
                                    <div class="input-group">
                                        <select name="doctor_id" class="form-select" required>
                                            <option value="">Choose a doctor...</option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT * FROM Doctors ORDER BY specialty, full_name");
                                            $stmt->execute();
                                            $doctors = $stmt->get_result();
                                            while($doc = $doctors->fetch_assoc()) {
                                                echo "<option value='{$doc['doctor_id']}'>{$doc['full_name']} - {$doc['specialty']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="input-icon">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    </div>
                                    <div class="form-help">Select the specialist you'd like to see</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Preferred Date</label>
                                    <div class="input-group">
                                        <input type="date" name="date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                                        <div class="input-icon">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                    </div>
                                    <div class="form-help">Select your preferred appointment date</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Preferred Time</label>
                                    <div class="input-group">
                                        <input type="time" name="time" class="form-input" required>
                                        <div class="input-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="form-help">Select your preferred time slot</div>
                                </div>
                            </div>

                            <div>
                                <div class="card" style="background: var(--primary-50); border: 1px solid var(--primary-200);">
                                    <div class="card-body">
                                        <h3 style="color: var(--primary-800); margin-bottom: 1rem;">
                                            <i class="fas fa-info-circle"></i> Booking Information
                                        </h3>
                                        <ul style="color: var(--gray-700); line-height: 1.8;">
                                            <li>Appointments are typically 30-60 minutes long</li>
                                            <li>Please arrive 15 minutes early for check-in</li>
                                            <li>Bring a valid ID and insurance card</li>
                                            <li>Cancel or reschedule at least 24 hours in advance</li>
                                            <li>Emergency appointments available for urgent cases</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" name="book" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-check"></i>
                                Book Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<?php
if (isset($_POST['book'])) {
    $pid = $_SESSION['patient_id'];
    $doc = $_POST['doctor_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $sql = "INSERT INTO Appointments(patient_id, doctor_id, appointment_date, appointment_time)
            VALUES('$pid', '$doc', '$date', '$time')";
    if ($conn->query($sql) === TRUE) {
        echo "Appointment booked successfully! <a href='my_appointments.php'>View My Appointments</a>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
