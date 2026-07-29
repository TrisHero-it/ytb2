<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_collaborator_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collaborators', [
            'content' => '<p>Hướng dẫn mới</p>',
            'status' => 'active',
        ]);

        $response->assertRedirect('/collaborators');
        $this->assertDatabaseHas('guild_collaborators', ['content' => '<p>Hướng dẫn mới</p>', 'status' => 'active']);
    }

    public function test_validation_fails_without_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collaborators', ['status' => 'active']);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collaborators', ['content' => 'Nội dung', 'status' => 'bogus']);

        $response->assertSessionHasErrors(['status']);
    }
}
