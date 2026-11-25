<?php
declare(strict_types=1);
namespace record_preparation_test;
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog, public readonly \type_model\Type_Store $types,
        public readonly \collect_symbols\Symbol_Store $symbols, public readonly \resolve_symbols\Resolution_Set $names,
        public readonly \instantiate\Instance_Store $instances, public readonly \instantiate\Bindings $reader,
        public array $tasks /** vector<\resolve_types\Record_Task> */) {}
    public static function prepare(string $mode): Fixture {
        $catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));$int32=$catalog->find_type('int32','');if($int32===null){throw new \LogicException('Missing int32');}
        $fields='public int32 $value;';$methods='';$extra='';$prefix='';$entry='';
        if(($mode==='array')||($mode==='wrong_extent')){$fields='public int32 $items[3];';}
        if($mode==='zero_extent'){$fields='public int32 $items[0];';}
        if($mode==='max_extent'){$fields='public int32 $items[9223372036854775807];';}
        if($mode==='overflow_extent'){$fields='public int32 $items[9223372036854775808];';}
        if($mode==='duplicate_field'){$fields='public int32 $value; public int32 $value;';}
        if($mode==='bad_field'){$fields='public int $value;';}
        if($mode==='empty'){$fields='';}
        if($mode==='constant_extent'){$prefix='const N=3;';$fields='public int32 $items[N];';}
        if(($mode==='lifecycle')||($mode==='wrong_bodies')){$methods='public function __construct(): void {} public function __destruct(): void {} public function __copy_construct(const Point &$source): void {} public function __copy_assign(const Point &$source): void {}';}
        if($mode==='ctor_parameters'){$methods='public function __construct($x int32): void {}';}
        if($mode==='copy_missing'){$methods='public function __copy_construct(): void {}';}
        if($mode==='copy_mutable'){$methods='public function __copy_construct(Point &$source): void {}';}
        if($mode==='copy_extra'){$methods='public function __copy_assign(const Point &$source, $x int32): void {}';}
        if($mode==='const_receiver'){$methods='public const function __destruct(): void {}';}
        if(($mode==='two_records')||($mode==='reverse')||($mode==='late_invalid')||($mode==='incomplete')||($mode==='duplicate_result')){$extra='struct Other { public uint8 $small; }';}
        $template_mode=($mode==='template')||($mode==='template_extent')||($mode==='stale_instance');
        $source=new \read_sources\Source_Buffer();$source->path='/records.phs';$source->content=$prefix.' struct Point { '.$fields.' '.$methods.' } '.$extra.' return 0;';
        if($template_mode){$template_fields='public T $value;';if($mode==='template_extent'){$template_fields='public int32 $items[N];';}$source->content='template<typename T,int N> struct Point { '.$template_fields.' } $value Point<int32,3>; return 0;';}
        if(($mode==='provider')||($mode==='provider_clone')||($mode==='provider_collision')){
            $name='Provided';if($mode==='provider_collision'){$name='Point';}
            $provider_fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',\type_model\Field_Type::named($int32),true)];
            $provided=new \type_model\Record_Declaration($name,'',$provider_fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
            $rows /** vector<\type_model\Named_Definition> */ = [];for($i=0;$i<$catalog->size();$i++){$rows[]=$catalog->definition_at($i);}
            $records /** vector<\type_model\Record_Declaration> */ = [$provided];$catalog=new \type_model\Type_Catalog($catalog->provider,$catalog->content_key,$catalog->representation_scope,$rows,$catalog->integer_literal_type,$catalog->entry_return_type,$catalog->boolean_type,$records);
        }
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($mode.': '.$file->error_reason);}
        $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;$providers /** vector<\collect_symbols\Provider_Declaration> */ = [];
        $symbols=\collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,$providers)->current;
        $update=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);if(!$update->valid()){throw new \LogicException($mode.': '.$update->error_reason);}$names=$update->result();
        $checks_empty /** vector<\check_templates\Definition_Result> */ = [];$checks=\check_templates\Template_Checker::check($symbols,$names,$catalog,new \check_templates\Template_Set($checks_empty,0),false)->result();
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c',$catalog->content_key,'t'));
        $state=new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage));$instances=new \instantiate\Instance_Store($state,$checks);
        $view=new \resolve_types\Definition_View($catalog,$types);$reader=new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,$view),$catalog,$instances->view());
        if($mode==='constant_extent'){$owner=$symbols->symbol_by_id($symbols->find_symbol('N',\collect_symbols\SYMBOL_CONSTANT,0,''));$state->constants[$owner->symbol_id]=\instantiate\Constant_Worker::resolve($owner,$reader);$instances=new \instantiate\Instance_Store($state,$checks);$reader=new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,$view),$catalog,$instances->view());}
        $tasks=\resolve_types\Record_Preparation::select($symbols,$reader,$types,false);
        if($template_mode){
            $entry_owner=$symbols->symbol_by_id($symbols->entry_symbol_id('/records.phs'));$context=\instantiate\Instance_Context::ordinary($entry_owner);$binding=$names->for_symbol($entry_owner->symbol_id);if($binding===null){throw new \LogicException('Missing application binding');}
            $app=new \instantiate\Application_Task($context,$binding->applications_at(0));$apps /** vector<\instantiate\Application_Task> */ = [$app];$results /** vector<\instantiate\Application_Result> */ = [\instantiate\Application_Worker::run($app,$reader)];
            $join=new \instantiate\Instance_Join($instances,$apps,$types,$names,$view,$catalog);$join->join($results);$concrete=$instances->application($context,$app->application->use_node_id);if($concrete===null){throw new \LogicException('Missing concrete record');}
            if($mode==='stale_instance'){$args /** vector<\instantiate\Template_Argument> */ = [$concrete->argument_at(0),$concrete->argument_at(1)];$concrete=new \instantiate\Instance_Context($concrete->definition,$concrete->instance_id,$args);}
            $tasks[]=new \resolve_types\Record_Task($reader,$symbols,$concrete);
        }
        return new Fixture($catalog,$types,$symbols,$names,$instances,$reader,$tasks);
    }
}
final class Probe {
    public static function changed(\type_model\Record_Declaration $record, string $mode): \type_model\Record_Declaration {
        $fields /** vector<\type_model\Field_Declaration> */ = [];
        for($i=0;$i<$record->field_count();$i++){
            $field=$record->field_at($i);$name=$field->name;$type=$field->definition;$writable=$field->writable;
            if($mode==='wrong_field'){$name='forged';}if($mode==='wrong_extent'){$type=\type_model\Field_Type::fixed_array($type->element,4);}if($mode==='readonly_field'){$writable=false;}
            $fields[]=new \type_model\Field_Declaration($name,$type,$writable);
        }
        $name=$record->name;if($mode==='wrong_name'){$name='Wrong';}$constructor=$record->constructor_body;if($mode==='wrong_bodies'){$constructor=0;}
        return new \type_model\Record_Declaration($name,$record->namespace_name,$fields,$record->automatic_lifecycle,$record->layout_policy,$record->native_layout,$constructor,$record->destructor_body,$record->copy_body,$record->assignment_body);
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($ci=0;$ci<$cases->size();$ci++){
            $mode=$cases->at($ci)->text();$f=Fixture::prepare($mode);$tasks=$f->tasks;$results /** vector<\resolve_types\Record_Result> */ = [];$rejected=false;
            $worker_error=($mode==='zero_extent')||($mode==='overflow_extent')||($mode==='duplicate_field')||($mode==='bad_field')||($mode==='empty')||($mode==='ctor_parameters')||($mode==='copy_missing')||($mode==='copy_mutable')||($mode==='copy_extra')||($mode==='const_receiver');
            $before=$f->types->type_count();
            try{foreach($tasks as $task){$results[]=\resolve_types\Record_Preparation::resolve($task);}}catch(\RuntimeException $error){if(!$worker_error){throw $error;}$rejected=true;}
            if($worker_error){$diagnostic=$f->reader->annotations->diagnostic();if(!$rejected){throw new \LogicException('Missing record worker rejection: '.$mode);}if($diagnostic===null){throw new \LogicException('Missing record diagnostic');}if(($diagnostic->path!=='/records.phs')||($diagnostic->length<1)||($f->types->type_count()!==$before)){throw new \LogicException('Invalid record diagnostic/purity');}echo "true\n";continue;}
            if($mode==='max_extent'){if($results[0]->declaration->field_at(0)->definition->extent!==9223372036854775807){throw new \LogicException('Incorrect maximum extent');}echo "true\n";continue;}
            $failure=false;$types=$f->types;
            if($mode==='reverse'){$results=[$results[1],$results[0]];}
            if($mode==='incomplete'){$results=[$results[0]];$failure=true;}
            if($mode==='duplicate_result'){$results=[$results[0],$results[0]];$failure=true;}
            if($mode==='duplicate_task'){$tasks[]=$tasks[0];$failure=true;}
            if(($mode==='wrong_field')||($mode==='wrong_extent')||($mode==='readonly_field')||($mode==='wrong_name')||($mode==='wrong_bodies')){$results[0]=new \resolve_types\Record_Result($tasks[0],Probe::changed($results[0]->declaration,$mode));$failure=true;}
            if($mode==='late_invalid'){$results[1]=new \resolve_types\Record_Result($tasks[1],Probe::changed($results[1]->declaration,'wrong_field'));$failure=true;}
            if($mode==='provider_clone'){$last=q_count($results)-1;$results[$last]=new \resolve_types\Record_Result($tasks[$last],Probe::changed($results[$last]->declaration,'clone'));$failure=true;}
            if(($mode==='provider_collision')||($mode==='stale_instance')){$failure=true;}
            if($mode==='wrong_catalog'){$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','wrong','t'));$failure=true;}
            if($mode==='forged_task'){$task=new \resolve_types\Record_Task($f->reader,$f->symbols,$tasks[0]->context);$results[0]=new \resolve_types\Record_Result($task,$results[0]->declaration);$failure=true;}
            $before=$types->type_count();$join=new \resolve_types\Record_Join($types,$tasks,$f->symbols);$rejected=false;$ok=true;
            try{$out=$join->join($results);$ok=$out===$types;}catch(\LogicException $error){$rejected=true;}catch(\RuntimeException $error){$rejected=true;}
            if($failure){$ok=$rejected&&($types->type_count()===$before);}
            else{
                $ok=$ok&&!$rejected;
                foreach($tasks as $task){$ok=$ok&&($types->find_type($task->name(),$task->namespace_name())!==0);}
                if(($mode==='array')||($mode==='constant_extent')||($mode==='template_extent')){$ok=$ok&&($results[0]->declaration->field_at(0)->definition->extent===3);}
                if($mode==='lifecycle'){$r=$results[0]->declaration;$owner=$tasks[0]->context;if($owner===null){throw new \LogicException('Missing lifecycle owner');}$oid=$owner->definition->symbol_id;$ok=$ok&&($r->constructor_body===$f->symbols->find_symbol('__construct',\collect_symbols\SYMBOL_FUNCTION,$oid,''))&&($r->destructor_body===$f->symbols->find_symbol('__destruct',\collect_symbols\SYMBOL_FUNCTION,$oid,''))&&($r->copy_body===$f->symbols->find_symbol('__copy_construct',\collect_symbols\SYMBOL_FUNCTION,$oid,''))&&($r->assignment_body===$f->symbols->find_symbol('__copy_assign',\collect_symbols\SYMBOL_FUNCTION,$oid,''));}
                if($mode==='selection'){$remaining=\resolve_types\Record_Preparation::select($f->symbols,$f->reader,$types,false);$full=\resolve_types\Record_Preparation::select($f->symbols,$f->reader,$types,true);$ok=$ok&&(q_count($remaining)===0)&&(q_count($full)===q_count($tasks));}
            }
            if(!$ok){throw new \LogicException('Record preparation case failed: '.$mode);}echo "true\n";
        }
    }
}
