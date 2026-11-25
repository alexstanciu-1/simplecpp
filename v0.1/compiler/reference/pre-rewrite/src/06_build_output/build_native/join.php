<?php
declare(strict_types=1);

/*
 * Role: Accept current native objects against emitted modules.
 * Used by: Native_Builder::run()
 * Call map:
 *   Native_Join::join()
 *     -> [action] check object validity and complete module membership
 */

namespace build_native;

/** @compiler-internal Accept compiled objects before native linking and publication. */
class Native_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<\emit_llvm\Emitted_Module> $tasks
     */
    public function __construct(
        private readonly \emit_llvm\Emitted_Program $program,
        private readonly ?Native_Artifact $previous,
        private readonly array $tasks
    )
    {
    }

    /**
     * @compiler-api Coordinator acceptance of one object per task in any order;
     * retain unselected objects without copying them and exclude removed modules.
     * @param list<Native_Object> $results
     * @return list<Native_Object>
     */
    public function join(array $results): array
    {
        // Accept results only for the exact selected module snapshots.
        $selected = [];
        foreach ($this->tasks as $task) {
            $id = $task->source_file_id;
            if ((isset($selected[$id])) || ($this->program->module_for($id) !== $task)) {
                throw new \LogicException('Duplicate or stale native task');
            }
            $selected[$id] = $task;
        }

        // Check result completeness before combining new and retained objects.
        $replacements = [];
        foreach ($results as $object) {
            $id = $object->module->source_file_id;
            if ((($selected[$id] ?? null) !== $object->module) || (isset($replacements[$id])) || (!$object->is_current($object->module))) {
                throw new \LogicException('Unexpected, duplicate or stale native result');
            }
            $replacements[$id] = $object;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete native batch');
        }

        // Preserve current module order and omit objects for removed files.
        $objects = [];
        foreach ($this->program->modules as $module) {
            $object = $replacements[$module->source_file_id] ?? $this->previous?->object_for($module->source_file_id);
            if (($object === null) || ($object->module !== $module)) {
                throw new \LogicException('Stale retained native object');
            }
            $objects[] = $object;
        }
        return $objects;
    }
}
