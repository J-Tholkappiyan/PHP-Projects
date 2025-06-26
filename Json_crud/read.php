<?php
$data = json_decode(file_get_contents('data.json'), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container mt-5">
    <div class="card shadow-lg">
      <div class="card-header bg-primary text-white text-center">
        <h2>User List</h2>
      </div>
      <div class="card-body">
        <ul class="list-group mb-4">
          <?php foreach ($data as $user): ?>
            <li class="list-group-item">
              <strong>ID:</strong> <?= $user['id'] ?> |
              <strong>Name:</strong> <?= $user['name'] ?> |
              <strong>Email:</strong> <?= $user['email'] ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="text-center">
          <a href="create.html" class="btn btn-success">Add New User</a>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
