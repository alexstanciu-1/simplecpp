<?php
declare(strict_types=1);
namespace construction_fixture;
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog, public readonly \type_model\Type_Store $types,
        public readonly \collect_symbols\Symbol_Store $symbols, public readonly \instantiate\Instance_Store $instances,
        public readonly \instantiate\Bindings $reader, public readonly \resolve_types\Entry_Contract $entry,
        public readonly \resolve_types\Callable_Input $input, public array $prepared /** hash<\type_model\Runtime_Callable,int> */) {}
    public static function callable(string $parameter, string $result, bool $borrow): \type_model\Runtime_Callable {
        $passing=\type_model\PASS_VALUE;$abi_parameter=\type_model\Runtime_Abi_Position::integer(32,\type_model\ABI_EXTENSION_NONE);
        if($borrow){$passing=\type_model\PASS_BORROW_CONST;$abi_parameter=\type_model\Runtime_Abi_Position::borrow(false);}
        $params /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter(\type_model\Type_Reference::named($parameter,''),$passing)];
        $result_contract=new \type_model\Semantic_Result(\type_model\Type_Reference::named($result,''),\type_model\RESULT_VALUE);
        $signature=new \type_model\Semantic_Signature($params,$result_contract);
        $positions /** vector<\type_model\Runtime_Abi_Position> */ = [$abi_parameter];
        $abi=new \type_model\Runtime_Callable_Abi('runtime_call','ccc',\type_model\Runtime_Abi_Position::integer(32,\type_model\ABI_EXTENSION_NONE),$positions);
        return new \type_model\Runtime_Callable('p','call','external','',$signature,$abi);
    }
    public static function providers(\type_model\Type_Catalog $catalog, bool $missing): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $parameter='int32';if($missing){$parameter='Missing';}
        $out /** vector<\collect_symbols\Provider_Declaration> */ = [new \collect_symbols\Provider_Declaration(Fixture::callable($parameter,'int32',false),null,null,null,null)];
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        $descriptor=new \type_model\Named_Definition('descriptor','',\type_model\Representation::opaque(16,8),new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$ops),null,'',false,false,true);
        $void_type=$catalog->find_type('void','');if($void_type===null){throw new \LogicException('Missing void');}
        $primitives /** hash<\type_model\Storage_Primitive> */ = [];$operations /** hash<string> */ = [];
        for($role=0;$role<6;$role++){$operations[\type_model\Storage_Roles::name($role)]='storage_'.$role;}
        $family=new \type_model\Storage_Family('p','storage',$descriptor,$catalog->integer_literal_type,$void_type,$primitives,$operations,'Storage','');
        $out[]=new \collect_symbols\Provider_Declaration(null,$family,null,null,null);
        for($role=0;$role<6;$role++){$function=new \type_model\Storage_Function($family,$role,'storage_'.$role,'','p','storage_'.$role);$out[]=new \collect_symbols\Provider_Declaration(null,null,$function,null,null);}
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE)];
        $arguments /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::parameter('["p","family"]',0)];$self=\type_model\Type_Reference::family('["p","family"]',$arguments);
        $params /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $sig=new \type_model\Semantic_Signature($params,new \type_model\Semantic_Result(\type_model\Type_Reference::named('int32',''),\type_model\RESULT_VALUE));
        $requirements /** vector<\type_model\Capability_Requirement> */ = [];$effects /** vector<\type_model\Element_Effect> */ = [];
        $operation=new \type_model\Family_Operation('read',$sig,$requirements,$effects,0,\type_model\Type_Reference::named('read',''));
        $family_ops /** hash<\type_model\Family_Operation> */ = [];$family_ops['read']=$operation;$life /** hash<string> */ = [];$mapping /** hash<\type_model\Type_Reference> */ = [];
        $decl=new \type_model\Family_Declaration(new \type_model\Family_Definition('p','family',$parameters,$family_ops,$life,\type_model\Type_Reference::named('Family','')),$mapping);
        $out[]=new \collect_symbols\Provider_Declaration(null,null,null,$decl,null);$out[]=new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($decl,$operation));return $out;
    }
    public static function prepare(string $mode, int $role): Fixture {
        $catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));$int32=$catalog->find_type('int32','');if($int32===null){throw new \LogicException('Missing int32');}
        $parameters='$x int32, $y uint8';$return_type='int32';$methods='public function get(): int32 { return $this->value; } public const function read(): int32 { return $this->value; }';
        if($mode==='void_parameter'){$parameters='$x void';}if($mode==='scalar_borrow'){$parameters='const int32 &$x';}if($mode==='record_value'){$parameters='$x Point';}
        if(($mode==='const_borrow')||($mode==='managed_const')){$parameters='const Point &$x';}if(($mode==='mutable_borrow')||($mode==='managed_mutable')){$parameters='Point &$x';}
        $managed=($mode==='managed_const')||($mode==='managed_mutable');if($managed){$methods.=' public function __destruct(): void {}';}
        if(($mode==='constructor')||($mode==='bad_lifecycle_return')){$life_return='void';if($mode==='bad_lifecycle_return'){$life_return='int32';}$methods.=' public function __construct(): '.$life_return.' {}';}
        if(($mode==='copy')||($mode==='bad_copy_type')){$source_type='Point';if($mode==='bad_copy_type'){$source_type='Other';}$methods.=' public function __copy_construct(const '.$source_type.' &$source): void {}';}
        $source=new \read_sources\Source_Buffer();$source->path='/signatures.phs';$source->content='struct Unready { public int32 $x; } struct Point { public int32 $value; '.$methods.' } struct Other { public int32 $value; } function f('.$parameters.'): '.$return_type.' { new int32(); new uint32(); new Point(); new Unready(); return 0; } template<typename T> function identity($x T): T { return $x; } return 0;';
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($file->error_reason);}$files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
        $symbols=\collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,Fixture::providers($catalog,$mode==='unresolved_provider'))->current;
        $update=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);if(!$update->valid()){throw new \LogicException($update->error_reason);}$names=$update->result();
        $empty_checks /** vector<\check_templates\Definition_Result> */ = [];$checks=\check_templates\Template_Checker::check($symbols,$names,$catalog,new \check_templates\Template_Set($empty_checks,0),false)->result();
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c',$catalog->content_key,$catalog->representation_scope));$instances=new \instantiate\Instance_Store(new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage)),$checks);
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',\type_model\Field_Type::named($int32),true)];
        $point=$symbols->symbol_by_id($symbols->find_symbol('Point',\collect_symbols\SYMBOL_STRUCT,0,''));$destructor=0;if($managed){$destructor=$symbols->find_symbol('__destruct',\collect_symbols\SYMBOL_FUNCTION,$point->symbol_id,'');}
        $rid=\resolve_types\Record_Definitions::materialize($types,new \type_model\Record_Declaration('Point','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,$destructor,0,0));$receiver=$types->definition_for_type($rid);
        \resolve_types\Record_Definitions::materialize($types,new \type_model\Record_Declaration('Other','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0));
        $entry=new \resolve_types\Entry_Contract($symbols->symbol_by_id($symbols->entry_symbol_id('/signatures.phs')),$catalog->entry_return_type);
        $input=new \resolve_types\Callable_Input($symbols->symbol_by_id($symbols->find_symbol('f',\collect_symbols\SYMBOL_FUNCTION,0,'')));$prepared /** hash<\type_model\Runtime_Callable,int> */ = [];
        if($mode==='entry'){$input=new \resolve_types\Callable_Input($entry->symbol);}
        if(($mode==='provider')||($mode==='unresolved_provider')){$input=new \resolve_types\Callable_Input($symbols->symbol_by_id($symbols->find_symbol('external',\collect_symbols\SYMBOL_FUNCTION,0,'')));}
        if(($mode==='record_nonparticipant')||($mode==='mismatched_input')){$input=new \resolve_types\Callable_Input($point);}
        if(($mode==='method')||($mode==='const_method')||($mode==='constructor')||($mode==='bad_lifecycle_return')||($mode==='copy')||($mode==='bad_copy_type')||($mode==='uninstantiated_method')){
            $name='get';if($mode==='const_method'){$name='read';}if(($mode==='constructor')||($mode==='bad_lifecycle_return')){$name='__construct';}if(($mode==='copy')||($mode==='bad_copy_type')){$name='__copy_construct';}
            $owner=$symbols->symbol_by_id($symbols->find_symbol($name,\collect_symbols\SYMBOL_FUNCTION,$point->symbol_id,''));$args /** vector<\instantiate\Template_Argument> */ = [];$id=$instances->allocate($types,$owner->symbol_id,$args);$instance=new \instantiate\Instance_Context($owner,$id,$args,$receiver);$instances->accept($instance);$input=new \resolve_types\Callable_Input($owner,$instance);if($mode==='uninstantiated_method'){$input=new \resolve_types\Callable_Input($owner);}
        }
        if(($mode==='template')||($mode==='stale_instance')||($mode==='uninstantiated_template')){$owner=$symbols->symbol_by_id($symbols->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''));$args /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($int32)];$id=$instances->allocate($types,$owner->symbol_id,$args);$instance=new \instantiate\Instance_Context($owner,$id,$args);$instances->accept($instance);if($mode==='stale_instance'){$instance=new \instantiate\Instance_Context($owner,$id,$args);}$input=new \resolve_types\Callable_Input($owner,$instance);if($mode==='uninstantiated_template'){$input=new \resolve_types\Callable_Input($owner);}}
        if(($mode==='storage')||($mode==='storage_record_push')||($mode==='missing_storage')||($mode==='storage_forged')){$owner=$symbols->symbol_by_id($symbols->find_symbol('storage_'.$role,\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''));$element=$int32;if($mode==='storage_record_push'){$element=$receiver;}$args /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($element)];$id=$instances->allocate($types,$owner->symbol_id,$args);$instance=new \instantiate\Instance_Context($owner,$id,$args);$instances->accept($instance);if($mode!=='missing_storage'){\resolve_types\Storage_Definitions::materialize($owner->provider()->storage_function()->family,$element,$types);}$input=new \resolve_types\Callable_Input($owner,$instance);}
        if(($mode==='family')||($mode==='missing_family')){$family=$symbols->symbol_by_id($symbols->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));$owner=$symbols->symbol_by_id($symbols->find_symbol('read',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,$family->symbol_id,''));$args /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($int32)];$id=$instances->allocate($types,$owner->symbol_id,$args);$instance=new \instantiate\Instance_Context($owner,$id,$args,$receiver);$instances->accept($instance);$input=new \resolve_types\Callable_Input($owner,$instance);if($mode==='family'){$prepared[$input->callable_id]=Fixture::callable('Point','int32',true);}}
        $reader=new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,new \resolve_types\Definition_View($catalog,$types)),$catalog,$instances->view());
        return new Fixture($catalog,$types,$symbols,$instances,$reader,$entry,$input,$prepared);
    }
}
