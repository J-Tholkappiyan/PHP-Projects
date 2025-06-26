<?php
// Function to divide two numbers with error handling
function divide($a, $b) {
    if ($b == 0) {
        return "Cannot divide by zero";
    } else {
        return $a / $b;
    }
}

echo divide(20, 4); // Output: 5
?>
