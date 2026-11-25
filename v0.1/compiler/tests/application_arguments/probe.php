<?php
declare(strict_types=1);
namespace application_arguments_test;
final class Fixtures {
    public static function providers(\type_model\Type_Catalog $catalog): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $policy=new \type_model\Lifetime_Policy();$ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        $descriptor=new \type_model\Named_Definition('descriptor','',\type_model\Representation::opaque(16,8),new \type_model\Lifetime_Contract($policy,$ops),null,'',false,false,true);
        $void_type=$catalog->find_type('void','');if($void_type===null){throw new \LogicException('Missing void');}
        $primitives /** hash<\type_model\Storage_Primitive> */ = [];$spellings /** hash<string> */ = [];$spellings['allocate']='allocate';
        $storage=new \type_model\Storage_Family('p','storage',$descriptor,$catalog->integer_literal_type,$void_type,$primitives,$spellings,'Storage','');
        $function=new \type_model\Storage_Function($storage,\type_model\STORAGE_ALLOCATE,'allocate','','p','allocate');
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE),new \type_model\Family_Parameter('U',\type_model\GENERIC_COPYABLE_VALUE)];
        $operations /** hash<\type_model\Family_Operation> */ = [];$lifecycle /** hash<string> */ = [];$mapping /** hash<\type_model\Type_Reference> */ = [];
        $definition=new \type_model\Family_Definition('p','family',$parameters,$operations,$lifecycle,\type_model\Type_Reference::named('Family',''));
        $family=new \type_model\Family_Declaration($definition,$mapping);
        $out /** vector<\collect_symbols\Provider_Declaration> */ = [new \collect_symbols\Provider_Declaration(null,$storage,null,null,null),new \collect_symbols\Provider_Declaration(null,null,$function,null,null),new \collect_symbols\Provider_Declaration(null,null,null,$family,null)];return $out;
    }
}
final class Probe {
    public static function run(string $text): void {
        $cases=json_read($text);$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        for($ci=0;$ci<$cases->size();$ci++){
            $mode=$cases->at($ci)->text();$application='Target<int,7>';$formals='typename T,int N';$constant_type='int';$expected_error='';$pending=0;
            if(($mode==='source_pending')||($mode==='source_ready')){$application='Target<Source,7>';if($mode==='source_pending'){$pending=1;}}
            if($mode==='source_void'){$application='Target<void,7>';$expected_error='value lifetime';}
            if($mode==='family'){$application='Family<int,uint8>';}
            if($mode==='family_pending'){$application='Family<Source,Other>';$pending=2;}
            if($mode==='family_partial'){$application='Family<int,Source>';$pending=1;}
            if($mode==='family_void'){$application='Family<int,void>';$expected_error='value lifetime';}
            if(($mode==='storage')||($mode==='materialize')||($mode==='stale_materialization')||($mode==='candidate_isolation')||($mode==='owned_element')||($mode==='floating_element')||($mode==='byte_element')){$application='Storage<int32>';}
            if($mode==='storage_pending'){$application='Storage<Source>';$pending=1;}
            if($mode==='storage_void'){$application='Storage<void>';$expected_error='Storage elements require';}
            if($mode==='storage_function'){$application='allocate<int32>' ;}
            if(($mode==='constant')||($mode==='typed_constant')){$application='Target<int,C>';if($mode==='typed_constant'){$constant_type='uint8';$expected_error='type mismatch';}}
            if($mode==='dependent_formal'){$formals='typename T,T N';}
            if($mode==='wrong_formal'){$formals='typename T,uint8 N';$expected_error='language integer contract';}
            $source=new \read_sources\Source_Buffer();$source->path='/applications.phs';
            $entry='$value '.$application.';';if($mode==='storage_function'){$entry=$application.'();';}
            $source->content='struct Source { public int32 $value; } struct Other { public int32 $value; } const C: '.$constant_type.'=3; template<'.$formals.'> struct Target { public T $value; } '.$entry.' return 0;';
            $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($file->error_reason);}
            $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;$providers=Fixtures::providers($catalog);
            $symbols=\collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,$providers)->current;
            $resolution=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);if(!$resolution->valid()){throw new \LogicException($mode.': '.$resolution->error_reason);}$names=$resolution->result();
            $none /** vector<\check_templates\Definition_Result> */ = [];
            $checks=\check_templates\Template_Checker::check($symbols,$names,$catalog,new \check_templates\Template_Set($none,0),false)->result();
            $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
            $state=new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage));
            $initial=new \instantiate\Instance_Set($state,$checks);$view=new \resolve_types\Definition_View($catalog,$types);
            $annotations=new \resolve_types\Annotation_Types($names,$view);$reader=new \instantiate\Bindings($annotations,$catalog,$initial->view());
            $constant=$symbols->symbol_by_id($symbols->find_symbol('C',\collect_symbols\SYMBOL_CONSTANT,0,''));
            $state->constants[$constant->symbol_id]=\instantiate\Constant_Worker::resolve($constant,$reader);
            $store=new \instantiate\Instance_Store($state,$checks);$reader=new \instantiate\Bindings($annotations,$catalog,$store->view());
            $owner=$symbols->symbol_by_id($symbols->entry_symbol_id('/applications.phs'));$binding=$names->for_symbol($owner->symbol_id);if($binding===null){throw new \LogicException('Missing application bindings');}
            if($binding->applications_count()!==1){throw new \LogicException('Unexpected fixture applications');}
            $task=new \instantiate\Application_Task(\instantiate\Instance_Context::ordinary($owner),$binding->applications_at(0));
            $int32=$catalog->find_type('int32','');if($int32===null){throw new \LogicException('Missing int32');}
            if($mode==='source_ready'){$fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',\type_model\Field_Type::named($int32),true)];$record=new \type_model\Record_Declaration('Source','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);\resolve_types\Record_Definitions::materialize($types,$record);}
            $before_types=$types->type_count();$before_ids=$store->next_id();$ok=true;$rejected=false;
            try{
                $result=\instantiate\Application_Worker::run($task,$reader);
                $ok=($result->task===$task)&&($result->prerequisite_count()===$pending)&&($types->type_count()===$before_types)&&($store->next_id()===$before_ids);
                if($pending!==0){
                    $ok=$ok&&!$result->ready()&&($result->argument_count()===0);
                    $parts=\parse\Syntax_Access::template_application_parts($file->tree,$task->application->use_node_id);$first=(int)$parts->first_argument_id;
                    if($mode==='family_partial'){$first=(int)$file->tree->row($first)->next_sibling;}
                    $ok=$ok&&($result->prerequisite_at(0)===$first);if($pending===2){$ok=$ok&&($result->prerequisite_at(1)===(int)$file->tree->row($first)->next_sibling);}
                }else{
                    $ok=$ok&&$result->ready();
                    if(($mode==='source')||($mode==='dependent_formal')){$ok=$ok&&($result->argument_count()===2)&&($result->argument_at(0)->type===$catalog->integer_literal_type)&&($result->argument_at(1)->value==='7');}
                    if($mode==='constant'){$ok=$ok&&($result->argument_at(1)===$state->constants[$constant->symbol_id]);}
                    if($mode==='family'){$ok=$ok&&($result->argument_count()===2)&&($result->argument_at(1)->type===$catalog->find_type('uint8',''));}
                    if(($mode==='storage')||($mode==='storage_function')){$ok=$ok&&($result->argument_count()===1)&&($result->argument_at(0)->type===$int32);}
                }
                if(($mode==='mixed_result')||($mode==='duplicate_prerequisite')||($mode==='invalid_prerequisite')){
                    $args /** vector<\instantiate\Template_Argument> */ = [];$missing /** vector<int> */ = [1,1];
                    if($mode==='mixed_result'){$args=$result->arguments();$missing=[1];}if($mode==='invalid_prerequisite'){$missing=[0];}
                    $bad=false;try{$invalid=new \instantiate\Application_Result($task,$args,$missing);}catch(\LogicException $error){$bad=true;}$ok=$ok&&$bad;
                }
                if(($mode==='materialize')||($mode==='stale_materialization')||($mode==='candidate_isolation')||($mode==='owned_element')||($mode==='floating_element')||($mode==='byte_element')){
                    $family=$providers[0]->storage_family();$definition=\resolve_types\Storage_Definitions::materialize($family,$int32,$types);$storage=$definition->element_storage;if($storage===null){throw new \LogicException('Missing element identity');}
                    $ok=$ok&&($storage->family===$family)&&($storage->element===$int32)&&($types->definition_for_type($storage->element_type)===$int32)&&($definition->name==='["p","storage","","int32"]');
                    if($mode==='materialize'){$ok=$ok&&(\resolve_types\Storage_Definitions::materialize($family,$int32,$types)===$definition);}
                    if($mode==='owned_element'){$ok=$ok&&!\resolve_types\Storage_Definitions::eligible($definition);}
                    if($mode==='stale_materialization'){$fresh=Fixtures::providers($catalog);$bad=false;try{\resolve_types\Storage_Definitions::materialize($fresh[0]->storage_family(),$int32,$types);}catch(\LogicException $error){$bad=true;}$ok=$ok&&$bad;}
                    if($mode==='candidate_isolation'){$fork=$types->fork();$parent_count=$types->type_count();$small=$catalog->find_type('uint8','');if($small===null){throw new \LogicException('Missing uint8');}\resolve_types\Storage_Definitions::materialize($family,$small,$fork);$ok=$ok&&($types->type_count()===$parent_count)&&($fork->type_count()>$parent_count);}
                    if(($mode==='floating_element')||($mode==='byte_element')){$shape=\type_model\Representation::byte_span();if($mode==='floating_element'){$shape=\type_model\Representation::floating('ieee_binary64');}$bad_type=new \type_model\Named_Definition('other','',$shape,$int32->lifetime,null,'',false,false,false);$ok=$ok&&!\resolve_types\Storage_Definitions::eligible($bad_type);}
                }
            }catch(\RuntimeException $error){$rejected=true;$diagnostic=$annotations->diagnostic();if($diagnostic===null){throw $error;}$ok=($expected_error!=='')&&(q_strpos($diagnostic->reason,$expected_error)!==false)&&($diagnostic->path==='/applications.phs')&&($diagnostic->length>0);}
            if($expected_error!==''){$ok=$ok&&$rejected;}else{$ok=$ok&&!$rejected;}
            if(!$ok){throw new \LogicException('Application argument case failed: '.$mode);}echo "true\n";
        }
    }
}
