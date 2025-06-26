<?php
$filename = "New.txt";
// ----------- Writing to File -------------
$data = "Hello, Hi friends.\nWelcome to PHP File Handling!\n";
file_put_contents($filename, $data); // Overwrites file
echo "<h3>✅ Data written to $filename</h3>";
// ----------- Reading from File -------------
if (file_exists($filename)) {
$readData = file_get_contents($filename);
echo "<h3>✅ Reading from $filename:</h3>";
echo "<pre>$readData</pre>";
} else {
echo "File not found!";
}
?>