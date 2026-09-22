<?php
declare(strict_types=1);

/*
 * Role: Accept analyses tied to exact checked bodies.
 * Used by: Lifetime_Analyzer::finalize()
 * Call map:
 *   Lifetime_Join::join()
 *     -> [action] validate selected membership and assemble Lifetime_Set
 */

namespace analyze_lifetimes;

use check_bodies\Body_Set;
use check_bodies\Checked_Body;

/** @compiler-internal Accept lifetime results against exact checked-body identities. */
class Lifetime_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<Checked_Body> $tasks
     */
    public function __construct(
        private readonly Body_Set $bodies,
        private readonly Lifetime_Set $previous,
        private readonly array $tasks,
        private readonly array $ownership = []
    )
    {
    }

    /**
     * @compiler-api Accept exactly one result per selected checked body; require exact body identity
     * for results/reuse. Return current analyses, excluding removed owners; incomplete,
     * duplicate or stale batches throw. Previous bodies/analyses remain unchanged.
     * @param list<Analyzed_Body> $results
     */
    public function join(array $results): Lifetime_Set
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            $id = $task->callable_id;
            if ((isset($selected[$id])) || ($this->bodies->for_callable($id) !== $task)) {
                throw new \LogicException('Duplicate or stale lifetime task');
            }
            $selected[$id] = $task;
        }
        $replacements = [];
        foreach ($results as $result) {
            $id = $result->body->callable_id;
            if ((($selected[$id] ?? null) !== $result->body) || (isset($replacements[$id]))) {
                throw new \LogicException('Unexpected, duplicate or stale lifetime result');
            }
            $replacements[$id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete lifetime phase');
        }
        $current = [];
        foreach ($this->bodies->bodies() as $body)
        {
            $result = $replacements[$body->callable_id] ?? $this->previous->for_callable($body->callable_id);
            if (($result?->body !== $body)
                || ($result->ownership !== ($this->ownership['body:' . $body->callable_id] ?? null))) {
                throw new \LogicException('Incomplete or stale lifetime phase');
            }
            $current[] = $result;
        }
        return new Lifetime_Set($current, $this->ownership);
    }
}
