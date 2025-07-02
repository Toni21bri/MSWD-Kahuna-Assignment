<?php
namespace com\icemalta\kahuna\model;

use \JsonSerializable;
use \PDO;
use com\icemalta\kahuna\model\DBConnect;

class User implements JsonSerializable
{

    private static $db;
    private int $id;
    private string $email;
    private string $password;
    private string $createdAt;

    private string $role;
   

    public function __construct(string $email, string $password, string $createdAt, ?string $role = 'user', ?int $id = 0)
    {
        $this->email = $email;
        $this->password = $password;
        $this->createdAt = $createdAt ?? date ('Y-m-d');
        $this->role = $role;
        $this->id = $id;
        self::$db = DBConnect::getInstance()->getConnection();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'accessLevel' => $this->role,
            'createdAt' => $this->createdAt
        ];
    }

    public static function save(User $user): User
    {
        $hashed = password_hash($user->password, PASSWORD_DEFAULT);

        if ($user->getId() === 0) {
            //Insert with ON DUPLICATE KEY UPDATE
            $sql = <<<SQL
                INSERT INTO User(email, password, role, createdAt) VALUES (:email, :password, :role, :createdAt)
                ON DUPLICATE KEY UPDATE
                    password = :password_update,
                    role = :role_update,
                    createdAt = :createdAt_update
            SQL;

            $sth = self::$db->prepare($sql);

            $sth->bindValue(':email', $user->getEmail());
            $sth->bindValue(':password', $hashed);
            $sth->bindValue(':role', $user->getRole());
            $sth->bindValue(':createdAt', $user->getCreatedAt());

            // Bind update parameters separately (must be unique names)
            $sth->bindValue(':password_update', $hashed);
            $sth->bindValue(':role_update', $user->getRole());
            $sth->bindValue(':createdAt_update', $user->getCreatedAt());

        $sth->execute();

        if ($sth->rowCount() > 0) {
            $user->setId((int) self::$db->lastInsertId());
        }

    } else {
        // Update
        $sql = <<<SQL
            UPDATE User SET email = :email, password = :password, role = :role, createdAt = :createdAt WHERE id = :id
        SQL;

        $sth = self::$db->prepare($sql);
        }

        $sth->bindValue('email', $user->getEmail());
        $sth->bindValue('password', $hashed);
        $sth->bindValue('role', $user->getRole());
        $sth->bindValue('createdAt', $user->getCreatedAt());
        $sth->execute();

        if ($sth->rowCount() > 0 && $user->getId() === 0) {
            $user->setId(self::$db->lastInsertId());
        }

        return $user;
    }

    public static function authenticate(User $user): ?User
    {
        $sql = 'SELECT * FROM User WHERE email = :email';
        $sth = self::$db->prepare($sql);
        $sth->bindValue('email', $user->getEmail());
        $sth->execute();

        $result = $sth->fetch(PDO::FETCH_OBJ);

        if ($result && password_verify($user->getPassword(), $result->password)) {
            return new User(
                $result->email,
                 $result->password,        // '', // Don't pass hash back in  (maybe)
                $result->createdAt,
                $result->role,
                $result->id
            );
        }

        return null;
    }

    public static function delete(User $user): bool
        {
            $sql = 'DELETE FROM User WHERE id = :id';
            $sth = self::$db->prepare($sql);
            $sth->bindValue('id', $user->getId());
            $sth->execute();
            return $sth->rowCount() > 0;
        }
            

     

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}