<?php
// config.php
function start_secure_session() {
    $secure = false; // Set to true if using https
    $httponly = true; // This stops JavaScript from being able to access the session ID in the cookie.

    if ($secure) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to false for HTTP
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    session_start();
}

start_secure_session();

// Define hardcoded users for demonstration (DO NOT USE IN PRODUCTION)
define('EMPLOYEE_USERNAME', 'admin');
define('EMPLOYEE_PASSWORD', 'password123'); // In production, hash passwords!

// Define paths for JSON data
define('CLIENTS_JSON_FILE', 'clients.json');
define('PRODUCTS_JSON_FILE', 'products.json');

// !!! IMPORTANT: Replace with your actual Google Maps API Key !!!
// Get one from Google Cloud Console: https://console.cloud.google.com/
define('Maps_API_KEY', 'YOUR_Maps_API_KEY'); 
?>