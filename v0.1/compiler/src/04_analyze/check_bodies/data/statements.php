<?php
declare(strict_types=1);
namespace check_bodies;
const STATEMENT_RETURN = 1;
const STATEMENT_EXPRESSION = 2;
const STATEMENT_LOCAL_DECLARATION = 3;
const STATEMENT_ASSIGNMENT = 4;
const STATEMENT_CONDITION = 5;
const WRITE_VALUE_COPY = 1;
const WRITE_ZERO_INITIALIZE = 2;
const WRITE_DIRECT_CONSTRUCT = 3;
const WRITE_COPY_CONSTRUCT = 4;
const WRITE_COPY_ASSIGN = 5;
const RETURN_VALUE = 1;
const RETURN_STORE = 2;
const RETURN_DIRECT_CONSTRUCT = 3;
const RETURN_COPY_CONSTRUCT = 4;
const RETURN_MOVE_CONSTRUCT = 5;
/** Contiguous call argument range; result zero represents a void call. */
final class Typed_Call {
    public function __construct(public readonly int $source_node_id, public readonly int $target_callable_id,
        public readonly int $result_value_id, public readonly int $argument_start = 0, public readonly int $argument_count = 0) {}
}
final class Typed_Argument {
    public function __construct(public readonly int $value_id, public readonly int $parameter_type_id,
        public readonly int $passing = 0) {
        if (($value_id < 1) || ($parameter_type_id < 1)) { throw new \LogicException('Invalid checked argument'); }
        \type_model\Semantic_Modes::passing_name($passing);
    }
}
/** Statement/evaluation plan, never a lowered instruction or a lifetime grant. */
final class Typed_Statement {
    public function __construct(public readonly int $source_node_id, public readonly int $kind,
        public readonly int $value_id, public readonly int $call_start, public readonly int $call_count,
        public readonly int $scope_id, public readonly ?Place $target = null,
        public readonly int $write_kind = 1, public readonly int $return_mode = 1) {
        if (($kind < 1) || ($kind > 5) || ($write_kind < 1) || ($write_kind > 5) || ($return_mode < 1) || ($return_mode > 5)) {
            throw new \InvalidArgumentException('Invalid checked statement tag');
        }
        $writes_local = ($kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) || ($kind === \check_bodies\STATEMENT_ASSIGNMENT);
        if (($return_mode !== \check_bodies\RETURN_VALUE) && ($kind !== \check_bodies\STATEMENT_RETURN)) { throw new \LogicException('Invalid checked return mode'); }
        if (($scope_id < 1) || ($writes_local !== ($target !== null))) { throw new \LogicException('Invalid checked statement scope or destination'); }
        if ($writes_local) { if ($value_id < 1) { throw new \LogicException('Invalid checked local value'); } }
        $constructs = ($write_kind === \check_bodies\WRITE_ZERO_INITIALIZE) || ($write_kind === \check_bodies\WRITE_DIRECT_CONSTRUCT) || ($write_kind === \check_bodies\WRITE_COPY_CONSTRUCT);
        if ($constructs && ($kind !== \check_bodies\STATEMENT_LOCAL_DECLARATION)) { throw new \LogicException('Invalid checked construction destination'); }
        if (($write_kind === \check_bodies\WRITE_COPY_ASSIGN) && ($kind !== \check_bodies\STATEMENT_ASSIGNMENT)) { throw new \LogicException('Invalid checked assignment destination'); }
    }
}
final class Typed_Scope {
    public function __construct(public readonly int $statement_start, public readonly int $statement_count) {
        if (($statement_start < 0) || ($statement_count < 0)) { throw new \LogicException('Invalid checked scope range'); }
    }
}
