<?php
declare(strict_types=1);

/*
 * Role: Body tasks, typed operations, flow records and cursors.
 * Used by: Body_Worker; Flow_Builder; lifetime/lowering consumers
 * Flow: resolved syntax -> typed statements/values/blocks
 */

namespace check_bodies;

/**
 * @compiler-api Fixed input for one callable check. References are shared read-only;
 * construction alone does not establish current bindings or type readiness.
 * Selection captures these inputs before execution; checking validates their
 * association and joins require the exact selected project snapshots.
 * This task is temporary, not retained by Checked_Body or its consumers.
 */
final class body_check_task
{
    public readonly int $callable_id;
    public function __construct(
        public readonly \collect_symbols\symbol_record $owner,
        public readonly \resolve_symbols\Symbol_Resolution $names,
        public readonly \resolve_types\Type_Resolution $types,
        public readonly ?\instantiate\instance_context $instance = null,
    )
    {
        $this->callable_id = $instance?->context_id ?? $owner->symbol_id;
    }
}

/** @compiler-api Discriminant for typed_value payload; each kind requires consumer support. */
enum value_kind: string
{
    case integer_literal = 'integer_literal';
    case record_default = 'record_default';
    case default_construct = 'default_construct';
    case byte_literal = 'byte_literal';
    case call_result = 'call_result';
    case local_read = 'local_read';
    case local_borrow = 'local_borrow';
    case conversion = 'conversion';
    case operation = 'operation';
}

/** @compiler-api Checked statement vocabulary shared by lifetime analysis and lowering. */
enum statement_kind: string {
    case return_statement = 'return';
    case expression_statement = 'expression';
    case local_declaration = 'local_declaration';
    case assignment = 'assignment';
    case condition = 'condition';
}

/** @compiler-api Checking boundary requesting conversion; no automatic coercion is implied. */
enum conversion_use: string {
    case argument = 'argument';
    case return_value = 'return_value';
    case initialization = 'initialization';
    case assignment = 'assignment';
    case condition = 'condition';
}

/** @compiler-internal Short-lived selection request; IDs belong to one fixed type snapshot. */
final class conversion_request {
    public function __construct(public readonly int $source_type, public readonly int $destination_type,
        public readonly \type_model\conversion_purpose $purpose)
    {
    }
}

/** @compiler-internal Execution forms selected before creating checked values/calls. */
enum conversion_form: string {
    case identity = 'identity';
    case primitive = 'primitive';
    case provider_call = 'provider_call';
}

/** @compiler-internal Selection scratch record; only the resulting checked operation/call is retained. */
final class conversion_selection
{
    /** Keep primitive and provider execution mutually exclusive; identity carries neither payload. */
    public function __construct(public readonly conversion_form $form,
        public readonly ?conversion_kind $primitive = null, public readonly int $callable_id = 0)
    {
        if ((($form === conversion_form::primitive) !== ($primitive !== null))
            || (($form === conversion_form::provider_call) !== ($callable_id > 0)) || ($callable_id < 0)) {
            throw new \InvalidArgumentException('Invalid conversion selection');
        }
    }
}

/** @compiler-api Primitive conversion retained on a unary value; identity and provider calls have separate forms. */
enum conversion_kind: string {
    case integer_widen = 'integer_widen';
}

/** @compiler-api Unary conversion input in the same checked value dataset; the containing value supplies the result type. */
final class conversion_value
{
    /** @compiler-internal Checking constructs resolved unary values; consumers use Checked_Body::conversion_for. */
    public function __construct(public readonly int $input_value_id, public readonly conversion_kind $operation)
    {
        if ($input_value_id <= 0) {
            throw new \LogicException('A conversion node requires an input value');
        }
    }
}

/**
 * @compiler-api Read-only source_node_id/type_id/kind/payload from checking.
 * Payload is exact decimal text for integer_literal, one-based call ID for call_result,
 * root/projected place for local_read/local_borrow, null for record_default, conversion_value for a conversion,
 * operation_value for selected binary arithmetic, or shared byte_literal contents.
 * IDs require the containing Checked_Body;
 * no value row represents void.
 */
final class typed_value
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $source_node_id,
        public readonly int $type_id,
        public readonly value_kind $kind,

        // Literal contents, producing call ID, local ID or operation payload; native tagged union.
        public readonly string|int|conversion_value|operation_value|byte_literal|place|null $payload,
    )
    {
    }
}

/** @compiler-internal Resolved storage awaiting its consumer's access choice; never retained in Checked_Body. */
final class pending_place_value {
    public function __construct(public readonly int $source_node_id, public readonly int $type_id,
        public readonly place $location)
    {
    }
}

/** @compiler-api Literal bytes shared by checking/lowering; JSON renders a lossless binary-safe view on demand. */
final class byte_literal implements \JsonSerializable
{
    public function __construct(public readonly string $bytes)
    {
    }

    public function jsonSerialize(): array
    {
        return ['hex' => bin2hex($this->bytes)];
    }
}

/**
 * @compiler-api Read-only call effect: source_node_id, project target_callable_id, result_value_id.
 * Zero result ID means no produced value, not an invalid call. Calls stay in evaluation
 * order; a nonzero result points into the same checked body's values.
 * argument_start/count selects a contiguous range in arguments, in source order.
 * Evaluate each argument (including nested calls) left to right before this call.
 */
final class typed_call
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $source_node_id,
        public readonly int $target_callable_id,

        // One-based value ID; zero means this call produces no value.
        public readonly int $result_value_id,
        public readonly int $argument_start = 0,
        public readonly int $argument_count = 0,
    )
    {
    }
}

/**
 * @compiler-api Checked argument in its call's ordered range. value_id belongs to
 * values; parameter_type_id is the checked passing type; value_id already includes conversion.
 * This is value evaluation/conversion, not a lifetime or native passing contract.
 */
final class typed_argument
{
    /** @compiler-internal Producer-only construction; void cannot be an argument. */
    public function __construct(
        public readonly int $value_id,
        public readonly int $parameter_type_id,
        public readonly \type_model\argument_passing $passing = \type_model\argument_passing::value,
    )
    {
        if (($value_id <= 0) || ($parameter_type_id <= 0)) {
            throw new \LogicException('Invalid checked argument');
        }
    }
}

/** @compiler-internal Private continuation for a call's left-to-right argument traversal. */
final class expression_cursor
{
    public int $argument_index = 0;

    /** @compiler-internal Private worker state, never a shared stage result. */
    public function __construct(
        public readonly int $source_node_id,
        public readonly int $target_callable_id,
        public readonly int $return_type_id,
        public readonly int $argument_start,
        public readonly int $argument_count,
        public int $next_argument_id,
        public readonly int $receiver_index = -1,
    )
    {
    }
}

/** @compiler-api Local write semantics: value writes, direct construction, copy construction or assignment of a live object. */
enum local_write_kind: string {
    case value_copy = 'value_copy';
    case zero_initialize = 'zero_initialize';
    case direct_construct = 'direct_construct';
    case copy_construct = 'copy_construct';
    case copy_assign = 'copy_assign';
}

/** @compiler-api Construction selected for the caller-owned result, separate from local writes. */
enum return_kind: string
{
    case value = 'value';
    case store = 'store';
    case direct_construct = 'direct_construct';
    case copy_construct = 'copy_construct';
    case move_construct = 'move_construct';
}

/**
 * @compiler-api Read-only statement/evaluation plan from checking; all readonly fields readable.
 * value_id zero means no value; call_start/count is a zero-based segment in calls.
 * scope_id and target.local_id belong to names; target.projections select fields or checked index values; value_id already includes any conversion.
 * Only local initialization/assignment has a target local. All source anchors refer
 * to the exact owner frontend; this row is not a lowered instruction.
 */
final class typed_statement
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $source_node_id,
        public readonly statement_kind $kind,

        // One-based index in this body's values; zero means no value.
        public readonly int $value_id,

        // Contiguous evaluation-order segment in calls (zero-based start + count).
        public readonly int $call_start,
        public readonly int $call_count,

        // Scope/local IDs refer to this body's shared name-resolution owner.
        public readonly int $scope_id,

        // Nonzero only for initialization and assignment; never a value ID.
        public readonly ?place $target = null,
        public readonly local_write_kind $write = local_write_kind::value_copy,
        public readonly return_kind $return = return_kind::value,
    )
    {
        $writes_local = ($kind === statement_kind::local_declaration) || ($kind === statement_kind::assignment);
        if ((($return !== return_kind::value) && ($kind !== statement_kind::return_statement))
            || ($scope_id <= 0) || ($writes_local !== ($target !== null))
            || (($writes_local) && ($value_id <= 0))
            || (in_array($write, [local_write_kind::zero_initialize, local_write_kind::direct_construct, local_write_kind::copy_construct], true) && ($kind !== statement_kind::local_declaration))
            || (($write === local_write_kind::copy_assign) && ($kind !== statement_kind::assignment))) {
            throw new \LogicException('Invalid checked statement scope or destination');
        }
    }
}

// One range per resolved scope, indexed by scope ID - 1. Includes descendant
// statements. Empty blocks have zero count; nesting lives in the shared scopes.
/**
 * @compiler-api Read-only statement_start/count range, indexed by name scope ID; includes descendants.
 * Zero count represents an empty block; nesting belongs to shared name scopes.
 */
final class typed_scope
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $statement_start,
        public readonly int $statement_count,
    )
    {
        if (($statement_start < 0) || ($statement_count < 0)) {
            throw new \LogicException('Invalid checked scope range');
        }
    }
}

// Private worker traversal state; not retained in Checked_Body.
/** @compiler-internal Private checking traversal record; never handed to another process. */
final class body_cursor
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $scope_id,
        public readonly int $statement_start,
        public int $next_statement_id,
    )
    {
    }
}

/**
 * @compiler-internal Checking-owned reuse fact retaining a shared signature representation, not callee AST.
 * Downstream consumers use Checked_Body::signature_for rather than this record/map.
 */
final class signature_dependency
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $callable_id,
        public readonly int $representation_id,
        public readonly \type_model\representation_record $representation,
        public readonly ?\type_model\runtime_callable $external = null,
        public readonly ?\type_model\storage_function $storage = null,
    )
    {
    }
}

/** @compiler-api Ordered inputs and selected exact-type operation; IDs belong to the containing checked body. */
final class operation_value {
    public function __construct(public readonly int $left, public readonly int $right,
        public readonly \type_model\operation_contract $contract)
    {
    }
}

/** @compiler-internal Suspended syntax operation; private checking state. */
final class operation_cursor {
    public array $operands = [];

    public function __construct(public readonly int $source_node_id, public int $next_operand_id)
    {
    }
}

/**
 * @compiler-api Transient evaluation item yielded by Expression_Order; consumers read only
 * value_id and call_id after operands complete. Remaining fields are internal traversal state.
 */
final class evaluation_cursor {
    public int $next_operand = 0;

    public function __construct(public readonly int $value_id, public readonly int $call_id,
        public readonly int $operand_count, public readonly int $call_limit)
    {
    }
}

/** @compiler-api Typed control-flow exit; branch consumes the final condition statement. */
enum flow_end: string {
    case jump = 'jump';
    case branch = 'branch';
    case return_exit = 'return';
    case fallthrough = 'fallthrough';
}

/** @compiler-api Contiguous checked statements and explicit successors; IDs are callable-local. */
final class typed_block {
    public function __construct(public readonly int $statement_start, public readonly int $statement_count,
        public readonly int $scope_id, public readonly flow_end $end,
        public readonly int $first = 0, public readonly int $second = 0)
    {
    }
}

/** @compiler-internal Private continuation for conditional arms or a loop body. */
final class control_cursor
{
    public int $stage = 0;

    public function __construct(public readonly \parse\control_parts $parts, public readonly int $scope_id,
        public readonly int $body_block, public readonly int $alternative_block,
        public readonly int $join_block, public readonly int $loop_header = 0)
    {
    }
}

/** A location projection selects a field ordinal or evaluates one array index value. */
enum projection_kind: string {
    case field = 'field';
    case index = 'index';
    case element = 'element';
}

/** Checked projection; call_end partitions target evaluation before an assignment's right-hand side. */
final class place_projection
{
    public function __construct(public readonly projection_kind $kind, public readonly int $operand,
        public readonly int $type_id, public readonly int $call_end = 0)
    {
        if (($operand < 0) || ($type_id <= 0) || ($call_end < 0)
            || (($kind !== projection_kind::field) && ($operand === 0))) {
            throw new \InvalidArgumentException('Invalid checked location projection');
        }
    }
}

/** Compact location: a live storage root and ordered projections, without independent ownership. */
final class place
{
    /** @param list<place_projection> $projections Root-to-leaf path, empty for the local itself. */
    public function __construct(public readonly int $local_id, public readonly array $projections = [])
    {
        if ($local_id <= 0) {
            throw new \InvalidArgumentException('Invalid storage place');
        }
    }

    /** Dynamic element locations depend on the containing allocation until their access ends. */
    public function allocation_backed(): bool
    {
        foreach ($this->projections as $projection) {
            if ($projection->kind === projection_kind::element) {
                return true;
            }
        }
        return false;
    }

    /** Yield checked index operands without retaining a second projection/operand list. */
    public function indices(): \Generator
    {
        foreach ($this->projections as $projection) {
            if ($projection->kind !== projection_kind::field) {
                yield $projection;
            }
        }
    }
}

/** Private location continuation; index operands run on the shared expression cursor stack. */
final class place_cursor
{
    public int $position = 0;
    /** @var list<place_projection> */
    public array $projections = [];

    /** @param list<int> $nodes Projection syntax in root-to-leaf order. */
    public function __construct(public readonly int $source_node_id, public readonly int $local_id,
        public int $type_id, public readonly array $nodes, public readonly bool $write)
    {
    }
}
