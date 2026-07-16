<?php
include __DIR__ . "/../config/db.php";

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// ==========================================================
// QUERY UTUH: MEMBACA DISTRIBUSI BERDASARKAN KOLOM LOCATION
// ==========================================================
$query = "SELECT 
            location,
            SUM(CASE WHEN status = 'IN_STORE' THEN quantity ELSE 0 END) as total_stok,
            SUM(CASE WHEN expired_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND expired_date >= CURDATE() THEN quantity ELSE 0 END) as hampir_expired,
            SUM(CASE WHEN expired_date < CURDATE() THEN quantity ELSE 0 END) as sudah_expired
          FROM eggs 
          WHERE location IS NOT NULL AND location != ''
          GROUP BY location";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Stok & Expired Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<div class="max-w-7xl mx-auto p-6">

    <div class="bg-white p-6 rounded-2xl shadow mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="list.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                <i data-lucide="chevron-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i data-lucide="store" class="text-blue-500"></i>
                    Monitoring Stok & Kedaluwarsa di Toko
                </h1>
                <p class="text-sm text-gray-500">
                    Pantau sisa stok dan kondisi kelayakan telur di setiap mitra retail/toko secara real-time.
                </p>
            </div>
        </div>
    </div>

    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex gap-3 items-start">
        <i data-lucide="alert-triangle" class="text-amber-600 w-5 h-5 mt-0.5 flex-shrink-0"></i>
        <div>
            <h4 class="text-sm font-bold text-amber-800">Sistem Peringatan Distribusi (Anti-Overstock)</h4>
            <p class="text-xs text-amber-700 mt-0.5">
                Gunakan data di bawah untuk menolak atau menunda pengiriman jika sisa stok di toko masih banyak, atau jika terdapat telur yang sudah kedaluwarsa yang harus ditarik terlebih dahulu.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="bg-white rounded-2xl shadow p-5 border border-gray-100 hover:shadow-md transition">
                    
                    <div class="flex items-center justify-between border-b pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                            </div>
                            <span class="font-bold text-gray-800 text-base"><?= ucwords($row['location']) ?></span>
                        </div>
                        <span class="text-xs font-medium px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">Mitra Retail</span>
                    </div>

                    <div class="space-y-3">
                        
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i data-lucide="package" class="w-4 h-4 text-slate-500"></i>
                                <span>Sisa Stok di Toko</span>
                            </div>
                            <span class="font-bold text-slate-900 text-sm"><?= number_format($row['total_stok']) ?> butir</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-amber-50">
                            <div class="flex items-center gap-2 text-sm text-amber-700">
                                <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
                                <span>Mendekati Expired</span>
                            </div>
                            <span class="font-bold text-amber-700 text-sm"><?= number_format($row['hampir_expired']) ?> butir</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50">
                            <div class="flex items-center gap-2 text-sm text-rose-700">
                                <i data-lucide="x-circle" class="w-4 h-4 text-rose-500"></i>
                                <span>Sudah Expired / Busuk</span>
                            </div>
                            <span class="font-bold text-rose-700 text-sm"><?= number_format($row['sudah_expired']) ?> butir</span>
                        </div>

                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex gap-2">
                        <?php if ($row['sudah_expired'] > 0 || $row['total_stok'] > 50): ?>
                            <div class="w-full text-center text-[11px] font-semibold py-2 bg-red-50 text-red-600 rounded-xl border border-red-200">
                                <i data-lucide="octagon-alert" class="inline w-3.5 h-3.5 mr-1 align-middle"></i> 
                                Rekomendasi: Tunda Kirim & Tarik Retur
                            </div>
                        <?php else: ?>
                            <div class="w-full text-center text-[11px] font-semibold py-2 bg-green-50 text-green-600 rounded-xl border border-green-200">
                                <i data-lucide="check-check" class="inline w-3.5 h-3.5 mr-1 align-middle"></i> 
                                Aman untuk Pengiriman Baru
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full bg-white p-12 text-center rounded-2xl border text-gray-400 italic">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                Belum ada data distribusi telur di toko retail. Pastikan kolom 'location' di database sudah terisi nama toko.
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>