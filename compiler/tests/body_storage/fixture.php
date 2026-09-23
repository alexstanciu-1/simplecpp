<?php
declare(strict_types=1);
namespace body_storage_fixture;
final class Fixture {
    public function __construct(public readonly \type_model\Type_Catalog $catalog, public readonly \type_model\Type_Store $types,
        public readonly \collect_symbols\Symbol_Store $symbols, public readonly \instantiate\Instance_Store $instances,
        public readonly \instantiate\Bindings $reader, public readonly \resolve_types\Entry_Contract $entry,
        public readonly \resolve_types\Callable_Input $input, public array $prepared /** hash<\type_model\Runtime_Callable,int> */) {}
    private static function providers(\type_model\Type_Catalog $catalog): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        $policy = new \type_model\Lifetime_Policy(); $policy->construction = \type_model\CONSTRUCTION_ZERO;
        $descriptor = new \type_model\Named_Definition('descriptor','',\type_model\Representation::opaque(16,8),new \type_model\Lifetime_Contract($policy,$ops),null,'',false,false,true);
        $void_type = $catalog->find_type('void',''); if ($void_type === null) { throw new \LogicException('Missing void'); }
        $primitives /** hash<\type_model\Storage_Primitive> */ = []; $operations /** hash<string> */ = [];
        for ($role = 0; $role < 6; $role++) { $operations[\type_model\Storage_Roles::name($role)] = 'storage_' . $role; }
        $family = new \type_model\Storage_Family('storage-proof','storage',$descriptor,$catalog->integer_literal_type,$void_type,$primitives,$operations,'Storage','');
        $providers /** vector<\collect_symbols\Provider_Declaration> */ = [new \collect_symbols\Provider_Declaration(null,$family,null,null,null)];
        for ($role = 0; $role < 6; $role++) {
            $function = new \type_model\Storage_Function($family,$role,'storage_'.$role,'','storage-proof','storage_'.$role);
            $providers[] = new \collect_symbols\Provider_Declaration(null,null,$function,null,null);
        }
        return $providers;
    }
    public static function prepare(string $program): Fixture {
        $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $providers = Fixture::providers($catalog);
        $source = new \read_sources\Source_Buffer(); $source->path = '/signatures.phs'; $source->content = 'struct Point { public int32 $value; } ' . $program;
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
        $int32 = $catalog->find_type('int32',''); if ($int32 === null) { throw new \LogicException('Missing int32'); }
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',\type_model\Field_Type::named($int32),true)];
        \resolve_types\Record_Definitions::materialize($types,new \type_model\Record_Declaration('Point','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0));
        $entry = new \resolve_types\Entry_Contract($symbols->symbol_by_id($symbols->entry_symbol_id('/signatures.phs')),$catalog->entry_return_type);
        $input = new \resolve_types\Callable_Input($symbols->symbol_by_id($symbols->find_symbol('f',\collect_symbols\SYMBOL_FUNCTION,0,'')));
        $reader = new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,new \resolve_types\Definition_View($catalog,$types)),$catalog,$instances->view());
        $prepared /** hash<\type_model\Runtime_Callable,int> */ = [];
        $fixture = new Fixture($catalog,$types,$symbols,$instances,$reader,$entry,$input,$prepared);
        Fixture::prepare_calls($fixture);
        return $fixture;
    }
    /** Bounded proof preparation using real application/member workers and joins.
     * This is not the production concrete-preparation coordinator.
     */
    private static function prepare_calls(Fixture $f): void {
        for ($i = 0; $i < $f->catalog->size(); $i++) { \resolve_types\Type_Cache::materialize($f->types,$f->catalog->definition_at($i)); }
        $pending /** vector<\instantiate\Instance_Context> */ = [$f->input->context()];
        $position = 0;
        while ($position < q_count($pending)) {
            $context = $pending[$position]; $position++;
            if ($position > 100) { throw new \LogicException('Unbounded fixture preparation'); }
            $names = $f->reader->annotations->bindings($context->definition);
            $applications /** vector<\instantiate\Application_Task> */ = [];
            $results /** vector<\instantiate\Application_Result> */ = [];
            for ($i = 0; $i < $names->applications_count(); $i++) {
                $task = new \instantiate\Application_Task($context,$names->applications_at($i));
                $applications[] = $task; $results[] = \instantiate\Application_Worker::run($task,$f->reader);
            }
            $definitions = new \resolve_types\Definition_View($f->catalog,$f->types);
            (new \instantiate\Instance_Join($f->instances,$applications,$f->types,$f->reader->annotations->names,$definitions,$f->catalog))->join($results);
            $members /** vector<\instantiate\Member_Task> */ = [];
            $member_results /** vector<\instantiate\Member_Result> */ = [];
            for ($i = 0; $i < $names->members_count(); $i++) {
                $task = \instantiate\Member_Task::occurrence($context,$names->members_at($i));
                $members[] = $task; $member_results[] = \instantiate\Member_Worker::run($task,$f->reader,$f->symbols);
            }
            (new \instantiate\Member_Join($f->instances,$members,$f->types,$f->reader->annotations->names,$definitions,$f->catalog,$f->symbols))->join($member_results);
            foreach ($f->instances->take_introduced() as $introduced) {
                if ($introduced->definition->is_source()) { $pending[] = $introduced; }
            }
        }
    }

}
