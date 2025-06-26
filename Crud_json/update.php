<?php
$data = json_decode(file_get_contents('data/data.json'), true);

$id = $_POST['id'];
$updated = false;

foreach ($data as &$user) {
    if ($user['id'] == $id) {
        // Handle photo update
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Delete old photo if exists
            if (!empty($user['photo']) && file_exists($user['photo'])) {
                unlink($user['photo']);
            }
            
            // Upload new photo
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoName = uniqid() . '.' . $ext;
            $photoPath = 'uploads/' . $photoName;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
            $user['photo'] = $photoPath;
        }

        $user['name'] = $_POST['name'];
        $user['email'] = $_POST['email'];
        $user['description'] = $_POST['description'] ?? $user['description'] ?? '';
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents('data/data.json', json_encode($data, JSON_PRETTY_PRINT));
    echo "User updated! <a href='read.php'>View Users</a>";
} else {
    echo "User not found.";
}
?>