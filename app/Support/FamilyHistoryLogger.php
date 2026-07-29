<?php

namespace App\Support;

use App\Models\History;

class FamilyHistoryLogger
{
    private const VALUE_FIELDS = ['order_code', 'product_name', 'email', 'region', 'purchase_date'];

    /**
     * @param  array<int, array{action: string, member: \App\Models\FamilyMember, old: array|null}>  $diffs
     */
    public static function logDiffs(int $familyId, array $diffs): void
    {
        foreach ($diffs as $diff) {
            $member = $diff['member'];

            match ($diff['action']) {
                'add' => History::create([
                    'order_id' => $member->order_code ?? '',
                    'family_id' => $familyId,
                    'status' => 'add',
                    'name_product' => $member->product_name ?? '',
                    'email' => $member->email ?? '',
                    'new_value' => json_encode(self::valueFields($member->getRawOriginal()), JSON_UNESCAPED_UNICODE),
                ]),
                'delete' => History::create([
                    'order_id' => $member->order_code ?? '',
                    'family_id' => $familyId,
                    'status' => 'delete',
                    'name_product' => $member->product_name ?? '',
                    'email' => $member->email ?? '',
                    'old_value' => json_encode(self::valueFields($diff['old'] ?? $member->getRawOriginal()), JSON_UNESCAPED_UNICODE),
                ]),
                'change' => History::create([
                    'order_id' => $diff['old']['order_code'] ?? '',
                    'family_id' => $familyId,
                    'status' => 'change',
                    'name_product' => $member->product_name ?? '',
                    'email' => $member->email ?? '',
                    'old_value' => json_encode(self::valueFields($diff['old'] ?? []), JSON_UNESCAPED_UNICODE),
                    'new_value' => json_encode(self::valueFields($member->getRawOriginal()), JSON_UNESCAPED_UNICODE),
                ]),
                default => null,
            };
        }
    }

    private static function valueFields(array $fields): array
    {
        $result = [];
        foreach (self::VALUE_FIELDS as $field) {
            $result[$field] = $fields[$field] ?? null;
        }

        return $result;
    }
}
