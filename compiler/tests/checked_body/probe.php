<?php
declare(strict_types=1);
namespace checked_body_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $mode = $cases->at($ci)->member('mode')->text();
            $f = \construction_fixture\Fixture::prepare('scalar', 0);
            $intdef = $f->catalog->find_type('int32',''); $bytedef = $f->catalog->find_type('uint8','');
            if (($intdef === null) || ($bytedef === null)) { throw new \LogicException('Missing fixture types'); }
            $integer_id = \resolve_types\Type_Cache::materialize($f->types, $intdef);
            $byte_id = \resolve_types\Type_Cache::materialize($f->types, $bytedef);
            $root = $integer_id;
            if (($mode === 'field') || ($mode === 'field_bounds')) { $root = $f->types->find_type('Point',''); }
            if (($mode === 'array') || ($mode === 'array_type') || ($mode === 'index_type')) {
                $def = new \type_model\Named_Definition('Array','test',\type_model\Representation::fixed_array($integer_id,4),$intdef->lifetime,null,'',false,false,true);
                $root = $f->types->declare_type('Array','test');
                $f->types->bind_definition($root,$def);
                $f->types->set_representation($root,$f->types->intern_array($integer_id,4));
            }
            if (($mode === 'element') || ($mode === 'element_type')) {
                for ($i = 0; $i < $f->symbols->size(); $i++) {
                    $owner = $f->symbols->record_at($i);
                    if ($owner->is_source()) { continue; }
                    if ($owner->provider()->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
                        $def = \resolve_types\Storage_Definitions::materialize($owner->provider()->storage_function()->family,$intdef,$f->types);
                        $root = $f->types->find_type($def->name,$def->namespace_name); break;
                    }
                }
            }
            $names = $f->reader->annotations->bindings($f->input->owner);
            $local_ids /** vector<int> */ = [$root,$byte_id];
            $locals = new \resolve_types\Local_Types($names,$local_ids);
            $passing /** vector<int> */ = [\type_model\PASS_VALUE,\type_model\PASS_VALUE];
            if ($mode === 'borrow') { $passing[0] = \type_model\PASS_BORROW_CONST; }
            $repr = $f->types->intern_signature($integer_id,$local_ids,$passing);
            $signature = new \resolve_types\Callable_Signature($f->input,0,$repr);
            $dependency = \check_bodies\Signature_Dependency::capture($f->types,$signature);
            $signatures /** hash<\check_bodies\Signature_Dependency,int> */ = [];
            $signatures[$f->input->callable_id] = $dependency;
            $types /** hash<\type_model\Type_Record,int> */ = [];
            for ($i = 1; $i < $f->types->type_count()+1; $i++) { $types[$i] = $f->types->type_by_id($i); }
            if ($mode === 'unresolved_type') { $types[$integer_id] = new \type_model\Type_Record('int32','',0,false,null); }
            $values /** vector<\check_bodies\Typed_Value> */ = [new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_INTEGER_LITERAL,'1'),new \check_bodies\Typed_Value(2,$integer_id,\check_bodies\VALUE_INTEGER_LITERAL,'2')];
            if ($mode === 'index_type') { $values[0] = new \check_bodies\Typed_Value(1,$root,\check_bodies\VALUE_RECORD_DEFAULT); }
            $operand = 1; if ($mode === 'conversion_cycle') { $operand = 3; }
            $conversion = new \check_bodies\Conversion_Value($operand,\check_bodies\CONVERSION_INTEGER_WIDEN);
            $values[] = new \check_bodies\Typed_Value(3,$integer_id,\check_bodies\VALUE_CONVERSION,'',0,null,$conversion);
            $args_types /** vector<int> */ = [$integer_id,$integer_id];
            if ($mode === 'operation_type') { $args_types[1] = $byte_id; }
            $left = 1; if ($mode === 'operation_cycle') { $left = 4; }
            $contract_result = $integer_id; if ($mode === 'operation_result') { $contract_result = $byte_id; }
            $operation = new \check_bodies\Operation_Value($left,2,new \type_model\Operation_Contract('addition',$args_types,$contract_result,new \type_model\Implementation_Binding(1,'compiler.integer','add_wrap')));
            $values[] = new \check_bodies\Typed_Value(4,$integer_id,\check_bodies\VALUE_OPERATION,'',0,null,null,$operation);
            $calls /** vector<\check_bodies\Typed_Call> */ = [new \check_bodies\Typed_Call(1,$f->input->callable_id,0,0,1)];
            $arguments /** vector<\check_bodies\Typed_Argument> */ = [new \check_bodies\Typed_Argument(1,$integer_id)];
            $statements /** vector<\check_bodies\Typed_Statement> */ = [new \check_bodies\Typed_Statement(1,\check_bodies\STATEMENT_RETURN,4,0,0,1)];
            $scopes /** vector<\check_bodies\Typed_Scope> */ = [new \check_bodies\Typed_Scope(0,1)];
            $blocks /** vector<\check_bodies\Typed_Block> */ = [new \check_bodies\Typed_Block(0,1,1,\check_bodies\FLOW_RETURN)];
            $body = new \check_bodies\Checked_Body($f->input,$names,false,$locals,$values,$calls,$statements,$scopes,$arguments,$blocks,$types,$signatures);
            $expected_error = false;
            if (($mode === 'conversion_cycle') || ($mode === 'operation_cycle') || ($mode === 'operation_type') || ($mode === 'operation_result') || ($mode === 'argument_bounds') || ($mode === 'field_bounds') || ($mode === 'array_type') || ($mode === 'element_type') || ($mode === 'index_type') || ($mode === 'unresolved_type')) { $expected_error = true; }
            if (string_byte_starts_with($mode,'missing_')) { $expected_error = true; }
            $failed = false; $valid = true;
            try {
                if (($mode === 'conversion') || ($mode === 'conversion_cycle')) { $valid = $body->conversion_for(3) === $conversion; }
                elseif (string_byte_starts_with($mode,'operation')) { $valid = $body->operation_for(4) === $operation; }
                elseif (($mode === 'argument') || ($mode === 'argument_bounds')) { $position = 1; if ($mode === 'argument_bounds') { $position = 2; } $valid = $body->argument_for(1,$position) === $arguments[0]; }
                elseif (($mode === 'field') || ($mode === 'field_bounds') || ($mode === 'array') || ($mode === 'array_type') || ($mode === 'element') || ($mode === 'element_type') || ($mode === 'index_type')) {
                    $kind = \check_bodies\PROJECTION_INDEX; $operand_id = 1; $projected = $integer_id;
                    if (string_byte_starts_with($mode,'field')) { $kind = \check_bodies\PROJECTION_FIELD; $operand_id = 0; }
                    if ($mode === 'field_bounds') { $operand_id = 1; }
                    if (string_byte_starts_with($mode,'element')) { $kind = \check_bodies\PROJECTION_ELEMENT; }
                    if (($mode === 'array_type') || ($mode === 'element_type')) { $projected = $byte_id; }
                    $path /** vector<\check_bodies\Place_Projection> */ = [new \check_bodies\Place_Projection($kind,$operand_id,$projected)];
                    $valid = $body->place_type(new \check_bodies\Place(1,$path)) === $integer_id;
                }
                elseif ($mode === 'missing_signature') { $body->signature_for(9999); }
                elseif ($mode === 'missing_type') { $body->definition_for(9999); }
                elseif ($mode === 'missing_value') { $body->value_for(0); }
                elseif ($mode === 'missing_scope') { $body->scope_for(0); }
                elseif ($mode === 'unresolved_type') { $body->definition_for($integer_id); }
                elseif ($mode === 'borrow') { $valid = $body->local_passing(1) === \type_model\PASS_BORROW_CONST; }
                else {
                    $values[] = new \check_bodies\Typed_Value(5,$byte_id,\check_bodies\VALUE_INTEGER_LITERAL,'5');
                    unset($types[$integer_id]); unset($signatures[$f->input->callable_id]);
                    $valid = ($body->value_count() === 4) && ($body->entry_parameter_count() === 2) && ($body->local_type_for(1) === $integer_id);
                    if ($body->definition_for($integer_id) !== $intdef) { $valid = false; }
                    if ($body->signature_for($body->callable_id) !== $dependency) { $valid = false; }
                    if (($dependency->parameter_count() !== 2) || ($dependency->parameter_type(1) !== $byte_id)) { $valid = false; }
                    if ($body->allocation_effect_for($body->callable_id) !== null) { $valid = false; }
                    if ($body->statement_at(0) !== $statements[0]) { $valid = false; }
                    if ($body->block_at(0) !== $blocks[0]) { $valid = false; }
                }
            } catch (\LogicException $error) { $failed = true; }
            catch (\OutOfBoundsException $error) { $failed = true; }
            if ($failed !== $expected_error) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
