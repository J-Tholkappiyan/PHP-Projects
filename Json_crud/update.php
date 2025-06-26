<?php
$data = json_decode(file_get_contents('data.json'), true);

$id = $_POST['id'];
$updated = false;

foreach ($data as &$user) {
    if ($user['id'] == $id) {
        $user['name'] = $_POST['name'];
        $user['email'] = $_POST['email'];
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
    $message = "User updated successfully!";
    $alert = "success";
} else {
    $message = "User not found.";
    $alert = "danger";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="alert alert-<?= $alert ?> text-center shadow-lg">
          <h4><?= $message ?></h4>
          <a href="read.php" class="btn btn-outline-primary mt-3">View Users</a>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
