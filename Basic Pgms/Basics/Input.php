<!DOCTYPE html>
<html>
<head>
    <title>Simple PHP Arithmetic</title>
</head>
<body>

<h2>Enter Two Numbers</h2>

<form method="post">
    First Number: <input type="number" name="num1" required><br><br>
    Second Number: <input type="number" name="num2" required><br><br>
    
    <input type="submit" name="add" value="Add">
    <input type="submit" name="sub" value="Subtract">
    <input type="submit" name="mul" value="Multiply">
    <input type="submit" name="div" value="Divide">
</form>

<hr>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $a = $_POST["num1"];
    $b = $_POST["num2"];

    if (isset($_POST['add'])) {
        $result = $a + $b;
        echo "Result: $a + $b = $result";
    } elseif (isset($_POST['sub'])) {
        $result = $a - $b;
        echo "Result: $a - $b = $result";
    } elseif (isset($_POST['mul'])) {
        $result = $a * $b;
        echo "Result: $a × $b = $result";
    } elseif (isset($_POST['div'])) {
        if ($b != 0) {
            $result = $a / $b;
            echo "Result: $a ÷ $b = $result";
        } else {
            echo "Cannot divide by zero!";
        }
    }
}
?>

</body>
</html>
