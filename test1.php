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
$message = urlencode('31-1234');
// find a way to encode and decode the message

$qrcode->addByteSegment("https://iwa.web.tr/c/$message");
$qrOutputInterface = new QRImageWithLogo($options, $qrcode->getQRMatrix());
$qrCodeImage = $qrOutputInterface->dump(null, __DIR__ . '/iwapim.png');
file_put_contents('qrcode.png', $qrCodeImage);

function removeTRChars($str) {
    return str_replace(['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'I', 'g', 'G', 'u', 'U', 's', 'S', 'o', 'O', 'c', 'C'], $str);    
}

$pdf = new Fpdi('P', 'mm', [60, 40]);
$pdf->AddPage();
$pdf->SetMargins(1, 1, 0);
$pdf->SetFont('Arial', 'B', 24);
$pdf->SetXY(0, 2);
$pdf->Cell(0, 7, '31-1234', 0, 0, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(10, 11);
$text = "iwa Concept\n";
$text .= date('Y-m');
$pdf->MultiCell(30, 5, removeTRChars($text), 0, 'L');

$pdf->Image('qrcode.png', 0, 20, 40, 40);
$pdf->Image('iwa_black.png', 3, 11, 8, 8);

$pdf->Output('I', 'qrcode_label.pdf');

unlink('qrcode.png');
