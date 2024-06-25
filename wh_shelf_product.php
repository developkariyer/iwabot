<?php

require_once('_login.php');
require_once('_init.php');

if (isset($_POST['shelf'])) {
    $shelfId = $_POST['shelf'];
    if ($shelfId === 'add_new') {
        $newShelfName = $_POST['newShelfName'];
        $newShelfType = $_POST['newShelfType'];
        $newShelfParent = $_POST['newShelfParent'] ?: null;

        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO wh_shelf (name, type, parent_id) VALUES (:name, :type, :parent_id)');
        $stmt->execute([
            'name' => $newShelfName,
            'type' => $newShelfType,
            'parent_id' => $newShelfParent
        ]);

        $shelfId = $GLOBALS['pdo']->lastInsertId();
    }

    $shelf_check = $GLOBALS['pdo']->prepare('SELECT * FROM wh_shelf WHERE id = :id LIMIT 1');
    $shelf_check->execute(['id' => $shelfId]);
    if (!$shelf_check->rowCount()) {
        unset($shelfId);
    }
} 

if (empty($shelfId)) {
    header('Location: wh_shelf_select.php');
    exit;
}

include '_header.php';

?>
<div class="container mt-5">
    <div class="mt-5">
        <h2>Sayım / Ürün Yerleştir</h2>
        <video id="video" width="100%" height="400" autoplay></video>
        <p>Scanned Code: <span id="barcode">Waiting...</span></p>
        <div class="input-group mt-3">
            <input type="text" id="manualBarcode" class="form-control" placeholder="Manuel Barkod Girin">
            <button class="btn btn-primary" id="manualSubmit">Submit</button>
        </div>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="./wh.php" class="btn btn-secondary btn-lg w-100">Depo Yönetim Ana Sayfa</a>
        <a href="./" class="btn btn-secondary btn-lg w-100">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg w-100">Logout</a>
    </div>
</div>

<!-- Modal for barcode confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Ürün Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="barcodeForm" action="wh_shelf_product_process.php" method="POST">
                <div class="modal-body">
                    <div id="product-info">Loading...</div>
                    <p>Barcode: <span id="modal-barcode"></span></p>
                    <input type="hidden" id="shelf" name="shelf" value="<?= $shelfId ?>">
                    <input type="hidden" id="barcodeInput" name="barcode">
                    <input type="hidden" id="actionType" name="actionType">
                    <label for="quantity" class="form-label">İşlem Yapılacak Miktar</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="backButton">Geri Dön</button>
                    <button type="button" class="btn btn-primary" id="takeButton">Raftan Al</button>
                    <button type="button" class="btn btn-primary" id="putButton">Rafa Koy</button>
                </div>
            </form>    
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.20/dist/index.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", async function () {
        const video = document.getElementById('video');
        const barcodeElement = document.getElementById('barcode');
        const modalBarcode = document.getElementById('modal-barcode');
        const productInfo = document.getElementById('product-info');
        const barcodeInput = document.getElementById('barcodeInput');
        const actionType = document.getElementById('actionType');
        const barcodeForm = document.getElementById('barcodeForm');
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const backButton = document.getElementById('backButton');
        const takeButton = document.getElementById('takeButton');
        const putButton = document.getElementById('putButton');
        const manualSubmit = document.getElementById('manualSubmit');
        const manualBarcode = document.getElementById('manualBarcode');

        let stream;

        const getProductInfo = (detectedBarcode) => {
            barcodeElement.innerText = detectedBarcode;
            modalBarcode.innerText = detectedBarcode;
            barcodeInput.value = detectedBarcode;

            // Make AJAX call to get product info
            $.ajax({
                url: 'wh_product_info.php',
                method: 'POST',
                data: { barcode: detectedBarcode },
                dataType: 'json',
                success: function(response) {
                    productInfo.innerHTML = response.productInfo;
                    if (response.stock === 0) {
                        takeButton.disabled = true;
                    } else {
                        takeButton.disabled = false;
                    }
                    confirmModal.show();
                },
                error: function() {
                    productInfo.innerHTML = 'Failed to retrieve product information.';
                    takeButton.disabled = true;
                    confirmModal.show();
                }
            });
        };

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
                                    video.pause();
                                    stream.getTracks().forEach(track => track.stop());
                                    getProductInfo(detectedBarcode);
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

        backButton.addEventListener('click', async () => {
            confirmModal.hide();
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                video.play();
            } catch (error) {
                console.error('Error restarting the camera:', error);
            }
        });

        takeButton.addEventListener('click', () => {
            actionType.value = 'take';
            barcodeForm.submit();
        });

        putButton.addEventListener('click', () => {
            actionType.value = 'put';
            barcodeForm.submit();
        });

        manualSubmit.addEventListener('click', () => {
            const manualBarcodeValue = manualBarcode.value.trim();
            if (manualBarcodeValue) {
                getProductInfo(manualBarcodeValue);
            }
        });
    });
</script>

<?php

include '_footer.php';

