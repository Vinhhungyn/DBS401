# Báo cáo bảo mật ứng dụng PHP

## Tổng quan
Đây là báo cáo phân tích bảo mật của ứng dụng PHP trong thư mục `webapp-php` và các thành phần liên quan (ModSecurity, ProxySQL, SQL init/patch). Ứng dụng bao gồm các chức năng: đăng nhập, tìm kiếm, hồ sơ, upload file, phản hồi.

## Các lỗ hổng bảo mật phát hiện

### 1. SQL Injection (SQLi)
- **Vị trí**: 
  - `webapp-php/search.php`: xây dựng câu query bằng chuỗi trực tiếp: `$sql = "SELECT … WHERE username='{$q}'"`.
  - `webapp-php/profile.php`: `$sql = "SELECT username, role, email, salary FROM employees WHERE username='{$user}';"`.
  - `webapp-php-patched/login.php`: đã quay lại sử dụng chuỗi concatenation: `$sql = "SELECT id, username, role FROM employees WHERE username='{$username}' AND password='{$password}'"`.
  - Stored procedure `search_employee` trong `sql/init.sql` (trước khi打补丁) cũng sử dụng concatenation.
- **Nguy cơ**: Tiêm mã SQLCho phép úto viên đọc, sửa, xóa dữ liệu, thậm chí thực thi lệnh hệ thống qua quyền FILE.
- **Các biện pháp giảm thiểu đã có**:
  - `webapp-php/login.php` (phiên bản gốc) sử dụng prepared statement với `?` placeholders và `bind_param`.
  - ModSecurity rules (`webapp-php-patched/modsecurity/rule-sql.conf`) chứa cácrule (ID 9020‑9029) để phát hiện và chặn các mẫu tiêm SQL phổ biến.
  - ProxySQL (`proxysql/proxysql.cnf`) chứa query rules 1‑7 sử dụng regex để chặn SQL injection.
  - Patch SQL (`sql/patch.sql`) đã thu hồi quyền FILE và GRANT OPTION từ user `app_user`, giới hạn権限 trên `company_db`, và tạo lại stored procedure sử dụng prepared statement.
- **Khuyến nghị**: 
  - Loại bỏ všech việc costruir query bằng concatenation; luôn dùng prepared statements hoặc參數化查询。
  - Đảm bảo tất cả các kết nối DB đều sử dụng tài khoản có quyền hạn tối thı̂n (chỉ SELECT/INSERT/UPDATE/DELETE trên DB cần thiết).
  - Kiểm tra và áp dụng lại các 규칙 ModSecurity/ProxySQL nếu chúng bị vô hiệu hóa.

### 2. Cross‑Site Scripting (XSS) – Stored & Reflected
- **Vị trí**: `webapp-php/feedback.php`
  - Lưu trữ dữ liệu đầu vào (`$_POST['name']`, `$_POST['comment']`) trực tiếp vào `$_SESSION['feedbacks']` mà không lọc/sanitize → **Stored XSS**.
  - Phản hồi trực tiếp nội dung từ `$_GET['search']` vào HTML mà không escape → **Reflected XSS**.
- **Nguy cơ**: Đ 공격 allemandes có thểInject JavaScript để đánh 자료 người dùng khác, chiếm đoán session, thực hiện hành động thay mặt người dùng.
- **Các biện pháp giảm th有一定**:
  - Hiện tại chưa có bất kỳ bộ lọc/escaping nào trong `feedback.php`.
- **Khuyến nghị**:
  - Áp dụng `htmlspecialchars()` hoặc một thư viện egress escaping (HTMLPurifier) trước khi xuất ra HTML.
  - Khi lưu trữ, vẫn lưu trữ nguyên bản nhưng cần kiểm soát đầu vào (validate) và luôn_escape khi xuất.
  - Cân nhactivating ModSecurity rules for XSS (not present in current modsecurity rules) hoặc sử dụng CSP header.

### 3. Unrestricted File Upload (webshell)
- **Vị trí**: `webapp-php/profile.php` (và có thể `upload.php` tương tự).
  - Sử dụng `move_uploaded_file($_FILES['avatar']['tmp_name'], $target_dir . $basename)` mà không kiểm tra:
    - phần mở rộng file (.php, .phtml, .asp, …)
    - MIME type thực tế (chỉ phụ thuộc vào `$_FILES['type']` mà có thể bị giả mạo).
    - nội dung file (không quét mã độc).
- **Nguy cơ**: Tấn công có thể upload webshell (ví dụ: `shell.php`), sau đó thực thi mã任意 trên server dẫn to được toàn quyền hệ thống.
- **Các biện pháp giảm thріkken đã có**:
  - ModSecurity rules trong `webapp-php-patched/modsecurity/rule-fileupload.conf` (ID 9040‑9047):
    - Chặn các phần mở rộng dangereux (.php, .phtml, .asp, .jsp, …).
    - Chặn double extension và null‑byte injection.
    - Bắt buộc chỉ cho phép MIME type `image/*` trên endpoint upload (Rule 9043‑9044).
    - Phát hiện webshell/script code bên trong nội dung file (Rule 9045).
    - Giới hạn độ dài tên file và chặn path traversal trong tên file.
- **Khuyến nghị**:
  - Đảm bảo ModSecurity đang được bật và các rule trên đượcロード vào Apache/Nginx.
  - Thêm kiểm tra phía ứng dụng: kiểm tra phần mở rộng whitelist, kiểm tra lại MIME qua `finfo_file()`, và lưu file vào thư mục có quyền `chmod 0644`, không cho thực thi.
  - Đổi tên file lưu trữ (ví dụ: hash) và không 신뢰 tên file gốc.

### 4. Insecure Session / Role Handling
- **Vị trí**: `webapp-php/login.php` (line 32‑33): `setcookie('role', $row[2], 0, '/');`
- **Nguy cơ**: Vai trò (role) được lưu trữ ở cookie ở dạng plaintext, có thể bị sửa đổi bởi Client để thăng quyền (ví dụ: đổi `role` thành `admin` để truy cập `/sysconfig.php`).
- **Các biện pháp giảm thььен**:
  - Không lưu trữ vai trò ở cookie; thay vì đó lưu trữ trong session (`$_SESSION['role']`) và xác thực ở server-side mỗi request.
  - Nếu phải lưu ở cookie, phải ký mã (HMAC) hoặc encrypt.
- **Khuyến nghị**: Loại bỏ việc set cookie role; chỉ sử dụng session để xác thực và авторизтация.

### 5. Hardcoded / Weak Database Credentials
- **Vị trí**: `webapp-php/config.php` (fallback values): `DB_USER=app_user`, `DB_PASS=app123`.
- **Nguy cơ**: Mật khẩu đơn giản, dễ đoán; nếu source code rò rãi, attacker có thể truy cập DB trực tiếp.
- **Khuyến nghị**:
  - Lưu trữ thông tin xác thực DB trong các biến môi trường hoặc file cấu hình ngoài source control (ví dụ: `.env` không được commit).
  - Sử dụng mật khẩu mạnh, thường xuyên thay đổi.
  - Giới hạn kết nối DB chỉ từ máy chủ ứng dụng (firewall).

### 6. Missing Input Validation & Output Encoding Elsewhere
- Các trang khác (search, upload) cũng cần kiểm tra đầu vào và mã hóa đầu ra tương tự.
- **Khuyến nghị**: Áp dụng principios difesa in depth:
  - **Input validation**: whitelist các ký tự hợp lệ, giới hạn độ dài.
  - **Output encoding**: luôn thoát dữ liệu trước khi đưa ra HTML, JS, SQL, v.v.
  - **Security headers**: Thêm `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `XXS-Protection`.

## Đánh giá mức độ piority
| Lỗ hổng | Mức độ nguy cơ | Giả sử影響 | Khuyến nghị cấp |
|---------|----------------|------------|----------------|
| SQL Injection (login/profile/search) | Cao | Đánh cắp dữ liệu, thăng quyền, RCE (qua FILE) | **Nhanh** – sửa dùng prepared statements, bật ModSecurity/ProxySQL |
| Unrestricted File Upload | Cao | Upload webshell → RCE | **Nhanh** – bật/tighten ModSecurity rules, thêm validation server‑side |
| Stored & Reflected XSS | Trung‑cao |Session hijacking, phishing | **Trung** – escape output, CSP |
| Insecure role cookie | Trung | Thăng quyền qua cookie jiggery | **Trung** – lưu role trong session |
| Hardcoded DB credentials | Trung | Đánh cắp DB nếu source rò rỉ | **Trung** – dùng env/vault |

## Kế hoạch khắc phục ngắn hạn (Next Sprint)
1. **Sửa tất cả các truy vấn SQL**: thay thế concatenation bằng prepared statements (`mysqli_prepare` / `PDO`) trong `search.php`, `profile.php` và các file khác.
2. **Kích hoạt và kiểm tra ModSecurity**: sao chép các file rule từ `webapp-php-patched/modsecurity/` vàoActive ModSecurity cấu hình; thử nghiệm với các payload SQLi và文件上传.
3. **Bật và tinh chỉnh ProxySQL**: xác認 query rules 1‑7 đang hoạt động; theo dõi log để обнаружение blokovaných попыток.
4. **Sửa lỗi XSS trong feedback.php**: thêm `htmlspecialchars()` cho `$name` và `$comment` khi lưu trữ (혹은عند xuất)؛ escape `$_GET['search']` trước khi đưa vào HTML.
5. **Eliminate insecure role cookie**: xóa dòng `setcookie('role', …);` và xử lý role chỉ từ `$_SESSION['role']`.
6. **Cải thiện upload file**: thêm kiểm tra phần mở rộng whitelist (jpg, png, gif), sử dụng `finfo_file()` để xác thực MIME, đổi tên file lưu trữ bằng hash, lưu ngoài web root hoặc trong thư mục với chmod 0644 và vô hiệu hoá thực thi.
7. **Quay lại và kiểm tra lại các bản vá SQL (`sql/patch.sql`)**: đảm bảo rằng quyền `FILE` và `GRANT OPTION` thực sự đã được thu hồi và các stored procedure sử dụng prepared statement.
8. **Thêm security headers** ở nível web server hoặc trong `layout.php` (header('Content-Security-Policy: …');).

## Kết luận
Ứng dụng hiện tại triển khai một số lớp bảo vệ (prepared statements trong một số file, ModSecurity, ProxySQL, bản vá SQL). Tuy nhiên, còn nhiều khoảng trống nghiêm trọng – đặc biệt là các trường hợp sử dụng concatenation SQL, file upload không kiểm soát, XSS và xử lý vai trò không an toàn. Việc vá các lỗ hổng trên theo khuyến nghị trên sẽ nâng ứng dụng lên một mức độ bảo mật đáng chấp nhận, giảm đáng kể nguy cơ rò rỉ dữ liệu, takeover tài khoản và remote code execution.

---  
*Báo cáo này được tổng hợp dựa trên việc kiểm tra mã nguồn, các file cấu hình ModSecurity/ProxySQL, tập tin SQL init/patch, và file PDF “Nhom de tai 1.pdf” (nội dung PDF không thể trích xuất tự động do hạn chế công cụ). Nếu bạn cung cấp nội dung chi tiết của PDF, tôi có thể bổ sung phần phân tích hoặc tài liệu tham khảo cụ thể vào báo cáo.*