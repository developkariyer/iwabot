<?php

require_once('_login.php');
require_once('_init.php');

include '_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>IWA Depo Yönetim</h1>
        <p><span class="username"><?= $_SESSION['user_info']['name'] ?></span></p>
        <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
    </div>
    <div class="d-grid gap-2 m-3">
        <a href="wh_" class="btn btn-primary btn-lg">Ürün Bul</a>
        <a href="wh_" class="btn btn-primary btn-lg">Kutu Bul</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_" class="btn btn-primary btn-lg">Raftan/Kutudan Ürün Al</a>
        <a href="wh_" class="btn btn-primary btn-lg">Rafa/Kutuya Ürün Koy</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_" class="btn btn-primary btn-lg">Raftan Kutu Al</a>
        <a href="wh_" class="btn btn-primary btn-lg">Rafa Kutu Koy</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_" class="btn btn-primary btn-lg">Güncel Depo Listesi</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="./" class="btn btn-secondary btn-lg">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg">Logout</a>
    </div>



    <div class="mt-5">
        <h2>Barcode Scanner</h2>
        <video id="video" width="100%" height="400" autoplay></video>
        <p>Scanned Code: <span id="barcode">Waiting...</span></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.20/dist/index.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", async function () {
        const video = document.getElementById('video');
        const barcodeElement = document.getElementById('barcode');

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;

                const barcodeDetector = new BarcodeDetector({ formats: ['code_128', 'ean_13', 'qr_code'] });

                video.addEventListener('play', () => {
                    const scanBarcode = async () => {
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            try {
                                const barcodes = await barcodeDetector.detect(video);
                                if (barcodes.length > 0) {
                                    barcodeElement.innerText = barcodes[0].rawValue;
                                }
                            } catch (error) {
                                console.error('Barcode detection failed:', error);
                            }
                        }
                        requestAnimationFrame(scanBarcode);
                    };
                    scanBarcode();
                });
            } catch (error) {
                console.error('Error accessing the camera:', error);
            }
        } else {
            alert('getUserMedia is not supported by your browser');
        }
    });
</script>
<?php



include '_footer.php';