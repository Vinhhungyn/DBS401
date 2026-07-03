-- ============================================================
-- init_patched.sql - Khoi tao DB cho bên PATCHED (port 5001)
-- FIX: password duoc luu dang bcrypt hash (PASSWORD_BCRYPT)
-- Tach rieng khoi company_db cua bên vulnerable
-- ============================================================

USE company_db_patched;

-- ============================================================
-- 1. TAO BANG DU LIEU
-- ============================================================

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    -- FIX: password luu bcrypt hash ($2y$10$...), khong con plaintext
    password VARCHAR(255),
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
-- 2. NHAP DU LIEU MAU - PASSWORD DA BCRYPT HASH
-- FIX: dung password_hash($plain, PASSWORD_BCRYPT) de tao hash
-- admin123   -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- alice456   -> $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm
-- bob789     -> $2y$10$yQXNMhiDVFPCv0E7nSQ4.OvWFMiGgGqT3FXsAZ.5F5E3bGl3.xMme
-- charlie000 -> $2y$10$N0/j5YtMKUrjrR.YXHM9/e9f.P8pBBGZLVJ5GR1hZMVuEcRk6sJmm
-- ============================================================

INSERT INTO employees (username, password, role, salary, email) VALUES
('admin',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',   9000000, 'admin@company.vn'),
('alice',   '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user',    5000000, 'alice@company.vn'),
('bob',     '$2y$10$yQXNMhiDVFPCv0E7nSQ4.OvWFMiGgGqT3FXsAZ.5F5E3bGl3.xMme', 'user',    4500000, 'bob@company.vn'),
('charlie', '$2y$10$N0/j5YtMKUrjrR.YXHM9/e9f.P8pBBGZLVJ5GR1hZMVuEcRk6sJmm', 'manager', 7000000, 'charlie@company.vn');

INSERT INTO transactions (from_user, to_user, amount) VALUES
('alice',   'bob',     500000),
('charlie', 'alice',  1200000),
('bob',     'charlie', 300000);

INSERT INTO secret_data (label, content) VALUES
('API Key',        'sk-prod-a1b2c3d4e5f6'),
('Server Config',  'DB_HOST=192.168.1.10 DB_PASS=supersecret'),
('Internal Notes', 'Backup every Sunday 2AM. Admin PIN: 9981');

-- ============================================================
-- 3. TAO USER DB - QUYEN GIOI HAN (da patch privilege escalation)
-- FIX: chi cap SELECT/INSERT/UPDATE/DELETE, khong GRANT/FILE
-- ============================================================
CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED WITH mysql_native_password BY 'app123';
GRANT SELECT, INSERT, UPDATE, DELETE ON company_db_patched.* TO 'app_user'@'%';

FLUSH PRIVILEGES;
