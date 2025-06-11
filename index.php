<?php
session_start();

require_once 'db_connect.php';

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") { //

    $identification_code = trim($_POST['identification_code']);
    $password = trim($_POST['password']);

    $sql = "SELECT id, full_name, password FROM doctors WHERE identification_code = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $identification_code);

        if ($stmt->execute()) {
            // Store the result
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $full_name, $hashed_password);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        session_regenerate_id();

                        $_SESSION["loggedin"] = true; //
                        $_SESSION["doctor_id"] = $id; //
                        $_SESSION["doctor_name"] = $full_name; //

                        header("location: patient-list.php");
                        exit;
                    } else {
                        $login_error = "The access code you entered was not valid.";
                    }
                }
            } else {
                $login_error = "No account found with that identification code.";
            }
        } else {
            $login_error = "Oops! Something went wrong. Please try again later."; //
        }

        $stmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicare - Login</title>

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="Logo">
        <div class="logo-container">
            <img src="/Medicare/ecg_heart_70dp_C42847_FILL0_wght500_GRAD0_opsz48.png" alt="Medicare Logo" class="logo-img">
            <h1>MEDICARE</h1>
        </div>
        <h2>Empowering Patients Through Smart, Innovative Healthcare Solutions..</h2>
    </div>

    <section class="login-section">
        <div class="Login form">
            <h1>Login</h1>
            <h2>Login to see your patient data.</h2>
            <form action="" method="post"> <?php
                if (!empty($login_error)) {
                    echo "<p style='color: red; text-align: center;'>{$login_error}</p>";
                }
                ?>
                <div class="input-group">
                    <label for="username">Identification Code</label>
                    <input type="text" id="username" name="identification_code" placeholder="Identification Code" required>
                </div>
                <div class="input-group">
                    <label for="password">Access Code</label>
                    <input type="password" id="password" name="password" placeholder="Access Code" required>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </section>
</body>

</html>