<?php

namespace App\Queries;

use App\Models\Family;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FamilyListQuery
{
    public const SORT_NEXT_PAYMENT = 'next_payment';

    public const SORT_FAMILY_EMPTY = 'family_empty';

    public const SORT_MEMBERS_DESC = 'members_desc';

    public const SORT_MEMBERS_ASC = 'members_asc';

    public function paginate(string $search = '', string $sort = '', int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $sort = in_array($sort, [self::SORT_NEXT_PAYMENT, self::SORT_FAMILY_EMPTY, self::SORT_MEMBERS_DESC, self::SORT_MEMBERS_ASC], true)
            ? $sort
            : self::SORT_NEXT_PAYMENT;

        $aggregates = DB::table('family_members')
            ->selectRaw('family_id')
            // Lấy con số đứng ngay trước "Tháng" trong tên sản phẩm. Cách cũ là
            // LIKE '%6%' nên "12 Tháng x 6" bị tính thành gói 6 tháng. Không đọc
            // được số nào thì coi như 12 tháng, giống chỗ hiển thị từng thành viên.
            ->selectRaw("MIN(COALESCE(DATE_ADD(purchase_date, INTERVAL COALESCE(CAST(REGEXP_SUBSTR(product_name, '[0-9]+(?= *Th)') AS UNSIGNED), 12) MONTH), '9999-12-31')) as family_empty_date")
            ->selectRaw('COUNT(*) as member_count')
            ->groupBy('family_id');

        $query = Family::query()
            ->with('members')
            ->leftJoinSub($aggregates, 'member_aggregates', 'member_aggregates.family_id', '=', 'families.id')
            ->selectRaw('families.*')
            ->selectRaw("COALESCE(member_aggregates.family_empty_date, '9999-12-31') as family_empty_date")
            ->selectRaw('COALESCE(member_aggregates.member_count, 0) as member_count')
            ->selectRaw("COALESCE(families.next_payment_at, '9999-12-31') as next_payment_date");

        $search = trim($search);
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('families.user', 'like', $term)
                    ->orWhereHas('members', function ($q) use ($term) {
                        $q->where('order_code', 'like', $term)->orWhere('email', 'like', $term);
                    });
            });
        }

        $query->orderBy(match ($sort) {
            self::SORT_FAMILY_EMPTY => 'family_empty_date',
            self::SORT_MEMBERS_DESC, self::SORT_MEMBERS_ASC => 'member_count',
            default => 'next_payment_date',
        }, $sort === self::SORT_MEMBERS_DESC ? 'desc' : 'asc')->orderBy('families.id');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
