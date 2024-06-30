<?php

if (php_sapi_name() !== 'cli') {
    die('Hello World!');
}

require_once ('_init.php');


//$response = openProjectApiGet('/api/v3/queries/242');
//$response = openProjectApiGet('/api/v3/projects/36/work_packages', ['columns' => 'id']);
//$response = openProjectApiGet('/api/v3/projects/36/work_packages?columns%5B%5D=id&columns%5B%5D=category&columns%5B%5D=dueDate&columns%5B%5D=subject&columns%5B%5D=type&columns%5B%5D=status&columns%5B%5D=assignee&columns%5B%5D=author&columns%5B%5D=percentageDone&filters=%5B%7B%22status%22%3A%7B%22operator%22%3A%22o%22%2C%22values%22%3A%5B%5D%7D%7D%5D&groupBy=assigned_to&includeSubprojects=true&offset=1&pageSize=50&showHierarchies=false&showSums=false&sortBy=%5B%5B%22id%22%2C%22asc%22%5D%5D');
//$response = openProjectApiGet('/api/v3/users', ['pageSize' => 200]);
//print_r($response);


require_once('wh_include.php');

echo "Reading koliler.txt...";
$boxcsv = file_get_contents('koliler.txt');
$boxcsv = explode("\n", $boxcsv);
echo "done\n";

$boxes = [];

foreach ($boxcsv as $line) {
    $line = trim($line);
    echo "Processing $line...";
    if (empty($line)) {
        echo "empty!\n";
        continue;
    }
    $data = explode(',', $line);
    $raf = explode('-', $data[0])[0];
    echo "Raf: $raf, Koli: $data[0], Ürün: $data[1], Adet: $data[2] ...";
    if (!isset($boxes[$raf])) {
        $boxes[$raf] = StockShelf::getById($raf, $GLOBALS['pdo']);
        if (!$boxes[$raf]) {
            echo "creating shelf...";
            $boxes[$raf] = StockShelf::newShelf(db:$GLOBALS['pdo'], name:"Gemi-$raf", type:'Raf');
        } else echo "shelf found...";
        if (!$boxes[$raf]) {
            echo "failed to create shelf\n";
            continue;
        }
    }
    $flag=true;
    for ($t=0;$t<$data[2];$t++) {
        $product = StockProduct::getByFnsku($data[1], $GLOBALS['pdo']);
        if (!$product) {
            $flag=false;
            break;
        }
    }
    if (!$flag) {
        echo "missing products in box!\n";
        continue;
    }

    for ($t=0;$t<$data[2];$t++) {
        $product = StockProduct::getByFnsku($data[1], $GLOBALS['pdo']);
        $product->putOnShelf($boxes[$raf], log:false);
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
