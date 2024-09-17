<?php

require '../vendor/autoload.php'; 

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRCodeOutputException;

error_reporting(E_ALL);
ini_set('display_errors', 1);


class QRImageWithLogo extends QRGdImagePNG{

    public function dump(string|null $file = null, string|null $logo = null): string {
        $this->options->returnResource = true;
        if ($logo !== null) {
            if (!is_file($logo) || !is_readable($logo)) {
                throw new QRCodeOutputException('Invalid logo');
            }
        }
        parent::dump($file);
        if ($logo !== null) {
            $im = imagecreatefrompng($logo);
            if ($im === false) {
                throw new QRCodeOutputException('imagecreatefrompng() error');
            }
            $w = imagesx($im);
            $h = imagesy($im);
            $lw = ($this->options->logoSpaceWidth - 2) * $this->options->scale;
            $lh = ($this->options->logoSpaceHeight - 2) * $this->options->scale;
            $ql = $this->matrix->getSize() * $this->options->scale;
            imagecopyresampled($this->image, $im, ($ql - $lw) / 2, ($ql - $lh) / 2, 0, 0, $lw, $lh, $w, $h);
            imagedestroy($im);
        }
        $imageData = $this->dumpImage();
        $this->saveToFile($imageData, $file);
        if ($this->options->outputBase64) {
            $imageData = $this->toBase64DataURI($imageData);
        }
        return $imageData;
    }
}   

class QrModel{
    private $db;

    public function __construct() {
        $this->db = $GLOBALS['pdo'];
    }

    private function findByField($field, $value){
        $sql = "SELECT * FROM qr_records WHERE $field = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLinkByUniqueCode($uniqueCode) {
        $stmt = $this->db->prepare('SELECT link FROM qr_records WHERE unique_code = ?');
        $stmt->execute([$uniqueCode]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['link'];
        }
        return false;
    }

    public function getQRCodeBase64($uniqueCode) {
        $sql = "SELECT qr_code FROM qr_records WHERE unique_code = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Sorgu hazırlama hatası: ' . $this->db->error);
        }
        if (!$stmt->execute([$uniqueCode])) {
            throw new Exception("Sorgu çalıştırma hatası: {$stmt->error}");
        }
        if ($base64Image = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $base64Image['qr_code'];
        }
        return null;
    }

    public function generateUniqueCode($numberDigits=5){ 
        $candidateCode = self::generateCustomString($numberDigits);
        while (true) {
            $candidateCode = self::generateCustomString($numberDigits);
            if (!$this->findByField('unique_code',$candidateCode)) {
                return $candidateCode;
            }
        }
    }

    public static function generateCustomString($length = 5) {
        $characters = 'ABCDEFGHJKMNPQRSTVWXYZ1234567890';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = mt_rand(0, $charactersLength - 1);
            $randomString .= $characters[$randomIndex];
        }
        return $randomString;
    }

    // public function createQRCodeWithLogo($qrlink, $qrImagePath, $logoPath=null){
    //     $options = new QROptions;
    //     $options->version = 6;
    //     $options->outputBase64 = false;
    //     $options->scale = 50;
    //     $options->imageTransparent = false;
    //     $options->drawCircularModules = true;
    //     $options->circleRadius = 0.45;
    //     $options->keepAsSquare = [
    //         QRMatrix::M_FINDER,
    //         QRMatrix::M_FINDER_DOT,
    //     ];
    //     $options->eccLevel = EccLevel::H;
    //     $options->addLogoSpace = $logoPath !== null;
    //     $options->logoSpaceWidth = 13;
    //     $options->logoSpaceHeight = 13;
    //     $qrCode = new QRCode($options);
    //     $qrCode->addByteSegment($qrlink);
    //     $qrOutputInterface = new QRImageWithLogo($options, $qrCode->getQRMatrix());

    //     if ($logoPath && file_exists($logoPath)) {
    //         $qrOutputInterface->dump($qrImagePath, $logoPath);
    //     } else {
    //         $qrOutputInterface->dump($qrImagePath);
    //     }
    //     return $qrImagePath;
    // }


    public function createQRCodeWithLogo($qrlink, $logoPath = null) {
        $options = new QROptions;
        $options->version = 5;
        $options->outputBase64 = true;
        $options->scale = 50;
        $options->imageTransparent = false;
        $options->drawCircularModules = false;
        $options->circleRadius = 0.5;
        $options->keepAsSquare = [
            QRMatrix::M_FINDER,
            QRMatrix::M_FINDER_DOT,
        ];
        
        
        $options->eccLevel = EccLevel::L;
        $options->addLogoSpace = $logoPath !== null;
        $options->logoSpaceWidth = 13;
        $options->logoSpaceHeight = 13;
        $qrCode = new QRCode($options);
        $qrCode->addByteSegment($qrlink);
        $qrOutputInterface = new QRImageWithLogo($options, $qrCode->getQRMatrix());
        try {
            $base64Image = $qrOutputInterface->dump(null, $logoPath);
        } catch (Exception $e) {
            echo 'QR kodu oluşturulurken bir hata oluştu: ' . $e->getMessage();
            $base64Image = '';
        }

        return $base64Image;
    }
    public function getTotalRecords($search = null) {
        $sql = "SELECT COUNT(*) as total FROM qr_records WHERE deleted_at IS NULL";
        if ($search) {
            $sql .= " AND link LIKE ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetch(PDO::FETCH_COLUMN) ?? 0;
    }

    public function getRecords($start, $limit, $search = null) {
        $sql = "SELECT * FROM qr_records WHERE deleted_at IS NULL";
        if ($search) {
            $sql .= " AND link LIKE :search";
        }
        
        $sql .= " LIMIT :start, :limit";
        $stmt = $this->db->prepare($sql);
        
        if ($search) {
            $searchTerm = "%$search%";
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateQRCode($uniqueCode, $description, $link){
        $sql = "UPDATE qr_records SET description = ?, link = ?, user_name = ? WHERE unique_code = ?";
        $stmt = $this->db->prepare($sql);
        $userName = $_SESSION['user_info']['name'] ?? '';
        if ($stmt) {
            return $stmt->execute([$description, $link, $userName, $uniqueCode]);
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
    }

    public function deleteQRCode($uniqueCode){
        $sql = "UPDATE qr_records SET deleted_at = NOW(), user_name = ? WHERE unique_code = ?";
        $stmt = $this->db->prepare($sql);
        $userName = $_SESSION['user_info']['name'] ?? '';

        if ($stmt) {
            return $stmt->execute([$userName, $uniqueCode]);
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
    }
    public function getQRCodeByUniqueCode($uniqueCode){
        $sql = "SELECT * FROM qr_records WHERE unique_code = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            if ($stmt->execute([$uniqueCode])) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            echo "Prepare failed: " . $this->db->error;
        }
        return null;
    }
    public function saveQrCode($uniqueCode, $qrImagePath, $description, $link, $userName = ""){
        $userName = $_SESSION['user_info']['name'] ?? '';
        $sql = "INSERT INTO qr_records (unique_code, qr_code, description, link, user_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            return $stmt->execute([$uniqueCode, $qrImagePath, $description, $link, $userName]);
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
    }

}
