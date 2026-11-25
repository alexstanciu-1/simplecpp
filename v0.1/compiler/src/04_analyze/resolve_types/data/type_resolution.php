<?php
declare(strict_types=1);
namespace resolve_types;
/** Assembled association snapshot; coordinator must finish all joins before publishing.
 * Checks coherence, not completeness of every semantic worker obligation.
 */
final class Type_Resolution {
    private array $locals /** hash<Local_Types,int> */ = [];
    private array $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
    private array $language_calls /** hash<int> */ = [];
    private array $conversion_calls /** hash<int> */ = [];
    private int $default_literal = 0;
    public function __construct(public readonly \type_model\Type_Store $types,
        public readonly \type_model\Type_Catalog $catalog, public readonly Entry_Contract $entry,
        public readonly Signature_Set $callables, array $locals /** vector<Local_Types> */,
        public readonly \resolve_symbols\Resolution_Set $names, public readonly \instantiate\Instance_Set $instances,
        array $families /** hash<\load_runtime\Family_Preparation_Result,int> */) {
        if ($callables->types !== $types) { throw new \LogicException('Signatures belong to a different canonical store'); }
        foreach ($families as $id => $prepared) {
            $context = $prepared->task->context;
            if (($id !== $context->instance_id) || ($instances->context_for($context->context_id) !== $context)) { throw new \LogicException('Prepared family differs from its accepted instance'); }
            $definition = $instances->instance_type($id);
            if ($definition === null) { throw new \LogicException('Prepared family has no accepted concrete type'); }
            if ($prepared->package->type_for($prepared->type_id)->language_type !== $definition) { throw new \LogicException('Prepared family package differs from its accepted type'); }
            $this->families[$id] = $prepared;
        }
        for ($i = 0; $i < $callables->size(); $i++) {
            $signature = $callables->at($i); $instance = $signature->input->instance;
            if ($instance !== null) {
                if ($instances->context_for($signature->callable_id) !== $instance) { throw new \LogicException('Stale signature instance'); }
            }
            $this->index_bindings($signature);
        }
        foreach ($locals as $result) {
            $id = $result->callable_id; $signature = $callables->for_callable($id);
            if ($signature === null) { throw new \LogicException('Local types have no signature'); }
            if (($signature->input->instance !== $result->instance) || ($signature->input->owner !== $result->names->owner)
                || ($names->for_symbol($result->names->owner->symbol_id) !== $result->names)
                || isset($this->locals[$id]) || ($result->size() === 0)) { throw new \LogicException('Invalid or duplicate local type owner'); }
            if (!$signature->input->owner->is_source()) { throw new \LogicException('Local types require a source body'); }
            $shape = $types->representation_by_id($signature->representation_id);
            if ($shape->member_count() !== $result->names->runtime_parameter_count()) { throw new \LogicException('Local parameter count differs from signature'); }
            for ($p = 0; $p < $shape->member_count(); $p++) {
                if ($result->type_for($p+1) !== $types->member_at($shape->member_first()+$p)->type_id) { throw new \LogicException('Local parameter type differs from signature'); }
            }
            for ($p = 1; $p < $result->size()+1; $p++) { $types->definition_for_type($result->type_for($p)); }
            $this->locals[$id] = $result;
        }
        $entry_signature = $callables->for_callable($entry->symbol->symbol_id);
        if ($entry_signature === null) { throw new \LogicException('Missing implicit entry signature'); }
        if (($entry_signature->input->owner !== $entry->symbol) || ($entry_signature->input->instance !== null)
            || ((int)$entry->symbol->source_fact()->declaration_node_id !== 0) || ($entry_signature->return_annotation_id !== 0)) { throw new \LogicException('Stale implicit entry signature'); }
        $shape = $types->representation_by_id($entry_signature->representation_id);
        if ($types->definition_for_type($shape->signature_return()) !== $entry->return_type) { throw new \LogicException('Entry signature differs from language return contract'); }
    }
    /** Flat numeric tuple keys replace nested PHP maps; no provider-name inference. */
    private static function language_key(int $role, int $type): string { return $role.':'.$type; }
    private static function conversion_key(int $purpose, int $source, int $destination): string { return $purpose.':'.$source.':'.$destination; }
    private function index_bindings(Callable_Signature $signature): void {
        $external = $signature->external; if ($external === null) { return; }
        $shape = $this->types->representation_by_id($signature->representation_id);
        $purpose = $external->conversion_purpose;
        if ($purpose !== null) {
            if ($shape->member_count() !== 1) { throw new \LogicException('Conversion requires one parameter'); }
            $source = $this->types->member_at($shape->member_first())->type_id; $destination = $shape->signature_return();
            $key = Type_Resolution::conversion_key($purpose,$source,$destination);
            if (($source === $destination) || isset($this->conversion_calls[$key])) { throw new \LogicException('Invalid or duplicate conversion binding'); }
            $this->conversion_calls[$key] = $signature->callable_id;
        }
        $role = $external->language_binding;
        if ($role !== null) {
            $type = $shape->signature_return();
            if ($role !== \type_model\BINDING_BYTE_LITERAL) {
                if ($shape->member_count() === 0) { throw new \LogicException('Language operation requires an operand'); }
                $type = $this->types->member_at($shape->member_first())->type_id;
            }
            $key = Type_Resolution::language_key($role,$type);
            if (isset($this->language_calls[$key])) { throw new \LogicException('Duplicate language operation binding'); }
            $this->language_calls[$key] = $signature->callable_id;
            if ($external->default_literal) {
                if ($this->default_literal !== 0) { throw new \LogicException('Duplicate default byte literal binding'); }
                $this->default_literal = $signature->callable_id;
            }
        }
    }
    public function language_callable(int $role, int $type): int {
        if (($role === \type_model\BINDING_BYTE_LITERAL) && ($type === 0)) { return $this->default_literal; }
        $key = Type_Resolution::language_key($role,$type); if (!isset($this->language_calls[$key])) { return 0; } return $this->language_calls[$key];
    }
    public function conversion_callable(int $purpose, int $source, int $destination): int {
        $key = Type_Resolution::conversion_key($purpose,$source,$destination); if (!isset($this->conversion_calls[$key])) { return 0; } return $this->conversion_calls[$key];
    }
    public function for_symbol(int $id): ?Callable_Signature {
        if ($id > \collect_symbols\MAX_SYMBOL_ID) { return null; } return $this->callables->for_callable($id);
    }
    public function for_callable(int $id): ?Callable_Signature { return $this->callables->for_callable($id); }
    public function locals_for(int $id): ?Local_Types { if (!isset($this->locals[$id])) { return null; } return $this->locals[$id]; }
    public function family_for(int $id): ?\load_runtime\Family_Preparation_Result { if (!isset($this->families[$id])) { return null; } return $this->families[$id]; }
    public function prepared_families(): array /** hash<\load_runtime\Family_Preparation_Result,int> */ { return $this->families; }
    public function signature_for(int $id): \type_model\Representation {
        $signature = $this->callables->for_callable($id); if ($signature === null) { throw new \OutOfBoundsException('Missing resolved callable'); }
        return $this->types->representation_by_id($signature->representation_id);
    }
    public function parameter_type_for(int $id, int $position): int {
        $shape = $this->signature_for($id);
        if (($position < 1) || ($position > $shape->member_count())) { throw new \OutOfBoundsException('Missing signature parameter'); }
        return $this->types->member_at($shape->member_first()+$position-1)->type_id;
    }
    public function definition_for(int $id): \type_model\Named_Definition { return $this->types->definition_for_type($id); }
    public function boolean_type(): int {
        $definition = $this->catalog->boolean_type; if ($definition === null) { return 0; }
        return $this->types->find_type($definition->name,$definition->namespace_name);
    }
    public function integer_literal_type(): int {
        $definition = $this->catalog->integer_literal_type; $id = $this->types->find_type($definition->name,$definition->namespace_name);
        if ($id === 0) { throw new \LogicException('Integer literal type is not prepared'); }
        if ($this->definition_for($id) !== $definition) { throw new \LogicException('Integer literal type differs from catalog'); } return $id;
    }
    public function body_signatures(): array /** vector<Callable_Signature> */ {
        $out /** vector<Callable_Signature> */ = [];
        for ($i = 0; $i < $this->callables->size(); $i++) { $signature = $this->callables->at($i); if ($signature->input->owner->is_source()) { $out[] = $signature; } }
        return $out;
    }
}
