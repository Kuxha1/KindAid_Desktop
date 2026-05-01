<?php
// For local database only its does not work with xamp
// For xamp use other db.php file
// for local MySQL Add your MySQL  details in db0.php like and rename this file to db.php  
$conn = new mysqli("localhost", "root", "085279", "kindaid");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>