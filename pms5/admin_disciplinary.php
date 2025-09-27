<?php
require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['add_disciplinary'])) {
    $patient_id = intval($_POST['patient_id']);
    $violation_type = trim($_POST['violation_type']);
    $description = trim($_POST['description']);
    $severity = $_POST['severity'];
    $action_taken = trim($_POST['action_taken']);
    
    if (empty($violation_type) || empty($description)) {
        $message = 'Violation type and description are required fields.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO DisciplinaryRecords (patient_id, violation_type, description, severity, action_taken, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $patient_id, $violation_type, $description, $severity, $action_taken, $admin_id);
            
            if ($stmt->execute()) {
                $message = 'Disciplinary record added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add disciplinary record.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'An error occurred while adding the disciplinary record.';
            $message_type = 'error';
        }
    }
}


if (isset($_POST['update_disciplinary'])) {
    $record_id = intval($_POST['record_id']);
    $violation_type = trim($_POST['violation_type']);
    $description = trim($_POST['description']);
    $severity = $_POST['severity'];
    $action_taken = trim($_POST['action_taken']);
    
    if (empty($violation_type) || empty($description)) {
        $message = 'Violation type and description are required fields.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE DisciplinaryRecords SET violation_type = ?, description = ?, severity = ?, action_taken = ?, updated_at = CURRENT_TIMESTAMP WHERE record_id = ?");
            $stmt->bind_param("ssssi", $violation_type, $description, $severity, $action_taken, $record_id);
            
            if ($stmt->execute()) {
                $message = 'Disciplinary record updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update disciplinary record.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'An error occurred while updating the disciplinary record.';
            $message_type = 'error';
        }
    }
}


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $record_id = intval($_GET['delete']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM DisciplinaryRecords WHERE record_id = ?");
        $stmt->bind_param("i", $record_id);
        
        if ($stmt->execute()) {
            $message = 'Disciplinary record deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete disciplinary record.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while deleting the disciplinary record.';
        $message_type = 'error';
    }
}


$search = $_GET['search'] ?? '';
$severity_filter = $_GET['severity'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';


$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR d.violation_type LIKE ? OR d.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if (!empty($severity_filter)) {
    $where_conditions[] = "d.severity = ?";
    $params[] = $severity_filter;
    $param_types .= "s";
}

if (!empty($date_from)) {
    $where_conditions[] = "d.created_at >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "d.created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $param_types .= "s";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";


$query = "
    SELECT 
        d.*,
        p.full_name as patient_name,
        p.email as patient_email,
        a.full_name as recorded_by_name
    FROM DisciplinaryRecords d
    JOIN Patients p ON d.patient_id = p.patient_id
    LEFT JOIN Admins a ON d.recorded_by = a.admin_id
    $where_clause
    ORDER BY d.created_at DESC
";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM DisciplinaryRecords");
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as critical FROM DisciplinaryRecords WHERE severity = 'Critical'");
$stmt->execute();
$critical_records = $stmt->get_result()->fetch_assoc()['critical'];

$stmt = $conn->prepare("SELECT COUNT(*) as this_month FROM DisciplinaryRecords WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->execute();
$this_month_records = $stmt->get_result()->fetch_assoc()['this_month'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as unique_patients FROM DisciplinaryRecords");
$stmt->execute();
$unique_patients = $stmt->get_result()->fetch_assoc()['unique_patients'];


$stmt = $conn->prepare("SELECT patient_id, full_name FROM Patients ORDER BY full_name");
$stmt->execute();
$patients = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary Records - Admin Dashboard</title>
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
                    <a href="admin_disciplinary.php" class="nav-link active">
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
                    <h1 class="page-title">Disciplinary Records</h1>
                    <p class="page-subtitle">Manage patient disciplinary records and violations</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-primary" onclick="openAddDisciplinaryModal()">
                        <i class="fas fa-plus"></i>
                        Add Record
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-outline">
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
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Records</h3>
                        <div class="stat-number"><?php echo $total_records; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-file-alt"></i>
                            All disciplinary records
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Critical Violations</h3>
                        <div class="stat-number"><?php echo $critical_records; ?></div>
                        <div class="stat-change <?php echo $critical_records > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $critical_records > 0 ? 'exclamation' : 'check'; ?>"></i>
                            High severity
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
                        <div class="stat-number"><?php echo $this_month_records; ?></div>
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
                            With violations
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filter Disciplinary Records</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-input" placeholder="Search by patient or violation..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Severity</label>
                                <select name="severity" class="form-select">
                                    <option value="">All Severities</option>
                                    <option value="Low" <?php echo $severity_filter === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo $severity_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="High" <?php echo $severity_filter === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Critical" <?php echo $severity_filter === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-input" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-input" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="admin_disciplinary.php" class="btn btn-outline">
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
                    <h2 class="card-title">Disciplinary Records</h2>
                </div>
                <div class="card-body">
                    <?php if ($records->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Record ID</th>
                                        <th>Patient</th>
                                        <th>Violation Type</th>
                                        <th>Severity</th>
                                        <th>Description</th>
                                        <th>Action Taken</th>
                                        <th>Recorded By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $records->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $record['record_id']; ?></strong></td>
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
                                                <div style="max-width: 150px;">
                                                    <?php echo htmlspecialchars($record['violation_type']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($record['severity']); ?>">
                                                    <?php echo $record['severity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($record['description']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($record['action_taken']): ?>
                                                    <div style="max-width: 200px; font-size: 0.875rem;">
                                                        <?php echo htmlspecialchars($record['action_taken']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500); font-size: 0.875rem;">No action taken</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($record['recorded_by_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($record['created_at'])); ?><br>
                                                <small style="color: var(--gray-600);"><?php echo date('g:i A', strtotime($record['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="viewRecord(<?php echo $record['record_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </button>
                                                    <button class="btn btn-outline btn-sm" onclick="editRecord(<?php echo $record['record_id']; ?>, '<?php echo htmlspecialchars($record['violation_type']); ?>', '<?php echo htmlspecialchars($record['description']); ?>', '<?php echo $record['severity']; ?>', '<?php echo htmlspecialchars($record['action_taken']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                        Edit
                                                    </button>
                                                    <a href="admin_disciplinary.php?delete=<?php echo $record['record_id']; ?>" class="btn btn-error btn-sm" onclick="return confirm('Are you sure you want to delete this disciplinary record?')">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Disciplinary Records Found</h3>
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($search) || !empty($severity_filter) || !empty($date_from) || !empty($date_to)): ?>
                                    No records match your current filters.
                                <?php else: ?>
                                    No disciplinary records have been created yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="addDisciplinaryModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Disciplinary Record</h3>
                <button class="modal-close" onclick="closeAddDisciplinaryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addDisciplinaryForm">
                    <div class="form-group">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Select patient...</option>
                            <?php while($patient = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo htmlspecialchars($patient['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Violation Type *</label>
                        <input type="text" name="violation_type" class="form-input" placeholder="e.g., Late arrival, No-show, Inappropriate behavior" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-textarea" rows="3" placeholder="Detailed description of the violation..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Action Taken</label>
                        <textarea name="action_taken" class="form-textarea" rows="2" placeholder="Action taken in response to the violation..."></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="add_disciplinary" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Save Disciplinary Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal-overlay" id="editDisciplinaryModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Disciplinary Record</h3>
                <button class="modal-close" onclick="closeEditDisciplinaryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editDisciplinaryForm">
                    <input type="hidden" name="record_id" id="edit_record_id">
                    
                    <div class="form-group">
                        <label class="form-label">Violation Type *</label>
                        <input type="text" name="violation_type" id="edit_violation_type" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea name="description" id="edit_description" class="form-textarea" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Severity</label>
                        <select name="severity" id="edit_severity" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Action Taken</label>
                        <textarea name="action_taken" id="edit_action_taken" class="form-textarea" rows="2"></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="update_disciplinary" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Update Disciplinary Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal-overlay" id="viewRecordModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Disciplinary Record Details</h3>
                <button class="modal-close" onclick="closeViewRecordModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewRecordContent">
                
            </div>
        </div>
    </div>

    <script>
        function openAddDisciplinaryModal() {
            document.getElementById('addDisciplinaryModal').classList.add('active');
        }

        function closeAddDisciplinaryModal() {
            document.getElementById('addDisciplinaryModal').classList.remove('active');
            document.getElementById('addDisciplinaryForm').reset();
        }

        function editRecord(recordId, violationType, description, severity, actionTaken) {
            document.getElementById('edit_record_id').value = recordId;
            document.getElementById('edit_violation_type').value = violationType;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_severity').value = severity;
            document.getElementById('edit_action_taken').value = actionTaken;
            document.getElementById('editDisciplinaryModal').classList.add('active');
        }

        function closeEditDisciplinaryModal() {
            document.getElementById('editDisciplinaryModal').classList.remove('active');
        }

        function viewRecord(recordId) {
            
            document.getElementById('viewRecordContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>Disciplinary Record #${recordId}</h4>
                    <p><em>Full record details would be loaded here</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Complete violation details</li>
                        <li>Patient information</li>
                        <li>Severity and impact</li>
                        <li>Action taken and follow-up</li>
                        <li>Record history and updates</li>
                    </ul>
                </div>
            `;
            document.getElementById('viewRecordModal').classList.add('active');
        }

        function closeViewRecordModal() {
            document.getElementById('viewRecordModal').classList.remove('active');
        }

        
        document.getElementById('addDisciplinaryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddDisciplinaryModal();
            }
        });

        document.getElementById('editDisciplinaryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditDisciplinaryModal();
            }
        });

        document.getElementById('viewRecordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeViewRecordModal();
            }
        });
    </script>
</body>
</html>
