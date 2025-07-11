<?php
// Error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Start session to manage login state

$message = '';

// --- Handle Client Signup ---
if (isset($_POST['signup_submit'])) {
    $firstName = isset($_POST['first_name']) ? htmlspecialchars(trim($_POST['first_name'])) : '';
    $lastName = isset($_POST['last_name']) ? htmlspecialchars(trim($_POST['last_name'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : '';
    $mobileNo = isset($_POST['mobile_no']) ? htmlspecialchars(trim($_POST['mobile_no'])) : '';
    $address = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : '';
    $pincode = isset($_POST['pincode']) ? htmlspecialchars(trim($_POST['pincode'])) : '';
    $latitude = isset($_POST['latitude']) ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
    $longitude = isset($_POST['longitude']) ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;
    $locationName = isset($_POST['location_name']) ? htmlspecialchars(trim($_POST['location_name'])) : 'Unknown Location';


    $signupErrors = [];

    // Validation for First Name (Server-side: still uppercase only, now with min length 3)
    if (empty($firstName)) {
        $signupErrors[] = "First Name is required.";
    } elseif (!preg_match('/^[A-Z]{3,}$/', $firstName)) { // Server-side check for uppercase only and min 3 chars
        $signupErrors[] = "First Name must contain only uppercase alphabets and be at least 3 characters long.";
    }

    // Validation for Last Name (e.g., "A" or "A B" format)
    if (empty($lastName)) {
        $signupErrors[] = "Last Name is required.";
    } elseif (!preg_match('/^(?:[A-Z]|[A-Z]\s[A-Z])$/', $lastName)) {
        $signupErrors[] = "Last Name must be a single uppercase letter or two uppercase letters separated by a space (e.g., 'A' or 'A B').";
    }

    if (!$email) {
        $signupErrors[] = "Valid Email is required.";
    }

    // Validation for Mobile Number
    if (!preg_match('/^[0-9]{10}$/', $mobileNo)) { // Exactly 10 digits
        $signupErrors[] = "Mobile Number must be exactly 10 digits.";
    }

    if (empty($address)) {
        $signupErrors[] = "Address is required.";
    }

    // Validation for Pincode
    if (empty($pincode)) {
        $signupErrors[] = "Pincode is required.";
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) { // Exactly 6 digits
        $signupErrors[] = "Pincode must be exactly 6 digits.";
    }

    // Server-side check for location, ensuring both lat/lon are set
    if ($latitude === null || $longitude === null) {
        $signupErrors[] = "Location could not be determined. Please enable location services and click 'Get My Location'.";
    }
    // Also validate that the locationName is not the initial 'Fetching...' or empty
    if (empty($locationName) || $locationName === 'Fetching...') {
        $signupErrors[] = "Current Location must be set by clicking 'Get My Location' and providing a name.";
    }


    if (empty($signupErrors)) {
        $clientsFilePath = 'json/clients.json';
        $clients = [];
        if (file_exists($clientsFilePath)) {
            $jsonData = file_get_contents($clientsFilePath);
            $clients = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $clients = []; // Reset if JSON is invalid
            }
        }

        // Check for existing client (e.g., by email or mobile)
        $exists = false;
        foreach ($clients as $client) {
            if ($client['email'] === $email || $client['mobile_no'] === $mobileNo) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            $signupErrors[] = "A user with this email or mobile number already exists.";
        } else {
            $newClient = [
                'id' => uniqid('client_'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'mobile_no' => $mobileNo, // This will be used as password
                'address' => $address,
                'pincode' => $pincode,
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'name' => $locationName
                ]
            ];

            $clients[] = $newClient;
            file_put_contents($clientsFilePath, json_encode($clients, JSON_PRETTY_PRINT));
            $message = '<div class="message success">Signup successful! You can now log in.</div>';
            // Clear POST data to prevent re-submission and display login form
            unset($_POST);
        }
    } else {
        $message = '<div class="message error">' . implode('<br>', $signupErrors) . '</div>';
    }
}

// --- Handle Client Login ---
if (isset($_POST['login_submit'])) {
    $loginUsername = isset($_POST['login_username']) ? htmlspecialchars(trim($_POST['login_username'])) : '';
    $loginPassword = isset($_POST['login_password']) ? htmlspecialchars(trim($_POST['login_password'])) : '';

    $loginErrors = [];

    if (empty($loginUsername) || empty($loginPassword)) {
        $loginErrors[] = "Both username (Email) and password (Mobile Number) are required.";
    }

    if (empty($loginErrors)) {
        $clientsFilePath = 'json/clients.json';
        $clients = [];
        if (file_exists($clientsFilePath)) {
            $jsonData = file_get_contents($clientsFilePath);
            $clients = json_decode($jsonData, true);
        }

        $loggedIn = false;
        foreach ($clients as $client) {
            // Compare the input username directly with the client's email
            // and the input password with the client's mobile_no
            if ($loginUsername === $client['email'] && $loginPassword === $client['mobile_no']) {
                $_SESSION['client_logged_in'] = true;
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_name'] = $client['first_name'] . ' ' . $client['last_name'];
                $loggedIn = true;
                break;
            }
        }

        if ($loggedIn) {
            header('Location: client_dashboard.php'); // Redirect to a dashboard for viewing products
            exit();
        } else {
            $message = '<div class="message error">Invalid email or mobile number.</div>';
        }
    } else {
        $message = '<div class="message error">' . implode('<br>', $loginErrors) . '</div>';
    }
}

// Check if client is already logged in for immediate redirection
if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
    header('Location: client_dashboard.php');
    exit();
}

// Default to showing login form unless signup was attempted and failed
$showLoginForm = !isset($_POST['signup_submit']) || !empty($signupErrors);
if (isset($_POST['signup_submit']) && empty($signupErrors)) {
    $showLoginForm = true; // Show login form after successful signup
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Login / Signup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            box-sizing: border-box;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group label .required-star {
            color: red;
            margin-left: 3px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group textarea {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-toggle-link {
            display: block;
            margin-top: 20px;
            color: #007bff;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }
        .form-toggle-link:hover {
            text-decoration: underline;
        }
        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        #signupForm, #loginForm {
            display: none; /* Hidden by default, shown by JS */
        }
        .location-field {
            background-color: #f8f8f8; /* Differentiate readonly field */
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $message; ?>

        <div id="loginForm" style="display: <?php echo $showLoginForm ? 'block' : 'none'; ?>">
            <h2>Client Login</h2>
            <form action="client_login.php" method="POST">
                <div class="form-group">
                    <label for="login_username">Email ID:<span class="required-star">*</span></label>
                    <input type="email" id="login_username" name="login_username" required autocomplete="username"
                                value="<?php echo isset($_POST['login_username']) ? htmlspecialchars($_POST['login_username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="login_password">Password (Mobile Number):<span class="required-star">*</span></label>
                    <input type="password" id="login_password" name="login_password" required autocomplete="current-password">
                </div>
                <button type="submit" name="login_submit" class="submit-btn">Login</button>
            </form>
            <a href="#" class="form-toggle-link" onclick="toggleForms(false); return false;">Not signed up? Click to Signup</a>
            <a href="login.php" class="form-toggle-link" style="margin-top: 10px;">Back to Dashboard</a>
        </div>

        <div id="signupForm" style="display: <?php echo !$showLoginForm ? 'block' : 'none'; ?>">
            <h2>Client Signup</h2>
            <form action="client_login.php" method="POST" id="clientSignupForm">
                <div class="form-group">
                    <label for="first_name">First Name:<span class="required-star">*</span></label>
                    <input type="text" id="first_name" name="first_name" required
                                value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name (e.g., A or A B):<span class="required-star">*</span></label>
                    <input type="text" id="last_name" name="last_name" required maxlength="3" placeholder="e.g., A or A B" disabled
                                value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:<span class="required-star">*</span></label>
                    <input type="email" id="email" name="email" required disabled
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="mobile_no">Mobile Number (10 Digits):<span class="required-star">*</span></label>
                    <input type="number" id="mobile_no" name="mobile_no" required minlength="10" maxlength="10" disabled
                                value="<?php echo isset($_POST['mobile_no']) ? htmlspecialchars($_POST['mobile_no']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="address">Address:<span class="required-star">*</span></label>
                    <textarea id="address" name="address" required disabled><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="pincode">Pincode (6 Digits):<span class="required-star">*</span></label>
                    <input type="text" id="pincode" name="pincode" required minlength="6" maxlength="6" disabled
                                value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="location_name">Current Location:<span class="required-star">*</span></label>
                    <input type="text" id="location_name" name="location_name" placeholder="Enter your location name" class="location-field" disabled>
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    <button type="button" id="getLocationBtn" class="submit-btn" style="width: auto; padding: 8px 15px; font-size: 0.9em; margin-top: 5px;" disabled>Get My Location</button>
                    <p id="locationStatus" style="font-size: 0.9em; color: #666; margin-top: 5px;"></p>
                </div>
                <button type="submit" name="signup_submit" class="submit-btn" disabled>Signup</button>
            </form>
            <a href="#" class="form-toggle-link" onclick="toggleForms(true); return false;">Already have an account? Click to Login</a>
            <a href="login.php" class="form-toggle-link" style="margin-top: 10px;">Back to Dashboard</a>
        </div>
    </div>

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=geometry,places&callback=initMap"></script>
    <script>
        // Callback function for Google Maps API, needs to be globally accessible
        function initMap() {
            console.log("Google Maps API loaded.");
            // Your script logic will be placed here or called from here
        }

        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const getLocationBtn = document.getElementById('getLocationBtn');
            const locationNameInput = document.getElementById('location_name');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const locationStatus = document.getElementById('locationStatus');

            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            const emailInput = document.getElementById('email');
            const mobileNoInput = document.getElementById('mobile_no');
            const addressInput = document.getElementById('address');
            const pincodeInput = document.getElementById('pincode');
            const clientSignupForm = document.getElementById('clientSignupForm');
            const signupSubmitBtn = clientSignupForm.querySelector('button[name="signup_submit"]');


            // Function to toggle forms
            window.toggleForms = function(showLogin) {
                if (showLogin) {
                    loginForm.style.display = 'block';
                    signupForm.style.display = 'none';
                } else {
                    loginForm.style.display = 'none';
                    signupForm.style.display = 'block';
                    resetSignupFormAndEnableFirstField(); // Reset and enable first field on signup form display
                }
                // Clear any existing messages when toggling forms (the div at the top)
                const messageDiv = document.querySelector('.message');
                if (messageDiv) {
                    messageDiv.innerHTML = '';
                    messageDiv.style.display = 'none';
                }
            };

            // Set initial form visibility based on PHP logic
            const showLoginFormInitial = <?php echo json_encode($showLoginForm); ?>;
            toggleForms(showLoginFormInitial);

            // --- Sequential Input and Validation Logic ---

            const fields = [
                { input: firstNameInput, regex: /^[A-Z]{3,}$/, errorMessage: "First Name must contain only uppercase alphabets and be at least 3 characters long." },
                { input: lastNameInput, regex: /^(?:[A-Z]|[A-Z]\s[A-Z])$/, errorMessage: "Last Name must be a single uppercase letter or two uppercase letters separated by a space (e.g., 'A' or 'A B')." },
                { input: emailInput, regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, errorMessage: "Valid Email is required (e.g., user@example.com)." },
                { input: mobileNoInput, regex: /^\d{10}$/, errorMessage: "Mobile Number must be exactly 10 digits." },
                { input: addressInput, regex: /.+/, errorMessage: "Address is required." },
                { input: pincodeInput, regex: /^\d{6}$/, errorMessage: "Pincode must be exactly 6 digits." },
                { input: getLocationBtn, type: 'button', dependency: [latitudeInput, longitudeInput], errorMessage: "Please click 'Get My Location' to set your current location and then provide a name." }
            ];

            function enableField(index) {
                if (index < fields.length) {
                    const field = fields[index].input;
                    if (field) {
                        field.disabled = false;
                        if (field.tagName !== 'BUTTON') { // Don't focus on button automatically
                            field.focus();
                        }
                    }
                }
                checkAllFieldsAndEnableSubmit(); // Always check submission after enabling any field
            }

            function disableAllFieldsAfter(startIndex) {
                for (let i = startIndex; i < fields.length; i++) {
                    const field = fields[i].input;
                    if (field) {
                        field.disabled = true;
                    }
                }
                signupSubmitBtn.disabled = true; // Disable submit button
            }

            function validateField(fieldObj) {
                const input = fieldObj.input;
                if (fieldObj.type === 'button') { // For the Get Location button
                    // It's valid if latitude, longitude are set, and locationName is not empty/fetching
                    return latitudeInput.value && longitudeInput.value && locationNameInput.value && locationNameInput.value !== 'Fetching...';
                } else {
                    const value = input.value.trim();
                    if (!value) {
                        return false; // Basic check for empty required fields
                    }
                    if (fieldObj.regex && !fieldObj.regex.test(value)) {
                        return false;
                    }
                    return true;
                }
            }

            // MODIFIED: This function now uses alert()
            function showClientSideError(message) {
                alert(message);
            }

            // MODIFIED: This function is now empty as we use alert for errors
            function clearClientSideError() {
                // No need to clear a div if we're using alert boxes
            }

            function checkAllFieldsAndEnableSubmit() {
                let allValid = true;
                for (let i = 0; i < fields.length; i++) {
                    if (!validateField(fields[i])) {
                        allValid = false;
                        break;
                    }
                }
                signupSubmitBtn.disabled = !allValid;
            }

            // Attach event listeners for sequential validation
            fields.forEach((fieldObj, index) => {
                const input = fieldObj.input;

                if (fieldObj.type === 'button') { // Special handling for Get Location button
                    input.addEventListener('click', () => {
                            // Geolocation logic remains the same
                        locationStatus.textContent = 'Fetching your current location...';
                        locationStatus.style.color = '#007bff';
                        locationNameInput.value = ''; // Clear previous value
                        locationNameInput.placeholder = 'Fetching...'; // Set placeholder
                        latitudeInput.value = '';
                        longitudeInput.value = '';

                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    const lat = position.coords.latitude;
                                    const lon = position.coords.longitude;
                                    latitudeInput.value = lat;
                                    longitudeInput.value = lon;
                                    locationStatus.textContent = `Location coordinates fetched: Lat ${lat.toFixed(4)}, Lon ${lon.toFixed(4)}. Please enter your location name.`;
                                    locationStatus.style.color = 'green';
                                    locationNameInput.disabled = false; // Enable location name input for user to type
                                    locationNameInput.placeholder = 'Enter your location name'; // Reset placeholder
                                    locationNameInput.focus(); // Focus on the input for user to type
                                    clearClientSideError(); // No change here, still "clears" the (now non-existent) div
                                    checkAllFieldsAndEnableSubmit(); // Re-check validation
                                },
                                (error) => {
                                    console.error('Geolocation error:', error);
                                    let errorMessage = 'Error getting location. ';
                                    switch (error.code) {
                                        case error.PERMISSION_DENIED: errorMessage += "You denied the request for Geolocation."; break;
                                        case error.POSITION_UNAVAILABLE: errorMessage += "Location information is unavailable."; break;
                                        case error.TIMEOUT: errorMessage += "The request to get user location timed out."; break;
                                        case error.UNKNOWN_ERROR: errorMessage += "An unknown error occurred."; break;
                                    }
                                    locationNameInput.value = '';
                                    locationNameInput.placeholder = 'Click \'Get My Location\' to set';
                                    latitudeInput.value = '';
                                    longitudeInput.value = '';
                                    locationStatus.textContent = errorMessage + " Please enable location services and try again.";
                                    locationStatus.style.color = 'red';
                                    showClientSideError("Location could not be determined. Please enable location services and click 'Get My Location'."); // MODIFIED
                                    disableAllFieldsAfter(index + 1); // Disable subsequent fields if this fails
                                    input.disabled = false; // Re-enable the button itself so user can retry
                                },
                                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                            );
                        } else {
                            locationStatus.textContent = 'Geolocation is not supported by your browser.';
                            locationStatus.style.color = 'red';
                            showClientSideError("Geolocation is not supported by your browser."); // MODIFIED
                            disableAllFieldsAfter(index + 1);
                            input.disabled = false; // Keep the button enabled to show message
                        }
                    });

                    // Add an event listener to the location_name input to enable the next field
                    locationNameInput.addEventListener('blur', function() {
                        clearClientSideError(); // No change here
                        // This field is considered valid if it's not empty and coordinates are present
                        if (this.value.trim() !== '' && latitudeInput.value && longitudeInput.value) {
                            enableField(index + 1); // Enable the submit button
                        } else {
                            showClientSideError(fieldObj.errorMessage); // MODIFIED: Use alert for location error
                            signupSubmitBtn.disabled = true; // Ensure submit button is disabled
                            this.focus(); // Keep focus on this field
                        }
                    });
                } else {
                    input.addEventListener('blur', function() {
                        clearClientSideError(); // No change here
                        if (validateField(fieldObj)) {
                            enableField(index + 1);
                        } else {
                            showClientSideError(fieldObj.errorMessage); // MODIFIED: Use alert for field specific error
                            disableAllFieldsAfter(index + 1);
                            this.focus(); // Keep focus on the current field until valid
                        }
                    });

                    // Live input cleaning and formatting
                    if (input.id === 'first_name') {
                        input.addEventListener('input', function() {
                            this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase(); // Force uppercase and remove non-alpha
                        });
                    } else if (input.id === 'last_name') {
                        input.addEventListener('input', function() {
                            this.value = this.value.replace(/[^A-Z\s]/g, '').toUpperCase();
                            if (this.value.length > 3) {
                                this.value = this.value.slice(0, 3);
                            }
                        });
                    } else if (input.id === 'mobile_no') {
                        input.addEventListener('input', function() {
                            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                        });
                    } else if (input.id === 'pincode') {
                        input.addEventListener('input', function() {
                            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                        });
                    }
                }
            });

            // Initial setup for signup form: disable all fields except the first one
            function resetSignupFormAndEnableFirstField() {
                clientSignupForm.reset(); // Clear form values
                disableAllFieldsAfter(0); // Disable all
                firstNameInput.disabled = false; // Enable only the first field
                clearClientSideError(); // No change here
                locationStatus.textContent = '';
                locationNameInput.value = ''; // Ensure value is empty
                locationNameInput.placeholder = "Click 'Get My Location' to set"; // Ensure placeholder is visible initially
            }

            // Call this initially if the signup form is shown
            if (!showLoginFormInitial) {
                resetSignupFormAndEnableFirstField();
            }

            // Final client-side validation on form submission for signup form
            if (clientSignupForm) {
                clientSignupForm.addEventListener('submit', function(e) {
                    let clientErrors = [];
                    for (let i = 0; i < fields.length; i++) {
                        if (!validateField(fields[i])) {
                            clientErrors.push(fields[i].errorMessage);
                        }
                    }

                    if (clientErrors.length > 0) {
                        e.preventDefault(); // Stop form submission
                        showClientSideError(clientErrors.join('\n')); // MODIFIED: Join errors with newline for alert
                    }
                });
            }

        });
    </script>
</body>
</html>