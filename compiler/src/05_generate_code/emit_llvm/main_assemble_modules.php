<?php
declare(strict_types=1);

/*
 * Role: Module_Assembler phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed module tasks
 *   run() -> Module_Worker::assemble() [each task]
 *   finalize() -> Module_Join::join()
 * Output: result() returns Emitted_Program after finalize().
 */

namespace emit_llvm;

use prepare_backend\LLVM_Types;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Module_Assembler implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Emitted_Program $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly Emitted_Function_Set $current,
        private readonly ?Emitted_Program $previous,
        private readonly bool $full
    )
    {
    }

    /** Select fixed emitted callables for this update before executing work. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->current, $this->previous, $this->full);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Execute selected module assembly tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = Module_Worker::assemble($task);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join selected LLVM modules, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Module_Join($this->current, $this->previous, $this->tasks))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Emitted_Program
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Emitted_Program
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function status(): \compile\step_status
    {
        return $this->state;
    }

    public function supports_run(): bool
    {
        return true;
    }

    /**
     * @compiler-internal Select all files on full, otherwise changed functions/backend/entry.
     * Unselected modules remain shared. No import traversal or text assembly here.
     * @return list<module_task>
     */
    private static function select(Emitted_Function_Set $current, ?Emitted_Program $previous, bool $full): array
    {
        $tasks = [];
        foreach ($current->files() as $id => $functions) {
            if (($full) || (!Module_Validity::is_current($previous?->module_for($id), $current, $id))) {
                $tasks[] = new module_task($id, $current->backend, $functions, $current->entry_for_file($id), $current->entry_for_file($id) === null ? [] : $current->lifecycle);
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Module_Assembler::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
