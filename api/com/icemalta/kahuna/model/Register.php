<?php
namespace com\icemalta\kahuna\model;
require_once 'com/icemalta/kahuna/model/DBConnect.php';

use \PDO;
use JsonSerializable;
use com\icemalta\kahuna\model\DBConnect;


class Register implements JsonSerializable
{
    private static PDO $db;

    private int $id = 0;
    private int $userId;
    private string $serial;  // YY-MM-DD
    private string $purchaseDate;  // timestamp string, defaults to CURRENT_TIMESTAMP on insert

    private string $retailer;
    private string $warrantyExpires;
    private int $productId;


    public function __construct(
        int $userId,
        string $serial,
        string $purchaseDate,
        string $retailer = '',
        int $id = 0,
        string $warrantyExpires = ''
        
    ) {
        self::$db = DBConnect::getInstance()->getConnection();
        $this->userId = $userId;
        $this->serial = $serial;
        $this->purchaseDate = $purchaseDate;
        $this->retailer = $retailer;
        $this->id = $id;

        $product = self::getProductBySerial($serial);
        if (!$product) {
            throw new \Exception("Product with serial $serial not found.");
        }

        $this->productId = $product->getId();

        // Calculate warranty expiry date if not provided
        if (!$warrantyExpires) {
            $dtPurchase = new \DateTime($purchaseDate);
            $warrantyLengthMonths = $product->getWarrantyLength();
            $dtPurchase->modify("+{$warrantyLengthMonths} months");
            $this->warrantyExpires = $dtPurchase->format('Y-m-d');
        } else {
            $this->warrantyExpires = $warrantyExpires;
        }
    }

    private static function userExists(int $userId): bool
    {
        self::$db = DBConnect::getInstance()->getConnection();
        $sth = self::$db->prepare("SELECT 1 FROM User WHERE id = ?");
        $sth->execute([$userId]);
        return (bool) $sth->fetchColumn();
    }


    public static function register(Register $reg): Register
    {
        // ✅ Ensure user exists before proceeding
        if (!self::userExists($reg->userId)) {
            throw new \Exception("User with ID {$reg->userId} does not exist.");
        }

    // Check for duplicate registration
    $sql = "SELECT 1 FROM Register WHERE userId = :userId AND serial = :serial";
    $sth = self::$db->prepare($sql);
    $sth->execute(['userId' => $reg->userId, 'serial' => $reg->serial]);
    if ($sth->fetchColumn()) {
        throw new \Exception("This product is already registered by the user.");
    }




        // Insert new record
        $sql = <<<SQL
        INSERT INTO Register (userId, productId, serial, purchaseDate, retailer, warrantyExpires)
        VALUES (:userId, :productId, :serial, :purchaseDate, :retailer, :warrantyExpires)
        SQL;
        $sth = self::$db->prepare($sql);
        $sth->execute([
            'userId' => $reg->userId,
            'productId' => $reg->productId,
            'serial' => $reg->serial,
            'purchaseDate' => $reg->purchaseDate,
            'retailer' => $reg->retailer,
            'warrantyExpires' => $reg->warrantyExpires
        ]);

        $reg->id = self::$db->lastInsertId();
        return $reg;
    }

    public static function getProductBySerial(string $serial): ?Product
    {
        $sql = "SELECT serial, name, warrantyLength, id FROM Product WHERE serial = ?";
        $sth = self::$db->prepare($sql);
        $sth->execute([$serial]);
        $data = $sth->fetch(PDO::FETCH_NUM);
        return $data ? new Product(...$data) : null;
    }



    public function jsonSerialize(): array
    {
        return get_object_vars($this); // Return everything (Except $db)
    }
       
    

    // Load all registrations
public static function loadAll(): array
{
    self::$db = DBConnect::getInstance()->getConnection();
    $sql = "SELECT id, userId, productId, serial, purchaseDate, retailer, warrantyExpires FROM Register";
    $sth = self::$db->prepare($sql);
    $sth->execute();
    return $sth->fetchAll(PDO::FETCH_FUNC, function($id, $userId, $productId, $serial, $purchaseDate, $retailer, $warrantyExpires) {
        return new Register($userId, $serial, $purchaseDate, $retailer, $id, $warrantyExpires);
    });
}

// Load registrations by user ID
public static function loadByUser(int $userId): array
{
    self::$db = DBConnect::getInstance()->getConnection();
    $sql = "SELECT id, userId, productId, serial, purchaseDate, retailer, warrantyExpires FROM Register WHERE userId = :userId";
    $sth = self::$db->prepare($sql);
    $sth->execute(['userId' => $userId]);
    return $sth->fetchAll(PDO::FETCH_FUNC, function($id, $userId, $productId, $serial, $purchaseDate, $retailer, $warrantyExpires) {
        return new Register($userId, $serial, $purchaseDate, $retailer, $id, $warrantyExpires);
    });
}

public static function getByUser(int $userId): array {
    $db = DBConnect::getInstance()->getConnection();
    $sth = $db->prepare("
        SELECT rp.serial, p.name, rp.purchaseDate, rp.retailer, 
               p.warrantyLength,
               TIMESTAMPDIFF(YEAR, rp.purchaseDate, CURDATE()) AS yearsSincePurchase
        FROM registered_products rp
        JOIN products p ON rp.serial = p.serial
        WHERE rp.user_id = ?
    ");
    $sth->execute([$userId]);
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Calculate remaining warranty
    foreach ($rows as &$row) {
        $remaining = $row['warrantyLength'] - $row['yearsSincePurchase'];
        $row['warrantyRemaining'] = max($remaining, 0);
        unset($row['warrantyLength'], $row['yearsSincePurchase']);
    }

    return $rows;
}

public static function getProductByUserId(int $userId): array {
    return self::getByUser($userId);
}

public static function getProductByUserAndSerial(int $userId, string $serial): ?array {
    $db = DBConnect::getInstance()->getConnection();
    $sth = $db->prepare("
        SELECT rp.serial, p.name AS product_name, rp.purchaseDate, rp.retailer,
               p.warrantyLength,
               TIMESTAMPDIFF(MONTH, rp.purchaseDate, CURDATE()) AS monthsSincePurchase
        FROM Register rp
        JOIN Product p ON rp.serial = p.serial
        WHERE rp.userId = :userId AND rp.serial = :serial
        LIMIT 1
    ");
    $sth->execute(['userId' => $userId, 'serial' => $serial]);
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $warrantyLeft = max(0, $row['warrantyLength'] - $row['monthsSincePurchase']);
    unset($row['warrantyLength'], $row['monthsSincePurchase']);
    $row['warrantyLeftMonths'] = $warrantyLeft;
    return $row;
}


    // Getters (add setters if needed)
    public function getId(): int 
    { 
        return $this->id; 
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): int 
    {
         return $this->userId; 
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
    public function getSerial(): string 
    {
         return $this->serial; 
    }

    public function setSerial(string $serial): self
    {
        $this->serial = $serial;
        return $this;
    }


    public function getRetailer(): string 
    {
         return $this->retailer; 
    }

    public function setRetailer(string $retailer): self
    {
        $this->retailer = $retailer;
        return $this;
    }

    public function getPurchaseDate(): string 
    {
         return $this->purchaseDate; 
    }

     public function setPurchaseDate(string $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;
        return $this;
    }


    public function getWarrantyExpires(): string 
    {
         return $this->warrantyExpires; 
    }
    
    public function setWarrantyExpires(string $warrantyExpires): self
    {
        $this->warrantyExpires = $warrantyExpires;
        return $this;
    }

}


 