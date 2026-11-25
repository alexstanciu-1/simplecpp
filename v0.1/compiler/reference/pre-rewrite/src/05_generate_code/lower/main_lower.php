<?php
declare(strict_types=1);

/*
 * Role: Lowerer phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed analyzed bodies
 *   run() -> Lowering_Worker::lower() [each task]
 *   finalize() -> Lowering_Join::join()
 * Output: result() returns Lowered_Set after finalize().
 */

namespace lower;

use prepare_backend\Backend_Context;

use analyze_lifetimes\Lifetime_Set;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Lowerer implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Lowered_Set $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly \analyze_lifetimes\Lifetime_Set $lifetimes,
        private readonly Backend_Context $backend,
        private readonly Lowered_Set $previous,
        private readonly bool $full_rebuild
    )
    {
    }

    /** Select fixed analyzed bodies and backend bindings for this update before executing work. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->lifetimes, $this->backend, $this->previous, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Execute selected lowering tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = (new Lowering_Worker($task))->lower();
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join selected lowered bodies, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Lowering_Join($this->lifetimes, $this->backend, $this->previous, $this->tasks))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Lowered_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Lowered_Set
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
     * @compiler-internal Coordinator selection; reads inputs without mutating them.
     * Select all current bodies when full, otherwise absent/stale plans. Reuse
     * requires the same analyzed-body and backend-context objects, not equal IDs.
     * @return list<lowering_input> Fixed tasks sharing analyzed bodies and prepared backend contracts.
     */
    private static function select(Lifetime_Set $lifetimes, Backend_Context $backend, Lowered_Set $previous, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ($lifetimes->bodies() as $analysis) {
            $old = $previous->for_callable($analysis->body->callable_id);
            if (($full_rebuild) || ($old === null) || (!$old->input->is_current($analysis, $backend))) {
                $tasks[] = new lowering_input($analysis, $backend);
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Lowerer::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
