<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_a_collaborator(): void
    {
        $user = User::factory()->create();
        $collaborator = Collaborator::create(['content' => 'Cũ', 'status' => 'active']);

        $response = $this->actingAs($user)->put("/collaborators/{$collaborator->id}", [
            'content' => '<p>Mới</p>',
            'status' => 'inactive',
        ]);

        $response->assertRedirect('/collaborators');
        $this->assertDatabaseHas('guild_collaborators', ['id' => $collaborator->id, 'content' => '<p>Mới</p>', 'status' => 'inactive']);
    }

    public function test_validation_fails_without_content(): void
    {
        $user = User::factory()->create();
        $collaborator = Collaborator::create(['content' => 'Cũ', 'status' => 'active']);

        $response = $this->actingAs($user)->put("/collaborators/{$collaborator->id}", ['status' => 'active']);

        $response->assertSessionHasErrors(['content']);
    }
}
