<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_the_collaborator(): void
    {
        $user = User::factory()->create();
        $collaborator = Collaborator::create(['content' => 'Nội dung', 'status' => 'active']);

        $response = $this->actingAs($user)->delete("/collaborators/{$collaborator->id}");

        $response->assertRedirect('/collaborators');
        $this->assertDatabaseMissing('guild_collaborators', ['id' => $collaborator->id]);
    }
}
