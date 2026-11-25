<?php
declare(strict_types=1);

/*
 * Role: Entry_Resolver phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] locate manifest entry symbol
 *   run() -> [action] validate top-level execution policy
 *   finalize() -> [action] complete entry_contract
 * Output: result() returns entry_contract after finalize().
 */

namespace resolve_types;

use collect_symbols\symbol_kind;
use parse\syntax_kind;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Entry_Resolver implements \compile\Step, \compile\Runnable_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private entry_contract $output;
    private \collect_symbols\symbol_record $entry;
    private entry_contract $candidate;

    public function __construct(
        private readonly \read_sources\Source_Set $sources,
        private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \type_model\Type_Catalog $catalog
    )
    {
    }

    /** Locate the manifest entry in the completed symbol set. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            $file = $this->sources->entry_file()->id;
            $id = $this->symbols->entry_symbol_id($file);
            if (($file === 0) || ($id === 0)) {
                throw new \LogicException('Missing participating project entry');
            }
            $this->entry = $this->symbols->symbol_by_id($id);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Reject executable code outside the manifest entry and prepare its language return contract. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            foreach ($this->symbols->records() as $symbol)
            {
                if (($symbol->kind !== symbol_kind::file_entry) || ($symbol === $this->entry)) {
                    continue;
                }
                $body = $symbol->frontend->syntax->nodes[$symbol->body_node_id - 1];
                if ($body->kind !== syntax_kind::block) {
                    throw new \LogicException('Missing file entry body');
                }
                if ($body->first_child_id !== 0) {
                    $statement = $symbol->frontend->syntax->nodes[$body->first_child_id - 1];
                    $source = $symbol->frontend->tokens->source;
                    throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $statement->start, $statement->length,
                        'Executable top-level code outside the manifest entry is unsupported; initialization ordering is not defined');
                }
            }
            $this->candidate = new entry_contract($this->entry, $this->catalog->entry_return_type);
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Expose the validated entry contract and finish this phase. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = $this->candidate;
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): entry_contract
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

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Entry_Resolver::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
