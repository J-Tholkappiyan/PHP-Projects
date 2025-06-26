<?php
// Include DB connection
$conn = mysqli_connect("localhost", "root", "", "db_crud");

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM student WHERE id=$id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    $name = $row['name'];
    $address = $row['address'];
    $mobile = $row['mobile'];
    $image = $row['image'];
    $description = $row['description'];
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $description = $_POST['description'];
    $old_image = $_POST['old_image'];

    // Check if new image uploaded
    if ($_FILES['image']['name'] != '') {
        $filename = $_FILES["image"]["name"];
        $tempname = $_FILES["image"]["tmp_name"];
        $folder = "uploads/" . $filename;
        move_uploaded_file($tempname, $folder);
        $new_image = $folder;
    } else {
        $new_image = $old_image; // retain old image
    }

    $sql = "UPDATE student SET 
                name='$name',
                address='$address',
                mobile='$mobile',
                image='$new_image',
                description='$description'
            WHERE id=$id";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: index.php");
    } else {
        echo "Update Failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Student Record</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <input type="hidden" name="old_image" value="<?php echo $image ?>">

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="<?php echo $name ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" value="<?php echo $address ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Mobile</label>
            <input type="text" name="mobile" value="<?php echo $mobile ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Current Image</label><br>
            <img src="<?php echo $image ?>" width="100">
        </div>

        <div class="mb-3">
            <label>Change Image</label>
            <input type="file" name="image" class="form-control">
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo $description ?></textarea>
        </div>

        <button type="submit" name="update" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
