<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/collaborators');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_collaborator_list(): void
    {
        $user = User::factory()->create();
        Collaborator::create(['content' => '<p>Hướng dẫn A</p>', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/collaborators');

        $response->assertOk();
        $response->assertSee('Hướng dẫn A', false);
    }

    public function test_shows_empty_state_when_no_collaborators(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/collaborators');

        $response->assertOk();
        $response->assertSee('Chưa có dữ liệu');
    }
}
