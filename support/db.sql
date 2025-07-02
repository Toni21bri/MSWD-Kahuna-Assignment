CREATE DATABASE IF NOT EXISTS kahuna;

USE kahuna;

-- Create Table Product
CREATE TABLE IF NOT EXISTS Product(
    id              INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    serial          VARCHAR(20) NOT NULL,
    name            VARCHAR(100) NOT NULL,
    warrantyLength  INT(20) NOT NULL COMMENT 'Warranty period in months'
);

-- Insert Item in Table Product
INSERT INTO Product (serial, name, warrantyLength)
VALUES 
('KHWM8199911', 'CombiSpin Washing Machine', 2),
('KHWM8199912', 'CombiSpin + Dry Washing Machine', 2),
('KHMW789991', 'CombiGrill Microwave', 1),
('KHWP890001', 'K5 Water Pump', 5),
('KHWP890002', 'K5 Heated Water Pump', 5),
('KHSS988881', 'Smart Switch Lite', 2),
('KHSS988882', 'Smart Switch Pro', 2),
('KHSS988883', 'Smart Switch Pro V2', 2),
('KHHM89762', 'Smart Heated Mug', 1),
('KHSB0001', 'Smart Bulb 001', 1);


-- Create Table User

CREATE TABLE User (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO User (email, password, role, createdAt)
VALUES (:email, :password, :role, :createdAt)
ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role);

-- Table: Register

CREATE TABLE Register (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    serial_number VARCHAR(255) NOT NULL,
    purchase_date DATE NOT NULL,
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    CONSTRAINT fk_product FOREIGN KEY (serial_number) REFERENCES Product(serial) ON DELETE CASCADE
);



-- Create Table AccessToken

CREATE TABLE AccessToken(
    id              INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    userId          INT NOT NULL,
    token           VARCHAR(255) NOT NULL,
    createdAt           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT c_accesstoken_user
        FOREIGN KEY(userId) REFERENCES User(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);





DROP Table Register;
DELETE FROM User WHERE id = 11;

SELECT * FROM User WHERE email = 'antoine21brincat@gmail.com';


CREATE TABLE Register (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    productId INT NOT NULL,
    serial VARCHAR(255) NOT NULL,
    purchaseDate DATE NOT NULL,
    retailer VARCHAR(255),
    warrantyExpires DATE,

    -- Enforce foreign key to User table
    CONSTRAINT fk_register_user FOREIGN KEY (userId)
        REFERENCES User(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Enforce foreign key to Product table
    CONSTRAINT fk_register_product FOREIGN KEY (productId)
        REFERENCES Product(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Ensure a user can't register the same serial twice
    UNIQUE KEY unique_user_product_serial (userId, serial)
);


