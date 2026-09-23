<?php
declare(strict_types=1);
namespace body_managed_fixture;
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog, public readonly \type_model\Type_Store $types,
        public readonly \collect_symbols\Symbol_Store $symbols, public readonly \instantiate\Instance_Store $instances,
        public readonly \instantiate\Bindings $reader, public readonly \resolve_types\Entry_Contract $entry,
        public readonly \resolve_types\Callable_Input $input, public array $prepared /** hash<\type_model\Runtime_Callable,int> */) {}
    private static function definition(string $name, bool $copy, bool $move, bool $construct, bool $assign): \type_model\Named_Definition {
        $policy = new \type_model\Lifetime_Policy(); $policy->cleanup = \type_model\CLEANUP_DESTROY;
        $roles /** vector<int> */ = [\type_model\LIFECYCLE_DESTROY];
        if ($copy) { $policy->copy = \type_model\COPY_CONSTRUCT; $roles[] = \type_model\LIFECYCLE_COPY; }
        if ($move) { $policy->expiring = \type_model\EXPIRING_CONSTRUCT; $roles[] = \type_model\LIFECYCLE_MOVE; }
        else { if ($copy) { $policy->expiring = \type_model\EXPIRING_COPY; } }
        if ($construct) { $policy->construction = \type_model\CONSTRUCTION_CONSTRUCT; $roles[] = \type_model\LIFECYCLE_DEFAULT; }
        if ($assign) { $policy->assignment = \type_model\ASSIGNMENT_CALL; $roles[] = \type_model\LIFECYCLE_ASSIGN; }
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        foreach ($roles as $role) { $operations[] = \type_model\Lifecycle_Operation::runtime('managed-proof',$name . $role,$name . $role,'ccc',$role); }
        return new \type_model\Named_Definition($name,'',\type_model\Representation::opaque(16,8),new \type_model\Lifetime_Contract($policy,$operations),null,'',false,false,true);
    }
    private static function provider(): \collect_symbols\Provider_Declaration {
        $parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $signature = new \type_model\Semantic_Signature($parameters,new \type_model\Semantic_Result(\type_model\Type_Reference::named('Managed',''),\type_model\RESULT_OWNED));
        $positions /** vector<\type_model\Runtime_Abi_Position> */ = [];
        $abi = new \type_model\Runtime_Callable_Abi('make_managed','ccc',null,$positions,\type_model\ABI_RESULT_CALLER_STORAGE);
        return new \collect_symbols\Provider_Declaration(new \type_model\Runtime_Callable('managed-proof','make','make_managed','',$signature,$abi),null,null,null,null);
    }
    public static function prepare(string $program): Fixture {
        $base = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i = 0; $i < $base->size(); $i++) { $definitions[] = $base->definition_at($i); }
        $definitions[] = Fixture::definition('Managed',true,true,true,true);
        $definitions[] = Fixture::definition('MoveOnly',false,true,true,false);
        $definitions[] = Fixture::definition('CopyOnly',true,false,true,false);
        $definitions[] = Fixture::definition('Pinned',false,false,true,false);
        $definitions[] = Fixture::definition('NoDefault',true,true,false,true);
        $catalog = new \type_model\Type_Catalog($base->provider,'managed-catalog',$base->representation_scope,$definitions,$base->integer_literal_type,$base->entry_return_type,$base->boolean_type);
        $providers /** vector<\collect_symbols\Provider_Declaration> */ = [Fixture::provider()];
        $source = new \read_sources\Source_Buffer(); $source->path = '/signatures.phs'; $source->content = $program;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0;
        $symbols = \collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,$providers)->current;
        $update = \resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);
        if (!$update->valid()) { throw new \LogicException($update->error_reason); }
        $names = $update->result(); $empty_checks /** vector<\check_templates\Definition_Result> */ = [];
        $checks = \check_templates\Template_Checker::check($symbols,$names,$catalog,new \check_templates\Template_Set($empty_checks,0),false)->result();
        $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('c',$catalog->content_key,$catalog->representation_scope));
        $instances = new \instantiate\Instance_Store(new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage)),$checks);
        $entry = new \resolve_types\Entry_Contract($symbols->symbol_by_id($symbols->entry_symbol_id('/signatures.phs')),$catalog->entry_return_type);
        $input = new \resolve_types\Callable_Input($symbols->symbol_by_id($symbols->find_symbol('f',\collect_symbols\SYMBOL_FUNCTION,0,'')));
        $reader = new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,new \resolve_types\Definition_View($catalog,$types)),$catalog,$instances->view());
        $prepared /** hash<\type_model\Runtime_Callable,int> */ = [];
        return new Fixture($catalog,$types,$symbols,$instances,$reader,$entry,$input,$prepared);
    }
}
