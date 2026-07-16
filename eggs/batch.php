<?php
include __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'toko') {
    exit("No access");
}

// Konversi store_id menjadi integer agar pencarian data relasi di MySQL bekerja maksimal
$store_id = (int)$_SESSION['user']['id'];

$data = mysqli_query($conn, "
    SELECT 
        eggs.barcode,
        eggs.created_at,
        eggs.production_date,
        eggs.expired_date,
        eggs.status,
        egg_stocks.quantity,
        egg_stocks.sold,
        egg_stocks.remaining
    FROM egg_stocks
    JOIN eggs ON eggs.id = egg_stocks.egg_id
    WHERE egg_stocks.store_id = $store_id
    ORDER BY eggs.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Batch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-50">

<div class="max-w-7xl mx-auto p-6">

    <div class="flex items-center gap-4 mb-6">

         <a href="../dashboard_toko.php"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-white shadow hover:bg-gray-100">
            <i data-lucide="chevron-left"></i>
        </a>

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Data Batch Telur
            </h1>
            <p class="text-gray-500 text-sm">
                Daftar stok telur di toko kamu
            </p>
        </div>

    </div>

    <div class="bg-white rounded-2xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="p-4 text-left">Barcode</th>
                        <th class="p-4 text-left">Tanggal Produksi</th>
                        <th class="p-4 text-left">Tanggal Expired</th>
                        <th class="p-4 text-left">Masuk</th>
                        <th class="p-4 text-left">Terjual</th>
                        <th class="p-4 text-left">Sisa</th>
                        <th class="p-4 text-left">Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($data) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($data)): ?>

                        <tr class="border-t hover:bg-gray-50">

                            <td class="p-4 font-mono">
                                <?= $row['barcode'] ?>
                            </td>

                            <td class="p-4">
                                <?= date('d M Y', strtotime($row['production_date'])) ?>
                            </td>

                            <td class="p-4">
                                <?= date('d M Y', strtotime($row['expired_date'])) ?>
                            </td>

                            <td class="p-4">
                                <?= $row['quantity'] ?>
                            </td>

                            <td class="p-4 text-green-600 font-semibold">
                                <?= $row['sold'] ?>
                            </td>

                            <td class="p-4 text-blue-600 font-semibold">
                                <?= $row['remaining'] ?>
                            </td>

                            <td class="p-4">
                                <?php 
                                $today = date('Y-m-d');
                                
                                // Kondisi 1: Jika berstatus EXPIRED atau tanggalnya sudah melewati hari ini
                                if ($row['status'] == 'EXPIRED' || $row['expired_date'] < $today): 
                                ?>
                                    <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-semibold">
                                        Expired
                                    </span>
                                
                                <?php 
                                // Kondisi 2: Jika sisa stoknya 0 (dan tidak expired), berarti murni habis terjual
                                elseif ($row['remaining'] == 0): 
                                ?>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                        Habis
                                    </span>
                                
                                <?php 
                                // Kondisi 3: Jika stok masih ada dan belum expired
                                else: 
                                ?>
                                    <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs">
                                        Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="7" class="text-center p-6 text-gray-500">
                            Belum ada data batch
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
lucide.createIcons();
</script>

</body>
</html>