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
        <h1>İşlem Kayıtları</h1>
        <p>İşlem kayıtlarını ve sipariş karşılanma durumunu görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>

    <div class="row g-3 m-1 mt-1">
        <?= button('controller.php?action=clear_cache', 'Önbellek Temizle', 'success') ?>
        <?= button('controller.php?action=empty_containers', 'Boş Raf/Koliler', 'success', 'btn-empty-containers') ?>
    </div>

    <div id="empty-containers"></div>

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






undeleteShipContainers('Gemi-28');




function undeleteShipContainers($shipName) {
    $containers = WarehouseContainer::getAll();
    foreach ($containers as $container) {
        if ($container->type === 'Koli' && $container->parent->name === $shipName) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT product_id FROM warehouse_container_product WHERE container_id = :id AND deleted_at IS NOT NULL");
            $stmt->execute(['id' => $container->id]);
            $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "Found ".count($products)." products in $container->name\n";
            foreach ($products as $product_id) {
                $product = WarehouseProduct::getById($product_id);
                if ($product) {
                    echo "    Restoring product $product->fnsku ($product->name) to $container->name...";
                }
            }
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



