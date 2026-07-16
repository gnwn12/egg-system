<?php
include __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'peternak') {
    exit("Akses ditolak");
}

// ==========================================================
// PROSES PENGAMBILAN NOTIFIKASI ANTISIPASI RE-SUBMIT (F5)
// ==========================================================
$message = "";
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_POST['submit'])) {

    $barcode = md5(uniqid());

    $jenis_telur = $_POST['jenis_telur'];
    $production = $_POST['production'];
    $qty = (int) $_POST['qty'];

    // ======================
    // TELUR MENTAH
    // ======================

    if ($jenis_telur == "mentah") {

        $method = "-";

        $lama_pengasinan = 0;
        $lama_expired = 14;

        $tanggal_mulai_asin = null;
        $tanggal_selesai_asin = null;

        $expired = date(
            'Y-m-d',
            strtotime($production . " +14 days")
        );

        $status_produk = "Siap Distribusi";

    } else {

        // ======================
        // TELUR ASIN
        // ======================

        $method = $_POST['method'];

        if ($method == "air_garam") {

            $lama_pengasinan = 7; // Durasi Baru: Rendam Air Garam 7 Hari
            $lama_expired = 14;

        } elseif ($method == "bata") {

            $lama_pengasinan = 10; // Durasi Baru: Serbuk Bata 10 Hari
            $lama_expired = 14;

        } elseif ($method == "arang") {

            $lama_pengasinan = 10; // Durasi Baru: Serbuk Arang 10 Hari
            $lama_expired = 14;

        } else {

            $lama_pengasinan = 0;
            $lama_expired = 14;
        }

        $tanggal_mulai_asin = $production;

        $tanggal_selesai_asin = date(
            'Y-m-d',
            strtotime($tanggal_mulai_asin . " +$lama_pengasinan days")
        );

        $expired = date(
            'Y-m-d',
            strtotime($tanggal_selesai_asin . " +$lama_expired days")
        );

        $status_produk = "Sedang Diasinkan";
    }

    // ======================
    // Batch Peternak
    // ======================

    $batch_peternak = "PTK-" . date('Ymd') . "-" . rand(100,999);

    // ======================
    // Insert Database
    // ======================

    mysqli_query($conn, "
        INSERT INTO eggs
        (
            barcode,
            method,
            production_date,
            expired_date,
            quantity,
            remaining,
            status,
            created_at,

            jenis_telur,
            metode_pengasinan,
            lama_pengasinan,
            tanggal_produksi,
            tanggal_mulai_asin,
            tanggal_selesai_asin,
            expired_at,
            batch_peternak,
            status_produk

        )
        VALUES
        (
            '$barcode',
            '$method',
            '$production',
            '$expired',
            '$qty',
            '$qty',
            'CREATED',
            NOW(),

            '$jenis_telur',
            '$method',
            '$lama_pengasinan',
            '$production',
            '$tanggal_mulai_asin',
            '$tanggal_selesai_asin',
            '$expired',
            '$batch_peternak',
            '$status_produk'
        )
    ");

    // Simpan pesan ke session lalu alihkan halaman (Anti-Resubmit)
    $_SESSION['success_message'] = "Batch berhasil dibuat";
    header("Location: create.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Input Produksi</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

<div class="max-w-5xl mx-auto p-6">

    <div class="bg-white p-6 rounded-2xl shadow mb-6">

        <h1 class="text-3xl font-bold text-gray-800">
            Input Produksi Telur
        </h1>

        <p class="text-sm text-gray-500 mt-2">
            Sistem monitoring produksi telur asin
        </p>

    </div>

    <?php if (!empty($message)) { ?>

        <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">

            <?= $message ?>

        </div>

    <?php } ?>

    <div class="bg-white p-6 rounded-2xl shadow">

        <form method="POST" class="space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>

                    <label class="text-sm text-gray-600">
                        Jenis Telur
                    </label>

                    <select name="jenis_telur"
                            id="jenis_telur"
                            class="w-full mt-2 border p-3 rounded-xl">

                        <option value="asin">
                            Telur Asin
                        </option>

                        <option value="mentah">
                            Telur Mentah
                        </option>

                    </select>

                </div>

                <div id="method_container">

                    <label class="text-sm text-gray-600">
                        Metode Pengasinan
                    </label>

                    <select name="method"
                            id="method"
                            class="w-full mt-2 border p-3 rounded-xl">

                        <option value="bata">
                            Serbuk Bata
                        </option>

                        <option value="arang">
                            Serbuk Arang
                        </option>

                        <option value="air_garam">
                            Rendam Air Garam
                        </option>

                    </select>

                </div>

                <div>

                    <label class="text-sm text-gray-600">
                        Jumlah Telur
                    </label>

                    <input type="number"
                           name="qty"
                           required
                           class="w-full mt-2 border p-3 rounded-xl"
                           placeholder="Contoh: 300">

                </div>

                <div>

                    <label class="text-sm text-gray-600">
                        Tanggal Produksi
                    </label>

                    <input type="date"
                           name="production"
                           id="production"
                           required
                           class="w-full mt-2 border p-3 rounded-xl">

                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="bg-blue-50 p-4 rounded-xl">

                    <p class="text-sm text-gray-500">
                        Lama Pengasinan
                    </p>

                    <h2 id="lama"
                        class="text-2xl font-bold text-blue-600 mt-2">

                        -
                    </h2>

                </div>

                <div class="bg-yellow-50 p-4 rounded-xl">

                    <p class="text-sm text-gray-500">
                        Selesai Pengasinan
                    </p>

                    <h2 id="selesai"
                        class="text-lg font-bold text-yellow-600 mt-2">

                        -
                    </h2>

                </div>

                <div class="bg-red-50 p-4 rounded-xl">

                    <p class="text-sm text-gray-500">
                        Expired
                    </p>

                    <h2 id="expired"
                        class="text-lg font-bold text-red-600 mt-2">

                        -
                    </h2>

                </div>

            </div>

            <button name="submit"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-xl font-semibold transition">

                Simpan Produksi

            </button>

        </form>

    </div>

</div>

<script>

const jenis = document.getElementById('jenis_telur');
const method = document.getElementById('method');
const production = document.getElementById('production');

const lama = document.getElementById('lama');
const selesai = document.getElementById('selesai');
const expired = document.getElementById('expired');

const methodContainer = document.getElementById('method_container');

function hitungTanggal() {

    // ======================
    // TELUR MENTAH
    // ======================

    if (jenis.value == 'mentah') {

        methodContainer.style.display = 'none';

        lama.innerHTML = '-';
        selesai.innerHTML = '-';

        if (production.value != "") {

            let tgl = new Date(production.value);

            let expiredDate = new Date(tgl);
            expiredDate.setDate(expiredDate.getDate() + 14);

            expired.innerHTML =
                expiredDate.toISOString().split('T')[0];
        }

        return;
    }

    // ======================
    // TELUR ASIN
    // ======================

    methodContainer.style.display = 'block';

    let hari = 0;
    let expiredHari = 14;

    if (method.value == 'air_garam') {

        hari = 7; // Tampilan Layar Durasi Baru: 7 Hari

    } else if (method.value == 'bata') {

        hari = 10; // Tampilan Layar Durasi Baru: 10 Hari

    } else if (method.value == 'arang') {

        hari = 10; // Tampilan Layar Durasi Baru: 10 Hari
    }

    lama.innerHTML = hari + " Hari";

    if (production.value != "") {

        let tgl = new Date(production.value);

        // selesai pengasinan
        let selesaiAsin = new Date(tgl);
        selesaiAsin.setDate(selesaiAsin.getDate() + hari);

        // expired
        let expiredDate = new Date(selesaiAsin);
        expiredDate.setDate(expiredDate.getDate() + expiredHari);

        selesai.innerHTML =
            selesaiAsin.toISOString().split('T')[0];

        expired.innerHTML =
            expiredDate.toISOString().split('T')[0];
    }
}

jenis.addEventListener('change', hitungTanggal);
method.addEventListener('change', hitungTanggal);
production.addEventListener('change', hitungTanggal);

hitungTanggal();

</script>

</body>
</html>