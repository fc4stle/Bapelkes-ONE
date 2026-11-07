<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TIYASTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'peserta']));
    }

    public function test_tiyas_form_displays_progress_text(): void
    {
        $response = $this->get(route('tiyas.form'));

        $response->assertStatus(200);
        $response->assertViewIs('tiyas.form');
        $response->assertSee('dari 65 terjawab');
    }

    public function test_tiyas_form_shows_all_questions(): void
    {
        $response = $this->get(route('tiyas.form'));

        $response->assertStatus(200);
        // Check that we see question numbers from 1 to 65
        $response->assertSee('Pertanyaan 1 dari 65');
        $response->assertSee('Pertanyaan 65 dari 65');
    }

    public function test_tiyas_form_shows_radio_options(): void
    {
        $response = $this->get(route('tiyas.form'));

        $response->assertStatus(200);
        $response->assertSee('Sangat Tidak Setuju');
        $response->assertSee('Tidak Setuju');
        $response->assertSee('Setuju');
        $response->assertSee('Sangat Setuju');
    }

    public function test_tiyas_submit_returns_success_message(): void
    {
        $response = $this->post(route('tiyas.store'), [
            'jawaban' => array_fill(1, 65, 'setuju'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'TIYAS berhasil disimpan!');
    }
}
