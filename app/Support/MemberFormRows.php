<?php

namespace App\Support;

use App\Models\Family;
use App\Models\FamilyMember;

/**
 * Dựng danh sách thành viên để hiển thị dạng bảng trong form thêm/sửa family.
 *
 * Mỗi dòng giữ lại `text` là nội dung dán gốc (thứ sẽ được gửi lên server và
 * phân tích lại ở `MemberTextParser`), kèm các cột đã tách sẵn để in ra bảng.
 */
class MemberFormRows
{
    /**
     * Ưu tiên dữ liệu người dùng vừa nhập (khi validate lỗi), nếu không thì lấy từ DB.
     *
     * @return array<int, array{id: int|null, text: string, order_code: ?string, product_name: ?string, email: ?string, region: ?string, purchase_date: ?string}>
     */
    public static function forForm(?Family $family = null): array
    {
        $texts = old('member_texts');

        if (is_array($texts)) {
            return self::fromSubmittedTexts($texts, (array) old('member_ids', []));
        }

        return $family ? self::fromFamily($family) : [];
    }

    /**
     * @param  array<int, string|null>  $texts
     * @param  array<int, mixed>  $ids
     */
    private static function fromSubmittedTexts(array $texts, array $ids): array
    {
        $rows = [];

        foreach ($texts as $index => $text) {
            if (trim((string) $text) === '') {
                continue;
            }

            $parsed = MemberTextParser::parse($text);

            $rows[] = [
                'id' => isset($ids[$index]) && $ids[$index] !== '' ? (int) $ids[$index] : null,
                'text' => (string) $text,
                'order_code' => $parsed['order_code'],
                'product_name' => $parsed['product_name'],
                'email' => $parsed['email'],
                'region' => $parsed['region'],
                'purchase_date' => self::displayDate($parsed['purchase_date']),
            ];
        }

        return $rows;
    }

    private static function fromFamily(Family $family): array
    {
        return $family->members->map(fn (FamilyMember $member) => [
            'id' => $member->id,
            'text' => self::textFor($member),
            'order_code' => $member->order_code,
            'product_name' => $member->product_name,
            'email' => $member->email,
            'region' => $member->region,
            'purchase_date' => $member->purchase_date?->format('d/m/Y'),
        ])->all();
    }

    /**
     * Nội dung để dán lại khi bấm sửa. Thành viên cũ chưa có `raw_text` thì
     * dựng lại dạng JSON - `MemberTextParser` đọc được cả hai dạng.
     */
    private static function textFor(FamilyMember $member): string
    {
        if ($member->raw_text !== null && trim($member->raw_text) !== '') {
            return $member->raw_text;
        }

        return json_encode([
            'order_code' => $member->order_code,
            'product_name' => $member->product_name,
            'email' => $member->email,
            'region' => $member->region,
            'purchase_date' => $member->purchase_date?->format('d/m/Y'),
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function displayDate(?string $isoDate): ?string
    {
        if ($isoDate === null || $isoDate === '') {
            return null;
        }

        $parts = explode('-', $isoDate);

        return count($parts) === 3 ? "{$parts[2]}/{$parts[1]}/{$parts[0]}" : $isoDate;
    }
}
