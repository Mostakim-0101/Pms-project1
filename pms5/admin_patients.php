<?php
require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['delete_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM Patients WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        
        if ($stmt->execute()) {
            $message = 'Patient deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete patient.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while deleting the patient.';
        $message_type = 'error';
    }
}


$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';


$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}


if ($status_filter === 'active') {
    $where_conditions[] = "p.patient_id IN (SELECT patient_id FROM Appointments WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH))";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "p.patient_id NOT IN (SELECT patient_id FROM Appointments WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH))";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";


$query = "
    SELECT 
        p.*,
        COUNT(DISTINCT a.appointment_id) as total_appointments,
        COUNT(DISTINCT th.history_id) as total_treatments,
        MAX(a.appointment_date) as last_appointment,
        (SELECT COUNT(*) FROM SupportTickets WHERE patient_id = p.patient_id) as total_tickets
    FROM Patients p
    LEFT JOIN Appointments a ON p.patient_id = a.patient_id
    LEFT JOIN TreatmentHistory th ON p.patient_id = th.patient_id
    $where_clause
    GROUP BY p.patient_id
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$patients = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM Patients");
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT p.patient_id) as active 
    FROM Patients p 
    WHERE p.patient_id IN (SELECT patient_id FROM Appointments WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH))
");
$stmt->execute();
$active_patients = $stmt->get_result()->fetch_assoc()['active'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as new 
    FROM Patients 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$new_patients = $stmt->get_result()->fetch_assoc()['new'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - Admin Dashboard</title>
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
                    <a href="admin_patients.php" class="nav-link active">
                        <極品 class="fas fa-users icon"></極品>
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
                   极客 <a href="admin_appointments.php" class="nav-link">
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
                    <a href="admin_disciplinary.php" class极客="nav-link">
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
                    <h1 class="page-title">Manage Patients</h1>
                    <p class="page-subtitle">View and manage all patient records</p>
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
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Patients</h3>
                        <div class="stat-number"><?php echo $total_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-user-plus"></i>
                            All registered patients
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class极客="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Active Patients</h3>
                        <div class="stat-number"><?php echo $active_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-heartbeat"></i>
                            Last 6 months
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>New Patients</h3>
                        <div class="stat-number"><?php echo $new_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar"></i>
                            Last 30 days
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Search & Filter Patients</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Search Patients</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-input" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Patients</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>
                                <a href="admin_patients.php" class="btn btn-outline">
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
                    <h2 class="card-title">Patients List</h2>
                </div>
                <div class="card-body">
                    <?php if ($patients->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Contact Info</th>
                                        <th>Date of Birth</th>
                                        <th>Appointments</th>
                                        <th>Treatments</th>
                                        <th>Support Tickets</th>
                                        <th>Last Activity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($patient = $patients->fetch_assoc()): 
                                        $last_activity = max(
                                            strtotime($patient['last_appointment'] ?? '0000-00-00'),
                                            strtotime($patient['created_at'])
                                        );
                                        $is_active = $last_activity >= strtotime('-6 months');
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($patient['full_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($patient['full_name']); ?></div>
                                                        <div class="patient-email"><?php echo htmlspecialchars($patient['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.875rem;">
                                                    <div><i class="fas fa-envelope" style="color: var(--gray-500); margin-right: 0.5rem;"></i><?php echo htmlspecialchars($patient['email']); ?></div>
                                                    <div><i class="fas fa-phone" style="color: var(--gray-500); margin-right: 0.5rem;"></i><?php echo htmlspecialchars($patient['phone']); ?></div>
                                                    <?php if ($patient['address']): ?>
                                                        <div><i class="fas fa-map-marker-alt" style="color: var(--gray-500); margin-right: 0.5rem;"></i><?php echo htmlspecialchars($patient['address']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($patient['date_of_birth']) {
                                                    $dob = new DateTime($patient['date_of_birth']);
                                                    $age = $dob->diff(new DateTime())->y;
                                                    echo date('M j, Y', strtotime($patient['date_of_birth'])) . '<br><small style="color: var(--gray-600);">Age: ' . $age . '</small>';
                                                } else {
                                                    echo '<span style="color: var(--gray-500);">Not provided</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $patient['total_appointments']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--success-100); color: var(--success-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $patient['total_treatments']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--warning-100); color: var(--warning-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $patient['total_tickets']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($patient['last_appointment']): ?>
                                                    <?php echo date('M j, Y', strtotime($patient['last_appointment'])); ?>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500);">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $is_active ? 'active' : 'inactive'; ?>">
                                                    <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="viewPatientDetails(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['full_name']); ?>')">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </button>
                                                    <button class="btn btn-warning btn-sm" onclick="editPatient(<?php echo $patient['patient_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                        Edit
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this patient? This action cannot be undone.');">
                                                        <input type="hidden" name="patient_id" value="<?php echo $patient['patient_id']; ?>">
                                                        <button type="submit" name="delete_patient" class="btn btn-error btn-sm">
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
                            <i class="fas fa-users" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Patients Found</h3>
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($search)): ?>
                                    No patients match your search criteria.
                                <?php else: ?>
                                    No patients are registered in the system yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="patientModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Patient Details</h3>
                <button class="modal-close" onclick="closePatientModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="patientModalContent">
                
            </div>
        </div>
    </div>

    <script>
        function viewPatientDetails(patientId, patientName) {
            
            document.getElementById('patientModalContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>${patientName}</h4>
                    <p><em>Patient details would be loaded here for patient ID: ${patientId}</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Complete personal information</li>
                        <li>Full medical history</li>
                        <li>Appointment history</li>
                        <li>Treatment records</li>
                        <li>Support ticket history</li>
                        <li>Emergency contacts</li>
                    </ul>
                </div>
            `;
            document.getElementById('patientModal').classList.add('active');
        }

        function editPatient(patientId) {
            
            alert('Edit patient functionality would open for patient ID: ' + patientId);
        }

        function closePatientModal() {
            document.getElementById('patientModal').classList.remove('active');
        }

        
        document.getElementById('patientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePatientModal();
            }
        });
    </script>
</body>
</html>