<?php

namespace App\Support;

use App\Models\Family;
use App\Models\History;
use Illuminate\Support\Facades\Auth;

class FamilyHistoryLogger
{
    private const VALUE_FIELDS = ['order_code', 'product_name', 'email', 'region', 'purchase_date'];

    /** Các cột của family được theo dõi khi sửa thông tin family. */
    public const FAMILY_FIELDS = [
        'user',
        'email',
        'number_phone',
        'number_bank',
        'name_bank',
        'payment_at',
        'next_payment_at',
        'afiilicate_by',
        'note',
    ];

    /**
     * @param  array<int, array{action: string, member: \App\Models\FamilyMember, old: array|null}>  $diffs
     */
    public static function logDiffs(int $familyId, array $diffs): void
    {
        foreach ($diffs as $diff) {
            $member = $diff['member'];

            match ($diff['action']) {
                'add' => History::create(self::withActor([
                    'order_id' => $member->order_code ?? '',
                    'family_id' => $familyId,
                    'status' => 'add',
                    'name_product' => $member->product_name ?? '',
                    'email' => $member->email ?? '',
                    'new_value' => self::encode(self::valueFields($member->getRawOriginal())),
                ])),
                'delete' => History::create(self::withActor([
                    'order_id' => $member->order_code ?? '',
                    'family_id' => $familyId,
                    'status' => 'delete',
                    'name_product' => $member->product_name ?? '',
                    'email' => $member->email ?? '',
                    'old_value' => self::encode(self::valueFields($diff['old'] ?? $member->getRawOriginal())),
                ])),
                'change' => History::create(self::withActor([
                    'order_id' => $diff['old']['order_code'] ?? '',
                    'family_id' => $familyId,
                    'status' => 'change',
                    'name_product' => $member->product_name ?? '',
                    'email' => $member->email ?? '',
                    'old_value' => self::encode(self::valueFields($diff['old'] ?? [])),
                    'new_value' => self::encode(self::valueFields($member->getRawOriginal())),
                ])),
                default => null,
            };
        }
    }

    /**
     * Ghi lại việc ấn nút thanh toán (thanh toán nhanh).
     */
    public static function logPayment(Family $family, array $old, int $months): void
    {
        History::create(self::withActor([
            'order_id' => '',
            'family_id' => $family->id,
            'status' => 'payment',
            'name_product' => '',
            'email' => $family->email ?? '',
            'old_value' => self::encode([
                'payment_at' => self::stringify($old['payment_at'] ?? null),
                'next_payment_at' => self::stringify($old['next_payment_at'] ?? null),
            ]),
            'new_value' => self::encode([
                'months' => $months,
                'payment_at' => self::stringify($family->payment_at),
                'next_payment_at' => self::stringify($family->next_payment_at),
            ]),
        ]));
    }

    /**
     * Ghi lại việc sửa thông tin family (chỉ các cột thực sự thay đổi).
     *
     * @param  array<string, mixed>  $old  giá trị trước khi lưu
     */
    public static function logFamilyChanged(Family $family, array $old): void
    {
        $changedOld = [];
        $changedNew = [];

        foreach (self::FAMILY_FIELDS as $field) {
            $before = self::stringify($old[$field] ?? null);
            $after = self::stringify($family->{$field});

            if ($before !== $after) {
                $changedOld[$field] = $before;
                $changedNew[$field] = $after;
            }
        }

        if ($changedOld === []) {
            return;
        }

        History::create(self::withActor([
            'order_id' => '',
            'family_id' => $family->id,
            'status' => 'family',
            'name_product' => '',
            'email' => $family->email ?? '',
            'old_value' => self::encode($changedOld),
            'new_value' => self::encode($changedNew),
        ]));
    }

    /**
     * Gắn người đang đăng nhập vào bản ghi lịch sử.
     * `user_name` được lưu kèm để lịch sử vẫn đọc được nếu tài khoản bị xoá.
     */
    private static function withActor(array $attributes): array
    {
        return $attributes + [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
        ];
    }

    private static function valueFields(array $fields): array
    {
        $result = [];
        foreach (self::VALUE_FIELDS as $field) {
            $result[$field] = $fields[$field] ?? null;
        }

        return $result;
    }

    private static function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }
}
