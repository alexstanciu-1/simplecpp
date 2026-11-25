<?php
declare(strict_types=1);
namespace local_types_test;
final class Probe {
    public static function reader(\local_types_fixture\Fixture $f, \type_model\Type_Store $types): \instantiate\Bindings {
        return new \instantiate\Bindings(new \resolve_types\Annotation_Types($f->reader->annotations->names,new \resolve_types\Definition_View($f->catalog,$types)),$f->catalog,$f->instances->view());
    }
    public static function signatures(\local_types_fixture\Fixture $f): \resolve_types\Signature_Set {
        $none /** vector<\resolve_types\Callable_Signature> */ = [];$old=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$none);
        $tasks=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$old,$f->prepared,false);
        $requests /** vector<\resolve_types\Signature_Request> */ = [];foreach($tasks as $task){$requests[]=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared);}
        return (new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$old,$tasks,$f->entry,$f->prepared))->join($requests);
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++) {
            $mode=$cases->at($ci)->member('mode')->text();$fixture_mode='scalar';if(($mode==='template')||($mode==='method')||($mode==='copy')||($mode==='void_local')){$fixture_mode=$mode;}if($mode==='stale_instance'){$fixture_mode='template';}
            $f=\local_types_fixture\Fixture::prepare($fixture_mode,0);$types=$f->types;$signatures=\local_types_test\Probe::signatures($f);$history=new \resolve_types\Local_Type_Validity();
            $tasks=\resolve_types\Local_Type_Resolver::select($f->symbols,$f->reader,$types,$history,false,$f->entry);$requests /** vector<\resolve_types\Local_Type_Request> */ = [];
            $before_types=$types->type_count();$before_shapes=$types->representation_count();$before_members=$types->member_count();$caught=false;$reject=$mode==='void_local';$ok=false;
            try{
                foreach($tasks as $task){$requests[]=\resolve_types\Local_Type_Resolver::resolve($f->symbols,$f->reader,$task);}
                if($mode==='duplicate_task'){$reject=true;$tasks[]=$tasks[0];}
                if($mode==='duplicate_result'){$reject=true;$requests[]=$requests[0];}
                if($mode==='missing'){$reject=true;$empty /** vector<\resolve_types\Local_Type_Request> */ = [];$requests=$empty;}
                if($mode==='unselected'){$reject=true;$empty_tasks /** vector<\resolve_types\Callable_Input> */ = [];$tasks=$empty_tasks;}
                if(($mode==='wrong_input')||($mode==='wrong_definition')||($mode==='wrong_count')||($mode==='stale_names')){
                    $reject=true;$request=$requests[0];$input=$request->input;$bindings=$request->names;$definitions /** vector<\type_model\Named_Definition> */ = [];for($i=0;$i<$request->size();$i++){$definitions[]=$request->at($i);}
                    if($mode==='wrong_input'){$input=new \resolve_types\Callable_Input($input->owner,$input->instance);}
                    if($mode==='wrong_definition'){$other=$f->catalog->find_type('uint8','');if($other===null){throw new \LogicException('Missing fixture type');}$definitions[0]=$other;}
                    if($mode==='wrong_count'){$definitions[]=$request->at(0);}
                    if($mode==='stale_names'){$other=\local_types_fixture\Fixture::prepare('scalar',0);$bindings=$other->reader->annotations->bindings($other->input->owner);$input=$other->input;}
                    $requests[0]=new \resolve_types\Local_Type_Request($input,$bindings,$definitions);
                }
                if(($mode==='missing_signature')||($mode==='signature_count')){
                    $reject=true;$rows /** vector<\resolve_types\Callable_Signature> */ = [];
                    for($i=0;$i<$signatures->size();$i++){$row=$signatures->at($i);if($row->callable_id===$tasks[0]->callable_id){if($mode==='missing_signature'){continue;}$parameters /** vector<int> */ = [];$passing /** vector<int> */ = [];$shape=$types->intern_signature($types->find_type('int',''),$parameters,$passing);$row=new \resolve_types\Callable_Signature($row->input,$row->return_annotation_id,$shape);}$rows[]=$row;}
                    $signatures=new \resolve_types\Signature_Set($types,$rows);$before_shapes=$types->representation_count();$before_members=$types->member_count();
                }
                if($mode==='foreign_signatures'){$reject=true;$rows /** vector<\resolve_types\Callable_Signature> */ = [];for($i=0;$i<$signatures->size();$i++){$rows[]=$signatures->at($i);}$signatures=new \resolve_types\Signature_Set($types->fork(),$rows);}
                if($mode==='wrong_context'){$reject=true;$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','wrong','wrong'));$before_types=0;$before_shapes=0;$before_members=0;}
                $locals=(new \resolve_types\Local_Type_Join($f->symbols,$f->reader,$types,$history,$tasks,$f->entry,$signatures))->join($requests);
                $expected_count=1;if(($fixture_mode==='template')||($fixture_mode==='method')||($fixture_mode==='copy')){$expected_count=2;}
                $ok=q_count($locals)===$expected_count;
                $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];$families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
                $snapshot=new \resolve_types\Type_Resolution($types,$f->catalog,$f->entry,$signatures,$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
                $ordinary=$f->symbols->find_symbol('f',\collect_symbols\SYMBOL_FUNCTION,0,'');$local=$snapshot->locals_for($ordinary);if($local===null){throw new \LogicException('Missing ordinary local result');}
                $ok=$ok&&($local->size()===4)&&($local->type_for(1)===$types->find_type('int32',''))&&($local->type_for(2)===$types->find_type('uint8',''))&&($local->type_for(3)===$types->find_type('uint32',''))&&($local->type_for(4)===$types->find_type('uint8',''));
                if($mode==='template'){$instance_local=$snapshot->locals_for($f->input->callable_id);if($instance_local===null){throw new \LogicException('Missing template local');}$ok=$ok&&($instance_local->size()===2)&&($instance_local->type_for(1)===$instance_local->type_for(2));}
                if(($mode==='reuse')||($mode==='full_select')||($mode==='invalidate_local')||($mode==='stale_binding')||($mode==='stale_instance')){
                    $candidate=$types->fork();$history=new \resolve_types\Local_Type_Validity($snapshot);$reader=\local_types_test\Probe::reader($f,$candidate);
                    if($mode==='invalidate_local'){$candidate->invalidate_definition($candidate->find_type('uint32',''));}
                    if($mode==='stale_binding'){$other=\local_types_fixture\Fixture::prepare('scalar',0);$ok=!$history->is_current($candidate,$other->reader->annotations->bindings($other->input->owner),$other->input);}
                    elseif($mode==='stale_instance'){$old=$f->input->instance;if($old===null){throw new \LogicException('Missing instance');}$args /** vector<\instantiate\Template_Argument> */ = [$old->argument_at(0)];$input=new \resolve_types\Callable_Input($old->definition,new \instantiate\Instance_Context($old->definition,$old->instance_id,$args));$ok=!$history->is_current($candidate,$f->reader->annotations->bindings($old->definition),$input);}
                    else {
                        $next=\resolve_types\Local_Type_Resolver::select($f->symbols,$reader,$candidate,$history,$mode==='full_select',$f->entry);
                        if($mode==='full_select'){$ok=$ok&&(q_count($next)===1);}
                        else {
                            $expected=0;if($mode==='invalidate_local'){$expected=1;}$ok=$ok&&(q_count($next)===$expected);
                            $next_requests /** vector<\resolve_types\Local_Type_Request> */ = [];foreach($next as $input){$next_requests[]=\resolve_types\Local_Type_Resolver::resolve($f->symbols,$reader,$input);}
                            $rows /** vector<\resolve_types\Callable_Signature> */ = [];for($i=0;$i<$signatures->size();$i++){$rows[]=$signatures->at($i);}$next_signatures=new \resolve_types\Signature_Set($candidate,$rows);
                            $again=(new \resolve_types\Local_Type_Join($f->symbols,$reader,$candidate,$history,$next,$f->entry,$next_signatures))->join($next_requests);
                            $same=$again[0]===$locals[0];$ok=$ok&&($same===($mode==='reuse'))&&($again[0]->type_for(3)===$local->type_for(3));
                        }
                    }
                }
            }catch(\LogicException $error){$caught=true;}catch(\OutOfBoundsException $error){$caught=true;}catch(\RuntimeException $error){$caught=true;}
            if($reject){$ok=$caught&&($types->type_count()===$before_types)&&($types->representation_count()===$before_shapes)&&($types->member_count()===$before_members);if($mode==='void_local'){$diagnostic=$f->reader->annotations->diagnostic();if($diagnostic===null){$ok=false;}else{$ok=$ok&&($diagnostic->path==='/signatures.phs')&&($diagnostic->reason==='A local requires a value type; void has no value')&&($diagnostic->length===4)&&(string_byte_slice($f->input->owner->source_frontend()->tokens->source->content,$diagnostic->start,$diagnostic->length)==='void');}}}else{if($caught){$ok=false;}}
            echo $ok ? "true\n" : "false\n";
        }
    }
}
