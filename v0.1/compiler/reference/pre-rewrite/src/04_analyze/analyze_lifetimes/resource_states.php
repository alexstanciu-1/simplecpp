<?php
declare(strict_types=1);

/*
 * Role: Two-state resource transfer algebra, shared by body and complete-lifecycle analysis.
 * Used by: Allocation_Flow; Ownership_Worker
 * Call map: compatible(); compose(); deterministic()
 * Each two-bit lane describes outputs for one initial state, not an execution path.
 */
namespace analyze_lifetimes;

final class Resource_States
{
    // State masks are distinct from relations: low two bits give outputs for an
    // empty input; the next two give outputs for an owned input. Zero means no output.
    public const EMPTY = 1;
    public const OWNED = 2;
    public const EITHER = self::EMPTY | self::OWNED;
    public const IDENTITY = 9;
    public const EMPTY_VALUE = 5;
    public const OWNED_VALUE = 10;

    /** Inputs whose entire possible output set satisfies the required state mask. */
    public static function compatible(int $state, int $required): int
    {
        $accepted = 0;
        foreach ([0, 1] as $lane) {
            $value = ($state >> ($lane * 2)) & self::EITHER;
            if (($value !== 0) && (($value & $required) === $value)) {
                $accepted |= 1 << $lane;
            }
        }
        return $accepted;
    }

    /** Compose one accepted transition independently for each incoming state. */
    public static function compose(int $state, int $transition): int
    {
        $result = 0;
        foreach ([0, 1] as $lane) {
            $input = ($state >> ($lane * 2)) & self::EITHER;
            $output = (($input & self::EMPTY) ? ($transition & self::EITHER) : 0)
                | (($input & self::OWNED) ? (($transition >> 2) & self::EITHER) : 0);
            $result |= $output << ($lane * 2);
        }
        return $result;
    }

    /** Reject ambiguous poststates without multiplying incoming field combinations. */
    public static function deterministic(int $state): int
    {
        $result = 0;
        foreach ([0, 1] as $lane) {
            if (in_array(($state >> ($lane * 2)) & self::EITHER, [self::EMPTY, self::OWNED], true)) {
                $result |= 1 << $lane;
            }
        }
        return $result;
    }
}
