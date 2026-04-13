<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoCenterInfographicsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_infographics_page(): void
    {
        $response = $this->get(route('info-center.infographics'));

        $response->assertRedirect(route('login'));
    }

    public function test_applicant_can_open_infographics_page(): void
    {
        $user = User::factory()->create([
            'role' => 'applicant',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('info-center.infographics'));

        $response->assertOk();
    }

    public function test_admin_can_open_infographics_page(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('info-center.infographics'));

        $response->assertOk();
    }

    public function test_superadmin_can_open_infographics_page(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('info-center.infographics'));

        $response->assertOk();
    }
}
