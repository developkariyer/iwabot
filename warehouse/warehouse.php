<?php

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
        button('product.php', 'Ürün İşlem').
        button('container.php', 'Koli/Raf İşlem').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('inventory.php', 'Depo Envanteri').
        button('transfers.php', 'Hareket Raporu').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('addsolditem.php', 'Yeni Sipariş').
        button('order.php', 'Sipariş Sil').
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
    return "
    <b>Ürün Adı:</b> {$product->name}<br>
    <b>FNSKU:</b> {$product->fnsku}<br>
    <b>Kategori:</b> {$product->category}<br>
    <b>IWASKU:</b> {$product->iwasku}<br>
    <b>Özellikler (metrik):</b><br>{$product->dimension1}x{$product->dimension2}x{$product->dimension3}cm, {$product->weight}gr<br>
    <b>Özellikler (imperyal):</b><br>".metricToImp($product->dimension1)."x".metricToImp($product->dimension2)."x".metricToImp($product->dimension3)."in, ".metricToImp($product->weight, 0.00220462)."lbs<br>
    <b>Toplam Depo Stoğu:</b> {$product->getTotalCount()} adet<br>";
    //    <b>Seri Numarası:</b> {$product->serial_number}<br>
}

function containerOptGrouped($product) {
    if (!$product instanceof WarehouseProduct) {
        throw new Exception('containerOptGrouped fonksiyonuna geçersiz bir ürün nesnesi gönderildi.');
    }
    $containers = $product->getContainers();
    $raflar = [];
    foreach($containers as $container) {
        if ($container->type == 'Raf' || $container->type == 'Gemi') {
            if ($container->type === 'Gemi') {
                $icon = '🚢'; //\u{1F6A2}
            } else {
                $icon = '🗄️'; // \u{1F5C4}
            }
            if (!isset($raflar["$icon {$container->name}"])) {
                $raflar["$icon {$container->name}"] = [];
            }
            $raflar["$icon {$container->name}"][] = $container;
        } else {
            if ($container->parent) {
                if ($container->parent->type === 'Gemi') {
                    $icon = '🚢'; //\u{1F6A2}
                } else {
                    $icon = '🗄️'; // \u{1F5C4}
                }
                if (!isset($raflar["$icon {$container->parent->name}"])) {
                    $raflar["$icon {$container->parent->name}"] = [];
                }
                $raflar["$icon {$container->parent->name}"][] = $container;
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
            $html .= '<option value="'.$container->id.'">';
            if ($container->type === 'Koli') {
                $icon = '📦'; //\u{1F4E6}
                $html .= "$icon {$container->name} ({$container->parent->name})";
            } elseif ($container->type === 'Raf') {
                $icon = '📤'; //\u{1F4E4}
                $html .= "$icon {$container->name} rafında açık";
            } else {
                $html .= $container->name.' gemisinde bilinmiyor! (HATA!)';
            }
            $html .= '('.$product->getInContainerCount($container).')';
            $html .= '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}

function productSelect() {
    $GLOBALS['footer_script'] = '$(document).ready(function(){$(\'.select2-select\').select2({theme: "classic"});});';

    $options = [];
    foreach (WarehouseProduct::getAll() as $product) {
        if (!isset($options[$product->category])) {
            $options[$product->category] = [];
        }
        $options[$product->category][] = '<option value="'.$product->id.'">'.$product->name.' ('.$product->fnsku.')</option>';
    }
    $html = '<select name="product_id" class="select2-select form-select w-100" required style="width: 100%; height: 50px;">';
    $html .= '<option value="">Ürün Seçin</option>';
    foreach($options as $category => $products) {
        $html .= '<optgroup label="'.$category.'">';
        $html .= implode('', $products);
        $html .= '</optgroup>';
    }
    $html .= '</select>';
    return $html;
}