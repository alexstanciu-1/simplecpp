<?php
declare(strict_types=1);

/*
 * Role: LLVM_Emitter phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed function and source lifecycle work
 *   run() -> Emission_Worker::emit(); Lifecycle_Emission::emit() [selected tasks]
 *   finalize() -> Lifecycle_Emission_Join::join(); Emission_Join::join()
 * Output: result() returns Emitted_Function_Set after finalize().
 */

namespace emit_llvm;

use lower\Lowered_Body;
use lower\Lowered_Set;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class LLVM_Emitter implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Emitted_Function_Set $output;
    private array $tasks = [];
    private array $results = [];
    private array $lifecycle_tasks = [];
    private array $lifecycle_results = [];

    public function __construct(
        private readonly \lower\Lowered_Set $bodies,
        private readonly \prepare_backend\Backend_Context $backend,
        private readonly \lower\native_entry_plan $entry,
        private readonly ?Emitted_Program $previous,
        private readonly bool $full
    )
    {
    }

    /** Select fixed lowered bodies for this update before executing work. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            $this->tasks = self::select($this->bodies, $this->previous, $this->full);
            foreach ($this->backend->source_operations as $operation) {
                $old = $this->previous?->lifecycle[$operation->link_name] ?? null;
                if (($this->full) || ($old?->task->operation !== $operation) || ($old->task->backend !== $this->backend)) {
                    $this->lifecycle_tasks[] = new lifecycle_emission_task($operation, $this->backend);
                }
            }
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Execute selected LLVM emission tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            foreach ($this->tasks as $task) {
                $this->results[] = (new Emission_Worker($task))->emit();
            }
            foreach ($this->lifecycle_tasks as $task) {
                $this->lifecycle_results[] = Lifecycle_Emission::emit($task);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join selected emitted callables, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try
        {
            $lifecycle = (new Lifecycle_Emission_Join($this->backend, $this->previous?->lifecycle ?? [], $this->lifecycle_tasks))
                ->join($this->lifecycle_results);
            $this->output = (new Emission_Join($this->bodies, $this->backend, $this->entry, $this->previous, $this->tasks, $lifecycle))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->lifecycle_tasks = [];
            $this->lifecycle_results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Emitted_Function_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Emitted_Function_Set
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
     * @compiler-internal Coordinator selection: full work or changed plan identities.
     * Lowered bodies are immutable inputs; equal callable IDs alone cannot reuse IR.
     * @return list<Lowered_Body> Shared current plans, not copies.
     */
    private static function select(Lowered_Set $bodies, ?Emitted_Program $previous, bool $full): array
    {
        $tasks = [];
        foreach ($bodies->bodies() as $body) {
            if (($full) || ($previous?->function_for($body->binding->callable_id)?->body !== $body)) {
                $tasks[] = $body;
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('LLVM_Emitter::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
