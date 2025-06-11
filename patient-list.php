<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'db_connect.php';

$doctor_id = $_SESSION["doctor_id"];
$doctor_name = $_SESSION["doctor_name"];

$patients = [];

$sql = "SELECT id, patient_name, age_category FROM patients WHERE doctor_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $doctor_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    } else {
        error_log("Error fetching patients: " . $conn->error);
        $patients_fetch_error = "Could not load patient data. Please try again later.";
    }
    $stmt->close();
} else {
    error_log("Error preparing patient fetch query: " . $conn->error);
    $patients_fetch_error = "An internal error occurred. Please try again later.";
}

$conn->close();

$all_patients_json = json_encode($patients);

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
    <title>Doctor Dashboard - Patient List</title>
    <link rel="stylesheet" href="css/patient_search.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="search-page-container">
        <header class="page-header">
            <h1 class="logo">
                <i class="material-symbols-outlined">ecg_heart</i>
                <span>MEDICARE</span>
            </h1>
            <p class="tagline">Welcome, <?php echo htmlspecialchars($doctor_name); ?>!</p>
        </header>

        <button class="logout-btn" onclick="location.href='patient-list.php?logout=true'"
                style="position: absolute; top: 2rem; right: 2rem;">
            <span>Logout</span>
            <i class="material-symbols-outlined">logout</i>
        </button>

        <div class="toolbar">
            <div class="search-bar">
                <input type="text" id="searchPatient" placeholder="Search patients by name...">
                <i class="material-symbols-outlined">search</i>
            </div>
            <div class="sort-control">
                <span>Sort by:</span>
                <div class="dropdown">
                    <select id="sortBy" style="background: none; border: none; color: inherit; font-size: inherit; font-family: inherit; padding: 0;">
                        <option value="nameAsc">Name (A-Z)</option>
                        <option value="nameDesc">Name (Z-A)</option>
                        <option value="ageAsc">Age (Low to High)</option>
                        <option value="ageDesc">Age (High to Low)</option>
                    </select>
                    <i class="material-symbols-outlined">arrow_drop_down</i>
                </div>
            </div>
        </div>

        <main class="patient-grid" id="patientGrid">
            <?php if (!empty($patients_fetch_error)): ?>
                <p style="color: red; text-align: center; width: 100%;"><?php echo $patients_fetch_error; ?></p>
            <?php elseif (empty($patients)): ?>
                <p style="text-align: center; width: 100%;">You have no patients assigned to you yet.</p>
            <?php else: ?><?php endif; ?>
        </main>

        <div class="pagination" id="paginationControls">

        </div>
    </div>

    <script>
        const allPatients = <?php echo $all_patients_json; ?>;

        const renderPatientCard = (patient) => {
            const avatarText = patient.patient_name ? patient.patient_name.charAt(0).toUpperCase() : 'P';
            return `
                <a href="dashboard.php?patient_id=${patient.id}" class="patient-card-item-link" style="text-decoration: none; color: inherit;">
                    <div class="patient-card-item">
                        <img src="https://placehold.co/60x60/35324B/FFFFFF?text=${avatarText}" alt="Patient Avatar" class="avatar">
                        <div class="patient-details">
                            <h4>${patient.patient_name ? patient.patient_name : 'Unknown Patient'}</h4>
                            <p>Age Category: ${patient.age_category ? patient.age_category : 'N/A'}</p>
                        </div>
                        <i class="material-symbols-outlined menu-icon">dehaze</i>
                    </div>
                </a>
            `;
        };
        window.renderPatientCard = renderPatientCard;
    </script>
    <script src="js/pagination_handler.js"></script>
    <script src="js/search_sort_handler.js"></script>
</body>

</html>
