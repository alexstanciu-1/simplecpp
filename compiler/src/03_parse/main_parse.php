<?php
declare(strict_types=1);

/*
 * Role: Parser phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> Parser_Selection::select(); create Frontend_Join
 *   run() -> File_Parser::parse() [each task]
 *   finalize() -> Frontend_Join::join()
 * Output: result() returns Frontend_Set after finalize().
 */

namespace parse;

use compile\step_status;
use read_sources\Source_Set;
use tokenize\Token_Set;

/** @compiler-api One use per update; workers and joins retain their existing contracts. */
final class Parser implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private step_status $state = step_status::created;
    /** @var list<\tokenize\Token_Buffer> */
    private array $tasks = [];
    /** @var list<File_Frontend> */
    private array $results = [];
    private Frontend_Join $join;
    private Frontend_Set $output;

    public function __construct(
        private readonly Source_Set $sources,
        private readonly Token_Set $tokens,
        private readonly Frontend_Set $previous,
        private readonly bool $full_rebuild,
    )
    {
    }

    /** Select fixed token buffers for this update before executing work. */
    public function init(): void
    {
        $this->require_status(step_status::created, __FUNCTION__);
        try {
            $this->tasks = Parser_Selection::select($this->sources, $this->tokens, $this->previous, $this->full_rebuild);
            $this->join = new Frontend_Join($this->previous, $this->sources, $this->tokens, $this->tasks);
            $this->state = step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = step_status::failed;
            throw $error;
        }
    }

    /** Execute selected parsing tasks into private results; retained inputs remain unchanged. */
    public function run(): void
    {
        $this->require_status(step_status::ready, __FUNCTION__);
        $this->state = step_status::running;
        try {
            foreach ($this->tasks as $task) {
                $this->results[] = (new File_Parser($task))->parse();
            }
            $this->state = step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = step_status::failed;
            throw $error;
        }
    }

    /** Join selected syntax trees, release transient tasks/results, and expose the accepted output. */
    public function finalize(): void
    {
        $this->require_status(step_status::processed, __FUNCTION__);
        try {
            $this->output = $this->join->join($this->results);
            $this->state = step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = step_status::failed;
            throw $error;
        }
    }

    /** Completed output is shared read-only; repeated reads never rerun the join. */
    public function result(): Frontend_Set
    {
        $this->require_status(step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Frontend_Set
    {
        $this->require_status(step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function status(): step_status
    {
        return $this->state;
    }

    public function supports_run(): bool
    {
        return true;
    }

    private function require_status(step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Parser::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
