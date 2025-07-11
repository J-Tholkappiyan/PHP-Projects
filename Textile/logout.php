<?php
session_start();

if (isset($_GET['type'])) {
    if ($_GET['type'] === 'client') {
        unset($_SESSION['client_logged_in']);
        unset($_SESSION['client_id']);
        unset($_SESSION['client_name']);
        session_destroy(); // Destroy all session data
        header('Location: client_login.php');
        exit();
    } elseif ($_GET['type'] === 'employee') {
        unset($_SESSION['employee_logged_in']);
        session_destroy(); // Destroy all session data
        header('Location: login.php');
        exit();
    }
}

// Default redirect if type is not specified or invalid
header('Location: login.php');
exit();
?>