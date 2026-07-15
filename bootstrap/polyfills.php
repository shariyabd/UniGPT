<?php

/*
|--------------------------------------------------------------------------
| Runtime polyfills
|--------------------------------------------------------------------------
|
| Loaded very early via composer's autoload "files" so every request has them.
| These fill gaps left by PHP builds that ship a partial mbstring — some
| production builds compile mbstring WITHOUT the oniguruma regex family
| (--disable-mbregex), leaving mb_split()/mb_ereg*() undefined even though the
| extension is "loaded". Laravel's Str::headline()/apa()/ucwords() call
| mb_split(), so those crash on such builds ("Call to undefined function
| mb_split()"). Local/dev builds that already provide it keep the native one.
|
*/

if (! function_exists('mb_split')) {
    /**
     * Minimal mb_split() polyfill for PHP builds without mbregex.
     *
     * Splits $string on the oniguruma pattern $pattern (no delimiters). We only
     * need whitespace-style patterns (Laravel passes '\s+'), so a UTF-8 aware
     * preg_split is an exact substitute.
     *
     * @return array<int, string>|false
     */
    function mb_split(string $pattern, string $string, int $limit = -1)
    {
        // Escape the chosen preg delimiter, then run the pattern UTF-8 aware.
        $delimited = '#'.str_replace('#', '\#', $pattern).'#u';

        return preg_split($delimited, $string, $limit < 1 ? -1 : $limit);
    }
}
