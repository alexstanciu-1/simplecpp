<?php
declare(strict_types=1);
namespace resolve_types;
/** Exact declaration plus optional accepted concrete instance; no union-valued task input. */
final class Callable_Input {
    public readonly int $callable_id;
    public function __construct(public readonly \collect_symbols\Symbol_Record $owner,
        public readonly ?\instantiate\Instance_Context $instance = null) {
        if ($instance !== null) {
            if ($instance->definition !== $owner) { throw new \LogicException('Callable input requires its exact instance declaration'); }
        }
        $this->callable_id = $instance === null ? $owner->symbol_id : $instance->context_id;
    }
    public function context(): \instantiate\Instance_Context {
        if ($this->instance !== null) { return $this->instance; }
        return \instantiate\Instance_Context::ordinary($this->owner);
    }
}
/** Ordered authoritative definitions and explicit semantic passing; no canonical allocation. */
final class Signature_Request {
    private array $parameters /** vector<\type_model\Named_Definition> */ = [];
    private array $passing_modes /** vector<int> */ = [];
    public function __construct(public readonly Callable_Input $input, public readonly int $return_annotation_id,
        public readonly \type_model\Named_Definition $definition,
        array $parameters /** vector<\type_model\Named_Definition> */, array $passing /** vector<int> */) {
        if ($return_annotation_id < 0) { throw new \LogicException('Invalid return annotation'); }
        if (q_count($passing) !== 0) {
            if (q_count($passing) !== q_count($parameters)) { throw new \LogicException('Signature passing count mismatch'); }
        }
        foreach ($parameters as $i => $parameter) {
            $mode = \type_model\PASS_VALUE;
            if (q_count($passing) !== 0) { $mode = $passing[$i]; }
            \type_model\Semantic_Modes::passing_name($mode);
            $this->parameters[] = $parameter; $this->passing_modes[] = $mode;
        }
    }
    public function parameter_count(): int { return q_count($this->parameters); }
    public function parameter_at(int $index): \type_model\Named_Definition {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \OutOfBoundsException('Missing signature parameter'); }
        return $this->parameters[$index];
    }
    public function passing_at(int $index): int {
        if (($index < 0) || ($index >= q_count($this->passing_modes))) { throw new \OutOfBoundsException('Missing signature passing mode'); }
        return $this->passing_modes[$index];
    }
}
