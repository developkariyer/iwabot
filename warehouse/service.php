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
    cli_exec();
    exit;
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


function cli_exec() {
    rearrangeStrafors();
}


function rearrangeStrafors() {
    WarehouseAbstract::clearAllCache();
    $db = $GLOBALS['pdo'];
    $stmt = $db->prepare("SELECT cp.product_id,cp.container_id,p.category,c.type,COUNT(*) AS product_count
                        FROM warehouse_container_product cp
                        JOIN warehouse_product p ON cp.product_id = p.id
                        JOIN warehouse_container c ON cp.container_id = c.id
                        WHERE p.category = 'STRAFOR' AND cp.deleted_at IS NULL AND p.deleted_at IS NULL AND c.deleted_at IS NULL
                        GROUP BY cp.product_id,cp.container_id,p.category,c.type
                        ORDER BY c.type DESC;");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $shelf = [];
    try {
        foreach ($results as $result) {
            if ($result['type'] === 'Raf') {
                $shelf[$result['product_id']] = $result['container_id'];
                continue;
            }
            if (empty($shelf[$result['product_id']])) {
                echo "***************** Product {$result['product_id']} not found in shelf\n";
                print_r($result);
                continue;
            }
            echo "Product {$result['product_id']} found in shelf {$shelf[$result['product_id']]} and in container {$result['container_id']}...\n";
            $parent = WarehouseContainer::getById($shelf[$result['product_id']]);
            $container = WarehouseContainer::getById($result['container_id']);
            $product = WarehouseProduct::getById($result['product_id']);
            if (!$parent || !$container || !$product) {
                echo "***************** Product {$result['product_id']} not found\n";
                print_r($result);
                continue;
            }
            echo "Moving {$result['product_count']} {$product->name} from {$container->name} to {$parent->name}...\n";
            $product->moveToContainer($container, $parent, $result['product_count'], true);
            echo "Deleting container {$container->name}...\n";
            if ($container->delete()) {
                addMessage("{$container->name} içindeki ürünler {$parent->name} altına taşındı ve {$container->name} silindi");
            } else {
                addMessage("$container->name silinemedi");
            }
        }
    } catch (Exception $e) {
        echo "***************** Exception: ".$e->getMessage()."\n";
    }
    WarehouseAbstract::clearAllCache();
}



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
    foreach ($containerNames as $containerName) {
        $container = WarehouseContainer::getByField('name', $containerName);
        if ($container && $container->type === 'Koli' && $container->parent->name === $shipName && in_array($container->name, $containerNames)) {
//            echo "Found container $container->name\n";
            $stmt = $GLOBALS['pdo']->prepare("SELECT deleted_at FROM warehouse_container WHERE id = :id");
            $stmt->execute(['id' => $container->id]);
            $deleted_at = $stmt->fetchColumn();
            if ($deleted_at) {
//                echo "\tRestoring container $container->name...\n";
                $stmt = $GLOBALS['pdo']->prepare("UPDATE warehouse_container SET deleted_at = NULL WHERE id = :id");
                if ($stmt->execute(['id' => $container->id])) {
//                    echo "\tContainer $container->name restored\n";
                } else {
                    echo "\tFailed to restore container $container->name\n";
                }
                /*
                $stmt = $GLOBALS['pdo']->prepare("SELECT product_id FROM warehouse_container_product WHERE container_id = :id AND deleted_at IS NOT NULL");
                $stmt->execute(['id' => $container->id]);
                $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "\tFound ".count($products)." products in $container->name\n";
                foreach ($products as $product_id) {
                    $product = WarehouseProduct::getById($product_id);
                    if ($product) {
                        echo "\t\tRestoring product $product->fnsku ($product->name) to $container->name...\n";
                        if ($dryRun) {
                            echo "\t\tDry run for product->placeInContainer($container->id)\n";
                        } else {
                            if ($product->placeInContainer($container)) {
                                echo "\t\tProduct {$product->name} restored\n";
                            } else {
                                echo "\t\tFailed to restore product {$product->name}\n";
                                exit;
                            }
                        }
                    } else {
                        echo "\t\tProduct not found!\n";
                        exit;
                    }
                }*/
            } else {
                echo "Container $container->name is not deleted\n";
            }
            //echo "Continuing retrieve";
        }
    }
    echo "All containers in $shipName retrieved\n";
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



