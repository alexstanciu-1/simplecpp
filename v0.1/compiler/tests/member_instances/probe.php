<?php
declare(strict_types=1);
namespace member_instances_test;
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog, public readonly \type_model\Type_Store $types,
        public readonly \collect_symbols\Symbol_Store $symbols, public readonly \resolve_symbols\Resolution_Set $names,
        public readonly \instantiate\Instance_Store $store, public readonly \instantiate\Instance_Set $initial,
        public readonly \instantiate\Bindings $reader, public readonly \resolve_types\Definition_View $definitions,
        public readonly \instantiate\Instance_Context $context, public array $tasks /** vector<\instantiate\Member_Task> */) {}
    public static function providers(): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE)];
        $arguments /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::parameter('["p","family"]',0)];
        $self=\type_model\Type_Reference::family('["p","family"]',$arguments);
        $params /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $signature=new \type_model\Semantic_Signature($params,new \type_model\Semantic_Result(\type_model\Type_Reference::named('int32',''),\type_model\RESULT_VALUE));
        $requirements /** vector<\type_model\Capability_Requirement> */ = [];$effects /** vector<\type_model\Element_Effect> */ = [];
        $operation=new \type_model\Family_Operation('get',$signature,$requirements,$effects,0,\type_model\Type_Reference::named('get',''));
        $operations /** hash<\type_model\Family_Operation> */ = [];$operations['get']=$operation;$lifecycle /** hash<string> */ = [];$mappings /** hash<\type_model\Type_Reference> */ = [];
        $family=new \type_model\Family_Declaration(new \type_model\Family_Definition('p','family',$parameters,$operations,$lifecycle,\type_model\Type_Reference::named('Family','')),$mappings);
        $out /** vector<\collect_symbols\Provider_Declaration> */ = [new \collect_symbols\Provider_Declaration(null,null,null,$family,null),new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$operation))];return $out;
    }
    public static function prepare(string $mode): Fixture {
        $catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));$int32=$catalog->find_type('int32','');if($int32===null){throw new \LogicException('Missing int32');}
        $is_template=($mode==='template')||($mode==='template_declaration')||($mode==='wrong_argument')||($mode==='argument_clone');
        $family=$mode==='family';$type='Point';if($is_template){$type='Box<int32>';}if($family){$type='Family<int32>';}
        $entry='$p '.$type.'; $p->get(); $p->get();';if($mode==='unknown'){$entry='$p Point; $p->absent();';}if($mode==='nonrecord'){$entry='$p int32; $p->get();';}if($mode==='lifecycle'){$entry='$p Point; $p->__construct();';}
        $source=new \read_sources\Source_Buffer();$source->path='/members.phs';
        $source->content='struct Point { public int32 $value; public function get(): int32 { return $this->value; } public function other(): int32 { return $this->get(); } public function __construct(): void {} } template<typename T> struct Box { public T $value; public function get(): T { return $this->value; } } function consume($p Point): int32 { return $p->get(); } '.$entry.' return 0;';
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($file->error_reason);}
        $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
        $symbols=\collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,Fixture::providers())->current;
        $update=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);if(!$update->valid()){throw new \LogicException($update->error_reason);}$names=$update->result();
        $empty_checks /** vector<\check_templates\Definition_Result> */ = [];$checks=\check_templates\Template_Checker::check($symbols,$names,$catalog,new \check_templates\Template_Set($empty_checks,0),false)->result();
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));$initial=new \instantiate\Instance_Set(new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage)),$checks);$store=$initial->candidate();
        $definitions=new \resolve_types\Definition_View($catalog,$types);$reader=new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,$definitions),$catalog,$store->view());
        $entry_owner=$symbols->symbol_by_id($symbols->entry_symbol_id('/members.phs'));$context=\instantiate\Instance_Context::ordinary($entry_owner);
        $point=$symbols->symbol_by_id($symbols->find_symbol('Point',\collect_symbols\SYMBOL_STRUCT,0,''));
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',\type_model\Field_Type::named($int32),true)];
        $record=new \type_model\Record_Declaration('Point','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
        $point_type=$int32;
        if(($mode!=='pending')&&($mode!=='declaration_pending')){$id=\resolve_types\Record_Definitions::materialize($types,$record);$point_type=$types->definition_for_type($id);}
        if($is_template||$family){
            $binding=$names->for_symbol($entry_owner->symbol_id);if($binding===null){throw new \LogicException('Missing application');}
            $task=new \instantiate\Application_Task($context,$binding->applications_at(0));$app_tasks /** vector<\instantiate\Application_Task> */ = [$task];$app_results /** vector<\instantiate\Application_Result> */ = [\instantiate\Application_Worker::run($task,$reader)];
            $join=new \instantiate\Instance_Join($store,$app_tasks,$types,$names,$definitions,$catalog);$join->join($app_results);
            $instance=$store->application($context,$task->application->use_node_id);if($instance===null){throw new \LogicException('Missing record instance');}
            $type_record=new \type_model\Record_Declaration($instance->type_name(),$instance->type_namespace(),$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
            $concrete=$int32;
            if($family){$concrete=new \type_model\Named_Definition($instance->type_name(),$instance->type_namespace(),\type_model\Representation::opaque(8,8),$int32->lifetime,null,'',false,false,true);\resolve_types\Type_Cache::materialize($types,$concrete);}
            else{$id=\resolve_types\Record_Definitions::materialize($types,$type_record);$concrete=$types->definition_for_type($id);}
            $store->accept_type($instance,$concrete);
            if($mode==='template_declaration'){$context=$instance;}
        }
        if($mode==='parameter'){$context=\instantiate\Instance_Context::ordinary($symbols->symbol_by_id($symbols->find_symbol('consume',\collect_symbols\SYMBOL_FUNCTION,0,'')));}
        if(($mode==='this')||($mode==='missing_this')){$method=$symbols->symbol_by_id($symbols->find_symbol('other',\collect_symbols\SYMBOL_FUNCTION,$point->symbol_id,''));$empty_args /** vector<\instantiate\Template_Argument> */ = [];$id=$store->allocate($types,$method->symbol_id,$empty_args);$context=new \instantiate\Instance_Context($method,$id,$empty_args);if($mode==='this'){$context=new \instantiate\Instance_Context($method,$id,$empty_args,$point_type);}$store->accept($context);}
        $tasks /** vector<\instantiate\Member_Task> */ = [];
        if(($mode==='declaration')||($mode==='declaration_pending')||($mode==='template_declaration')){
            if($mode!=='template_declaration'){$context=\instantiate\Instance_Context::ordinary($point);}
            $owner=$context->definition;$kind=\collect_symbols\SYMBOL_FUNCTION;if($owner->is_template()){$kind=\collect_symbols\SYMBOL_TEMPLATE_FUNCTION;}
            $method=$symbols->symbol_by_id($symbols->find_symbol('get',$kind,$owner->symbol_id,''));$tasks[]=\instantiate\Member_Task::definition($context,$method);
        }else{
            $binding=$names->for_symbol($context->definition->symbol_id);if($binding===null){throw new \LogicException('Missing member bindings');}
            for($i=0;$i<$binding->members_count();$i++){$tasks[]=\instantiate\Member_Task::occurrence($context,$binding->members_at($i));}
        }
        return new Fixture($catalog,$types,$symbols,$names,$store,$initial,$reader,$definitions,$context,$tasks);
    }
    public function join(): \instantiate\Member_Join {return new \instantiate\Member_Join($this->store,$this->tasks,$this->types,$this->names,$this->definitions,$this->catalog,$this->symbols);}
}
final class Probe {
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++){
            $mode=$cases->at($ci)->text();$f=Fixture::prepare($mode);$tasks=$f->tasks;$results /** vector<\instantiate\Member_Result> */ = [];
            $worker_error=($mode==='unknown')||($mode==='nonrecord')||($mode==='lifecycle')||($mode==='missing_this');$failed=false;
            try{foreach($tasks as $task){$results[]=\instantiate\Member_Worker::run($task,$f->reader,$f->symbols);}}catch(\RuntimeException $error){if(!$worker_error){throw $error;}$failed=true;}catch(\LogicException $error){if(!$worker_error){throw $error;}$failed=true;}
            if($worker_error){if(!$failed){throw new \LogicException('Missing member worker rejection');}if($mode!=='missing_this'){$diag=$f->reader->annotations->diagnostic();if($diag===null){throw new \LogicException('Missing source diagnostic');}if(($diag->path!=='/members.phs')||($diag->length<1)){throw new \LogicException('Invalid member diagnostic');}}echo "true\n";continue;}
            if($failed){throw new \LogicException('Unexpected worker error');}
            $failure=false;$types=$f->types;
            if($mode==='reverse'){$results=[$results[1],$results[0]];}
            if($mode==='incomplete'){$results=[$results[0]];$failure=true;}
            if($mode==='duplicate_result'){$results=[$results[0],$results[0]];$failure=true;}
            if($mode==='duplicate_task'){$tasks[]=$tasks[0];$failure=true;}
            if($mode==='forged_task'){$task=new \instantiate\Member_Task($tasks[0]->context,$tasks[0]->use_node_id,$tasks[0]->receiver_node_id);$results[0]=new \instantiate\Member_Result($task,$results[0]->receiver,$results[0]->definition,$results[0]->arguments());$failure=true;}
            if($mode==='stale_occurrence'){$task=new \instantiate\Member_Task($tasks[0]->context,$tasks[0]->use_node_id,$tasks[0]->receiver_node_id+1);$tasks[0]=$task;$results[0]=new \instantiate\Member_Result($task,$results[0]->receiver,$results[0]->definition,$results[0]->arguments());$failure=true;}
            if(($mode==='wrong_receiver')||($mode==='late_invalid')){$index=0;if($mode==='late_invalid'){$index=1;}$results[$index]=new \instantiate\Member_Result($tasks[$index],$f->catalog->integer_literal_type,$results[$index]->definition,$results[$index]->arguments());$failure=true;}
            if($mode==='wrong_method'){$owner=$f->symbols->symbol_by_id($f->symbols->find_symbol('Point',\collect_symbols\SYMBOL_STRUCT,0,''));$other=$f->symbols->symbol_by_id($f->symbols->find_symbol('other',\collect_symbols\SYMBOL_FUNCTION,$owner->symbol_id,''));$results[0]=new \instantiate\Member_Result($tasks[0],$results[0]->receiver,$other,$results[0]->arguments());$failure=true;}
            if(($mode==='wrong_argument')||($mode==='argument_clone')){$args=$results[0]->arguments();$args[0]=new \instantiate\Template_Argument($args[0]->type);if($mode==='wrong_argument'){$args[0]=new \instantiate\Template_Argument($f->catalog->integer_literal_type);}$results[0]=new \instantiate\Member_Result($tasks[0],$results[0]->receiver,$results[0]->definition,$args);$failure=true;}
            if($mode==='false_pending'){$empty /** vector<\instantiate\Template_Argument> */ = [];$results[0]=new \instantiate\Member_Result($tasks[0],null,null,$empty);$failure=true;}
            if($mode==='foreign_lineage'){$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));$failure=true;}
            $before_count=$f->store->size();$before_ids=$f->store->next_id();$before_types=$types->type_count();$join=new \instantiate\Member_Join($f->store,$tasks,$types,$f->names,$f->definitions,$f->catalog,$f->symbols);$rejected=false;$ok=true;
            try{$out=$join->join($results);$ok=$out===$f->store;}catch(\LogicException $error){$rejected=true;}
            if($failure){$ok=$rejected&&($f->store->size()===$before_count)&&($f->store->next_id()===$before_ids)&&($types->type_count()===$before_types);}
            else{
                $ok=$ok&&!$rejected&&($f->initial->size()===0);
                $pending=($mode==='pending')||($mode==='declaration_pending');
                if($pending){$ok=$ok&&($f->store->size()===$before_count)&&($f->store->next_id()===$before_ids);}
                else{
                    $all=$f->store->contexts();$member=$all[q_count($all)-1];
                    $ok=$ok&&($f->store->size()===$before_count+1)&&($member->definition===$results[0]->definition)&&($member->receiver_type===$results[0]->receiver);
                    foreach($tasks as $task){if($task->declaration===null){$ok=$ok&&($f->store->application($task->context,$task->use_node_id)===$member);}else{$ok=$ok&&($f->store->application($task->context,0)===null);}}
                    if(($mode==='template')||($mode==='template_declaration')||($mode==='family')){$ok=$ok&&($member->argument_count()===1)&&($member->argument_at(0)===$results[0]->argument_at(0));}
                    if($mode==='repeat'){$introduced=$f->store->take_introduced();$join->join($results);$again=$f->store->take_introduced();$ok=$ok&&(q_count($introduced)===1)&&(q_count($again)===0);}
                    if($mode==='reuse_previous'){$bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];$previous=$f->store->snapshot($bindings);$state=$previous->export_state();$empty_contexts /** hash<\instantiate\Instance_Context,int> */ = [];$empty_uses /** hash<int> */ = [];$state->contexts=$empty_contexts;$state->uses=$empty_uses;$candidate=new \instantiate\Instance_Store($state,$previous->template_checks());$reuse=new \instantiate\Member_Join($candidate,$tasks,$types,$f->names,$f->definitions,$f->catalog,$f->symbols,$previous);$reuse->join($results);$ok=$ok&&($candidate->application($tasks[0]->context,$tasks[0]->use_node_id)===$member)&&($candidate->next_id()===$previous->next_id());}
                }
            }
            if(!$ok){throw new \LogicException('Member instance case failed: '.$mode);}echo "true\n";
        }
    }
}
