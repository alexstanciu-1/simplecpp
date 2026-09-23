<?php
declare(strict_types=1);
namespace expression_order_test;
final class Probe {
    public static function run(string $text): void {
        $f = \construction_fixture\Fixture::prepare('scalar',0);
        $intdef = $f->catalog->find_type('int32',''); $bytedef = $f->catalog->find_type('uint8',''); $voiddef = $f->catalog->find_type('void','');
        if (($intdef === null) || ($bytedef === null) || ($voiddef === null)) { throw new \LogicException('Missing fixture types'); }
        $integer_id = \resolve_types\Type_Cache::materialize($f->types,$intdef);
        $byte_id = \resolve_types\Type_Cache::materialize($f->types,$bytedef);
        $void_id = \resolve_types\Type_Cache::materialize($f->types,$voiddef);
        $names = $f->reader->annotations->bindings($f->input->owner);
        $locals_ids /** vector<int> */ = [$integer_id,$byte_id];
        $locals = new \resolve_types\Local_Types($names,$locals_ids);
        $signatures /** hash<\check_bodies\Signature_Dependency,int> */ = [];
        for ($target = 500; $target < 505; $target++) {
            $params /** vector<int> */ = []; $passing /** vector<int> */ = [];
            if ($target < 503) { $params[] = $integer_id; }
            if ($target === 501) { $params[] = $integer_id; }
            $result = $integer_id; if (($target === 502) || ($target === 504)) { $result = $void_id; }
            $repr = $f->types->intern_signature($result,$params,$passing);
            $signatures[$target] = new \check_bodies\Signature_Dependency($target,$repr,$f->types->representation_by_id($repr),$params);
        }
        $types /** hash<\type_model\Type_Record,int> */ = [];
        for ($i = 1; $i < $f->types->type_count()+1; $i++) { $types[$i] = $f->types->type_by_id($i); }
        $statements /** vector<\check_bodies\Typed_Statement> */ = [];
        $scopes /** vector<\check_bodies\Typed_Scope> */ = []; $blocks /** vector<\check_bodies\Typed_Block> */ = [];
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $rows = $case_data->member('values');
            $values /** vector<\check_bodies\Typed_Value> */ = [];
            for ($i = 0; $i < $rows->size(); $i++) {
                $row = $rows->at($i); $kind = $row->at(0)->text(); $a = $row->at(1)->integer(); $b = $row->at(2)->integer();
                $value = new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_INTEGER_LITERAL,'1');
                if ($kind === 'convert') { $value = new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_CONVERSION,'',0,null,new \check_bodies\Conversion_Value($a,1)); }
                elseif ($kind === 'operation') {
                    $operand_types /** vector<int> */ = [$integer_id,$integer_id];
                    $op = new \check_bodies\Operation_Value($a,$b,new \type_model\Operation_Contract('addition',$operand_types,$integer_id,new \type_model\Implementation_Binding(1,'compiler.integer','add_wrap')));
                    $value = new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_OPERATION,'',0,null,null,$op);
                } elseif (($kind === 'call') || ($kind === 'wrong_call')) {
                    $result_type = $integer_id; if ($kind === 'wrong_call') { $result_type = $byte_id; }
                    $value = new \check_bodies\Typed_Value(1,$result_type,\check_bodies\VALUE_CALL_RESULT,'',$a);
                } elseif ($kind === 'place') {
                    $path /** vector<\check_bodies\Place_Projection> */ = [new \check_bodies\Place_Projection(1,0,$integer_id),new \check_bodies\Place_Projection(2,$a,$integer_id),new \check_bodies\Place_Projection(3,$b,$integer_id)];
                    $value = new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_LOCAL_READ,'',0,new \check_bodies\Place(1,$path));
                }
                $values[] = $value;
            }
            $calls /** vector<\check_bodies\Typed_Call> */ = []; $call_rows = $case_data->member('calls');
            for ($i = 0; $i < $call_rows->size(); $i++) { $row = $call_rows->at($i); $calls[] = new \check_bodies\Typed_Call(1,$row->at(0)->integer(),$row->at(1)->integer(),$row->at(2)->integer(),$row->at(3)->integer()); }
            $arguments /** vector<\check_bodies\Typed_Argument> */ = []; $args = $case_data->member('args');
            for ($i = 0; $i < $args->size(); $i++) { $arguments[] = new \check_bodies\Typed_Argument($args->at($i)->integer(),$integer_id); }
            $body = new \check_bodies\Checked_Body($f->input,$names,false,$locals,$values,$calls,$statements,$scopes,$arguments,$blocks,$types,$signatures);
            $order = \check_bodies\Expression_Order::steps($body,$case_data->member('root')->integer(),$case_data->member('start')->integer(),$case_data->member('limit')->integer());
            $expected = $case_data->member('expected'); $seen = 0; $valid = true; $failed = false;
            try {
                $step = $order->next();
                while ($step !== null) {
                    if ($seen >= $expected->size()) { $valid = false; break; }
                    $pair = $expected->at($seen);
                    if (($step->value_id !== $pair->at(0)->integer()) || ($step->call_id !== $pair->at(1)->integer())) { $valid = false; }
                    $seen++; $step = $order->next();
                }
                if (!$case_data->member('error')->boolean()) { if ($order->next() !== null) { $valid = false; } }
            } catch (\LogicException $error) { $failed = true; }
            if (($failed !== $case_data->member('error')->boolean()) || ($seen !== $expected->size())) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
        // Deep input proves iterative traversal; no retained event vector is needed.
        $deep /** vector<\check_bodies\Typed_Value> */ = [new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_INTEGER_LITERAL,'1')];
        for ($i = 1; $i < 4096; $i++) { $deep[] = new \check_bodies\Typed_Value(1,$integer_id,\check_bodies\VALUE_CONVERSION,'',0,null,new \check_bodies\Conversion_Value($i,1)); }
        $no_calls /** vector<\check_bodies\Typed_Call> */ = []; $no_args /** vector<\check_bodies\Typed_Argument> */ = [];
        $body = new \check_bodies\Checked_Body($f->input,$names,false,$locals,$deep,$no_calls,$statements,$scopes,$no_args,$blocks,$types,$signatures);
        $order = \check_bodies\Expression_Order::steps($body,4096,0,0); $step = $order->next(); $seen = 0;
        while ($step !== null) { $seen++; if ($step->value_id !== $seen) { throw new \LogicException('Deep evaluation order differs'); } $step = $order->next(); }
        if ($seen !== 4096) { throw new \LogicException('Incomplete deep expression'); }
    }
}
