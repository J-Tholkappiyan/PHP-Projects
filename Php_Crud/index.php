<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Crud Application</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.min.js" type="text/javascript"></script>
</head>
<body>
        <div class="container">
            <div class="row">
                 <div class="col-md-12">
                    <div class="card">
                    <div class="card-header"><h1> Student CRUD Application </h1></div>
                    <div class="card-body">
                    <button class="btn btn-success">
                        <a href="add.php" class="text-light"> Add New</a>
                    </button>
                        
                    <br/>
                    <br/>

                    <table class="table">
                        <thead>
                            <tr>
                            <th scope="col">Id</th>
                            <th scope="col">Name</th>
                            <th scope="col">Address</th>
                            <th scope="col">Mobile</th>
                            <th scope="col">Option</th>
                            <th scope="col">Image</th>
                            <th scope="col">Description</th>

                            </tr>
                        </thead>

                        <tbody>
                                <?php
                                $connection = mysqli_connect("localhost","root","");
                                $db = mysqli_select_db($connection,"db_crud");

                                $sql = "select * from student";
                                $run = mysqli_query($connection, $sql);
                                $id= 1;

                                while($row = mysqli_fetch_array($run))
                                {
                                    $uid = $row['id'];
                                    $name = $row['name'];
                                    $address = $row['address'];
                                    $mobile = $row['mobile'];
                                    $image = $row['image'];
                                    $description = $row['description'];

                                ?>

                                   <tr>
                                        <td><?php echo $uid ?></td>
                                        <td><?php echo $name ?></td>
                                        <td><?php echo $address ?></td>
                                        <td><?php echo $mobile ?></td>
                                        <td><img src="<?php echo $image ?>" width="70"></td>
                                        <td><?php echo $description ?></td>


                                        <td>
                                        <button class="btn btn-success">
                                             <a href='edit.php?edit=<?php echo $uid ?>' class="text-light"> Edit </a>
                                         </button> &nbsp;

                                       <button class="btn btn-danger">
                                        <a href='delete.php?del=<?php echo $uid ?>' class="text-light"> Delete </a> </button>
                                        </td>
                                   </tr>
                                    <?php  
                                    } 
                                    ?>
                        </tbody>

                        </table>

                    </div>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>