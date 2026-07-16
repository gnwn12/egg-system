<?php
include "config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'peternak') {
    header("Location: auth/login.php");
    exit;
}

$user = $_SESSION['user'];
$hari_ini = date('Y-m-d');

// ==========================================================
// 1. QUERY KOTAK STATISTIK (DENGAN HITUNGAN OTOMATIS)
// ==========================================================
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM eggs"));

// Hitung Sedang Diasinkan
$q_created = "SELECT COUNT(*) as total FROM eggs WHERE status IN ('CREATED', 'created', 'Sedang Diasinkan') AND (tanggal_selesai_asin > '$hari_ini' OR tanggal_selesai_asin IS NULL OR tanggal_selesai_asin = '0000-00-00')";
$created = mysqli_fetch_assoc(mysqli_query($conn, $q_created));

// Hitung Siap Distribusi
$q_ready = "SELECT COUNT(*) as total FROM eggs WHERE status IN ('READY', 'ready', 'Siap Distribusi') OR (status IN ('CREATED', 'created', 'Sedang Diasinkan') AND tanggal_selesai_asin <= '$hari_ini' AND tanggal_selesai_asin IS NOT NULL AND tanggal_selesai_asin != '0000-00-00')";
$ready = mysqli_fetch_assoc(mysqli_query($conn, $q_ready));

$expired = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM eggs WHERE status IN ('EXPIRED', 'expired')"));

// ==========================================================
// 2. QUERY TABEL UTAMA
// ==========================================================
$query = "SELECT * FROM eggs WHERE status NOT IN ('SOLD', 'EXPIRED', 'sold', 'expired') ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// ==========================================================
// 3. HELPER BADGE STATUS
// ==========================================================
function statusBadgePeternak($status_awal, $tanggal_selesai, $hari_ini) {
    $status_clean = strtoupper(trim($status_awal ?? ''));
    
    if (($status_clean == 'CREATED' || $status_clean == 'SEDANG DIASINKAN') && (!empty($tanggal_selesai) && $tanggal_selesai != '0000-00-00' && $hari_ini >= $tanggal_selesai)) {
        return ['text' => 'Siap Distribusi', 'color' => 'bg-green-50 text-green-700 border-green-200'];
    }

    switch ($status_clean) {
        case 'CREATED':
        case 'SEDANG DIASINKAN': 
            return ['text' => 'Sedang Diasinkan', 'color' => 'bg-amber-50 text-amber-700 border-amber-200'];
        
        case 'READY':
        case 'SIAP DISTRIBUSI': 
            return ['text' => 'Siap Distribusi', 'color' => 'bg-green-50 text-green-700 border-green-200'];
        
        case 'IN_STORE':
        case 'DI TOKO':
            return ['text' => 'Di Toko', 'color' => 'bg-indigo-50 text-indigo-700 border-indigo-200'];
        
        case 'SOLD':
        case 'TERJUAL': 
            return ['text' => 'Terjual', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
        
        case 'EXPIRED':
        case 'KEDALUWARSA': 
            return ['text' => 'Expired', 'color' => 'bg-rose-50 text-rose-700 border-rose-200'];
        
        default: 
            return ['text' => !empty($status_awal) ? $status_awal : 'Sedang Diasinkan', 'color' => 'bg-amber-50 text-amber-700 border-amber-200'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Peternak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>

<body class="bg-gray-50">

<div class="max-w-7xl mx-auto p-6">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dashboard Peternak</h1>
            <p class="text-gray-500 text-sm">Monitoring produksi & distribusi telur asin</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="text-sm text-gray-500">Peternak</p>
                <p class="font-semibold text-gray-800"><?= $user['username'] ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-500 text-white flex items-center justify-center rounded-full font-bold">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <a href="auth/logout.php" class="p-2 rounded-lg hover:bg-red-100 text-red-500">
                <i data-lucide="log-out"></i>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Produksi</p>
                    <h2 class="text-3xl font-bold mt-1"><?= $total['total'] ?></h2>
                </div>
                <div class="bg-blue-100 p-3 rounded-xl">
                    <i data-lucide="layers" class="text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Sedang Diasinkan</p>
                    <h2 class="text-3xl font-bold mt-1"><?= $created['total'] ?></h2>
                </div>
                <div class="bg-amber-100 p-3 rounded-xl">
                    <i data-lucide="sun" class="text-amber-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Siap Distribusi</p>
                    <h2 class="text-3xl font-bold mt-1"><?= $ready['total'] ?></h2>
                </div>
                <div class="bg-green-100 p-3 rounded-xl">
                    <i data-lucide="truck" class="text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Expired</p>
                    <h2 class="text-3xl font-bold mt-1"><?= $expired['total'] ?></h2>
                </div>
                <div class="bg-rose-100 p-3 rounded-xl">
                    <i data-lucide="alert-circle" class="text-rose-600"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="eggs/create.php" class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col gap-3">
            <div class="bg-blue-100 w-fit p-3 rounded-xl">
                <i data-lucide="plus-circle" class="text-blue-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Input Produksi</h3>
            <p class="text-sm text-gray-500">Tambahkan produksi telur baru</p>
        </a>

        <a href="eggs/list.php" class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col gap-3">
            <div class="bg-indigo-100 w-fit p-3 rounded-xl">
                <i data-lucide="package" class="text-indigo-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Data Produksi</h3>
            <p class="text-sm text-gray-500">Pantau seluruh data produksi</p>
        </a>

        <a href="eggs/scan_out.php" class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col gap-3">
            <div class="bg-green-100 w-fit p-3 rounded-xl">
                <i data-lucide="scan-line" class="text-green-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Distribusi ke Toko</h3>
            <p class="text-sm text-gray-500">Kirim batch telur ke toko</p>
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                <i data-lucide="store" class="text-blue-500 w-4 h-4"></i>
                Monitoring Real-Time Kelayakan & Pemisahan Sisa Stok Telur di Mitra Toko
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 text-[11px] font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="py-4 px-6">Nama Toko / Retail</th>
                        <th class="py-4 px-6 text-center text-blue-600">Sisa Mentah</th>
                        <th class="py-4 px-6 text-center text-indigo-600">Sisa Asin</th>
                        <th class="py-4 px-6 text-center text-amber-600">Mendekati Expired (&lt; 3 Hari)</th>
                        <th class="py-4 px-6 text-center text-rose-600">Sudah Expired / Busuk</th>
                        <th class="py-4 px-6 text-center">Rekomendasi Rantai Pasok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php
                    $kolom_nama = 'username';
                    $cek_kolom = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
                    if ($cek_kolom) {
                        $fields = mysqli_fetch_fields($cek_kolom);
                        $list = [];
                        foreach ($fields as $f) { $list[] = $f->name; }
                        if (in_array('name', $list)) { $kolom_nama = 'name'; } 
                        elseif (in_array('nama', $list)) { $kolom_nama = 'nama'; }
                    }

                    $q_monitor = "SELECT 
                                    u.$kolom_nama as nama_toko,
                                    SUM(CASE WHEN LOWER(e.type) = 'mentah' OR LOWER(e.method) = 'mentah' OR e.method IS NULL OR e.method = '' OR e.method = '-' THEN es.remaining ELSE 0 END) as stok_mentah,
                                    SUM(CASE WHEN LOWER(e.type) != 'mentah' AND LOWER(e.method) != 'mentah' AND e.method IS NOT NULL AND e.method != '' AND e.method != '-' THEN es.remaining ELSE 0 END) as stok_asin,
                                    SUM(es.remaining) as total_stok,
                                    SUM(CASE WHEN e.expired_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND e.expired_date >= CURDATE() THEN es.remaining ELSE 0 END) as hampir_expired,
                                    SUM(CASE WHEN e.expired_date < CURDATE() THEN es.remaining ELSE 0 END) as sudah_expired
                                  FROM egg_stocks es
                                  JOIN users u ON es.store_id = u.id
                                  JOIN eggs e ON es.egg_id = e.id
                                  GROUP BY es.store_id";
                    
                    $r_monitor = mysqli_query($conn, $q_monitor);

                    if ($r_monitor && mysqli_num_rows($r_monitor) > 0):
                        while ($m = mysqli_fetch_assoc($r_monitor)):
                    ?>
                        <tr class="hover:bg-gray-50/80 transition duration-150">
                            <td class="py-4 px-6 font-bold text-gray-700 flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4 text-gray-400"></i>
                                <?= ucwords($m['nama_toko']) ?>
                            </td>
                            <td class="py-4 px-6 text-center font-semibold text-blue-600 bg-blue-50/20">
                                <?= number_format($m['stok_mentah']) ?> butir
                            </td>
                            <td class="py-4 px-6 text-center font-semibold text-indigo-600 bg-indigo-50/20">
                                <?= number_format($m['stok_asin']) ?> butir
                            </td>
                            <td class="py-4 px-6 text-center font-bold text-amber-600 bg-amber-50/30">
                                <?= number_format($m['hampir_expired']) ?> butir
                            </td>
                            <td class="py-4 px-6 text-center font-bold text-rose-600 bg-rose-50/30">
                                <?= number_format($m['sudah_expired']) ?> butir
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php if ($m['sudah_expired'] > 0 || $m['total_stok'] > 100): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i> Tunda Kirim & Tarik Retur
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Aman Dikirim
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400 italic">
                                Belum ada data distribusi aktif di toko retail.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                <i data-lucide="clipboard-list" class="text-gray-500 w-4 h-4"></i>
                Monitoring Production Eggs (Active)
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 text-[11px] font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="py-4 px-6">Batch Peternak</th>
                        <th class="py-4 px-6">Jenis</th>
                        <th class="py-4 px-6">Metode</th>
                        <th class="py-4 px-6">Produksi</th>
                        <th class="py-4 px-6">Selesai Asin</th>
                        <th class="py-4 px-6">Expired</th>
                        <th class="py-4 px-6 text-center">Status</th>
                        <th class="py-4 px-6 text-center">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            
                            $raw_type = strtolower(trim($row['type'] ?? ''));
                            $raw_method = strtolower(trim($row['method'] ?? ''));
                            
                            if ($raw_method == 'mentah' || $raw_type == 'mentah' || empty($raw_method) || $raw_method == '-') {
                                $jenis_tampil = 'Mentah';
                                $metode_tampil = '-';
                                $selesai_asin = '-'; 
                            } else {
                                $jenis_tampil = 'Telur Bebek Asin';
                                
                                if (strpos($raw_method, 'garam') !== false) {
                                    $metode_tampil = 'Rendam Air Garam';
                                    $hari_asin = 7;
                                } else if (strpos($raw_method, 'areng') !== false || strpos($raw_method, 'arang') !== false) {
                                    $metode_tampil = 'Balutan Abu/Arang';
                                    $hari_asin = 10;
                                } else {
                                    $metode_tampil = ucwords($row['method']);
                                    $hari_asin = 10;
                                }

                                if (!empty($row['tanggal_selesai_asin']) && $row['tanggal_selesai_asin'] != '0000-00-00') {
                                    $selesai_asin = $row['tanggal_selesai_asin'];
                                } else {
                                    $selesai_asin = date('Y-m-d', strtotime($row['production_date'] . ' + ' . $hari_asin . ' days'));
                                }
                            }

                            $statusUI = statusBadgePeternak($row['status'], $selesai_asin, $hari_ini);
                        ?>
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="py-4 px-6 font-mono font-bold text-blue-600"><?= $row['barcode'] ?></td>
                                <td class="py-4 px-6 text-gray-600"><?= $jenis_tampil ?></td>
                                <td class="py-4 px-6 font-medium text-gray-700"><?= $metode_tampil ?></td>
                                <td class="py-4 px-6 text-gray-500"><?= $row['production_date'] ?></td>
                                <td class="py-4 px-6 text-gray-500 font-medium"><?= $selesai_asin ?></td>
                                <td class="py-4 px-6 text-rose-600 font-medium"><?= $row['expired_date'] ?></td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-block px-2.5 py-0.5 border text-[10px] font-bold rounded-md <?= $statusUI['color'] ?>">
                                        <?= $statusUI['text'] ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-gray-700"><?= number_format($row['quantity']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-400 italic">Tidak ada data batch produksi aktif di peternakan saat ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

</body>
</html>