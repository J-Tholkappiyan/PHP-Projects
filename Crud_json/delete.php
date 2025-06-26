<?php
$data = json_decode(file_get_contents('data/data.json'), true);

$id = $_POST['id'];
$found = false;

$data = array_filter($data, function($user) use ($id, &$found) {
    if ($user['id'] == $id) {
        // Delete the photo file if exists
        if (!empty($user['photo']) && file_exists($user['photo'])) {
            unlink($user['photo']);
        }
        $found = true;
        return false;
    }
    return true;
});

if ($found) {
    file_put_contents('data/data.json', json_encode(array_values($data), JSON_PRETTY_PRINT));
    echo "User deleted! <a href='read.php'>View Users</a>";
} else {
    echo "User not found.";
}
?>