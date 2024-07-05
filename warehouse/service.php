<?php

if (php_sapi_name() !== 'cli') {
    die('Hello World!');
}

require_once('WarehouseAbstract.php');
require_once('WarehouseProduct.php');
require_once('WarehouseContainer.php');

require_once ('../_init.php');

echo "Reading koliler...";
$boxcsv = file_get_contents('koliler.csv');
$boxcsv = explode("\n", $boxcsv);
echo "done\n";

$raflar = [];

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

    echo "Raf: $raf, Koli: $koli, Ürün: $urun, Adet: $adet ...";

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
            exit;
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
            exit;
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
            exit;
        }
    }
    echo "done\n";
}









/* Unknown Service
$iblck = addInfluencerSuccessBlock('Umut');
$json = json_encode($iblck, JSON_PRETTY_PRINT);
print_r($json);
*/

/* Add Users Service
$emails = "yukselaytac.sid@gmail.com
mdnllcusa@gmail.com
husbas@gmail.com
Adamdorrsan@gmail.com
amzhakankirec@gmail.com
se.selimevren@gmail.com
fatmarslan114@gmail.com
maytac118@gmail.com
sedatyayla2412@gmail.com
gdanismanahmetercan@gmail.com
iwa@kariyerfora.com
brkmrl33@gmail.com
fceyhan55@gmail.com
kemalkurada@gmail.com
berkayiwa@gmail.com
ecepolatiwa@gmail.com
serkaniwaconcept@gmail.com
merve.yyorulmaz@gmail.com
omermesut6406@gmail.com
seymakandemirr99@gmail.com
ctarikiwa@gmail.com
iwacwf@gmail.com
nyepey2023@gmail.com
yuceliwa@gmail.com
kadergulakyol@gmail.com
mahiryusuf531@gmail.com
emrahteacher4448@gmail.com
iwaconcepttr@gmail.com
kyhnbdrhn0@gmail.com
7sagey1@gmail.com
emrahbilaloglu@gmail.com
nurten.yener.ba@gmail.com
pogenver@gmail.com
hbetulnursen@gmail.com
alfonsooiwa@gmail.com
hkus3052@gmail.com
omermenekse@gmail.com
citiglobalmobilya@gmail.com
umitgulmaden@gmail.com
srknkrgc@gmail.com
niyazisonmez07@gmail.com
iwaconceptpersonal@gmail.com
cihatemreiwa@gmail.com
zehraiwa@gmail.com
iwaconcept06@gmail.com
ilkermeric@gmail.com
krmykmz@gmail.com
iwaconcept1@gmail.com
emrahiwa@gmail.com
mustafacolakk1978@gmail.com
yusufgozukara11@gmail.com
ayselkurt987@gmail.com
ugurkaraca1011@gmail.com
dgnaltkn@gmail.com
ozaneller29@gmail.com
aligokkayaa2001@gmail.com
usancar57@gmail.com
Ot2825@gmail.com
lvntats75@gmail.com
unal.boztas3425@gmail.com
elifnazli83@gmail.com
mrvshn70@hotmail.com
iwaconcept.office@gmail.com
glsrn0633@gmail.com
emrebedelnl@gmail.com
umut@kariyerfora.com
bahadiriwa@gmail.com
ferdiozkayaiwa@gmail.com";


$emails = explode("\n", $emails);

foreach ($emails as $email) {
    $email = trim($email);
    $payload = [
        'login' => $email,
        'email' => $email,
        'firstName' => $email,
        'lastName' => $email,
        'status' => 'invited',
    ];
    $response = openProjectApiPost('/api/v3/users', $payload);
    echo "$email: $response\n\n";
}
*/
