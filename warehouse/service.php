<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (php_sapi_name() === 'cli') {
    echo "Running in CLI mode ...\n";
    require_once('../_init.php');
    require_once 'WarehouseAbstract.php';
    require_once 'WarehouseProduct.php';
    require_once 'WarehouseContainer.php';
    require_once 'WarehouseSold.php';
    require_once 'WarehouseLogger.php';
} else {
    require_once 'warehouse.php';
    include "../_header.php";
?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Servis İşlemleri</h1>
        <p>İşlem kayıtlarını ve sipariş karşılanma durumunu görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>

    <div id="empty-containers" class="m-3 p-3 border d-none"></div>

    <div class="row g-3 m-1 mt-1">
        <?= button('controller.php?action=clear_cache', 'Önbellek Temizle', 'success') ?>
        <?= button('controller.php?action=user_info', 'Kullanıcı İstatistikleri', 'success', 'btn-user-info') ?>
    </div>
    <div class="row g-3 m-1 mt-1">
        <?= button('controller.php?action=empty_containers', 'Boş Raf/Koliler', 'success', 'btn-empty-containers') ?>
        <?= button('controller.php?action=containers_in_ship', 'Gemide Kalan Koliler', 'success', 'btn-containers-in-ship') ?>
    </div>

    <hr>

    <?= wh_menu() ?>
</div>

<script>
    $(document).ready(function() {
        $('#btn-empty-containers').click(function() {
            $.ajax({
                url: 'controller.php?action=empty_containers',
                success: function(data) {
                    $('#empty-containers').html(data);
                    $('#empty-containers').removeClass('d-none');
                }
            });
            return false;
        });
        $('#btn-containers-in-ship').click(function() {
            $.ajax({
                url: 'controller.php?action=containers_in_ship',
                success: function(data) {
                    $('#empty-containers').html(data);
                    $('#empty-containers').removeClass('d-none');
                }
            });
            return false;
        });
        $('#btn-user-info').click(function() {
            $.ajax({
                url: 'controller.php?action=user_info',
                success: function(data) {
                    $('#empty-containers').html(data);
                    $('#empty-containers').removeClass('d-none');
                }
            });
            return false;
        });
    });
</script>

<?php

    include "../_footer.php";
    exit;   
}






WarehouseAbstract::clearAllCache();

undeleteShipContainers('Gemi-28', [
    '28-0004',
    '28-0006',
    '28-0009',
    '28-4049',
    '28-4046',
    '28-4066',
    '28-4065',
    '28-4047',
    '28-4062',
    '28-4044',
    '28-4039',
    '28-4040',
    '28-4062',
    '28-4043',
    '28-4041',
    '28-4051',
    '28-4050',
    '28-4052',
    '28-4053',
    '28-4064',
    '28-4069',
    '28-4045',
    '28-4048',
    '28-4068',
    '28-4063',
    '28-4067',
    '28-4055',
    '28-4054',
    '28-4036',
    '28-4034',
    '28-4035',
    '28-4032',
    '28-4033',
    '28-4037',
    '28-4038',
    '28-1020',
    '28-0007',
    '28-0008',
    '28-1017',
    '28-0018',
    '28-0003',
    '28-0001',
    '28-0002',
    '28-0019',
    '28-2011',
    '28-2010',
    '28-2005',
    '28-2007',
    '28-2004',
    '28-2006',
    '28-2009',
    '28-2013',
    '28-2012',
    '28-2003',
    '28-2008',
    '28-2002',
], true);




WarehouseAbstract::clearAllCache();


function getMissingProductImages() {
    $products = WarehouseProduct::getAll();
    $product_images = file_get_contents('images_colorful.json');
    $product_images = json_decode($product_images, true);
    $stmt = $GLOBALS['pdo']->prepare("UPDATE warehouse_product SET image_url = ? WHERE id = ?");
    foreach ($products as $product) {
        if (empty($product->image_url)) {
            if  (isset($product_images[$product->fnsku])) {
                if ($stmt->execute([$product_images[$product->fnsku], $product->id])) {
                    echo "Updated image for $product->fnsku\n";
                } else {
                    echo "Failed to update image for $product->fnsku\n";
                }
            } else {
                echo "No image found for $product->fnsku\n";
            }
        }
    }
}


function undeleteShipContainers($shipName, $containerNames = [], $dryRun = false) {
    echo "Retrieving containers in $shipName";
    $containers = WarehouseContainer::getAll();
    foreach ($containers as $container) {
        if ($container->type === 'Koli' && $container->parent->name === $shipName && in_array($container->name, $containerNames)) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT deleted_at FROM warehouse_container WHERE id = :id");
            $stmt->execute(['id' => $container->id]);
            $deleted_at = $stmt->fetchColumn();
            if ($deleted_at) {
                echo "Restoring container $container->name...\n";
                $stmt = $GLOBALS['pdo']->prepare("SELECT product_id FROM warehouse_container_product WHERE container_id = :id AND deleted_at IS NOT NULL");
                $stmt->execute(['id' => $container->id]);
                $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "\nFound ".count($products)." products in $container->name\n";
                foreach ($products as $product_id) {
                    $product = WarehouseProduct::getById($product_id);
                    if ($product) {
                        echo "    Restoring product $product->fnsku ($product->name) to $container->name...\n";
                        if ($dryRun) {
                            echo "    Dry run for product->placeInContainer($container->id)\n";
                        } else {
                            $product->placeInContainer($container);
                        }
                    } else {
                        echo "    Product not found!\n";
                    }
                }
            } else {
                echo "Container $container->name is not deleted\n";
            }

            echo "Continuing retrieve";
        }
    }
}














function cleanShips() {
    echo "Deleting products and containers in ships...\n";
    $containers = WarehouseContainer::getAll();
    $ships = [];
    foreach ($containers as $container) {
        if ($container->type === 'Gemi') {
            if ($container->name === 'Gemi-29') {
                continue;
            }
            echo "Found ship: $container->name\n";
            $children = $container->getChildren();
            $children[] = $container;
            foreach ($children as $child) {
                echo "    Found container: $child->name\n";
                $products = $child->getProducts();
                foreach ($products as $product) {
                    echo "        Found ".$product->getInContainerCount($child)." $product->name. Deleting...";
                    if ($product->removeFromContainer($child, $product->getInContainerCount($child), true)) {
                        echo " OK\n";
                    } else {
                        echo " FAILED\n";
                    }
                }
                echo "    Deleting container $child->name..."; 
                if ($child->delete()) {
                    echo " OK\n";
                } else {
                    echo " FAILED\n";
                }
            }
            echo "$container->name deleted\n";
        }
    }
}

function cik() {
    echo "Exiting...\n";
    $GLOBALS['pdo']->rollBack();
    exit;
}

function readCsv() {
    echo "Flushing tables...\n";
    $GLOBALS['pdo']->query('TRUNCATE warehouse_container_product');
    $GLOBALS['pdo']->query('TRUNCATE warehouse_container');
    $GLOBALS['pdo']->query('TRUNCATE warehouse_log');


    echo "Reading koliler...";
    $boxcsv = file_get_contents('koliler.csv');
    $boxcsv = explode("\n", $boxcsv);
    echo "done\n";

    $raflar = [];

    $GLOBALS['pdo']->beginTransaction();

    foreach ($boxcsv as $line) {
        $line = trim($line);
        echo "Processing $line...";
        if (empty($line)) {
            echo "empty!\n";
            continue;
        }
        $data = explode(',', $line);
        $koli = $data[0];
        if (strpos($koli, '-')) {
            $raf = explode('-', $koli)[0];
        } else {
            $raf = substr($koli, 0, 1);
        }
        $urun = $data[1];
        $adet = $data[2];

        echo "********Raf: $raf, Koli: $koli, Ürün: $urun, Adet: $adet ...\n";

        if (!($adet>0)) {
            echo "invalid quantity!\n";
            continue;
        }
        if (!isset($raflar[$raf])) {
            $raflar[$raf] = WarehouseContainer::getByField('name', "Gemi-$raf");
            if (!$raflar[$raf]) {
                echo "creating shelf...";
                $raflar[$raf] = WarehouseContainer::addNew(['name' => "Gemi-$raf", 'type' => 'Gemi', 'parent_id' => null]);
            } else echo "shelf found...";
            if (!$raflar[$raf]) {
                echo "failed to create shelf\n";
                cik();
            }
        }
        if (!isset($raflar[$koli])) {
            $raflar[$koli] = WarehouseContainer::getByField('name', $koli);
            if (!$raflar[$koli]) {
                echo "creating box...";
                $raflar[$koli] = WarehouseContainer::addNew(['name' => $koli, 'type' => 'Koli', 'parent_id' => $raflar[$raf]->id]);
            } else echo "box found...";
            if (!$raflar[$koli]) {
                echo "failed to create box\n";
                cik();
            }
        }
        if (!isset($urunler[$urun])) {
            $urunler[$urun] = WarehouseProduct::getByField('fnsku', $urun);
            if (!$urunler[$urun]) {
                echo "product not found!\n";
                file_put_contents('log.txt', "$line\n", FILE_APPEND);
                continue;
            }
        }

        for ($t=0;$t<$adet;$t++) {
            if ($urunler[$urun]->placeInContainer($raflar[$koli])) {
                echo ".";
            } else {
                echo "failed to place product in box\n";
                cik();
            }
        }
        echo "done\n";
    }
    $GLOBALS['pdo']->commit();
}



