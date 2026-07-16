<?php
include __DIR__ . "/../config/db.php";
session_start();

$barcode = $_GET['barcode'] ?? '';

// 1. Query Data Batch Utama
$query = "SELECT * FROM eggs WHERE barcode='$barcode' LIMIT 1";
$data = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$data) {
    die("<div class='min-h-screen bg-gray-50 flex items-center justify-center font-sans'><div class='text-center p-8 bg-white rounded-2xl shadow-sm border'><p class='text-red-500 font-semibold text-lg'>❌ Data Telur Tidak Ditemukan</p></div></div>");
}

$egg_id = $data['id'];

// ==========================================
// UPDATE OTOMATIS EXPIRED SAAT CUSTOMER SCAN
// ==========================================
$today_check = date('Y-m-d');
if (!empty($data['expired_date']) && $data['expired_date'] < $today_check && $data['status'] != 'EXPIRED') {
    mysqli_query($conn, "UPDATE eggs SET status='EXPIRED' WHERE id = $egg_id");
    mysqli_query($conn, "UPDATE egg_stocks SET remaining = 0 WHERE egg_id = $egg_id");
    mysqli_query($conn, "INSERT INTO egg_logs (egg_id, status, location, role) VALUES ($egg_id, 'EXPIRED', 'Toko / Konsumen', 'system')");
    $data['status'] = 'EXPIRED';
}

// ==========================================
// LOGIKA GAMBAR FORMAT .PNG (DISESUAIKAN)
// ==========================================
$metode_telur = strtolower(trim($data['method'] ?? $data['metode'] ?? 'mentah'));
if (empty($metode_telur)) { $metode_telur = 'mentah'; }

// Default diarahkan ke Mentah1.png
$gambar_produk = "../assets/img/Mentah1.png"; 

if ($metode_telur == 'bata') { 
    $gambar_produk = "../assets/img/bata.png"; 
} elseif ($metode_telur == 'serbuk') { 
    $gambar_produk = "../assets/img/serbuk.png"; 
} elseif ($metode_telur == 'arang') { 
    // Mengarahkan ke file Areng1.png sesuai request terbaru
    $gambar_produk = "../assets/img/Areng1.png"; 
} elseif (strpos($metode_telur, 'garam') !== false) { 
    // Mengarahkan ke file air garam.png milikmu
    $gambar_produk = "../assets/img/air garam.png"; 
} elseif ($metode_telur == 'mentah') { 
    $gambar_produk = "../assets/img/Mentah1.png"; 
}

// ==========================================================
// KONTEN EDUKASI KESEHATAN DAN VITAMIN (TANPA PROTEIN)
// ==========================================================
$edukasi = [
    'title' => 'Kandungan Vitamin & Kesehatan',
    'vitamin' => 'Vitamin A & Zat Besi',
    'desc' => 'Kaya akan kandungan mikronutrisi alami yang baik untuk mendukung imunitas dan metabolisme tubuh.',
    'tips' => 'Konsumsi secara berkala dalam batas wajar demi pemenuhan gizi seimbang.'
];

if ($metode_telur == 'mentah') {
    $edukasi['title'] = 'Manfaat Kesehatan Telur Bebek';
    $edukasi['vitamin'] = 'Fosfor, Vitamin D & Kolin';
    $edukasi['desc'] = 'Telur bebek segar kaya akan kandungan kolin alami yang sangat optimal untuk mendukung kesehatan jaringan saraf otak, meningkatkan konsentrasi, serta mempercepat pemulihan energi sel tubuh yang lelah.';
    $edukasi['tips'] = 'Simpan di tempat yang sejuk atau lemari es agar keutuhan kandungan vitamin di dalam cangkang tetap terjaga.';
} else {
    $edukasi['title'] = 'Manfaat Gizi Telur Asin Bebek';
    $edukasi['vitamin'] = 'Vitamin A, B12 & Selenium';
    $edukasi['desc'] = 'Mengandung Vitamin A konsentrasi tinggi untuk memelihara fungsi retina mata serta menangkal radikal bebas. Senyawa selenium di dalamnya berperan aktif sebagai antioksidan alami bagi tubuh.';
    $edukasi['tips'] = 'Perhatian: Proses pengasinan meningkatkan kadar natrium. Batasi konsumsi maksimal 1 butir per hari bagi penderita hipertensi.';
}

// ==========================================================
// DETEKSI OTOMATIS NAMA KOLOM TABEL USERS (ANTI-ERROR)
// ==========================================================
$kolom_user_nama = 'username'; 
$test_user_fields = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
if ($test_user_fields) {
    $fields = mysqli_fetch_fields($test_user_fields);
    $list_kolom = [];
    foreach ($fields as $val) { $list_kolom[] = $val->name; }
    if (in_array('name', $list_kolom)) { $kolom_user_nama = 'name'; } 
    elseif (in_array('nama', $list_kolom)) { $kolom_user_nama = 'nama'; }
}

// Query mengambil data sebaran toko beserta sisa stok terupdate (remaining)
$distribusi_toko_query = "
    SELECT es.quantity as stok_dikirim, es.remaining as stok_sisa, u.$kolom_user_nama as nama_toko
    FROM egg_stocks es
    JOIN users u ON es.store_id = u.id
    WHERE es.egg_id = '$egg_id'
";
$distribusi_toko_res = mysqli_query($conn, $distribusi_toko_query);

$logs_query = "
    SELECT el.*, u.$kolom_user_nama as nama_toko_log 
    FROM egg_logs el
    LEFT JOIN users u ON el.store_id = u.id
    WHERE el.egg_id = '$egg_id' 
    ORDER BY el.created_at DESC
";
$logs = mysqli_query($conn, $logs_query);

function statusBadge($status) {
    switch ($status) {
        case 'CREATED': return ['text'=>'Tahap Produksi','color'=>'bg-amber-50 text-amber-700 border-amber-200'];
        case 'OUT_FARM': return ['text'=>'Sedang Didistribusikan','color'=>'bg-blue-50 text-blue-700 border-blue-200'];
        case 'IN_STORE': return ['text'=>'Tersedia di Toko','color'=>'bg-indigo-50 text-indigo-700 border-indigo-200'];
        case 'DI_TOKO': return ['text'=>'Tersedia di Toko','color'=>'bg-indigo-50 text-indigo-700 border-indigo-200'];
        case 'SOLD': return ['text'=>'Sudah Terjual','color'=>'bg-emerald-50 text-emerald-700 border-emerald-200'];
        case 'EXPIRED': return ['text'=>'Kedaluwarsa','color'=>'bg-rose-50 text-rose-700 border-rose-200'];
        default: return ['text'=>$status,'color'=>'bg-gray-50 text-gray-600 border-gray-200'];
    }
}
$statusUI = statusBadge($data['status']);

function tanggalIndo($date) {
    if (empty($date) || $date == '0000-00-00') return '-';
    $hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $timestamp = strtotime($date);
    return $hari[date('l',$timestamp)] . ', ' . date('j',$timestamp) . ' ' . $bulan[(int)date('m',$timestamp)] . ' ' . date('Y',$timestamp);
}

// ==========================================================
// PERHITUNGAN DAN STRATEGI BACKUP TANGGAL EXPIRED
// ==========================================================
if (!empty($data['expired_date']) && $data['expired_date'] != '0000-00-00') {
    $tgl_expired_indo = tanggalIndo($data['expired_date']);
} else {
    $tgl_basis = !empty($data['production_date']) ? $data['production_date'] : date('Y-m-d');
    $tgl_produksi_dt = new DateTime($tgl_basis);
    $tgl_produksi_dt->modify('+21 days'); 
    $tgl_expired_indo = tanggalIndo($tgl_produksi_dt->format('Y-m-d'));
}

// Menentukan Desain HTML Notifikasi Expired
if ($data['status'] == 'EXPIRED') {
    $notif_expired_html = "<p class='text-xs text-rose-600 font-semibold mt-2 flex items-center gap-1'><i data-lucide='alert-triangle' class='w-3.5 h-3.5'></i> Expired sejak tanggal: $tgl_expired_indo</p>";
} else {
    $notif_expired_html = "<p class='text-xs text-slate-500 font-medium mt-2 flex items-center gap-1'><i data-lucide='calendar-clock' class='w-3.5 h-3.5 text-slate-400'></i> Baik digunakan sebelum: <span class='text-slate-700 font-semibold'>$tgl_expired_indo</span></p>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pelacakan Telur Asin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen pb-12">

<div class="max-w-4xl mx-auto px-4 pt-6">

    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-6 rounded-3xl shadow-md mb-6 relative overflow-hidden">
        <span class="bg-white/20 text-white text-xs px-3 py-1 rounded-full font-medium uppercase">Sistem Transparansi Kuliner</span>
        <h1 class="text-2xl font-bold mt-2 flex items-center gap-2">Pelacakan Batches Telur Asin</h1>
        <p class="text-xs text-blue-100 mt-1 max-w-md">Ketahui asal-usul, total batch produksi, dan peta sebaran distribusi produk secara transparan langsung dari peternak.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 items-start">
        
        <div class="md:col-span-1 space-y-4 sticky top-6">
            <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Visual Produk</p>
                <div class="w-full h-48 bg-slate-50 rounded-2xl overflow-hidden border border-slate-100 flex items-center justify-center">
                    <img class="w-full h-full object-cover" src="<?= $gambar_produk ?>" alt="Metode Telur Asin">
                </div>
                <div class="mt-3 bg-blue-50 text-blue-700 inline-block px-4 py-1 rounded-full text-xs font-semibold capitalize">
                    Metode <?= ucwords($metode_telur) ?>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-teal-50/60 border border-emerald-200 p-5 rounded-3xl shadow-sm">
                <h2 class="text-xs font-bold text-emerald-800 mb-2 flex items-center gap-1.5 uppercase tracking-wide">
                    <i data-lucide="sparkles" class="text-emerald-600 w-4 h-4"></i>
                    <?= $edukasi['title'] ?>
                </h2>
                <p class="text-[11px] text-emerald-900 leading-relaxed mb-3">
                    <?= $edukasi['desc'] ?>
                </p>
                
                <div class="bg-white/80 p-2.5 rounded-xl border border-emerald-100 flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] text-slate-400 font-semibold uppercase">Vitamin & Mineral Unggulan</p>
                        <p class="text-xs font-bold text-slate-700"><?= $edukasi['vitamin'] ?></p>
                    </div>
                </div>
                
                <p class="text-[9px] text-amber-700 font-medium mt-3 bg-amber-50/70 p-2 rounded-lg border border-amber-100 leading-tight">
                    <i data-lucide="info" class="w-3 h-3 inline-block mr-0.5 text-amber-600"></i> <?= $edukasi['tips'] ?>
                </p>
            </div>
        </div>

        <div class="md:col-span-2 space-y-6">
            
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-5">
                <div>
                    <p class="text-xs text-slate-400 font-medium">Status Batch Utama</p>
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-bold border <?= $statusUI['color'] ?>">
                        <?= $statusUI['text'] ?>
                    </span>
                    <?= $notif_expired_html; ?>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center gap-3">
                        <div class="p-2.5 bg-blue-500 text-white rounded-xl"><i data-lucide="layers" class="w-5 h-5"></i></div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Batch Produksi</p>
                            <p class="text-base font-bold text-slate-700"><?= $data['quantity'] ?? 0 ?> Butir</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center gap-3">
                        <div class="p-2.5 bg-emerald-500 text-white rounded-xl"><i data-lucide="calendar" class="w-5 h-5"></i></div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Tanggal Produksi</p>
                            <p class="text-xs font-bold text-slate-700 mt-0.5"><?= tanggalIndo($data['production_date'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                <h2 class="text-md font-bold text-slate-800 mb-4 flex items-center gap-2 border-b pb-3">
                    <i data-lucide="truck" class="text-indigo-500 w-5 h-5"></i>
                    Informasi Ketersediaan Stok Mitra Toko
                </h2>
                <p class="text-xs text-slate-400 mb-4">Jika stok di toko pembelian Anda habis, Anda dapat memeriksa sisa stok batch ini di toko mitra kami yang lain:</p>
                
                <div class="space-y-3">
                    <?php if ($distribusi_toko_res && mysqli_num_rows($distribusi_toko_res) > 0): ?>
                        <?php while ($toko = mysqli_fetch_assoc($distribusi_toko_res)): 
                            $sisa = (int)$toko['stok_sisa'];
                            $badge_color = $sisa > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200';
                            $status_text = $sisa > 0 ? "Tersedia: $sisa Butir" : "Habis Terjual";
                        ?>
                            <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><i data-lucide="store" class="w-4 h-4"></i></div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700"><?= ucwords($toko['nama_toko'] ?? 'Toko Mitra') ?></p>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 border text-[10px] font-semibold rounded-md <?= $badge_color ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Total Suplai Awal</p>
                                    <p class="text-sm font-bold text-slate-600"><?= $toko['stok_dikirim'] ?> Butir</p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center p-4 bg-slate-50 rounded-2xl text-slate-400 text-xs">
                            <i data-lucide="info" class="w-4 h-4 inline-block mr-1"></i> Telur belum didistribusikan ke toko manapun.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                <h2 class="text-md font-bold text-slate-800 mb-6 flex items-center gap-2 border-b pb-3">
                    <i data-lucide="milestone" class="text-blue-500 w-5 h-5"></i>
                    Riwayat Perjalanan & Logistik Sistem
                </h2>
                <div class="relative pl-6 border-l-2 border-slate-100 space-y-6">
                    <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                        <?php 
                        $toko_terakhir = 'Toko Mitra'; 
                        $log_list = [];
                        while ($l = mysqli_fetch_assoc($logs)) { $log_list[] = $l; }
                        
                        foreach (array_reverse($log_list) as $log_check) {
                            if (!empty($log_check['nama_toko_log'])) { $toko_terakhir = $log_check['nama_toko_log']; }
                        }
                        
                        foreach ($log_list as $log): 
                            $lokasi_tampil = $log['location'];
                            $nama_toko_aktif = !empty($log['nama_toko_log']) ? $log['nama_toko_log'] : $toko_terakhir;
                            
                            if (trim(strtolower($lokasi_tampil)) == 'toko' || empty($lokasi_tampil)) {
                                if ($log['status'] == 'SOLD') { $lokasi_tampil = "Laku terjual di " . $nama_toko_aktif; } 
                                else { $lokasi_tampil = "Masuk ke " . $nama_toko_aktif; }
                            }
                        ?>
                            <div class="relative">
                                <div class="absolute -left-[31px] top-0.5 bg-white border-2 border-blue-500 w-4 h-4 rounded-full flex items-center justify-center">
                                    <div class="bg-blue-500 w-1.5 h-1.5 rounded-full"></div>
                                </div>
                                <div class="bg-slate-50/70 p-3 rounded-xl border border-slate-100">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                                            <?= str_replace('_', ' ', $log['status']) ?>
                                        </p>
                                        <span class="text-[10px] text-slate-400 bg-slate-200/60 px-2 py-0.5 rounded font-medium">
                                            <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i> Alur: <span class="font-medium text-slate-600"><?= ucwords($lokasi_tampil) ?></span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-slate-400 text-xs">Belum ada riwayat logistik.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>