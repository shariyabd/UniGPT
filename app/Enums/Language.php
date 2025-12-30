<?php

namespace App\Enums;

enum Language: string
{
    case ENGLISH = 'en';
    case ARABIC = 'ar';
    case SPANISH = 'es';
    case FRENCH = 'fr';
    case GERMAN = 'de';
    case CHINESE = 'zh';
    case JAPANESE = 'ja';

    public function label(): string
    {
        return match($this) {
            self::ENGLISH => 'English',
            self::ARABIC => 'العربية',
            self::SPANISH => 'Español',
            self::FRENCH => 'Français',
            self::GERMAN => 'Deutsch',
            self::CHINESE => '中文',
            self::JAPANESE => '日本語',
        };
    }

    public function nativeName(): string
    {
        return $this->label();
    }
}
