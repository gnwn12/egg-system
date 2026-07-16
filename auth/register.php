<?php
session_start();
require_once __DIR__ . "/../config/db.php";

$message = "";

if (isset($_POST['register'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (strlen($password) < 4) {
        $message = ["text" => "Password minimal 4 karakter", "type" => "error"];
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $check = mysqli_query($conn,
            "SELECT * FROM users WHERE username='$username'"
        );

        if (mysqli_num_rows($check) > 0) {
            $message = ["text" => "Username sudah digunakan!", "type" => "error"];
        } else {

            mysqli_query($conn, "
                INSERT INTO users (username, password, role)
                VALUES ('$username', '$hashedPassword', '$role')
            ");

            $message = ["text" => "Register berhasil, silakan login", "type" => "success"];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Sistem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-md">

    <div class="bg-white p-5 rounded-2xl shadow mb-4 flex items-center gap-4">


        <div>
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="user-plus" class="text-blue-500"></i>
                Register Akun
            </h1>
            <p class="text-sm text-gray-500">
                Buat akun untuk mengakses sistem
            </p>
        </div>

    </div>

    <div class="bg-white p-6 rounded-2xl shadow">

        <?php if (!empty($message)): ?>
            <div class="mb-4 p-3 rounded flex items-center gap-2 text-sm
                <?= $message['type'] == 'success' 
                    ? 'bg-green-100 text-green-700' 
                    : 'bg-red-100 text-red-700' ?>">
                
                <i data-lucide="<?= $message['type'] == 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
                <?= $message['text'] ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="flex flex-col gap-4">

            <div>
                <label class="text-sm text-gray-600">Username</label>
                <input type="text"
                       name="username"
                       placeholder="Masukkan username"
                       class="w-full border p-3 rounded-lg mt-1 focus:ring-2 focus:ring-blue-400 outline-none"
                       required>
            </div>

            <div>
                <label class="text-sm text-gray-600">Password</label>
                <input type="password"
                       name="password"
                       placeholder="Masukkan password"
                       class="w-full border p-3 rounded-lg mt-1 focus:ring-2 focus:ring-blue-400 outline-none"
                       required>
            </div>

            <div>
                <label class="text-sm text-gray-600">Role</label>
                <select name="role"
                        class="w-full border p-3 rounded-lg mt-1 focus:ring-2 focus:ring-blue-400 outline-none"
                        required>
                    <option value="">Pilih Role</option>
                    <option value="peternak">Peternak</option>
                    <option value="toko">Toko / Pemasok</option>
                </select>
            </div>

            <button name="register"
                    class="bg-blue-500 text-white p-3 rounded-xl hover:bg-blue-600 flex items-center justify-center gap-2">
                <i data-lucide="user-plus"></i>
                Daftar Sekarang
            </button>

        </form>

        <p class="text-center text-sm text-gray-500 mt-4">
            Sudah punya akun?
            <a href="login.php" class="text-blue-600 hover:underline">
                Login disini
            </a>
        </p>

    </div>

</div>

<script>
lucide.createIcons();
</script>

</body>
</html>