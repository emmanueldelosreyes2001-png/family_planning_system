<?php
$conn = new mysqli("localhost", "root", "", "family_planning");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>