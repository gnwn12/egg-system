<?php
include __DIR__ . "/../config/db.php";

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$eggs = mysqli_query($conn, "SELECT * FROM eggs ORDER BY created_at DESC");

// ==========================================================
// FUNGSI DETEKSI GAMBAR BERBASIS METODE PENGASINAN
// ==========================================================
function methodImage($method, $type = null) {
    $method = strtolower(trim($method ?? ''));
    $type   = strtolower(trim($type ?? ''));

    // Jika mengandung kata garam, arahkan ke nama file yang ada di folder kamu
    if (strpos($method, 'garam') !== false) {
        return '../assets/img/air garam.png';
    }

    if ($method == 'bata') {
        return '../assets/img/bata.png';
    }

    if ($method == 'arang') {
        // Mengarahkan ke file Areng1.png sesuai request terbaru
        return '../assets/img/Areng1.png';
    }

    if ($method == 'serbuk') {
        return '../assets/img/serbuk.png';
    }

    return '../assets/img/default.png';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Batch Telur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-7xl mx-auto p-6">

    <div class="bg-white p-6 rounded-2xl shadow mb-6 flex items-center justify-between">

        <div class="flex items-center gap-4">

            <a href="../dashboard_peternak.php"
               class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                <i data-lucide="chevron-left"></i>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    Daftar Batch Telur
                </h1>
                <p class="text-sm text-gray-500">
                    Monitoring produksi & distribusi telur asin
                </p>
            </div>

        </div>

        <a href="create.php"
           class="flex items-center gap-2 bg-blue-500 text-white px-5 py-2.5 rounded-xl hover:bg-blue-600 transition shadow">
            <i data-lucide="plus"></i>
            Batch Baru
        </a>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <?php while ($e = mysqli_fetch_assoc($eggs)) { 
            $raw_type = strtolower(trim($e['type'] ?? ''));
            $raw_method = strtolower(trim($e['method'] ?? ''));

            // LOGIKA FILTER KETAT UNTUK TIPE & METODE DI HALAMAN TAMPILAN
            if (strpos($raw_type, 'mentah') !== false || empty($raw_method) || $raw_method == 'mentah') {
                $tipe_tampil = 'Mentah';
            } else {
                $tipe_tampil = 'Asin';
                
                if (strpos($raw_method, 'garam') !== false) {
                    $metode_tampil = 'Rendam Air Garam';
                } else {
                    $metode_tampil = ucwords($e['method']);
                }
            }
        ?>

        <div class="bg-white p-5 rounded-2xl shadow hover:shadow-lg transition border border-gray-100">
            
            <div class="mb-4 flex justify-center">
                <img
                    src="<?= ($tipe_tampil == 'Mentah') ? '../assets/img/Mentah1.png' : methodImage($e['method'], $e['type']) ?>"
                    alt="<?= $e['method'] ?>"
                    class="w-24 h-24 object-cover rounded-xl border bg-white p-2"
                >
            </div>

            <div class="flex justify-between items-center mb-4">

                <div class="text-sm text-gray-500 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <?= $e['production_date'] ?>
                </div>

                <span class="px-3 py-1 text-xs font-semibold rounded-full
                    <?php
                        if ($e['status'] == 'CREATED') echo 'bg-yellow-100 text-yellow-600';
                        elseif ($e['status'] == 'IN_STORE') echo 'bg-blue-100 text-blue-600';
                        elseif ($e['status'] == 'SOLD') echo 'bg-green-100 text-green-600';
                        elseif ($e['status'] == 'EXPIRED') echo 'bg-red-100 text-red-600';
                        else echo 'bg-gray-100 text-gray-600';
                     ?>">
                    <?= $e['status'] ?>
                </span>

            </div>

            <div class="space-y-2 text-sm text-gray-700 mb-4">

                <div class="flex items-center gap-2">
                    <i data-lucide="egg" class="w-4 h-4 text-gray-500"></i>
                    <span>Tipe: <b><?= $tipe_tampil ?></b></span>
                </div>                   
               
                <?php if ($tipe_tampil !== 'Mentah'): ?>
                <div class="flex items-center gap-2">
                    <i data-lucide="settings" class="w-4 h-4 text-gray-500"></i>
                    <span>Metode: <b><?= $metode_tampil ?></b></span>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-gray-500"></i>
                    <span>Jumlah: <b><?= $e['quantity'] ?></b> butir</span>
                </div>

                <div class="flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-gray-500"></i>
                    <span><?= $e['production_date'] ?> → <?= $e['expired_date'] ?></span>
                </div>

            </div>

            <div class="bg-gray-50 p-4 rounded-xl flex items-center gap-4">

                <div class="flex flex-col items-center gap-2">

                    <img
                        class="border p-2 rounded-lg bg-white"
                        width="90"
                        src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= $e['barcode'] ?>"
                    >

                    <a 
                        href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= $e['barcode'] ?>"
                        download="barcode_<?= $e['barcode'] ?>.png"
                        class="text-xs bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600 flex items-center gap-1"
                    >
                        <i data-lucide="download" class="w-3 h-3"></i>
                        Download
                    </a>

                </div>

                <div class="flex-1">

                    <p class="text-xs text-gray-400 break-all mb-2">
                        <?= $e['barcode'] ?>
                    </p>

                    <a href="detail_peternak.php?barcode=<?= $e['barcode'] ?>"
                       class="inline-flex items-center gap-1 text-blue-600 text-sm hover:underline">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        Lihat Detail
                    </a>

                </div>

            </div>

        </div>

        <?php } ?>

    </div>

</div>

<script>
lucide.createIcons();
</script>

</body>
</html>