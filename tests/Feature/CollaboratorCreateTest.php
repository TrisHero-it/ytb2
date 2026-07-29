<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/collaborators/create');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_the_add_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/collaborators/create');

        $response->assertOk();
        $response->assertSee('Thêm form hướng dẫn');
        $response->assertSee('cdn.ckeditor.com', false);
    }
}
