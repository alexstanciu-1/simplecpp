<?php
declare(strict_types=1);
namespace body_context_test;
final class Probe {
    public static function snapshot(\construction_fixture\Fixture $f, string $mode): \resolve_types\Type_Resolution {
        for ($i = 0; $i < $f->catalog->size(); $i++) { \resolve_types\Type_Cache::materialize($f->types, $f->catalog->definition_at($i)); }
        $empty /** vector<\resolve_types\Callable_Signature> */ = [];
        $previous = new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context), $empty);
        $tasks = \resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$previous,$f->prepared,false);
        $requests /** vector<\resolve_types\Signature_Request> */ = [];
        foreach ($tasks as $task) { $requests[] = \resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared); }
        $set = (new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$previous,$tasks,$f->entry,$f->prepared))->join($requests);
        $rows /** vector<\resolve_types\Callable_Signature> */ = [];
        for ($i = 0; $i < $set->size(); $i++) {
            $row = $set->at($i);
            if ($mode === 'missing_signature') { if ($row->callable_id === $f->input->callable_id) { continue; } }
            $rows[] = $row;
        }
        $locals /** vector<\resolve_types\Local_Types> */ = [];
        $names = $f->reader->annotations->bindings($f->input->owner);
        if (($mode !== 'missing_local_result') && ($mode !== 'missing_signature')) {
            if ($names->locals_count() > 0) {
                $signature = $set->for_callable($f->input->callable_id);
                if ($signature === null) { throw new \LogicException('Fixture lost signature'); }
                $shape = $f->types->representation_by_id($signature->representation_id);
                $ids /** vector<int> */ = [];
                for ($i = 0; $i < $shape->member_count(); $i++) { $ids[] = $f->types->member_at($shape->member_first()+$i)->type_id; }
                $locals[] = new \resolve_types\Local_Types($names,$ids,$f->input->instance);
            }
        }
        $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [$names];
        $instances = $f->instances->snapshot($bindings);
        if ($mode === 'missing_template_permission') {
            $checks /** vector<\check_templates\Definition_Result> */ = [];
            $instances = new \instantiate\Instance_Set($instances->export_state(),new \check_templates\Template_Set($checks,0));
        }
        $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        return new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,new \resolve_types\Signature_Set($f->types,$rows),$locals,$f->reader->annotations->names,$instances,$families);
    }
    public static function array_type(\type_model\Type_Store $types, int $element, string $name): int {
        $definition = $types->definition_for_type($element);
        $id = $types->declare_type($name,'proof');
        $array = new \type_model\Named_Definition($name,'proof',\type_model\Representation::fixed_array($element,2),$definition->lifetime,null,'',false,false,true);
        $types->bind_definition($id,$array); $types->set_representation($id,$types->intern_array($element,2));
        return $id;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $mode = $cases->at($ci)->member('mode')->text();
            $fixture_mode = 'scalar';
            if (($mode === 'entry') || ($mode === 'template') || ($mode === 'method')) { $fixture_mode = $mode; }
            if (($mode === 'stale_instance') || ($mode === 'missing_template_permission')) { $fixture_mode = 'template'; }
            $f = \construction_fixture\Fixture::prepare($fixture_mode,0);
            $types = Probe::snapshot($f,$mode);
            $names = $f->reader->annotations->bindings($f->input->owner); $input = $f->input;
            if (($mode === 'foreign_names') || ($mode === 'foreign_owner')) {
                $other = \construction_fixture\Fixture::prepare('scalar',0);
                if ($mode === 'foreign_names') { $names = $other->reader->annotations->bindings($other->input->owner); }
                else { $input = $other->input; }
            }
            if ($mode === 'stale_instance') {
                $original = $f->input->instance;
                if ($original === null) { throw new \LogicException('Missing template fixture'); }
                $args /** vector<\instantiate\Template_Argument> */ = [];
                for ($i = 0; $i < $original->argument_count(); $i++) { $args[] = $original->argument_at($i); }
                $input = new \resolve_types\Callable_Input($f->input->owner,new \instantiate\Instance_Context($original->definition,$original->instance_id,$args));
            }
            $expected = '';
            if (($mode === 'foreign_names') || ($mode === 'foreign_owner')) { $expected = 'Body task requires current callable name bindings'; }
            if (($mode === 'missing_signature') || ($mode === 'stale_instance')) { $expected = 'Body task requires its current resolved callable contract'; }
            if ($mode === 'missing_local_result') { $expected = 'Body task requires current resolved local types'; }
            if ($mode === 'missing_template_permission') { $expected = 'Source template requires its current definition permission result'; }
            if ($mode === 'unknown_signature') { $expected = 'Missing resolved function signature'; }
            if ($mode === 'invalid_local') { $expected = 'Missing resolved local type'; }
            if ($mode === 'pending_type') { $expected = 'Body dependency requires a resolved type'; }
            if ($mode === 'unknown_type') { $expected = 'Unknown type ID'; }
            $valid = true; $error_text = '';
            try {
                $context = new \check_bodies\Body_Context($input,$names,$types);
                if ($context->integer_literal_type !== $types->integer_literal_type()) { $valid = false; }
                if ((q_count($context->type_dependencies()) !== 0) || (q_count($context->signature_dependencies()) !== 0)) { $valid = false; }
                if ($mode === 'unknown_signature') { $context->signature(99999); }
                elseif ($mode === 'invalid_local') { $context->local_type(0); }
                elseif ($mode === 'unknown_type') { $context->retain_type(99999); }
                elseif ($mode === 'pending_type') { $context->retain_type($f->types->reference_type('Pending','proof')); }
                elseif (($mode === 'array_closure') || ($mode === 'deep_closure') || ($mode === 'cyclic_closure') || ($mode === 'storage_closure') || ($mode === 'structure_boundary')) {
                    $element = $f->types->find_type('int32',''); $root = $element; $expected_count = 1;
                    if (($mode === 'array_closure') || ($mode === 'deep_closure')) {
                        $depth = 3; if ($mode === 'deep_closure') { $depth = 4096; }
                        for ($i = 0; $i < $depth; $i++) { $root = Probe::array_type($f->types,$root,'Array_'.$i); }
                        $expected_count = $depth + 1;
                    }
                    if ($mode === 'cyclic_closure') {
                        $root = $f->types->declare_type('Cycle','proof');
                        $definition = $f->types->definition_for_type($element);
                        $array = new \type_model\Named_Definition('Cycle','proof',\type_model\Representation::fixed_array($root,2),$definition->lifetime,null,'',false,false,true);
                        $f->types->bind_definition($root,$array);
                    }
                    if ($mode === 'storage_closure') {
                        for ($i = 0; $i < $f->symbols->size(); $i++) {
                            $owner = $f->symbols->record_at($i); if ($owner->is_source()) { continue; }
                            if ($owner->provider()->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
                                $definition = \resolve_types\Storage_Definitions::materialize($owner->provider()->storage_function()->family,$f->types->definition_for_type($element),$f->types);
                                $root = $f->types->find_type($definition->name,$definition->namespace_name); break;
                            }
                        }
                        $expected_count = 2;
                    }
                    if ($mode === 'structure_boundary') { $root = $f->types->find_type('Point',''); }
                    $before_types = $f->types->type_count(); $before_shapes = $f->types->representation_count();
                    $context->retain_type($root); $context->retain_type($root);
                    $rows = $context->type_dependencies();
                    $valid = (q_count($rows) === $expected_count) && ($rows[$root] === $f->types->type_by_id($root));
                    if (($mode === 'array_closure') || ($mode === 'deep_closure') || ($mode === 'storage_closure')) { if ($rows[$element] !== $f->types->type_by_id($element)) { $valid = false; } }
                    if (($before_types !== $f->types->type_count()) || ($before_shapes !== $f->types->representation_count())) { $valid = false; }
                } else {
                    $id = $input->callable_id;
                    if ($mode === 'provider_dependency') { $id = $f->symbols->find_symbol('external',\collect_symbols\SYMBOL_FUNCTION,0,''); }
                    $before_types = $f->types->type_count(); $before_shapes = $f->types->representation_count();
                    $signature = $context->signature($id);
                    if ($context->signature($id) !== $signature) { $valid = false; }
                    $source = $types->for_callable($id);
                    if ($source === null) { throw new \LogicException('Fixture missing signature'); }
                    if (($signature->external !== $source->external) || ($signature->storage !== $source->storage)) { $valid = false; }
                    $dependencies = $context->type_dependencies();
                    $result_type = $signature->representation->signature_return();
                    if (!isset($dependencies[$result_type])) { $valid = false; }
                    for ($i = 0; $i < $signature->parameter_count(); $i++) {
                        $parameter = $signature->parameter_type($i); if (!isset($dependencies[$parameter])) { $valid = false; }
                    }
                    if ($mode === 'local') { if ($context->local_type(1) !== $signature->parameter_type(0)) { $valid = false; } }
                    if ($mode === 'entry') { if ($context->local_types() !== null) { $valid = false; } }
                    if ($mode === 'snapshot') {
                        $retained = $context->signature_dependencies(); unset($retained[$id]);
                        $returned = $context->type_dependencies(); $key = $signature->representation->signature_return(); unset($returned[$key]);
                        if ((q_count($context->signature_dependencies()) !== 1) || (q_count($context->type_dependencies()) !== q_count($dependencies))) { $valid = false; }
                    }
                    if (($before_types !== $f->types->type_count()) || ($before_shapes !== $f->types->representation_count())) { $valid = false; }
                }
            } catch (\LogicException $error) { $error_text = $error->getMessage(); }
            catch (\OutOfBoundsException $error) { $error_text = $error->getMessage(); }
            if ($error_text !== $expected) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
