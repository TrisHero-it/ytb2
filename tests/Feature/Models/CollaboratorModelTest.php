<?php

namespace Tests\Feature\Models;

use App\Models\Collaborator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaborator_can_be_created_without_timestamp_columns(): void
    {
        $collaborator = Collaborator::create([
            'content' => '<p>Hướng dẫn đăng ký</p>',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('guild_collaborators', ['id' => $collaborator->id, 'content' => '<p>Hướng dẫn đăng ký</p>']);
    }

    public function test_status_defaults_to_active_when_omitted(): void
    {
        $collaborator = Collaborator::create(['content' => 'Nội dung']);

        $this->assertSame('active', $collaborator->fresh()->status);
    }

    public function test_maps_to_guild_collaborators_table(): void
    {
        $collaborator = new Collaborator();

        $this->assertSame('guild_collaborators', $collaborator->getTable());
    }
}
