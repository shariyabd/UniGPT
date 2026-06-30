<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class EmailSettingsController extends Controller
{
    public function index(): Response
    {
        $saved = (array) Setting::get('mail', []);

        // Effective values come from config (which the service provider has
        // already overlaid with stored settings). The SMTP password is NEVER
        // sent to the client — only a boolean indicating whether it is stored.
        return Inertia::render('Admin/EmailSettings', [
            'settings' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => (int) config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.scheme'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'mailerOptions' => [
                ['value' => 'smtp', 'label' => 'SMTP'],
                ['value' => 'log', 'label' => 'Log (write to laravel.log, no delivery)'],
            ],
            'encryptionOptions' => [
                ['value' => 'tls', 'label' => 'TLS'],
                ['value' => 'ssl', 'label' => 'SSL'],
                ['value' => '', 'label' => 'None'],
            ],
            'status' => [
                'passwordStored' => ! empty($saved['password']),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mailer' => ['required', 'in:smtp,log'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'in:tls,ssl,'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'remove_password' => ['nullable', 'boolean'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = (array) Setting::get('mail', []);

        // The SMTP password is write-only: store encrypted only when a new value
        // is submitted; a blank field keeps the current password; an explicit
        // remove clears it.
        $removePassword = (bool) ($validated['remove_password'] ?? false);
        unset($validated['remove_password']);

        if (! empty($validated['password'])) {
            $validated['password'] = encrypt(trim($validated['password']));
        } else {
            unset($validated['password']);
        }

        $merged = array_merge($existing, $validated);

        if ($removePassword) {
            unset($merged['password']);
        }

        Setting::put('mail', $merged);

        return back()->with('success', 'Email settings saved.');
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email'],
        ]);

        try {
            Mail::raw(
                "This is a test email from UniGPT.\n\nIf you received this, your email configuration is working correctly.",
                function ($message) use ($validated) {
                    $message->to($validated['to'])
                        ->subject('UniGPT — Email configuration test');
                }
            );

            return response()->json([
                'available' => true,
                'message' => 'Test email sent to '.$validated['to'].'.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'available' => false,
                'message' => 'Failed to send: '.$e->getMessage(),
            ], 422);
        }
    }
}
