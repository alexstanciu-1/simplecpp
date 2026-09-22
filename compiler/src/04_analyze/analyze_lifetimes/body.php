<?php
declare(strict_types=1);

/*
 * Role: Own per-body live locals and temporary lifetimes.
 * Used by: Lifetime_Analyzer::run()
 * Call map:
 *   Lifetime_Worker::analyze()
 *     -> Local_Flow::entries(); analyze_statement() [each reachable statement]
 *     -> Allocation_Flow::analyze() [if no accepted ownership result]
 * Output: Analyzed_Body retains accepted lifetime and resource facts.
 */

namespace analyze_lifetimes;

use check_bodies\Checked_Body;
use type_model\copy_kind;
use type_model\cleanup_kind;

// One fixed checked body, private outputs and live-binding state. Scope ranges
// and local IDs are consumed as contracts; no source name lookup or AST walk.
/**
 * @compiler-internal Lifetime implementation behind Lifetime_Analyzer::run(); private live-local maps
 * and traversal stacks may change without changing the result contract.
 */
class Lifetime_Worker
{
    use Statement_Lifetimes;
    use Value_Lifetimes;
    use Local_Lifetimes;

    /** @var array<int, value_lifetime> Value ID -> completed temporary lifetime. */
    private array $values = [];
    private int $block_id = 0;

    /** @var list<cleanup_obligation> Ordered destruction actions; no row for no-cleanup values. */
    private array $cleanups = [];

    /** @var list<int> Owned temporaries in construction order within the current full expression. */
    private array $temporaries = [];

    /** @var array<int, bool> Produced temporaries awaiting their one consumer. */
    private array $live_values = [];

    /** @var list<local_lifetime> Completed binding lifetimes in exit order. */
    private array $locals = [];

    /** @var list<active_local> */
    private array $active = [];

    /** @var array<int, active_local> References to the same stack records. */
    private array $live = [];

    /** @compiler-internal Initialize private state for one task; use the process entry point externally. */
    public function __construct(private readonly Checked_Body $body, private readonly ?ownership_result $ownership = null)
    {
    }

    /** @compiler-internal Produce private lifetime facts from this fixed checked body; use Lifetime_Analyzer externally. */
    public function analyze(): Analyzed_Body
    {
        $body = $this->body;
        $reachable_blocks = \check_bodies\Flow_Graph::reachable($body->blocks);
        $entries = Local_Flow::entries($body);
        $reachable = 0;
        $falls_through = false;
        foreach ($reachable_blocks as $block_id)
        {
            // Reconstruct this block's initialized bindings from the fixed flow solution.
            $this->block_id = $block_id;
            $block = $body->blocks[$block_id - 1];
            $this->active = [];
            $this->live = [];
            foreach ($entries[$block_id] as $id => $initialization) {
                $this->start_local($id, $initialization);
                $this->contract($body->local_type_for($id), $body->names->local_for($id)->declaration_node_id, false);
            }

            // Statements consume access lifetimes and schedule full-expression cleanup.
            $end_index = $block->statement_start + $block->statement_count;
            for ($index = $block->statement_start; $index < $end_index; ++$index) {
                ++$reachable;
                $this->analyze_statement($body->statements[$index], $index, $end_index, $block);
            }

            // Apply the scope exits required before leaving this block along its reachable edges.
            $successors = \check_bodies\Flow_Graph::successors($block);
            if ($successors === []) {
                $returning = $block->end === \check_bodies\flow_end::return_exit;
                while ($this->active !== []) {
                    $this->end_local($end_index, $returning ? local_end::return_exit : local_end::scope_exit);
                }
                $falls_through = ($falls_through) || (!$returning);
            }
            else
            {
                // Structured branches enter child scopes; both preserve the same
                // outer locals. Loop backedges leave body locals before re-entry.
                $scope = $body->blocks[$successors[0] - 1]->scope_id;
                $this->exit_to_scope($scope, $end_index);
                foreach ($successors as $target) {
                    if (array_keys(Local_Flow::retain($body, $this->live, $body->blocks[$target - 1]->scope_id)) !== array_keys($this->live)) {
                        throw new \LogicException('Unsupported unequal local exits on one branch');
                    }
                }
            }
        }

        // Cross-check reachability before handing private lifetime facts to result validation.
        if ($falls_through !== $body->falls_through) {
            throw new \LogicException('Inconsistent checked body flow');
        }
        return new Analyzed_Body($body, array_values($this->values), $reachable, $falls_through, $this->locals, $reachable_blocks, $this->cleanups, $this->ownership !== null ? $this->ownership->allocations : (new Allocation_Flow($body))->analyze(), $this->ownership);
    }

    /** Reject unsupported lifetime policies and require value-copy permission only at copying uses. */
    private function contract(int $type_id, int $node_id, bool $copy): void
    {
        $contract = $this->body->definition_for($type_id)->lifetime;
        if ($contract === null) {
            $this->fail($node_id, 'Value type has no lifetime contract');
        }
        if (!in_array($contract->cleanup, [cleanup_kind::none, cleanup_kind::destroy], true)) {
            $this->fail($node_id, 'Unsupported lifetime cleanup contract');
        }
        if (($copy) && ($contract->copy !== copy_kind::value)) {
            $this->fail($node_id, 'Unsupported lifetime value-copy contract');
        }
    }

    private function fail(int $node_id, string $message): never
    {
        $node = $this->body->owner->frontend->syntax->nodes[$node_id - 1];
        $source = $this->body->owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
