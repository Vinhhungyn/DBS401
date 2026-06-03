-- ============================================================
-- init.sql - Khoi tao database co lo hong co y
-- Muc dich: Phuc vu demo tan cong DBS401
-- ============================================================

USE company_db;

-- ============================================================
-- 1. TAO BANG DU LIEU MAU
-- ============================================================

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(100),       -- lo hong: luu plain text, khong hash
    role VARCHAR(20),
    salary DECIMAL(10,2),
    email VARCHAR(100)
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user VARCHAR(50),
    to_user VARCHAR(50),
    amount DECIMAL(10,2),
    created_at DATETIME DEFAULT NOW()
);

CREATE TABLE secret_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100),
    content TEXT
);

-- ============================================================
-- 2. NHAP DU LIEU MAU
-- ============================================================

INSERT INTO employees (username, password, role, salary, email) VALUES
('admin',     'admin123',   'admin',   9000000, 'admin@company.vn'),
('alice',     'alice456',   'user',    5000000, 'alice@company.vn'),
('bob',       'bob789',     'user',    4500000, 'bob@company.vn'),
('charlie',   'charlie000', 'manager', 7000000, 'charlie@company.vn');

INSERT INTO transactions (from_user, to_user, amount) VALUES
('alice',   'bob',     500000),
('charlie', 'alice',  1200000),
('bob',     'charlie', 300000);

INSERT INTO secret_data (label, content) VALUES
('API Key',        'sk-prod-a1b2c3d4e5f6'),
('Server Config',  'DB_HOST=192.168.1.10 DB_PASS=supersecret'),
('Internal Notes', 'Backup every Sunday 2AM. Admin PIN: 9981');

-- ============================================================
-- 3. TAO USER CO LO HONG - QUYEN QUA CAO (co y)
-- ===================================================
-- app_user: quyen FILE va GRANT - lo hong Privilege Escalation
CREATE USER 'app_user'@'%' IDENTIFIED BY 'app123';
GRANT ALL PRIVILEGES ON *.* TO 'app_user'@'%' WITH GRANT OPTION;

-- readonly_user: dung de demo SQLi (khong gioi han query)
CREATE USER 'readonly_user'@'%' IDENTIFIED BY 'read123';
GRANT SELECT ON company_db.* TO 'readonly_user'@'%';

FLUSH PRIVILEGES;

-- ============================================================
-- 4. STORED PROCEDURE BI LOI HONG SQLi (khong dung prepared stmt)
-- ============================================================

DELIMITER //
CREATE PROCEDURE search_employee(IN search_name VARCHAR(100))
BEGIN
    -- lo hong: noi truc tiep chuoi vao query, khong sanitize
    SET @sql = CONCAT('SELECT id, username, role, email FROM employees WHERE username = ''', search_name, '''');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //
DELIMITER ;
-- Fix authentication method cho ProxySQL tuong thich
ALTER USER 'app_user'@'%' IDENTIFIED WITH mysql_native_password BY 'app123';
ALTER USER 'readonly_user'@'%' IDENTIFIED WITH mysql_native_password BY 'read123';
CREATE USER IF NOT EXISTS 'proxysql_monitor'@'%' IDENTIFIED WITH mysql_native_password BY 'monitor123';
GRANT USAGE ON *.* TO 'proxysql_monitor'@'%';
FLUSH PRIVILEGES;