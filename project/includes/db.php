<?php
$host = "localhost";     
$user = "root";         
$pass = "";             
$dbname = "university_system";  

// Δημιουργία σύνδεσης
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Έλεγχος σύνδεσης
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
