<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuildCollaboratorsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_guild_collaborators_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('guild_collaborators'));
        $this->assertTrue(Schema::hasColumns('guild_collaborators', ['id', 'content', 'status']));
    }

    public function test_status_defaults_to_active(): void
    {
        $id = DB::table('guild_collaborators')->insertGetId([
            'content' => 'Huong dan dang ky',
        ]);

        $row = DB::table('guild_collaborators')->find($id);

        $this->assertSame('active', $row->status);
    }
}
