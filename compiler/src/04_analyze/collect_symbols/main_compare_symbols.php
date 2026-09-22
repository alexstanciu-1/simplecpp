<?php
declare(strict_types=1);

/*
 * Role: Symbol_Comparer phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] select fixed symbol comparisons
 *   run() -> Comparison_Worker::compare() [each task]
 *   finalize() -> Comparison_Join::join()
 * Output: result() returns Symbol_Refresh / Symbol_Store after finalize().
 */

namespace collect_symbols;

use parse\Syntax_Comparer;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Symbol_Comparer implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Symbol_Refresh $output;
    private array $tasks = [];
    private array $results = [];

    public function __construct(
        private readonly Symbol_Refresh $input,
        private readonly bool $full_rebuild
    )
    {
    }

    /** Select fixed symbol comparisons for this update before executing work. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->tasks = self::select($this->input, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Execute selected comparison tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = Comparison_Worker::compare($task);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join selected symbol change classifications, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = (new Comparison_Join($this->input, $this->tasks))->join($this->results);
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
     * @compiler-internal Select matched previous/current pairs when full or still uncompared; added/removed rows need no comparison.
     * @return list<symbol_change> Fixed previous/current pairs; no copied symbols.
     */
    private static function select(Symbol_Refresh $input, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ($input->changes as $change) {
            // Added/removed elements have no pair to compare, even on full rebuild.
            if (($change->previous !== null) && ($change->current !== null)
                && (($full_rebuild) || ($change->own_status === change_status::uncompared))) {
                $tasks[] = $change;
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Symbol_Comparer::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
