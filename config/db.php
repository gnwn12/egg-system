<?php
$host = "acela.proxy.rlwy.net";
$user = "root";
$pass = "lQWImwnvBhVpqbswKiGCYDOeEFYnuENf";
$db   = "railway";
$port = 21972

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>