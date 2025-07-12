<?php
header('Content-Type: application/json');

// Allow cross-origin requests (for development, consider more specific origins in production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true); // Decode as associative array

// Basic validation (you can add more robust validation)
if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
    exit;
}

// Define the directory to save files
$saveDir = 'prescriptions/';

// Create the directory if it doesn't exist
if (!is_dir($saveDir)) {
    if (!mkdir($saveDir, 0755, true)) { // 0755 permissions, true for recursive creation
        echo json_encode(['success' => false, 'message' => 'Failed to create save directory.']);
        exit;
    }
}

// Generate a unique filename
$patientName = isset($data['patientName']) ? preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $data['patientName'])) : 'unknown_patient';
$date = isset($data['date']) ? $data['date'] : date('Y-m-d');
$timestamp = date('His'); // HourMinuteSecond for uniqueness
$filename = $saveDir . $patientName . '_' . $date . '_' . $timestamp . '.json';

// Save the JSON data to the file
if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) !== false) {
    echo json_encode(['success' => true, 'message' => 'Prescription saved successfully.', 'filename' => basename($filename)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to write prescription file. Check folder permissions.']);
}

?>