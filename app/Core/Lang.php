<?php
declare(strict_types=1);

/**
 * English / Filipino. The interface is written in English; when Filipino is chosen, the finished
 * page is translated on the way out using app/lang/fil.php (exact text first, then patterns for text
 * that contains names or numbers). Scripts, styles and form fields that hold what people typed are left alone.
 */
final class Lang
{
    public const COOKIE = 'se_lang';
    public const SUPPORTED = ['en' => 'English', 'fil' => 'Filipino'];

    public static function current(): string
    {
        $c = (string) ($_COOKIE[self::COOKIE] ?? 'en');
        return isset(self::SUPPORTED[$c]) ? $c : 'en';
    }

    public static function set(string $lang): void
    {
        if (!isset(self::SUPPORTED[$lang])) $lang = 'en';
        $_COOKIE[self::COOKIE] = $lang;
        setcookie(self::COOKIE, $lang, ['expires' => time() + 31536000, 'path' => base_path() ?: '/', 'samesite' => 'Lax', 'secure' => is_https()]);
    }

    private static function dict(): array
    {
        static $d = null;
        return $d ??= require APP . '/lang/fil.php';
    }

    /** Translate one piece of interface text (used for text built in code, like toasts and JSON). */
    public static function text(string $s): string
    {
        if (self::current() !== 'fil') return $s;
        $d = self::dict();
        $key = trim(preg_replace('/\s+/u', ' ', $s));
        if ($key === '') return $s;
        if (isset($d['exact'][$key])) return $d['exact'][$key];
        foreach ($d['patterns'] as [$re, $to]) {
            if (preg_match($re, $key)) return (string) preg_replace($re, $to, $key);
        }
        return $s;
    }

    public static function translateHtml(string $html): string
    {
        if (self::current() !== 'fil') return $html;
        // Keep scripts, styles and textareas exactly as they are
        $parts = preg_split('#(<(script|style|textarea)\b.*?</\2>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        for ($i = 0, $n = count($parts); $i < $n; $i++) {
            $chunk = $parts[$i];
            if ($i % 3 === 1) { $out .= $chunk; continue; }   // a protected block
            if ($i % 3 === 2) continue;                      // the captured tag name
            $chunk = preg_replace_callback('/>([^<>]+)</u', function ($m) {
                $raw = $m[1];
                if (!preg_match('/\p{L}/u', $raw)) return $m[0];
                $tr = self::text(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($tr === html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')) return $m[0];
                preg_match('/^\s*/u', $raw, $lead); preg_match('/\s*$/u', $raw, $trail);
                return '>' . $lead[0] . e($tr) . $trail[0] . '<';
            }, $chunk);
            $chunk = preg_replace_callback('/\b(placeholder|aria-label|title|data-confirm)="([^"]*)"/u', function ($m) {
                $raw = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $tr = self::text($raw);
                return $tr === $raw ? $m[0] : $m[1] . '="' . e($tr) . '"';
            }, $chunk);
            $out .= $chunk;
        }
        return preg_replace('/<html lang="en"/', '<html lang="fil"', $out, 1);
    }
}
