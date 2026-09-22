<?php
declare(strict_types=1);

/*
 * Role: Solve definite initialization at block entries.
 * Used by: Lifetime_Worker::analyze()
 * Call map:
 *   Local_Flow::entries()
 *     -> [action] converge predecessor facts over reachable blocks
 */

namespace analyze_lifetimes;

/** @compiler-internal Definite initialization at block entry; private, sparse and discarded after analysis. */
class Local_Flow
{
    /** Converge definite initialization over reachable blocks using private per-block facts. */
    public static function entries(\check_bodies\Checked_Body $body): array
    {
        $parameters = [];
        for ($id = 1; $id <= $body->entry_parameter_count(); ++$id) {
            $parameters[$id] = 0;
        }
        $entries = [1 => $parameters];
        $pending = [1];
        $queued = [1 => true];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            unset($queued[$id]);
            $block = $body->blocks[$id - 1];
            $state = $entries[$id];
            $scope = $block->scope_id;
            for ($index = $block->statement_start; $index < ($block->statement_start + $block->statement_count); ++$index)
            {
                $statement = $body->statements[$index];
                if (!self::contains($body, $scope, $statement->scope_id)) {
                    $state = self::retain($body, $state, $statement->scope_id);
                }
                $scope = $statement->scope_id;
                if ($statement->kind === \check_bodies\statement_kind::local_declaration) {
                    $state[($statement->target?->local_id ?? 0)] = $index + 1;
                }
            }
            foreach (\check_bodies\Flow_Graph::successors($block) as $target)
            {
                $next = self::retain($body, $state, $body->blocks[$target - 1]->scope_id);
                $merged = isset($entries[$target]) ? array_intersect_key($entries[$target], $next) : $next;
                if (!isset($entries[$target]) || ($entries[$target] !== $merged)) {
                    $entries[$target] = $merged;
                    if (!isset($queued[$target])) {
                        $queued[$target] = true;
                        $pending[] = $target;
                    }
                }
            }
        }
        return $entries;
    }

    /** Walk parent scopes to decide whether a binding remains visible. */
    public static function contains(\check_bodies\Checked_Body $body, int $outer, int $inner): bool
    {
        for ($id = $inner; $id !== 0; $id = $body->names->scope_for($id)->parent_scope_id) {
            if ($id === $outer) {
                return true;
            }
        }
        return false;
    }

    /** Discard local facts whose declaring scope does not contain the destination scope. */
    public static function retain(\check_bodies\Checked_Body $body, array $state, int $scope): array
    {
        foreach ($state as $id => $initialization) {
            if (!self::contains($body, $body->names->local_for($id)->scope_id, $scope)) {
                unset($state[$id]);
            }
        }
        return $state;
    }
}
