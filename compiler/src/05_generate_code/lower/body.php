<?php
declare(strict_types=1);

/*
 * Role: Own lowered value/slot identities and lifetime consumption.
 * Used by: Lowerer::run()
 * Call map:
 *   Lowering_Worker::lower()
 *     -> prepare_storage_slots(); lower_statement(); lower_terminator() [traits]
 */

namespace lower;

use check_bodies\value_kind;
use analyze_lifetimes\lifetime_end;

/**
 * @compiler-internal Lowering's single-task implementation; use Lowerer::run().
 * Constructor and public lower() are not cross-process entry points. All mutable
 * cursors, value maps and live slots belong to this worker and may change freely.
 * Reads one fixed input, writes private output; never descends into callee bodies.
 */
class Lowering_Worker
{
    use Statement_Lowering;
    use Expression_Lowering;
    use Local_Lowering;
    use Storage_Lowering;
    use Control_Flow_Lowering;

    /** @var list<lowered_value> */
    private array $values = [];

    /** @var list<lowered_instruction> */
    private array $instructions = [];

    /** @var array<int, int> Checked value ID -> lowered value ID. */
    private array $value_ids = [];

    /** @var array<int, bool> */
    private array $ended = [];

    /** @var list<storage_slot> */
    private array $slots = [];

    /** @var array<int, int> Reached source local ID -> static slot ID. */
    private array $slot_ids = [];

    /** @var array<int, int> Checked constructor result -> destination local slot; private worker index. */
    private array $construction_destinations = [];

    /** @var list<int> Lowered value IDs, contiguous per completed call. */
    private array $arguments = [];
    private int $next_lifetime = 0;
    private int $next_local_end = 0;
    private int $next_cleanup = 0;
    private int $next_address = 0;
    private int $result_slot = 0;

    public function __construct(private readonly lowering_input $input)
    {
    }

    /** Lower one fixed analyzed body into private storage and instructions, consuming every reachable lifetime obligation. */
    public function lower(): Lowered_Body
    {
        $analysis = $this->input->analysis;
        $body = $analysis->body;
        $binding = $this->input->backend->callable_for($body, $body->callable_id);

        // Translate reachable checked blocks to this body's compact lowered block IDs.
        $block_ids = [];
        foreach ($analysis->reachable_blocks as $index => $id) {
            $block_ids[$id] = $index + 1;
        }

        // Prepare stable local destinations before lowering any constructor call.
        $this->prepare_storage_slots();
        $blocks = [];
        $reached = 0;
        foreach ($analysis->reachable_blocks as $source_block_id)
        {
            // Entry initialization and boundary cleanup precede the block's statements.
            $block = $body->blocks[$source_block_id - 1];
            $start = count($this->instructions);
            if ($source_block_id === 1) {
                $this->initialize_parameters($binding);
            }
            $this->end_locals($block->statement_start, false, $source_block_id);
            $this->emit_cleanups($block->statement_start, $source_block_id);

            // Keep the final statement value for the already-checked return or condition.
            $value_id = 0;
            $node_id = $body->owner->body_node_id;
            $end_index = $block->statement_start + $block->statement_count;
            for ($index = $block->statement_start; $index < $end_index; ++$index) {
                ++$reached;
                $statement = $body->statements[$index];
                $node_id = $statement->source_node_id;
                $value_id = $this->lower_statement($statement, $index + 1, $source_block_id);
            }

            // Empty blocks can still leave scopes; consume those exits before the terminator.
            if ($block->statement_count === 0) {
                $this->end_locals($end_index, false, $source_block_id);
                $this->emit_cleanups($end_index, $source_block_id);
            }
            $terminator = $this->lower_terminator($block, $block_ids, $value_id, $node_id, $binding);
            $blocks[] = new basic_block($start, count($this->instructions) - $start, $terminator);
        }

        // Reject partial plans before exposing any worker output to the join.
        if (($reached !== $analysis->reachable_statement_count)
            || ($this->next_lifetime !== count($analysis->lifetimes)) || (count($this->ended) !== count($this->value_ids))) {
            throw new \LogicException('Incomplete lifetime facts for lowered values');
        }
        if (($this->next_local_end !== count($analysis->local_lifetimes)) || ($this->next_cleanup !== count($analysis->cleanups))) {
            throw new \LogicException('Incomplete lifetime facts for lowered locals in ' . $body->owner->name
                . ': exits ' . $this->next_local_end . '/' . count($analysis->local_lifetimes)
                . ', cleanups ' . $this->next_cleanup . '/' . count($analysis->cleanups));
        }
        return new Lowered_Body($this->input, $binding, $this->values, $this->instructions, $blocks,
            $block_ids[1], $this->slots, $this->arguments);
    }

    /** Match the next analyzed value-consumption fact and mark that checked value unavailable for further access. */
    private function consume(int $source_id, int $statement_id, lifetime_end $end, int $call_id = 0): void
    {
        $lifetime = $this->input->analysis->lifetimes[$this->next_lifetime] ?? null;
        if (($lifetime === null) || ($lifetime->value_id !== $source_id) || ($lifetime->statement_id !== $statement_id)
            || ($lifetime->end !== $end) || ($lifetime->consumer_id !== $call_id)) {
            throw new \LogicException('Lowered consumption does not match analyzed lifetime');
        }
        if ((!isset($this->value_ids[$source_id])) || (isset($this->ended[$source_id]))) {
            throw new \LogicException('Lifetime refers to an absent or already ended lowered value');
        }
        $this->ended[$source_id] = true;
        ++$this->next_lifetime;
    }

    /** Reuse a live lowered value or materialize a leaf; computed results must already have executed. */
    private function get_value(int $source_id): int
    {
        if (isset($this->ended[$source_id])) {
            throw new \LogicException('Use after temporary lifetime end during lowering');
        }
        if (isset($this->value_ids[$source_id])) {
            return $this->value_ids[$source_id];
        }
        $value = $this->input->analysis->body->values[$source_id - 1]
            ?? throw new \LogicException('Missing checked value during lowering');
        if (($value->kind === value_kind::call_result) || ($value->kind === value_kind::conversion)
            || ($value->kind === value_kind::operation)) {
            throw new \LogicException('Expression result requested before its execution');
        }
        $slot = $value->kind === value_kind::local_borrow
            ? ($this->slot_ids[$value->payload->local_id] ?? throw new \LogicException('Borrow requires local storage')) : 0;
        if ($value->kind === value_kind::default_construct) {
            $slot = $this->construction_destinations[$source_id] ?? 0;
            if ($slot === 0) {
                $this->slots[] = new storage_slot(0, $value->type_id, $source_id);
                $slot = count($this->slots);
            }
        }
        $id = $this->add_value($source_id, $slot);
        $this->lower_simple_value($value, $id);
        return $id;
    }

    /** Allocate one body-local lowered value ID with its checked type and optional addressed storage. */
    private function add_value(int $source_id, int $storage_slot_id = 0): int
    {
        if (isset($this->value_ids[$source_id])) {
            throw new \LogicException('Lowered value produced more than once');
        }
        $value = $this->input->analysis->body->values[$source_id - 1]
            ?? throw new \LogicException('Missing checked value during lowering');
        $this->values[] = new lowered_value($source_id, $value->type_id, $storage_slot_id);
        return $this->value_ids[$source_id] = count($this->values);
    }
}
