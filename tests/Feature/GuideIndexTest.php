<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_the_guide_page_without_login(): void
    {
        $response = $this->get('/guide');

        $response->assertOk();
    }

    public function test_shows_active_collaborator_content_unescaped(): void
    {
        Collaborator::create(['content' => '<p>Hướng dẫn công khai</p>', 'status' => 'active']);

        $response = $this->get('/guide');

        $response->assertOk();
        $response->assertSee('Hướng dẫn công khai', false);
    }

    public function test_does_not_show_inactive_collaborator_content(): void
    {
        Collaborator::create(['content' => '<p>Nội dung đã ẩn</p>', 'status' => 'inactive']);

        $response = $this->get('/guide');

        $response->assertOk();
        $response->assertDontSee('Nội dung đã ẩn');
    }

    public function test_shows_empty_state_when_no_active_collaborators(): void
    {
        Collaborator::create(['content' => 'Đã ẩn', 'status' => 'inactive']);

        $response = $this->get('/guide');

        $response->assertOk();
        $response->assertSee('Chưa có nội dung hướng dẫn');
    }

    public function test_shows_nav_links_to_admin_pages(): void
    {
        $response = $this->get('/guide');

        $response->assertOk();
        $response->assertSee(route('families.index'), false);
        $response->assertSee(route('families.create'), false);
        $response->assertSee(route('collaborators.index'), false);
        $response->assertSee(route('collaborators.create'), false);
    }

    public function test_shows_hero_and_footer_content(): void
    {
        $response = $this->get('/guide');

        $response->assertOk();
        $response->assertSee(asset('images/ytb-pre-logo.webp'), false);
        $response->assertSee('Cần hỗ trợ?');
    }
}
