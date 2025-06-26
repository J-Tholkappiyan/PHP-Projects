<?php 
$data = json_decode(file_get_contents('data.json'), true);

$newUser = [
    "id" => time(),
    "name" => $_POST['name'],
    "email" => $_POST['email']
];

$data[] = $newUser;

file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create User Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="alert alert-success text-center shadow-lg">
          <h4>User created successfully!</h4>
          <a href="read.php" class="btn btn-outline-primary mt-3">View All Users</a>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
