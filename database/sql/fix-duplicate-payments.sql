-- =============================================================================
-- Sửa các family bị cộng thừa tháng do bấm nút "Xác nhận thanh toán" hai lần.
--
-- Bối cảnh: trước commit 236e256 không có gì chặn việc gửi trùng form, nên một
-- cú double-click làm quickPay chạy hai lần và cộng số tháng hai lần. Dấu vết
-- để lại trong history_joining_family là hai dòng status='payment' của cùng một
-- family ở cùng một giây.
--
-- Từ nay server tự bỏ qua lần bấm trùng (FamilyController::quickPay), nên script
-- này chỉ dùng để dọn dữ liệu đã lỡ sai trước đó.
--
-- KHÔNG dùng cách lùi thẳng mọi ngày 2027 về 2026: 11/12/2026 + 1 tháng bằng
-- 11/01/2027 là hoàn toàn đúng, và family trả trước 12 tháng cũng rơi vào 2027.
-- Script này chỉ đụng tới những family thực sự có bản ghi thanh toán trùng.
--
-- Cách chạy:
--   mysqldump -u root -p <database> families history_joining_family > backup.sql
--   mysql -u root -p <database> < database/sql/fix-duplicate-payments.sql
--
-- Yêu cầu MySQL 8.0 trở lên (dùng CTE và window function).
-- =============================================================================


-- -----------------------------------------------------------------------------
-- BƯỚC 1 - Xem trước. Chưa thay đổi gì cả.
-- Đọc kỹ cột sau_khi_sua trước khi chạy bước 2.
-- Không ra dòng nào nghĩa là không có family nào bị, dừng tại đây.
--
-- Cột can_kiem_tra_tay: ngày hạn rơi vào 29, 30 hoặc 31. Phiên bản cũ cộng tháng
-- kiểu tràn (31/01 + 1 tháng ra 03/03) nên DATE_SUB không trả lại đúng ngày ban
-- đầu được. Những dòng này phải tự đối chiếu với old_value trong lịch sử.
-- -----------------------------------------------------------------------------
WITH dup AS (
    SELECT id, family_id,
           CASE WHEN JSON_VALID(new_value)
                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(new_value, '$.months')) AS UNSIGNED)
                ELSE 0 END AS months,
           ROW_NUMBER() OVER (PARTITION BY family_id, created_at ORDER BY id) AS lan
    FROM history_joining_family
    WHERE status = 'payment'
)
SELECT f.id,
       f.user,
       f.next_payment_at                                              AS hien_tai,
       SUM(d.months)                                                  AS so_thang_thua,
       DATE_SUB(f.next_payment_at, INTERVAL SUM(d.months) MONTH)      AS sau_khi_sua,
       IF(DAY(f.next_payment_at) > 28, 'CÓ', '')                      AS can_kiem_tra_tay
FROM dup d
JOIN families f ON f.id = d.family_id
WHERE d.lan > 1
GROUP BY f.id, f.user, f.next_payment_at;


-- -----------------------------------------------------------------------------
-- BƯỚC 2 - Trừ số tháng thừa VÀ xoá dòng lịch sử trùng, trong cùng một giao dịch.
--
-- Hai việc này phải đi liền nhau: dòng lịch sử trùng chính là thứ đánh dấu
-- "family này đã được sửa". Nếu chỉ trừ tháng mà giữ lại lịch sử, chạy script
-- lần thứ hai sẽ trừ thêm một lần nữa.
-- -----------------------------------------------------------------------------
START TRANSACTION;

WITH dup AS (
    SELECT id, family_id,
           CASE WHEN JSON_VALID(new_value)
                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(new_value, '$.months')) AS UNSIGNED)
                ELSE 0 END AS months,
           ROW_NUMBER() OVER (PARTITION BY family_id, created_at ORDER BY id) AS lan
    FROM history_joining_family
    WHERE status = 'payment'
),
thua AS (
    SELECT family_id, SUM(months) AS so_thang
    FROM dup
    WHERE lan > 1
    GROUP BY family_id
)
UPDATE families f
JOIN thua t ON t.family_id = f.id
SET f.next_payment_at = DATE_SUB(f.next_payment_at, INTERVAL t.so_thang MONTH);

WITH dup AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY family_id, created_at ORDER BY id) AS lan
    FROM history_joining_family
    WHERE status = 'payment'
)
DELETE h FROM history_joining_family h
JOIN dup d ON d.id = h.id
WHERE d.lan > 1;

COMMIT;


-- -----------------------------------------------------------------------------
-- BƯỚC 3 - Kiểm tra lại. Không ra dòng nào là xong.
-- Chạy lại cả script lúc này cũng không trừ thêm lần nữa.
-- -----------------------------------------------------------------------------
SELECT family_id, created_at, COUNT(*) AS so_lan
FROM history_joining_family
WHERE status = 'payment'
GROUP BY family_id, created_at
HAVING COUNT(*) > 1;
