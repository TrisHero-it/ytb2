<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_toward_the_families_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/families');
    }
}
