<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$productList = $GLOBALS['pdo']->query("SELECT * FROM wh_sold")->fetchAll(PDO::FETCH_ASSOC);
$products = [];
foreach ($productList as $product) {
    $p = StockProduct::getById($product['product_id'], $GLOBALS['pdo']);
    $products[$p->id] = $p;
}

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-5">
        <h2>Çıkış İçin Bekleyen Ürünler</h2>
        <div class="accordion" id="productAccordion">
            <?php foreach ($products as $index => $product): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button btn-success collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                            <span><?= $product->name ?> (<?= $product->fnsku ?>)</span>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#productAccordion">
                        <div class="accordion-body">
                            <p><?= $product->productInfo() ?></p>
                            <p>Adres</p>
                            <button class="btn btn-outline-success btn-lg rounded-pill w-100 py-3 mt-2 select-button" data-fnsku="<?= $product->fnsku ?>" data-product-id="<?= $product->id ?>" onclick="event.stopPropagation();">Seç</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-none" id="cameraOpenDiv">
            <video id="video" width="100%" height="400" autoplay></video>
            <p>Scanned Code: <span id="barcode">Waiting...</span></p>
        </div>
        <div class="input-group mt-4">
            <input type="text" id="manualBarcode" class="form-control rounded-start" placeholder="Manuel Barkod Girin">
            <button class="btn btn-primary btn-lg rounded-end" id="manualSubmit">Gönder</button>
        </div>
        <div class="row g-3 m-3 mt-5">
            <div class="col-md-3"></div>
            <div class="mt-3 col-md-6">
                <button class="btn btn-success btn-lg rounded-pill w-100 py-3" id="openCamera">Kameradan Tara</button>
            </div>
        </div>
    </div>
    <?= wh_menu() ?>
</div>

<!-- Modal for barcode confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Ürün Bilgisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="product-info">Loading...</div>
                <p>Barcode: <span id="modal-barcode"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="backButton">Geri Dön</button>
                <a href="#" id="devamLink" class="btn btn-success">Devam</a>
            </div>
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
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const backButton = document.getElementById('backButton');
        const devamLink = document.getElementById('devamLink');
        const manualSubmit = document.getElementById('manualSubmit');
        const manualBarcode = document.getElementById('manualBarcode');
        const openCameraButton = document.getElementById('openCamera');
        const cameraOpenDiv = document.getElementById('cameraOpenDiv');

        let stream;

        const getProductInfo = (detectedBarcode) => {
            barcodeElement.innerText = detectedBarcode;
            modalBarcode.innerText = detectedBarcode;

            // Make AJAX call to get product info
            $.ajax({
                url: 'wh_product_info.php',
                method: 'POST',
                data: { 
                    product: detectedBarcode,
                },
                dataType: 'json',
                success: function(response) {
                    productInfo.innerHTML = response.productInfo;
                    devamLink.href = 'wh_product.php?product=' + detectedBarcode; // Set product link
                    confirmModal.show();
                },
                error: function() {
                    productInfo.innerHTML = 'Failed to retrieve product information.';
                    confirmModal.show();
                }
            });
        };

        const openCamera = async () => {
            cameraOpenDiv.classList.remove('d-none');
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
        };

        backButton.addEventListener('click', async () => {
            confirmModal.hide();
            openCamera();
        });

        manualSubmit.addEventListener('click', () => {
            const manualBarcodeValue = manualBarcode.value.trim();
            if (manualBarcodeValue) {
                getProductInfo(manualBarcodeValue);
            }
        });

        manualBarcode.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                manualSubmit.click();
            }
        });

        document.querySelectorAll('.select-button').forEach(button => {
            button.addEventListener('click', () => {
                const fnsku = button.getAttribute('data-fnsku');
                const productId = button.getAttribute('data-product-id');
                getProductInfo(fnsku);
                devamLink.href = 'wh_product.php?product=' + productId; // Set product link
            });
        });

        openCameraButton.addEventListener('click', openCamera);
    });
</script>

<?php include '_footer.php'; ?>
