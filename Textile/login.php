<?php
// Error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Start session to manage login state

$message = '';

// Function to generate a random CAPTCHA string
function generateCaptcha($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%&*';
    $captcha = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $captcha .= $characters[mt_rand(0, $max)];
    }
    return $captcha;
}

// Generate a new CAPTCHA if it doesn't exist in the session (first load)
if (!isset($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = generateCaptcha();
}

// Check if already logged in and redirect to appropriate dashboard
if (isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true) {
    header('Location: employee_dashboard.php');
    exit();
}
if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
    header('Location: client_dashboard.php');
    exit();
}

// Check for and display any session-based messages (e.g., from client_login.php success)
if (isset($_SESSION['form_message'])) {
    $message = $_SESSION['form_message'];
    unset($_SESSION['form_message']); // Clear the message after displaying
}


// --- Handle Login Submission ---
if (isset($_POST['login_submit'])) {
    $inputUsername = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
    $inputPassword = isset($_POST['password']) ? htmlspecialchars(trim($_POST['password'])) : '';
    $inputCaptcha = isset($_POST['captcha']) ? htmlspecialchars(trim($_POST['captcha'])) : '';

    // --- CAPTCHA Validation FIRST ---
    if (empty($inputCaptcha) || strtolower($inputCaptcha) !== strtolower($_SESSION['captcha_code'])) {
        $message = '<div class="message error">Invalid CAPTCHA code. Please try again.</div>';
        $_SESSION['captcha_code'] = generateCaptcha(); // Regenerate CAPTCHA on failure
    } elseif (empty($inputUsername) || empty($inputPassword)) {
        $message = '<div class="message error">Both username and password are required.</div>';
        $_SESSION['captcha_code'] = generateCaptcha(); // Regenerate CAPTCHA on failure
    } else {
        // CAPTCHA is valid, proceed with login logic
        
        // Define paths to JSON files using absolute paths for robustness
        $employeeFile = __DIR__ . '/json/employee.json';
        $clientsFile = __DIR__ . '/json/clients.json';

        // 1. Attempt Employee Login from employee.json (using email and mobile no)
        if (file_exists($employeeFile)) {
            $jsonData = file_get_contents($employeeFile);
            $employees = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error decoding employee.json: " . json_last_error_msg());
                $employees = []; // Treat as empty array if decoding fails
            }

            if (is_array($employees)) {
                foreach ($employees as $employee) {
                    // Normalize email for case-insensitive comparison
                    $employeeEmail = strtolower($employee['email'] ?? '');
                    $inputEmail = strtolower($inputUsername);
                    
                    // Ensure mobile number is treated as a string for comparison if it's stored as such
                    $employeeMobileNo = (string)($employee['mobileno'] ?? '');
                    $inputMobileNo = (string)$inputPassword; // Password is mobile number

                    // Check if email and mobile number match for employee
                    if ($employeeEmail === $inputEmail && $employeeMobileNo === $inputMobileNo) {
                        $_SESSION['employee_logged_in'] = true;
                        $_SESSION['username'] = $employee['email'];
                        $_SESSION['user_id'] = $employee['empid'] ?? '';
                        $_SESSION['name'] = ($employee['ename'] ?? '');
                        $_SESSION['user_type'] = 'employee';
                        
                        $_SESSION['form_message'] = '<div class="message success">Employee login successful!</div>';
                        header('Location: employee_dashboard.php');
                        exit();
                    }
                }
            }
        }

        // 2. If not found in employee.json, check hardcoded employee credentials (owner, admin, employee)
        $validEmployeeCredentials = [
            'owner' => 'owner',
            'admin' => 'admin',
            'employee' => 'employee'
        ];

        if (isset($validEmployeeCredentials[$inputUsername]) && $validEmployeeCredentials[$inputUsername] === $inputPassword) {
            $_SESSION['employee_logged_in'] = true;
            $_SESSION['username'] = $inputUsername;
            $_SESSION['name'] = $inputUsername;
            $_SESSION['user_id'] = $inputUsername; // Use username as ID for hardcoded roles
            $_SESSION['user_type'] = 'employee';
            
            $_SESSION['form_message'] = '<div class="message success">Employee login successful!</div>';
            header('Location: employee_dashboard.php');
            exit();
        }

        // 3. Attempt Client Login from clients.json
        if (file_exists($clientsFile)) {
            $jsonData = file_get_contents($clientsFile);
            $clients = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error decoding clients.json: " . json_last_error_msg());
                $clients = []; // Treat as empty array if decoding fails
            }

            if (is_array($clients)) {
                foreach ($clients as $client) {
                    // Normalize email for case-insensitive comparison
                    $clientEmail = strtolower($client['email'] ?? '');
                    $inputEmail = strtolower($inputUsername);
                    
                    // Ensure mobile number is treated as a string for comparison
                    $clientMobileNo = (string)($client['mobile_no'] ?? '');
                    $inputMobileNo = (string)$inputPassword; // Password is mobile number

                    if ($clientEmail === $inputEmail && $clientMobileNo === $inputMobileNo) {
                        $_SESSION['client_logged_in'] = true;
                        $_SESSION['user_id'] = $client['id'] ?? '';
                        $_SESSION['username'] = $client['email'];
                        $_SESSION['name'] = ($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '');
                        $_SESSION['user_type'] = 'client';
                        
                        $_SESSION['form_message'] = '<div class="message success">Client login successful!</div>';
                        header('Location: client_dashboard.php');
                        exit();
                    }
                }
            }
        }

        // 4. If none of the above login attempts succeeded
        $message = '<div class="message error">Invalid username or password.</div>';
        $_SESSION['captcha_code'] = generateCaptcha(); // Regenerate CAPTCHA on any login failure
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .logo {
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }
        .login-title {
            margin-bottom: 30px;
            font-size: 24px;
            color: #2c3e50;
        }
        .message {
            margin-bottom: 25px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
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
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .captcha-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        .captcha-image {
            background-color: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #2c3e50;
            user-select: none; /* Prevent text selection */
            min-width: 120px; /* Ensure a consistent width */
            text-align: center;
        }
        .refresh-captcha {
            background: none;
            border: none;
            color: #3498db;
            font-size: 20px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .refresh-captcha:hover {
            color: #2980b9;
        }
        .captcha-input {
            flex-grow: 1; /* Allow input to take remaining space */
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        .submit-btn:active {
            transform: translateY(0);
        }
        .login-links {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .login-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .login-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .login-li {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .login-li .login-link { /* More specific selector for clarity */
            color:rgb(187, 42, 42); /* Your specific color for this link */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .login-li .login-link:hover {
            color:rgb(0, 12, 19);
            text-decoration: underline;
        }

        .login-instructions {
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .instructions-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">Textile Portal</div>
        <h1 class="login-title">Login to Your Account</h1>
        
        <?php echo $message; // Display login messages ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username/Email</label>
                <input type="text" id="username" name="username" required 
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="captcha">Enter CAPTCHA</label>
                <div class="captcha-container">
                    <div id="captchaImage" class="captcha-image"><?php echo $_SESSION['captcha_code']; ?></div>
                    <button type="button" class="refresh-captcha" id="refreshCaptchaBtn">&#x21BB;</button>
                    <input type="text" id="captcha" name="captcha" class="captcha-input" required autocomplete="off">
                </div>
            </div>
            
            <button type="submit" name="login_submit" class="submit-btn">Login</button>
        </form>
        
        <div class="login-links">
            <a href="client_login.php" class="login-link">Register as New Client</a>
        </div>

        <div class="login-links">
            <a href="employee_login.php" class="login-link">Register as New Employee</a>
        </div>
        
        <div class="login-li">
            <a href="index.html" class="login-link">Back to Home</a>
        </div>

        </div>

    <script>
        // JavaScript to handle CAPTCHA refresh via AJAX
        document.getElementById('refreshCaptchaBtn').addEventListener('click', function() {
            fetch('generate_captcha.php') // Make an AJAX request to the CAPTCHA generation script
                .then(response => response.text()) // Get the response as plain text
                .then(captchaText => {
                    document.getElementById('captchaImage').textContent = captchaText; // Update the CAPTCHA image div
                    document.getElementById('captcha').value = ''; // Clear CAPTCHA input field
                })
                .catch(error => {
                    console.error('Error refreshing CAPTCHA:', error);
                    alert('Could not refresh CAPTCHA. Please refresh the page.'); // Alert user on error
                });
        });
    </script>
</body>
</html>