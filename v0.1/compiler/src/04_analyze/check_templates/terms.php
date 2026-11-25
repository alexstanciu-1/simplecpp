<?php
declare(strict_types=1);
namespace check_templates;
/** Interpret already-bound declarations. No concrete specialization or canonical type creation. */
final class Terms {
    private array $declaration_index /** hash<bool,int> */ = [];
    private array $binding_index /** hash<bool,int> */ = [];
    private array $declaration_rows /** vector<\collect_symbols\Symbol_Record> */ = [];
    private array $binding_rows /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
    private ?Template_Diagnostic $diagnostic_value = null;
    public function __construct(public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names, public readonly \type_model\Type_Catalog $catalog) {}
    public function declaration(int $id): \collect_symbols\Symbol_Record {
        $owner = $this->symbols->symbol_by_id($id);
        if (!isset($this->declaration_index[$id])) { $this->declaration_index[$id] = true; $this->declaration_rows[] = $owner; }
        return $owner;
    }
    public function bindings(\collect_symbols\Symbol_Record $owner): \resolve_symbols\Symbol_Resolution {
        $result = $this->names->for_symbol($owner->symbol_id);
        if ($result === null) { throw new \LogicException('Missing symbolic declaration bindings'); }
        if ($result->owner !== $owner) { throw new \LogicException('Stale symbolic declaration bindings'); }
        if (!isset($this->binding_index[$owner->symbol_id])) { $this->binding_index[$owner->symbol_id] = true; $this->binding_rows[] = $result; }
        return $result;
    }
    public function declarations(): array /** vector<\collect_symbols\Symbol_Record> */ { return $this->declaration_rows; }
    public function resolutions(): array /** vector<\resolve_symbols\Symbol_Resolution> */ { return $this->binding_rows; }
    public function diagnostic(): ?Template_Diagnostic { return $this->diagnostic_value; }
    public function fail(\collect_symbols\Symbol_Record $owner, int $id, string $reason): void {
        $frontend = $owner->source_frontend(); $node = $frontend->tree->row($id);
        $this->diagnostic_value = new Template_Diagnostic($frontend->tokens->source->path,(int)$node->start,(int)$node->length,$reason);
        throw new \RuntimeException($reason);
    }
    public static function text(\collect_symbols\Symbol_Record $owner, int $id): string {
        return \collect_symbols\File_Collector::name_text($owner->source_frontend(),$id);
    }
    public function arguments(\collect_symbols\Symbol_Record $owner, int $first): array /** vector<int> */ {
        $out /** vector<int> */ = []; $tree = $owner->source_frontend()->tree; $id = $first;
        while ($id !== 0) { $out[] = $id; $id = (int)$tree->row($id)->next_sibling; }
        return $out;
    }
    public function annotation(\collect_symbols\Symbol_Record $owner, int $root,
        array $substitutions /** vector<Type_Term> */): Type_Term {
        $tree = $owner->source_frontend()->tree; $names = $this->bindings($owner);
        $first = new Annotation_Visit(); $first->node = $root;
        $pending /** vector<Annotation_Visit> */ = [$first]; $used = 1; $terms /** hash<Type_Term,int> */ = [];
        while ($used > 0) {
            $used = $used - 1; $visit = $pending[$used]; $id = (int)$visit->node; $node = $tree->row($id); $kind = (int)$node->kind;
            if ($kind === \parse\SYNTAX_TEMPLATE_APPLICATION) {
                $parts = \parse\Syntax_Access::template_application_parts($tree,$id);
                $arguments = $this->arguments($owner,(int)$parts->first_argument_id);
                if (!$visit->finish) {
                    $finish = new Annotation_Visit(); $finish->node = $id; $finish->finish = true;
                    if ($used === q_count($pending)) { $pending[] = $finish; } else { $pending[$used] = $finish; } $used++;
                    $position = q_count($arguments);
                    while ($position > 0) {
                        $position = $position - 1; $child = new Annotation_Visit(); $child->node = $arguments[$position];
                        if ($used === q_count($pending)) { $pending[] = $child; } else { $pending[$used] = $child; } $used++;
                    }
                    continue;
                }
                $target = $this->declaration($names->name_for((int)$parts->name_id)->target_id);
                $values /** vector<Type_Term> */ = [];
                foreach ($arguments as $argument) {
                    $value = $terms[$argument]; $this->forwarded_type($value,$owner,$id);
                    if (!$target->is_source()) {
                        if ($target->provider()->kind() === \collect_symbols\PROVIDER_FAMILY) { $this->family_argument($value,$owner,$id); }
                        elseif ($value->dependent) { $this->fail($owner,$id,'Generic contract does not guarantee the provider storage family element requirements'); }
                    }
                    $values[] = $value;
                }
                $terms[$id] = Type_Term::application($target->symbol_id,$values);
            } elseif ($kind === \parse\SYNTAX_INTEGER_LITERAL) {
                $terms[$id] = Type_Term::constant('literal:' . Terms::text($owner,$id));
            } elseif ($kind === \parse\SYNTAX_NAME) {
                $binding = $names->name_for($id);
                if ($binding->kind === \resolve_symbols\REFERENCE_TEMPLATE_PARAMETER) {
                    $base = $owner->owner_symbol_id; if ($base === 0) { $base = $owner->symbol_id; }
                    if ($binding->target_id < q_count($substitutions)) { $terms[$id] = $substitutions[$binding->target_id]; }
                    else { $terms[$id] = Type_Term::parameter($base,$binding->target_id); }
                } elseif ($binding->kind === \resolve_symbols\REFERENCE_PROJECT_CONSTANT) {
                    $terms[$id] = Type_Term::constant('project_constant:' . $binding->target_id);
                } elseif ($binding->kind === \resolve_symbols\REFERENCE_LOCAL_CONSTANT) {
                    $terms[$id] = Type_Term::constant('local_constant:' . $binding->target_id);
                } elseif ($binding->kind === \resolve_symbols\REFERENCE_PROVIDED_TYPE) {
                    $definition = $binding->provided_type;
                    if ($definition === null) { throw new \LogicException('Missing provided symbolic type'); }
                    $terms[$id] = Type_Term::named($definition);
                } elseif ($binding->kind === \resolve_symbols\REFERENCE_PROVIDED_RECORD) {
                    $record = $binding->provided_record;
                    if ($record === null) { throw new \LogicException('Missing provided symbolic record'); }
                    $terms[$id] = Type_Term::record($record);
                } else {
                    $declared = $this->declaration($binding->target_id); $terms[$id] = Type_Term::source($declared->symbol_id);
                }
            } else { $this->fail($owner,$id,'Unsupported symbolic template argument; constant evaluation is not implemented'); }
        }
        return $terms[$root];
    }
    public function receiver(\collect_symbols\Symbol_Record $method): Type_Term {
        $owner = $this->declaration($method->owner_symbol_id); $bindings = $this->bindings($owner);
        $arguments /** vector<Type_Term> */ = [];
        for ($position = 0; $position < $bindings->parameters_count(); $position++) { $arguments[] = Type_Term::parameter($owner->symbol_id,$position); }
        if (q_count($arguments) === 0) { return Type_Term::source($owner->symbol_id); }
        return Type_Term::application($owner->symbol_id,$arguments);
    }
    private function struct_owner(Type_Term $type, \collect_symbols\Symbol_Record $use_owner, int $use): \collect_symbols\Symbol_Record {
        if (($type->kind === \check_templates\TERM_PARAMETER) || ($type->symbol_id === 0)) { $this->fail($use_owner,$use,'Generic contract does not permit member access on this type'); }
        $owner = $this->declaration($type->symbol_id); $kind = $owner->kind();
        if (($kind !== \collect_symbols\SYMBOL_STRUCT) && ($kind !== \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) { $this->fail($use_owner,$use,'Unsupported symbolic member receiver'); }
        if (!$owner->is_source()) {
            if ($owner->provider()->kind() !== \collect_symbols\PROVIDER_FAMILY) { $this->fail($use_owner,$use,'Unsupported symbolic member receiver'); }
        }
        return $owner;
    }
    public function field(Type_Term $receiver, string $name, \collect_symbols\Symbol_Record $use_owner, int $use): Type_Term {
        $owner = $this->struct_owner($receiver,$use_owner,$use);
        if (!$owner->is_source()) { $this->fail($use_owner,$use,'Provider family does not expose structural fields'); }
        $tree = $owner->source_frontend()->tree;
        $cursor = \parse\Syntax_Access::struct_members($tree,(int)$owner->source_fact()->declaration_node_id,\parse\SYNTAX_FIELD_DECLARATION);
        $field = 0;
        while ($cursor->advance()) {
            $candidate = $cursor->current(); $parts = \parse\Syntax_Access::field_declaration_parts($tree,$candidate);
            $spelling = Terms::text($owner,(int)$parts->variable_id);
            if (string_byte_slice($spelling,1,string_byte_len($spelling)-1) === $name) { $field = $candidate; break; }
        }
        if ($field === 0) { $this->fail($use_owner,$use,'Unknown field in declared generic receiver: ' . $name); }
        $selected = \parse\Syntax_Access::field_declaration_parts($tree,$field);
        $substitutions /** vector<Type_Term> */ = [];
        for ($i = 0; $i < $receiver->argument_count(); $i++) { $substitutions[] = $receiver->argument_at($i); }
        $type = $this->annotation($owner,(int)$selected->type_syntax_id,$substitutions);
        if ((int)$selected->extent_id !== 0) { $type = Type_Term::array_type($type); }
        return $type;
    }
    public function method(Type_Term $receiver, string $name, \collect_symbols\Symbol_Record $use_owner, int $use): \collect_symbols\Symbol_Record {
        $owner = $this->struct_owner($receiver,$use_owner,$use);
        $id = $this->symbols->find_symbol($name,\collect_symbols\SYMBOL_FUNCTION,$owner->symbol_id,$owner->namespace_name);
        if ($id === 0) { $id = $this->symbols->find_symbol($name,\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,$owner->symbol_id,$owner->namespace_name); }
        if ($id === 0) { $this->fail($use_owner,$use,'Unknown method in declared generic receiver: ' . $name); }
        return $this->declaration($id);
    }
    private function is_family(Type_Term $type): bool {
        if ($type->kind !== \check_templates\TERM_APPLICATION) { return false; }
        $owner = $this->declaration($type->symbol_id);
        if ($owner->is_source()) { return false; }
        return $owner->provider()->kind() === \collect_symbols\PROVIDER_FAMILY;
    }
    public function forwarded_type(Type_Term $type, \collect_symbols\Symbol_Record $owner, int $id): void {
        if ($type->dependent) { if ($this->is_family($type)) { $this->fail($owner,$id,'Declared generic baseline is not established for this provider application'); } }
    }
    public function default_construction(Type_Term $type, \collect_symbols\Symbol_Record $owner, int $id): void {
        if ($this->is_family($type)) {
            $family = $this->declaration($type->symbol_id)->provider()->family(); $lifecycle = $family->definition->lifecycle_bindings();
            if (!isset($lifecycle['construct'])) { $this->fail($owner,$id,'Family has no declared empty constructor'); }
            $operation = $family->definition->find_operation($lifecycle['construct']);
            if ($operation === null) { $this->fail($owner,$id,'Family has no declared empty constructor'); }
            if ($operation->signature->parameter_count() !== 0) { $this->fail($owner,$id,'Family has no declared empty constructor'); }
            return;
        }
        if ($type->dependent) { $this->fail($owner,$id,'Generic contract does not permit default construction'); }
    }
    public function provider_value_use(Type_Term $type, \collect_symbols\Symbol_Record $owner, int $id): void {
        if ($type->dependent) { if ($this->is_family($type)) { $this->fail($owner,$id,'Whole provider value lifecycle is not implemented'); } }
    }
    public function family_argument(Type_Term $type, \collect_symbols\Symbol_Record $owner, int $id): void {
        if ($type->dependent) { if ($type->kind !== \check_templates\TERM_PARAMETER) { $this->fail($owner,$id,'Declared generic baseline is not established for this dependent type'); } }
        $definition = $type->named_definition;
        if ($definition !== null) {
            $missing = \type_model\Generic_Contracts::missing($definition,\type_model\GENERIC_COPYABLE_VALUE);
            $reason /** string */ = '';
            if (take_nullable($reason,$missing)) { $this->fail($owner,$id,'Default generic contract requires supported ' . $reason); }
        }
    }
    public function provider_type(\type_model\Type_Reference $reference, \type_model\Family_Declaration $family, Type_Term $receiver): Type_Term {
        $kind = $reference->kind;
        if (($kind !== \type_model\TYPE_REFERENCE_PARAMETER) && ($kind !== \type_model\TYPE_REFERENCE_FAMILY) && ($kind !== \type_model\TYPE_REFERENCE_PROVIDER)) {
            throw new \LogicException('Unsupported validated family signature reference');
        }
        if ($kind === \type_model\TYPE_REFERENCE_PARAMETER) { return $receiver->argument_at($reference->parameter_slot()); }
        if ($kind === \type_model\TYPE_REFERENCE_FAMILY) { return $receiver; }
        return $this->mapped_provider_type($reference,$family);
    }
    private function mapped_provider_type(\type_model\Type_Reference $reference, \type_model\Family_Declaration $family): Type_Term {
        $key = '[' . json_quote($reference->provider()) . ',' . json_quote($reference->id()) . ']';
        $name = $family->find_language_type($key);
        if ($name === null) { throw new \LogicException('Family source mapping was not validated'); }
        $definition = $this->catalog->find_type($name->name(),$name->namespace_name());
        if ($definition !== null) { return Type_Term::named($definition); }
        $record = $this->catalog->find_record($name->name(),$name->namespace_name());
        if ($record === null) { throw new \LogicException('Family source mapping was not validated'); }
        return Type_Term::record($record);
    }
}
