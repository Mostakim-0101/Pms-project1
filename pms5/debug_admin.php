<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting debug...<br>";

try {
    require_once 'includes/auth.php';
    echo "Auth file loaded successfully<br>";
} catch (Exception $e) {
    echo "Error loading auth: " . $e->getMessage() . "<br>";
    exit;
}

try {
    require_once 'config.php';
    echo "Config loaded successfully<br>";
} catch (Exception $e) {
    echo "Error loading config: " . $e->getMessage() . "<br>";
    exit;
}


try {
    $test_query = $conn->query("SELECT 1");
    echo "Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
    exit;
}


$tables = ['Patients', 'Doctors', 'Appointments', 'SupportTickets', 'DisciplinaryRecords'];
foreach ($tables as $table) {
    try {
        $result = $conn->query("SELECT COUNT(*) FROM $table");
        echo "Table $table: EXISTS<br>";
    } catch (Exception $e) {
        echo "Table $table: MISSING - " . $e->getMessage() . "<br>";
    }
}


try {
    $auth = new Auth($conn);
    echo "Auth class: SUCCESS<br>";
} catch (Exception $e) {
    echo "Auth class: FAILED - " . $e->getMessage() . "<br>";
}

echo "Debug completed!";
?>
