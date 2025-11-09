<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "student_performance";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Database Connection failed: " . $conn->connect_error);
}
?>
