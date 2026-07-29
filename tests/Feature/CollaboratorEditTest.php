<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $collaborator = Collaborator::create(['content' => 'Nội dung', 'status' => 'active']);

        $response = $this->get("/collaborators/{$collaborator->id}/edit");

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_prefilled_edit_form(): void
    {
        $user = User::factory()->create();
        $collaborator = Collaborator::create(['content' => '<p>Nội dung hiện tại</p>', 'status' => 'inactive']);

        $response = $this->actingAs($user)->get("/collaborators/{$collaborator->id}/edit");

        $response->assertOk();
        $response->assertSee('Nội dung hiện tại', false);
    }
}
