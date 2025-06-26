<!DOCTYPE html>
<html>
<head>
    <title>Arithmetic Operations</title>
</head>
<body>
    <h2>Simple Arithmetic Calculator</h2>

    <form method="post">
        Enter First Number: <input type="number" name="num1" required><br><br>
        Enter Second Number: <input type="number" name="num2" required><br><br>

        Choose Operation:
        <select name="operation">
            <option value="add">Addition (+)</option>
            <option value="sub">Subtraction (-)</option>
            <option value="mul">Multiplication (*)</option>
            <option value="div">Division (/)</option>
        </select><br><br>

        <input type="submit" name="submit" value="Calculate">
    </form>

    <hr>

    <?php
    if (isset($_POST['submit'])) {
        $num1 = $_POST['num1'];
        $num2 = $_POST['num2'];
        $operation = $_POST['operation'];

        echo "<strong>Result: </strong>";

        switch ($operation) {
            case "add":
                echo "$num1 + $num2 = " . ($num1 + $num2);
                break;
            case "sub":
                echo "$num1 - $num2 = " . ($num1 - $num2);
                break;
            case "mul":
                echo "$num1 * $num2 = " . ($num1 * $num2);
                break;
            case "div":
                if ($num2 != 0) {
                    echo "$num1 / $num2 = " . ($num1 / $num2);
                } else {
                    echo "Division by zero is not allowed.";
                }
                break;
            default:
                echo "Invalid Operation";
        }
    }
    ?>
</body>
</html>
