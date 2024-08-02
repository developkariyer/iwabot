<?php
require 'vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRGdImagePNG;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf;

class QRImageWithLogo extends QRGdImagePNG {

    public function dump(string|null $file = null, string|null $logo = null): string {
        $logo ??= '';
        $this->options->returnResource = true;
        if (!is_file($logo) || !is_readable($logo)) {
            throw new QRCodeOutputException('invalid logo');
        }
        parent::dump($file);
        $im = imagecreatefrompng($logo);
        if ($im === false) {
            throw new QRCodeOutputException('imagecreatefrompng() error');
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $lw = (($this->options->logoSpaceWidth - 2) * $this->options->scale);
        $lh = (($this->options->logoSpaceHeight - 2) * $this->options->scale);
        $ql = ($this->matrix->getSize() * $this->options->scale);
        imagecopyresampled($this->image, $im, (($ql - $lw) / 2), (($ql - $lh) / 2), 0, 0, $lw, $lh, $w, $h);
        $imageData = $this->dumpImage();
        $this->saveToFile($imageData, $file);
        if ($this->options->outputBase64) {
            $imageData = $this->toBase64DataURI($imageData);
        }
        return $imageData;
    }
}

function customBase64Encode($data) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $base = 64;
    $result = '';
    while ($data > 0) {
        $remainder = $data % $base;
        $result = $characters[$remainder] . $result;
        $data = floor($data / $base);
    }
    return $result;
}

function customBase64Decode($data) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $base = 64;
    $length = strlen($data);
    $result = 0;
    for ($i = 0; $i < $length; $i++) {
        $result = $result * $base + strpos($characters, $data[$i]);
    }
    return $result;
}

function encodeMessage($message) {
    list($part1, $part2) = explode('-', $message);
    return customBase64Encode($part1 . str_pad($part2, 5, '0', STR_PAD_LEFT));
}

function decodeMessage($encodedMessage) {
    $decodedNumber = customBase64Decode($encodedMessage);
    $decodedString = str_pad((string)$decodedNumber, 8, '0', STR_PAD_LEFT);
    return substr($decodedString, 0, -5). '-' . intval(substr($decodedString, -5));
}

function generateQRPdf($codeParameter) {
    $options = new QROptions;
    $options->version = 5;
    $options->outputBase64 = false;
    $options->scale = 6;
    $options->imageTransparent = false;
    $options->drawCircularModules = true;
    $options->circleRadius = 0.45;
    $options->keepAsSquare = [
        QRMatrix::M_FINDER,
        QRMatrix::M_FINDER_DOT,
    ];
    $options->eccLevel = EccLevel::H;
    $options->addLogoSpace = true;
    $options->logoSpaceWidth = 13;
    $options->logoSpaceHeight = 13;
    $qrcode = new QRCode($options);
    $message = encodeMessage($codeParameter);

    $qrcode->addByteSegment("https://iwa.web.tr/c/$message");
    $qrOutputInterface = new QRImageWithLogo($options, $qrcode->getQRMatrix());
    $qrCodeImage = $qrOutputInterface->dump(null, __DIR__ . '/iwapim.png');
    file_put_contents("$message.png", $qrCodeImage);

    function removeTRChars($str) {
        return str_replace(['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'I', 'g', 'G', 'u', 'U', 's', 'S', 'o', 'O', 'c', 'C'], $str);    
    }

    $pdf = new Fpdi('P', 'mm', [60, 40]);
    $pdf->AddPage();
    $pdf->SetMargins(1, 1, 0);
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetXY(0, 2);
    $pdf->Cell(0, 7, $codeParameter, 0, 0, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(12, 11);
    $text = "iwa Concept\n";
    $text .= date('Y-m');
    $pdf->MultiCell(30, 5, removeTRChars($text), 0, 'L');

    $pdf->Image("$message.png", 0, 20, 40, 40);
    $pdf->Image('iwa_black.png', 3, 11, 8, 8);

    $pdf->Output('D', "$codeParameter.pdf");

    unlink("$message.png");
}

if (isset($_GET['code'])) {
    generateQRPdf($_POST['code']);
} else {
    echo '<form method="get" action="test1.php"><input type="input" name="code"><input type="submit" value="Generate"></form>';
}

