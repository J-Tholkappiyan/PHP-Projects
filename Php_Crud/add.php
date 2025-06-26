<?php
$connection = mysqli_connect("localhost","root","");
$db = mysqli_select_db($connection,"db_crud");

if(isset($_POST['submit'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $description = $_POST['description'];

    $imgName = $_FILES['image']['name'];
    $tmpName = $_FILES['image']['tmp_name'];
    $folder = "uploads/" . $imgName;
    move_uploaded_file($tmpName, $folder);

    $sql = "INSERT INTO student(name, address, mobile, image, description) 
            VALUES('$name', '$address', '$mobile', '$folder', '$description')";

    if(mysqli_query($connection, $sql)) {
        echo '<script> location.replace("index.php")</script>';  
    } else {
        echo "Error: " . $connection->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Crud Application</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

        <div class="container">
            <div class="row">
                 <div class="col-md-9">
                    <div class="card">
                    <div class="card-header">
                        <h1> Student Crud Application </h1>
                    </div>
                    <div class="card-body">
                    <form action="add.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control"  placeholder="Enter Name"> 
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control"  placeholder="Enter Address"> 
                        </div>

                        <div class="form-group">
                            <label>Mobile</label>
                            <input type="text" name ="mobile" class="form-control"  placeholder="Enter Mobile"> 
                        </div>
                                            <div class="form-group">
                            <label>Upload Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" placeholder="Enter Description"></textarea>
                        </div>

                        <br/>
                        <input type="submit" class="btn btn-primary" name="submit" value="Register">
                    </form>
                    </div>
                    </div>

                </div>
            
            </div>
        </div>

</body>
</html>