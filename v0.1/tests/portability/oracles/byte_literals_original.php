<?php
declare(strict_types=1);

/*
 * Role: Decode the supported non-interpolated PHP++ quoted byte literals.
 * Used by: Body_Worker::check_byte_literal()
 * Call map: Byte_Literals::decode() -> [action] normalize escapes without provider knowledge
 */
namespace check_bodies;

/** @compiler-internal Source quoting rules only; runtime type/constructor selection belongs to body checking. */
final class Baseline_Byte_Literals
{
    private const ESCAPES = ['n' => "\n", 'r' => "\r", 't' => "\t", 'v' => "\v", 'f' => "\f", 'e' => "\x1B", '$' => '$'];

    /** Decode literal bytes exactly; reject interpolation and unsupported Unicode escape syntax explicitly. */
    public static function decode(string $text): string
    {
        $quote = $text[0] ?? '';
        $end = strlen($text) - 1;
        if (!in_array($quote, ["'", '"'], true) || ($end < 1) || ($text[$end] !== $quote)) {
            throw new \InvalidArgumentException('Malformed quoted literal');
        }
        $bytes = '';
        for ($index = 1; $index < $end; ++$index)
        {
            $byte = $text[$index];
            if (($quote === '"') && ($byte === '$') && preg_match('/[A-Za-z_{\x80-\xFF]/', $text[$index + 1])) {
                throw new \InvalidArgumentException('String interpolation is not supported');
            }
            if ($byte !== '\\') {
                $bytes .= $byte;
                continue;
            }
            $next = $text[++$index];
            if (($next === '\\') || ($next === $quote)) {
                $bytes .= $next;
                continue;
            }
            if ($quote === "'") {
                $bytes .= '\\' . $next;
                continue;
            }

            // PHP double-quoted byte escapes; numeric escapes consume only their own digits.
            if (isset(self::ESCAPES[$next])) {
                $bytes .= self::ESCAPES[$next];
            }
            elseif (str_contains('01234567', $next)) {
                $count = min(2, strspn($text, '01234567', $index + 1, max(0, $end - $index - 1)));
                $bytes .= chr(octdec(substr($text, $index, $count + 1)) & 255);
                $index += $count;
            }
            elseif (($next === 'x') && ($index + 1 < $end) && ctype_xdigit($text[$index + 1])) {
                $count = min(2, strspn($text, '0123456789abcdefABCDEF', $index + 1, $end - $index - 1));
                $bytes .= chr(hexdec(substr($text, $index + 1, $count)));
                $index += $count;
            }
            elseif (($next === 'u') && ($text[$index + 1] === '{')) {
                throw new \InvalidArgumentException('Unicode escape syntax is not supported; use UTF-8 literal bytes');
            }
            else {
                $bytes .= '\\' . $next;
            }
        }
        return $bytes;
    }
}
