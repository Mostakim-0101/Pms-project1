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
    $visit_date = $_POST['visit_date'] ?: date('Y-m-d');
    
    if (empty($diagnosis) || empty($treatment)) {
        $message = 'Diagnosis and treatment are required fields.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO TreatmentHistory (patient_id, doctor_id, visit_date, diagnosis, treatment, notes, prescription, follow_up_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssss", $patient_id, $doctor_id, $visit_date, $diagnosis, $treatment, $notes, $prescription, $follow_up_date);
            
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


$filter_patient = $_GET['patient'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';


$where_conditions = ["th.doctor_id = ?"];
$params = [$doctor_id];
$param_types = "i";

if (!empty($filter_patient)) {
    $where_conditions[] = "(p.full_name LIKE ? OR p.email LIKE ?)";
    $params[] = "%$filter_patient%";
    $params[] = "%$filter_patient%";
    $param_types .= "ss";
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "th.visit_date >= ?";
    $params[] = $filter_date_from;
    $param_types .= "s";
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "th.visit_date <= ?";
    $params[] = $filter_date_to;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);


$stmt = $conn->prepare("
    SELECT 
        th.*,
        p.full_name as patient_name,
        p.email as patient_email
    FROM TreatmentHistory th
    JOIN Patients p ON th.patient_id = p.patient_id
    WHERE $where_clause
    ORDER BY th.visit_date DESC, th.created_at DESC
");
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$treatment_records = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM TreatmentHistory WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_treatments = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as this_month FROM TreatmentHistory WHERE doctor_id = ? AND visit_date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$this_month_treatments = $stmt->get_result()->fetch_assoc()['this_month'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as unique_patients FROM TreatmentHistory WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$unique_patients = $stmt->get_result()->fetch_assoc()['unique_patients'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment Records - Doctor Dashboard</title>
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
                    <a href="doctor_patients.php" class="nav-link">
                        <i class="fas fa-users icon"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_treatment.php" class="nav-link active">
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
                    <h1 class="page-title">Treatment Records</h1>
                    <p class="page-subtitle">Manage patient treatment history and medical records</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-primary" onclick="openAddTreatmentModal()">
                        <i class="fas fa-plus"></i>
                        Add Treatment Record
                    </button>
                    <a href="doctor_dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
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
                            <i class="fas fa-file-medical"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Treatments</h3>
                        <div class="stat-number"><?php echo $total_treatments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            All time records
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-month"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>This Month</h3>
                        <div class="stat-number"><?php echo $this_month_treatments; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar"></i>
                            Current month
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
                        <h3>Unique Patients</h3>
                        <div class="stat-number"><?php echo $unique_patients; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-user-friends"></i>
                            Treated patients
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filter Treatment Records</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Patient</label>
                                <input type="text" name="patient" class="form-input" placeholder="Search by name or email" value="<?php echo htmlspecialchars($filter_patient); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-input" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-input" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="doctor_treatment.php" class="btn btn-outline">
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
                    <h2 class="card-title">Treatment Records</h2>
                </div>
                <div class="card-body">
                    <?php if ($treatment_records->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient</th>
                                        <th>Diagnosis</th>
                                        <th>Treatment</th>
                                        <th>Prescription</th>
                                        <th>Follow-up</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $treatment_records->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></strong><br>
                                                <small style="color: var(--gray-600);"><?php echo date('g:i A', strtotime($record['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($record['patient_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($record['patient_name']); ?></div>
                                                        <div class="patient-email"><?php echo htmlspecialchars($record['patient_email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($record['diagnosis']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($record['treatment']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($record['prescription']): ?>
                                                    <div style="max-width: 200px; font-size: 0.875rem;">
                                                        <?php echo htmlspecialchars($record['prescription']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500); font-size: 0.875rem;">No prescription</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['follow_up_date']): ?>
                                                    <?php 
                                                    $follow_up = new DateTime($record['follow_up_date']);
                                                    $today = new DateTime();
                                                    $is_overdue = $follow_up < $today;
                                                    ?>
                                                    <span style="color: <?php echo $is_overdue ? 'var(--error)' : 'var(--success)'; ?>; font-size: 0.875rem;">
                                                        <?php echo date('M j, Y', strtotime($record['follow_up_date'])); ?>
                                                        <?php if ($is_overdue): ?>
                                                            <br><small style="color: var(--error);">Overdue</small>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500); font-size: 0.875rem;">No follow-up</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="viewTreatmentDetails(<?php echo $record['history_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-file-medical" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Treatment Records Found</h3>
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($filter_patient) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
                                    No treatment records match your current filters.
                                <?php else: ?>
                                    You haven't added any treatment records yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="addTreatmentModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Treatment Record</h3>
                <button class="modal-close" onclick="closeAddTreatmentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addTreatmentForm">
                    <div class="form-group">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Select patient...</option>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT DISTINCT p.patient_id, p.full_name 
                                FROM Patients p 
                                JOIN Appointments a ON p.patient_id = a.patient_id 
                                WHERE a.doctor_id = ? 
                                ORDER BY p.full_name
                            ");
                            $stmt->bind_param("i", $doctor_id);
                            $stmt->execute();
                            $patients = $stmt->get_result();
                            while($patient = $patients->fetch_assoc()) {
                                echo "<option value='{$patient['patient_id']}'>{$patient['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Visit Date</label>
                        <input type="date" name="visit_date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
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

    
    <div class="modal-overlay" id="treatmentDetailsModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Treatment Record Details</h3>
                <button class="modal-close" onclick="closeTreatmentDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="treatmentDetailsContent">
                
            </div>
        </div>
    </div>

    <script>
        function openAddTreatmentModal() {
            document.getElementById('addTreatmentModal').classList.add('active');
        }

        function closeAddTreatmentModal() {
            document.getElementById('addTreatmentModal').classList.remove('active');
            document.getElementById('addTreatmentForm').reset();
        }

        function viewTreatmentDetails(historyId) {
            
            document.getElementById('treatmentDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>Treatment Record #${historyId}</h4>
                    <p><em>Full treatment details would be loaded here</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Complete diagnosis details</li>
                        <li>Full treatment plan</li>
                        <li>Detailed notes</li>
                        <li>Prescription information</li>
                        <li>Follow-up instructions</li>
                    </ul>
                </div>
            `;
            document.getElementById('treatmentDetailsModal').classList.add('active');
        }

        function closeTreatmentDetailsModal() {
            document.getElementById('treatmentDetailsModal').classList.remove('active');
        }

        
        document.getElementById('addTreatmentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddTreatmentModal();
            }
        });

        document.getElementById('treatmentDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTreatmentDetailsModal();
            }
        });
    </script>
</body>
</html>
