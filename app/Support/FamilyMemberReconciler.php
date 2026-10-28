<?php

namespace App\Support;

use App\Models\Family;
use App\Models\FamilyMember;

class FamilyMemberReconciler
{
    private const COMPARABLE_FIELDS = ['order_code', 'product_name', 'email', 'region', 'purchase_date', 'raw_text'];

    /**
     * @param  array<int, array{id: int|null, text: string}>  $submitted
     * @return array<int, array{action: string, member: FamilyMember, old: array|null}>
     */
    public static function reconcile(Family $family, array $submitted): array
    {
        $existing = $family->members()->get()->keyBy('id');
        $diffs = [];

        foreach ($submitted as $row) {
            $parsed = MemberTextParser::parse($row['text'] ?? '');
            $id = $row['id'] ?? null;

            if ($id !== null && $existing->has($id)) {
                $member = $existing->pull($id);
                $old = array_intersect_key($member->getRawOriginal(), array_flip(self::COMPARABLE_FIELDS));

                if (self::differs($old, $parsed)) {
                    $member->fill($parsed);
                    $member->save();
                    $diffs[] = ['action' => 'change', 'member' => $member, 'old' => $old];
                }

                continue;
            }

            $member = $family->members()->create($parsed);
            $diffs[] = ['action' => 'add', 'member' => $member, 'old' => null];
        }

        foreach ($existing as $leftover) {
            $old = array_intersect_key($leftover->getRawOriginal(), array_flip(self::COMPARABLE_FIELDS));
            $leftover->delete();
            $diffs[] = ['action' => 'delete', 'member' => $leftover, 'old' => $old];
        }

        return $diffs;
    }

    /** Các cột mang dữ liệu thật của thành viên, tức là không tính `raw_text`. */
    private const DATA_FIELDS = ['order_code', 'product_name', 'email', 'region', 'purchase_date'];

    /**
     * Danh sách gửi lên có thêm, xóa hay đổi dữ liệu của thành viên nào không.
     * Không ghi gì xuống DB.
     *
     * Không so `raw_text`: thành viên cũ chưa có `raw_text` được form dựng lại
     * thành JSON và gửi lên, nên so cả cột đó thì family có thành viên cũ luôn
     * bị coi là đã sửa dù người dùng không chạm vào.
     *
     * @param  array<int, array{id: int|null, text: string}>  $submitted
     */
    public static function changesMemberData(Family $family, array $submitted): bool
    {
        $existing = $family->members()->get()->keyBy('id');

        foreach ($submitted as $row) {
            $id = $row['id'] ?? null;

            if ($id === null || ! $existing->has($id)) {
                return true;
            }

            $old = $existing->pull($id)->getRawOriginal();

            if (self::differs($old, MemberTextParser::parse($row['text'] ?? ''), self::DATA_FIELDS)) {
                return true;
            }
        }

        return $existing->isNotEmpty();
    }

    private static function differs(array $old, array $parsed, array $fields = self::COMPARABLE_FIELDS): bool
    {
        foreach ($fields as $field) {
            if ((string) ($old[$field] ?? '') !== (string) ($parsed[$field] ?? '')) {
                return true;
            }
        }

        return false;
    }
}
