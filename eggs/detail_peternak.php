<?php
include __DIR__ . "/../config/db.php";
session_start();

// Proteksi jika bukan peternak yang buka (opsional, sesuaikan session role kamu)
$barcode = $_GET['barcode'] ?? '';

// Query Data Batch Utama
$query = "SELECT * FROM eggs WHERE barcode='$barcode' LIMIT 1";
$data = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$data) {
    die("<div class='min-h-screen bg-gray-50 flex items-center justify-center font-sans'><div class='text-center p-8 bg-white rounded-2xl shadow-sm border'><p class='text-red-500 font-semibold text-lg'>❌ Data Batch Tidak Ditemukan</p></div></div>");
}

function tanggalIndo($date) {
    $hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $timestamp = strtotime($date);
    return $hari[date('l',$timestamp)] . ', ' . date('j',$timestamp) . ' ' . $bulan[(int)date('m',$timestamp)] . ' ' . date('Y',$timestamp);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Batch Produksi - Peternak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen p-6">

<div class="max-w-md mx-auto">
    
    <!-- Tombol Kembali ke Dashboard -->
    <a href="list.php" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 mb-4 transition">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Dashboard
    </a>

    <!-- Card Utama Fokus QR Code -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 text-center space-y-6">
        <div>
            <span class="bg-blue-50 text-blue-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Otoritas Peternak</span>
            <h1 class="text-lg font-bold text-slate-800 mt-2">QR Code Batch Produksi</h1>
            <p class="text-xs text-slate-400">Gunakan QR Code ini untuk pelabelan pada kemasan atau mika telur.</p>
        </div>

        <!-- QR Code Utama -->
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 inline-block shadow-inner">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $data['barcode'] ?>" alt="QR Code Batch" class="w-48 h-48 rounded-lg mx-auto">
        </div>

        <!-- Kode Barcode Mentah -->
        <div class="bg-slate-50 p-3 rounded-xl border font-mono text-xs text-slate-600 break-all select-all">
            <?= $data['barcode'] ?>
        </div>

        <!-- Rincian Informasi Produksi Ringkas -->
        <div class="border-t pt-4 text-left grid grid-cols-2 gap-4 text-xs">
            <div>
                <p class="text-slate-400 font-medium">Metode Pengolahan</p>
                <p class="font-bold text-slate-700 capitalize"><?= $data['method'] ?? $data['metode'] ?? 'Mentah' ?></p>
            </div>
            <div>
                <p class="text-slate-400 font-medium">Volume Batch</p>
                <p class="font-bold text-slate-700"><?= $data['quantity'] ?> Butir</p>
            </div>
            <div class="col-span-2">
                <p class="text-slate-400 font-medium">Tanggal Masuk Produksi</p>
                <p class="font-bold text-slate-700"><?= tanggalIndo($data['production_date']) ?></p>
            </div>
        </div>

        <!-- Tombol Cetak Langsung -->
        <button onclick="window.print()" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs py-3 rounded-xl transition flex items-center justify-center gap-2 shadow-sm no-print">
            <i data-lucide="printer" class="w-4 h-4"></i> Cetak Label QR
        </button>
    </div>
</div>

<!-- CSS Tambahan khusus mode cetak kertas agar tombol kembali & print tidak ikut tercetak -->
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: white; padding: 0; }
        .bg-white { border: none; shadow: none; }
    </div>
</style>

<script>lucide.createIcons();</script>
</body>
</html>