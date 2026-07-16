<?php
include __DIR__ . "/../config/db.php";
session_start();

/* =========================
   CEK SUDAH LOGIN
========================= */
if (isset($_SESSION['user'])) {

    if ($_SESSION['user']['role'] == 'peternak') {
        header("Location: ../dashboard_peternak.php");
    } else {
        header("Location: ../dashboard_toko.php");
    }
    exit;
}

$message = "";

/* =========================
   HANDLE LOGIN
========================= */
if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,
        "SELECT * FROM users WHERE username='$username' LIMIT 1"
    );

    $user = mysqli_fetch_assoc($query);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user'] = $user;

        if ($user['role'] == 'peternak') {
            header("Location: ../dashboard_peternak.php");
        } else {
            header("Location: ../dashboard_toko.php");
        }
        exit;

    } else {
        $message = "❌ Username atau password salah";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Sistem</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    

<div class="bg-white p-8 rounded-2xl shadow w-full max-w-sm">

    <h2 class="text-2xl font-bold mb-4 text-center">
        Login Sistem Telur Asin
    </h2>

    <!-- ALERT -->
    <?php if ($message): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col gap-3">

        <input type="text"
               name="username"
               placeholder="Username"
               class="border p-3 rounded-lg"
               required>

        <input type="password"
               name="password"
               placeholder="Password"
               class="border p-3 rounded-lg"
               required>

        <button name="login"
                class="bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">
            Login
        </button>

    </form>

</div>

</body>
</html>