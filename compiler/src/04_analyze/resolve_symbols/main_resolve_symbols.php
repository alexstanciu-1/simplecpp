<?php
declare(strict_types=1);

/*
 * Role: Symbol_Resolver phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed source-owner tasks
 *   run() -> Resolution_Worker::run() [each task]
 *   finalize() -> Resolution_Join::join()
 * Output: result() returns Resolution_Set after finalize().
 */

namespace resolve_symbols;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Symbol_Resolver implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Resolution_Set $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly Resolution_Set $previous,
        private readonly bool $full_rebuild,
        private readonly \type_model\Type_Catalog $catalog
    )
    {
    }

    /** Select name-resolution tasks against this update's fixed symbol inputs. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->symbols, $this->previous, $this->full_rebuild, $this->catalog);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Resolve selected declarations/bodies into private bindings without mutating retained results. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = (new Resolution_Worker($this->symbols, $task, $this->catalog))->run();
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join resolved bindings with reusable results and release temporary task state. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Resolution_Join($this->previous, $this->symbols, $this->tasks, $this->catalog))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Resolution_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Resolution_Set
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
     * @compiler-internal Select all current source records on full, otherwise absent/stale name results.
     * Read only; current declaration membership is fixed before dispatch.
     * @return list<symbol_record> Fixed source records from the candidate index.
     */
    private static function select(Symbol_Store $symbols, Resolution_Set $previous, bool $full_rebuild, \type_model\Type_Catalog $catalog): array
    {
        $tasks = [];
        foreach ($symbols->resolution_records() as $symbol) {
            if (($full_rebuild) || (!Resolution_Validity::is_current($previous->for_symbol($symbol->symbol_id), $symbol, $symbols, $catalog))) {
                $tasks[] = $symbol;
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Symbol_Resolver::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
