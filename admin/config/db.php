<?php
// For xamp use file
// for xamp Add your phpmyadmin details in db.php
$conn = mysqli_connect("127.0.0.1:3307", "root", "", "kindaid");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>