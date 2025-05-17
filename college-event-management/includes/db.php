<?php
// Database connection for Integral University Event System
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "integral_event_db";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
