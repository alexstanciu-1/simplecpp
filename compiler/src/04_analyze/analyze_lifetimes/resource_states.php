<?php
declare(strict_types=1);
namespace analyze_lifetimes;
const RESOURCE_EMPTY = 1;
const RESOURCE_OWNED = 2;
const RESOURCE_EITHER = 3;
const RESOURCE_IDENTITY = 9;
const RESOURCE_EMPTY_VALUE = 5;
const RESOURCE_OWNED_VALUE = 10;
/** Four-bit relation: low lane describes empty input, high lane describes owned input.
 * Lanes are output sets 0..3, not execution paths. Arithmetic selects the two lanes
 * without unsupported PHP bitwise syntax or temporary containers.
 */
final class Resource_States {
    private static function require_relation(int $state): void {
        if (($state < 0) || ($state > 15)) { throw new \InvalidArgumentException('Invalid resource relation'); }
    }
    private static function accepts(int $outputs, int $required): bool {
        return ($outputs !== 0) && (($outputs === $required) || ($required === \analyze_lifetimes\RESOURCE_EITHER));
    }
    public static function compatible(int $state, int $required): int {
        Resource_States::require_relation($state);
        if (($required < 0) || ($required > 3)) { throw new \InvalidArgumentException('Invalid resource state mask'); }
        $accepted = 0;
        if (Resource_States::accepts($state % 4,$required)) { $accepted = 1; }
        if (Resource_States::accepts((int)($state / 4),$required)) { $accepted = $accepted + 2; }
        return $accepted;
    }
    private static function transfer(int $input, int $empty, int $owned): int {
        $output = 0;
        if ($input === \analyze_lifetimes\RESOURCE_EMPTY) { $output = $empty; }
        else if ($input === \analyze_lifetimes\RESOURCE_OWNED) { $output = $owned; }
        else if ($input === \analyze_lifetimes\RESOURCE_EITHER) {
            if ($empty === 0) { $output = $owned; }
            else if (($owned === 0) || ($empty === $owned)) { $output = $empty; }
            else { $output = \analyze_lifetimes\RESOURCE_EITHER; }
        }
        return $output;
    }
    public static function compose(int $state, int $transition): int {
        Resource_States::require_relation($state); Resource_States::require_relation($transition);
        $empty = $transition % 4; $owned = (int)($transition / 4);
        return Resource_States::transfer($state % 4,$empty,$owned) + 4 * Resource_States::transfer((int)($state / 4),$empty,$owned);
    }
    public static function deterministic(int $state): int {
        Resource_States::require_relation($state); $empty = $state % 4; $owned = (int)($state / 4); $accepted = 0;
        if (($empty === \analyze_lifetimes\RESOURCE_EMPTY) || ($empty === \analyze_lifetimes\RESOURCE_OWNED)) { $accepted = 1; }
        if (($owned === \analyze_lifetimes\RESOURCE_EMPTY) || ($owned === \analyze_lifetimes\RESOURCE_OWNED)) { $accepted = $accepted+2; }
        return $accepted;
    }
}
