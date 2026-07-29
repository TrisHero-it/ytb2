<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/families/create');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_the_add_family_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/families/create');

        $response->assertOk();
        $response->assertSee('Thêm family');
    }

    public function test_create_form_wires_member_email_duplicate_check(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/families/create');

        $response->assertOk();
        $response->assertSee('checkMemberEmailDuplicate', false);
        $response->assertSee('data-member-email-warning', false);
    }
}
