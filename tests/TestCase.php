<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createPeserta()
    {
        return User::factory()->create(['role' => 'peserta']);
    }

    protected function createPanitia()
    {
        return User::factory()->create(['role' => 'panitia']);
    }

    protected function createAdmin()
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
