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

$allProducts = StockProduct::allProducts($GLOBALS['pdo']);

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-5">
        <h2>Ürün Arama</h2>
        <p>
            Bu bölümde, aşağıdaki 4 yöntemden birini kullanarak üzerinde işlem (rafa/koliye koy, raf/koli arası taşı, satışa gönder) yapmak istediğiniz ürünü seçmelisiniz:
        </p>
        <ul>
            <li>
                <strong>Çıkış İçin Bekleyen Ürünler</strong> başlığına bastığınızda etiketi gönderilmiş, kargo bekleyen ürünler listelenmektedir.
            </li>
            <li>
                <strong>Manuel Barkod Girin</strong> kutusunda FNSKU/Barkod numarasını elle veya bilgisayara bağlı barkod okuyucu ile girerek ürün arayabilirsiniz.
            </li>
            <li>
                <strong>Manuel Ürün Seçin</strong> kısmında dilerseniz stok numarası olan tüm ürünler içinden seçim yaparak ilerleyebilirsiniz.
            </li>
            <li>
                Android telefon ile kullanıyorsanız, <strong>Kameradan Tara</strong> diyerek kameranız ile barkod okutmayı tercih edebilirsiniz.
            </li>
        </ul>
        <h4 class="bs-primary-border-subtle" data-bs-toggle="collapse" data-bs-target="#productAccordion" aria-expanded="true" aria-controls="productAccordion">Çıkış İçin Bekleyen Ürünler <small>(<?= count($products) ?> adet) <i>Görmek için basınız</i></small></h4>
        <div class="accordion collapse collapse" id="productAccordion">
            <?php foreach ($products as $index => $product): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button btn-success collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                            <span><strong><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</strong></span>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#productAccordion">
                        <div class="accordion-body">
                            <p><?= nl2br(htmlspecialchars($product->productInfo())) ?></p>
                            <p>Adres</p>
                            <a href="wh_product.php?product=<?= urlencode($product->id) ?>" class="btn btn-outline-success btn-lg rounded-pill w-100 py-3 mt-2">Seç</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="input-group mt-4">
            <input type="text" id="manualBarcode" class="form-control rounded-start" placeholder="Manuel Barkod Girin">
            <button class="btn btn-primary btn-lg rounded-end" id="manualSubmit">Gönder</button>
        </div>
        <div class="mt-5">
            <select class="form-select manual-select" id="manualSelect" aria-label="">
                <option selected>Manuel Ürün Seçin</option>
                <?php foreach ($allProducts as $product): ?>
                    <option value="<?= $product->fnsku ?>" data-product-id="<?= $product->id ?>"><?= htmlspecialchars($product->fnsku) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-none" id="cameraOpenDiv">
            <video id="video" width="100%" height="400" autoplay></video>
            <p>Scanned Code: <span id="barcode">Waiting...</span></p>
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
        const manualSelect = document.getElementById('manualSelect');

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
                    productInfo.innerHTML = response.productInfo.replace(/\n/g, '<br>'); // Convert newlines to <br>
                    devamLink.href = 'wh_product.php?product=' + response.productId; // Set product link using product ID
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
                const productId = button.getAttribute('data-product-id');
                window.location.href = 'wh_product.php?product=' + productId; // Directly navigate to wh_product.php
            });
        });

        manualSelect.addEventListener('change', () => {
            const productId = manualSelect.value;
            if (productId) {
                getProductInfo(productId); // Trigger the modal with product information
            }
        });

        openCameraButton.addEventListener('click', openCamera);
    });
</script>

<?php include '_footer.php'; ?>
