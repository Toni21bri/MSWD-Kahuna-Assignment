<?php
namespace com\icemalta\kahuna\model;


require 'com/icemalta/kahuna/model/DBConnect.php';

use \PDO;
use \JsonSerializable;
use com\icemalta\kahuna\model\DBConnect;

class Product implements JsonSerializable 
{
    public static $db;
    private int|string $id = 0;
    private string $serial;
    private string $name;

    private int $warrantyLength = 0;

    public function __construct(string $serial, string $name, int $warrantyLength, int|string $id = 0)
    {
        $this->serial = $serial;
        $this->name = $name;
        $this->warrantyLength = $warrantyLength;
        $this->id = $id;
        self::$db = DBConnect::getInstance()->getConnection();
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this); // Return everything (Except $db)
    }
    
    public static function save(Product $product): Product
    {
       if ($product->getId() === 0) {
        // New Product
        $sql = <<<SQL
        INSERT INTO Product(serial, name, warrantyLength)
        VALUES (:serial, :name, :warrantyLength)
        SQL;
        $sth = self::$db->prepare($sql);
       } else {
        // Update Product
        $sql = <<<SQL
        UPDATE Product SET serial = :serial, name = :name, warrantyLength = :warrantyLength
        WHERE id = :id
        SQL;
        $sth = self::$db->prepare($sql);
        $sth->bindValue('id', $product->getId());
       }
       $sth->bindValue('serial', $product->getSerial());
       $sth->bindValue('name', $product->getName());
       $sth->bindValue('warrantyLength', $product->getWarrantyLength());
       $sth->execute();
     

      if ($sth->rowCount() > 0 && $product->getId() === 0) {
            $product->setId(self::$db->lastInsertId());
        }

        return $product;
    }

    public static function load(): array
    {
        self::$db = DBConnect::getInstance()->getConnection(); // Ensure there is a connection to the DB
        $sql = "SELECT serial, name, warrantyLength, id FROM Product"; // Same order as constructor
        $sth = self::$db->prepare($sql);
        $sth->execute();
        $products = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new Product(...$fields));
        return $products;
    }

     public function getId(): int|string
    {
        return $this->id;
    }

    public function setId(int|string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getWarrantyLength(): int
    {
        return $this->warrantyLength;
    }

    public function setWarrantyLength(int $warrantyLength): self
    {
        $this->warrantyLength = $warrantyLength;
        return $this;
    }
    
}