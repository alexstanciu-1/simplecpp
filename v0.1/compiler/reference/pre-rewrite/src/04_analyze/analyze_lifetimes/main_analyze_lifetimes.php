<?php
declare(strict_types=1);

/*
 * Role: Lifetime_Analyzer phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] validate phase state
 *   run() -> Ownership_Preparation::run(); select(); Lifetime_Worker::analyze() [each task]
 *   finalize() -> Lifetime_Join::join()
 * Output: result() returns Lifetime_Set after finalize().
 */

namespace analyze_lifetimes;

use check_bodies\Body_Set;
use check_bodies\Checked_Body;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Lifetime_Analyzer implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Lifetime_Set $output;
    private array $tasks = [];
    private array $results = [];
    private array $ownership = [];

    public function __construct(
        private readonly \check_bodies\Body_Set $bodies,
        private readonly Lifetime_Set $previous,
        private readonly bool $full_rebuild,
        private readonly ?\type_model\Type_Store $types = null
    )
    {
    }

    /** Enter readiness; dependent selection follows accepted ownership summaries in run(). */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            // Ownership dependencies are accepted before dependent lifetime work is selected.
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Execute selected lifetime analysis tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            $this->ownership = Ownership_Preparation::run($this->bodies, $this->types, $this->previous->ownership_results, $this->full_rebuild);
            $this->tasks = self::select($this->bodies, $this->previous, $this->full_rebuild, $this->ownership);
            foreach ($this->tasks as $task) {
                $this->results[] = (new Lifetime_Worker($task, $this->ownership['body:' . $task->callable_id] ?? null))->analyze();
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join selected lifetime plans, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Lifetime_Join($this->bodies, $this->previous, $this->tasks, $this->ownership))->join($this->results);
            $this->tasks = [];
            $this->results = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Lifetime_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Lifetime_Set
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
     * @compiler-internal Select on full, changed checked body, or changed accepted ownership input.
     * @return list<Checked_Body>
     */
    private static function select(Body_Set $bodies, Lifetime_Set $previous, bool $full_rebuild, array $ownership = []): array
    {
        $tasks = [];
        foreach ($bodies->bodies() as $body) {
            if (($full_rebuild) || ($previous->for_callable($body->callable_id)?->body !== $body)
                || ($previous->for_callable($body->callable_id)?->ownership !== ($ownership['body:' . $body->callable_id] ?? null))) {
                $tasks[] = $body;
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Lifetime_Analyzer::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
