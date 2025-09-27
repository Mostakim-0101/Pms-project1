<?php
require_once 'includes/auth.php';


$auth->requireRole('Doctor', 'login.php');

$user = $auth->getCurrentUser();
$doctor_id = $user['user_id'];

$message = '';
$message_type = '';


if (isset($_POST['reply_ticket'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $reply_message = trim($_POST['reply_message']);
    
    if (empty($reply_message)) {
        $message = 'Please enter a message.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO TicketMessages (ticket_id, sender_id, sender_type, message) VALUES (?, ?, 'Doctor', ?)");
            $stmt->bind_param("iis", $ticket_id, $doctor_id, $reply_message);
            
            if ($stmt->execute()) {
                
                $stmt = $conn->prepare("UPDATE SupportTickets SET status = 'In_Progress', updated_at = NOW() WHERE ticket_id = ?");
                $stmt->bind_param("i", $ticket_id);
                $stmt->execute();
                
                $message = 'Reply sent successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to send reply. Please try again.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'An error occurred while sending your reply.';
            $message_type = 'error';
        }
    }
}


if (isset($_POST['update_ticket_status'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE SupportTickets SET status = ?, updated_at = NOW() WHERE ticket_id = ?");
        $stmt->bind_param("si", $new_status, $ticket_id);
        
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


$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_category = $_GET['category'] ?? '';


$where_conditions = ["1=1"]; 
$params = [];
$param_types = "";

if (!empty($filter_status)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

if (!empty($filter_priority)) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $filter_priority;
    $param_types .= "s";
}

if (!empty($filter_category)) {
    $where_conditions[] = "t.category = ?";
    $params[] = $filter_category;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);


$stmt = $conn->prepare("
    SELECT 
        t.*,
        p.full_name as patient_name,
        p.email as patient_email,
        (SELECT COUNT(*) FROM TicketMessages tm WHERE tm.ticket_id = t.ticket_id) as message_count,
        (SELECT tm.message FROM TicketMessages tm WHERE tm.ticket_id = t.ticket_id ORDER BY tm.created_at DESC LIMIT 1) as last_message
    FROM SupportTickets t
    JOIN Patients p ON t.patient_id = p.patient_id
    WHERE $where_clause
    ORDER BY t.priority DESC, t.created_at DESC
");
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM SupportTickets");
$stmt->execute();
$total_tickets = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as open FROM SupportTickets WHERE status IN ('Open', 'In_Progress')");
$stmt->execute();
$open_tickets = $stmt->get_result()->fetch_assoc()['open'];

$stmt = $conn->prepare("SELECT COUNT(*) as resolved FROM SupportTickets WHERE status IN ('Resolved', 'Closed')");
$stmt->execute();
$resolved_tickets = $stmt->get_result()->fetch_assoc()['resolved'];

$stmt = $conn->prepare("SELECT COUNT(*) as urgent FROM SupportTickets WHERE priority = 'Urgent' AND status IN ('Open', 'In_Progress')");
$stmt->execute();
$urgent_tickets = $stmt->get_result()->fetch_assoc()['urgent'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Doctor Dashboard</title>
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
                    <a href="doctor_treatment.php" class="nav-link">
                        <i class="fas fa-file-medical icon"></i>
                        <span>Treatment Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_support.php" class="nav-link active">
                        <i class="fas fa-headset icon"></i>
                        <span>Support Tickets</span>
                        <?php if($open_tickets > 0): ?>
                            <span class="badge"><?php echo $open_tickets; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>

        
        <main class="main-content">
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Support Tickets</h1>
                    <p class="page-subtitle">Help patients with their support requests</p>
                </div>
                <a href="doctor_dashboard.php" class="btn btn-outline">
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
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Total Tickets</h3>
                        <div class="stat-number"><?php echo $total_tickets; ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            All support requests
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

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3>Urgent</h3>
                        <div class="stat-number"><?php echo $urgent_tickets; ?></div>
                        <div class="stat-change <?php echo $urgent_tickets > 0 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-<?php echo $urgent_tickets > 0 ? 'exclamation' : 'check'; ?>"></i>
                            High priority
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
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Open" <?php echo $filter_status === 'Open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="In_Progress" <?php echo $filter_status === 'In_Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Resolved" <?php echo $filter_status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="Closed" <?php echo $filter_status === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All Priorities</option>
                                    <option value="Low" <?php echo $filter_priority === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo $filter_priority === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="High" <?php echo $filter_priority === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Urgent" <?php echo $filter_priority === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <option value="General" <?php echo $filter_category === 'General' ? 'selected' : ''; ?>>General</option>
                                    <option value="Technical" <?php echo $filter_category === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                                    <option value="Medical" <?php echo $filter_category === 'Medical' ? 'selected' : ''; ?>>Medical</option>
                                    <option value="Billing" <?php echo $filter_category === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                                    <option value="Appointment" <?php echo $filter_category === 'Appointment' ? 'selected' : ''; ?>>Appointment</option>
                                    <option value="Other" <?php echo $filter_category === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="doctor_support.php" class="btn btn-outline">
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
                                                <span style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500;">
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
                                                    <button class="btn btn-outline btn-sm" onclick="replyTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-reply"></i>
                                                        Reply
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
                                <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category)): ?>
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

    
    <div class="modal-overlay" id="replyModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Reply to Ticket</h3>
                <button class="modal-close" onclick="closeReplyModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="replyForm">
                    <input type="hidden" name="ticket_id" id="reply_ticket_id">
                    
                    <div class="form-group">
                        <label class="form-label">Your Reply *</label>
                        <textarea name="reply_message" class="form-textarea" rows="5" placeholder="Enter your reply to the patient..." required></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" name="reply_ticket" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i>
                            Send Reply
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

        function replyTicket(ticketId) {
            document.getElementById('reply_ticket_id').value = ticketId;
            document.getElementById('replyModal').classList.add('active');
        }

        function closeReplyModal() {
            document.getElementById('replyModal').classList.remove('active');
            document.getElementById('replyForm').reset();
        }

        
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTicketModal();
            }
        });

        document.getElementById('replyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReplyModal();
            }
        });
    </script>
</body>
</html>
