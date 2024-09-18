<?php

require_once "../_init.php";
require_once "../_login.php";
require_once 'QrModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_info'])) {
    $qrModel = new QrModel();
    $qrlink = $_POST['qrlink'] ?? ''; 
    $description = $_POST['description'] ?? ''; 
    $uniqueCode = $qrModel->generateUniqueCode(); 
    $logoPath = null;
    
    //yonlendirme yapilacak sayfa
    $qrbaselink = "https://cfw.web.tr/{$uniqueCode}";

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoTmpName = $_FILES['logo']['tmp_name'];
        $logoExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logoPath = "../storage/tmp/logo.$uniqueCode.$logoExtension";
        if (in_array($logoExtension, ['png'])) {
            move_uploaded_file($logoTmpName, $logoPath);
        }
    }
    if (empty($qrlink) || empty($description)) {
        header('Location: generate.php?status=error&message=Link ve açıklama boş bırakılamaz.');
        exit();
    }
    if (!filter_var($qrlink, FILTER_VALIDATE_URL)) {
        header('Location: generate.php?status=error&message=Geçerli bir URL giriniz.');
        exit();
    }
    //$result = $qrModel->createQRCodeWithLogo($qrbaselink, $qrImagePath, $logoPath);
   
    $result_png = $qrModel->createQRCodeWithLogo($qrbaselink, $logoPath);
    $result_svg = $qrModel->createQRCodeSvg($qrbaselink);
    if ($result_png&&$result_svg) {
        $qrModel->saveQrCode($uniqueCode, $result_png,$result_svg, $description, $qrlink, "test");
        header('Location: index.php?status=success&code=' . urlencode($uniqueCode));
    } else {
        header('Location: index.php?status=error');
    }
    unset($qrModel);
    exit();
}
?>

<?php include '../_header.php'; ?>

    <body>
        <div class="container mt-5">
        <?php if (isset($_GET['status'])): ?>
            <?php
            $status = $_GET['status'];
            $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
            ?>
            <div class="alert alert-<?php echo $status === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
            <h2>QR Kod Bilgilerini Girin</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="qrlink">QR Kod Bağlantisi:</label>
                    <input type="text" class="form-control" name="qrlink" id="qrlink" placeholder="Url link giriniz" required>
                </div>
                <div class="form-group">
                    <label for="description">Açıklama:</label>
                    <textarea class="form-control" name="description" id="description" rows="3" placeholder="QR kodu için açıklama giriniz" required></textarea>
                </div>
                <div class="form-group mt-3">
                    <label for="logo">Logo Dosyası:</label>
                    <input type="file" class="form-control" name="logo" id="logo" accept="image/png" >
                </div>
                <button type="submit" name="submit_info" class="btn btn-success mt-4">QR Kod Oluştur ve Kaydet</button>
            </form>
            <a href="index.php" class="btn btn-primary mt-3">Ana Sayfaya Dön</a>
        </div>
    </body>

</html>