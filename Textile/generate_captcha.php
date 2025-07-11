<?php
// Start session to access/store CAPTCHA code
session_start();

// Function to generate a random CAPTCHA string
function generateCaptcha($length = 6) {
    // Define characters to be used in the CAPTCHA
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%&*';
    $captcha = '';
    $max = strlen($characters) - 1; // Get the maximum index of the characters string

    // Loop to build the CAPTCHA string character by character
    for ($i = 0; $i < $length; $i++) {
        // Append a random character from the defined set
        $captcha .= $characters[mt_rand(0, $max)];
    }
    return $captcha;
}

// Generate a new CAPTCHA and store it in the session
$_SESSION['captcha_code'] = generateCaptcha();

// Output the new CAPTCHA code as plain text.
// This is what the AJAX call in index.php will receive.
echo $_SESSION['captcha_code'];
?>