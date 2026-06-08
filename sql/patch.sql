-- ============================================================
-- patch.sql - Va lo hong sau tan cong (Blue Team)
-- KHONG doi mat khau app_user, chi thu hoi quyen
-- ============================================================

USE company_db;

-- ============================================================
-- 1. VA LO HONG PHAN QUYEN (giu nguyen mat khau)
-- ============================================================

-- Thu hoi quyen FILE va GRANT cua app_user
REVOKE FILE ON *.* FROM 'app_user'@'%';
REVOKE GRANT OPTION ON *.* FROM 'app_user'@'%';

-- Gioi han app_user chi duoc thao tac tren company_db
REVOKE ALL PRIVILEGES ON *.* FROM 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON company_db.* TO 'app_user'@'%';

-- KHONG doi mat khau app_user (giu nguyen 'app123')
-- Neu muon doi mat khau cho readonly_user va root (tuychon), co the giu hoac comment
-- ALTER USER 'readonly_user'@'%' IDENTIFIED BY 'Read@Only#2024!';
-- ALTER USER 'root'@'%' IDENTIFIED BY 'R00t@Str0ng#2024!';

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
    WHERE username = search_name;
END //
DELIMITER ;

-- ============================================================
-- 3. KHONG HASH MAT KHAU (de tranh anh huong dang nhap web)
-- ============================================================
-- UPDATE employees SET password = SHA2(password, 256);  -- DONG NAY DA DUOC COMMENT

-- ============================================================
-- 4. KIEM TRA SAU PATCH
-- ============================================================
SHOW GRANTS FOR 'app_user'@'%';