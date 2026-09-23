<?php
declare(strict_types=1);
namespace construction_types_test;
final class Probe {
    public static function package(\type_model\Type_Catalog $catalog, \type_model\Named_Definition $definition): \load_runtime\Runtime_Package {
        $rows /** hash<\load_runtime\Runtime_Type> */ = [];
        $rows['instance']=new \load_runtime\Runtime_Type('instance',new \load_runtime\Runtime_Storage(\load_runtime\RUNTIME_STORAGE_INTEGER,4,4),32,true,$definition);
        $calls /** vector<\type_model\Runtime_Callable> */ = [];$strings /** vector<string> */ = [];$modules /** hash<string> */ = [];
        $families /** hash<\type_model\Storage_Family> */ = [];$exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        return new \load_runtime\Runtime_Package('p','/fixture','target','layout','clang',$strings,$rows,$calls,$modules,$strings,'manifest',$catalog,$catalog,$families,null,null,$exports);
    }
    public static function snapshot(\construction_fixture\Fixture $f, bool $with_family): \resolve_types\Type_Resolution {
        $none /** vector<\resolve_types\Callable_Signature> */ = [];$old=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$none);
        $tasks=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$old,$f->prepared,false);
        $requests /** vector<\resolve_types\Signature_Request> */ = [];foreach($tasks as $task){$requests[]=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared);}
        $set=(new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$old,$tasks,$f->entry,$f->prepared))->join($requests);
        $locals /** vector<\resolve_types\Local_Types> */ = [];$bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];$families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        $owner_names=$f->reader->annotations->bindings($f->input->owner);$row=$set->for_callable($f->input->callable_id);if($row===null){throw new \LogicException('Missing fixture callable');}$shape=$f->types->representation_by_id($row->representation_id);$ids /** vector<int> */ = [];for($i=0;$i<$shape->member_count();$i++){$ids[]=$f->types->member_at($shape->member_first()+$i)->type_id;}$locals[]=new \resolve_types\Local_Types($owner_names,$ids,$f->input->instance);
        if($with_family){
            $owner=$f->symbols->symbol_by_id($f->symbols->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));$definition=$f->catalog->find_type('int32','');if($definition===null){throw new \LogicException('Missing fixture type');}
            $args /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($definition)];$id=$f->instances->allocate($f->types,$owner->symbol_id,$args);$context=new \instantiate\Instance_Context($owner,$id,$args);$f->instances->accept($context);$f->instances->accept_type($context,$definition);
            $ops /** vector<string> */ = ['read'];$sources /** hash<\prepare_backend\Source_Type_Export,int> */ = [];$calls /** hash<\type_model\Runtime_Callable> */ = [];$calls['read']=\construction_fixture\Fixture::callable('int32','int32',false);
            $task=new \load_runtime\Family_Preparation_Task($context,$ops,$sources);$families[$id]=new \load_runtime\Family_Preparation_Result($task,\construction_types_test\Probe::package($f->catalog,$definition),'instance',$calls);
        }
        return new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$set,$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
    }
    public static function name_node(\construction_fixture\Fixture $f, string $name): int {
        $bindings=$f->reader->annotations->bindings($f->input->owner);$tree=$f->input->owner->source_frontend()->tree;$found=0;
        if(($name!=='T')&&($f->input->owner->owner_symbol_id===0)){
            for($i=1;$i<$tree->size()+1;$i++){$parent=$tree->row($i);if((int)$parent->kind!==\parse\SYNTAX_CONSTRUCT_EXPRESSION){continue;}$node=(int)$parent->first_child;$row=$tree->row($node);$text=string_byte_slice($f->input->owner->source_frontend()->tokens->source->content,(int)$row->start,(int)$row->length);if($text===$name){$found=$node;break;}}
        }else{
            for($i=0;$i<$bindings->names_count();$i++){$node=$bindings->names_at($i)->use_node_id;$row=$tree->row($node);$text=string_byte_slice($f->input->owner->source_frontend()->tokens->source->content,(int)$row->start,(int)$row->length);if($text===$name){$found=$node;break;}}
        }
        if($found===0){throw new \LogicException('Missing fixture name');}return $found;
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++){
            $mode=$cases->at($ci)->member('mode')->text();$fixture_mode='scalar';if(($mode==='template_annotation')||($mode==='stale_instance')){$fixture_mode='template';}
            if($mode==='debug_method'){$fixture_mode='method';}$f=\construction_fixture\Fixture::prepare($fixture_mode,0);$snapshot=\construction_types_test\Probe::snapshot($f,$mode==='debug_family');$input=$f->input;$name='int32';$expected_error=false;$source_error=false;$reason='';
            if($mode==='record'){$name='Point';}
            if($mode==='unmaterialized'){$name='uint32';$expected_error=true;$source_error=true;$reason='Construction type has no prepared value contract';}
            if($mode==='unprepared_record'){$name='Unready';$expected_error=true;$source_error=true;$reason='Bound construction type requires unsupported concrete preparation';}
            if(($mode==='template_annotation')||($mode==='stale_instance')){$name='T';}
            $node=\construction_types_test\Probe::name_node($f,$name);
            if($mode==='stale_owner'){$expected_error=true;$other=\construction_fixture\Fixture::prepare('scalar',0);$input=$other->input;}
            if($mode==='stale_instance'){$expected_error=true;$instance=$input->instance;if($instance===null){throw new \LogicException('Missing fixture instance');}$args /** vector<\instantiate\Template_Argument> */ = [$instance->argument_at(0)];$input=new \resolve_types\Callable_Input($instance->definition,new \instantiate\Instance_Context($instance->definition,$instance->instance_id,$args));}
            if($mode==='provider_owner'){$expected_error=true;$input=new \resolve_types\Callable_Input($f->symbols->symbol_by_id($f->symbols->find_symbol('external',\collect_symbols\SYMBOL_FUNCTION,0,'')));}
            if($mode==='unknown_node'){$expected_error=true;$node=0;}
            if($mode==='rebound'){
                $expected_error=true;$source_error=true;$reason='Construction type has no prepared value contract';$definition=$f->catalog->find_type('int32','');if($definition===null){throw new \LogicException('Missing definition');}
                $replacement=new \type_model\Named_Definition($definition->name,$definition->namespace_name,$definition->representation,$definition->lifetime,$definition->signed,$definition->integer_family,$definition->wrapping_addition,$definition->ordered_comparison,$definition->struct_field);
                $f->types->invalidate_definition($f->types->find_type('int32',''));\resolve_types\Type_Cache::materialize($f->types,$replacement);
            }
            $lookup=\resolve_types\Construction_Types::for_snapshot($snapshot);$before_types=$f->types->type_count();$before_shapes=$f->types->representation_count();$before_members=$f->types->member_count();$caught=false;$ok=false;
            try{$id=$lookup->resolve($input,$node);$expected=$name;if($mode==='template_annotation'){$expected='int32';}$ok=($id===$f->types->find_type($expected,''))&&($lookup->diagnostic()===null);}
            catch(\LogicException $error){$caught=true;}catch(\RuntimeException $error){$caught=true;}
            if($expected_error){$ok=$caught;if($source_error){$diagnostic=$lookup->diagnostic();if($diagnostic===null){$ok=false;}else{$ok=$ok&&($diagnostic->path==='/signatures.phs')&&($diagnostic->reason===$reason)&&(string_byte_slice($f->input->owner->source_frontend()->tokens->source->content,$diagnostic->start,$diagnostic->length)===$name);}}else{$ok=$ok&&($lookup->diagnostic()===null);}}
            else{if($caught){$ok=false;}}
            $ok=$ok&&($f->types->type_count()===$before_types)&&($f->types->representation_count()===$before_shapes)&&($f->types->member_count()===$before_members);
            $projection=json_read(\resolve_types\Type_Association_Debug::encode($snapshot));$ok=$ok&&($projection->member('entry_symbol_id')->integer()===$f->entry->symbol->symbol_id)&&($projection->member('signatures')->size()===$snapshot->callables->size())&&($projection->member('local_types')->size()===1)&&($projection->member('prepared_families')->size()===($mode==='debug_family'?1:0));
            $row=$projection->member('local_types')->at(0);$ok=$ok&&($row->member('callable_id')->integer()===$f->input->callable_id)&&($row->member('source_path')->text()==='/signatures.phs');
            if($mode==='debug_family'){$family=$projection->member('prepared_families')->at(0);$ok=$ok&&($family->member('provider')->text()==='p')&&($family->member('family')->text()==='family')&&($family->member('operations')->at(0)->text()==='read')&&($family->member('package_directory')->text()==='/fixture');}
            if($mode==='debug_method'){$signatures=$projection->member('signatures');$ok=$ok&&($signatures->at($signatures->size()-1)->member('receiver_index')->integer()===0);}
            echo $ok ? "true\n" : "false\n";
        }
    }
}
