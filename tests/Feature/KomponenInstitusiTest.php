<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class KomponenInstitusiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test primary button gunakan var(--color-primary) dan tanpa uppercase
     */
    public function test_primary_button_institusi_style()
    {
        $html = Blade::render('<x-primary-button>Simpan</x-primary-button>');

        $this->assertStringContainsString('bg-[var(--color-primary)]', $html);
        $this->assertStringContainsString('hover:bg-[var(--color-primary-hover)]', $html);
        $this->assertStringNotContainsString('uppercase', $html);
        $this->assertStringContainsString('text-[13px]', $html);
        $this->assertStringContainsString('focus-visible:ring-[var(--color-accent)]', $html);
    }

    /**
     * Test secondary button gunakan border var(--color-primary)
     */
    public function test_secondary_button_institusi_style()
    {
        $html = Blade::render('<x-secondary-button>Batal</x-secondary-button>');

        $this->assertStringContainsString('border-[var(--color-primary)]', $html);
        $this->assertStringContainsString('text-[var(--color-primary)]', $html);
        $this->assertStringContainsString('hover:bg-[var(--color-primary-soft)]', $html);
        $this->assertStringNotContainsString('uppercase', $html);
    }

    /**
     * Test danger button gunakan var(--color-danger)
     */
    public function test_danger_button_institusi_style()
    {
        $html = Blade::render('<x-danger-button>Hapus</x-danger-button>');

        $this->assertStringContainsString('bg-[var(--color-danger)]', $html);
        $this->assertStringContainsString('text-white', $html);
        $this->assertStringContainsString('focus-visible:ring-[var(--color-accent)]', $html);
        $this->assertStringNotContainsString('uppercase', $html);
    }

    /**
     * Test card normal gunakan border var(--color-line)
     */
    public function test_card_normal_institusi_style()
    {
        $html = Blade::render('<x-card>Konten</x-card>');

        $this->assertStringContainsString('bg-[var(--color-surface)]', $html);
        $this->assertStringContainsString('border-[var(--color-line)]', $html);
        $this->assertStringContainsString('rounded-[var(--radius-card)]', $html);
        $this->assertStringNotContainsString('shadow', $html);
    }

    /**
     * Test card accent variant gunakan border-t var(--color-accent)
     */
    public function test_card_accent_variant_institusi_style()
    {
        $html = Blade::render('<x-card variant="accent">Konten</x-card>');

        $this->assertStringContainsString('border-t-[var(--color-accent)]', $html);
        $this->assertStringContainsString('border-[var(--color-line)]', $html);
    }

    /**
     * Test status badge pending gunakan color-mix warning
     */
    public function test_status_badge_pending_institusi_style()
    {
        $html = Blade::render('<x-status-badge status="pending" />');

        $this->assertStringContainsString('color-mix(in srgb, var(--color-warning) 15%, white)', $html);
        $this->assertStringContainsString('color: var(--color-warning);', $html);
        $this->assertStringContainsString('rounded-[3px]', $html);
    }

    /**
     * Test status badge approved gunakan color-mix success
     */
    public function test_status_badge_approved_institusi_style()
    {
        $html = Blade::render('<x-status-badge status="approved" />');

        $this->assertStringContainsString('color-mix(in srgb, var(--color-success) 15%, white)', $html);
        $this->assertStringContainsString('color: var(--color-success);', $html);
    }

    /**
     * Test status badge rejected gunakan color-mix danger
     */
    public function test_status_badge_rejected_institusi_style()
    {
        $html = Blade::render('<x-status-badge status="rejected" />');

        $this->assertStringContainsString('color-mix(in srgb, var(--color-danger) 15%, white)', $html);
        $this->assertStringContainsString('color: var(--color-danger);', $html);
    }

    /**
     * Test text input institusi style
     */
    public function test_text_input_institusi_style()
    {
        $html = Blade::render('<x-text-input name="test" />');

        $this->assertStringContainsString('border-[var(--color-line)]', $html);
        $this->assertStringContainsString('rounded-[var(--radius-btn)]', $html);
        $this->assertStringContainsString('bg-[var(--color-surface)]', $html);
        $this->assertStringContainsString('focus:border-[var(--color-primary)]', $html);
        $this->assertStringContainsString('focus:ring-[var(--color-primary)]', $html);
    }

    /**
     * Test input label institusi style
     */
    public function test_input_label_institusi_style()
    {
        $html = Blade::render('<x-input-label value="Nama" />');

        $this->assertStringContainsString('font-semibold', $html);
        $this->assertStringContainsString('text-[13px]', $html);
        $this->assertStringContainsString('text-[var(--color-text)]', $html);
        $this->assertStringNotContainsString('uppercase', $html);
    }

    /**
     * Test flash success gunakan color-mix success
     */
    public function test_flash_success_institusi_style()
    {
        session()->flash('success', 'Berhasil disimpan');
        $html = Blade::render('<x-flash type="success" />');

        $this->assertStringContainsString('border-[var(--color-success)]', $html);
        $this->assertStringContainsString('color-mix(in srgb, var(--color-success) 10%, white)', $html);
        $this->assertStringContainsString('rounded-[var(--radius-card)]', $html);
    }

    /**
     * Test modal panel gunakan border var(--color-line)
     */
    public function test_modal_institusi_style()
    {
        $html = Blade::render('<x-modal name="test">Konten</x-modal>');

        $this->assertStringContainsString('bg-[var(--color-surface)]', $html);
        $this->assertStringContainsString('border-[var(--color-line)]', $html);
        $this->assertStringContainsString('rounded-[var(--radius-card)]', $html);
        $this->assertStringContainsString('color-mix(in srgb, var(--color-text) 50%, transparent)', $html);
    }

    /**
     * Test modal JS selector tidak rusak — satu backslash per tanda kutip
     */
    public function test_modal_selector_js_tidak_rusak()
    {
        $html = Blade::render('<x-modal name="test">K</x-modal>');

        $this->assertStringContainsString("input:not([type=\\'hidden\\'])", $html);
        $this->assertStringNotContainsString("\\\\'hidden", $html);
    }
}
