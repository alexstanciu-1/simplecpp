<?php
declare(strict_types=1);

/*
 * Role: Template_Checker phase; one instance per update.
 * Used by: Concrete_Preparation::init()
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed definition tasks
 *   run() -> Template_Worker::check() [each task]
 *   finalize() -> Template_Join::join()
 * Output: result() returns Template_Set after finalize().
 */

namespace check_templates;

use collect_symbols\Symbol_Store;
use resolve_symbols\Resolution_Set;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Template_Checker implements \compile\Step, \compile\Runnable_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Template_Set $output;
    /** @var list<definition_task> */
    private array $tasks = [];
    /** @var list<definition_result> */
    private array $results = [];

    public function __construct(
        private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \resolve_symbols\Resolution_Set $names,
        private readonly \type_model\Type_Catalog $catalog,
        private readonly Template_Set $previous,
        private readonly bool $full_rebuild
    )
    {
    }

    /** Select checked-definition tasks against fixed names and types for this update. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->symbols, $this->names, $this->catalog, $this->previous, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Check selected definitions into private results; failures leave the phase terminal. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = (new Template_Worker($task, $this->symbols, $this->names, $this->catalog))->check();
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join checked results with reusable definition results and release temporary task state. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Template_Join($this->symbols, $this->names, $this->catalog, $this->previous, $this->tasks))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Template_Set
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
     * @compiler-internal Select participating definitions on full or stale dependencies; capture exact
     * declaration bindings and the fixed catalog snapshot without mutating either.
     * @return list<definition_task>
     */
    public static function select(Symbol_Store $symbols, Resolution_Set $names, \type_model\Type_Catalog $catalog,
        Template_Set $previous, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ($symbols->resolution_records() as $owner)
        {
            if (!$owner->is_template()) {
                continue;
            }
            $old = $previous->definitions[$owner->symbol_id] ?? null;
            if (($full_rebuild) || !($old?->current($owner, $names, $catalog) ?? false)) {
                $tasks[] = new definition_task($owner, $names->for_symbol($owner->symbol_id));
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Template_Checker::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
