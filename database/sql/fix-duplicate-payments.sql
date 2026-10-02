-- =============================================================================
-- Sửa các family bị cộng thừa tháng do bấm nút "Xác nhận thanh toán" hai lần.
--
-- Bối cảnh: trước commit 236e256 không có gì chặn việc gửi trùng form, nên một
-- cú double-click làm quickPay chạy hai lần và cộng số tháng hai lần. Dấu vết
-- để lại trong history_joining_family là hai dòng status='payment' của cùng một
-- family ở cùng một giây.
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
-- -----------------------------------------------------------------------------
WITH dup AS (
    SELECT id, family_id,
           CAST(JSON_UNQUOTE(JSON_EXTRACT(new_value, '$.months')) AS UNSIGNED) AS months,
           ROW_NUMBER() OVER (PARTITION BY family_id, created_at ORDER BY id) AS lan
    FROM history_joining_family
    WHERE status = 'payment'
)
SELECT f.id,
       f.user,
       f.next_payment_at                                              AS hien_tai,
       SUM(d.months)                                                  AS so_thang_thua,
       DATE_SUB(f.next_payment_at, INTERVAL SUM(d.months) MONTH)      AS sau_khi_sua
FROM dup d
JOIN families f ON f.id = d.family_id
WHERE d.lan > 1
GROUP BY f.id, f.user, f.next_payment_at;


-- -----------------------------------------------------------------------------
-- BƯỚC 2 - Trừ đi đúng số tháng đã cộng thừa.
-- -----------------------------------------------------------------------------
WITH dup AS (
    SELECT id, family_id,
           CAST(JSON_UNQUOTE(JSON_EXTRACT(new_value, '$.months')) AS UNSIGNED) AS months,
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


-- -----------------------------------------------------------------------------
-- BƯỚC 3 - Xoá các dòng lịch sử trùng, giữ lại dòng đầu tiên của mỗi lần bấm.
-- Bỏ qua bước này nếu muốn giữ nguyên dấu vết sự cố.
-- -----------------------------------------------------------------------------
WITH dup AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY family_id, created_at ORDER BY id) AS lan
    FROM history_joining_family
    WHERE status = 'payment'
)
DELETE h FROM history_joining_family h
JOIN dup d ON d.id = h.id
WHERE d.lan > 1;


-- -----------------------------------------------------------------------------
-- BƯỚC 4 - Kiểm tra lại. Không ra dòng nào là xong.
-- -----------------------------------------------------------------------------
SELECT family_id, created_at, COUNT(*) AS so_lan
FROM history_joining_family
WHERE status = 'payment'
GROUP BY family_id, created_at
HAVING COUNT(*) > 1;
