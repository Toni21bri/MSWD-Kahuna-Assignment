<?php
namespace com\icemalta\kahuna\model;

use \PDO;

class DBConnect
{
    private static $singleton = null;
    private $dbh;

    private function __construct()
    {
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/.env');
        $this->dbh = new PDO(
            "mysql:host=mariadb;dbname=kahuna",
            $env['DB_USER'],
            $env['DB_PASS'],
            array(PDO::ATTR_PERSISTENT => true)
        );
    }

    public static function getInstance()
    {
        self::$singleton = self::$singleton ?? new DBConnect();
        return self::$singleton;
    }

    public function getConnection()
    {
        return $this->dbh;
    }

    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
        
    }
}