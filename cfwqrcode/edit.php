<?php

require_once "../_init.php";
require_once "../_login.php";
require_once 'QrModel.php';

$uniqueCode = $_GET['unique_code'] ?? null;
if ($uniqueCode) {
    $qrModel = new QrModel();
    $record = $qrModel->getQRCodeByUniqueCode($uniqueCode);
    if (!$record) {
        header('Location: index.php?status=error&message=Geçersiz QR kodu.');
        exit();
    }
} else {
    header('Location: index.php?status=error&message=QR kodu seçilmedi.');
    exit();
}
if (!$record) {
    header('Location: index.php?status=error&message=Kayıt bulunamadı.');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_info'])) {
    $qrlink = $_POST['qrlink'];
    $description = $_POST['description'];

    if (empty($qrlink) || empty($description)) {
        header('Location: edit.php?status=error&message=Link ve açıklama boş bırakılamaz.');
        exit();
    }
    if (!filter_var($qrlink, FILTER_VALIDATE_URL)) {
        header('Location: edit.php?status=error&message=Geçerli bir URL giriniz.');
        exit();
    }
    // update database
    $updateResult = $qrModel->updateQRCode($uniqueCode, $description, $qrlink);
    if ($updateResult) {
        header('Location: index.php?status=success&code=' . urlencode($uniqueCode));
    } else {
        header('Location: edit.php?status=error&message=Güncelleme sırasında bir hata oluştu.');
    }
}
unset($qrModel);

include '../includes/_header.php';

?>

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
        <h2>QR Kod Bilgilerini Güncelleyin</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="qrlink">QR Kod Bağlantısı:</label>
                <input type="text" class="form-control" name="qrlink" id="qrlink" value="<?php echo htmlspecialchars($record['link']); ?>" placeholder="URL link giriniz"  required>
            </div>
            <div class="form-group">
                <label for="description">Açıklama:</label>
                <textarea class="form-control" name="description" id="description" rows="3"  placeholder="QR kodu için açıklama giriniz" required><?php echo htmlspecialchars($record['description']); ?></textarea>
            </div>
            <button type="submit" name="submit_info" class="btn btn-success mt-4">QR Kod Güncelle ve Kaydet</button>
        </form>
        <a href="index.php" class="btn btn-primary mt-3">Ana Sayfaya Dön</a>
    </div>
</body>

</html>
