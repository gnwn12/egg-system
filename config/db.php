<?php
$host = getenv('MYSQLHOST') ?: "acela.proxy.rlwy.net";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "lQWImwnvBhVpqbswKiGCYDOeEFYnuENf";
$db   = getenv('MYSQLDATABASE') ?: "railway";
$port = getenv('MYSQLPORT') ?: 21972;

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// TAMBAHKAN BARIS INI UNTUK MENGATASI ERROR DATE 0000-00-00
mysqli_query($conn, "SET sql_mode = ''"); 

?>