<?php
include __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['user'])) {
    exit("Akses ditolak");
}

$message = "";

if (isset($_POST['submit'])) {

    $barcode = mysqli_real_escape_string($conn, $_POST['barcode']);
    $qty = isset($_POST['qty']) && (int)$_POST['qty'] > 0 ? (int)$_POST['qty'] : 0;
    $store_id = (int)$_SESSION['user']['id'];

    if ($qty <= 0) {
        $message = "❌ Jumlah masuk tidak valid";
    } else {
        // Cari data telur berdasarkan barcode
        $result = mysqli_query($conn, "SELECT * FROM eggs WHERE barcode='$barcode'");
        $egg = mysqli_fetch_assoc($result);

        if ($egg) {
            $egg_id = $egg['id'];
            $sisa_di_peternak = (int)$egg['remaining']; // Mengambil sisa telur yang tersedia di peternak

            // ==========================================
            // KODE VALIDASI TAMBAHAN: CEK SISA STOK PETERNAK
            // ==========================================
            if ($sisa_di_peternak < $qty) {
                $message = "❌ Gagal! Sisa telur di peternak tidak mencukupi (Sisa tersedia: $sisa_di_peternak butir)";
            } else {
                
                // =========================
                // Generate Batch Toko
                // =========================
                $batch_toko = "TKO-" . date('Ymd') . "-" . rand(100,999);

                mysqli_begin_transaction($conn);

                try {
                    // 1. Update Status Utama & Kurangi Kolom 'remaining' di Tabel Eggs Peternak
                    mysqli_query($conn, "
                        UPDATE eggs
                        SET
                            batch_toko='$batch_toko',
                            status_produk='Masuk Toko',
                            status='IN_STORE',
                            remaining = remaining - $qty
                        WHERE id='$egg_id'
                    ");

                    // 2. Insert / Update ke Tabel egg_stocks (Stok Toko)
                    $check_stock = mysqli_query($conn, "SELECT id FROM egg_stocks WHERE egg_id='$egg_id' AND store_id='$store_id' LIMIT 1");
                    $stock_exists = mysqli_fetch_assoc($check_stock);

                    if ($stock_exists) {
                        mysqli_query($conn, "
                            UPDATE egg_stocks 
                            SET quantity = quantity + $qty, 
                                remaining = remaining + $qty 
                            WHERE id = {$stock_exists['id']}
                        ");
                    } else {
                        mysqli_query($conn, "
                            INSERT INTO egg_stocks (egg_id, store_id, quantity, sold, remaining, status) 
                            VALUES ('$egg_id', '$store_id', '$qty', 0, '$qty', 'IN_STORE')
                        ");
                    }

                    // 3. Catat Riwayat ke egg_logs
                    mysqli_query($conn, "
                        INSERT INTO egg_logs (egg_id, store_id, status, location, role) 
                        VALUES ('$egg_id', '$store_id', 'IN_STORE', 'Toko', 'toko')
                    ");

                    mysqli_commit($conn);

                    $message = "Berhasil masuk $qty telur ke toko";

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = "❌ Terjadi kesalahan sistem saat menyimpan data";
                }
            }

        } else {
            $message = "Barcode tidak ditemukan";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scan Barang Masuk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-xl mx-auto p-6">

    <div class="bg-white p-5 rounded-2xl shadow mb-6 flex items-center gap-4">
        <a href="../dashboard_toko.php"
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200">
            <i data-lucide="chevron-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="qr-code" class="text-blue-500"></i>
                Scan Masuk Toko
            </h1>
            <p class="text-sm text-gray-500">
                Terima distribusi telur dari peternak
            </p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">

        <?php if (!empty($message)): ?>
            
            <?php if (str_contains($message, 'Berhasil')): ?>
                <div class="mb-4 p-4 rounded-xl bg-green-100 text-green-700 flex items-center gap-2">
                    <i data-lucide="check-circle"></i>
                    <?= $message ?>
                </div>
            <?php else: ?>
                <div class="mb-4 p-4 rounded-xl bg-red-100 text-red-700 flex items-center gap-2">
                    <i data-lucide="alert-circle"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <button type="button"
                onclick="startScanner()"
                class="mb-4 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg flex items-center gap-2">
            <i data-lucide="camera"></i>
            Scan Kamera
        </button>

        <div id="scanner" class="hidden mb-4">
            <div id="reader"></div>
        </div>

        <form method="POST" class="space-y-4">

            <div>
                <label class="text-sm text-gray-600">
                    Scan / Input Barcode
                </label>
                <input type="text" name="barcode" id="barcodeInput"
                    placeholder="Scan / Input Barcode"
                    class="w-full mt-1 border p-3 rounded-xl focus:ring-2 focus:ring-blue-400"
                    required autofocus>
            </div>

            <div>
                <label class="text-sm text-gray-600">
                    Jumlah masuk
                </label>
                <input type="number" name="qty" id="qtyInput"
                    placeholder="Jumlah masuk"
                    class="w-full mt-1 border p-3 rounded-xl focus:ring-2 focus:ring-blue-400"
                    min="1" required>
            </div>

            <button name="submit"
                class="w-full bg-blue-500 text-white p-3 rounded-xl hover:bg-blue-600 flex items-center justify-center gap-2 shadow">
                <i data-lucide="download"></i>
                Scan Masuk
            </button>

        </form>

    </div>

</div>

<script>
lucide.createIcons();

let html5QrCode;
let active = false;

function startScanner() {
    const scanner = document.getElementById("scanner");

    if (!active) {
        scanner.classList.remove("hidden");
        html5QrCode = new Html5Qrcode("reader");

        Html5Qrcode.getCameras().then(devices => {
            if (devices.length) {
                let cam = devices[0].id;
                const back = devices.find(d => d.label.toLowerCase().includes('back'));
                if (back) cam = back.id;

                html5QrCode.start(
                    cam,
                    { fps: 10, qrbox: 250 },
                    (decodedText) => {
                        document.getElementById("barcodeInput").value = decodedText;
                        html5QrCode.stop();
                        scanner.classList.add("hidden");
                        active = false;
                        document.getElementById("qtyInput").focus();
                    }
                );
                active = true;
            }
        }).catch(() => {
            alert("Tidak bisa akses kamera");
        });
    } else {
        html5QrCode.stop();
        scanner.classList.add("hidden");
        active = false;
    }
}
</script>

</body>
</html>