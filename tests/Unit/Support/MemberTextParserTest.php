<?php

namespace Tests\Unit\Support;

use App\Support\MemberTextParser;
use PHPUnit\Framework\TestCase;

class MemberTextParserTest extends TestCase
{
    public function test_parses_legacy_labeled_text_format(): void
    {
        $input = "Mã đơn hàng: DH123\nTên sản phẩm: YouTube Premium 12 tháng\nEmail: Member@Example.com\nKhu vực bạn sống: Ha Noi\nNgày mua: 15/03/2026";

        $result = MemberTextParser::parse($input);

        $this->assertSame('DH123', $result['order_code']);
        $this->assertSame('YouTube Premium 12 tháng', $result['product_name']);
        $this->assertSame('member@example.com', $result['email']);
        $this->assertSame('Ha Noi', $result['region']);
        $this->assertSame('2026-03-15', $result['purchase_date']);
        $this->assertSame($input, $result['raw_text']);
    }

    public function test_parses_json_object_input(): void
    {
        $json = json_encode([
            'order_code' => 'DH456',
            'product_name' => 'YouTube Premium 6 thang',
            'email' => 'Json@Example.com',
            'region' => 'HCM',
            'purchase_date' => '01/01/2026',
        ]);

        $result = MemberTextParser::parse($json);

        $this->assertSame('DH456', $result['order_code']);
        $this->assertSame('json@example.com', $result['email']);
        $this->assertSame('2026-01-01', $result['purchase_date']);
        $this->assertSame($json, $result['raw_text']);
    }

    public function test_parses_short_labeled_text_format(): void
    {
        $input = "Mã ĐH: 1040749\nSản phẩm: Nâng Cấp Youtube Premium & YouTube Music 6 Tháng x 1\nEmail: liumarik4420@gmail.com\nKhu vực bạn sống: Hà Nội\nNgày mua: 08:28:04 13/04/2026";

        $result = MemberTextParser::parse($input);

        $this->assertSame('1040749', $result['order_code']);
        $this->assertSame('Nâng Cấp Youtube Premium & YouTube Music 6 Tháng x 1', $result['product_name']);
        $this->assertSame('liumarik4420@gmail.com', $result['email']);
        $this->assertSame('Hà Nội', $result['region']);
        $this->assertSame('2026-04-13', $result['purchase_date']);
        $this->assertSame($input, $result['raw_text']);
    }

    public function test_falls_back_to_raw_text_when_unparseable(): void
    {
        $result = MemberTextParser::parse('just some random pasted note');

        $this->assertNull($result['order_code']);
        $this->assertNull($result['email']);
        $this->assertSame('just some random pasted note', $result['raw_text']);
    }

    public function test_empty_input_returns_all_null(): void
    {
        $result = MemberTextParser::parse('');

        $this->assertNull($result['order_code']);
        $this->assertNull($result['raw_text']);
    }

    public function test_unparseable_date_is_dropped_but_other_fields_kept(): void
    {
        $input = "Mã đơn hàng: DH789\nTên sản phẩm: YouTube\nEmail: a@example.com\nKhu vực bạn sống: Da Nang\nNgày mua: not-a-date";

        $result = MemberTextParser::parse($input);

        $this->assertSame('DH789', $result['order_code']);
        $this->assertNull($result['purchase_date']);
    }
}
