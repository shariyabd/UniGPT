<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Guests hitting the root are redirected to the login page.
     */
    public function test_the_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    /**
     * The login page renders for guests.
     */
    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }
}
