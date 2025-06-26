
<?php

$connection = mysqli_connect("localhost", "root", "", "db_crud");
if (!$connection)
{
    die("Connection failed: " . mysqli_connect_error());
}
$xls_filename = 'export_' . date('Y-m-d') . '.xls'; // Define Excel (.xls) file name
$sql = "SELECT * FROM student"; 
$result = mysqli_query($connection, $sql); 
if (!$result)
 {
    die("Failed to execute query: " . mysqli_error($connection));
}
header("Content-Type: application/xls");
header("Content-Disposition: attachment; filename=$xls_filename");
header("Pragma: no-cache");
header("Expires: 0");
// Define separator (defines columns in excel & tabs in word)
$sep = "\t"; // tabbed character
$field_count = mysqli_num_fields($result);
for ($i = 0; $i < $field_count; $i++) {
    echo mysqli_fetch_field_direct($result, $i)->name . "\t";  // Print column name
}
print("\n");
while ($row = mysqli_fetch_row($result))
{
    $schema_insert = "";
    for ($j = 0; $j < $field_count; $j++)
 {
        if (!isset($row[$j]))
	 {
            $schema_insert .= "NULL" . $sep;
        } elseif ($row[$j] != "") {
            $schema_insert .= "$row[$j]" . $sep;
        } 
	else {
            $schema_insert .= "" . $sep;
        }
    }
    // Clean up data to avoid issues with tabs or new lines
    $schema_insert = str_replace($sep . "$", "", $schema_insert);
    $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
    $schema_insert .= "\t";

    // Print the row data
    print(trim($schema_insert));
    print "\n";
}

// Close the connection
mysqli_close($connection);


?>
   
