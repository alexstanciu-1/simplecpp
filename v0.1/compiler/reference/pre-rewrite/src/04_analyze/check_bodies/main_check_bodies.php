<?php
declare(strict_types=1);

/*
 * Role: Body_Checker phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed callable tasks
 *   run() -> Body_Worker::check() [each task]
 *   finalize() -> Body_Join::join()
 * Output: result() returns Body_Set after finalize().
 */

namespace check_bodies;

use collect_symbols\Symbol_Store;
use resolve_symbols\Resolution_Set;
use resolve_types\Type_Resolution;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Body_Checker implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Body_Set $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \resolve_symbols\Resolution_Set $names,
        private readonly \resolve_types\Type_Resolution $types,
        private readonly Body_Set $previous,
        private readonly bool $full_rebuild
    )
    {
    }

    /** Select checked-body tasks against fixed names and types for this update. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->symbols, $this->names, $this->types, $this->previous, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Check selected bodies into private results; failures leave the phase terminal. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = (new Body_Worker($task))->check();
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join checked results with reusable bodies and release temporary task state. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Body_Join($this->symbols, $this->names, $this->types, $this->previous, $this->tasks))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Body_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Body_Set
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
     * @compiler-internal Select participating callables on full or stale dependencies; capture exact
     * callable bindings and the fixed type snapshot without mutating either.
     * @return list<body_check_task>
     */
    private static function select(Symbol_Store $symbols, Resolution_Set $names, Type_Resolution $types,
        Body_Set $previous, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ($types->body_signatures() as $signature)
        {
            $symbol = $symbols->symbol_by_id($signature->symbol_id);
            if (($full_rebuild) || !Body_Validity::is_current($previous->for_callable($signature->callable_id), $symbol, $names, $types, $signature->instance)) {
                $bindings = $names->for_symbol($symbol->symbol_id)
                    ?? throw new \LogicException('Body selection requires current callable name bindings');
                $tasks[] = new body_check_task($symbol, $bindings, $types, $signature->instance);
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Body_Checker::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
