<?php
require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['delete_doctor'])) {
    $doctor_id = intval($_POST['doctor_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM Doctors WHERE doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
        
        if ($stmt->execute()) {
            $message = 'Doctor deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete doctor.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while deleting the doctor.';
        $message_type = 'error';
    }
}


if (isset($_POST['add_doctor'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $specialty = trim($_POST['specialty']);
    $phone = trim($_POST['phone']);
    $license_number = trim($_POST['license_number']);
    $experience_years = intval($_POST['experience_years']);
    $consultation_fee = floatval($_POST['consultation_fee']);
    $available_days = trim($_POST['available_days']);
    $available_start_time = $_POST['available_start_time'];
    $available_end_time = $_POST['available_end_time'];
    
    try {
        
        $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'Email already registered!';
            $message_type = 'error';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO Doctors (full_name, email, password, specialty, phone, license_number, experience_years, consultation_fee, available_days, available_start_time, available_end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssidsss", $full_name, $email, $hashed_password, $specialty, $phone, $license_number, $experience_years, $consultation_fee, $available_days, $available_start_time, $available_end_time);
            
            if ($stmt->execute()) {
                $doctor_id = $conn->insert_id;
                
                $auth->assignRole($doctor_id, 'Doctor', 'doctor');
                
                $message = 'Doctor added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add doctor.';
                $message_type = 'error';
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred while adding the doctor.';
        $message_type = 'error';
    }
}


$search = $_GET['search'] ?? '';
$specialty_filter = $_GET['specialty'] ?? '';


$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(d.full_name LIKE ? OR d.email LIKE ? OR d.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if (!empty($specialty_filter)) {
    $where_conditions[] = "d.specialty = ?";
    $params[] = $specialty_filter;
    $param_types .= "s";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";


$query = "
    SELECT 
        d.*,
        COUNT(DISTINCT a.appointment_id) as total_appointments,
        COUNT(DISTINCT th.history_id) as total_treatments,
        AVG(f.rating) as avg_rating,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments
    FROM Doctors d
    LEFT JOIN Appointments a ON d.doctor_id = a.doctor_id
    LEFT JOIN TreatmentHistory th ON d.doctor_id = th.doctor_id
    LEFT JOIN AppointmentFeedback f ON d.doctor_id = f.doctor_id
    $where_clause
    GROUP BY d.doctor_id
    ORDER BY d.created_at DESC
";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$doctors = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM Doctors");
$stmt->execute();
$total_doctors = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT specialty) as specialties FROM Doctors");
$stmt->execute();
$total_specialties = $stmt->get_result()->fetch_assoc()['specialties'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as active 
    FROM Doctors d 
    WHERE d.doctor_id IN (SELECT doctor_id FROM Appointments WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY))
");
$stmt->execute();
$active_doctors = $stmt->get_result()->fetch_assoc()['active'];


$stmt = $conn->prepare("SELECT DISTINCT specialty FROM Doctors ORDER BY specialty");
$stmt->execute();
$specialties = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - Admin Dashboard</title>
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
                    <a href="admin_doctors.php" class="nav-link active">
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
                    <h1 class="page-title">Manage Doctors</h1>
                    <p class="page-subtitle">View and manage all healthcare professionals</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="admin_dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="openAddDoctorModal()">
                        <i class="fas fa-plus"></i>
                        Add New Doctor
                    </button>
                </div>
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
                            <i class="fas fa-heartbeat"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Specialties</h3>
                        <div class="stat-number"><?php echo $total_specialties; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-star"></i>
                            Medical specialties
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
                        <h3>Active Doctors</h3>
                        <div class="stat-number"><?php echo $active_doctors; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-user-check"></i>
                            Last 30 days
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Search & Filter Doctors</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Search Doctors</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-input" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Specialty</label>
                                <select name="specialty" class="form-select">
                                    <option value="">All Specialties</option>
                                    <?php while ($spec = $specialties->fetch_assoc()): ?>
                                        <option value="<?php echo $spec['specialty']; ?>" <?php echo $specialty_filter === $spec['specialty'] ? 'selected' : ''; ?>>
                                            <?php echo $spec['specialty']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>
                                <a href="admin_doctors.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Doctors List</h2>
                </div>
                <div class="card-body">
                    <?php if ($doctors->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialty & Contact</th>
                                        <th>Experience</th>
                                        <th>Fee</th>
                                        <th>Appointments</th>
                                        <th>Treatments</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($doctor = $doctors->fetch_assoc()): 
                                        $is_active = $doctor['completed_appointments'] > 0;
                                        $avg_rating = $doctor['avg_rating'] ? number_format($doctor['avg_rating'], 1) : 'N/A';
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar" style="background: var(--primary-500);"><?php echo substr($doctor['full_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                                        <div class="patient-email"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.875rem;">
                                                    <div><strong><?php echo htmlspecialchars($doctor['specialty']); ?></strong></div>
                                                    <div><i class="fas fa-envelope" style="color: var(--gray-500); margin-right: 0.5rem;"></i><?php echo htmlspecialchars($doctor['email']); ?></div>
                                                    <div><i class="fas fa-phone" style="color: var(--gray-500); margin-right: 0.5rem;"></i><?php echo htmlspecialchars($doctor['phone']); ?></div>
                                                    <?php if ($doctor['license_number']): ?>
                                                        <div><i class="fas fa-id-card" style="color: var(--gray-500); margin-right: 0.5rem;"></i>License: <?php echo htmlspecialchars($doctor['license_number']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $doctor['experience_years'] ?? 0; ?> years
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--success-100); color: var(--success-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    $<?php echo number_format($doctor['consultation_fee'] ?? 0, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--info-100); color: var(--info-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $doctor['total_appointments']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--warning-100); color: var(--warning-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $doctor['total_treatments']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--purple-100); color: var(--purple-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    ⭐ <?php echo $avg_rating; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $is_active ? 'active' : 'inactive'; ?>">
                                                    <?php echo $is_active ? 'Active' : 'New'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="viewDoctorDetails(<?php echo $doctor['doctor_id']; ?>, '<?php echo htmlspecialchars($doctor['full_name']); ?>')">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </button>
                                                    <button class="btn btn-warning btn-sm" onclick="editDoctor(<?php echo $doctor['doctor_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                        Edit
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.');">
                                                        <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctor_id']; ?>">
                                                        <button type="submit" name="delete_doctor" class="btn btn-error btn-sm">
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
                            <i class="fas fa-user-md" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Doctors Found</h3>
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($search) || !empty($specialty_filter)): ?>
                                    No doctors match your search criteria.
                                <?php else: ?>
                                    No doctors are registered in the system yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="addDoctorModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Add New Doctor</h3>
                <button class="modal-close" onclick="closeAddDoctorModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addDoctorForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-input" placeholder="Dr. John Smith" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" placeholder="doctor@hospital.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-input" placeholder="Enter password" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Specialty *</label>
                            <input type="text" name="specialty" class="form-input" placeholder="Cardiology, Neurology, etc." required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-input" placeholder="+1 (555) 123-4567">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">License Number</label>
                            <input type="text" name="license_number" class="form-input" placeholder="MED123456">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-input" placeholder="5" min="0" max="50">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Consultation Fee ($)</label>
                            <input type="number" name="consultation_fee" class="form-input" placeholder="100.00" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Available Days</label>
                            <input type="text" name="available_days" class="form-input" placeholder="Monday, Wednesday, Friday">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="available_start_time" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="available_end_time" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="add_doctor" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Add Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal-overlay" id="doctorModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Doctor Details</h3>
                <button class="modal-close" onclick="closeDoctorModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="doctorModalContent">
                
            </div>
        </div>
    </div>

    <script>
        function openAddDoctorModal() {
            document.getElementById('addDoctorModal').classList.add('active');
        }

        function closeAddDoctorModal() {
            document.getElementById('addDoctorModal').classList.remove('active');
            document.getElementById('addDoctorForm').reset();
        }

        function viewDoctorDetails(doctorId, doctorName) {
            document.getElementById('doctorModalContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>${doctorName}</h4>
                    <p><em>Doctor details would be loaded here for doctor ID: ${doctorId}</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Complete professional information</li>
                        <li>Appointment statistics</li>
                        <li>Treatment history</li>
                        <li>Patient feedback and ratings</li>
                        <li>Schedule and availability</li>
                    </ul>
                </div>
            `;
            document.getElementById('doctorModal').classList.add('active');
        }

        function editDoctor(doctorId) {
            
            alert('Edit doctor functionality would open for doctor ID: ' + doctorId);
        }

        function closeDoctorModal() {
            document.getElementById('doctorModal').classList.remove('active');
        }

        
        document.getElementById('addDoctorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddDoctorModal();
            }
        });

        document.getElementById('doctorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDoctorModal();
            }
        });
    </script>
</body>
</html>