<?php
declare(strict_types=1);

/*
 * Role: Lowered instructions, storage locations and function plans.
 * Used by: Lowering_Worker; Lowering_Join; Native_Entry; LLVM emission
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace lower;

use prepare_backend\Backend_Context;
use prepare_backend\abi_target;
use prepare_backend\callable_binding;
use prepare_backend\integer_adaptation;

/**
 * @compiler-api Fixed task for the coordinator and Lowerer, not emitted data.
 * analysis and backend are shared read-only inputs. The coordinator keeps them
 * fixed for the selected batch. No fields are copied and no readiness is implied
 * merely by constructing a task; interpretation belongs to the lowering process.
 */
final class lowering_input
{
    public function __construct(
        public readonly \analyze_lifetimes\Analyzed_Body $analysis,
        public readonly Backend_Context $backend,
    )
    {
    }

    /** @compiler-api Exact input-identity check for reuse; does not validate contents. */
    public function is_current(\analyze_lifetimes\Analyzed_Body $analysis, Backend_Context $backend): bool
    {
        return ($this->analysis === $analysis) && ($this->backend === $backend);
    }

    /**
     * @compiler-api Optional callable-contract check for coordinator/readiness queries.
     * Checks the owner and reached callees; does not lower or validate all lifetimes.
     * @throws \LogicException Missing or stale backend callable contracts.
     */
    public function require_callables(): void
    {
        $body = $this->analysis->body;
        $this->backend->callable_for($body, $body->callable_id);
        foreach ($this->analysis->reachable_blocks as $id)
        {
            $block = $body->blocks[$id - 1];
            for ($index = $block->statement_start; $index < ($block->statement_start + $block->statement_count); ++$index) {
                $statement = $body->statements[$index];
                $end = $statement->call_start + $statement->call_count;
                for ($call = $statement->call_start; $call < $end; ++$call) {
                    $this->backend->callable_for($body, $body->calls[$call]->target_callable_id);
                }
            }
        }
    }
}

/** @compiler-api Lowered operation tags; each payload contract is on lowered_instruction. */
enum instruction_kind: string
{
    case parameter = 'parameter';
    case constant = 'constant';
    case byte_literal = 'byte_literal';
    case convert = 'convert';
    case call = 'call';
    case storage_begin = 'storage_begin';
    case storage_end = 'storage_end';
    case load = 'load';
    case store = 'store';
    case borrow = 'borrow';
    case address = 'address';
    case destroy = 'destroy';
    case default_construct = 'default_construct';
    case copy_construct = 'copy_construct';
    case move_construct = 'move_construct';
    case copy_assign = 'copy_assign';
    case binary = 'binary';
}

/**
 * @compiler-api Read-only row produced by lowering for emission's value operands.
 * type_id resolves through Lowered_Body::definition_for(); source_value_id is
 * provenance in the retained checked body, not another lowered value or slot ID.
 * Source value zero denotes an incoming parameter; its instruction carries the position.
 * A scalar and its call-storage address may share checked provenance but have distinct lowered IDs.
 * storage_slot_id is zero for value operands, otherwise this body's addressed storage.
 */
final class lowered_value
{
    public function __construct(
        public readonly int $source_value_id,
        public readonly int $type_id,
        public readonly int $storage_slot_id = 0,
    )
    {
    }
}

/**
 * @compiler-api Read-only storage row, produced by lowering and read by emission.
 * One per reached local, constructed temporary or materialized scalar argument; type_id supplies representation via the owning
 * Lowered_Body. source_local_id is provenance, not the slot ID. Slots and values
 * occupy distinct datasets; emitters must not equate their numeric positions.
 * A temporary has source_local_id zero and source_value_id as its checked origin.
 * incoming_parameter is a one-based parameter position for borrowed caller storage;
 * incoming_result denotes the caller-owned result destination. Otherwise zero denotes
 * a local allocation. Incoming storage has no callee destruction obligation.
 */
final class storage_slot
{
    public function __construct(
        public readonly int $source_local_id,
        public readonly int $type_id,
        public readonly int $source_value_id = 0,
        public readonly int $incoming_parameter = 0,
        public readonly bool $incoming_result = false,
    )
    {
    }
}

/**
 * @compiler-api Read-only store payload: projected address and one-based value_id in the
 * same Lowered_Body. The existing value is copied into storage; no result is made.
 */
final class store_operands
{
    public function __construct(
        public readonly storage_address $address,
        public readonly int $value_id,
    )
    {
        if ($value_id <= 0) {
            throw new \InvalidArgumentException('Store requires a slot and a value');
        }
    }
}

/** @compiler-api Call target, argument value-ID range and optional constructor destination in this body's slots. */
final class call_operands
{
    public function __construct(
        public readonly callable_binding $target,
        public readonly int $argument_start,
        public readonly int $argument_count,
        public readonly int $destination_slot_id = 0,
    )
    {
        if (($argument_start < 0) || ($argument_count !== $target->signature->count)) {
            throw new \InvalidArgumentException('Invalid lowered call arguments');
        }
    }
}

/** @compiler-api Unary backend conversion; IDs refer to the containing lowered body. */
final class conversion_operands
{
    /** @compiler-internal Lowering constructs this payload from a resolved conversion. */
    public function __construct(public readonly int $input_value_id, public readonly integer_adaptation $operation)
    {
        if (($input_value_id <= 0) || ($operation === integer_adaptation::identity)) {
            throw new \LogicException('Invalid lowered conversion');
        }
    }
}

/** @compiler-api Native binary primitives selected by lowering, independently of source spelling. */
enum binary_operation: string {
    case add_wrap = 'add_wrap';
    case less_signed = 'less_signed';
    case less_unsigned = 'less_unsigned';
}

/** @compiler-api Two earlier lowered values and their selected native binary operation. */
final class binary_operands {
    public function __construct(public readonly int $left, public readonly int $right, public readonly binary_operation $operation)
    {
    }
}

/**
 * @compiler-api Read-only instruction produced by lowering for ordered emission.
 * All fields are readable. Payload by kind: constant = exact decimal string;
 * byte_literal = shared decoded byte contents;
 * parameter = one-based incoming position; convert = conversion_operands;
 * call = call_operands with shared target; binary = binary_operands;
 * storage_begin/storage_end = one shared storage_transition around element lifecycle;
 * load/borrow = storage_address; store = store_operands; destroy = destruction_operands; copy_construct = construction_operands.
 * Borrow produces an address without loading bytes; destruction produces no value.
 * result_value_id is the produced value in this plan, zero for stores/void calls.
 * Operands are defined before use; load/store types match exactly. These are
 * producer guarantees, not a claim that this constructor validates the full plan.
 * source_node_id belongs to retained source syntax and is provenance only.
 * Native port: represent the alternatives as a tagged union.
 */
final class lowered_instruction
{
    /** Require a payload and result-presence shape consistent with the instruction tag. */
    public function __construct(
        public readonly instruction_kind $kind,
        public readonly int $source_node_id,

        // One-based lowered value ID; zero for stores and calls without a result.
        public readonly int $result_value_id,
        public readonly \prepare_backend\abi_target|string|call_operands|storage_transition|int|storage_address|store_operands|conversion_operands|binary_operands|destruction_operands|construction_operands|copy_assignment_operands|\check_bodies\byte_literal $payload,
    )
    {
        $valid = match ($kind)
        {
            instruction_kind::binary => ($payload instanceof binary_operands) && ($result_value_id > 0),
            instruction_kind::parameter => is_int($payload) && ($payload > 0) && ($result_value_id > 0),
            instruction_kind::convert => ($payload instanceof conversion_operands) && ($result_value_id > 0),
            instruction_kind::byte_literal => ($payload instanceof \check_bodies\byte_literal) && ($result_value_id > 0),
            instruction_kind::constant => is_string($payload) && ($result_value_id > 0),
            instruction_kind::storage_begin, instruction_kind::storage_end => ($payload instanceof storage_transition) && ($result_value_id === 0),
            instruction_kind::call => ($payload instanceof call_operands) && ($result_value_id >= 0),
            instruction_kind::load, instruction_kind::borrow => ($payload instanceof storage_address) && ($result_value_id > 0),
            instruction_kind::default_construct => ($payload instanceof \prepare_backend\abi_target)
                && ($payload->lifecycle_operation?->kind === \type_model\lifecycle_operation_kind::default_construct) && ($result_value_id > 0),
            instruction_kind::copy_construct, instruction_kind::move_construct => ($payload instanceof construction_operands) && ($result_value_id === 0),
            instruction_kind::copy_assign => ($payload instanceof copy_assignment_operands) && ($result_value_id === 0),
            instruction_kind::destroy => ($payload instanceof destruction_operands) && ($result_value_id === 0),
            instruction_kind::address => ($payload instanceof storage_address) && ($result_value_id === 0),
            instruction_kind::store => ($payload instanceof store_operands) && ($result_value_id === 0),
        };
        if (!$valid) {
            throw new \InvalidArgumentException('Invalid lowered instruction payload or result');
        }
    }
}

/**
 * @compiler-api Read-only block exit: return an existing lowered value, or zero
 * for void. source_node_id anchors the return/implicit exit in retained syntax.
 * Produced by lowering; emission uses the callable's prepared return contract.
 */
final class return_terminator
{
    public function __construct(
        public readonly int $value_id,

        // Origin is the return node, or the body node for implicit void fallthrough.
        public readonly int $source_node_id,
    )
    {
    }
}

/**
 * @compiler-api Read-only instruction range and terminator for LLVM emission.
 * instruction_start/count select a zero-based contiguous range in Lowered_Body;
 * the terminator is separate from that range. Edges refer to this body's block IDs.
 */
final class basic_block
{
    public function __construct(

        // Zero-based range in the body's instruction dataset.
        public readonly int $instruction_start,
        public readonly int $instruction_count,
        public readonly return_terminator|jump_terminator|branch_terminator $terminator,
    )
    {
    }
}

/**
 * @compiler-api Read-only module-entry plan produced by Native_Entry preparation.
 * Emission reads all fields; entry is the shared language callable binding,
 * native_bits/conversion adapt its result, link_name/calling_convention describe
 * the probed hosted entry. No source-level implicit conversion is authorized here.
 * Current support: parameterless C main with direct integer return, no attributes.
 */
final class native_entry_plan implements \compile\Step_Result
{
    public function __construct(
        public readonly callable_binding $entry,
        public readonly int $native_bits,
        public readonly integer_adaptation $conversion,
        public readonly string $link_name,
        public readonly string $calling_convention,
    )
    {
    }
}

/** @compiler-api Unconditional edge to a block in this lowered body. */
final class jump_terminator {
    public function __construct(public readonly int $target)
    {
    }
}

/** @compiler-api Integer condition: nonzero takes first, zero takes second. */
final class branch_terminator {
    public function __construct(public readonly int $value_id, public readonly int $first, public readonly int $second)
    {
    }
}

/** @compiler-api Destroy an owned object at a prepared destination using its selected ABI target. */
final class destruction_operands
{
    public function __construct(public readonly storage_address $destination, public readonly abi_target $target)
    {
        if (($target->lifecycle_operation?->kind !== \type_model\lifecycle_operation_kind::destroy)) {
            throw new \InvalidArgumentException('Destruction requires storage and a cleanup target');
        }
    }
}

/** @compiler-api Construct fresh storage; a null target denotes checked trivial value copying only. */
final class construction_operands
{
    public function __construct(public readonly storage_address $destination, public readonly int $source_value_id,
        public readonly ?abi_target $target)
    {
        if (($source_value_id <= 0)
            || (($target !== null) && !in_array($target->lifecycle_operation?->kind, [\type_model\lifecycle_operation_kind::copy_construct, \type_model\lifecycle_operation_kind::move_construct], true))) {
            throw new \InvalidArgumentException('Construction requires storage, a source and a construction target');
        }
    }
}

/** @compiler-api Update live storage from a borrowed source; identity and aliases are preserved. */
final class copy_assignment_operands
{
    public function __construct(public readonly storage_address $destination, public readonly int $source_value_id,
        public readonly abi_target $target)
    {
        if (($source_value_id <= 0) || ($target->lifecycle_operation?->kind !== \type_model\lifecycle_operation_kind::copy_assign)) {
            throw new \InvalidArgumentException('Copy assignment requires a source and an assignment target');
        }
    }
}

/** Address prepared once: root/projected slot, or internal native slot (slot_id zero, positive address_id). */
final class storage_address
{
    /** @param list<address_projection> $projections Ordered lowered field/index operands. */
    public function __construct(public readonly int $slot_id, public readonly array $projections = [],
        public readonly int $address_id = 0)
    {
        if (($slot_id < 0) || ($address_id < 0) || (($slot_id === 0)
            ? (($address_id === 0) || ($projections !== [])) : (($projections === []) !== ($address_id === 0)))) {
            throw new \InvalidArgumentException('Invalid storage address');
        }
    }
}

/** Lowered location operand; index values refer to this body's lowered value domain. */
final class address_projection {
    public function __construct(public readonly \check_bodies\projection_kind $kind,
        public readonly int $operand, public readonly int $type_id)
    {
    }
}

/** @compiler-api Paired prefix transition around ordinary element lifecycle instructions; arguments are consumed at begin only. */
final class storage_transition
{
    /** Only checked push/pop calls may reserve an internal element address and later commit their count change. */
    public function __construct(public readonly call_operands $call, public readonly storage_address $destination)
    {
        if (!in_array($call->target->storage?->role, [\type_model\storage_role::push, \type_model\storage_role::pop], true)
            || ($call->destination_slot_id !== 0) || ($destination->slot_id !== 0)) {
            throw new \InvalidArgumentException('Invalid element lifecycle transition');
        }
    }
}
