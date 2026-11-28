<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaHijauTest extends TestCase
{
    public function test_login_page_does_not_contain_study_case_text(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('studi kasus Bapelkes DIY');
    }

    public function test_login_page_does_not_contain_uppercase_class(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('uppercase', $response->getContent());
    }

    public function test_lupa_kata_sandi_link_before_submit_button(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Find position of "Lupa kata sandi" link
        $lupa_pos = strpos($content, 'Lupa kata sandi');
        // Find position of submit button
        $button_pos = strpos($content, 'type="submit"');

        // Assert "Lupa kata sandi" appears before submit button
        $this->assertNotFalse($lupa_pos, '"Lupa kata sandi" link not found in page');
        $this->assertNotFalse($button_pos, 'Submit button not found in page');
        $this->assertLessThan($button_pos, $lupa_pos, '"Lupa kata sandi" should appear before submit button in HTML');
    }
}
