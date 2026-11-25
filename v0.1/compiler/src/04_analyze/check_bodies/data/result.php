<?php
declare(strict_types=1);
namespace check_bodies;
/** Fixed body membership and retained dependencies; construction does not run semantic checking. */
final class Checked_Body {
    public readonly int $callable_id;
    private array $values /** vector<Typed_Value> */ = [];
    private array $calls /** vector<Typed_Call> */ = [];
    private array $statements /** vector<Typed_Statement> */ = [];
    private array $scopes /** vector<Typed_Scope> */ = [];
    private array $arguments /** vector<Typed_Argument> */ = [];
    private array $blocks /** vector<Typed_Block> */ = [];
    private array $types /** hash<\type_model\Type_Record,int> */ = [];
    private array $signatures /** hash<Signature_Dependency,int> */ = [];
    public function __construct(public readonly \resolve_types\Callable_Input $input,
        public readonly \resolve_symbols\Symbol_Resolution $names, public readonly bool $falls_through,
        public readonly ?\resolve_types\Local_Types $local_types,
        array $values /** vector<Typed_Value> */, array $calls /** vector<Typed_Call> */,
        array $statements /** vector<Typed_Statement> */, array $scopes /** vector<Typed_Scope> */,
        array $arguments /** vector<Typed_Argument> */, array $blocks /** vector<Typed_Block> */,
        array $types /** hash<\type_model\Type_Record,int> */, array $signatures /** hash<Signature_Dependency,int> */) {
        if ($names->owner !== $input->owner) { throw new \LogicException('Body requires exact name owner'); }
        if ($local_types !== null) {
            if (($local_types->names !== $names) || ($local_types->instance !== $input->instance)) { throw new \LogicException('Body requires exact local type owner'); }
        }
        $this->callable_id = $input->callable_id;
        foreach ($values as $row) { $this->values[] = $row; }
        foreach ($calls as $row) { $this->calls[] = $row; }
        foreach ($statements as $row) { $this->statements[] = $row; }
        foreach ($scopes as $row) { $this->scopes[] = $row; }
        foreach ($arguments as $row) { $this->arguments[] = $row; }
        foreach ($blocks as $row) { $this->blocks[] = $row; }
        foreach ($types as $id => $row) { $this->types[$id] = $row; }
        foreach ($signatures as $id => $row) {
            if ($id !== $row->callable_id) { throw new \LogicException('Checked signature key differs from callable'); }
            $this->signatures[$id] = $row;
        }
    }
    public function value_count(): int { return q_count($this->values); }
    public function value_for(int $id): Typed_Value {
        if (($id < 1) || ($id > q_count($this->values))) { throw new \OutOfBoundsException('Missing checked value'); }
        return $this->values[$id - 1];
    }
    public function call_count(): int { return q_count($this->calls); }
    public function call_for(int $id): Typed_Call {
        if (($id < 1) || ($id > q_count($this->calls))) { throw new \OutOfBoundsException('Missing checked call'); }
        return $this->calls[$id - 1];
    }
    public function statement_count(): int { return q_count($this->statements); }
    public function statement_at(int $id): Typed_Statement {
        if (($id < 0) || ($id >= q_count($this->statements))) { throw new \OutOfBoundsException('Missing checked statement'); }
        return $this->statements[$id];
    }
    public function scope_count(): int { return q_count($this->scopes); }
    public function scope_for(int $id): Typed_Scope {
        if (($id < 1) || ($id > q_count($this->scopes))) { throw new \OutOfBoundsException('Missing checked scope'); }
        return $this->scopes[$id - 1];
    }
    public function argument_count(): int { return q_count($this->arguments); }
    public function argument_at(int $id): Typed_Argument {
        if (($id < 0) || ($id >= q_count($this->arguments))) { throw new \OutOfBoundsException('Missing checked argument'); }
        return $this->arguments[$id];
    }
    public function block_count(): int { return q_count($this->blocks); }
    public function block_at(int $id): Typed_Block {
        if (($id < 0) || ($id >= q_count($this->blocks))) { throw new \OutOfBoundsException('Missing checked block'); }
        return $this->blocks[$id];
    }
    public function type_dependencies(): array /** hash<\type_model\Type_Record,int> */ { return $this->types; }
    public function signature_dependencies(): array /** hash<Signature_Dependency,int> */ { return $this->signatures; }
    public function flow_blocks(): array /** vector<Typed_Block> */ { return $this->blocks; }
    public function entry_parameter_count(): int { return $this->names->runtime_parameter_count(); }
    public function signature_for(int $callable_id): Signature_Dependency {
        if (!isset($this->signatures[$callable_id])) { throw new \OutOfBoundsException('Missing checked callable dependency'); }
        return $this->signatures[$callable_id];
    }
    public function definition_for(int $type_id): \type_model\Named_Definition {
        if (!isset($this->types[$type_id])) { throw new \OutOfBoundsException('Missing checked type dependency'); }
        $definition = $this->types[$type_id]->definition;
        if ($definition === null) { throw new \LogicException('Missing checked value type definition'); }
        return $definition;
    }
    public function local_type_for(int $local_id): int {
        $locals = $this->local_types;
        if ($locals === null) { throw new \OutOfBoundsException('Body has no local types'); }
        return $locals->type_for($local_id);
    }
    public function local_passing(int $local_id): int {
        $this->names->local_for($local_id);
        $signature = $this->signature_for($this->callable_id);
        $passing = \type_model\PASS_VALUE;
        if ($local_id < $signature->parameter_count() + 1) { $passing = $signature->representation->parameter_passing($local_id - 1); }
        return $passing;
    }
    public function allocation_effect_for(int $callable_id): ?\type_model\Allocation_Effect {
        $dependency = $this->signature_for($callable_id);
        if ($dependency->storage !== null) { return $dependency->storage->allocation_effect(); }
        if ($dependency->external !== null) { return $dependency->external->signature->allocation_effect; }
        return null;
    }
    public function argument_for(int $call_id, int $position): Typed_Argument {
        $call = $this->call_for($call_id);
        if (($position < 1) || ($position > $call->argument_count)) { throw new \OutOfBoundsException('Missing checked argument'); }
        return $this->argument_at($call->argument_start + $position - 1);
    }
    public function conversion_for(int $value_id): Conversion_Value {
        if (($value_id < 1) || ($value_id > q_count($this->values))) { throw new \LogicException('Invalid or cyclic checked conversion'); }
        $value = $this->value_for($value_id);
        $conversion = $value->conversion();
        if ($conversion->input_value_id >= $value_id) { throw new \LogicException('Invalid or cyclic checked conversion'); }
        return $conversion;
    }
    public function operation_for(int $value_id): Operation_Value {
        if (($value_id < 1) || ($value_id > q_count($this->values))) { throw new \LogicException('Invalid checked operation'); }
        $value = $this->value_for($value_id); $operation = $value->operation();
        if (($operation->contract->size() !== 2) || ($value->type_id !== $operation->contract->result_type)) { throw new \LogicException('Invalid checked operation'); }
        $operands /** vector<int> */ = [$operation->left, $operation->right];
        for ($i = 0; $i < 2; $i++) {
            $id = $operands[$i];
            if (($id < 1) || ($id >= $value_id)) { throw new \LogicException('Invalid or cyclic checked operation operand'); }
            if ($this->value_for($id)->type_id !== $operation->contract->operand_at($i)) { throw new \LogicException('Invalid checked operation operand type'); }
        }
        return $operation;
    }
    public function place_type(Place $location): int {
        $type = $this->local_type_for($location->local_id);
        for ($i = 0; $i < $location->size(); $i++) {
            $projection = $location->at($i);
            $definition = $this->definition_for($type); $shape = $definition->representation;
            if ($projection->kind === \check_bodies\PROJECTION_FIELD) {
                if ($shape->kind() !== \type_model\REPRESENTATION_STRUCTURE) { throw new \LogicException('Invalid field lifetime projection'); }
                if ($projection->operand >= $shape->member_count()) { throw new \LogicException('Invalid field lifetime projection'); }
            } else {
                if ($projection->operand > q_count($this->values)) { throw new \LogicException('Missing checked projection index'); }
                $index = $this->value_for($projection->operand);
                if ($this->definition_for($index->type_id)->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { throw new \LogicException('Invalid index lifetime projection'); }
                if ($projection->kind === \check_bodies\PROJECTION_ELEMENT) {
                    $storage = $definition->element_storage;
                    if ($storage === null) { throw new \LogicException('Invalid dynamic element projection'); }
                    if ($storage->element_type !== $projection->type_id) { throw new \LogicException('Invalid dynamic element projection'); }
                } else {
                    if ($shape->kind() !== \type_model\REPRESENTATION_ARRAY) { throw new \LogicException('Invalid index lifetime projection'); }
                    if ($shape->element() !== $projection->type_id) { throw new \LogicException('Invalid index lifetime projection'); }
                }
            }
            $type = $projection->type_id; $this->definition_for($type);
        }
        return $type;
    }
}
