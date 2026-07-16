<?php
include __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'toko') {
    exit("No access");
}

$message = "";

if (isset($_POST['scan'])) {

    $barcode = mysqli_real_escape_string($conn, $_POST['barcode']);
    $qty = (int) $_POST['qty'];
    $store_id = (int) $_SESSION['user']['id'];

    if ($qty <= 0) {
        $message = "❌ Qty tidak valid";
    } else {

        // ambil egg
        $egg = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM eggs WHERE barcode='$barcode' LIMIT 1"
        ));

        if (!$egg) {
            $message = "❌ Barcode tidak ditemukan";

        } else {

            $egg_id = $egg['id'];

            // ambil stok toko
            $stock = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT * FROM egg_stocks 
                 WHERE egg_id='$egg_id' 
                 AND store_id='$store_id'
                 LIMIT 1"
            ));

            if (!$stock) {
                $message = "❌ Barang belum masuk ke toko ini";

            } else if ($stock['remaining'] < $qty) {
                $message = "❌ Stok tidak cukup (sisa: {$stock['remaining']})";

            } else {

                mysqli_begin_transaction($conn);

                try {

                    // update stok toko
                    mysqli_query($conn, "
                        UPDATE egg_stocks
                        SET sold = sold + $qty,
                            remaining = remaining - $qty
                        WHERE id = {$stock['id']}
                    ");

                    // log
                    mysqli_query($conn, "
                        INSERT INTO egg_logs (egg_id,status,location,role)
                        VALUES ('$egg_id','SOLD','Toko','toko')
                    ");

                    mysqli_commit($conn);

                    // Menambahkan simbol ✔ agar terbaca oleh kondisi pewarnaan CSS di bawah
                    $message = "✔ Berhasil jual $qty telur";

                } catch (Exception $e) {

                    mysqli_rollback($conn);
                    $message = "❌ Terjadi kesalahan sistem";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scan Terjual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-3xl mx-auto p-6">

    <div class="bg-white p-5 rounded-2xl shadow mb-6 flex items-center gap-4">

        <a href="../dashboard_toko.php"
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200">
            <i data-lucide="chevron-left"></i>
        </a>

        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="shopping-cart" class="text-green-500"></i>
                Scan Terjual
            </h1>
            <p class="text-sm text-gray-500">
                Proses penjualan telur di toko
            </p>
        </div>

    </div>

    <div class="bg-white p-6 rounded-2xl shadow">

        <?php if (!empty($message)): ?>
            <div class="mb-4 p-3 rounded flex items-center gap-2 text-sm
                <?= str_contains($message,'✔') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                
                <i data-lucide="<?= str_contains($message,'✔') ? 'check-circle' : 'alert-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <button type="button"
                onclick="startScanner()"
                class="mb-3 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg flex items-center gap-2">
            <i data-lucide="camera"></i>
            Scan Kamera
        </button>

        <div id="scanner" class="hidden mb-4">
            <div id="reader"></div>
        </div>

        <form method="POST" class="flex flex-col gap-4">

            <input type="text"
                   id="barcodeInput"
                   name="barcode"
                   placeholder="Scan / Input Barcode"
                   class="border p-3 rounded-lg focus:ring-2 focus:ring-green-400"
                   required>

            <input type="number"
                   id="qtyInput"
                   name="qty"
                   placeholder="Jumlah terjual"
                   class="border p-3 rounded-lg focus:ring-2 focus:ring-green-400"
                   min="1"
                   required>

            <button name="scan"
                    class="bg-green-500 text-white p-3 rounded-xl hover:bg-green-600 flex items-center justify-center gap-2">
                Scan Terjual
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

                // prefer kamera belakang
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