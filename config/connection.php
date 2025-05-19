<?php 
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hotel_reservation";

$conn = new mysqli($host,$user,$pass ,$dbname);

if ($conn->connect_error) {
    die("gagal koneksi ke database" . $conn->connect_error);
}

// ini database ya 
?>