<?php
declare(strict_types=1);
namespace symbolic_interpretation_test;
final class Fixtures {
    public static function providers(string $namespace_name): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $empty_parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $result = new \type_model\Semantic_Result(\type_model\Type_Reference::provided('p','void'),\type_model\RESULT_NONE);
        $signature = new \type_model\Semantic_Signature($empty_parameters,$result);
        $abi_parameters /** vector<\type_model\Runtime_Abi_Position> */ = [];
        $abi = new \type_model\Runtime_Callable_Abi('call_link','ccc',null,$abi_parameters);
        $callable = new \type_model\Runtime_Callable('p','call','call',$namespace_name,$signature,$abi);
        $life_operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$life_operations);
        $descriptor = new \type_model\Named_Definition('descriptor','',\type_model\Representation::opaque(16,8),$life,null,'',false,false,true);
        $counter = new \type_model\Named_Definition('counter','',\type_model\Representation::integer(64),$life,false,'',false,false,true);
        $void_type = new \type_model\Named_Definition('void','',\type_model\Representation::void_type(),null,null,'',false,false,false);
        $primitives /** hash<\type_model\Storage_Primitive> */ = []; $operations /** hash<string> */ = [];
        $operations['allocate'] = 'allocate';
        $storage = new \type_model\Storage_Family('p','storage',$descriptor,$counter,$void_type,$primitives,$operations,'Storage',$namespace_name);
        $function = new \type_model\Storage_Function($storage,0,'allocate',$namespace_name,'p','alloc');
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE)];
        $arguments /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::parameter('["p","family"]',0)];
        $self = \type_model\Type_Reference::family('["p","family"]',$arguments);
        $method_parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $method_signature = new \type_model\Semantic_Signature($method_parameters,$result);
        $requirements /** vector<\type_model\Capability_Requirement> */ = []; $effects /** vector<\type_model\Element_Effect> */ = [];
        $operation = new \type_model\Family_Operation('inspect',$method_signature,$requirements,$effects,0,\type_model\Type_Reference::named('inspect',''));
        $family_operations /** hash<\type_model\Family_Operation> */ = []; $family_operations['inspect'] = $operation;
        $lifecycle /** hash<string> */ = []; $mappings /** hash<\type_model\Type_Reference> */ = [];
        $family_definition = new \type_model\Family_Definition('p','family',$parameters,$family_operations,$lifecycle,\type_model\Type_Reference::named('Family',$namespace_name));
        $family = new \type_model\Family_Declaration($family_definition,$mappings); $method = new \type_model\Family_Method($family,$operation);
        $values /** vector<\collect_symbols\Provider_Declaration> */ = [
            new \collect_symbols\Provider_Declaration($callable,null,null,null,null),
            new \collect_symbols\Provider_Declaration(null,$storage,null,null,null),
            new \collect_symbols\Provider_Declaration(null,null,$function,null,null),
            new \collect_symbols\Provider_Declaration(null,null,null,$family,null),
            new \collect_symbols\Provider_Declaration(null,null,null,null,$method)];
        return $values;
    }
}
final class Probe {
    private static function collect(string $text, array $providers /** vector<\collect_symbols\Provider_Declaration> */): \collect_symbols\Symbol_Store {
        $source = new \read_sources\Source_Buffer(); $source->path = '/symbolic.phs'; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0;
        $collected = \collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,$providers);
        if (!$collected->valid) { throw new \LogicException($collected->error_reason); }
        return $collected->current;
    }
    private static function constructible(array $providers /** vector<\collect_symbols\Provider_Declaration> */): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $old = $providers[3]->family(); $definition = $old->definition;
        $parameters /** vector<\type_model\Family_Parameter> */ = [];
        for ($i = 0; $i < $definition->parameter_count(); $i++) { $parameters[] = $definition->parameter_at($i); }
        $operations /** hash<\type_model\Family_Operation> */ = [];
        for ($i = 0; $i < $definition->operation_count(); $i++) { $operation = $definition->operation_at($i); $operations[$operation->id] = $operation; }
        $empty_parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $requirements /** vector<\type_model\Capability_Requirement> */ = []; $effects /** vector<\type_model\Element_Effect> */ = [];
        $signature = new \type_model\Semantic_Signature($empty_parameters,$providers[0]->callable()->signature->result);
        $operations['construct'] = new \type_model\Family_Operation('construct',$signature,$requirements,$effects,null,null);
        $lifecycle /** hash<string> */ = []; $lifecycle['construct'] = 'construct';
        $updated = new \type_model\Family_Definition($definition->provider,$definition->id,$parameters,$operations,$lifecycle,$definition->language_type);
        $mappings /** hash<\type_model\Type_Reference> */ = []; $family = new \type_model\Family_Declaration($updated,$mappings);
        $providers[3] = new \collect_symbols\Provider_Declaration(null,null,null,$family,null);
        $providers[4] = new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$providers[4]->method()->operation));
        return $providers;
    }
    private static function return_type(\collect_symbols\Symbol_Record $owner): int {
        $tree = $owner->source_frontend()->tree;
        return (int)\parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,(int)$owner->source_fact()->declaration_node_id))->return_type_id;
    }
    public static function run(string $text): void {
        $cases = json_read($text); $base = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i = 0; $i < $base->size(); $i++) { $definitions[] = $base->definition_at($i); }
        $fields /** vector<\type_model\Field_Declaration> */ = [];
        $record = new \type_model\Record_Declaration('Record','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
        $records /** vector<\type_model\Record_Declaration> */ = [$record];
        $catalog = new \type_model\Type_Catalog($base->provider,$base->content_key,$base->representation_scope,$definitions,$base->integer_literal_type,$base->entry_return_type,$base->boolean_type,$records);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $mode = $cases->at($case_index)->text(); $ok = true; $providers = Fixtures::providers('');
            if ($mode === 'provider_default_success') { $providers = Probe::constructible($providers); }
            $annotation = 'T';
            if ($mode === 'nested') { $annotation = 'Box<Box<T>>'; }
            if ($mode === 'deep') { for ($i = 0; $i < 100; $i++) { $annotation = 'Box<' . $annotation . '>'; } }
            if ($mode === 'provided_record') { $annotation = 'Record'; }
            if ($mode === 'constant_literal') { $annotation = 'Sized<int,3>'; }
            if ($mode === 'project_constant') { $annotation = 'Sized<int,N>'; }
            if ($mode === 'provider_argument') { $annotation = 'Family<T>'; }
            if ($mode === 'provider_nested_reject') { $annotation = 'Family<Family<T>>'; }
            if ($mode === 'storage_dependent_reject') { $annotation = 'Storage<T>'; }
            $source = 'const N: int = 3; template<typename T> struct Box { public T $value; public T $items[3]; public function get(): T { return $this->value; } } template<typename T, int N> struct Sized { public T $items[N]; } template<typename T> function generic($value ' . $annotation . '): ' . $annotation . ' { return $value; }';
            $symbols = Probe::collect($source,$providers);
            $names = \resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false)->result();
            $id = $symbols->find_symbol('generic',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''); $owner = $symbols->symbol_by_id($id);
            $box = $symbols->find_symbol('Box',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,'');
            $family = $symbols->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,'');
            $use = Probe::return_type($owner); $terms = new \check_templates\Terms($symbols,$names,$catalog);
            $empty /** vector<\check_templates\Type_Term> */ = []; $substitutions = $empty;
            $formal = \check_templates\Type_Term::parameter($id,0); $actual /** vector<\check_templates\Type_Term> */ = [$formal];
            $receiver = \check_templates\Type_Term::application($box,$actual);
            $provider_receiver = \check_templates\Type_Term::application($family,$actual);
            $expected_error = '';
            if ($mode === 'unknown_method') { $expected_error = 'Unknown method in declared generic receiver: absent'; }
            if ($mode === 'family_argument_reject') { $expected_error = 'Default generic contract requires supported value lifetime'; }
            if ($mode === 'dependent_argument_reject') { $expected_error = 'Declared generic baseline is not established for this dependent type'; }
            if ($mode === 'unknown_field') { $expected_error = 'Unknown field in declared generic receiver: absent'; }
            if ($mode === 'bare_parameter_member') { $expected_error = 'Generic contract does not permit member access on this type'; }
            if ($mode === 'provider_field') { $expected_error = 'Provider family does not expose structural fields'; }
            if ($mode === 'provider_nested_reject') { $expected_error = 'Declared generic baseline is not established for this provider application'; }
            if ($mode === 'storage_dependent_reject') { $expected_error = 'Generic contract does not guarantee the provider storage family element requirements'; }
            if ($mode === 'default_parameter_reject') { $expected_error = 'Generic contract does not permit default construction'; }
            if ($mode === 'provider_default_reject') { $expected_error = 'Family has no declared empty constructor'; }
            if ($mode === 'provider_value_reject') { $expected_error = 'Whole provider value lifecycle is not implemented'; }
            if ($mode === 'diagnostic') { $expected_error = 'Explicit symbolic rejection'; }
            $rejected = false;
            try {
                if ($mode === 'substitution') { $substitutions[] = \check_templates\Type_Term::named($base->entry_return_type); }
                if (($mode === 'field') || ($mode === 'array_field') || ($mode === 'unknown_field') || ($mode === 'bare_parameter_member') || ($mode === 'provider_field')) {
                    $field_name = 'value'; if ($mode === 'array_field') { $field_name = 'items'; }
                    if ($mode === 'unknown_field') { $field_name = 'absent'; }
                    if ($mode === 'bare_parameter_member') { $receiver = $formal; }
                    if ($mode === 'provider_field') { $receiver = $provider_receiver; }
                    $field = $terms->field($receiver,$field_name,$owner,$use);
                    if ($mode === 'array_field') { $ok = ($field->kind === \check_templates\TERM_ARRAY) && \check_templates\Type_Term::same($field->argument_at(0),$formal); }
                    else { $ok = \check_templates\Type_Term::same($field,$formal); }
                } elseif (($mode === 'method') || ($mode === 'receiver') || ($mode === 'provider_method') || ($mode === 'unknown_method')) {
                    $name = 'get'; if ($mode === 'provider_method') { $receiver = $provider_receiver; $name = 'inspect'; }
                    if ($mode === 'unknown_method') { $name = 'absent'; }
                    $method = $terms->method($receiver,$name,$owner,$use);
                    $ok = ($method->owner_symbol_id === $receiver->symbol_id) && ($method->name === $name);
                    if ($mode === 'receiver') {
                        $method_receiver = $terms->receiver($method);
                        $ok = $ok && ($method_receiver->symbol_id === $box) && ($method_receiver->argument_at(0)->symbol_id === $box) && ($method_receiver->argument_at(0)->parameter_slot === 0);
                    }
                } elseif ($mode === 'ordinary_default') { $terms->default_construction(\check_templates\Type_Term::named($base->entry_return_type),$owner,$use); }
                elseif ($mode === 'provider_default_success') { $terms->default_construction($provider_receiver,$owner,$use); }
                elseif ($mode === 'family_argument_reject') {
                    $void_type = $catalog->find_type('void',''); if ($void_type === null) { throw new \LogicException('Missing void'); }
                    $terms->family_argument(\check_templates\Type_Term::named($void_type),$owner,$use);
                } elseif ($mode === 'dependent_argument_reject') { $terms->family_argument(\check_templates\Type_Term::array_type($formal),$owner,$use); }
                elseif ($mode === 'default_parameter_reject') { $terms->default_construction($formal,$owner,$use); }
                elseif ($mode === 'provider_default_reject') { $terms->default_construction($provider_receiver,$owner,$use); }
                elseif ($mode === 'provider_value_reject') { $terms->provider_value_use($provider_receiver,$owner,$use); }
                elseif ($mode === 'diagnostic') { $terms->fail($owner,$use,$expected_error); }
                elseif (($mode === 'provider_mapping') || ($mode === 'provider_mapping_named') || ($mode === 'provider_mapping_self') || ($mode === 'missing_mapping')) {
                    $mapping /** hash<\type_model\Type_Reference> */ = [];
                    $mapping['["p","record"]'] = \type_model\Type_Reference::named('Record','');
                    $mapping['["p","integer"]'] = \type_model\Type_Reference::named($base->entry_return_type->name,$base->entry_return_type->namespace_name);
                    $mapped_family = new \type_model\Family_Declaration($providers[3]->family()->definition,$mapping);
                    $parameter_type = $terms->provider_type(\type_model\Type_Reference::parameter('["p","family"]',0),$mapped_family,$provider_receiver);
                    $record_type = $terms->provider_type(\type_model\Type_Reference::provided('p','record'),$mapped_family,$provider_receiver);
                    $ok = ($parameter_type === $formal) && ($record_type->record_definition === $record);
                    if ($mode === 'provider_mapping_named') {
                        $named = $terms->provider_type(\type_model\Type_Reference::provided('p','integer'),$mapped_family,$provider_receiver);
                        $ok = $ok && ($named->named_definition === $base->entry_return_type);
                    }
                    if ($mode === 'provider_mapping_self') {
                        $refs /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::parameter('["p","family"]',0)];
                        $same_receiver = $terms->provider_type(\type_model\Type_Reference::family('["p","family"]',$refs),$mapped_family,$provider_receiver);
                        $ok = $ok && ($same_receiver === $provider_receiver);
                    }
                    if ($mode === 'missing_mapping') {
                        $mapping_rejected = false;
                        try { $bad_mapping = $terms->provider_type(\type_model\Type_Reference::provided('p','missing'),$mapped_family,$provider_receiver); }
                        catch (\LogicException $error) { $mapping_rejected = true; }
                        $ok = $ok && $mapping_rejected;
                    }
                } else {
                    $type = $terms->annotation($owner,$use,$substitutions);
                    if ($mode === 'parameter') { $ok = \check_templates\Type_Term::same($type,$formal); }
                    elseif ($mode === 'substitution') { $ok = $type === $substitutions[0]; }
                    elseif (($mode === 'nested') || ($mode === 'deep')) {
                        $levels = 2; if ($mode === 'deep') { $levels = 100; }
                        $cursor = $type;
                        for ($i = 0; $i < $levels; $i++) { if (($cursor->kind !== \check_templates\TERM_APPLICATION) || ($cursor->symbol_id !== $box)) { $ok = false; } $cursor = $cursor->argument_at(0); }
                        $ok = $ok && \check_templates\Type_Term::same($cursor,$formal);
                    } elseif ($mode === 'provided_record') { $ok = $type->record_definition === $record; }
                    elseif ($mode === 'constant_literal') { $ok = $type->argument_at(1)->text_key === 'literal:3'; }
                    elseif ($mode === 'project_constant') { $constant = $symbols->find_symbol('N',\collect_symbols\SYMBOL_CONSTANT,0,''); $ok = $type->argument_at(1)->text_key === 'project_constant:' . $constant; }
                    elseif ($mode === 'provider_argument') { $ok = ($type->symbol_id === $family) && $type->dependent; }
                    elseif ($mode === 'dependencies') {
                        $seen = $terms->declaration($box); $seen_again = $terms->declaration($box); $binding_again = $terms->bindings($owner);
                        $declarations = $terms->declarations(); $resolutions = $terms->resolutions();
                        $ok = ($seen === $seen_again) && (q_count($declarations) === 1) && ($declarations[0] === $symbols->symbol_by_id($box)) && (q_count($resolutions) === 1) && ($resolutions[0] === $binding_again);
                    }
                }
            } catch (\RuntimeException $error) {
                $rejected = true; if ($error->getMessage() !== $expected_error) { $ok = false; }
            }
            if ($rejected !== ($expected_error !== '')) { $ok = false; }
            $diagnostic = $terms->diagnostic();
            if ($expected_error !== '') {
                if ($diagnostic === null) { $ok = false; }
                else { $node = $owner->source_frontend()->tree->row($use); $ok = $ok && ($diagnostic->path === '/symbolic.phs') && ($diagnostic->start === (int)$node->start) && ($diagnostic->length === (int)$node->length) && ($diagnostic->reason === $expected_error); }
            } elseif ($diagnostic !== null) { $ok = false; }
            echo $ok ? "true\n" : "false\n";
        }
    }
}
