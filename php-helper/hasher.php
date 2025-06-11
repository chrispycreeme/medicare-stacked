<?php
/**
 * PHP Script for Hashing and Verifying Passwords
 *
 * This script demonstrates how to securely hash and verify passwords using PHP's
 * built-in password hashing API. It uses `password_hash()` for hashing, which
 * defaults to the bcrypt algorithm (recommended), and `password_verify()` for
 * checking a plain-text password against a stored hash.
 *
 * This version includes an HTML form to allow users to input a password for hashing
 * and verification demonstration.
 */

// --- Configuration ---
// Initialize variables for password handling
$plainTextPassword = '';
$hashedPassword = '';
$message = '';
$displayResults = false;

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $plainTextPassword = $_POST['password'];

        // --- Hashing a Password ---
        /**
         * Hashes a plain-text password using the bcrypt algorithm.
         *
         * The `password_hash()` function handles salting and iterative hashing automatically.
         * PASSWORD_DEFAULT currently uses bcrypt and adapts to future algorithms.
         * PASSWORD_BCRYPT forces bcrypt.
         *
         * @param string $password The plain-text password to hash.
         * @return string The hashed password.
         */
        function hashPassword(string $password): string
        {
            // Use PASSWORD_DEFAULT for maximum compatibility and future-proofing.
            // The cost parameter can be adjusted, but the default is usually sufficient.
            // Higher cost means more CPU time, making brute-force attacks harder but
            // also increasing the time it takes to hash.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($hashedPassword === false) {
                // Handle error: password_hash failed (e.g., invalid algorithm, memory issues)
                // In a web context, avoid die() and instead return an error state or log.
                return 'Error: Password hashing failed.';
            }
            return $hashedPassword;
        }

        $startHashTime = microtime(true);
        $hashedPassword = hashPassword($plainTextPassword);
        $endHashTime = microtime(true);

        $displayResults = true; // Set flag to display results

        // --- Verifying a Password ---
        /**
         * Verifies a plain-text password against a stored hash.
         *
         * @param string $password The plain-text password provided by the user.
         * @param string $hash The stored hashed password from the database.
         * @return bool True if the password matches the hash, false otherwise.
         */
        function verifyPassword(string $password, string $hash): bool
        {
            return password_verify($password, $hash);
        }

        // Test with the correct password (the one just hashed)
        $testPasswordCorrect = $plainTextPassword;
        $startVerifyCorrectTime = microtime(true);
        $isCorrect = verifyPassword($testPasswordCorrect, $hashedPassword);
        $endVerifyCorrectTime = microtime(true);

        // Test with an incorrect password (a dummy one)
        $testPasswordIncorrect = 'WrongPassword456';
        $startVerifyIncorrectTime = microtime(true);
        $isIncorrect = verifyPassword($testPasswordIncorrect, $hashedPassword);
        $endVerifyIncorrectTime = microtime(true);

        // --- Rehash Check (Important for security) ---
        /**
         * Checks if a password needs to be rehashed (e.g., due to updated cost, algorithm, or PHP version).
         *
         * @param string $hash The stored hashed password.
         * @return bool True if the password needs rehashing, false otherwise.
         */
        function needsRehash(string $hash): bool
        {
            return password_needs_rehash($hash, PASSWORD_DEFAULT);
        }

        $rehashStatus = needsRehash($hashedPassword) ?
            "<p style='color: orange;'>The stored hash needs to be rehashed for better security or algorithm updates.</p>" :
            "<p style='color: blue;'>The stored hash is currently up to date and does not need rehashing.</p>";

    } else {
        $message = "<p style='color: red;'>Please enter a password.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Password Hashing Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 600px;
        }

        h2 {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
        }

        code {
            background-color: #e5e7eb;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-family: monospace;
            word-break: break-all;
            /* Ensures long hashes wrap */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-3xl font-extrabold text-center text-gray-900 mb-6">Password Hashing and Verification</h1>

        <form action="" method="POST" class="mb-8 p-6 bg-gray-50 rounded-lg shadow-inner">
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Enter Password:</label>
                <input type="password" id="password" name="password"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                    placeholder="e.g., MyStrongPassword123" required>
            </div>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                Hash and Verify
            </button>
        </form>

        <?php echo $message; // Display general messages ?>

        <?php if ($displayResults): ?>
            <h2 class="text-xl font-semibold text-gray-800">Hashing Result</h2>
            <p class="text-gray-700">Original Password: <code
                    class="block mt-2"><?php echo htmlspecialchars($plainTextPassword); ?></code></p>
            <p class="text-gray-700 mt-2">Hashed Password: <code
                    class="block mt-2"><?php echo htmlspecialchars($hashedPassword); ?></code></p>
            <p class="text-gray-600 text-sm mt-1">Hashing Time:
                <?php echo round(($endHashTime - $startHashTime) * 1000, 2); ?> ms</p>

            <h2 class="text-xl font-semibold text-gray-800 mt-6">Verification Results</h2>

            <div
                class="mb-4 p-4 rounded-md <?php echo $isCorrect ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <p class="font-medium">Attempting to verify: <code
                        class="block mt-1"><?php echo htmlspecialchars($testPasswordCorrect); ?></code> against the hash.
                </p>
                <p class="mt-2">Result: <strong><?php echo $isCorrect ? 'MATCH!' : 'NO MATCH!'; ?></strong> (Password is
                    <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>)</p>
                <p class="text-gray-600 text-sm mt-1">Verification Time (Correct):
                    <?php echo round(($endVerifyCorrectTime - $startVerifyCorrectTime) * 1000, 2); ?> ms</p>
            </div>

            <div
                class="p-4 rounded-md <?php echo $isIncorrect ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <p class="font-medium">Attempting to verify: <code
                        class="block mt-1"><?php echo htmlspecialchars($testPasswordIncorrect); ?></code> against the hash.
                </p>
                <p class="mt-2">Result: <strong><?php echo $isIncorrect ? 'MATCH!' : 'NO MATCH!'; ?></strong> (Password is
                    <?php echo $isIncorrect ? 'correct' : 'incorrect'; ?>)</p>
                <p class="text-gray-600 text-sm mt-1">Verification Time (Incorrect):
                    <?php echo round(($endVerifyIncorrectTime - $startVerifyIncorrectTime) * 1000, 2); ?> ms</p>
            </div>

            <h2 class="text-xl font-semibold text-gray-800 mt-6">Rehash Check</h2>
            <?php echo $rehashStatus; ?>

        <?php endif; ?>
    </div>
</body>

</html>