<?php
declare(strict_types=1);
namespace instance_join_test;
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
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog,
        public readonly \type_model\Type_Store $types, public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names, public readonly \instantiate\Instance_Store $store,
        public readonly \instantiate\Instance_Set $initial, public readonly \instantiate\Bindings $reader,
        public readonly \resolve_types\Definition_View $definitions, public readonly \instantiate\Instance_Context $context,
        public array $tasks /** vector<\instantiate\Application_Task> */, public array $results /** vector<\instantiate\Application_Result> */) {}
    public static function prepare(string $entry, string $formals, bool $permissions, string $extra): Fixture {
        $tasks /** vector<\instantiate\Application_Task> */ = []; $results /** vector<\instantiate\Application_Result> */ = [];
        $catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $source=new \read_sources\Source_Buffer();$source->path='/join.phs';
        $source->content='struct Source { public int32 $value; } struct Other { public int32 $value; } template<'.$formals.'> struct Target { public T $value; } '.$extra.' '.$entry.' return 0;';
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($file->error_reason);}
        $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
        $symbols=\collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,Fixtures::providers($catalog))->current;
        $update=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);if(!$update->valid()){throw new \LogicException($update->error_reason);}$names=$update->result();
        $empty /** vector<\check_templates\Definition_Result> */ = [];$checks=new \check_templates\Template_Set($empty,0);
        if($permissions){$checks=\check_templates\Template_Checker::check($symbols,$names,$catalog,$checks,false)->result();}
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
        $initial=new \instantiate\Instance_Set(new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage)),$checks);
        $store=$initial->candidate();$definitions=new \resolve_types\Definition_View($catalog,$types);
        $reader=new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,$definitions),$catalog,$store->view());
        $owner=$symbols->symbol_by_id($symbols->entry_symbol_id('/join.phs'));$context=\instantiate\Instance_Context::ordinary($owner);
        $binding=$names->for_symbol($owner->symbol_id);if($binding===null){throw new \LogicException('Missing fixture bindings');}
        for($i=0;$i<$binding->applications_count();$i++){$task=new \instantiate\Application_Task($context,$binding->applications_at($i));$tasks[]=$task;if($permissions){$results[]=\instantiate\Application_Worker::run($task,$reader);}}
        return new Fixture($catalog,$types,$symbols,$names,$store,$initial,$reader,$definitions,$context,$tasks,$results);
    }
    public function join(): \instantiate\Instance_Join {return new \instantiate\Instance_Join($this->store,$this->tasks,$this->types,$this->names,$this->definitions,$this->catalog);}
}
final class Probe {
    public static function storage_family(\collect_symbols\Provider_Declaration $provider): \type_model\Storage_Family {
        if($provider->kind()===\collect_symbols\PROVIDER_STORAGE_FUNCTION){return $provider->storage_function()->family;}
        return $provider->storage_family();
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++){
            $mode=$cases->at($ci)->text();$entry='$a Target<int,7>; $b Target<int,8>;';$formals='typename T,int N';$permissions=true;$failure=false;
            if($mode==='same_instance'){$entry='$a Target<int,7>; $b Target<int,7>;';}
            if($mode==='source_pending'){$entry='$a Target<Source,7>;';}
            if(($mode==='source_subset')||($mode==='wrong_prerequisite')){$entry='$a Target<Source,Other>;';$formals='typename T,typename U';}
            if(($mode==='family_pending')||($mode==='family_partial_prerequisites')){$entry='$a Family<Source,Other>;';}
            if(($mode==='storage_pending')||($mode==='storage_wrong_prerequisite')){$entry='$a Storage<Source>;';}
            if($mode==='storage_type'){$entry='$a Storage<int32>; $b Storage<int32>;';}
            if($mode==='storage_function'){$entry='allocate<int32>();';}
            if(($mode==='family')||($mode==='family_wrong_args')){$entry='$a Family<int,uint8>;';}
            if($mode==='no_permissions'){$permissions=false;$failure=true;}
            $extra='';if(($mode==='concrete_context')||($mode==='unaccepted_context')||($mode==='stale_concrete_context')){$extra='template<typename T> struct Wrapper { public Target<T,7> $inner; }';}
            $f=Fixture::prepare($entry,$formals,$permissions,$extra);$tasks=$f->tasks;$results=$f->results;$types=$f->types;
            $none /** vector<int> */ = [];$empty_args /** vector<\instantiate\Template_Argument> */ = [];
            if($mode==='reverse'){$results=[$f->results[1],$f->results[0]];}
            if($mode==='incomplete'){$results=[$f->results[0]];$failure=true;}
            if($mode==='duplicate_result'){$results=[$f->results[0],$f->results[0]];$failure=true;}
            if($mode==='duplicate_task'){$tasks[]=$tasks[0];$failure=true;}
            if($mode==='forged_task'){$task=new \instantiate\Application_Task($f->context,$tasks[0]->application);$results[0]=new \instantiate\Application_Result($task,$results[0]->arguments(),$none);$failure=true;}
            if($mode==='stale_binding'){$task=new \instantiate\Application_Task($f->context,new \resolve_symbols\Template_Application_Binding($tasks[0]->application->use_node_id,$tasks[0]->application->definition));$tasks[0]=$task;$results[0]=new \instantiate\Application_Result($task,$results[0]->arguments(),$none);$failure=true;}
            if(($mode==='wrong_value')||($mode==='late_invalid')){$index=0;if($mode==='late_invalid'){$index=1;}$args=$results[$index]->arguments();$args[1]=new \instantiate\Template_Argument($f->catalog->integer_literal_type,'9');$results[$index]=new \instantiate\Application_Result($tasks[$index],$args,$none);$failure=true;}
            if(($mode==='wrong_type')||($mode==='value_for_type')){$args=$results[0]->arguments();$type=$f->catalog->find_type('uint8','');if($type===null){throw new \LogicException('Missing uint8');}$args[0]=new \instantiate\Template_Argument($type);if($mode==='value_for_type'){$args[0]=new \instantiate\Template_Argument($f->catalog->integer_literal_type,'7');}$results[0]=new \instantiate\Application_Result($tasks[0],$args,$none);$failure=true;}
            if($mode==='family_wrong_args'){$args=$results[0]->arguments();$args[1]=$args[0];$results[0]=new \instantiate\Application_Result($tasks[0],$args,$none);$failure=true;}
            if($mode==='wrong_count'){$args /** vector<\instantiate\Template_Argument> */ = [$results[0]->argument_at(0)];$results[0]=new \instantiate\Application_Result($tasks[0],$args,$none);$failure=true;}
            if($mode==='foreign_lineage'){$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));$failure=true;}
            if($mode==='source_subset'){$tree=$f->context->definition->source_frontend()->tree;$first=$results[0]->prerequisite_at(0);$missing /** vector<int> */ = [(int)$tree->row($first)->next_sibling];$results[0]=new \instantiate\Application_Result($tasks[0],$empty_args,$missing);}
            if(($mode==='wrong_prerequisite')||($mode==='storage_wrong_prerequisite')){$missing /** vector<int> */ = [$tasks[0]->application->use_node_id];$results[0]=new \instantiate\Application_Result($tasks[0],$empty_args,$missing);$failure=true;}
            if($mode==='family_partial_prerequisites'){$missing /** vector<int> */ = [$results[0]->prerequisite_at(0)];$results[0]=new \instantiate\Application_Result($tasks[0],$empty_args,$missing);$failure=true;}
            if($mode==='false_pending'){$tree=$f->context->definition->source_frontend()->tree;$parts=\parse\Syntax_Access::template_application_parts($tree,$tasks[0]->application->use_node_id);$missing /** vector<int> */ = [(int)$parts->first_argument_id];$results[0]=new \instantiate\Application_Result($tasks[0],$empty_args,$missing);$failure=true;}
            if(($mode==='concrete_context')||($mode==='unaccepted_context')||($mode==='stale_concrete_context')){
                $wrapper=$f->symbols->symbol_by_id($f->symbols->find_symbol('Wrapper',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));
                $arguments /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($f->catalog->integer_literal_type)];
                $id=$f->store->allocate($types,$wrapper->symbol_id,$arguments);$context=new \instantiate\Instance_Context($wrapper,$id,$arguments);
                if($mode!=='unaccepted_context'){$f->store->accept($context);}
                if($mode==='stale_concrete_context'){$context=new \instantiate\Instance_Context($wrapper,$id,$arguments);}
                $binding=$f->names->for_symbol($wrapper->symbol_id);if($binding===null){throw new \LogicException('Missing wrapper binding');}
                $task=new \instantiate\Application_Task($context,$binding->applications_at(0));$tasks=[$task];$results=[\instantiate\Application_Worker::run($task,$f->reader)];
                if($mode!=='concrete_context'){$failure=true;}
            }
            $before_types=$types->type_count();$before_ids=$f->store->next_id();$before_size=$f->store->size();
            $join=new \instantiate\Instance_Join($f->store,$tasks,$types,$f->names,$f->definitions,$f->catalog);$rejected=false;$ok=true;
            try{$out=$join->join($results);$ok=$out===$f->store;}catch(\LogicException $error){$rejected=true;}
            if($failure){$ok=$rejected&&($types->type_count()===$before_types)&&($f->store->next_id()===$before_ids)&&($f->store->size()===$before_size);}
            else{
                $ok=$ok&&!$rejected&&($f->initial->size()===0)&&($f->initial->next_id()===1);
                $pending=($mode==='source_pending')||($mode==='source_subset')||($mode==='family_pending')||($mode==='storage_pending');
                if($pending){$ok=$ok&&($f->store->size()===0)&&($f->store->next_id()===1)&&($types->type_count()===$before_types);}
                else{
                    $first=$f->store->application($tasks[0]->context,$tasks[0]->application->use_node_id);if($first===null){throw new \LogicException('Missing accepted instance');}
                    $expected_id=1;if($mode==='concrete_context'){$expected_id=2;}
                    $ok=$ok&&($first->instance_id===$expected_id)&&($first->definition===$tasks[0]->application->definition);
                    if(q_count($tasks)===2){$second=$f->store->application($tasks[1]->context,$tasks[1]->application->use_node_id);if($second===null){throw new \LogicException('Missing second instance');}
                        if(($mode==='same_instance')||($mode==='storage_type')){$ok=$ok&&($second===$first)&&($f->store->size()===1);}
                        else{$ok=$ok&&($second->instance_id===2)&&($first->argument_at(1)->value==='7')&&($second->argument_at(1)->value==='8');}}
                    if(($mode==='storage_type')||($mode==='storage_function')){$family=Probe::storage_family($first->definition->provider());$element=$first->argument_at(0)->type;$known=$types->find_type(\resolve_types\Storage_Definitions::name($family,$element),string_byte_from_int(0).'element_storage');$ok=$ok&&($known!==0);if($mode==='storage_type'){$ok=$ok&&($f->store->instance_type($first->instance_id)===$types->definition_for_type($known));}else{$ok=$ok&&($f->store->instance_type($first->instance_id)===null);}}
                    if($mode==='repeat'){$introduced=$f->store->take_introduced();$watermark=$f->store->next_id();$join->join($results);$again=$f->store->take_introduced();$ok=$ok&&(q_count($introduced)===2)&&(q_count($again)===0)&&($f->store->next_id()===$watermark)&&($f->store->application($tasks[0]->context,$tasks[0]->application->use_node_id)===$first);}
                    if($mode==='reuse_previous'){$bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];$previous=$f->store->snapshot($bindings);$state=$previous->export_state();$empty_contexts /** hash<\instantiate\Instance_Context,int> */ = [];$empty_uses /** hash<int> */ = [];$state->contexts=$empty_contexts;$state->uses=$empty_uses;$candidate=new \instantiate\Instance_Store($state,$previous->template_checks());$reuse=new \instantiate\Instance_Join($candidate,$tasks,$types,$f->names,$f->definitions,$f->catalog,$previous);$reuse->join($results);$ok=$ok&&($candidate->application($tasks[0]->context,$tasks[0]->application->use_node_id)===$first)&&($candidate->next_id()===$previous->next_id())&&($previous->size()===2);}
                }
            }
            if(!$ok){throw new \LogicException('Instance join case failed: '.$mode);}echo "true\n";
        }
    }
}
