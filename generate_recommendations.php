<?php
header('Content-Type: application/json');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep display_errors off for security, use error_log instead

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');

error_log("generate_recommendations.php: Script started.");


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    error_log("generate_recommendations.php: Unauthorized access attempt.");
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    error_log("generate_recommendations.php: Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("generate_recommendations.php: Failed to decode JSON input: " . json_last_error_msg());
    echo json_encode(['error' => 'Invalid JSON input.']);
    exit;
}

error_log("generate_recommendations.php: Received patient data: " . json_encode($data));

$patientName = $data['patientName'] ?? 'a patient';
$ageCategory = $data['ageCategory'] ?? 'N/A';
$latestVitals = $data['latestVitals'] ?? [];
$vitalsHistory = $data['vitalsHistory'] ?? [];

$prompt = "As a medical AI assistant for a doctor's dashboard, provide concise health recommendations for a patient based on their vital signs.
Patient Name: " . $patientName . "
Age Category: " . $ageCategory . "
Latest Vitals: " . json_encode($latestVitals) . "
Vital History (last 7 readings, ordered oldest to newest): " . json_encode($vitalsHistory) . "

Provide three sections:
1. **Overview**: A worded summary of the patient's general health based on the data.
2. **Potential Health Risks**: A list of possible health implications.
3. **Precautionary Measures**: A list of actionable precautionary advice.

For 'Potential Health Risks' and 'Precautionary Measures', each item should have a 'title' and a 'description'. Keep the 'description' very short, typically a single sentence or a few words. Do NOT include any introductory or concluding remarks outside the JSON. Do NOT include any markdown formatting like ```json.
Example JSON structure:
{
  \"overview\": \"Based on the provided vitals, the patient's overall health appears to be within normal limits...\",
  \"health_risks\": [
    { \"title\": \"Slightly elevated heart rate\", \"description\": \"Could indicate stress or exertion.\" },
    { \"title\": \"Minor oxygen fluctuations\", \"description\": \"Monitor respiratory patterns.\" }
  ],
  \"precautionary_measures\": [
    { \"title\": \"Monitor heart rate regularly\", \"description\": \"Log readings during activity and rest.\" },
    { \"title\": \"Ensure adequate hydration\", \"description\": \"Drink 8 glasses water daily.\" }
  ]
}
";

// Google Gemini API Configuration (Direct Integration)
$gemini_api_key = 'AIzaSyAnGkHMDZochb5rxYJtTrzk1btNyiPfjLE';
$gemini_api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        // 'temperature' => 0.7, // Optional: Adjust temperature for creativity (0.0-1.0)
        // 'topP' => 0.9,      // Optional: Top-p sampling
        // 'topK' => 40       // Optional: Top-k sampling
    ]
];

$ch = curl_init($gemini_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
// Headers for Google Gemini API
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $gemini_api_key
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Re-enable SSL verification for direct Google API for security

error_log("generate_recommendations.php: Sending request to Google Gemini API.");
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

error_log("generate_recommendations.php: Received HTTP Code: " . $http_code);
error_log("generate_recommendations.php: cURL Error: " . ($curl_error ? $curl_error : "None"));
error_log("generate_recommendations.php: Raw API Response: " . $response);


if ($curl_error) {
    echo json_encode(['error' => 'API connection failed: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    $api_error_details = json_decode($response, true);
    echo json_encode(['error' => 'API request failed with status ' . $http_code, 'details' => $api_error_details]);
    exit;
}

$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("generate_recommendations.php: Failed to decode JSON from Google Gemini API response: " . json_last_error_msg());
    echo json_encode(['error' => 'Failed to decode API response JSON.', 'raw_response' => $response]);
    exit;
}

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $ai_response_content = $result['candidates'][0]['content']['parts'][0]['text'];
    error_log("generate_recommendations.php: AI Response Content (raw): " . $ai_response_content);

    $ai_response_content = preg_replace('/^```json\\s*|```$/im', '', trim($ai_response_content));

    $ai_data = json_decode($ai_response_content, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($ai_data['overview']) && isset($ai_data['health_risks']) && is_array($ai_data['health_risks']) && isset($ai_data['precautionary_measures']) && is_array($ai_data['precautionary_measures'])) {
            error_log("generate_recommendations.php: Successfully parsed AI data.");
            echo json_encode($ai_data); // Return the parsed AI data
        } else {
            error_log("generate_recommendations.php: AI data structure invalid after JSON decode. Expected keys: overview, health_risks (array), precautionary_measures (array).");
            echo json_encode([
                'error' => 'AI response structure invalid.',
                'raw_response' => $ai_response_content,
                'parsed_data' => $ai_data
            ]);
        }
    } else {
        error_log("generate_recommendations.php: AI response content is not valid JSON. Error: " . json_last_error_msg());
        echo json_encode(['error' => 'AI response content is not valid JSON.', 'raw_response' => $ai_response_content]);
    }
} else {
    error_log("generate_recommendations.php: AI response content missing or malformed in candidates array.");
    echo json_encode(['error' => 'AI response content missing from API response.', 'raw_response' => $response]);
}
?>
