-- ============================================================
-- patch.sql - Va lo hong sau tan cong (Blue Team)
-- Chay: mysql -u root -proot123 -h 127.0.0.1 < patch.sql
-- ============================================================

USE company_db;

-- ============================================================
-- 1. VA LO HONG PHAN QUYEN
-- ============================================================

-- Thu hoi quyen FILE va GRANT cua app_user
REVOKE FILE ON *.* FROM 'app_user'@'%';
REVOKE GRANT OPTION ON *.* FROM 'app_user'@'%';

-- Gioi han app_user chi duoc thao tac tren company_db
REVOKE ALL PRIVILEGES ON *.* FROM 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON company_db.* TO 'app_user'@'%';

-- Doi mat khau manh hon
ALTER USER 'app_user'@'%' IDENTIFIED BY 'App@Str0ng#2024!';
ALTER USER 'readonly_user'@'%' IDENTIFIED BY 'Read@Only#2024!';
ALTER USER 'root'@'%' IDENTIFIED BY 'R00t@Str0ng#2024!';

FLUSH PRIVILEGES;

-- ============================================================
-- 2. XOA STORED PROCEDURE BI VULN, TAO LAI DUNG CACH
-- ============================================================

DROP PROCEDURE IF EXISTS search_employee;

DELIMITER //
CREATE PROCEDURE search_employee(IN search_name VARCHAR(100))
BEGIN
    -- Sau patch: dung prepared statement chong SQLi
    SELECT id, username, role, email
    FROM employees
    WHERE username = search_name;   -- truyen truc tiep, MySQL xu ly an toan
END //
DELIMITER ;

-- ============================================================
-- 3. HASH MAT KHAU (simulate - thuc te nen hash o app layer)
-- ============================================================

UPDATE employees SET password = SHA2(password, 256);

-- ============================================================
-- 4. KIEM TRA SAU PATCH
-- ============================================================

-- Xem lai quyen cua app_user (phai chi con SELECT/INSERT/UPDATE/DELETE tren company_db)
SHOW GRANTS FOR 'app_user'@'%';
