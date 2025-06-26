<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Collect form data
$name = $_POST['name'] ?? '';
$regno = $_POST['regno'] ?? '';
$age = $_POST['age'] ?? '';
$country = $_POST['country'] ?? '';
$state = $_POST['state'] ?? '';
$city = $_POST['city'] ?? '';

$newCountry = $_POST['newCountry'] ?? '';
$newState = $_POST['newState'] ?? '';
$newCity = $_POST['newCity'] ?? '';

$stateCityFile = 'statesandcities.json';
$employeeFile = 'employee.json';

// ===========================
// ✅ 1. Handle new entries in statesandcities.json
// ===========================
if (!file_exists($stateCityFile)) {
    echo "JSON file not found.";
    exit;
}

$json = file_get_contents($stateCityFile);
$data = json_decode($json, true);

// Handle custom country/state/city
if ($country === 'Other' && $newCountry && $newState && $newCity) {
    $country = $newCountry;
    $state = $newState;
    $city = $newCity;

    if (!isset($data[$country])) {
        $data[$country] = [];
    }

    if (!isset($data[$country][$state])) {
        $data[$country][$state] = [];
    }

    if (!in_array($city, $data[$country][$state])) {
        $data[$country][$state][] = $city;
    }

    // Save updates
    file_put_contents($stateCityFile, json_encode($data, JSON_PRETTY_PRINT));
}

// ===========================
// ✅ 2. Save entry to employee.json (your request)
// ===========================
$employeeEntry = [
    'name' => $name,
    'regno' => $regno,
    'age' => $age,
    'country' => $country,
    'state' => $state,
    'city' => $city
];

$employeeData = [];

if (file_exists($employeeFile)) {
    $content = file_get_contents($employeeFile);
    $employeeData = json_decode($content, true);
    if (!is_array($employeeData)) {
        $employeeData = [];
    }
}

$employeeData[] = $employeeEntry;

file_put_contents($employeeFile, json_encode($employeeData, JSON_PRETTY_PRINT));

// ===========================
// ✅ 3. Output confirmation
// ===========================
echo "<h2>✔ Registration Saved Successfully</h2>";
echo "<pre>";
echo "Name: $name\nReg No: $regno\nAge: $age\nCountry: $country\nState: $state\nCity: $city";
echo "</pre>";
?>
