<?php
declare(strict_types=1);
namespace signature_publication_test;
final class Probe {
    public static function reader(\signature_requests_test\Fixture $f, \type_model\Type_Store $types): \instantiate\Bindings {
        return new \instantiate\Bindings(new \resolve_types\Annotation_Types($f->reader->annotations->names,new \resolve_types\Definition_View($f->catalog,$types)),$f->catalog,$f->instances->view());
    }
    public static function requests(\signature_requests_test\Fixture $f, array $tasks /** vector<\resolve_types\Callable_Input> */): array /** vector<\resolve_types\Signature_Request> */ {
        $out /** vector<\resolve_types\Signature_Request> */ = [];
        foreach($tasks as $task){$out[]=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared);}return $out;
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++) {
            $mode=$cases->at($ci)->member('mode')->text();$fixture_mode='scalar';
            if(($mode==='template')||($mode==='method')||($mode==='const_method')||($mode==='copy')||($mode==='family')||($mode==='storage')){$fixture_mode=$mode;}
            if($mode==='changed_external'){$fixture_mode='family';}
            if($mode==='bad_storage'){$fixture_mode='storage';}if($mode==='bad_receiver'){$fixture_mode='method';}if($mode==='stale_instance'){$fixture_mode='template';}
            $f=\signature_requests_test\Fixture::prepare($fixture_mode,0);$empty /** vector<\resolve_types\Callable_Signature> */ = [];
            $previous=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$empty);
            $tasks=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$previous,$f->prepared,false);
            $results=\signature_publication_test\Probe::requests($f,$tasks);$expected_count=q_count($tasks);
            $reject=false;$caught=false;$ok=false;$types=$f->types;
            $before_types=$types->type_count();$before_shapes=$types->representation_count();$before_members=$types->member_count();
            try {
                if($mode==='duplicate_task'){$reject=true;$tasks[]=$tasks[0];}
                if($mode==='duplicate_result'){$reject=true;$results[]=$results[0];}
                if($mode==='missing'){$reject=true;$few /** vector<\resolve_types\Signature_Request> */ = [];for($j=1;$j<q_count($results);$j++){$few[]=$results[$j];}$results=$few;}
                if($mode==='unselected'){$reject=true;$none /** vector<\resolve_types\Callable_Input> */ = [];$tasks=$none;}
                if($mode==='reverse'){$reversed /** vector<\resolve_types\Signature_Request> */ = [];for($j=q_count($results)-1;$j>=0;$j=$j-1){$reversed[]=$results[$j];}$results=$reversed;}
                if($mode==='same_store'){$reject=true;$previous=new \resolve_types\Signature_Set($types,$empty);}
                if($mode==='wrong_context'){$reject=true;$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','wrong','wrong'));$before_types=0;$before_shapes=0;$before_members=0;}
                for($j=0;$j<q_count($results);$j++){
                    $result=$results[$j];$owner=$result->input->owner;$alter=false;
                    if(($mode==='bad_entry')&&($owner===$f->entry->symbol)){$alter=true;}
                    if($mode==='bad_provider'){if(!$owner->is_source()){if($owner->provider()->kind()===\collect_symbols\PROVIDER_CALLABLE){$alter=true;}}}
                    if(($mode==='bad_storage')||($mode==='bad_receiver')){if($result->input->callable_id===$f->input->callable_id){$alter=true;}}
                    if($owner===$f->input->owner){if(($mode==='wrong_input')||($mode==='wrong_return')||($mode==='wrong_parameter')||($mode==='wrong_passing')||($mode==='wrong_count')||($mode==='wrong_annotation')){$alter=true;}}
                    if(!$alter){continue;}$reject=true;
                    $parameters /** vector<\type_model\Named_Definition> */ = [];$passing /** vector<int> */ = [];
                    for($p=0;$p<$result->parameter_count();$p++){$parameters[]=$result->parameter_at($p);$passing[]=$result->passing_at($p);}
                    $input=$result->input;$definition=$result->definition;$annotation=$result->return_annotation_id;
                    $other=$f->catalog->find_type('uint8','');if($other===null){throw new \LogicException('Missing test type');}
                    if($mode==='wrong_input'){$input=new \resolve_types\Callable_Input($input->owner,$input->instance);}
                    if(($mode==='wrong_return')||($mode==='bad_entry')||($mode==='bad_provider')||($mode==='bad_storage')){$definition=$other;}
                    if(($mode==='wrong_parameter')||($mode==='bad_receiver')){$parameters[0]=$other;}
                    if($mode==='wrong_passing'){$passing[0]=\type_model\PASS_BORROW_CONST;}
                    if($mode==='wrong_count'){$parameters[]=$other;$passing[]=\type_model\PASS_VALUE;}
                    if($mode==='wrong_annotation'){$annotation=0;}
                    $results[$j]=new \resolve_types\Signature_Request($input,$annotation,$definition,$parameters,$passing);
                }
                $joined=(new \resolve_types\Signature_Join($f->symbols,$f->reader,$types,$previous,$tasks,$f->entry,$f->prepared))->join($results);
                $ok=$joined->size()===$expected_count;
                for($j=0;$j<$joined->size();$j++){
                    $row=$joined->at($j);if($row->callable_id!==$tasks[$j]->callable_id){$ok=false;}
                    $shape=$types->representation_by_id($row->representation_id);$request=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$row->input,$f->entry,$f->prepared);
                    if(($types->definition_for_type($shape->signature_return())!==$request->definition)||($shape->member_count()!==$request->parameter_count())){$ok=false;}
                    for($p=0;$p<$shape->member_count();$p++){if(($types->definition_for_type($types->member_at($shape->member_first()+$p)->type_id)!==$request->parameter_at($p))||($shape->parameter_passing($p)!==$request->passing_at($p))){$ok=false;}}
                }
                if(($mode==='reuse')||($mode==='full_select')||($mode==='invalidate_return')||($mode==='invalidate_parameter')||($mode==='foreign_store')||($mode==='changed_external')||($mode==='stale_owner')||($mode==='stale_instance')) {
                    $candidate=$types->fork();$prepared=$f->prepared;$input=$f->input;
                    if(($mode==='invalidate_return')||($mode==='invalidate_parameter')){$name='int32';if($mode==='invalidate_parameter'){$name='uint8';}$candidate->invalidate_definition($candidate->find_type($name,''));}
                    if($mode==='foreign_store'){$candidate=\type_model\Type_Store::fresh($types->context);}
                    if($mode==='stale_owner'){$another=\signature_requests_test\Fixture::prepare('scalar',0);$input=$another->input;}
                    if($mode==='stale_instance'){$old=$input->instance;if($old===null){throw new \LogicException('Missing fixture instance');}$args /** vector<\instantiate\Template_Argument> */ = [];for($p=0;$p<$old->argument_count();$p++){$args[]=$old->argument_at($p);}$input=new \resolve_types\Callable_Input($input->owner,new \instantiate\Instance_Context($input->owner,$old->instance_id,$args,$old->receiver_type));}
                    if($mode==='changed_external'){$prepared[$input->callable_id]=\signature_requests_test\Fixture::callable('Point','int32',true);}
                    if(($mode==='changed_external')||($mode==='stale_owner')||($mode==='stale_instance')||($mode==='invalidate_return')||($mode==='invalidate_parameter')||($mode==='foreign_store')){$ok=!\resolve_types\Signature_Validity::is_current($joined,$candidate,$input,$prepared);}
                    else {
                        $next=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$candidate,$joined,$prepared,$mode==='full_select');
                        if($mode==='full_select'){$ok=q_count($next)===$joined->size();}
                        else {$ok=q_count($next)===0;$none /** vector<\resolve_types\Signature_Request> */ = [];$again=(new \resolve_types\Signature_Join($f->symbols,\signature_publication_test\Probe::reader($f,$candidate),$candidate,$joined,$next,$f->entry,$prepared))->join($none);for($j=0;$j<$joined->size();$j++){if($again->at($j)!==$joined->at($j)){$ok=false;}}}
                    }
                }
            } catch(\LogicException $error){$caught=true;} catch(\OutOfBoundsException $error){$caught=true;}
            if($reject){$ok=$caught&&($types->type_count()===$before_types)&&($types->representation_count()===$before_shapes)&&($types->member_count()===$before_members);}else{if($caught){$ok=false;}}
            echo $ok ? "true\n" : "false\n";
        }
    }
}
