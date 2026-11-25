<?php
declare(strict_types=1);

/*
 * Role: Accept current declaration/body name-resolution results.
 * Used by: Symbol_Resolver::finalize()
 * Call map:
 *   Resolution_Join::join()
 *     -> Resolution_Validity::is_current(); Binding_Coverage::complete()
 */

namespace resolve_symbols;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;

/** @compiler-internal Accept declaration/body name bindings and retain current resolutions. */
class Resolution_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<symbol_record> $tasks
     */
    public function __construct(
        private readonly Resolution_Set $previous,
        private readonly Symbol_Store $symbols,
        private readonly array $tasks,
        private readonly \type_model\Type_Catalog $catalog
    )
    {
    }

    /**
     * @compiler-api Accept one current result per selected owner in any arrival order; validate retained
     * bindings, omit removed owners and return a separate set. Bad/incomplete batches throw.
     * Join only complete selected results, then retain valid unchanged owners.
     * Membership comes from the candidate store, so removed results disappear.
     * @param list<Symbol_Resolution> $results
     */
    public function join(array $results): Resolution_Set
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            if (($task->frontend === null) || (isset($selected[$task->symbol_id])) || (!$this->symbols->contains($task->symbol_id))
                || ($this->symbols->symbol_by_id($task->symbol_id) !== $task)) {
                throw new \LogicException('Duplicate, removed or stale resolution task');
            }
            $selected[$task->symbol_id] = $task;
        }
        $replacements = [];
        foreach ($results as $result)
        {
            $owner = $selected[$result->symbol_id] ?? null;
            if (($owner === null) || (isset($replacements[$result->symbol_id]))
                || (!Resolution_Validity::is_current($result, $owner, $this->symbols, $this->catalog))
                || !Binding_Coverage::complete($result, $owner)) {
                throw new \LogicException('Unexpected, duplicate or stale resolution result');
            }
            $replacements[$result->symbol_id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete resolution phase');
        }
        $current = [];
        foreach ($this->symbols->resolution_records() as $symbol) {
            $result = $replacements[$symbol->symbol_id] ?? $this->previous->for_symbol($symbol->symbol_id);
            if (($result === null) || ((!isset($replacements[$symbol->symbol_id])) && (!Resolution_Validity::is_current($result, $symbol, $this->symbols, $this->catalog)))) {
                throw new \LogicException('Incomplete or stale resolution phase');
            }
            $current[] = $result;
        }
        return new Resolution_Set($current, $this->symbols);
    }
}
