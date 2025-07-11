<?php
session_start();

// Initialize all data arrays
$employees = [];
$religions = [];
$designations = [];
$statesAndCities = [];

// Helper function to safely load JSON data
function loadJsonData($filename) {
    if (file_exists($filename)) {
        $data = file_get_contents($filename);
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

// Load all data files
$employeeFile = 'json/employee.json';
$employees = loadJsonData($employeeFile);

$religionFile = 'json/religion.json';
$religions = loadJsonData($religionFile);

$designationFile = 'json/designation.json';
$designations = loadJsonData($designationFile);

$statesAndCitiesFile = 'json/statesandcities.json';
$statesAndCities = loadJsonData($statesAndCitiesFile);

$message = '';
$showSuccess = false;

// --- Handle Employee Login ---
if (isset($_POST['login_submit'])) {
    $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
    $password = isset($_POST['password']) ? htmlspecialchars(trim($_POST['password'])) : '';

    // First check against registered employees
    if (is_array($employees)) {
        foreach ($employees as $employee) {
            if (isset($employee['email']) && $employee['email'] === $username && 
                isset($employee['mobileno']) && $employee['mobileno'] === $password) {
                $_SESSION['employee_logged_in'] = true;
                $_SESSION['employee_username'] = $username;
                $_SESSION['employee_id'] = $employee['empid'] ?? '';
                $_SESSION['employee_name'] = $employee['ename'] ?? '';
                $_SESSION['form_message'] = '<div class="message success">Login successful!</div>';
                header('Location: employee_dashboard.php');
                exit();
            }
        }
    }

    // If no match, check hardcoded credentials
    $validCredentials = ['owner' => 'owner', 'admin' => 'admin', 'employee' => 'employee'];
    if (isset($validCredentials[$username]) && $validCredentials[$username] === $password) {
        $_SESSION['employee_logged_in'] = true;
        $_SESSION['employee_username'] = $username;
        $_SESSION['form_message'] = '<div class="message success">Login successful!</div>';
        header('Location: employee_dashboard.php');
        exit();
    } else {
        $message = '<div class="message error">Invalid username or password.</div>';
    }
}

// --- Handle Employee Registration ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $email = $_POST['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="message error">Invalid email format</div>';
    } else {
        // Check if email already exists
        $emailExists = false;
        if (is_array($employees)) {
            foreach ($employees as $employee) {
                if (isset($employee['email']) && $employee['email'] === $email) {
                    $emailExists = true;
                    break;
                }
            }
        }

        if ($emailExists) {
            $message = '<div class="message error">This email is already registered</div>';
        } else {
            // Last Name validation
            $lname = trim($_POST['lname']);
            if (!preg_match('/^[a-zA-Z]$|^[a-zA-Z]\s[a-zA-Z]$/', $lname)) {
                $message = '<div class="message error">Last Name must be a single letter (a-z, A-Z) OR two letters separated by a single space (e.g., A B).</div>';
            } else {
                // Handle 'Other' selections
                $selectedReligion = $_POST['religion_other'] ?? $_POST['religion'] ?? '';
                if (!empty($_POST['religion_other'])) {
                    $newReligion = trim($_POST['religion_other']);
                    if (!in_array($newReligion, $religions)) {
                        $religions[] = $newReligion;
                        sort($religions);
                        file_put_contents($religionFile, json_encode($religions, JSON_PRETTY_PRINT));
                    }
                } elseif ($selectedReligion === 'Other') {
                    $selectedReligion = '';
                }

                $selectedDesignation = $_POST['designation_other'] ?? $_POST['designation'] ?? '';
                if (!empty($_POST['designation_other'])) {
                    $newDesignation = trim($_POST['designation_other']);
                    if (!in_array($newDesignation, $designations)) {
                        $designations[] = $newDesignation;
                        sort($designations);
                        file_put_contents($designationFile, json_encode($designations, JSON_PRETTY_PRINT));
                    }
                } elseif ($selectedDesignation === 'Other') {
                    $selectedDesignation = '';
                }

                // Handle location data
                $selectedCountry = $_POST['country_other'] ?? $_POST['country'] ?? '';
                $selectedState = $_POST['state_other'] ?? $_POST['state'] ?? '';
                $selectedCity = $_POST['city_other'] ?? $_POST['city'] ?? '';

                if (!empty($_POST['country_other'])) {
                    $newCountry = trim($_POST['country_other']);
                    if (!array_key_exists($newCountry, $statesAndCities)) {
                        $statesAndCities[$newCountry] = [];
                    }
                } elseif ($selectedCountry === 'Other') {
                    $selectedCountry = '';
                }

                if (!empty($_POST['state_other'])) {
                    $newState = trim($_POST['state_other']);
                    if (!empty($selectedCountry) && !array_key_exists($newState, $statesAndCities[$selectedCountry])) {
                        $statesAndCities[$selectedCountry][$newState] = [];
                    }
                } elseif ($selectedState === 'Other') {
                    $selectedState = '';
                }

                if (!empty($_POST['city_other'])) {
                    $newCity = trim($_POST['city_other']);
                    if (!empty($selectedCountry) && !empty($selectedState) && !in_array($newCity, $statesAndCities[$selectedCountry][$selectedState])) {
                        $statesAndCities[$selectedCountry][$selectedState][] = $newCity;
                        sort($statesAndCities[$selectedCountry][$selectedState]);
                    }
                } elseif ($selectedCity === 'Other') {
                    $selectedCity = '';
                }

                file_put_contents($statesAndCitiesFile, json_encode($statesAndCities, JSON_PRETTY_PRINT));

                // Generate new employee ID
                $lastId = 3000;
                if (!empty($employees) && is_array($employees)) {
                    $ids = array_column($employees, 'empid');
                    if (!empty($ids)) {
                        $lastId = max($ids);
                    }
                }
                $nextId = $lastId + 1;

                // Format DOB
                $dob_formatted = '';
                if (!empty($_POST['dob'])) {
                    $dob_timestamp = strtotime($_POST['dob']);
                    if ($dob_timestamp !== false) {
                        $dob_formatted = date('d/m/Y', $dob_timestamp);
                    }
                }

                // Create new employee
                $newEmployee = [
                    'empid' => $nextId,
                    'ename' => $_POST['ename'],
                    'lname' => $lname,
                    'mobileno' => $_POST['mobileno'],
                    'email' => $email,
                    'aadhar' => $_POST['aadhar'],
                    'pincode' => $_POST['pincode'],
                    'address' => $_POST['address'] ?? '',
                    'country' => $selectedCountry,
                    'state' => $selectedState,
                    'city' => $selectedCity,
                    'gender' => $_POST['gender'] ?? '',
                    'dob' => $dob_formatted,
                    'religion' => $selectedReligion,
                    'designation' => $selectedDesignation,
                ];

                $employees[] = $newEmployee;
                file_put_contents($employeeFile, json_encode($employees, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $showSuccess = true;
                $message = '<div class="message success">Registration successful! You can now login with your email and mobile number.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal | Company Name</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --error-color: #f72585;
            --warning-color: #f8961e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
            padding: 20px;
        }

        .portal-container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .portal-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .portal-header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .portal-tabs {
            display: flex;
            background-color: #e9ecef;
        }

        .portal-tab {
            padding: 15px 25px;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray-color);
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }

        .portal-tab:hover {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.1);
        }

        .portal-tab.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background-color: white;
        }

        .portal-content {
            display: none;
            padding: 30px;
        }

        .portal-content.active {
            display: block;
        }

        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        label.required:after {
            content: " *";
            color: var(--error-color);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 16px;
            transition: var(--transition);
            background-color: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .back-to-main-btn {
            display: block;
            text-align: center;
            background-color: var(--gray-color);
            color: white;
            padding: 14px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            margin-top: 20px;
            transition: var(--transition);
        }

        .back-to-main-btn:hover {
            background-color: #5a6268;
            color: white;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-align: center;
        }

        .message.success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0c5460;
            border-left: 4px solid var(--success-color);
        }

        .message.error {
            background-color: rgba(247, 37, 133, 0.1);
            color: #721c24;
            border-left: 4px solid var(--error-color);
        }

        .toggle-optional {
            color: var(--primary-color);
            cursor: pointer;
            margin: 20px 0;
            display: inline-block;
            font-weight: 500;
            transition: var(--transition);
        }

        .toggle-optional:hover {
            color: var(--secondary-color);
        }

        .optional-section {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .select-as-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            margin-top: 8px;
        }

        .form-note {
            font-size: 13px;
            color: var(--gray-color);
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .portal-container {
                margin: 10px auto;
            }
            
            .portal-tabs {
                flex-direction: column;
            }
            
            .portal-tab {
                border-bottom: 1px solid #dee2e6;
                border-left: 3px solid transparent;
            }
            
            .portal-tab.active {
                border-bottom: 1px solid #dee2e6;
                border-left: 3px solid var(--primary-color);
            }
            
            .portal-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="portal-header">
            <h1>Employee Portal</h1>
        </div>
        
        <div class="portal-tabs">
            <div class="portal-tab active" onclick="openTab(event, 'login')">Login</div>
            <div class="portal-tab" onclick="openTab(event, 'register')">Register</div>
        </div>

        <?php echo $message; ?>

        <div id="login" class="portal-content active">
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="username" class="required">Email</label>
                        <input type="email" id="username" name="username" required placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="password" class="required">Mobile Number</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your mobile number"
                               onkeydown="allowOnlyNumbers(event)" 
                               onpaste="sanitizePaste(event)"
                               maxlength="10">
                        <p class="form-note">Use your registered mobile number as password</p>
                    </div>
                    <button type="submit" name="login_submit" class="submit-btn">Login</button>
                </form>
                <a href="login.php" class="back-to-main-btn">Back to Main Dashboard</a>
            </div>
        </div>

        <div id="register" class="portal-content">
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label class="required">Full Name</label>
                        <input type="text" name="ename" required 
                               placeholder="Enter your full name"
                               onkeydown="restrictToAlphabets(event)">
                    </div>

                    <div class="form-group">
                        <label class="required">Last Name</label>
                        <input type="text" name="lname" required 
                               placeholder="Format: A or A B"
                               onkeydown="restrictToLastnameFormat(event)" 
                               onpaste="sanitizeAndEnforceLastnameFormat(event)"
                               maxlength="3" 
                               pattern="[a-zA-Z]{1}|[a-zA-Z]\s[a-zA-Z]{1}" 
                               title="Last Name must be a single letter (e.g., A) OR two letters separated by a space (e.g., A B)">
                        <p class="form-note">Must be a single letter (A-Z) OR two letters separated by space (A B)</p>
                    </div>

                    <div class="form-group">
                        <label class="required">Email</label>
                        <input type="email" name="email" required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label class="required">Mobile Number</label>
                        <input type="tel" name="mobileno" required 
                               placeholder="10 digit mobile number"
                               pattern="[0-9]{10}" 
                               onkeydown="allowOnlyNumbers(event)" 
                               onpaste="sanitizePaste(event)" 
                               maxlength="10">
                    </div>

                    <div class="form-group">
                        <label class="required">Aadhar Number</label>
                        <input type="tel" name="aadhar" required 
                               placeholder="12 digit Aadhar number"
                               pattern="[0-9]{12}" 
                               onkeydown="allowOnlyNumbers(event)" 
                               onpaste="sanitizePaste(event)"
                               maxlength="12">
                    </div>

                    <div class="form-group">
                        <label class="required">Pincode</label>
                        <input type="tel" name="pincode" required 
                               placeholder="6 digit pincode"
                               pattern="[0-9]{6}" 
                               onkeydown="allowOnlyNumbers(event)" 
                               onpaste="sanitizePaste(event)"
                               maxlength="6">
                    </div>

                    <div class="toggle-optional" onclick="toggleOptional()">▼ Show Additional Information</div>
                    <div id="optionalFields" class="optional-section">
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="3" placeholder="Enter your address"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Country</label>
                            <select name="country" onchange="loadStates()">
                                <option value="">-- Select Country --</option>
                            </select>
                            <div id="countryInputContainer" style="display:none; margin-top:10px;">
                                <input type="text" id="countryInput" class="select-as-input" placeholder="Enter country">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>State</label>
                            <select name="state" onchange="loadCities()">
                                <option value="">-- Select State --</option>
                            </select>
                            <div id="stateInputContainer" style="display:none; margin-top:10px;">
                                <input type="text" id="stateInput" class="select-as-input" placeholder="Enter state">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>City</label>
                            <select name="city" onchange="handleOtherSelect('city')">
                                <option value="">-- Select City --</option>
                            </select>
                            <div id="cityInputContainer" style="display:none; margin-top:10px;">
                                <input type="text" id="cityInput" class="select-as-input" placeholder="Enter city">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">-- Select --</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" min="1900-01-01" max="<?php echo date('Y') + 1; ?>-12-31">
                        </div>
                        
                        <div class="form-group">
                            <label>Religion</label>
                            <select name="religion" onchange="handleOtherSelect('religion')">
                                <option value="">-- Select Religion --</option>
                            </select>
                            <div id="religionInputContainer" style="display:none; margin-top:10px;">
                                <input type="text" id="religionInput" class="select-as-input" placeholder="Enter religion">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Designation</label>
                            <select name="designation" onchange="handleOtherSelect('designation')">
                                <option value="">-- Select Designation --</option>
                            </select>
                            <div id="designationInputContainer" style="display:none; margin-top:10px;">
                                <input type="text" id="designationInput" class="select-as-input" placeholder="Enter designation">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="register_submit" class="submit-btn">Register</button>
                </form>
                <a href="login.php" class="back-to-main-btn">Back to Main Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName("portal-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            const tabLinks = document.getElementsByClassName("portal-tab");
            for (let i = 0; i < tabLinks.length; i++) {
                tabLinks[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Input restriction functions
        function restrictToNumbers(event) {
            const allowedKeys = [8, 9, 37, 38, 39, 40, 46];
            if (allowedKeys.includes(event.keyCode) || 
                (event.keyCode >= 48 && event.keyCode <= 57) || 
                (event.keyCode >= 96 && event.keyCode <= 105)) {
                return true;
            }
            event.preventDefault();
            return false;
        }

        function restrictToAlphabets(event) {
            const allowedKeys = [8, 9, 32, 37, 38, 39, 40, 46];
            if (allowedKeys.includes(event.keyCode) || 
                (event.keyCode >= 65 && event.keyCode <= 90)) {
                return true;
            }
            event.preventDefault();
            return false;
        }

        function restrictToLastnameFormat(event) {
            const key = event.key;
            const keyCode = event.keyCode;
            const input = event.target;
            const currentValue = input.value;

            const allowedControlKeys = [8, 9, 37, 38, 39, 40, 46];
            if (allowedControlKeys.includes(keyCode)) return true;

            const upperKey = key.toUpperCase();

            if (currentValue.length === 0) {
                if (key.match(/^[a-zA-Z]$/)) return true;
            } else if (currentValue.length === 1) {
                if (currentValue.match(/^[a-zA-Z]$/)) {
                    if (key === ' ') return true;
                    else if (key.match(/^[a-zA-Z]$/)) {
                        event.preventDefault();
                        return false;
                    }
                }
            } else if (currentValue.length === 2) {
                if (currentValue.match(/^[a-zA-Z]\s$/)) {
                    if (key.match(/^[a-zA-Z]$/)) return true;
                }
            } else if (currentValue.length >= 3) {
                event.preventDefault();
                return false;
            }

            event.preventDefault();
            return false;
        }

        function sanitizeAndEnforceLastnameFormat(event) {
            const clipboardData = event.clipboardData || window.clipboardData;
            let pastedText = clipboardData.getData('text/plain');
            
            event.preventDefault();
            pastedText = pastedText.replace(/[^a-zA-Z\s]/g, '');
            pastedText = pastedText.trim().replace(/\s+/g, ' ');

            const input = event.target;
            let finalValue = '';

            if (pastedText.match(/^[a-zA-Z]$/)) {
                finalValue = pastedText;
            } else if (pastedText.match(/^[a-zA-Z]\s[a-zA-Z]$/)) {
                finalValue = pastedText;
            }

            input.value = finalValue;
            input.selectionStart = input.selectionEnd = finalValue.length;
        }

        function allowOnlyNumbers(event) {
            const keyCode = event.which ? event.which : event.keyCode;
            if ([8, 9, 37, 38, 39, 40, 46].includes(keyCode) || 
                (keyCode >= 48 && keyCode <= 57) || 
                (keyCode >= 96 && keyCode <= 105)) {
                return true;
            }
            event.preventDefault();
            return false;
        }

        function sanitizePaste(event) {
            const clipboardData = event.clipboardData || window.clipboardData;
            const pastedText = clipboardData.getData('text/plain');
            const input = event.target;
            
            const sanitizedText = pastedText.replace(/[^0-9]/g, '');
            
            event.preventDefault();
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const textBefore = input.value.substring(0, start);
            const textAfter = input.value.substring(end, input.value.length);
            
            input.value = textBefore + sanitizedText + textAfter;
            input.selectionStart = input.selectionEnd = start + sanitizedText.length;
        }

        // Helper function to create options
        function createOption(value, text) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            return option;
        }

        // Function to handle "Other" selection
        function handleOtherSelect(field) {
            const selectElement = document.querySelector(`select[name="${field}"]`);
            const otherInputContainer = document.getElementById(`${field}InputContainer`);
            const otherInputField = document.getElementById(`${field}Input`);
            
            const originalSelectName = selectElement.dataset.originalName || selectElement.name;
            selectElement.dataset.originalName = originalSelectName;

            if (selectElement.value === 'Other') {
                selectElement.style.display = 'none';
                selectElement.removeAttribute('name');
                otherInputContainer.style.display = 'block';
                otherInputField.name = `${field}_other`;
                otherInputField.value = '';
                otherInputField.focus();
            } else {
                selectElement.style.display = 'block';
                selectElement.name = originalSelectName;
                otherInputContainer.style.display = 'none';
                otherInputField.removeAttribute('name');
            }
        }

        // Function to load states and cities based on selected country
        function loadStates() {
            const countrySelect = document.querySelector('select[name="country"]');
            const stateSelect = document.querySelector('select[name="state"]');
            const citySelect = document.querySelector('select[name="city"]'); 

            const countryInputContainer = document.getElementById('countryInputContainer');
            const stateInputContainer = document.getElementById('stateInputContainer');
            const cityInputContainer = document.getElementById('cityInputContainer');

            const countryInputField = document.getElementById('countryInput');
            const stateInputField = document.getElementById('stateInput');
            const cityInputField = document.getElementById('cityInput');

            const selectedCountry = countrySelect.value;

            stateSelect.innerHTML = '<option value="">-- Select State --</option>';
            citySelect.innerHTML = '<option value="">-- Select City --</option>';

            stateInputContainer.style.display = 'none';
            cityInputContainer.style.display = 'none';
            stateInputField.removeAttribute('name');
            cityInputField.removeAttribute('name');

            stateSelect.name = 'state';
            citySelect.name = 'city';
            stateSelect.style.display = 'block';
            citySelect.style.display = 'block';

            if (selectedCountry === 'Other') {
                countrySelect.style.display = 'none';
                countrySelect.removeAttribute('name');
                countryInputContainer.style.display = 'block';
                countryInputField.name = 'country_other';
                countryInputField.value = '';
                countryInputField.focus();

                stateSelect.style.display = 'none';
                stateSelect.removeAttribute('name');
                stateInputContainer.style.display = 'block';
                stateInputField.name = 'state_other';
                stateInputField.value = '';

                citySelect.style.display = 'none';
                citySelect.removeAttribute('name');
                cityInputContainer.style.display = 'block';
                cityInputField.name = 'city_other';
                cityInputField.value = '';

                return;
            } else {
                countrySelect.style.display = 'block';
                countrySelect.name = 'country';
                countryInputContainer.style.display = 'none';
                countryInputField.removeAttribute('name');
            }

            const statesAndCities = <?php echo json_encode($statesAndCities); ?>;

            if (selectedCountry && statesAndCities[selectedCountry]) {
                const sortedStates = Object.keys(statesAndCities[selectedCountry]).sort();
                sortedStates.forEach(state => {
                    stateSelect.appendChild(createOption(state, state));
                });
            }
            stateSelect.appendChild(createOption('Other', 'Other'));
        }

        // Function to load cities based on selected state
        function loadCities() {
            const countrySelect = document.querySelector('select[name="country"]');
            const stateSelect = document.querySelector('select[name="state"]');
            const citySelect = document.querySelector('select[name="city"]');

            const stateInputContainer = document.getElementById('stateInputContainer');
            const cityInputContainer = document.getElementById('cityInputContainer');

            const stateInputField = document.getElementById('stateInput');
            const cityInputField = document.getElementById('cityInput');

            const selectedCountry = countrySelect.value;
            const selectedState = stateSelect.value;

            citySelect.innerHTML = '<option value="">-- Select City --</option>';

            cityInputContainer.style.display = 'none';
            cityInputField.removeAttribute('name');

            citySelect.name = 'city';
            citySelect.style.display = 'block';

            if (selectedState === 'Other') {
                stateSelect.style.display = 'none';
                stateSelect.removeAttribute('name');
                stateInputContainer.style.display = 'block';
                stateInputField.name = 'state_other';
                stateInputField.value = '';
                stateInputField.focus();

                citySelect.style.display = 'none';
                citySelect.removeAttribute('name');
                cityInputContainer.style.display = 'block';
                cityInputField.name = 'city_other';
                cityInputField.value = '';

                return;
            } else {
                stateSelect.style.display = 'block';
                stateSelect.name = 'state';
                stateInputContainer.style.display = 'none';
                stateInputField.removeAttribute('name');
            }

            const statesAndCities = <?php echo json_encode($statesAndCities); ?>;

            if (selectedCountry && selectedState && statesAndCities[selectedCountry] && statesAndCities[selectedCountry][selectedState]) {
                const sortedCities = statesAndCities[selectedCountry][selectedState].sort();
                sortedCities.forEach(city => {
                    citySelect.appendChild(createOption(city, city));
                });
            }
            citySelect.appendChild(createOption('Other', 'Other'));
        }

        // Function for toggling optional fields visibility
        function toggleOptional() {
            const section = document.getElementById('optionalFields');
            const toggleText = document.querySelector('.toggle-optional');

            if (section.style.display === 'block') {
                section.style.display = 'none';
                toggleText.textContent = '▼ Show Additional Information';
            } else {
                section.style.display = 'block';
                toggleText.textContent = '▲ Hide Additional Information';
            }
        }

        // Execute when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Load Religions dropdown
            const religionSelect = document.querySelector('select[name="religion"]');
            if (religionSelect) {
                <?php foreach ($religions as $r): ?>
                    if ("<?php echo htmlspecialchars($r); ?>" !== "Other") {
                        religionSelect.appendChild(createOption("<?php echo htmlspecialchars($r); ?>", "<?php echo htmlspecialchars($r); ?>"));
                    }
                <?php endforeach; ?>
                religionSelect.appendChild(createOption('Other', 'Other'));
            }

            // Load Designations dropdown
            const designationSelect = document.querySelector('select[name="designation"]');
            if (designationSelect) {
                <?php foreach ($designations as $d): ?>
                    if ("<?php echo htmlspecialchars($d); ?>" !== "Other") {
                        designationSelect.appendChild(createOption("<?php echo htmlspecialchars($d); ?>", "<?php echo htmlspecialchars($d); ?>"));
                    }
                <?php endforeach; ?>
                designationSelect.appendChild(createOption('Other', 'Other'));
            }

            // Load Countries dropdown
            const countrySelect = document.querySelector('select[name="country"]');
            if (countrySelect) {
                countrySelect.innerHTML = '<option value="">-- Select Country --</option>';
                const statesAndCities = <?php echo json_encode($statesAndCities); ?>;
                const sortedCountries = Object.keys(statesAndCities).sort();
                sortedCountries.forEach(country => {
                    countrySelect.appendChild(createOption(country, country));
                });
                countrySelect.appendChild(createOption('Other', 'Other'));
                countrySelect.addEventListener('change', loadStates);
            }

            // Attach event listener for state select
            const stateSelect = document.querySelector('select[name="state"]');
            if (stateSelect) {
                stateSelect.addEventListener('change', loadCities);
            }

            // Attach event listener for city select
            const citySelect = document.querySelector('select[name="city"]');
            if (citySelect) {
                citySelect.addEventListener('change', function() { handleOtherSelect('city'); });
            }

            // Hide all 'Other' input fields initially
            const fieldsToHideOtherInputs = ['religion', 'designation', 'country', 'state', 'city'];
            fieldsToHideOtherInputs.forEach(field => {
                const otherInputContainer = document.getElementById(`${field}InputContainer`);
                if (otherInputContainer) {
                    otherInputContainer.style.display = 'none';
                }
            });

            // Set login tab as active by default
            document.getElementById('login').classList.add('active');
            document.querySelector('.portal-tab[onclick*="login"]').classList.add('active');
        });
    </script>    
</body>
</html>