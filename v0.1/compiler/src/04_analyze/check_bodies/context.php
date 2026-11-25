<?php
declare(strict_types=1);
namespace check_bodies;

/** Per-body checking context: exact accepted inputs and the dependencies actually read.
 * Calls retain signatures; they never recursively check or wait for another body.
 * Discard the context after an error. The supplied type snapshot stays read-only.
 */
final class Body_Context {
    public readonly int $integer_literal_type;
    private ?\resolve_types\Local_Types $locals = null;
    private array $type_rows /** hash<\type_model\Type_Record,int> */ = [];
    private array $signature_rows /** hash<Signature_Dependency,int> */ = [];
    public function __construct(public readonly \resolve_types\Callable_Input $input,
        public readonly \resolve_symbols\Symbol_Resolution $names,
        public readonly \resolve_types\Type_Resolution $types) {
        $owner = $input->owner;
        if (!$owner->is_source()) { throw new \LogicException('Body task requires a source callable'); }
        if ($owner->is_template()) {
            $types->instances->template_checks()->require_definition($owner, $types->names, $types->catalog);
        }
        if ($names->owner !== $owner) { throw new \LogicException('Body task requires current callable name bindings'); }
        if ($types->names->for_symbol($owner->symbol_id) !== $names) { throw new \LogicException('Body task requires current callable name bindings'); }
        if ($names->scopes_count() === 0) { throw new \LogicException('Body task requires a callable root scope'); }
        if ((int)$names->scopes_at(0)->block_node_id !== (int)$owner->source_fact()->body_node_id) {
            throw new \LogicException('Body task requires current callable name bindings');
        }
        $signature = $types->for_callable($input->callable_id);
        if ($signature === null) { throw new \LogicException('Body task requires its current resolved callable contract'); }
        if (($signature->input->owner !== $owner) || ($signature->input->instance !== $input->instance)) {
            throw new \LogicException('Body task requires its current resolved callable contract');
        }
        $local_types = $types->locals_for($input->callable_id);
        if ($names->locals_count() === 0) {
            if ($local_types !== null) { throw new \LogicException('Body task requires current resolved local types'); }
        } else {
            if ($local_types === null) { throw new \LogicException('Body task requires current resolved local types'); }
            if (($local_types->names !== $names) || ($local_types->instance !== $input->instance)) {
                throw new \LogicException('Body task requires current resolved local types');
            }
        }
        $this->locals = $local_types;
        $this->integer_literal_type = $types->integer_literal_type();
    }
    public function local_types(): ?\resolve_types\Local_Types { return $this->locals; }
    public function local_type(int $local_id): int {
        $locals = $this->locals;
        if ($locals === null) { throw new \LogicException('Missing local type associations'); }
        $id = $locals->type_for($local_id);
        $this->retain_type($id);
        return $id;
    }
    public function signature(int $callable_id): Signature_Dependency {
        if (isset($this->signature_rows[$callable_id])) { return $this->signature_rows[$callable_id]; }
        $signature = $this->types->for_callable($callable_id);
        if ($signature === null) { throw new \LogicException('Missing resolved function signature'); }
        $dependency = Signature_Dependency::capture($this->types->types, $signature);
        $this->retain_type($dependency->representation->signature_return());
        for ($i = 0; $i < $dependency->parameter_count(); $i++) { $this->retain_type($dependency->parameter_type($i)); }
        $this->signature_rows[$callable_id] = $dependency;
        return $dependency;
    }
    /** Iterative implicit element closure, including storage lifecycle dependencies.
     * Structure fields are not implicitly traversed: preserve the prototype's rule.
     */
    public function retain_type(int $type_id): void {
        $pending /** vector<int> */ = [$type_id];
        $position = 0;
        while ($position < q_count($pending)) {
            $id = $pending[$position]; $position++;
            if (isset($this->type_rows[$id])) { continue; }
            $record = $this->types->types->type_by_id($id);
            $definition = $record->definition;
            if ($definition === null) { throw new \LogicException('Body dependency requires a resolved type'); }
            $this->type_rows[$id] = $record;
            $storage = $definition->element_storage;
            if ($storage !== null) { $pending[] = $storage->element_type; }
            if ($definition->representation->kind() === \type_model\REPRESENTATION_ARRAY) {
                $pending[] = $definition->representation->element();
            }
        }
    }
    public function type_dependencies(): array /** hash<\type_model\Type_Record,int> */ { return $this->type_rows; }
    public function signature_dependencies(): array /** hash<Signature_Dependency,int> */ { return $this->signature_rows; }
}
