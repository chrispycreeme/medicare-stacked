<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'db_connect.php';
$doctor_id = $_SESSION["doctor_id"];
$doctor_name = $_SESSION["doctor_name"];

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

$patient_info = null;
$vitals_data = [];
$latest_vitals = ['heart_rate' => 'N/A', 'oxygen_level' => 'N/A', 'body_temperature' => 'N/A']; // Initialize

if ($patient_id > 0) {
    $sql_patient = "SELECT patient_name, age_category, status FROM patients WHERE id = ? AND doctor_id = ?";
    if ($stmt_patient = $conn->prepare($sql_patient)) {
        $stmt_patient->bind_param("ii", $patient_id, $doctor_id);
        $stmt_patient->execute();
        $result_patient = $stmt_patient->get_result();
        if ($result_patient->num_rows == 1) {
            $patient_info = $result_patient->fetch_assoc();
        } else {
            header("location: patient-list.php?error=patient_not_found");
            exit;
        }
        $stmt_patient->close();
    } else {
        error_log("Error preparing patient info query: " . $conn->error);
    }

    $sql_latest_vitals = "SELECT heart_rate, oxygen_level, body_temperature FROM vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1";
    if ($stmt_latest = $conn->prepare($sql_latest_vitals)) {
        $stmt_latest->bind_param("i", $patient_id);
        $stmt_latest->execute();
        $result_latest = $stmt_latest->get_result();
        if ($result_latest->num_rows > 0) {
            $latest_vitals = $result_latest->fetch_assoc();
        }
        $stmt_latest->close();
    } else {
        error_log("Error preparing latest vitals query: " . $conn->error);
    }

    $sql_history_vitals = "SELECT heart_rate, oxygen_level, body_temperature, recorded_at FROM vitals WHERE patient_id = ? ORDER BY recorded_at ASC LIMIT 7";
    if ($stmt_history = $conn->prepare($sql_history_vitals)) {
        $stmt_history->bind_param("i", $patient_id);
        $stmt_history->execute();
        $result_history = $stmt_history->get_result();
        while ($row = $result_history->fetch_assoc()) {
            $vitals_data[] = $row;
        }
        $stmt_history->close();
    } else {
        error_log("Error preparing historical vitals query: " . $conn->error);
    }
} else {
    header("location: patient-list.php?error=no_patient_id");
    exit;
}

$conn->close();

$chart_labels = [];
$heart_rate_data = [];
$oxygen_level_data = [];
$body_temperature_data = [];

foreach ($vitals_data as $record) {
    $chart_labels[] = date('M d', strtotime($record['recorded_at']));
    $heart_rate_data[] = $record['heart_rate'];
    $oxygen_level_data[] = $record['oxygen_level'];
    $body_temperature_data[] = $record['body_temperature'];
}

$patient_data_for_ai = [
    'patientName' => $patient_info['patient_name'] ?? 'Unknown Patient',
    'ageCategory' => $patient_info['age_category'] ?? 'N/A',
    'latestVitals' => $latest_vitals,
    'vitalsHistory' => $vitals_data
];

if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    $_SESSION = array();

    session_destroy();

    header("location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/patient-card.css">
    <link rel="stylesheet" href="css/vital-grid-cards.css">
    <link rel="stylesheet" href="css/vital-graph.css">
    <link rel="stylesheet" href="css/sidebar-recommendations.css">
</head>

<body>

    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="sidebar-top">
                <i class="material-symbols-outlined">menu</i>
            </div>
            <div class="sidebar-bottom">
                <i class="material-symbols-outlined ph-heartbeat">cardiology</i>
            </div>
        </nav>

        <main class="main-content">
            <header class="main-header">
                <div class="header-text">
                    <h1>Welcome, <?php echo htmlspecialchars($doctor_name); ?>!</h1>
                    <p>Your personal patient dashboard overview</p>
                </div>
                <!-- Logout button -->
                <button class="logout-btn" onclick="location.href='dashboard.php?logout=true'">
                    <span>Log out</span>
                    <i class="material-symbols-outlined">logout</i>
                </button>
            </header>

            <div class="content-grid">
                <section class="patient-card">
                    <div class="patient-card-header">
                        <h2>Patient</h2>
                        <i class="material-symbols-outlined refresh-ai" id="refreshRecommendations">refresh</i>
                    </div>
                    <div class="patient-info">
                        <?php $avatar_initial = $patient_info['patient_name'] ? htmlspecialchars(substr($patient_info['patient_name'], 0, 1)) : 'P'; ?>
                        <img src="https://placehold.co/150x150/6C757D/FFFFFF?text=<?php echo $avatar_initial; ?>" alt="Patient Avatar"
                            class="patient-avatar">
                        <h3><?php echo htmlspecialchars($patient_info['patient_name']); ?></h3>
                        <p>Age Category: <?php echo htmlspecialchars($patient_info['age_category']); ?></p>
                    </div>
                    <div class="patient-status">
                        <i class="material-symbols-outlined">info</i>
                        <span>Status: <?php echo htmlspecialchars($patient_info['status']); ?></span>
                    </div>
                </section>

                <section class="vitals-grid">
                    <div class="vital-card">
                        <div class="vital-icon heart-rate">
                            <i class="material-symbols-outlined">cardiology</i>
                        </div>
                        <div class="vital-details">
                            <h4>Heart Rate</h4>
                            <p>Status: Normal</p>
                        </div>
                        <div class="vital-reading">
                            <span class="value"><?php echo htmlspecialchars($latest_vitals['heart_rate']); ?></span>
                            <span class="unit">bpm</span>
                        </div>
                    </div>
                    <div class="vital-card">
                        <div class="vital-icon oxygen-level">
                            <i class="material-symbols-outlined">spo2</i>
                        </div>
                        <div class="vital-details">
                            <h4>Oxygen Level</h4>
                            <p>Status: Normal</p>
                        </div>
                        <div class="vital-reading">
                            <span class="value"><?php echo htmlspecialchars($latest_vitals['oxygen_level']); ?></span>
                            <span class="unit">percent</span>
                        </div>
                    </div>
                    <div class="vital-card">
                        <div class="vital-icon body-temp">
                            <i class="material-symbols-outlined">device_thermostat</i>
                        </div>
                        <div class="vital-details">
                            <h4>Body Temperature</h4>
                            <p>Status: Normal</p>
                        </div>
                        <div class="vital-reading">
                            <span class="value"><?php echo htmlspecialchars($latest_vitals['body_temperature']); ?></span>
                            <span class="unit">Celsius</span>
                        </div>
                    </div>
                </section>

                <section class="vitals-graph-section">
                    <div class="graph-header">
                        <div class="graph-title">
                            <h2>Vitals</h2>
                            <p>Summary of patient vitals</p>
                        </div>
                        <div class="graph-range">
                            <span>Range: Monthly</span>
                            <i class="material-symbols-outlined">arrow_drop_down</i>
                        </div>
                    </div>
                    <div class="graph-container">
                        <div class="y-axis-labels">
                            <?php foreach ($chart_labels as $label): ?>
                                <span><?php echo $label; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="vitalsChart"></canvas>
                        </div>
                    </div>
                    <div class="graph-footer">
                        <div class="legend">
                            <span class="legend-item heart-rate-legend"><span class="dot"></span>Heart Rate</span>
                            <span class="legend-item oxygen-level-legend"><span class="dot"></span>Oxygen Level</span>
                            <span class="legend-item body-temp-legend"><span class="dot"></span>Body Temperature</span>
                        </div>
                        <div class="last-updated">
                            Last Updated: <?php echo date('m/d, h:ia'); ?> <!-- Dynamic last updated time -->
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <aside class="recommendations">
            <div class="recommendations-header">
                <h2>Recommendations</h2>
                <i class="material-symbols-outlined">chat_bubble</i>
            </div>
            <div class="ai-badge">
                <i class="material-symbols-outlined">auto_awesome</i>
                <span>AI Powered using Google Gemini 2.0</span>
            </div>

            <div class="recommendation-card">
                <h3>Overview</h3>
                <p id="overviewText" class="details">
                    <span class="loading-spinner">Loading recommendations...</span>
                </p>
            </div>

            <div class="recommendation-card">
                <h3>Potential Health Risks</h3>
                <p>List of possible health implications based on collected data.</p>
                <ul class="risk-list" id="riskList">
                    <li><span class="loading-spinner">Loading recommendations...</span></li>
                </ul>
            </div>

            <div class="recommendation-card">
                <h3>Precautionary Measures</h3>
                <p>List of possible health implications based on collected data.</p>
                <ul class="precaution-list" id="precautionList">
                    <li><span class="loading-spinner">Loading precautions...</span></li>
                </ul>
            </div>
        </aside>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const heartRateData = <?php echo json_encode($heart_rate_data); ?>;
        const oxygenLevelData = <?php echo json_encode($oxygen_level_data); ?>;
        const bodyTemperatureData = <?php echo json_encode($body_temperature_data); ?>;
        const patientDataForAI = <?php echo json_encode($patient_data_for_ai); ?>; // Data for AI
    </script>
    <script src="js/chart_handler.js"></script>
    <script src="js/ai_recommendation_handler.js"></script>
</body>

</html>
