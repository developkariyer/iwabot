<?php

require_once "../_init.php";
require_once 'QrModel.php';
require_once "../_login.php";


$uniqueCode = $_GET['code'] ?? null;
$status = $_GET['status'] ?? null;
$filePath = $_GET['file'] ?? null;
$search = $_GET['search'] ?? null;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10; // Her sayfada gösterilecek kayıt sayısı

$qrModel = new QrModel();
$totalRecords = $qrModel->getTotalRecords($search); 
$totalPages = ceil($totalRecords / $recordsPerPage); 

$start = ($page - 1) * $recordsPerPage;
$records = $qrModel->getRecords($start, $recordsPerPage,$search); 
unset($qrModel);

include '../_header.php';

?>

<div class = "container mt-5">
    <div class="jumbotron text-center">
            <h1>IWA Bot'a hoş geldiniz, <span class="username"><?= $_SESSION['user_info']['name'] ?? 'Misafir' ?></span></h1>
            <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
        </div>
        <div class="container mt-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="index.php" class="btn btn-primary w-100">Listele</a>
            </div>
            <div class="col-md-4 mb-3">
                <form method="post" action="generate.php" class="d-inline w-100">
                    <button type="submit" class="btn btn-primary w-100">QR Kod Oluştur</button>
                </form>
            </div>
            <div class="col-md-4 mb-3">
                <form method="get" action="" class="form-inline d-flex w-100">
                    <div class="form-group mr-2 flex-grow-1">
                        <input type="text" name="search" class="form-control w-100" placeholder="Linke göre ara..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Ara</button>
                </form>
            </div>
        </div>
    </div>



    <?php if ($status === 'success') : ?>
        <script>
                document.addEventListener('DOMContentLoaded', function() {
                    alert('QR kodu başarıyla oluşturuldu.');
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.pathname);
                    }
                });
        </script>
    <?php elseif ($status === 'error') : ?>
        <script>
                document.addEventListener('DOMContentLoaded', function() {
                    alert('QR kodu oluşturulurken bir hata oluştu.');
                });
        </script>
    <?php endif; ?>


    <table class="table table-bordered mt-5">
            <thead>
                <tr>
                <th>Oluşturulma Tarihi</th>
                    <th>QR Kod Png</th>
                    <th>QR Kod Svg</th>
                    <th>Açıklama</th>
                    <th>Kod</th>
                    <th>Link</th>
                    <th>Kullanıcı Adı</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['created_at']); ?></td>
                        <td>
                            <a href="download.php?code=<?php echo urlencode($record['unique_code']); ?>&format=png"  class="btn btn-info">QR Kodunu İndir (PNG)</a>
                        </td>
                        <td>
                            <a href="download.php?code=<?php echo urlencode($record['unique_code']); ?>&format=svg"  class="btn btn-info">QR Kodunu İndir (SVG)</a>
                        </td>

                        <td><?php echo htmlspecialchars($record['description']); ?></td>
                        <td><?php echo htmlspecialchars($record['unique_code']); ?></td>
                        <td><a href="<?php echo htmlspecialchars($record['link']); ?>" target="_blank">Linki Aç</a></td>
                        <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                        <td>
                            <a href="edit.php?unique_code=<?php echo urlencode($record['unique_code']); ?>" class="btn btn-warning">Düzenle</a>
                            <a href="delete.php?unique_code=<?php echo urlencode($record['unique_code']); ?>" class="btn btn-danger" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </table>

    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($page > 1) : ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Önceki">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages) : ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Sonraki">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>