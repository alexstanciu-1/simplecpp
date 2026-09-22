<?php
declare(strict_types=1);

/*
 * Role: Accept comparison results into the change catalog.
 * Used by: Symbol_Comparer::finalize()
 * Call map:
 *   Comparison_Join::join()
 *     -> [action] check task membership and retain meaningful changes
 */

namespace collect_symbols;

/** @compiler-internal Accept symbol comparison results into a complete change catalog. */
class Comparison_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<symbol_change> $tasks
     */
    public function __construct(
        private readonly Symbol_Refresh $input,
        private readonly array $tasks
    )
    {
    }

    /**
     * @compiler-api Validate the complete selected pair/result batch, then omit fully unchanged descriptions.
     * Retain changed/added/removed facts with current symbols; throw for stale/duplicate
     * or missing comparisons. An empty changes list does not mean the project is empty.
     * @param list<symbol_change> $results
     */
    public function join(array $results): Symbol_Refresh
    {
        $pairs = [];
        foreach ($this->input->changes as $change) {
            $id = ($change->current ?? $change->previous)->symbol_id;
            if (isset($pairs[$id])) {
                throw new \LogicException('Duplicate change catalog identity');
            }
            $pairs[$id] = $change;
        }
        $selected = [];
        foreach ($this->tasks as $task) {
            $id = $task->current?->symbol_id;
            if (($id === null) || ($task->previous === null) || (($pairs[$id] ?? null) !== $task) || (isset($selected[$id]))) {
                throw new \LogicException('Unexpected, duplicate or stale comparison task');
            }
            $selected[$id] = $task;
        }
        $replacements = [];
        foreach ($results as $result)
        {
            $id = $result->current?->symbol_id;
            $task = $selected[$id] ?? null;
            if (($task === null) || (isset($replacements[$id])) || ($result->previous !== $task->previous)
                || ($result->current !== $task->current) || ($result->children_changed === null)
                || (($result->own_status !== change_status::unchanged) && ($result->own_status !== change_status::changed))) {
                throw new \LogicException('Unexpected, duplicate or stale comparison result');
            }
            $replacements[$id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete comparison phase');
        }
        $output = new Symbol_Refresh();
        $output->current = $this->input->current;
        foreach ($pairs as $id => $change)
        {
            $result = $replacements[$id] ?? $change;
            if ($result->own_status === change_status::uncompared) {
                throw new \LogicException('Incomplete comparison phase');
            }

            // Logical equality does not permit restoring old AST references.
            // Keep current symbols; omit only the redundant catalog description.
            if (($result->own_status !== change_status::unchanged) || ($result->children_changed !== false)) {
                $output->changes[] = $result;
            }
        }
        return $output;
    }
}
