<?php

require_once('warehouse.php');
include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>IWA Depo Yönetim</h1>
        <p>Barkodu kameraya gösteriniz veya elle giriniz.</p>
        <p class="alert alert-warning">iOS cihazlarda çalışmaz :(</p>
    </div>

    <div class="d-none" id="cameraOpenDiv">
        <video id="video" width="100%" height="400" autoplay></video>
    </div>
    <p class="text-center"><span id="barcode">Hazır...</span></p>
    <div class="row g-3 m-1 mb-3">
        <div class="col-md-3"></div>
        <div class="col-md-6">
            <input type="text" id="barcodeInput" class="form-control" placeholder="Barkodu elle giriniz" autofocus>
        </div>
    </div>
    <div class="row g-3 m-1 mb-3">
        <div class="col-md-3"></div>
        <div class="col-md-6">
            <button class="btn btn-success btn-lg rounded-pill w-100 py-3" id="openCamera" onclick="toggleCamera()">Kameradan Tara</button>
        </div>
    </div>

    <?= wh_menu() ?>
</div>

<script>
    let stream;
    let video;
    let barcodeDetector;

    document.addEventListener('DOMContentLoaded', (event) => {
        document.getElementById('barcodeInput').focus();

        document.getElementById('barcodeInput').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const barcode = e.target.value.trim();
                if (barcode) {
                    document.getElementById('barcode').textContent = barcode;
                    checkBarcode(barcode);
                }
            }
        });
    });

    const toggleCamera = async () => {
        const cameraOpenDiv = document.getElementById('cameraOpenDiv');
        const openCameraButton = document.getElementById('openCamera');
        video = document.getElementById('video');

        if (cameraOpenDiv.classList.contains('d-none')) {
            cameraOpenDiv.classList.remove('d-none');
            openCameraButton.textContent = 'Kamerayı Kapat';
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    video.srcObject = stream;
                    barcodeDetector = new BarcodeDetector({ formats: ['code_128', 'ean_13'] });
                    video.addEventListener('play', scanBarcode);
                } catch (error) {
                    console.error('Error accessing the camera:', error);
                }
            } else {
                alert('getUserMedia is not supported by your browser');
            }
        } else {
            closeCamera();
        }
    };

    const closeCamera = () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        if (video) {
            video.pause();
            video.removeEventListener('play', scanBarcode);
        }
        document.getElementById('cameraOpenDiv').classList.add('d-none');
        document.getElementById('openCamera').textContent = 'Kameradan Tara';
    };

    const scanBarcode = async () => {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            try {
                const barcodes = await barcodeDetector.detect(video);
                if (barcodes.length > 0) {
                    const detectedBarcode = barcodes[0].rawValue;
                    document.getElementById('barcode').textContent = detectedBarcode;
                    checkBarcode(detectedBarcode);
                }
            } catch (error) {
                console.error('Barcode detection failed:', error);
            }
        }
        requestAnimationFrame(scanBarcode);
    };

    const checkBarcode = (barcode) => {
        closeCamera(); // Stop camera during barcode check
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'controller.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            console.log(xhr.responseText);
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.id && !response.error) {
                    window.location.href = `product.php?product_id=${response.id}`;
                } else {
                    console.error('Error in response:', response.error);
                    document.getElementById('barcode').textContent = `${barcode} - Ürün Bulunamadı!`;
                    reopenCamera();
                }
            }
        };
        xhr.send(`fnsku=${barcode}&action=barcode_scan`);
    };

    const reopenCamera = async () => {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                video.play();
            } catch (error) {
                console.error('Error reopening the camera:', error);
            }
        }
    };
</script>

<?php

include '../_footer.php';

?>
