<?php
include "config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'toko') {
    header("Location: auth/login.php");
    exit;
}

$user = $_SESSION['user'];
$store_id = $user['id'];

// ==========================================
// KODE TAMBAHAN: UPDATE OTOMATIS EXPIRED DI SISI TOKO (VERSI GLOBAL KUAT)
// ==========================================
$today_check = date('Y-m-d');

// 1. Ambil semua telur yang sudah melewati tanggal kedaluwarsa tetapi status utamanya belum EXPIRED
$check_expired = mysqli_query($conn, "SELECT id FROM eggs WHERE expired_date < '$today_check' AND status != 'EXPIRED'");

while ($expired_egg = mysqli_fetch_assoc($check_expired)) {
    $egg_id = $expired_egg['id'];
    
    // 2. Ubah status utama telur di tabel eggs menjadi EXPIRED
    mysqli_query($conn, "UPDATE eggs SET status='EXPIRED' WHERE id = $egg_id");
    
    // 3. Set sisa stok di toko (remaining) menjadi 0 untuk batch telur yang kedaluwarsa tersebut
    mysqli_query($conn, "UPDATE egg_stocks SET remaining = 0 WHERE egg_id = $egg_id");
    
    // 4. Masukkan riwayat kejadian tersebut ke dalam log aktivitas
    mysqli_query($conn, "INSERT INTO egg_logs (egg_id, store_id, status, location, role) VALUES ($egg_id, $store_id, 'EXPIRED', 'Toko', 'toko')");
}
// ==========================================

$total_batch = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(DISTINCT egg_id) as total FROM egg_stocks WHERE store_id = $store_id"));

$total_masuk = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(quantity) as total FROM egg_stocks WHERE store_id = $store_id"));

$total_terjual = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(sold) as total FROM egg_stocks WHERE store_id = $store_id"));

$total_sisa = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(remaining) as total FROM egg_stocks WHERE store_id = $store_id"));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-50">

<div class="max-w-7xl mx-auto p-6">

    <div class="flex justify-between items-center mb-8">

        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                Dashboard Toko
            </h1>
            <p class="text-gray-500 text-sm">
                Kelola distribusi & penjualan telur asin
            </p>
        </div>

        <div class="flex items-center gap-4">

            <div class="text-right">
                <p class="text-sm text-gray-500">Toko</p>
                <p class="font-semibold text-gray-800">
                    <?= $user['username'] ?>
                </p>
            </div>

            <div class="w-10 h-10 bg-green-500 text-white flex items-center justify-center rounded-full font-bold">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>

            <a href="auth/logout.php"
               class="p-2 rounded-lg hover:bg-red-100 text-red-500">
                <i data-lucide="log-out"></i>
            </a>

        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Batch</p>
                    <h2 class="text-3xl font-bold"><?= $total_batch['total'] ?? 0 ?></h2>
                </div>
                <div class="bg-purple-100 p-3 rounded-xl">
                    <i data-lucide="layers" class="text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Masuk</p>
                    <h2 class="text-3xl font-bold"><?= $total_masuk['total'] ?? 0 ?></h2>
                </div>
                <div class="bg-blue-100 p-3 rounded-xl">
                    <i data-lucide="package" class="text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Terjual</p>
                    <h2 class="text-3xl font-bold"><?= $total_terjual['total'] ?? 0 ?></h2>
                </div>
                <div class="bg-green-100 p-3 rounded-xl">
                    <i data-lucide="shopping-cart" class="text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Sisa Stok</p>
                    <h2 class="text-3xl font-bold"><?= $total_sisa['total'] ?? 0 ?></h2>
                </div>
                <div class="bg-yellow-100 p-3 rounded-xl">
                    <i data-lucide="archive" class="text-yellow-600"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <a href="eggs/scan_in.php"
           class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col gap-3">

            <div class="bg-blue-100 w-fit p-3 rounded-xl">
                <i data-lucide="qr-code" class="text-blue-600"></i>
            </div>

            <h3 class="font-semibold text-gray-800">
                Scan Masuk
            </h3>

            <p class="text-sm text-gray-500">
                Terima telur dari peternak
            </p>

        </a>

        <a href="eggs/scan_sell.php"
           class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col gap-3">

            <div class="bg-green-100 w-fit p-3 rounded-xl">
                <i data-lucide="banknote" class="text-green-600"></i>
            </div>

            <h3 class="font-semibold text-gray-800">
                Scan Terjual
            </h3>

            <p class="text-sm text-gray-500">
                Catat penjualan telur
            </p>

        </a>

        <a href="eggs/batch.php"
        class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col gap-3">

            <div class="bg-purple-100 w-fit p-3 rounded-xl">
                <i data-lucide="layers" class="text-purple-600"></i>
            </div>

            <h3 class="font-semibold text-gray-800">
                Lihat Batch
            </h3>

            <p class="text-sm text-gray-500">
                Lihat daftar batch telur di toko
            </p>

        </a>

    </div>

</div>

<script>
lucide.createIcons();
</script>

</body>
</html>