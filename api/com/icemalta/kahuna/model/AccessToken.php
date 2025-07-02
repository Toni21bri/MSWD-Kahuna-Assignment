<?php
namespace com\icemalta\kahuna\model;

use \PDO;
use \JsonSerializable;
use com\icemalta\kahuna\model\DBConnect;

class AccessToken implements JsonSerializable
{

    private static $db;
    private int $userId;
    private string $token;

    public function __construct(int $userId, ?string $token = null)
    {
        $this->userId = $userId;
        $this->token = $token ?? str_replace("=", "", base64_encode(random_bytes(160 / 8)));
        self::$db = DBConnect::getInstance()->getConnection();
    }

    /**
     * Save a new token to the database 
     * @param \com\icemalta\kahuna\model\AccessToken $accessToken the token to save 
     * @return AccessToken the updated token 
     */
    public static function save(AccessToken $accessToken): AccessToken
    {
        $sql = <<<SQL
            INSERT INTO AccessToken(userId, token) VALUES (:userId, :token)
        SQL;
        $sth = self::$db->prepare($sql);
        $sth->bindValue('userId', $accessToken->getUserId());
        $sth->bindValue('token', $accessToken->getToken());
        $sth->execute();
        return $accessToken;
    } 

    public static function verify(AccessToken $accessToken): bool
    {
        $sql = <<<SQL
            SELECT * FROM AccessToken WHERE userId = :userId AND token = :token
        SQL;
        $sth = self::$db->prepare($sql);
        $sth->bindValue('userId', $accessToken->getUserId());
        $sth->bindValue('token', $accessToken->getToken());
        $sth->execute();
        $token = $sth->fetch(PDO::FETCH_OBJ);

        if ($token) {
            $birth = strtotime($token->createdAt);
            $age = abs(strtotime('now') - $birth);
            if ($age < 3600) {
                return true;
            }
        }
        return false;
    }

    public static function delete(AccessToken $accessToken): bool
    {
        $sql = <<<SQL
            DELETE FROM AccessToken WHERE userId = :userId
        SQL;
        $sth = self::$db->prepare($sql);
        $sth->bindValue('userId', $accessToken->getUserId());
        $sth->execute();
        return $sth->rowCount() > 0;
    }

    public function jsonSerialize(): array
    {
        return [ 
            'token' => $this->token
        ];
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

    public function getToken(): string
    {
        return $this->token;
    }
}