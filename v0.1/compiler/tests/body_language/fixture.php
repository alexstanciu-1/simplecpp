<?php
declare(strict_types=1);
namespace body_language_fixture;
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog, public readonly \type_model\Type_Store $types,
        public readonly \collect_symbols\Symbol_Store $symbols, public readonly \instantiate\Instance_Store $instances,
        public readonly \instantiate\Bindings $reader, public readonly \resolve_types\Entry_Contract $entry,
        public readonly \resolve_types\Callable_Input $input, public array $prepared /** hash<\type_model\Runtime_Callable,int> */) {}
    private static function result_abi(string $result, int $bits): ?\type_model\Runtime_Abi_Position {
        if ($result === 'void') { return null; }
        return \type_model\Runtime_Abi_Position::integer($bits,\type_model\ABI_EXTENSION_NONE);
    }
    private static function binding(int $role): ?int {
        if ($role < 0) { return null; }
        return $role;
    }
    private static function callable(string $name, string $parameter, string $result, int $passing, int $role, bool $default_literal): \collect_symbols\Provider_Declaration {
        $parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter(\type_model\Type_Reference::named($parameter,''),$passing)];
        $production = \type_model\RESULT_VALUE;
        if ($result === 'void') { $production = \type_model\RESULT_NONE; }
        $signature = new \type_model\Semantic_Signature($parameters,new \type_model\Semantic_Result(\type_model\Type_Reference::named($result,''),$production));
        $parameter_bits = $parameter === 'uint8' ? 8 : 64;
        $result_bits = $result === 'uint8' ? 8 : 64;
        $parameter_abi = \type_model\Runtime_Abi_Position::integer($parameter_bits,\type_model\ABI_EXTENSION_NONE);
        if ($passing === \type_model\PASS_BYTE_SPAN) { $parameter_abi = \type_model\Runtime_Abi_Position::byte_span($parameter_abi); }
        $positions /** vector<\type_model\Runtime_Abi_Position> */ = [$parameter_abi];
        $result_abi = Fixture::result_abi($result,$result_bits);
        $binding = Fixture::binding($role);
        $call = new \type_model\Runtime_Callable('language-fixture',$name,$name,'',$signature,new \type_model\Runtime_Callable_Abi($name,'ccc',$result_abi,$positions),$binding,$default_literal);
        return new \collect_symbols\Provider_Declaration($call,null,null,null,null);
    }
    public static function prepare(string $program, bool $default_literal): Fixture {
        $base = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i = 0; $i < $base->size(); $i++) { $definitions[] = $base->definition_at($i); }
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        $definitions[] = new \type_model\Named_Definition('Bytes','',\type_model\Representation::byte_span(),new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$ops),null,'',false,false,false);
        $catalog = new \type_model\Type_Catalog($base->provider,'body-language-catalog',$base->representation_scope,$definitions,$base->integer_literal_type,$base->entry_return_type,$base->boolean_type);
        $providers /** vector<\collect_symbols\Provider_Declaration> */ = [
            Fixture::callable('literal_int','Bytes','int',\type_model\PASS_BYTE_SPAN,\type_model\BINDING_BYTE_LITERAL,$default_literal),
            Fixture::callable('literal_byte','Bytes','uint8',\type_model\PASS_BYTE_SPAN,\type_model\BINDING_BYTE_LITERAL,false),
            Fixture::callable('print_int','int','void',\type_model\PASS_VALUE,\type_model\BINDING_ECHO,false),
            Fixture::callable('print_byte','uint8','void',\type_model\PASS_VALUE,\type_model\BINDING_ECHO,false),
            Fixture::callable('span_size','Bytes','int',\type_model\PASS_BYTE_SPAN,-1,false)
        ];
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
