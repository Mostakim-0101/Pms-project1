<?php
require_once 'includes/auth.php';


$auth->requireRole('Admin', 'login.php');

$user = $auth->getCurrentUser();
$admin_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['update_ticket_status'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $new_status = $_POST['status'];
    $resolution_notes = trim($_POST['resolution_notes'] ?? '');
    
    try {
        $stmt = $conn->prepare("UPDATE SupportTickets SET status = ?, resolution_notes = ?, updated_at = CURRENT_TIMESTAMP, assigned_to = ? WHERE ticket_id = ?");
        $stmt->bind_param("ssii", $new_status, $resolution_notes, $admin_id, $ticket_id);
        
        if ($stmt->execute()) {
            $message = 'Ticket status updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update ticket status.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while updating the ticket.';
        $message_type = 'error';
    }
}


if (isset($_POST['update_ticket_priority'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $new_priority = $_POST['priority'];
    
    try {
        $stmt = $conn->prepare("UPDATE SupportTickets SET priority = ?, updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?");
        $stmt->bind_param("si", $new_priority, $ticket_id);
        
        if ($stmt->execute()) {
            $message = 'Ticket priority updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update ticket priority.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while updating the ticket priority.';
        $message_type = 'error';
    }
}


if (isset($_POST['assign_ticket'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $assigned_to = intval($_POST['assigned_to']);
    
    try {
        $stmt = $conn->prepare("UPDATE SupportTickets SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?");
        $stmt->bind_param("ii", $assigned_to, $ticket_id);
        
        if ($stmt->execute()) {
            $message = 'Ticket assigned successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to assign ticket.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while assigning the ticket.';
        $message_type = 'error';
    }
}


$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$category_filter = $_GET['category'] ?? '';


$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR t.subject LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($priority_filter)) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $priority_filter;
    $param_types .= "s";
}

if (!empty($category_filter)) {
    $where_conditions[] = "t.category = ?";
    $params[] = $category_filter;
    $param_types .= "s";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";


$query = "
    SELECT 
        t.*,
        p.full_name as patient_name,
        p.email as patient_email,
        p.phone as patient_phone,
        a.full_name as assigned_admin,
        (SELECT COUNT(*) FROM TicketMessages WHERE ticket_id = t.ticket_id) as message_count,
        (SELECT COUNT(*) FROM TicketMessages WHERE ticket_id = t.ticket_id AND is_internal = TRUE) as internal_message_count
    FROM SupportTickets t
    JOIN Patients p ON t.patient_id = p.patient_id
    LEFT JOIN Admins a ON t.assigned_to = a.admin_id
    $where_clause
    ORDER BY 
        CASE 
            WHEN t.status IN ('Open', 'In_Progress') THEN 0 
            ELSE 1 
        END,
        CASE t.priority
            WHEN 'Urgent' THEN 0
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            WHEN 'Low' THEN 3
        END,
        t.created_at DESC
";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM SupportTickets");
$stmt->execute();
$total_tickets = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as open 
    FROM SupportTickets 
    WHERE status IN ('Open', 'In_Progress')
");
$stmt->execute();
$open_tickets = $stmt->get_result()->fetch_assoc()['open'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as urgent 
    FROM SupportTickets 
    WHERE priority = 'Urgent' AND status IN ('Open', 'In_Progress')
");
$stmt->execute();
$urgent_tickets = $stmt->get_result()->fetch_assoc()['urgent'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as resolved 
    FROM SupportTickets 
    WHERE status = 'Resolved'
");
$stmt->execute();
$resolved_tickets = $stmt->get_result()->fetch_assoc()['resolved'];


$stmt = $conn->prepare("SELECT admin_id, full_name FROM Admins ORDER BY full_name");
$stmt->execute();
$admins = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin Dashboard</title>
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
                    <a href="admin_support.php" class="nav-link active">
                        <i class="fas fa-headset icon"></i>
                        <span>Support Tickets</span>
                        <?php if($open_tickets > 0): ?>
                            <span class="badge"><?php echo $open_tickets; ?></span>
                        <?php endif; ?>
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
                    <h1 class="page-title">Support Tickets</h1>
                    <p class="page-subtitle">Manage and resolve patient support requests</p>
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
                            <i class="fas fa-headset"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Tickets</h3>
                        <div class="stat-number"><?php echo $total_tickets; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-ticket"></i>
                            All support requests
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Open Tickets</h3>
                        <div class="stat-number"><?php echo $open_tickets; ?></div>
                        <div class="stat-change <?php echo $open_tickets > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $open_tickets > 0 ? 'exclamation' : 'check'; ?>"></i>
                            Pending resolution
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
                        <h3>Urgent Tickets</h3>
                        <div class="stat-number"><?php echo $urgent_tickets; ?></div>
                        <div class="stat-change <?php echo $urgent_tickets > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $urgent_tickets > 0 ? 'exclamation' : 'check'; ?>"></i>
                            High priority
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Resolved</h3>
                        <div class="stat-number"><?php echo $resolved_tickets; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-check"></i>
                            Successfully resolved
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filter Support Tickets</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-container" style="max-width: none; padding: 0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-input" placeholder="Search by patient or subject..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Open" <?php echo $status_filter === 'Open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="In_Progress" <?php echo $status_filter === 'In_Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All Priorities</option>
                                    <option value="Low" <?php echo $priority_filter === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo $priority_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="High" <?php echo $priority_filter === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Urgent" <?php echo $priority_filter === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <option value="General" <?php echo $category_filter === 'General' ? 'selected' : ''; ?>>General</option>
                                    <option value="Technical" <?php echo $category_filter === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                                    <option value="Medical" <?php echo $category_filter === 'Medical' ? 'selected' : ''; ?>>Medical</option>
                                    <option value="Billing" <?php echo $category_filter === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                                    <option value="Appointment" <?php echo $category_filter === 'Appointment' ? 'selected' : ''; ?>>Appointment</option>
                                    <option value="Other" <?php echo $category_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="admin_support.php" class="btn btn-outline">
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
                    <h2 class="card-title">Support Tickets</h2>
                </div>
                <div class="card-body">
                    <?php if ($tickets->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Patient</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Messages</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ticket = $tickets->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $ticket['ticket_id']; ?></strong></td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-avatar"><?php echo substr($ticket['patient_name'], 0, 2); ?></div>
                                                    <div class="patient-details">
                                                        <div class="patient-name"><?php echo htmlspecialchars($ticket['patient_name']); ?></div>
                                                        <div class="patient-email"><?php echo htmlspecialchars($ticket['patient_email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px;">
                                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($ticket['category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($ticket['priority']); ?>">
                                                    <?php echo $ticket['priority']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '', $ticket['status'])); ?>">
                                                    <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($ticket['assigned_admin']): ?>
                                                    <?php echo htmlspecialchars($ticket['assigned_admin']); ?>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-500);">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="background: var(--gray-100); color: var(--gray-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem;">
                                                    <?php echo $ticket['message_count']; ?> message<?php echo $ticket['message_count'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?><br>
                                                <small style="color: var(--gray-600);"><?php echo date('g:i A', strtotime($ticket['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <button class="btn btn-primary btn-sm" onclick="viewTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </button>
                                                    <button class="btn btn-outline btn-sm" onclick="updateTicketStatus(<?php echo $ticket['ticket_id']; ?>, '<?php echo $ticket['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                        Status
                                                    </button>
                                                    <button class="btn btn-outline btn-sm" onclick="assignTicket(<?php echo $ticket['ticket_id']; ?>, <?php echo $ticket['assigned_to'] ?? 'null'; ?>)">
                                                        <i class="fas fa-user-plus"></i>
                                                        Assign
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
                            <i class="fas fa-ticket-alt" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
                            <h3 style="color: var(--gray-600); margin-bottom: 1rem;">No Support Tickets Found</h3>
                            <p style="color: var(--gray-500);">
                                <?php if (!empty($search) || !empty($status_filter) || !empty($priority_filter) || !empty($category_filter)): ?>
                                    No tickets match your current filters.
                                <?php else: ?>
                                    No support tickets have been submitted yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    
    <div class="modal-overlay" id="ticketModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Support Ticket Details</h3>
                <button class="modal-close" onclick="closeTicketModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="ticketContent">
                
            </div>
        </div>
    </div>

    
    <div class="modal-overlay" id="statusModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Update Ticket Status</h3>
                <button class="modal-close" onclick="closeStatusModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="statusForm">
                    <input type="hidden" name="ticket_id" id="status_ticket_id">
                    
                    <div class="form-group">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Open">Open</option>
                            <option value="In_Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Resolution Notes</label>
                        <textarea name="resolution_notes" class="form-textarea" rows="3" placeholder="Enter resolution notes..."></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="update_ticket_status" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal-overlay" id="assignModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Assign Ticket</h3>
                <button class="modal-close" onclick="closeAssignModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="assignForm">
                    <input type="hidden" name="ticket_id" id="assign_ticket_id">
                    
                    <div class="form-group">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">Select admin...</option>
                            <?php while($admin = $admins->fetch_assoc()): ?>
                                <option value="<?php echo $admin['admin_id']; ?>">
                                    <?php echo htmlspecialchars($admin['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="assign_ticket" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i>
                            Assign Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewTicket(ticketId) {
            
            document.getElementById('ticketContent').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h4>Support Ticket #${ticketId}</h4>
                    <p><em>Full ticket details would be loaded here</em></p>
                    <p>This would include:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Complete conversation history</li>
                        <li>Patient information</li>
                        <li>Ticket status and priority</li>
                        <li>All messages and replies</li>
                        <li>Resolution notes</li>
                    </ul>
                </div>
            `;
            document.getElementById('ticketModal').classList.add('active');
        }

        function closeTicketModal() {
            document.getElementById('ticketModal').classList.remove('active');
        }

        function updateTicketStatus(ticketId, currentStatus) {
            document.getElementById('status_ticket_id').value = ticketId;
            document.querySelector('#statusForm select[name="status"]').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        function assignTicket(ticketId, currentAssigned) {
            document.getElementById('assign_ticket_id').value = ticketId;
            if (currentAssigned) {
                document.querySelector('#assignForm select[name="assigned_to"]').value = currentAssigned;
            }
            document.getElementById('assignModal').classList.add('active');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
        }

        
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTicketModal();
            }
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        document.getElementById('assignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssignModal();
            }
        });
    </script>
</body>
</html>
