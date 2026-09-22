<?php
declare(strict_types=1);

/*
 * Role: Query edges and reachable typed blocks.
 * Used by: Body checking, lifetime flow and lowering
 * Call map:
 *   Flow_Graph::reachable()
 *     -> Flow_Graph::successors() [each visited block]
 */

namespace check_bodies;

/** @compiler-api Graph queries over fixed checked blocks; no name, lifetime or target decisions. */
class Flow_Graph
{
    /** Walk reachable blocks and return them in deterministic statement order, independent of traversal order. */
    public static function reachable(array $blocks): array
    {
        $seen = [];
        $pending = [1];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            if (isset($seen[$id])) {
                continue;
            }
            $block = $blocks[$id - 1] ?? throw new \LogicException('Missing checked flow block');
            $seen[$id] = true;
            foreach (self::successors($block) as $target) {
                $pending[] = $target;
            }
        }
        $ids = array_keys($seen);
        usort($ids, static fn($a, $b) => [$blocks[$a - 1]->statement_start, $a] <=> [$blocks[$b - 1]->statement_start, $b]);
        return $ids;
    }

    public static function successors(typed_block $block): array
    {
        return match ($block->end) {
            flow_end::jump => [$block->first],
            flow_end::branch => [$block->first, $block->second],
            flow_end::return_exit, flow_end::fallthrough => [],
        };
    }
}
