<?php
header('Content-Type: application/json');

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Ensure the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Define the directory where files are saved
$saveDir = 'prescriptions/';

// Get the filename from the query parameter
$filename = isset($_GET['filename']) ? basename($_GET['filename']) : ''; // basename() to prevent directory traversal

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'Filename not provided.']);
    exit;
}

$filePath = $saveDir . $filename;

// Check if the file exists and is readable
if (file_exists($filePath) && is_readable($filePath)) {
    $jsonData = file_get_contents($filePath);
    $data = json_decode($jsonData, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error parsing JSON file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Prescription file not found or not readable.']);
}

?>