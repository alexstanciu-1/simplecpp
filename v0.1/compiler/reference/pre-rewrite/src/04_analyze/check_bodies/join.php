<?php
declare(strict_types=1);

/*
 * Role: Accept checked bodies against fixed semantic inputs.
 * Used by: Body_Checker::finalize()
 * Call map:
 *   Body_Join::join()
 *     -> Body_Validity::is_current()
 */

namespace check_bodies;

use collect_symbols\Symbol_Store;
use resolve_symbols\Resolution_Set;
use resolve_types\Type_Resolution;

/** @compiler-internal Accept checked bodies and retain bodies with current dependencies. */
class Body_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<body_check_task> $tasks
     */
    public function __construct(
        private readonly Symbol_Store $symbols,
        private readonly Resolution_Set $names,
        private readonly Type_Resolution $types,
        private readonly Body_Set $previous,
        private readonly array $tasks
    )
    {
    }

    /**
     * @compiler-api Validate one result per selected owner and every retained dependency. Return a
     * separate current body set, excluding removed/nonparticipating owners. Bad/incomplete
     * batches throw without changing inputs; no lifetime or backend readiness implied.
     * @param list<Checked_Body> $results
     */
    public function join(array $results): Body_Set
    {
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $owner = $task->owner;
            if (($owner->body_node_id === 0) || isset($selected[$task->callable_id]) || !$this->symbols->contains($owner->symbol_id)
                || ($this->symbols->symbol_by_id($owner->symbol_id) !== $owner)
                || ($task->names !== $this->names->for_symbol($owner->symbol_id)) || ($task->types !== $this->types)
                || (($this->types->for_callable($task->callable_id) === null)
                    || ($this->types->for_callable($task->callable_id)->instance !== $task->instance))) {
                throw new \LogicException('Duplicate or stale body task');
            }
            $selected[$task->callable_id] = $task;
        }
        $replacements = [];
        foreach ($results as $result)
        {
            $id = $result->callable_id;
            if (((($selected[$id] ?? null)?->owner !== $result->owner) || (($selected[$id] ?? null)?->instance !== $result->instance)) || (isset($replacements[$id]))
                || (!Body_Validity::is_current($result, $result->owner, $this->names, $this->types, $result->instance))) {
                throw new \LogicException('Unexpected, duplicate or stale body result');
            }
            $replacements[$id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete body phase');
        }
        $bodies = [];
        foreach ($this->types->body_signatures() as $signature)
        {
            $symbol = $this->symbols->symbol_by_id($signature->symbol_id);
            $body = $replacements[$signature->callable_id] ?? $this->previous->for_callable($signature->callable_id);
            if (!Body_Validity::is_current($body, $symbol, $this->names, $this->types, $signature->instance)) {
                throw new \LogicException('Incomplete or stale body phase');
            }
            $bodies[] = $body;
        }
        return new Body_Set($bodies);
    }
}
