<?php
require_once 'includes/auth.php';


$auth->requireRole('Doctor', 'login.php');

$user = $auth->getCurrentUser();
$doctor_id = $user['user_id'];

$message = '';
$message_type = '';


$search = $_GET['search'] ?? '';


$where_conditions = ["a.doctor_id = ?"];
$params = [$doctor_id];
$param_types = "i";

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR p.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);


$stmt = $conn->prepare("
    SELECT 
        p.patient_id,
        p.full_name,
        p.email,
        p.phone,
        p.date_of_birth,
        p.address,
        MAX(a.appointment_date) as last_appointment,
        COUNT(a.appointment_id) as total_appointments,
        COUNT(th.history_id) as total_treatments,
        MAX(th.visit_date) as last_treatment
    FROM Patients p
    JOIN Appointments a ON p.patient_id = a.patient_id
    LEFT JOIN TreatmentHistory th ON p.patient_id = th.patient_id AND th.doctor_id = ?
    WHERE $where_clause
    GROUP BY p.patient_id, p.full_name, p.email, p.phone, p.date_of_birth, p.address
    ORDER BY last_appointment DESC
");
$params = array_merge([$doctor_id], $params);
$param_types = "i" . $param_types;
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$patients = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as total FROM Appointments WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT a.patient_id) as active 
    FROM Appointments a 
    WHERE a.doctor_id = ? AND a.appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$active_patients = $stmt->get_result()->fetch_assoc()['active'];

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT th.patient_id) as treated 
    FROM TreatmentHistory th 
    WHERE th.doctor_id = ?
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$treated_patients = $stmt->get_result()->fetch_assoc()['treated'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - Doctor Dashboard</title>
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
                    <a href="doctor_appointments.php" class="nav-link">
                        <i class="fas fa-calendar-check icon"></i>
                        <span>My Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_patients.php" class="nav-link active">
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
                    <h1 class="page-title">My Patients</h1>
                    <p class="page-subtitle">Manage your patient records and history</p>
                </div>
                <a href="doctor_dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            <!-- Stats Grid -->
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
                            <i class="fas fa-user-friends"></i>
                            All time patients
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
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
                            <i class="fas fa-file-medical"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Treated Patients</h3>
                        <div class="stat-number"><?php echo $treated_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-stethoscope"></i>
                            With treatment records
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Search Patients</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: flex; gap: 1rem; align-items: end;">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Search by Name or Email</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-input" placeholder="Enter patient name or email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>
                                <a href="doctor_patients.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Patients List -->
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
                                        <th>Last Visit</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($patient = $patients->fetch_assoc()): ?>
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
                                                $dob = new DateTime($patient['date_of_birth']);
                                                $age = $dob->diff(new DateTime())->y;
                                                echo date('M j, Y', strtotime($patient['date_of_birth'])) . '<br><small style="color: var(--gray-600);">Age: ' . $age . '</small>';
                                                ?>
                                            </td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $patient['total_appointments']; ?> appointment<?php echo $patient['total_appointments'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background: var(--success-100); color: var(--success-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo $patient['total_treatments']; ?> treatment<?php echo $patient['total_treatments'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($patient['last_appointment']): ?>
                                                    <?php echo date('M j, Y', strtotime($patient['last_appointment'])); ?>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500);">No visits</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="viewPatientHistory(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['full_name']); ?>')">
                                                        <i class="fas fa-history"></i>
                                                        History
                                                    </button>
                                                    <button class="btn btn-outline btn-sm" onclick="viewPatientDetails(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['full_name']); ?>')">
                                                        <i class="fas fa-eye"></i>
                                                        Details
                                                    </button>
                                                    <button class="btn btn-success btn-sm" onclick="addTreatment(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['full_name']); ?>')">
                                                        <i class="fas fa-plus"></i>
                                                        Treatment
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
                            <i class="fas fa-users" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Patients Found</h3>
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($search)): ?>
                                    No patients match your search criteria.
                                <?php else: ?>
                                    You haven't seen any patients yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Patient Details Modal -->
    <div class="modal-overlay" id="patientModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Patient Details</h3>
                <button class="modal-close" onclick="closePatientModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="patientModalContent">
                <!-- Patient details will be loaded here -->
            </div>
        </div>
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
        function viewPatientHistory(patientId, patientName) {
            
            alert('Patient history view would open for: ' + patientName + ' (ID: ' + patientId + ')');
        }

        function viewPatientDetails(patientId, patientName) {
            
            document.getElementById('patientModalContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>${patientName}</h4>
                    <p><em>Patient details would be loaded here for patient ID: ${patientId}</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Personal information</li>
                        <li>Medical history</li>
                        <li>Allergies</li>
                        <li>Current medications</li>
                        <li>Emergency contacts</li>
                    </ul>
                </div>
            `;
            document.getElementById('patientModal').classList.add('active');
        }

        function addTreatment(patientId, patientName) {
            document.getElementById('modal_patient_id').value = patientId;
            document.getElementById('modal_patient_name').value = patientName;
            document.getElementById('treatmentModal').classList.add('active');
        }

        function closePatientModal() {
            document.getElementById('patientModal').classList.remove('active');
        }

        function closeTreatmentModal() {
            document.getElementById('treatmentModal').classList.remove('active');
            document.getElementById('treatmentForm').reset();
        }

        
        document.getElementById('patientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePatientModal();
            }
        });

        document.getElementById('treatmentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTreatmentModal();
            }
        });
    </script>
</body>
</html>
