<?php
// Simple admin test page
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';

// Test admin login
$auth = new Auth($conn);

echo "<h1>Admin Test Page</h1>";

// Test if we can get current user
try {
    $user = $auth->getCurrentUser();
    echo "Current user: " . print_r($user, true) . "<br>";
} catch (Exception $e) {
    echo "Error getting current user: " . $e->getMessage() . "<br>";
}

// Test basic queries
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Patients");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "Total patients: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "Error counting patients: " . $e->getMessage() . "<br>";
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Doctors");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "Total doctors: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "Error counting doctors: " . $e->getMessage() . "<br>";
}

echo "<br><a href='admin_dashboard.php'>Try Admin Dashboard</a>";
?>
