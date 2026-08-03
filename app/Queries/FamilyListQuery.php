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
            ->selectRaw("MIN(COALESCE(DATE_ADD(purchase_date, INTERVAL (CASE WHEN product_name LIKE '%6%' THEN 6 ELSE 12 END) MONTH), '9999-12-31')) as family_empty_date")
            ->selectRaw('COUNT(*) as member_count')
            ->groupBy('family_id');

        $query = Family::query()
            ->with('members')
            ->leftJoinSub($aggregates, 'member_aggregates', 'member_aggregates.family_id', '=', 'families.id')
            ->selectRaw('families.*')
            ->selectRaw("COALESCE(member_aggregates.family_empty_date, '9999-12-31') as family_empty_date")
            ->selectRaw('COALESCE(member_aggregates.member_count, 0) as member_count')
            ->selectRaw("CASE
                WHEN families.auto_payment_day IS NULL THEN '9999-12-31'
                ELSE
                    DATE_ADD(
                        DATE_ADD(
                            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
                            INTERVAL (LEAST(families.auto_payment_day, DAY(LAST_DAY(CURDATE()))) - 1) DAY
                        ),
                        INTERVAL COALESCE(families.monthly_payment, 0) MONTH
                    )
            END as next_payment_date");

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
