# Bảng Rubric Đánh Giá Dự Án: Giả Lập Tấn Công, Phòng Thủ Và Đánh Giá Lỗ Hổng CSDL

## Mô tả chung
Bảng rubric này dùng để đánh giá hiệu suất của mỗi vai trò trong dự án (Red Team, Blue Team, Auditor) dựa trên các yêu cầu cụ thể từ mô tả đề tài. Mỗi tiêu chí được đánh giá theo thang 4 mức: Xuất sắc (4), Tốt (3), Trung bình (2), Kém (1).

## Phần A: Đánh giá Red Team (2 người)
*Nhiệm vụ: Lên kịch bản và thực hiện tấn công (thăm dò, khai thác, trích xuất dữ liệu)*

| Tiêu chí | Xuất sắc (4) | Tốt (3) | Trung bình (2) | Kém (1) |
|----------|--------------|---------|----------------|---------|
| **Thực hiện thành công 3 kịch bản tấn công**<br>(SQL Injection, Leo thang đặc quyền, Khai thác điểm yếu cấu hình) | Thực hiện thành công cả 3 kịch bản với bằng chứng chi tiết (logs, screenshots, dữ liệu trích xuất) | Thực hiện thành công 2/3 kịch bản với bằng chứng đủ | Thực hiện thành công 1/3 kịch bản hoặc bằng chứng không đầy đủ | Không thực hiện thành công bất kỳ kịch bản nào hoặc không có bằng chứng |
| **Chi tiết kỹ thuật của mỗi cuộc tấn công**<br>(Payloads sử dụng, phương pháp khai thác, bước-by-step) | Cung cấp tài liệuAttack chi tiết bao gồm: payloads chính xác, giai đoạn tấn công, công cụ sử dụng, và phân tích root cause | Cung cấp tài liệuAttack좋은 với hầu hết chi tiết cần thiết | Tài liệuAttack cơ bản, thiếu một số khía cạnh kỹ thuật quan trọng | Tài liệuAttack không đầy đủ, không rõ ràng hoặc sai lầm về kỹ thuật |
| **Khả năng làm việc nhóm và chia sẻ kiến thức**<br>(Trong Red Team và với Blue Team/Auditor nếu cần) | Tham gia tích cực, chia sẻ kiến thức attack techniques một cách rõ ràng, hỗ trợ thành viên khác trong team | Tham gia tốt, chia sẻ kiến thức một phần | Tham gia hạn chế, chia sẻ kiến thức tối thiểu | Không tham gia, không chia sẻ kiến thức hoặc gây cản trở cho team |
| **Tuân thủ phạm vi và道德规范**<br>(Chỉ tấn công trong môi trường được phép, không gây hại ngoài hệ thống mục tiêu) | Hoàn toàn tuân thủ phạm vi, có bằng chứng giấy phép tấn công, không gây ảnh hưởng hệ thống khác | Pferde như vậy nhưng nhỏ hoặc được khắc phục ngay | Có một vài vi phạm phạm vi nhỏ nhưng không gây hậu quả nghiêm trọng | Vi phạm phạm vi rõ ràng, gây tiềm ẩn hại cho hệ thống ngoài mục tiêu |

## Phần B: Đánh giá Blue Team (2 người)
*Nhiệm vụ: Theo dõi log, cấu hình ProxySQL để chặn query độc hại, thiết lập tường lửa CSDL và vá lỗi phân quyền*

| Tiêu chí | Xuất sắc (4) | Tốt (3) | Trung bình (2) | Kém (1) |
|----------|--------------|---------|----------------|---------|
| **Cấu hình ProxySQL hiệu quả**<br>(Nhận diện và chặn các luồng truy vấn độc hại từ các kịch bản tấn công) | ProxySQL chặn thành công 100% các truy vấn ataque làm theo kịch bản, có bằng chứng logs chặn chi tiết | ProxySQL chặn thành công 80-99% các truy vấn ataque, có logs chặn | ProxySQL chặn thành công 50-79% các truy vấn attaque hoặc logs không đầy đủ | ProxySQL chặn dưới 50% các truy vấn attaque hoặc không có bằng chứng cấu hình/chặn |
| **Thiết lập tường lửa CSDL và vá lỗi phân quyền**<br>(Áp dụng principle of least privilege, thu hồi quyền опасные) | Hoàn toàn thu hồi quyền FILE, GRANT OPTION, thiết lập host-based restrictions như рекомендовано, có bằng chứng verify | Thực hiện nhiều khía cạnh của việc hardening nhưng còn một vàiGap nhỏ | Thực hiện cơ bản hardening nhưng còn nhiều lỗ hổng明显 | Không thực hiện hardening hoặc làm giảm bảo mật (ví dụ: grant thêm quyền) |
| **Giám sát và phản hồi**<br>(Theo dõi log, phát hiện anomalies, thời gian phản hồi) | Có hệ thống giám sát real-time, phát hiện tất cả các cuộc tấn công trong thời gian <5 phút, có báo cáo incident chi tiết | Giám sát tốt, phát hiện hầu hết cuộc tấn công trong thời gian <15 phút | Giám sát cơ bản, phát hiện cuộc tấn công sau >15 phút hoặc logs không được phân tích | Không có hệ thống giám sát hiệu quả hoặc không phản hồiành 손해 |
| **Tài liệu hóa quy trình defense**<br>(Cấu hình áp dụng, thay đổi Made, kết quả防御) | Tài liệu hóa chi tiết,includes: cấu hình trước/sau, câu lệnh exact, screenshots cấu hình, 평가 hiệu quả | Tài liệu hóa tốt với hầu hết thông tin cần thiết | Tài liệu hóa cơ bản, thiếu một số khía cạnh quan trọng | Tài liệu hóa không đầy đủ, khó hiểu hoặc không liên quan tới defense actions |

## Phần C: Đánh giá Auditor (1 người)
*Nhiệm vụ: Viết báo cáo đánh giá rủi ro và tài liệu hóa các lỗ hổng theo chuẩn (OWASP, CVE)*

| Tiêu chí | Xuất sắc (4) | Tốt (3) | Trung bình (2) | Kém (1) |
|----------|--------------|---------|----------------|---------|
| **Báo cáo đánh giá rủi ro đầy đủ**<br>(Theo chuẩn OWASP 2021/CVE, bao gồm impact, likelihood, risk level) | Báo cáo tuân thủ hoàn toàn chuẩn OWASP/CVE, bao gồm: mô tả lỗ hổng, vector tấn công, impact analysis, likelihood assessment, risk rating (Critical/High/Medium/Low), và khắc phục đề xuất | Báo cáo tốt, thiếu một vài yếu tố nhỏ của chuẩn hoặc анализ không sâu bằng | Báo cáo cơ bản, covers 주요 내용 nhưng thiếu phần phân tích risk chi tiết hoặc đề xuất khắc phục chung chung | Báo cáo không đáp ứng chuẩn, thiếu ROS thành phần chính hoặc chứa lỗi factuáł |
| **Tài liệu hóa lỗ hổng theo chuẩn**<br>(Mỗi lỗ hổng được document với CVE-like ID, steps to reproduce, fix verification) | Mỗi lỗ hổng được tài liệu hóa như một CVE bản sửa: includes ID, description, affected version, PoC steps, fix verification steps, và references | Tài liệu hóa lỗ hổng tốt với hầu hết thành phần CVE cần thiết | Tài liệu hóa lỗ hổng cơ bản, thiếu PoC chi tiết hoặc fix verification | Tài liệu hóa lỗ hổng không đầy đủ, khó tái tạo hoặc không có cách xác thực fix |
| **Phân tích hiệu quả của defesa Blue Team**<br>(So sánh trước/sau defense, оценивание減少风险) | Phân tích chi tiết về cómo từng biện pháp defense làm việc, giảm risco theo từng hyökk擊, có metrics concrete (số lượng truy vấn chặn được, thời gian phản hồi mejora) | Phân tích tốt về hiệu quả defense với hầu hết metrics cần thiết | Phân tích cơ bản về defense, thiếu một số metrics ή So sánh trước/sau không duidelijk | Phân tích防御 thiếu 강 또는 không liên quan tới các biện pháp defense đã triển khai |
| **Chất lượng presentation và professionalism**<br>(Báo cáo rõ ràng, có cấu trúc, tránh jargon không cần thiết) | Báo cáo xuất sắc: có mục lục واضحة, phần tóm tóm Executive Summary, استفاده من الرسومات/الرسوم البيانية عند الضرورة، اللغة professionnelle et claire | Báo cáo tốt với cấu trúc hợp lý, ngôn ngữ dễ hiểu | Báo cáo Acceptable nhưng có thể lỗi về cấu trúc hoặc cách diễn giải chưa rõ | Báo cáo khó đọc, thiếu cấu trúc hoặc chứa nhiều lỗilanguage/unprofessional |

## Quy chuẩn Điểm Tổng Kho
- **Điểm tối đa mỗi vai trò**: 16 điểm (4 tiêu chí × 4 điểm)
- **Xếp loại tổng thể**:
  - 14-16 điểm: Xuất sắc (Đạt todas mục tiêu với chất lượng cao)
  - 11-13 điểm: Tốt (Đạt hầu hết mục tiêu, sommige cải tiến cần thiết)
  - 8-10 điểm: Trung bản (Đạt những mục tiêu cơ bản, cần cải thiện đáng kể)
  - 0-7 điểm: Kém (Không đạt hầu hết mục tiêu, cần luyện tập lại)

## Lưu ý quan trọng khi sử dụng rubric
1. **By rzecz**: Mỗi thành viên nên tự đánh giá và được đánh giá bởi đồng đội vàGiáo viên hướng dẫn nếu có.
2. **Bằng chứng bắt buộc**: Điểm số chỉ được cấp khi có bằng chứng cụ thể (file log, screenshots, tài liệu, screenplay exploits, cấu hình files).
3. **Phản hồi kịp thời**: Rubric nên được sử dụng sau mỗi sprint lớn để cung cấp feedback cải thiện feng어진 tốt nhất cho sprint tiếp theo.
4. **Tinh thần hợp tác**: Đề điểm cho những hành động hỗ trợ đồng đội và chia sẻ kiến thức vượt poza nhiệm vụ cá nhân.

---
*Rubric này được thiết kế cụ thể cho đề tài "Giả lập tấn công, phòng thủ và đánh giá lỗ hổng CSDL" dựa trên mô tả được cung cấp. Các tiêu chí mà sát với yêu cầu cụ thể về các kịch bản tấn công, difesa ProxySQL,보고 cáo tuân thủ chuẩn OWASP/CVE, và vai trò cụ thể trong nhóm.*