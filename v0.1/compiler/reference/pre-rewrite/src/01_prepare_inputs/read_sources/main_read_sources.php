<?php
declare(strict_types=1);

/*
 * Role: Source_Reader phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> Source_Read_Selection::select()
 *   run() -> Snapshot_Reader::read() [each task]
 *   finalize() -> Snapshot_Join::join()
 * Output: result() returns Source_Set after finalize().
 */

namespace read_sources;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Source_Reader implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Source_Set $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly Source_Set $sources,
        private readonly bool $full_rebuild
    )
    {
    }

    /** Select fixed source files for this update before executing work. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = Source_Read_Selection::select($this->sources, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Execute selected source reading tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = Snapshot_Reader::read($task);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join selected source snapshots, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Snapshot_Join($this->sources, $this->tasks))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Source_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Source_Set
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

    /** @compiler-api User-facing statement of current scan timing constraints; no scan or mutation. */
    public static function change_detection_limitation(): string
    {
        return "Current limitation: file changes use whole-second mtime and size. Wait at least one second after the last edit before compiling, and at least one second after the scan before editing again. Edits outside this timing window may go undetected.";
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Source_Reader::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
