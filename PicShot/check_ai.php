<?php
// check_ai.php
// This file handles the server-side call to the Sightengine API
// It should be in the same directory as post.php

// --- IMPORTANT: Store API keys securely in a production environment (e.g., environment variables) ---
// For demonstration, they are hardcoded. Do NOT hardcode them directly in your web-accessible files in production.
$api_user = '197575865';    // Your Sightengine API User
$api_secret = 'fEDX6bKrLqRS8GZPHydS8XJQb55Dk9Sr'; // Your Sightengine API Secret
// --- End of IMPORTANT ---

// Set the content type header to JSON, as this script will respond with JSON
header('Content-Type: application/json');

// Enable error reporting for debugging (TEMPORARILY UNCOMMENT THIS FOR DEBUGGING, THEN REMOVE IN PRODUCTION)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Ensure this script only processes POST requests and has an 'imageUrl' parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['imageUrl'])) {
    $imageUrl = $_POST['imageUrl'];

    // Construct the multipart/form-data payload as a string
    // This is crucial for the Sightengine API when sending a URL
    $boundary = uniqid(); // Generate a unique boundary string
    $payload = "";

    // Add 'media' field (the image URL)
    $payload .= "--" . $boundary . "\r\n";
    $payload .= "Content-Disposition: form-data; name=\"media\"\r\n";
    $payload .= "Content-Type: text/plain\r\n\r\n"; // Content-Type for a URL in a multipart form
    $payload .= $imageUrl . "\r\n";

    // Add 'models' field
    $payload .= "--" . $boundary . "\r\n";
    $payload .= "Content-Disposition: form-data; name=\"models\"\r\n";
    $payload .= "Content-Type: text/plain\r\n\r\n";
    $payload .= "genai\r\n";

    // Add 'api_user' field
    $payload .= "--" . $boundary . "\r\n";
    $payload .= "Content-Disposition: form-data; name=\"api_user\"\r\n";
    $payload .= "Content-Type: text/plain\r\n\r\n";
    $payload .= $api_user . "\r\n";

    // Add 'api_secret' field
    $payload .= "--" . $boundary . "\r\n";
    $payload .= "Content-Disposition: form-data; name=\"api_secret\"\r\n";
    $payload .= "Content-Type: text/plain\r\n\r\n";
    $payload .= $api_secret . "\r\n";

    // End of multipart payload
    $payload .= "--" . $boundary . "--\r\n"; // Final boundary with two hyphens

    // Initialize cURL session
    $ch = curl_init('https://api.sightengine.com/1.0/check.json');
    curl_setopt($ch, CURLOPT_POST, true);             // Set as POST request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // Return the response as a string
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);    // Set the manually constructed multipart payload
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: multipart/form-data; boundary=" . $boundary // Crucial: Set the correct Content-Type header with the boundary
    ]);

    // Execute cURL request
    $response = curl_exec($ch);
    $curl_error = curl_error($ch); // Check for cURL errors
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
    curl_close($ch); // Close cURL session

    if ($curl_error) {
        // If there's a cURL error (e.g., network issue from your server to Sightengine)
        echo json_encode(['error' => 'cURL error when calling Sightengine: ' . $curl_error]);
        exit;
    }

    if ($http_code !== 200) {
        // If Sightengine returned a non-200 HTTP status code (e.g., 401 Unauthorized, 429 Too Many Requests)
        echo json_encode(['error' => 'Sightengine API returned HTTP status ' . $http_code . ': ' . $response]);
        exit;
    }

    // Decode the JSON response from Sightengine
    $output = json_decode($response, true);

    // Check if the 'ai_generated' score is available in the response
    if (isset($output['type']['ai_generated'])) {
        $score = $output['type']['ai_generated'];
        // Determine if the image is AI-generated based on the score (e.g., > 0.8)
        $isAI = ($score > 0.8); // You can adjust this threshold (e.g., 0.5, 0.7)
        echo json_encode(['is_ai' => $isAI]); // Return the boolean result
    } else if (isset($output['error'])) {
        // If Sightengine returned an API-specific error message (e.g., invalid media)
        echo json_encode(['error' => 'Sightengine API Error: ' . $output['error']['message']]);
    } else {
        // Handle unexpected API response format from Sightengine
        echo json_encode(['error' => 'Unexpected API response format from Sightengine.', 'raw_response' => $response]);
    }
} else {
    // If the request method is not POST or 'imageUrl' is missing, return an error
    echo json_encode(['error' => 'Invalid request to check_ai.php: POST method and imageUrl required.']);
}
?>