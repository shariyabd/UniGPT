<?php

// app/Http/Controllers/LegalController.php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Display terms of service
     */
    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', [
            'last_updated' => '2024-01-01',
            'version' => '1.0',
        ]);
    }

    /**
     * Display privacy policy
     */
    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', [
            'last_updated' => '2024-01-01',
            'version' => '1.0',
            'contact_email' => 'privacy@university.edu',
        ]);
    }
}
