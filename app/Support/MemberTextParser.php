<?php

namespace App\Support;

use Carbon\Carbon;

class MemberTextParser
{
    private const PATTERN = '/(?:Mã đơn hàng|Mã ĐH):\s*(.*?)\s*(?:Tên sản phẩm|Sản phẩm):\s*(.*?)\s*Email:\s*(.*?)\s*(?:Khu vực bạn sống:\s*(.*?)\s*)?Ngày mua:\s*(.*)$/ui';

    private const DATE_FORMATS = ['H:i:s d/m/Y', 'd/m/Y H:i:s', 'd/m/Y'];

    public static function parse(?string $input): array
    {
        $input = trim((string) $input);

        $empty = [
            'order_code' => null,
            'product_name' => null,
            'email' => null,
            'region' => null,
            'purchase_date' => null,
            'raw_text' => null,
        ];

        if ($input === '') {
            return $empty;
        }

        if ($input[0] === '{') {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return [
                    'order_code' => $decoded['order_code'] ?? null,
                    'product_name' => $decoded['product_name'] ?? null,
                    'email' => isset($decoded['email']) ? strtolower(trim((string) $decoded['email'])) : null,
                    'region' => $decoded['region'] ?? null,
                    'purchase_date' => self::normalizeDate($decoded['purchase_date'] ?? null),
                    'raw_text' => $input,
                ];
            }
        }

        if (preg_match(self::PATTERN, $input, $matches)) {
            return [
                'order_code' => trim($matches[1]) ?: null,
                'product_name' => trim($matches[2]) ?: null,
                'email' => trim($matches[3]) !== '' ? strtolower(trim($matches[3])) : null,
                'region' => trim($matches[4] ?? '') ?: null,
                'purchase_date' => self::normalizeDate(trim($matches[5])),
                'raw_text' => $input,
            ];
        }

        return array_merge($empty, ['raw_text' => $input]);
    }

    private static function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
