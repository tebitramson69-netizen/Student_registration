-- Student Registration - database schema
--
--   mysql -u root -p < schema.sql
--
-- Creating the first admin account is a separate step, see create_admin.php.

CREATE DATABASE IF NOT EXISTS student_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE student_db;

CREATE TABLE IF NOT EXISTS students (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    telephone  VARCHAR(30)  NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- The listing sorts and searches on the name columns.
    INDEX idx_students_first_name (first_name),
    INDEX idx_students_last_name (last_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL,
    -- bcrypt output is 60 characters today; 255 leaves room for whatever
    -- PASSWORD_DEFAULT becomes in a later PHP version.
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_admins_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50) NOT NULL,
    -- 45 characters holds an IPv6 address in full.
    ip_address   VARCHAR(45) NOT NULL,
    attempted_at DATETIME    NOT NULL,
    INDEX idx_login_attempts_lookup (username, ip_address, attempted_at)
) ENGINE=InnoDB;
