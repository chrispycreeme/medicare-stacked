<?php
// Debug: print incoming POST data
print_r($_POST);

require_once '../db_connect.php';

// Get POST data safely
$patientId = isset($_POST['patient_id']) && $_POST['patient_id'] !== '' ? intval($_POST['patient_id']) : 0;
$heartRate = isset($_POST['heart_rate']) && $_POST['heart_rate'] !== '' ? intval($_POST['heart_rate']) : null;
$oxygenLevel = isset($_POST['oxygen_level']) && $_POST['oxygen_level'] !== '' ? intval($_POST['oxygen_level']) : null;
$bodyTemperature = isset($_POST['body_temperature']) && $_POST['body_temperature'] !== '' ? floatval($_POST['body_temperature']) : null;

// Check all fields are present and valid
if ($patientId > 0 && $heartRate !== null && $oxygenLevel !== null && $bodyTemperature !== null) {
    $sql = "INSERT INTO vitals (patient_id, heart_rate, oxygen_level, body_temperature, recorded_at) VALUES (?, ?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiid", $patientId, $heartRate, $oxygenLevel, $bodyTemperature);
        if ($stmt->execute()) {
            echo "Data saved to database successfully.";
        } else {
            echo "Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error;
    }   
} else {
    echo "Invalid or missing data.";
}

$conn->close();
?>
