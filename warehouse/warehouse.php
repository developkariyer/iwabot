<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once('../_login.php');
require_once('../_init.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once('WarehouseAbstract.php');
require_once('WarehouseProduct.php');
require_once('WarehouseContainer.php');

function button($url, $text, $color='primary') {
    return '<div class="col-md-6"><a href="'.$url.'" class="btn btn-'.$color.' btn-lg rounded-pill w-100 py-3">'.$text.'</a></div>';
}

function wh_menu() {
    return 
    '<div class="row g-3 m-1">'.
        button('product.php', 'Ürün İşlem', 'secondary').
        button('container.php', 'Koli/Raf İşlem', 'secondary').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('inventory.php', 'Depo Envanteri', 'secondary').
        button('transfers.php', 'Hareket Raporu', 'secondary').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('order.php', 'Sipariş İşlem', 'secondary').
        button('controller.php?action=clear_cache', 'Önbellek Temizle', 'secondary').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('./', 'Depo Ana Sayfa', 'secondary').
        button('../', 'Ana Sayfa', 'secondary').
    '</div><div class="row g-3 m-1 mt-1">'.
        '<div class="col-md-3"></div>'.
        button('../?logout=1', 'Çıkış', 'danger').
    '</div>';
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
    <b>Özellikler (metrik):</b><br>{$product->dimension1}x{$product->dimension2}x{$product->dimension3}cm, {$product->weight}gr<br>
    <b>Özellikler (imperyal):</b><br>".metricToImp($product->dimension1)."x".metricToImp($product->dimension2)."x".metricToImp($product->dimension3)."in, ".metricToImp($product->weight, 0.00220462)."lbs<br>
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
        $html .= "<br>Bu koli boş.";
    } else {
        $html .= "<ul>";
        foreach($products as $product) {
            $html .= "<li>{$product->name} ({$product->fnsku}): ".$product->getInContainerCount($container)."</li>";
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
        error_log("Cache Hit: containersInOpt{$type}");
        return $cache;
    }
    error_log("Cache Miss: containersInShipOpt");

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
        error_log("Cache Hit: parentContainersOpt{$type}");
        return $cache;
    }
    error_log("Cache Miss: parentContainersOpt{$type}");

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
        error_log("Cache Hit: containerOptGrouped{$product_id}");
        return $cache;
    }
    error_log("Cache Miss: containerOptGrouped{$product_id}");

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
        error_log("Cache Hit: productSelect{$product_id}");
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
    error_log("Cache Miss: productSelect{$product_id}");
    WarehouseAbstract::setCache("productSelect{$product_id}", $html);
    return $html;
}