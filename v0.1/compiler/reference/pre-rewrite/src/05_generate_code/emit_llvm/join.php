<?php
declare(strict_types=1);

/*
 * Role: Accept function IR and its backend/entry associations.
 * Used by: LLVM_Emitter::finalize()
 * Call map:
 *   Emission_Join::join()
 *     -> [action] validate functions and assemble Emitted_Function_Set
 */

namespace emit_llvm;

use lower\Lowered_Body;
use lower\Lowered_Set;

/** @compiler-internal Accept emitted functions and group them by current source file. */
class Emission_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<Lowered_Body> $tasks
     */
    public function __construct(
        private readonly Lowered_Set $bodies,
        private readonly \prepare_backend\Backend_Context $backend,
        private readonly \lower\native_entry_plan $entry,
        private readonly ?Emitted_Program $previous,
        private readonly array $tasks,
        private readonly array $lifecycle = [],
    )
    {
    }

    /**
     * @compiler-api Coordinator join; results may arrive in any order.
     * Requires exactly one result per task, matching current plan identities and
     * backend context, plus a current native-entry plan. Retains unchanged functions,
     * excludes removed ones and groups shared functions by source file.
     * No module text is built here; Module_Assembler consumes the fixed result.
     * @param list<Emitted_Function> $results
     * @throws \LogicException Incomplete, duplicate or stale inputs/results.
     */
    public function join(array $results): Emitted_Function_Set
    {
        // Match each selected body to its current plan before accepting emitted text.
        $selected = [];
        foreach ($this->tasks as $body) {
            $id = $body->binding->callable_id;
            if ((isset($selected[$id])) || ($this->bodies->for_callable($id) !== $body)) {
                throw new \LogicException('Duplicate or stale emission task');
            }
            $selected[$id] = $body;
        }

        // Require one emitted function per task before retaining unchanged functions.
        $replacements = [];
        foreach ($results as $result) {
            $id = $result->body->binding->callable_id;
            if ((($selected[$id] ?? null) !== $result->body) || (isset($replacements[$id]))) {
                throw new \LogicException('Unexpected or duplicate emission result');
            }
            $replacements[$id] = $result;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete emission phase');
        }
        if ($this->backend->binding_for($this->entry->entry->callable_id) !== $this->entry->entry) {
            throw new \LogicException('Stale native entry plan');
        }
        $by_file = [];
        $entry_file_id = 0;
        foreach ($this->bodies->bodies() as $body)
        {
            $id = $body->binding->callable_id;
            $result = $replacements[$id] ?? $this->previous?->function_for($id);
            if (($result === null) || ($result->body !== $body) || ($body->backend_context() !== $this->backend)) {
                throw new \LogicException('Incomplete or stale emission phase');
            }
            $file_id = $body->source_file_id();
            $by_file[$file_id][] = $result;
            if ($body->binding === $this->entry->entry) {
                $entry_file_id = $file_id;
            }
        }
        if ($entry_file_id === 0) {
            throw new \LogicException('Missing emitted entry function');
        }
        return new Emitted_Function_Set($this->backend, $this->entry, $entry_file_id, $by_file, $this->lifecycle);
    }
}
