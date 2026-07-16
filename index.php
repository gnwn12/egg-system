<?php
session_start();

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] == 'peternak') {
        header("Location: dashboard_peternak.php");
        exit;
    }
    if ($_SESSION['user']['role'] == 'toko') {
        header("Location: dashboard_toko.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sistem Tracking Telur Asin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-50">

<div class="relative bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 text-white py-24 overflow-hidden">

    <div class="absolute w-96 h-96 bg-white/10 rounded-full blur-3xl top-[-100px] left-[-100px]"></div>
    <div class="absolute w-96 h-96 bg-white/10 rounded-full blur-3xl bottom-[-100px] right-[-100px]"></div>

    <div class="max-w-6xl mx-auto px-6 text-center relative z-10">

        <h1 class="text-4xl md:text-6xl font-bold mb-4 leading-tight">
            Tracking Telur Asin <br>
            <span class="text-blue-200">Lebih Transparan & Modern</span>
        </h1>

        <p class="text-lg text-blue-100 mb-10 max-w-2xl mx-auto">
            Pantau distribusi telur dari peternak hingga ke konsumen secara real-time dengan sistem digital
        </p>

        <div class="flex justify-center gap-4 flex-wrap">

            <a href="auth/login.php"
               class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition shadow">
                Login
            </a>

            <a href="auth/register.php"
               class="bg-white/10 border border-white/30 px-6 py-3 rounded-xl font-semibold hover:bg-white/20 transition">
                Register
            </a>

        </div>

    </div>
</div>

<div class="max-w-3xl mx-auto px-6 -mt-20 relative z-20">

    <div class="bg-white/90 backdrop-blur p-6 rounded-2xl shadow-xl border">

        <h2 class="text-xl font-bold mb-2 flex items-center gap-2 text-gray-800">
            <i data-lucide="qr-code" class="text-blue-500"></i>
            Cek Keaslian & Status Telur
        </h2>

        <p class="text-gray-500 text-sm mb-5">
            Scan atau masukkan barcode untuk melihat detail distribusi telur
        </p>

        <form action="eggs/detail.php" method="GET" class="flex flex-col gap-3">

            <div class="relative">
                <i data-lucide="scan-line"
                   class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>

                <input type="text"
                       name="barcode"
                       placeholder="Scan / masukkan barcode..."
                       class="w-full pl-10 pr-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                       required autofocus>
            </div>

            <div class="flex gap-2">

                <button
                    class="flex-1 bg-blue-500 text-white py-3 rounded-xl hover:bg-blue-600 transition flex items-center justify-center gap-2 shadow">
                    <i data-lucide="search"></i>
                    Cek Sekarang
                </button>

                <a href="scan_camera.php"
                   class="flex items-center justify-center px-4 rounded-xl border hover:bg-gray-100 transition">
                    <i data-lucide="camera"></i>
                </a>

            </div>

            <p class="text-xs text-gray-400 text-center">
                 Gunakan scanner atau kamera untuk hasil lebih cepat
            </p>

        </form>

    </div>

</div>

<div class="max-w-6xl mx-auto px-6 py-20">

    <div class="grid md:grid-cols-3 gap-6 text-center">

        <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <i data-lucide="activity" class="mx-auto text-blue-500 mb-3"></i>
            <h3 class="font-semibold text-lg">Tracking Real-Time</h3>
            <p class="text-gray-500 text-sm">
                Pantau status telur dari produksi hingga terjual
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <i data-lucide="truck" class="mx-auto text-green-500 mb-3"></i>
            <h3 class="font-semibold text-lg">Distribusi Terpantau</h3>
            <p class="text-gray-500 text-sm">
                Lihat proses pengiriman dari peternak ke toko
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <i data-lucide="shield-check" class="mx-auto text-purple-500 mb-3"></i>
            <h3 class="font-semibold text-lg">Transparansi Produk</h3>
            <p class="text-gray-500 text-sm">
                Konsumen bisa cek kualitas & asal telur
            </p>
        </div>

    </div>

</div>

<div class="text-center text-gray-400 text-sm pb-6">
    © <?= date('Y') ?> Sistem Tracking Telur Asin
</div>

<script>
lucide.createIcons();
</script>

</body>
</html>