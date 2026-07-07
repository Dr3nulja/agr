-- Database creation script for AGR system
CREATE DATABASE IF NOT EXISTS agr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agr;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    login VARCHAR(255) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    role TINYINT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create objects table
CREATE TABLE IF NOT EXISTS objects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(255) NOT NULL,
    City VARCHAR(255) NULL,
    GSMNR VARCHAR(255) NULL,
    IMEI VARCHAR(255) NULL UNIQUE,
    IMEI2 VARCHAR(255) NULL,
    Contact VARCHAR(255) NULL,
    Description TEXT NULL,
    Description2 TEXT NULL,
    Company INT DEFAULT 0,
    dtype INT DEFAULT 0,
    status INT DEFAULT 1,
    Devqtty INT DEFAULT 0,
    RadioDevQty INT DEFAULT 0,
    MainRadio INT DEFAULT 0,
    GSMSERIAL VARCHAR(255) NULL,
    GSMSERIAL2 VARCHAR(255) NULL,
    pin1 VARCHAR(255) NULL,
    pin2 VARCHAR(255) NULL,
    puk1 VARCHAR(255) NULL,
    puk2 VARCHAR(255) NULL,
    KeyCode VARCHAR(255) NULL,
    manager INT DEFAULT 0,
    packet VARCHAR(255) NULL,
    traffic VARCHAR(255) NULL,
    callCnt INT DEFAULT 0,
    summ DECIMAL(10, 2) DEFAULT 0,
    ver INT DEFAULT 0,
    lastSession DATETIME NULL,
    selDate DATETIME NULL,
    lat DECIMAL(10, 8) NULL,
    lon DECIMAL(11, 8) NULL,
    saveHval BOOLEAN DEFAULT FALSE,
    m2_andur INT DEFAULT 0,
    dataToPage BOOLEAN DEFAULT FALSE,
    AddFee DECIMAL(10, 2) DEFAULT 0,
    fee DECIMAL(10, 2) DEFAULT 0,
    kuluM2 DECIMAL(10, 2) DEFAULT 0,
    AlgLopp INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_address (address),
    INDEX idx_company (Company),
    INDEX idx_dtype (dtype),
    INDEX idx_status (status),
    INDEX idx_manager (manager),
    INDEX idx_company_dtype_status (Company, dtype, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create objects_install_data table
CREATE TABLE IF NOT EXISTS objects_install_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    oid BIGINT UNSIGNED NOT NULL,
    devid INT NOT NULL,
    location VARCHAR(255) NULL,
    devtype INT DEFAULT 0,
    dnType INT DEFAULT 0,
    len INT DEFAULT 0,
    foto1 INT DEFAULT 0,
    foto2 INT DEFAULT 0,
    place1 VARCHAR(255) NULL,
    place2 VARCHAR(255) NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_oid (oid),
    INDEX idx_devid (devid),
    FOREIGN KEY (oid) REFERENCES objects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create log table
CREATE TABLE IF NOT EXISTS log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Content TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create csq table (GSM signal quality)
CREATE TABLE IF NOT EXISTS csq (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object BIGINT UNSIGNED NOT NULL,
    csq INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_object (object),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (object) REFERENCES objects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create migrations table (Laravel internals)
CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
