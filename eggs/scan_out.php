<?php
include "../config/db.php";
session_start();

// Validasi Login Peternak
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'peternak') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

// Eksekusi ketika barcode dimasukkan (Manual atau via Kamera Scan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['barcode'])) {
    $barcode = mysqli_real_escape_string($conn, trim($_POST['barcode']));
    
    // Default store_id diset ke 1 atau disesuaikan dengan ID mitra retail di database kamu
    $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 1; 

    if (empty($barcode)) {
        $error = "Barcode tidak boleh kosong!";
    } else {
        // 1. Cek apakah batch telur dengan barcode tersebut ada
        $cek_egg = mysqli_query($conn, "SELECT * FROM eggs WHERE barcode = '$barcode' LIMIT 1");
        
        // Proteksi awal jika query ke tabel eggs bermasalah
        if (!$cek_egg) {
            $error = "Gagal memproses query data telur: " . mysqli_error($conn);
        } elseif (mysqli_num_rows($cek_egg) > 0) {
            $egg = mysqli_fetch_assoc($cek_egg);
            $egg_id = $egg['id'];
            
            // 2. Cek status kelayakan produk sebelum keluar peternakan
            if ($egg['status'] == 'EXPIRED') {
                $error = "Gagal! Batch telur ini sudah Expired, tidak boleh didistribusikan.";
            } elseif ($egg['status'] == 'OUT_FARM' || $egg['status'] == 'IN_STORE' || $egg['status'] == 'DI_TOKO') {
                $error = "Batch telur ini sudah didistribusikan sebelumnya.";
            } elseif ($egg['status'] == 'SOLD') {
                $error = "Batch telur ini sudah terjual habis.";
            } else {
                // 3. Update status telur menjadi OUT_FARM (Keluar Peternakan)
                $update = mysqli_query($conn, "UPDATE eggs SET status = 'OUT_FARM' WHERE id = $egg_id");
                
                if ($update) {
                    // 4. Catat riwayat log keluar ke tabel egg_logs (DISEBARKAN KE EGG_ID SESUAI STRUKTUR DB)
                    // Ditambahkan role 'peternak' agar sesuai dengan struktur database sistem log kamu
                    $log_query = "INSERT INTO egg_logs (egg_id, store_id, status, location, role, created_at) 
                                  VALUES ($egg_id, '$store_id', 'OUT_FARM', 'Keluar Peternakan', 'peternak', NOW())";
                    mysqli_query($conn, $log_query);
                    
                    // 5. Masukkan atau update sisa stok aktif toko di tabel egg_stocks
                    $qty = $egg['quantity'];
                    $cek_stok = mysqli_query($conn, "SELECT * FROM egg_stocks WHERE store_id = '$store_id' AND egg_id = $egg_id");
                    
                    if ($cek_stok && mysqli_num_rows($cek_stok) > 0) {
                        mysqli_query($conn, "UPDATE egg_stocks SET remaining = remaining + $qty WHERE store_id = '$store_id' AND egg_id = $egg_id");
                    } else {
                        mysqli_query($conn, "INSERT INTO egg_stocks (store_id, egg_id, remaining) VALUES ('$store_id', $egg_id, '$qty')");
                    }

                    $success = "Sukses! Batch berkode $barcode berhasil discan keluar menuju rantai retail.";
                } else {
                    $error = "Gagal memperbarui status data di database: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Barcode tidak terdaftar dalam sistem produksi peternakan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Keluar Peternakan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center p-6">

<div class="w-full max-w-2xl space-y-6">

    <?php if (!empty($error)): ?>
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-2xl flex items-center gap-2 shadow-sm">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-red-500"></i>
            <span><?= $error ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-2xl flex items-center gap-2 shadow-sm">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 text-green-500"></i>
            <span><?= $success ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-5">
        <a href="../dashboard_peternak.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition text-gray-600">
            <i data-lucide="chevron-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Scan Keluar Peternakan</h1>
            <p class="text-sm text-gray-500 mt-0.5">Proses distribusi telur ke luar peternakan</p>
        </div>
    </div>

    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-6">
        
        <button type="button" class="w-fit flex items-center gap-2 px-4 py-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition">
            <i data-lucide="camera" class="w-4 h-4 text-gray-500"></i>
            <span>Scan Kamera (Auto)</span>
        </button>

        <form action="" method="POST" class="space-y-5">
            
            <input type="hidden" name="store_id" value="1">

            <div>
                <label for="barcode" class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-2">Barcode Manual</label>
                <input type="text" id="barcode" name="barcode" autofocus placeholder="Input manual kalau tidak scan" 
                       class="w-full px-4 py-3.5 bg-gray-50/50 border border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:bg-white transition text-sm">
            </div>

            <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl transition shadow-sm flex items-center justify-center gap-2 text-sm">
                <i data-lucide="scan" class="w-4 h-4"></i>
                <span>Scan Manual</span>
            </button>
        </form>

    </div>

</div>

<script>
    // Inisialisasi Ikon Lucide
    lucide.createIcons();
    
    // Auto-focus kembali ke input setelah halaman memuat ulang
    const inputBarcode = document.getElementById('barcode');
    if (inputBarcode) {
        inputBarcode.focus();
    }
</script>
</body>
</html>