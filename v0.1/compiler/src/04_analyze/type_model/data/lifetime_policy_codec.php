<?php
declare(strict_types=1);
namespace type_model;

/** Boundary vocabulary only; no inference of permissions from storage or operation availability. */
final class Lifetime_Policies {
    private static function names(string $category): array /** vector<string> */ {
        $names /** vector<string> */ = [];
        if ($category === 'copy') { $names = ['unavailable', 'value', 'construct']; }
        else if ($category === 'cleanup') { $names = ['none', 'destroy']; }
        else if ($category === 'construction') { $names = ['unavailable', 'zero', 'construct']; }
        else if ($category === 'assignment') { $names = ['unavailable', 'value', 'call']; }
        else if ($category === 'expiring') { $names = ['unavailable', 'value', 'copy', 'construct']; }
        else { throw new \InvalidArgumentException('Unknown lifetime policy category'); }
        return $names;
    }
    public static function decode(string $category, string $name): int {
        $names = Lifetime_Policies::names($category);
        $found = -1;
        for ($i /** int */ = 0; $i < q_count($names); ++$i) { if ($names[$i] === $name) { $found = $i; } }
        if ($found < 0) { throw new \InvalidArgumentException('Unknown lifetime policy'); }
        return $found;
    }
    public static function encode(string $category, int $value): string {
        $names = Lifetime_Policies::names($category);
        if (($value < 0) || ($value >= q_count($names))) { throw new \InvalidArgumentException('Unknown lifetime policy'); }
        return $names[$value];
    }
}
