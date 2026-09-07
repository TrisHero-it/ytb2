<?php

namespace Tests\Feature\Queries;

use App\Models\Family;
use App\Queries\FamilyListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyListQueryTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ], $overrides));
    }

    public function test_search_matches_member_order_code(): void
    {
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'DH999', 'email' => 'a@example.com']);
        $other = $this->makeFamily(['email' => 'other@example.com']);
        $other->members()->create(['order_code' => 'DHXXX', 'email' => 'b@example.com']);

        $result = (new FamilyListQuery)->paginate(search: 'DH999');

        $this->assertCount(1, $result->items());
        $this->assertSame($family->id, $result->items()[0]->id);
    }

    public function test_search_matches_member_email(): void
    {
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'DH1', 'email' => 'findme@example.com']);
        $this->makeFamily(['email' => 'other@example.com']);

        $result = (new FamilyListQuery)->paginate(search: 'findme');

        $this->assertCount(1, $result->items());
    }

    public function test_member_count_reflects_number_of_members(): void
    {
        $family = $this->makeFamily();
        $family->members()->create(['email' => 'a@example.com']);
        $family->members()->create(['email' => 'b@example.com']);

        $result = (new FamilyListQuery)->paginate();

        $this->assertSame(2, (int) $result->items()[0]->member_count);
    }

    public function test_family_with_no_members_has_zero_member_count_and_default_empty_date(): void
    {
        $this->makeFamily();

        $result = (new FamilyListQuery)->paginate();

        $this->assertSame(0, (int) $result->items()[0]->member_count);
        $this->assertSame('9999-12-31', substr($result->items()[0]->family_empty_date, 0, 10));
    }

    public function test_sort_family_empty_orders_by_earliest_member_expiry(): void
    {
        $soonFamily = $this->makeFamily(['email' => 'soon@example.com']);
        $soonFamily->members()->create(['product_name' => 'YouTube Premium 6 thang', 'purchase_date' => now()->subMonths(5)->toDateString(), 'email' => 'a@example.com']);

        $laterFamily = $this->makeFamily(['email' => 'later@example.com']);
        $laterFamily->members()->create(['product_name' => 'YouTube Premium 12 thang', 'purchase_date' => now()->toDateString(), 'email' => 'b@example.com']);

        $result = (new FamilyListQuery)->paginate(sort: FamilyListQuery::SORT_FAMILY_EMPTY);

        $this->assertSame($soonFamily->id, $result->items()[0]->id);
    }

    public function test_next_payment_date_comes_from_the_next_payment_at_column(): void
    {
        $this->makeFamily(['next_payment_at' => '2026-10-04']);

        $result = (new FamilyListQuery)->paginate();

        $this->assertSame('2026-10-04', substr($result->items()[0]->next_payment_date, 0, 10));
    }

    public function test_next_payment_date_defaults_to_far_future_when_not_set(): void
    {
        $this->makeFamily();

        $result = (new FamilyListQuery)->paginate();

        $this->assertSame('9999-12-31', substr($result->items()[0]->next_payment_date, 0, 10));
    }

    public function test_sort_next_payment_orders_by_due_date_with_unset_families_last(): void
    {
        $unset = $this->makeFamily(['email' => 'unset@example.com']);
        $later = $this->makeFamily(['email' => 'later@example.com', 'next_payment_at' => '2026-12-01']);
        $sooner = $this->makeFamily(['email' => 'sooner@example.com', 'next_payment_at' => '2026-10-04']);

        $result = (new FamilyListQuery)->paginate(sort: FamilyListQuery::SORT_NEXT_PAYMENT);

        $this->assertSame(
            [$sooner->id, $later->id, $unset->id],
            array_map(fn ($family) => $family->id, $result->items()),
        );
    }

    public function test_sort_members_desc_orders_by_member_count_descending(): void
    {
        $fewer = $this->makeFamily(['email' => 'fewer@example.com']);
        $fewer->members()->create(['email' => 'a@example.com']);

        $more = $this->makeFamily(['email' => 'more@example.com']);
        $more->members()->create(['email' => 'b@example.com']);
        $more->members()->create(['email' => 'c@example.com']);

        $result = (new FamilyListQuery)->paginate(sort: FamilyListQuery::SORT_MEMBERS_DESC);

        $this->assertSame($more->id, $result->items()[0]->id);
    }

    public function test_pagination_limits_results_per_page(): void
    {
        foreach (range(1, 3) as $i) {
            $this->makeFamily(['email' => "f{$i}@example.com"]);
        }

        $result = (new FamilyListQuery)->paginate(perPage: 2, page: 1);

        $this->assertCount(2, $result->items());
        $this->assertSame(3, $result->total());
    }
}
