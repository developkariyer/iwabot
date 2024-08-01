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
$qrcode->addByteSegment('https://iwa.web.tr/C1A43BDE3');
$qrOutputInterface = new QRImageWithLogo($options, $qrcode->getQRMatrix());
$qrCodeImage = $qrOutputInterface->dump(null, __DIR__ . '/iwa_black.png');
file_put_contents('qrcode.png', $qrCodeImage);

// Create instance of FPDF
// page will be 60mm x 40mm
$pdf = new Fpdi('P', 'mm', [60, 40]);
$pdf->AddPage();
$pdf->SetMargins(1, 1, 0);

$pdf->AddFont('DejaVuSans', '', 'DejaVuSans.ttf');
$pdf->SetFont('DejaVuSans', '', 24);

// Set font for the big text
//$pdf->SetFont('Arial', 'B', 24);
$pdf->SetXY(0, 1);
$pdf->Cell(0, 7, '31-1234', 0, 0, 'C');

// Set font for the small text
$pdf->SetFont('Arial', '', 4);
$pdf->SetXY(0, 8);
$text = "MAŞALLAH TEBAREKALLAH GOLD 69 CM (B0CD1WN9BZ) x 3\nMAŞALLAH TEBAREKALLAH GOLD 69 CM (B0CD1WN9BZ) x 3\nMAŞALLAH TEBAREKALLAH GOLD 69 CM (B0CD1WN9BZ) x 3\nMAŞALLAH TEBAREKALLAH GOLD 69 CM (B0CD1WN9BZ) x 3\nMAŞALLAH TEBAREKALLAH GOLD 69 CM (B0CD1WN9BZ) x 3";
$pdf->MultiCell(40, 1.5, $text, 0, 'C');

$pdf->SetXY(0, 30);
$pdf->Image('qrcode.png', 5, 30, 30, 30);

// Output the PDF
$pdf->Output('I', 'qrcode_label.pdf');

// Clean up
unlink('qrcode.png');
