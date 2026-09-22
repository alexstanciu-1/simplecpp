<?php
declare(strict_types=1);

/*
 * Role: Accept plans against fixed lifetime and backend inputs.
 * Used by: Lowerer::finalize()
 * Call map:
 *   Lowering_Join::join()
 *     -> [action] validate task provenance and assemble Lowered_Set
 */

namespace lower;

use prepare_backend\Backend_Context;

use analyze_lifetimes\Lifetime_Set;

/** @compiler-internal Accept lowered plans against current lifetime and backend inputs. */
class Lowering_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<lowering_input> $tasks
     */
    public function __construct(
        private readonly Lifetime_Set $lifetimes,
        private readonly Backend_Context $backend,
        private readonly Lowered_Set $previous,
        private readonly array $tasks
    )
    {
    }

    /**
     * @compiler-api Coordinator acceptance; never mutates prior results or inputs.
     * Accept exactly one result per selected task, in any completion order;
     * retain valid unselected plans and exclude removed owners. Failure returns
     * no replacement set; publishing session state belongs to compile.
     * @param list<Lowered_Body> $results
     * @throws \LogicException Duplicate, incomplete or stale task/result batches.
     */
    public function join(array $results): Lowered_Set
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            $id = $task->analysis->body->callable_id;
            if ((isset($selected[$id])) || ($this->lifetimes->for_callable($id) !== $task->analysis) || ($task->backend !== $this->backend)) {
                throw new \LogicException('Duplicate or stale lowering task');
            }
            $selected[$id] = $task;
        }
        $replacements = [];
        foreach ($results as $result) {
            $id = $result->binding->callable_id;
            if ((($selected[$id] ?? null) !== $result->input) || (isset($replacements[$id]))) {
                throw new \LogicException('Unexpected, duplicate or stale lowering result');
            }
            $replacements[$id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete lowering phase');
        }
        $current = [];
        foreach ($this->lifetimes->bodies() as $analysis) {
            $result = $replacements[$analysis->body->callable_id] ?? $this->previous->for_callable($analysis->body->callable_id);
            if (($result === null) || (!$result->input->is_current($analysis, $this->backend))) {
                throw new \LogicException('Incomplete or stale lowering phase');
            }
            $current[] = $result;
        }
        return new Lowered_Set($current);
    }
}
