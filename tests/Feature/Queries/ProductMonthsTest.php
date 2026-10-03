<?php

namespace Tests\Feature\Queries;

use App\Models\Family;
use App\Models\User;
use App\Queries\FamilyListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Ngày family trống" = ngày thành viên đầu tiên hết hạn. Số tháng được đọc từ
 * tên sản phẩm, trước đây bằng LIKE '%6%' nên bất kỳ chữ số 6 nào cũng thành
 * gói 6 tháng.
 */
class ProductMonthsTest extends TestCase
{
    use RefreshDatabase;

    private function familyWithProduct(string $email, string $productName): Family
    {
        $family = Family::create([
            'email' => $email, 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
        $family->members()->create([
            'order_code' => 'DH1', 'email' => 'm-'.$email,
            'product_name' => $productName, 'purchase_date' => '2026-01-15',
        ]);

        return $family;
    }

    private function emptyDateFor(Family $family): string
    {
        $row = collect((new FamilyListQuery())->paginate()->items())
            ->firstWhere('id', $family->id);

        return $row->family_empty_date;
    }

    public function test_a_six_month_product_expires_after_six_months(): void
    {
        $family = $this->familyWithProduct('six@example.com', 'Nâng Cấp Youtube Premium & YouTube Music 6 Tháng x 1');

        $this->assertSame('2026-07-15', $this->emptyDateFor($family));
    }

    public function test_a_twelve_month_product_expires_after_twelve_months(): void
    {
        $family = $this->familyWithProduct('twelve@example.com', 'Nâng Cấp Youtube Premium & YouTube Music 12 Tháng x 1');

        $this->assertSame('2027-01-15', $this->emptyDateFor($family));
    }

    /** Số lượng mua mới là số 6 ở đây, không phải số tháng. */
    public function test_a_quantity_of_six_does_not_turn_a_twelve_month_product_into_six(): void
    {
        $family = $this->familyWithProduct('qty@example.com', 'Nâng Cấp Youtube Premium 12 Tháng x 6');

        $this->assertSame('2027-01-15', $this->emptyDateFor($family));
    }

    public function test_a_product_name_with_no_month_count_falls_back_to_twelve(): void
    {
        $family = $this->familyWithProduct('unknown@example.com', 'Goi khong ro');

        $this->assertSame('2027-01-15', $this->emptyDateFor($family));
    }

    /** Thẻ family và bảng thành viên phải nói cùng một ngày hết hạn. */
    public function test_the_member_table_agrees_with_the_card(): void
    {
        $user = User::factory()->create();
        $family = $this->familyWithProduct('agree@example.com', 'Nâng Cấp Youtube Premium 12 Tháng x 6');

        $html = $this->actingAs($user)->get('/families')->assertOk()->getContent();

        $this->assertSame('2027-01-15', $this->emptyDateFor($family));
        $this->assertStringContainsString('15/01/2027', $html);
    }
}
