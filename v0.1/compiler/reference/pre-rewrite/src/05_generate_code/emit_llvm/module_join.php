<?php
declare(strict_types=1);

/*
 * Role: Accept independently assembled file modules.
 * Used by: Module_Assembler::finalize()
 * Call map:
 *   Module_Join::join()
 *     -> Module_Validity::is_current(); Source_Export_Emission::closure()
 */

namespace emit_llvm;

/** @compiler-internal Accept assembled modules into the current emitted program. */
class Module_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<module_task> $tasks
     */
    public function __construct(
        private readonly Emitted_Function_Set $current,
        private readonly ?Emitted_Program $previous,
        private readonly array $tasks
    )
    {
    }

    /**
     * @compiler-api Accept exactly one matching module per selected task; retain
     * valid unselected modules, omit removed files and preserve current file order.
     * Validate contracts/membership only, never rebuild IR. Incomplete, duplicate or
     * stale work throws without changing previous snapshots. Reuse the whole program
     * when modules/backend/entry are identical. No publication.
     * @param list<Emitted_Module> $results
     */
    public function join(array $results): Emitted_Program
    {
        // Validate task membership and exact backend/function snapshots before adoption.
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $id = $task->source_file_id;
            if ((isset($selected[$id])) || ($this->current->for_file($id) === null)
                || ($task->backend !== $this->current->backend) || ($task->functions !== $this->current->for_file($id))
                || ($task->entry !== $this->current->entry_for_file($id))
                || ($task->lifecycle !== ($task->entry === null ? [] : $this->current->lifecycle))) {
                throw new \LogicException('Duplicate or stale module assembly task');
            }
            $selected[$id] = $task;
        }

        // Accept each selected file once, independent of worker completion order.
        $replacements = [];
        foreach ($results as $result) {
            $id = $result->source_file_id;
            if ((!isset($selected[$id])) || (isset($replacements[$id])) || (!Module_Validity::is_current($result, $this->current, $id))) {
                throw new \LogicException('Unexpected, duplicate or stale module assembly result');
            }
            $replacements[$id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete module assembly batch');
        }

        // Join live file order with valid retained modules, omitting removed files.
        $modules = [];
        foreach ($this->current->files() as $id => $functions) {
            $module = $replacements[$id] ?? $this->previous?->module_for($id);
            if (!Module_Validity::is_current($module, $this->current, $id)) {
                throw new \LogicException('Incomplete or stale module assembly');
            }
            $modules[] = $module;
        }
        Source_Export_Emission::closure($this->current->backend, $modules);
        if (($this->previous !== null) && ($this->previous->backend === $this->current->backend) && ($this->previous->entry === $this->current->entry)
            && ($this->previous->modules === $modules)) {
            return $this->previous;
        }
        return new Emitted_Program($this->current->backend, $this->current->entry, $modules);
    }
}
