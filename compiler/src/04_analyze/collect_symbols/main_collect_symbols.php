<?php
declare(strict_types=1);

/*
 * Role: Declaration_Collector phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed file frontends
 *   run() -> File_Collector::collect_file() [each task]
 *   finalize() -> Declaration_Join::join()
 * Output: result() returns Symbol_Refresh / Symbol_Store after finalize().
 */

namespace collect_symbols;

use parse\File_Frontend;
use parse\Frontend_Set;
use read_sources\Source_Set;
use read_sources\file_change;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Declaration_Collector implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Symbol_Refresh $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly Symbol_Store $previous,
        private readonly \read_sources\Source_Set $sources,
        private readonly \parse\Frontend_Set $frontends,
        private readonly bool $full_rebuild,
        private readonly ?\load_runtime\Runtime_Input_Set $runtime = null,
        private readonly array $families = [],
    )
    {
    }

    /** Select declaration-collection tasks from the current sources and prior symbol state. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->previous, $this->sources, $this->frontends, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Collect selected declarations into private results before assigning shared symbol identities. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = File_Collector::collect_file($task);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join declarations and runtime bindings into the candidate symbol store. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Declaration_Join($this->previous, $this->sources, $this->frontends, $this->tasks, $this->runtime, $this->families))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Symbol_Refresh
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Symbol_Store
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output->current;
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
     * @compiler-internal Select complete frontends on full or changed frontend identity; reject stale inputs.
     * No global identity allocation occurs in selection or file workers.
     * @return list<File_Frontend> Fixed file snapshots; no global writes in workers.
     */
    private static function select(Symbol_Store $previous, Source_Set $sources, Frontend_Set $frontends, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ($sources->files as $file)
        {
            if ($file->change_state === file_change::deleted) {
                continue;
            }
            $frontend = Frontend_Validation::current_frontend($sources, $frontends, $file->id);
            $entry_id = $previous->entry_symbol_id($file->id);
            if (($full_rebuild) || ($entry_id === 0) || ($previous->symbol_by_id($entry_id)->frontend !== $frontend)) {
                $tasks[] = $frontend;
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Declaration_Collector::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
