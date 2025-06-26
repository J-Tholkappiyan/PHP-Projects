<?php
$data = json_decode(file_get_contents('data/data.json'), true) ?: [];

// Handle photo upload
$photoPath = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoName = uniqid() . '.' . $ext;
    $photoPath = 'uploads/' . $photoName;
    move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
}

$newUser = [
    "id" => time(),
    "name" => $_POST['name'],
    "email" => $_POST['email'],
    "photo" => $photoPath,
    "description" => $_POST['description'] ?? ''
];

$data[] = $newUser;
file_put_contents('data/data.json', json_encode($data, JSON_PRETTY_PRINT));

echo "User created successfully! <a href='read.php'>View All Users</a>";
?>