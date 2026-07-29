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

    private static function differs(array $old, array $parsed): bool
    {
        foreach (self::COMPARABLE_FIELDS as $field) {
            if ((string) ($old[$field] ?? '') !== (string) ($parsed[$field] ?? '')) {
                return true;
            }
        }

        return false;
    }
}
