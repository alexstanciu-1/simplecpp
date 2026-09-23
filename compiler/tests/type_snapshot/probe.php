<?php
declare(strict_types=1);
namespace type_snapshot_test;
final class Probe {
    public static function package(\type_model\Type_Catalog $catalog, \type_model\Named_Definition $definition): \load_runtime\Runtime_Package {
        $rows /** hash<\load_runtime\Runtime_Type> */ = [];
        $rows['instance']=new \load_runtime\Runtime_Type('instance',new \load_runtime\Runtime_Storage(\load_runtime\RUNTIME_STORAGE_INTEGER,4,4),32,true,$definition);
        $calls /** vector<\type_model\Runtime_Callable> */ = [];$strings /** vector<string> */ = [];$modules /** hash<string> */ = [];
        $families /** hash<\type_model\Storage_Family> */ = [];$exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        return new \load_runtime\Runtime_Package('p','/fixture','target','layout','clang',$strings,$rows,$calls,$modules,$strings,'manifest',$catalog,$catalog,$families,null,null,$exports);
    }
    public static function bound(\signature_requests_test\Fixture $f, int $id, string $mode, bool $second): \resolve_types\Callable_Signature {
        $output='uint8';if($mode==='conversion_identity'){$output='int32';}if($mode==='literal_duplicate'){if($second){$output='int32';}}
        $call=\signature_requests_test\Fixture::callable('int32',$output,false);
        $binding /** nullable<int> */ = null;$purpose /** nullable<int> */ = null;$is_default=false;
        if(($mode==='language')||($mode==='language_duplicate')){$binding=\type_model\BINDING_ECHO;}
        elseif(($mode==='literal')||($mode==='literal_duplicate')){$binding=\type_model\BINDING_BYTE_LITERAL;$is_default=true;}
        else {$purpose=\type_model\CONVERSION_EXPLICIT;}
        $call=new \type_model\Runtime_Callable('p','bound_'.$id,'bound_'.$id,'',$call->signature,$call->abi,$binding,$is_default,$purpose);
        $provider=new \collect_symbols\Provider_Declaration($call,null,null,null,null);$owner=\collect_symbols\Symbol_Record::from_provider($id,0,$provider);
        $target=$f->catalog->find_type($output,'');$source=$f->catalog->find_type('int32','');if(($target===null)||($source===null)){throw new \LogicException('Missing fixture definitions');}
        $parameters /** vector<int> */ = [\resolve_types\Type_Cache::materialize($f->types,$source)];$passing /** vector<int> */ = [\type_model\PASS_VALUE];
        $shape=$f->types->intern_signature(\resolve_types\Type_Cache::materialize($f->types,$target),$parameters,$passing);
        return new \resolve_types\Callable_Signature(new \resolve_types\Callable_Input($owner),0,$shape,$call);
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++){
            $mode=$cases->at($ci)->member('mode')->text();$fixture_mode='scalar';if(($mode==='template')||($mode==='stale_instance')){$fixture_mode='template';}
            $f=\signature_requests_test\Fixture::prepare($fixture_mode,0);$empty /** vector<\resolve_types\Callable_Signature> */ = [];
            $old=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$empty);
            $tasks=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$old,$f->prepared,false);
            $requests /** vector<\resolve_types\Signature_Request> */ = [];foreach($tasks as $task){$requests[]=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared);}
            $set=(new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$old,$tasks,$f->entry,$f->prepared))->join($requests);
            $rows /** vector<\resolve_types\Callable_Signature> */ = [];for($i=0;$i<$set->size();$i++){$rows[]=$set->at($i);}
            $locals /** vector<\resolve_types\Local_Types> */ = [];$names=$f->reader->annotations->names;$bindings=$names->for_symbol($f->input->owner->symbol_id);if($bindings===null){throw new \LogicException('Missing fixture bindings');}
            $record=$set->for_callable($f->input->callable_id);if($record===null){throw new \LogicException('Missing fixture signature');}
            $shape=$f->types->representation_by_id($record->representation_id);$ids /** vector<int> */ = [];for($p=0;$p<$shape->member_count();$p++){$ids[]=$f->types->member_at($shape->member_first()+$p)->type_id;}
            $entry=$f->entry;$types=$f->types;$families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
            $reject=false;$caught=false;$ok=false;
            try{
                if($mode==='local_mismatch'){$reject=true;$ids[0]=$types->find_type('uint8','');}
                if($mode==='stale_locals'){$reject=true;$other=\signature_requests_test\Fixture::prepare('scalar',0);$bindings=$other->reader->annotations->bindings($other->input->owner);}
                $local=new \resolve_types\Local_Types($bindings,$ids,$f->input->instance);if($mode!=='no_locals'){$locals[]=$local;}
                if($mode==='duplicate_locals'){$reject=true;$locals[]=$local;}
                if(($mode==='missing_entry')||($mode==='missing_local_signature')){$reject=true;$keep /** vector<\resolve_types\Callable_Signature> */ = [];foreach($rows as $row){$remove=$row->callable_id===$entry->symbol->symbol_id;if($mode==='missing_local_signature'){$remove=$row->callable_id===$f->input->callable_id;}if(!$remove){$keep[]=$row;}}$rows=$keep;}
                if($mode==='stale_entry'){$reject=true;$other=\signature_requests_test\Fixture::prepare('scalar',0);$entry=$other->entry;}
                if($mode==='entry_return'){$reject=true;$alternate=$f->catalog->find_type('uint8','');if($alternate===null){throw new \LogicException('Missing fixture alternate');}$entry=new \resolve_types\Entry_Contract($entry->symbol,$alternate);}
                if($mode==='wrong_store'){$reject=true;$types=$types->fork();}
                if(($mode==='conversion')||($mode==='conversion_duplicate')||($mode==='conversion_identity')||($mode==='language')||($mode==='language_duplicate')||($mode==='literal')||($mode==='literal_duplicate')){
                    $rows[]=\type_snapshot_test\Probe::bound($f,100,$mode,false);
                    if(($mode==='conversion_duplicate')||($mode==='language_duplicate')||($mode==='literal_duplicate')){$reject=true;$rows[]=\type_snapshot_test\Probe::bound($f,101,$mode,true);}
                    if($mode==='conversion_identity'){$reject=true;}
                }
                if(($mode==='family')||($mode==='family_key')||($mode==='family_context')||($mode==='family_type')||($mode==='family_missing_type')){
                    $owner=$f->symbols->symbol_by_id($f->symbols->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));$argument=$f->catalog->find_type('int32','');if($argument===null){throw new \LogicException('Missing family argument');}
                    $args /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($argument)];$id=$f->instances->allocate($types,$owner->symbol_id,$args);$context=new \instantiate\Instance_Context($owner,$id,$args);$f->instances->accept($context);$f->instances->accept_type($context,$argument);
                    if($mode==='family_context'){$reject=true;$context=new \instantiate\Instance_Context($owner,$id,$args);}
                    if($mode==='family_type'){$reject=true;$argument=$f->catalog->find_type('uint8','');if($argument===null){throw new \LogicException('Missing different type');}}
                    $ops /** vector<string> */ = ['read'];$sources /** hash<\prepare_backend\Source_Type_Export,int> */ = [];$callables /** hash<\type_model\Runtime_Callable> */ = [];$callables['read']=\signature_requests_test\Fixture::callable('int32','int32',false);
                    $task=new \load_runtime\Family_Preparation_Task($context,$ops,$sources);$package=\type_snapshot_test\Probe::package($f->catalog,$argument);$type_id='instance';if($mode==='family_missing_type'){$reject=true;$type_id='absent';}if($mode==='family_key'){$reject=true;$id=$id+1;}
                    $families[$id]=new \load_runtime\Family_Preparation_Result($task,$package,$type_id,$callables);
                }
                $source_bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];$instances=$f->instances->snapshot($source_bindings);
                if($mode==='stale_instance'){$reject=true;$other=\signature_requests_test\Fixture::prepare('scalar',0);$instances=$other->instances->snapshot($source_bindings);}
                $set=new \resolve_types\Signature_Set($f->types,$rows);
                $snapshot=new \resolve_types\Type_Resolution($types,$f->catalog,$entry,$set,$locals,$names,$instances,$families);
                $ok=($snapshot->for_callable($f->input->callable_id)===$record)&&($snapshot->integer_literal_type()===$types->find_type($f->catalog->integer_literal_type->name,''));
                if($mode==='no_locals'){$ok=$ok&&($snapshot->locals_for($f->input->callable_id)===null);}else{$ok=$ok&&($snapshot->locals_for($f->input->callable_id)===$local);}
                if($mode==='ordinary'){$ok=$ok&&($snapshot->parameter_type_for($f->input->callable_id,2)===$types->find_type('uint8',''))&&(q_count($snapshot->body_signatures())===2);}
                if($mode==='template'){$ok=$ok&&($snapshot->for_symbol($f->input->callable_id)===null);}
                if($mode==='family'){foreach($families as $id=>$accepted){$ok=$ok&&($snapshot->family_for($id)===$accepted)&&($accepted->task->operation_at(0)==='read')&&($accepted->operation_for('read')->id==='call');}}
                if($mode==='conversion'){$ok=$ok&&($snapshot->conversion_callable(\type_model\CONVERSION_EXPLICIT,$types->find_type('int32',''),$types->find_type('uint8',''))===100)&&($snapshot->conversion_callable(\type_model\CONVERSION_IMPLICIT,$types->find_type('int32',''),$types->find_type('uint8',''))===0);}
                if($mode==='language'){$ok=$ok&&($snapshot->language_callable(\type_model\BINDING_ECHO,$types->find_type('int32',''))===100);}
                if($mode==='literal'){$ok=$ok&&($snapshot->language_callable(\type_model\BINDING_BYTE_LITERAL,0)===100)&&($snapshot->language_callable(\type_model\BINDING_BYTE_LITERAL,$types->find_type('uint8',''))===100);}
                if($mode==='missing_lookup'){$ok=$ok&&($snapshot->for_callable(0)===null)&&($snapshot->locals_for(0)===null)&&($snapshot->family_for(0)===null)&&($snapshot->language_callable(\type_model\BINDING_ECHO,0)===0);}
                if($mode==='parameter_bounds'){$reject=true;$snapshot->parameter_type_for($f->input->callable_id,0);}
            }catch(\LogicException $error){$caught=true;}catch(\OutOfBoundsException $error){$caught=true;}
            if($reject){$ok=$caught;}else{if($caught){$ok=false;}}
            echo $ok ? "true\n" : "false\n";
        }
    }
}
