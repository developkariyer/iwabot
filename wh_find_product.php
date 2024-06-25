<?php

require_once('_login.php');
require_once('_init.php');

include '_header.php';

?>
<div class="container mt-5">
    <div class="mt-5">
        <h2>Barcode Scanner</h2>
        <video id="video" width="100%" height="400" autoplay></video>
        <form id="barcodeForm" action="process_barcode.php" method="POST" class="d-none">
            <input type="text" id="barcodeInput" name="barcode">
        </form>
        <p>Scanned Code: <span id="barcode">Waiting...</span></p>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="./wh.php" class="btn btn-secondary btn-lg">Depo Yönetim Ana Sayfa</a>
        <a href="./" class="btn btn-secondary btn-lg">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg">Logout</a>
    </div>
</div>

<!-- Modal for barcode confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Barcode Detected</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Barcode: <span id="modal-barcode"></span><br>
                Do you want to use this barcode?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="rejectButton" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-primary" id="acceptButton">Yes</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.20/dist/index.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", async function () {
        const video = document.getElementById('video');
        const barcodeElement = document.getElementById('barcode');
        const modalBarcode = document.getElementById('modal-barcode');
        const barcodeInput = document.getElementById('barcodeInput');
        const barcodeForm = document.getElementById('barcodeForm');
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const acceptButton = document.getElementById('acceptButton');
        const rejectButton = document.getElementById('rejectButton');

        let stream;

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;

                const barcodeDetector = new BarcodeDetector({ formats: ['code_128', 'ean_13', 'qr_code'] });

                video.addEventListener('play', () => {
                    const scanBarcode = async () => {
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            try {
                                const barcodes = await barcodeDetector.detect(video);
                                if (barcodes.length > 0) {
                                    const detectedBarcode = barcodes[0].rawValue;
                                    barcodeElement.innerText = detectedBarcode;
                                    modalBarcode.innerText = detectedBarcode;
                                    video.pause();
                                    stream.getTracks().forEach(track => track.stop());
                                    confirmModal.show();
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

        acceptButton.addEventListener('click', () => {
            barcodeInput.value = modalBarcode.innerText;
            //barcodeForm.submit();
        });

        rejectButton.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                video.play();
            } catch (error) {
                console.error('Error restarting the camera:', error);
            }
        });
    });
</script>

<?php

include '_footer.php';
