<?php
$data = json_decode(file_get_contents('php://input'), true);

// Save to employee.json
$empFile = 'employee.json';
$employees = file_exists($empFile) ? json_decode(file_get_contents($empFile), true) : [];
$employees[] = $data;
file_put_contents($empFile, json_encode($employees, JSON_PRETTY_PRINT));

// Update statesandcities.json
$scFile = 'statesandcities.json';
$statesCities = file_exists($scFile) ? json_decode(file_get_contents($scFile), true) : [];

$c = $data['country'];
$s = $data['state'];
$ct = $data['city'];

if (!isset($statesCities[$c])) {
    $statesCities[$c] = [$s => [$ct]];
} elseif (!isset($statesCities[$c][$s])) {
    $statesCities[$c][$s] = [$ct];
} elseif (!in_array($ct, $statesCities[$c][$s])) {
    $statesCities[$c][$s][] = $ct;
}

file_put_contents($scFile, json_encode($statesCities, JSON_PRETTY_PRINT));

echo "Employee Saved Successfully!";
?>
