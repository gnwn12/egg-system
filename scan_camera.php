<!DOCTYPE html>
<html>
<head>
    <title>Scan Kamera</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-xl mx-auto p-6">

    <div class="mb-6 flex items-center gap-3">

        <a href="index.php"
           class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200">
            ←
        </a>

        <h1 class="text-xl font-bold">Scan Barcode</h1>
    </div>

    <div class="bg-white p-4 rounded-2xl shadow">

        <div id="reader" style="width:100%"></div>

        <p class="text-sm text-gray-500 mt-3 text-center">
            Arahkan kamera ke QR / Barcode
        </p>

    </div>

</div>

<script>
function onScanSuccess(decodedText) {
    window.location.href = "eggs/detail.php?barcode=" + decodedText;
}

function onScanError(error) {
}

const html5QrCode = new Html5Qrcode("reader");

Html5Qrcode.getCameras().then(devices => {
    if (devices && devices.length) {

        let cameraId = devices[0].id;

        const backCam = devices.find(d => d.label.toLowerCase().includes('back'));
        if (backCam) cameraId = backCam.id;

        html5QrCode.start(
            cameraId,
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanError
        );

    } else {
        alert("Kamera tidak ditemukan");
    }
}).catch(err => {
    console.error(err);
    alert("Tidak bisa akses kamera");
});
</script>

</body>
</html>