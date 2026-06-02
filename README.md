# DBS401 – Đề tài 1: Database Pentesting & Active Defense
## Hướng dẫn chạy project (PHP Version)

---

## Cấu trúc thư mục

```
php-project/
├── docker-compose.yml       ← Cấu hình toàn bộ hệ thống
├── sql/
│   ├── init.sql             ← Tạo DB, bảng, user có lỗ hổng cố ý
│   ├── attack_demo.sql      ← Script Red Team tấn công
│   └── patch.sql            ← Script Blue Team vá lỗi
├── proxysql/
│   └── proxysql.cnf         ← Cấu hình ProxySQL + rule chặn SQLi
└── webapp-php/
    ├── Dockerfile           ← Build PHP + Apache
    ├── config.php           ← Kết nối DB
    ├── layout.php           ← HTML layout dùng chung
    ├── index.php            ← Redirect → login
    ├── login.php            ← Đăng nhập (có SQLi cố ý)
    ├── search.php           ← Tìm nhân viên (có UNION SQLi cố ý)
    ├── sysconfig.php        ← Lộ thông tin DB cố ý
    └── logout.php           ← Đăng xuất
```

---

## Yêu cầu cài đặt

- [Docker Desktop](https://www.docker.com/products/docker-desktop) (bật lên trước khi chạy)
- Không cần cài PHP, MySQL, hay bất cứ thứ gì khác

---

## Bước 1 — Chạy toàn bộ hệ thống

Mở terminal, cd vào thư mục chứa `docker-compose.yml` rồi chạy:

```bash
docker compose up -d --build
```

Lần đầu sẽ tải image về, đợi khoảng 2-3 phút.

Kiểm tra các container đã chạy chưa:

```bash
docker compose ps
```

Kết quả mong đợi — tất cả phải `running`:
```
mysql_vuln        running
proxysql          running
webapp_vuln       running
webapp_protected  running
```

---

## Bước 2 — Truy cập web app

| URL | Mô tả |
|-----|-------|
| http://localhost:5000 | ⚠️ Web app **có lỗ hổng** — Red Team tấn công tại đây |
| http://localhost:5001 | 🛡️ Web app **qua ProxySQL** — Blue Team demo chặn tại đây |

Tài khoản demo:
- `admin` / `admin123`
- `alice` / `alice456`

---

## Bước 3 — Kịch bản demo tấn công (Red Team)

### Kịch bản 1: SQL Injection — Bypass đăng nhập
Vào http://localhost:5000/login.php

Nhập vào ô **Username**:
```
' OR '1'='1' -- 
```
Để trống mật khẩu → nhấn Đăng nhập → **vào được không cần mật khẩu**

---

### Kịch bản 2: UNION SQLi — Lấy toàn bộ password
Vào http://localhost:5000/search.php

Nhập vào ô tìm kiếm:
```
' UNION SELECT 1,username,password,role,salary,email FROM employees -- 
```
→ **Hiện ra toàn bộ username + password của mọi nhân viên**

---

### Kịch bản 3: Khai thác cấu hình — Lộ thông tin DB
Vào http://localhost:5000/sysconfig.php

→ **Hiện ra DB_HOST, DB_PORT, DB_USER, DB_PASS, Secret Key** không cần đăng nhập

---

## Bước 4 — Demo phòng thủ (Blue Team)

Thử lại **y hệt** các kịch bản trên nhưng dùng http://localhost:5001

- SQLi bypass login → **bị chặn bởi ProxySQL rule**
- UNION SQLi → **bị chặn**
- Kịch bản cấu hình vẫn hiện (lỗ hổng tầng app, không qua ProxySQL)

---

## Bước 5 — Vá lỗi hoàn toàn (Blue Team)

Kết nối vào MySQL và chạy script vá lỗi:

```bash
docker exec -i mysql_vuln mysql -uroot -proot123 company_db < sql/patch.sql
```

Sau khi vá:
- Quyền `FILE` và `GRANT OPTION` của `app_user` bị thu hồi
- Password được hash (không còn plain text)
- Stored procedure được viết lại dùng prepared statement

---

## Bước 6 — Tắt hệ thống

```bash
docker compose down
```

Xóa luôn data (reset hoàn toàn để demo lại từ đầu):

```bash
docker compose down -v
```

---

## Kiến trúc hệ thống

```
                    ┌─────────────────────────────────────┐
                    │          Docker Network              │
                    │                                      │
  Trình duyệt ──────┤─→ webapp_vuln:80  ──────────────┐   │
  (port 5000)       │                                  ↓   │
                    │                           mysql_vuln:3306
  Trình duyệt ──────┤─→ webapp_protected:80 → proxysql:6033 ┤
  (port 5001)       │      (Blue Team demo)    (chặn SQLi)  │
                    └─────────────────────────────────────┘
```

---

## Lỗ hổng cố ý trong project

| File | Lỗ hổng | Mục đích demo |
|------|---------|---------------|
| `login.php` | Nối chuỗi trực tiếp vào SQL | SQLi bypass login |
| `search.php` | Nối chuỗi + hiện query ra màn hình | UNION SQLi lấy data |
| `sysconfig.php` | Lộ DB_HOST, DB_PASS, Secret Key | Khai thác cấu hình |
| `init.sql` | `app_user` có quyền FILE + GRANT | Privilege Escalation |
| `init.sql` | Password lưu plain text | Lộ credential |
| `docker-compose.yml` | Port 3306 expose ra ngoài | Kết nối DB trực tiếp |

---

> ⚠️ Project này chứa lỗ hổng **CỐ Ý** phục vụ mục đích học tập môn DBS401.  
> Không dùng trong môi trường thực tế.
