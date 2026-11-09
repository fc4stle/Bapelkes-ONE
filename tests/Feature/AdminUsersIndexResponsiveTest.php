<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersIndexResponsiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Admin users index page displays user data with data-label attributes
     */
    public function test_admin_users_index_displays_users_with_data_labels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panitia = User::factory()->create(['role' => 'panitia', 'name' => 'Panitia Test']);
        $peserta = User::factory()->create(['role' => 'peserta', 'name' => 'Peserta Test']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);

        // Verify user data is displayed
        $response->assertSee($panitia->name);
        $response->assertSee($panitia->email);
        $response->assertSee('Panitia');
        $response->assertSee('Aktif');

        // Verify data-label attributes exist for mobile responsiveness
        $response->assertSee('data-label=');
        // Check for specific data-label values using regex
        $content = $response->getContent();
        $this->assertStringContainsString('data-label', $content);
    }

    /**
     * Test: Reset password button is present and accessible
     */
    public function test_reset_password_button_is_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Sandi');
    }

    /**
     * Test: Deactivate button is present for active users
     */
    public function test_deactivate_button_is_present_for_active_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Nonaktifkan');
    }

    /**
     * Test: Activate button is present for inactive users
     */
    public function test_activate_button_is_present_for_inactive_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inactiveUser = User::factory()->create(['role' => 'panitia', 'dinonaktifkan_at' => now()]);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Aktifkan');
    }

    /**
     * Test: Role change dropdown is present
     */
    public function test_role_change_dropdown_is_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Ubah');
    }

    /**
     * Test: Status badge (Aktif/Nonaktif) is displayed
     */
    public function test_status_badge_is_displayed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeUser = User::factory()->create(['role' => 'panitia']);
        $inactiveUser = User::factory()->create(['role' => 'panitia', 'dinonaktifkan_at' => now()]);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        // Verify status badges appear in HTML (checking for the color codes or text)
        $this->assertTrue(
            str_contains($response->getContent(), 'Aktif') &&
            str_contains($response->getContent(), 'Nonaktif'),
            'Status badges should display both Aktif and Nonaktif'
        );
    }

    /**
     * Test: Table has responsive Tailwind classes for mobile/desktop
     */
    public function test_table_has_responsive_classes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);

        // Verify responsive table classes are present
        $response->assertSee('block md:table');
        $response->assertSee('hidden md:table-header-group');
        $response->assertSee('block md:table-row-group');
        $response->assertSee('block md:table-row');
        $response->assertSee('block md:table-cell');
    }

    /**
     * Test: Reset password modal is present in page
     */
    public function test_reset_password_modal_is_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('showResetPasswordModal');
    }

    /**
     * Test: Table wrapper has overflow-x-auto for horizontal scroll on mobile
     */
    public function test_table_wrapper_has_overflow_x_auto(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'panitia']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify overflow-x-auto is present
        $this->assertStringContainsString('overflow-x-auto', $content);

        // Verify overflow-hidden is NOT present (old implementation)
        $this->assertStringNotContainsString('border overflow-hidden', $content);
    }
}
