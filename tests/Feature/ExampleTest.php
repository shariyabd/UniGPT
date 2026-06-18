<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root serves the public marketing landing page (no auth required).
     */
    public function test_the_root_renders_the_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Landing', false));
    }

    /**
     * The login page renders for guests.
     */
    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }
}
