<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class MailConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->applyStoredSettings();
    }

    /**
     * Overlay admin-configured mail settings (stored in the settings table) on
     * top of the static config/env defaults, so SMTP credentials entered in the
     * Email Configuration admin UI take effect at runtime without touching .env.
     * The SMTP password is stored encrypted; everything is guarded so console/
     * boot before migration is safe.
     */
    private function applyStoredSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $mail = Setting::get('mail');
        } catch (Throwable) {
            return;
        }

        if (! is_array($mail)) {
            return;
        }

        $overrides = [];

        if (! empty($mail['mailer'])) {
            $overrides['mail.default'] = $mail['mailer'];
        }
        if (! empty($mail['host'])) {
            $overrides['mail.mailers.smtp.host'] = $mail['host'];
        }
        if (isset($mail['port']) && $mail['port'] !== '') {
            $overrides['mail.mailers.smtp.port'] = (int) $mail['port'];
        }
        if (array_key_exists('encryption', $mail)) {
            // '', 'null' or null all mean "no encryption"; the SMTP transport
            // reads this via the `scheme` key (tls / ssl).
            $scheme = $mail['encryption'];
            $overrides['mail.mailers.smtp.scheme'] = in_array($scheme, ['tls', 'ssl'], true) ? $scheme : null;
        }
        if (! empty($mail['username'])) {
            $overrides['mail.mailers.smtp.username'] = $mail['username'];
        }
        if (! empty($mail['password'])) {
            try {
                $overrides['mail.mailers.smtp.password'] = decrypt($mail['password']);
            } catch (Throwable) {
                // Ignore an undecryptable password (e.g. APP_KEY rotated); fall back to env.
            }
        }
        if (! empty($mail['from_address'])) {
            $overrides['mail.from.address'] = $mail['from_address'];
        }
        if (! empty($mail['from_name'])) {
            $overrides['mail.from.name'] = $mail['from_name'];
        }

        if ($overrides !== []) {
            config($overrides);
        }
    }
}
