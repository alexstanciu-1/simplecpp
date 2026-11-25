<?php
declare(strict_types=1);
namespace provider_declaration_test;
final class Probe {
    public static function run(string $text): void {
        $empty_parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $result = new \type_model\Semantic_Result(\type_model\Type_Reference::provided('p','void'),\type_model\RESULT_NONE);
        $signature = new \type_model\Semantic_Signature($empty_parameters,$result);
        $abi_parameters /** vector<\type_model\Runtime_Abi_Position> */ = [];
        $abi = new \type_model\Runtime_Callable_Abi('call_link','ccc',null,$abi_parameters);
        $callable = new \type_model\Runtime_Callable('p','call','call','ns',$signature,$abi);
        $life_operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$life_operations);
        $descriptor = new \type_model\Named_Definition('descriptor','',\type_model\Representation::opaque(16,8),$life,null,'',false,false,true);
        $counter = new \type_model\Named_Definition('counter','',\type_model\Representation::integer(64),$life,false,'',false,false,true);
        $void_type = new \type_model\Named_Definition('void','',\type_model\Representation::void_type(),null,null,'',false,false,false);
        $primitives /** hash<\type_model\Storage_Primitive> */ = []; $operations /** hash<string> */ = [];
        $operations['allocate'] = 'allocate';
        $storage = new \type_model\Storage_Family('p','storage',$descriptor,$counter,$void_type,$primitives,$operations,'Storage','ns');
        $function = new \type_model\Storage_Function($storage,0,'allocate','ns','p','alloc');
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE)];
        $arguments /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::parameter('["p","family"]',0)];
        $self = \type_model\Type_Reference::family('["p","family"]',$arguments);
        $method_parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $method_signature = new \type_model\Semantic_Signature($method_parameters,$result);
        $requirements /** vector<\type_model\Capability_Requirement> */ = []; $effects /** vector<\type_model\Element_Effect> */ = [];
        $operation = new \type_model\Family_Operation('inspect',$method_signature,$requirements,$effects,0,\type_model\Type_Reference::named('inspect',''));
        $family_operations /** hash<\type_model\Family_Operation> */ = []; $family_operations['inspect'] = $operation;
        $lifecycle /** hash<string> */ = []; $mappings /** hash<\type_model\Type_Reference> */ = [];
        $family_definition = new \type_model\Family_Definition('p','family',$parameters,$family_operations,$lifecycle,\type_model\Type_Reference::named('Family','ns'));
        $family = new \type_model\Family_Declaration($family_definition,$mappings); $method = new \type_model\Family_Method($family,$operation);
        $values /** vector<\collect_symbols\Provider_Declaration> */ = [
            new \collect_symbols\Provider_Declaration($callable,null,null,null,null),
            new \collect_symbols\Provider_Declaration(null,$storage,null,null,null),
            new \collect_symbols\Provider_Declaration(null,null,$function,null,null),
            new \collect_symbols\Provider_Declaration(null,null,null,$family,null),
            new \collect_symbols\Provider_Declaration(null,null,null,null,$method)];
        $names /** vector<string> */ = ['call','Storage','allocate','Family','inspect']; $ids /** vector<string> */ = ['call','storage','alloc','family','inspect'];
        $callable_copy = new \type_model\Runtime_Callable('p','call','call','ns',$signature,$abi);
        $storage_copy = new \type_model\Storage_Family('p','storage',$descriptor,$counter,$void_type,$primitives,$operations,'Storage','ns');
        $family_copy = new \type_model\Family_Declaration($family_definition,$mappings);
        $copies /** vector<\collect_symbols\Provider_Declaration> */ = [
            new \collect_symbols\Provider_Declaration($callable_copy,null,null,null,null),
            new \collect_symbols\Provider_Declaration(null,$storage_copy,null,null,null),
            new \collect_symbols\Provider_Declaration(null,null,new \type_model\Storage_Function($storage,0,'allocate','ns','p','alloc'),null,null),
            new \collect_symbols\Provider_Declaration(null,null,null,$family_copy,null),
            new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$operation))];
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $row = $cases->at($index); $mode = $row->member('mode')->text(); $kind = $row->member('kind')->integer(); $access = $row->member('access')->integer(); $ok = true;
            if ($mode === 'access') {
                $value = $values[$kind]; $accepted = true;
                try {
                    if ($access === 0) { $item = $value->callable(); if ($item !== $callable) { $ok = false; } }
                    elseif ($access === 1) { $item_storage = $value->storage_family(); if ($item_storage !== $storage) { $ok = false; } }
                    elseif ($access === 2) { $item_function = $value->storage_function(); if ($item_function !== $function) { $ok = false; } }
                    elseif ($access === 3) { $item_family = $value->family(); if ($item_family !== $family) { $ok = false; } }
                    else { $item_method = $value->method(); if ($item_method !== $method) { $ok = false; } }
                } catch (\LogicException $error) { $accepted = false; }
                if ($accepted !== ($kind === $access)) { $ok = false; }
                if (($value->kind() !== $kind+1) || ($value->provider() !== 'p') || ($value->namespace_name() !== 'ns') || ($value->name() !== $names[$kind]) || ($value->id() !== $ids[$kind])) { $ok = false; }
                if ($value->receiver_const() !== ($kind === 4)) { $ok = false; }
            } elseif ($mode === 'identity') {
                if ($values[$kind]->same($values[$access]) !== ($kind === $access)) { $ok = false; }
                if (\collect_symbols\Provider_Declaration::retain($values[$kind],$values[$access]) !== $values[$kind]) { $ok = false; }
            } elseif ($mode === 'clone') {
                $reusable = ($kind === 2) || ($kind === 4);
                if ($copies[$kind]->same($values[$kind]) !== $reusable) { $ok = false; }
                $expected = $copies[$kind]; if ($reusable) { $expected = $values[$kind]; }
                if (\collect_symbols\Provider_Declaration::retain($copies[$kind],$values[$kind]) !== $expected) { $ok = false; }
            } elseif ($mode === 'receiver') {
                $mutable_parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_MUTABLE)];
                $mutable_signature = new \type_model\Semantic_Signature($mutable_parameters,$result);
                $receiver_operation = new \type_model\Family_Operation('inspect',$mutable_signature,$requirements,$effects,0,\type_model\Type_Reference::named('inspect',''));
                if ($kind === 1) { $receiver_operation = new \type_model\Family_Operation('inspect',$method_signature,$requirements,$effects,null,\type_model\Type_Reference::named('inspect','')); }
                if ($kind === 2) { $receiver_operation = new \type_model\Family_Operation('inspect',$method_signature,$requirements,$effects,1,\type_model\Type_Reference::named('inspect','')); }
                $receiver_value = new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$receiver_operation));
                $rejected = false; $is_const = false;
                try { $is_const = $receiver_value->receiver_const(); }
                catch (\LogicException $error) { $rejected = true; }
                catch (\OutOfBoundsException $error) { $rejected = true; }
                $ok = ($rejected === ($kind !== 0)) && !$is_const;
            } elseif ($mode === 'invalid') {
                $rejected = false;
                try {
                    if ($kind === 0) { $bad = new \collect_symbols\Provider_Declaration(null,null,null,null,null); }
                    elseif ($kind === 1) { $bad = new \collect_symbols\Provider_Declaration($callable,$storage,null,null,null); }
                    else { $bad = new \collect_symbols\Provider_Declaration(null,null,$function,$family,$method); }
                } catch (\InvalidArgumentException $error) { $rejected = true; }
                $ok = $rejected;
            } else {
                $changed = $copies[2];
                if ($kind === 0) { $changed = new \collect_symbols\Provider_Declaration(null,null,new \type_model\Storage_Function($storage_copy,0,'allocate','ns','p','alloc'),null,null); }
                elseif ($kind === 1) { $changed = new \collect_symbols\Provider_Declaration(null,null,new \type_model\Storage_Function($storage,1,'allocate','ns','p','alloc'),null,null); }
                elseif ($kind === 2) { $changed = new \collect_symbols\Provider_Declaration(null,null,new \type_model\Storage_Function($storage,0,'renamed','ns','p','alloc'),null,null); }
                elseif ($kind === 3) { $changed = new \collect_symbols\Provider_Declaration(null,null,new \type_model\Storage_Function($storage,0,'allocate','other','p','alloc'),null,null); }
                elseif ($kind === 4) { $changed = new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family_copy,$operation)); }
                else {
                    $new_operation = new \type_model\Family_Operation('inspect',$method_signature,$requirements,$effects,0,\type_model\Type_Reference::named('inspect',''));
                    $changed = new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$new_operation));
                }
                $old = $values[2]; if ($kind > 3) { $old = $values[4]; }
                if ($changed->same($old) || (\collect_symbols\Provider_Declaration::retain($changed,$old) !== $changed)) { $ok = false; }
            }
            echo $ok ? "true\n" : "false\n";
        }
    }
}
