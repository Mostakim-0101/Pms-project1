<?php 
require_once 'includes/auth.php';


$auth->requireRole('Patient', 'login.php');

$user = $auth->getCurrentUser();
$patient_id = $user['user_id'];
$msg = "";


if (isset($_POST['add_record']) && (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    $pid = intval($_POST['patient_id']);
    $issue = $_POST['issue'];
    $action_taken = $_POST['action_taken'];
    $record_date = $_POST['record_date'];

    $stmt = $conn->prepare("INSERT INTO DisciplinaryRecords (patient_id, issue, action_taken, record_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $pid, $issue, $action_taken, $record_date);
    $stmt->execute();
    $stmt->close();
    $msg = "Disciplinary record added.";
}


$stmt = $conn->prepare("SELECT record_id, issue, action_taken, record_date FROM DisciplinaryRecords WHERE patient_id = ? ORDER BY record_date DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Disciplinary Records</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
  <h2>My Disciplinary Records</h2>

  <?php if ($msg) echo "<p class='success'>$msg</p>"; ?>

  <?php if ($results->num_rows == 0): ?>
    <p>No disciplinary records found.</p>
  <?php else: ?>
    <table>
      <tr><th>Date</th><th>Issue</th><th>Action Taken</th></tr>
      <?php while($row = $results->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['record_date']); ?></td>
          <td><?php echo nl2br(htmlspecialchars($row['issue'])); ?></td>
          <td><?php echo nl2br(htmlspecialchars($row['action_taken'])); ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php endif; ?>

  <p><a href="index.php">← Back to Dashboard</a></p>

  <?php
  
  if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']):
  ?>
    <hr>
    <h3>Add Disciplinary Record (Admin)</h3>
    <form method="POST">
      <label>Patient ID</label>
      <input type="number" name="patient_id" required>
      <label>Record Date</label>
      <input type="date" name="record_date" required value="<?php echo date('Y-m-d'); ?>">
      <label>Issue</label>
      <textarea name="issue" rows="3" required></textarea>
      <label>Action Taken</label>
      <textarea name="action_taken" rows="2" required></textarea>
      <button type="submit" name="add_record">Add Record</button>
    </form>
  <?php endif; ?>

</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
