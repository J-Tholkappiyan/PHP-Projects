<?php
// Sample JSON as string
$jsonString = '{
"id": 101,
"name": "Srinivasan",
"skills": ["HTML", "CSS", "PHP"],
"location": {
"city": "Chennai",
"state": "Tamil Nadu"
}
}';
// ✅ Convert JSON to associative array
$dataArray = json_decode($jsonString, true);
// ✅ Convert JSON to object (default)
$dataObject = json_decode($jsonString);
// ----------- Using Array ----------
echo "<h4>Using Array</h4>";
echo "ID: " . $dataArray['id'] . "<br>";
echo "Name: " . $dataArray['name'] . "<br>";
echo "First Skill: " . $dataArray['skills'][0] . "<br>";
echo "City: " . $dataArray['location']['city'] . "<br>";
// ----------- Using Object ----------
echo "<h4>Using Object</h4>";
echo "ID: " . $dataObject->id . "<br>";
echo "Name: " . $dataObject->name . "<br>";
echo "First Skill: " . $dataObject->skills[0] . "<br>";
echo "City: " . $dataObject->location->city . "<br>";
?>