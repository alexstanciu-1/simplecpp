<?php
declare(strict_types=1);

/*
 * Role: Accept complete definition permission batches without mutating retained results.
 * Used by: Template_Checker::finalize()
 * Call map: Template_Join::join() -> definition_result::current()
 */
namespace check_templates;

final class Template_Join implements \compile\Join
{
    /** @param list<definition_task> $tasks Fixed selected definitions for this batch. */
    public function __construct(private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \resolve_symbols\Resolution_Set $names, private readonly \type_model\Type_Catalog $catalog,
        private readonly Template_Set $previous, private readonly array $tasks)
    {
    }

    /** Validate selection, provenance and coverage before publishing any new permission rows.
     * @param list<definition_result> $results */
    public function join(array $results): Template_Set
    {
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $id = $task->owner->symbol_id;
            if (isset($selected[$id]) || ($this->symbols->symbol_by_id($id) !== $task->owner)
                || ($this->names->for_symbol($id) !== $task->bindings) || !$task->owner->is_template()
                || ($task->owner->frontend === null)) {
                throw new \LogicException('Invalid or duplicate template definition task');
            }
            $selected[$id] = $task;
        }

        // Accept each private output exactly once against its selected task.
        $accepted = [];
        foreach ($results as $result)
        {
            $id = $result->task->owner->symbol_id;
            if (($result->task !== ($selected[$id] ?? null)) || isset($accepted[$id])
                || !$result->current($result->task->owner, $this->names, $this->catalog)
                || (($result->dependencies[$id] ?? null) !== $result->task->owner)
                || (($result->bindings[$id] ?? null) !== $result->task->bindings) || ($result->visited_nodes < 1)) {
                throw new \LogicException('Invalid, duplicate or stale template definition result');
            }
            $accepted[$id] = $result;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete template definition checking');
        }

        // Membership comes from current declarations, so deleted definitions cannot survive reuse.
        $output = [];
        foreach ($this->symbols->resolution_records() as $owner)
        {
            if (!$owner->is_template()) {
                continue;
            }
            $id = $owner->symbol_id;
            $result = $accepted[$id] ?? $this->previous->definitions[$id] ?? null;
            if (!($result?->current($owner, $this->names, $this->catalog) ?? false)) {
                throw new \LogicException('Missing current template definition result');
            }
            $output[$id] = $result;
        }
        return new Template_Set($output, count($selected));
    }
}
