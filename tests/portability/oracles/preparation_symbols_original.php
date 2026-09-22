<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Readable, reversible symbol spelling from complete identity components. */
final class Baseline_Symbols
{
    /**
     * Encode the complete ordered identity as one reversible LLVM/C link name.
     * @param list<string> $components
     */
    public static function name(array $components): string
    {
        if (($components === []) || !array_is_list($components)) {
            throw new \InvalidArgumentException('Symbol identity requires an ordered component list');
        }
        $encoded = [];
        foreach ($components as $component) {
            if (!is_string($component)) {
                throw new \InvalidArgumentException('Symbol identity components must be strings');
            }
            $encoded[] = self::component($component);
        }
        return 'rp_' . implode('_X_', $encoded);
    }

    /** Append a fact name to an already encoded type identity. */
    public static function append(string $prefix, string $component): string
    {
        return $prefix . '_X_' . self::component($component);
    }

    private static function component(string $value): string
    {
        // No Unicode/locale character classes: non-ASCII UTF-8 bytes are each
        // escaped. Literal underscores cannot introduce escape/separator tokens.
        return preg_replace_callback('/[^A-Za-z0-9]/', static function (array $match): string {
            return ($match[0] === '_') ? '__' : sprintf('_x%02X_', ord($match[0]));
        }, $value);
    }
}
