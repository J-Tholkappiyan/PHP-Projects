<!DOCTYPE html>
<html>
<body>
<form method="post">
Enter Your Name: <input type="text" name="name">
<input type="submit">
</form>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$name = $_POST['name'];
echo "Hello, " . htmlspecialchars($name);
}
?>
</body>
</html>