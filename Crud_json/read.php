<?php
$data = json_decode(file_get_contents('data/data.json'), true) ?: [];

echo "<h2>User List</h2><ul>";
foreach ($data as $user) {
    $photo = !empty($user['photo']) 
        ? "<img src='{$user['photo']}' style='width:100px; height:100px; border-radius:50%; object-fit:cover; margin-right:10px;'>" 
        : "<div style='width:100px; height:100px; border-radius:50%; background:#ccc; display:inline-block;'></div>";
    
    $description = !empty($user['description']) ? "<br>Description: {$user['description']}" : "";
    
    echo "<li style='margin-bottom:15px; display:flex; align-items:center;'>
            {$photo}
            <div>
                ID: {$user['id']}<br>
                Name: {$user['name']}<br>
                Email: {$user['email']}
                {$description}
            </div>
          </li>";
}
echo "</ul>";

echo "<br><a href='create.html'>Add New User</a>";
?>