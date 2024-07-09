<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once('../_login.php');
require_once('../_init.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'WarehouseAbstract.php';
require_once 'WarehouseProduct.php';
require_once 'WarehouseContainer.php';

function button($url, $text, $color='primary') {
    return "<div class=\"col-md-6\"><a href=\"$url\" class=\"btn btn-$color btn-lg rounded-pill w-100 py-3\">$text</a></div>";
}

function userCan($actions = []) {
    if (empty($actions)) {
        return false;
    }
    if (!is_array($actions)) {
        $actions = [$actions];
    }
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    loadPermissions();
    foreach ($actions as $action) {
        if (!in_array($action, ['process', 'view', 'order', 'manage'])) {
            throw new Exception("Geçersiz yetki: $action");
        }
        if (in_array($_SESSION['user_id'], $GLOBALS['permissions'][$action])) {
            return true;
        }
    }
    return false;
}

function slackChannels() {
    if (!isset($GLOBALS['slackChannels']) || !is_array($GLOBALS['slackChannels'])) {
        $GLOBALS['slackChannels'] = [];
        $sql = "SELECT channel_id, name FROM channels ORDER BY name";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $GLOBALS['slackChannels'][$row['channel_id']] = $row['name'];
        }
    }
    return $GLOBALS['slackChannels'];
}

function slackUsers() {
    if (!isset($GLOBALS['slackUsers']) || !is_array($GLOBALS['slackUsers'])) {
        $GLOBALS['slackUsers'] = [];
        $sql = "SELECT user_id, json->>'$.name' AS name, json->>'$.real_name' AS real_name FROM users ORDER BY real_name;";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $GLOBALS['slackUsers'][$row['user_id']] = "{$row['real_name']} ({$row['name']})";
        }
    }
    return $GLOBALS['slackUsers'];
}

function loadPermissions($noCache = false) {
    if ($noCache || !isset($GLOBALS['permissions']) || !is_array($GLOBALS['permissions'])) {
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM warehouse_user");
        $stmt->execute();
        $viewChannels = $viewUsers = $orderUsers = $processUsers = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch($row['permission']) {
                case 'process':
                    $processUsers[] = $row['user_id'];
                    $viewUsers[] = $row['user_id'];
                    break;
                case 'view':
                    $viewChannels[] = $row['user_id'];
                    break;
                case 'order':
                    $orderUsers[] = $row['user_id'];
                    $viewUsers[] = $row['user_id'];
                    break;
                case 'manage':
                    $manageUsers[] = $row['user_id'];
                    $orderUsers[] = $row['user_id'];
                    $processUsers[] = $row['user_id'];
                    $viewUsers[] = $row['user_id'];
                    break;
            }
        }
        $stmt = $GLOBALS['pdo']->prepare("SELECT user_id FROM channel_user WHERE channel_id = ?");
        foreach ($viewChannels as $channel_id) {
            $stmt->execute([$channel_id]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $viewUsers[] = $row['user_id'];
            }
        }
        $GLOBALS['permissions'] = [
            'view_channels' => $viewChannels,
            'process' => array_unique($processUsers),
            'view' => array_unique($viewUsers),
            'order' => array_unique($orderUsers),
            'manage' => array_unique($manageUsers),
        ];
    }
}

function wh_menu() {
    $menu = '';
    if (userCan('process')) {
        $menu .= '<div class="row g-3 m-1">'.button('product.php', 'Ürün İşlem', 'secondary').button('container.php', 'Koli/Raf İşlem', 'secondary').'</div>';
    }
    if (userCan('view')) {
        $menu .= '<div class="row g-3 m-1 mt-1">'.button('inventory.php', 'Depo Envanteri', 'secondary').button('transfers.php', 'İşlem Kayıtları', 'secondary').'</div>';
    }
    if (userCan('order')) {
        $menu .= '<div class="row g-3 m-1 mt-1">'.button('order.php', 'Sipariş İşlem', 'secondary').button('#', 'Yeni Gemi Yükle', 'secondary').'</div>';
    }
    if (userCan('manage')) {
        $menu .= '<div class="row g-3 m-1 mt-1">'.button('users.php', 'Kullanıcı Yönetimi', 'secondary').button('controller.php?action=clear_cache', 'Önbellek Temizle', 'secondary').'</div>';
    }
    $menu .= '<div class="row g-3 m-1 mt-1">'.button('./', 'Depo Ana Sayfa', 'secondary').button('../', 'Ana Sayfa', 'secondary').'</div>';
    $menu .= '<div class="row g-3 m-1 mt-1">'.'<div class="col-md-3"></div>'.button('../?logout=1', 'Çıkış', 'danger').'</div>';
    return $menu;
}

function metricToImp($inp, $conv=0.393700787) {
    return number_format($inp * $conv, 2);
}

function productInfo($product) {
    if (!$product instanceof WarehouseProduct) {
        return "Ürün bilgisi alınamadı: Geçersiz ürün";
    }
    return "<b>Ürün Adı:</b> {$product->name}<br>
    <b>FNSKU:</b> {$product->fnsku}<br>
    <b>Kategori:</b> {$product->category}<br>
    <b>IWASKU:</b> {$product->iwasku}<br>
    <b>Özellikler (metrik):</b><br>{$product->dimension1}x{$product->dimension2}x{$product->dimension3}cm, {$product->weight}kg<br>
    <b>Özellikler (imperyal):</b><br>".metricToImp($product->dimension1)."x".metricToImp($product->dimension2)."x".metricToImp($product->dimension3)."in, ".metricToImp($product->weight, 2.20462)."lbs<br>
    <b>Toplam Depo Stoğu:</b> {$product->getTotalCount()} adet<br>";
    //    <b>Seri Numarası:</b> {$product->serial_number}<br>
}

function containerInfo($container) {
    if (!$container instanceof WarehouseContainer) {
        return "Koli bilgisi alınamadı: Geçersiz koli";
    }
    $icon = [
        'Gemi' => '🚢', //\u{1F6A2}
        'Raf' => '🗄️', // \u{1F5C4}
        'Koli' => '📦', //\u{1F4E6}
    ];
    $html = "<b>Adı:</b> {$container->name}<br>
    <b>Tipi:</b> {$icon[$container->type]} {$container->type}<br>";
    if ($container->parent) {
        $html .= "<b>Rafı:</b> {$icon[$container->parent->type]} {$container->parent->name}<br>";
    }
    $html .= "<b>İçindeki Ürünler:</b>";
    $products = $container->getProducts();
    if (empty($products)) {
        if ($container->type == 'Koli') {
            $html .= "<br>Bu koli boş görünüyor.";
        }
        if ($container->type == 'Raf') {
            $html .= "<br>Bu rafta açıkta ürün yok.";
        }
    } else {
        $html .= "<ul>";
        foreach($products as $product) {
            $html .= "<li>{$product->name} ({$product->fnsku}): ".$product->getInContainerCount($container)." adet</li>";
        }
        $html .= "</ul>";
    }
    return $html;
}

function containersInOpt($type='Raf') {
    if (!in_array($type, ['Raf', 'Gemi'])) {
        throw new Exception('Konteyner tipi "Raf" veya "Gemi" olmalı. Verilen: '.$type);
    }
    $cache = WarehouseAbstract::getCache("containersInOpt{$type}");
    if (!empty($cache) && is_string($cache)) {
        return $cache;
    }

    $html = '';
    $containers = WarehouseContainer::getContainers(type: $type);
    $icon = [
        'Gemi' => '🚢', //\u{1F6A2}
        'Raf' => '🗄️', // \u{1F5C4}
    ];
    foreach ($containers as $container) {
        $html .= "<optgroup label='{$icon[$type]} {$container->name}'>";
        foreach (WarehouseContainer::getContainers(type: 'Koli', parent_id: $container->id) as $box) {
            $html .= "<option value='{$box->id}'>📦 {$box->name}</option>";
        }
    }
    WarehouseAbstract::setCache("containersInOpt{$type}", $html);
    return $html;
}

function parentContainersOpt($type = 'Raf') {
    if (!in_array($type, ['Raf', 'Gemi'])) {
        throw new Exception('Ana konteyner tipi "Raf" veya "Gemi" olmalı. Verilen: '.$type);
    }
    $cache = WarehouseAbstract::getCache("parentContainersOpt{$type}");
    if (!empty($cache) && is_string($cache)) {
        return $cache;
    }

    $icon = [
        'Gemi' => '🚢', //\u{1F6A2}
        'Raf' => '🗄️', // \u{1F5C4}
    ];
    $containers = WarehouseContainer::getContainers(type: $type);
    $html = '';
    foreach($containers as $container) {
        if ($type && $container->type !== $type) {
            continue;
        }
        $html .= "<option value='{$container->id}'>{$icon[$container->type]} {$container->name}</option>";
    }
    WarehouseAbstract::setCache("parentContainersOpt{$type}", $html);
    return $html;
}

function containerOptGrouped($product = null) {
    $product_id = $product instanceof WarehouseProduct ? $product->id : 0;

    $cache = WarehouseAbstract::getCache("containerOptGrouped{$product_id}");
    if (!empty($cache) && is_string($cache)) {
        return $cache;
    }

    if ($product instanceof WarehouseProduct) {
        $containers = $product->getContainers();
    } else {
        $containers = WarehouseContainer::getAll();
    }
    $raflar = [];
    $icon = [
        'Gemi' => '🚢', //\u{1F6A2}
        'Raf' => '🗄️', // \u{1F5C4}
        'Koli' => '📦', //\u{1F4E6}
    ];
    foreach($containers as $container) {
        if ($container->type == 'Raf' || $container->type == 'Gemi') {
            if (!isset($raflar["{$icon[$container->type]} {$container->name}"])) {
                $raflar["{$icon[$container->type]} {$container->name}"] = [];
            }
            $raflar["{$icon[$container->type]} {$container->name}"][] = $container;
        } else {
            if ($container->parent) {
                if (!isset($raflar["{$icon[$container->parent->type]} {$container->parent->name}"])) {
                    $raflar["{$icon[$container->parent->type]} {$container->parent->name}"] = [];
                }
                $raflar["{$icon[$container->parent->type]} {$container->parent->name}"][] = $container;
            } else {
                throw new Exception('Rafı veya gemisi olmayan bir Koli var: '.$container->name);
            }            
        }
    }
    ksort($raflar);
    $html = '';
    foreach($raflar as $raflar_name => $raflar_containers) {
        $html .= '<optgroup label="'.$raflar_name.'">';
        foreach($raflar_containers as $container) {
            if ($container->type === 'Koli') {
                $icon = '📦'; //\u{1F4E6}
                $html .= "<option value='{$container->id}'>$icon {$container->name} ({$container->parent->name})";
            } elseif ($container->type === 'Raf') {
                $icon = '📤'; //\u{1F4E4}
                $html .= "<option value='{$container->id}'>$icon {$container->name} rafında açık";
            } else {
                continue;
            }
            if ($product instanceof WarehouseProduct) {
                $html .= '('.$product->getInContainerCount($container).')';
            }
            $html .= '</option>';
        }
        $html .= '</optgroup>';
    }
    WarehouseAbstract::setCache("containerOptGrouped{$product_id}", $html);
    return $html;
}

function productSelect($product_id = 0) {
    $GLOBALS['footer_script'] = '$(document).ready(function(){$(\'.select2-select\').select2({theme: "classic"';
    if ($product_id) {
        $GLOBALS['footer_script'] .= ',val:"'.$product_id.'"';
    }
    $GLOBALS['footer_script'].='});});';

    $cache = WarehouseAbstract::getCache("productSelect{$product_id}");
    if (!empty($cache) && is_string($cache)) {
        return $cache;
    }

    $options = [];
    foreach (WarehouseProduct::getAll() as $product) {
        if (!isset($options[$product->category])) {
            $options[$product->category] = [];
        }
        if ($product_id && $product->id == $product_id) {
            $selected = ' selected';
        } else {
            $selected = '';
        }
        $options[$product->category][] = "<option value='{$product->id}' $selected>{$product->name} ({$product->fnsku})</option>";
    }
    $html = '<select id="product_select" name="product_id" class="select2-select form-select w-100" required style="width: 100%;">';
    $html .= '<option value="">Ürün Seçin</option>';
    foreach($options as $category => $products) {
        $html .= '<optgroup label="'.$category.'">';
        $html .= implode('', $products);
        $html .= '</optgroup>';
    }
    $html .= '</select>';
    WarehouseAbstract::setCache("productSelect{$product_id}", $html);
    return $html;
}