<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
    }

    /** Bill cho phép cả PDF và Word, render bằng <img> thì ra ảnh vỡ. */
    public function test_a_pdf_bill_is_shown_as_a_link_not_an_image(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->bill_of_master = json_encode(['anh.jpg', 'hoa-don.pdf']);
        $family->save();

        $html = $this->actingAs($user)->get("/families/{$family->id}/edit")->assertOk()->getContent();

        $this->assertStringContainsString('<img src="http://localhost/storage/bills/anh.jpg"', $html);
        $this->assertStringNotContainsString('<img src="http://localhost/storage/bills/hoa-don.pdf"', $html);
        $this->assertStringContainsString('href="http://localhost/storage/bills/hoa-don.pdf"', $html);
    }

    /**
     * File quá lớn hoặc sai định dạng từng bị bỏ qua không một lời nào: người
     * dùng thấy báo thành công nhưng bill không hề được lưu.
     */
    public function test_an_oversized_bill_is_rejected_with_a_message(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->put("/families/{$family->id}", [
            'email' => $family->email, 'user' => $family->user,
            'number_bank' => $family->number_bank, 'name_bank' => $family->name_bank,
            'bill_of_master' => [UploadedFile::fake()->image('to-qua.jpg')->size(11 * 1024)],
        ]);

        $response->assertSessionHasErrors('bill_of_master.0');
        $this->assertSame('Bill gốc không được lớn hơn 10MB.', session('errors')->first('bill_of_master.0'));
        $this->assertNull($family->fresh()->bill_of_master);
    }

    public function test_a_bill_with_a_disallowed_type_is_rejected_with_a_message(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->put("/families/{$family->id}", [
            'email' => $family->email, 'user' => $family->user,
            'number_bank' => $family->number_bank, 'name_bank' => $family->name_bank,
            'bill_of_master' => [UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')],
        ]);

        $response->assertSessionHasErrors('bill_of_master.0');
        $this->assertNull($family->fresh()->bill_of_master);
    }

    public function test_an_oversized_quick_pay_bill_is_rejected_before_anything_is_recorded(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'months' => 1,
            'bill_payment' => [UploadedFile::fake()->image('to-qua.jpg')->size(11 * 1024)],
        ]);

        $response->assertSessionHasErrors('bill_payment.0');
        $this->assertNull($family->fresh()->payment_at);
        $this->assertDatabaseCount('history_joining_family', 0);
    }

    public function test_a_normal_bill_still_goes_through(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'months' => 1,
            'bill_payment' => [UploadedFile::fake()->image('bill.jpg')],
        ]);

        $this->assertCount(1, json_decode($family->fresh()->bill_payment, true));
    }
}
