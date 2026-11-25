<?php
declare(strict_types=1);
namespace instance_registry_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function integer(string $name): \type_model\Named_Definition {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy=1; $policy->construction=1; $policy->assignment=1; $policy->expiring=1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Named_Definition($name,'',\type_model\Representation::integer(32),new \type_model\Lifetime_Contract($policy,$operations),true,'',false,false,true);
    }
    private static function view(\instantiate\Instance_View $view, \instantiate\Instance_Context $context): bool { return $view->context_for($context->context_id) === $context; }
    public static function run(): void {
        $source = new \read_sources\Source_Buffer(); $source->path='/registry.phs';
        $source->content='template<typename T> struct Box { public T $value; } template<typename T> function identity($x T): T { return $x; } return 0;';
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        $frontends=new \parse\Frontend_Set();$frontends->add($file);$frontends->entry_index=0;
        $symbols=\collect_symbols\Declaration_Collector::collect($frontends,new \collect_symbols\Symbol_Store(1),false)->current;
        $box=$symbols->symbol_by_id($symbols->find_symbol('Box',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));
        $function=$symbols->symbol_by_id($symbols->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''));
        $entry=\instantiate\Instance_Context::ordinary($symbols->symbol_by_id($symbols->entry_symbol_id('/registry.phs')));
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
        $word=Probe::integer('word');$other=Probe::integer('other');
        $arguments /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($word)];
        $none /** vector<\check_templates\Definition_Result> */ = []; $permissions=new \check_templates\Template_Set($none,0);
        $state=new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage));
        $constant=new \instantiate\Template_Argument($word,'0');$state->constants[99]=$constant;$state->constant_owners[99]=$box;
        $seed=new \instantiate\Instance_Set($state,$permissions);$store=$seed->candidate();
        Probe::check(($store->size()===0)&&($store->next_id()===1)&&($store->lineage()===$types->lineage));
        Probe::check(($store->constant_for(99)===$constant)&&($seed->constant_owner(99)===$box));
        $empty_constants /** hash<\instantiate\Template_Argument,int> */ = []; $state->constants=$empty_constants;Probe::check($seed->constant_for(99)===$constant);
        Probe::check(($store->context_for(1)===null)&&($store->constant_for(1)===null)&&($store->instance_type(1)===null)&&($store->type_context($word)===null));
        $id=$store->allocate($types,$box->symbol_id,$arguments);$context=new \instantiate\Instance_Context($box,$id,$arguments);$store->accept($context);$store->accept($context);
        Probe::check(($store->size()===1)&&Probe::view($store->view(),$context));
        $introduced=$store->take_introduced();Probe::check((q_count($introduced)===1)&&($introduced[0]===$context)&& (q_count($store->take_introduced())===0));
        Probe::check(($seed->size()===0)&&($seed->next_id()===1));
        $rejected=false;try{$store->accept(new \instantiate\Instance_Context($box,$id,$arguments));}catch(\LogicException $error){$rejected=true;}Probe::check($rejected);
        $store->bind($entry,42,$context);Probe::check(($store->application($entry,42)===$context)&&($store->application($entry,43)===null)&&($store->type_for($entry,42)===null));
        $store->accept_type($context,$word);Probe::check(($store->type_for($entry,42)===$word)&&($store->type_context($word)===$context));
        $catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $names=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false)->result();
        $binding=$names->for_symbol($function->symbol_id);if($binding===null){throw new \LogicException('Missing fixture binding');}
        $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [$binding];
        $snapshot=$store->snapshot($bindings);Probe::check(Probe::view($snapshot->view(),$context)&&($snapshot->template_checks()===$permissions));
        $fork=$snapshot->candidate();Probe::check($fork->allocate($types,$box->symbol_id,$arguments)===$id);
        $function_id=$fork->allocate($types,$function->symbol_id,$arguments);$callable=new \instantiate\Instance_Context($function,$function_id,$arguments);$fork->accept($callable);
        Probe::check(($fork->size()===2)&&($snapshot->size()===1)&&($snapshot->next_id()===$id+1));
        $second=$fork->snapshot($bindings);$functions=$second->functions();Probe::check((q_count($functions)===1)&&($functions[0]===$callable));
        $fork->accept_type($context,$other);Probe::check(($fork->type_context($word)===null)&&($fork->type_context($other)===$context));
        Probe::check(($snapshot->type_context($word)===$context)&&($snapshot->type_for($entry,42)===$word));
        $escaped=$snapshot->export_state();$empty_contexts /** hash<\instantiate\Instance_Context,int> */ = []; $escaped->contexts=$empty_contexts;$escaped->constants=$empty_constants;Probe::check(($snapshot->size()===1)&&($snapshot->constant_for(99)===$constant));
        $escaped->identities->allocate($types,$function->symbol_id,$arguments);Probe::check($snapshot->next_id()===$id+1);
        $rejected=false;try{$fork->accept_type($entry,$word);}catch(\LogicException $error){$rejected=true;}Probe::check($rejected);
        $missing=new \instantiate\Instance_Context($box,999,$arguments);$rejected=false;try{$fork->accept_type($missing,$word);}catch(\LogicException $error){$rejected=true;}Probe::check($rejected);
        $foreign=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));$rejected=false;try{$fork->allocate($foreign,$box->symbol_id,$arguments);}catch(\LogicException $error){$rejected=true;}Probe::check($rejected);
        $different=$snapshot->candidate();$different->bind($entry,42,$callable);Probe::check($snapshot->application($entry,42)===$context);
        $next_intro=$fork->take_introduced();Probe::check((q_count($next_intro)===1)&&($next_intro[0]===$callable));
        $contexts=$second->contexts();$empty_rows /** vector<\instantiate\Instance_Context> */ = []; $contexts=$empty_rows;Probe::check($second->size()===2);
        Probe::check(($seed->source_binding(123)===null)&&($snapshot->source_binding(123)===null));
        Probe::check(($store->template_checks()===$permissions)&&($snapshot->constant_owner(99)===$box));
        Probe::check($snapshot->source_binding($function->symbol_id)===$binding);
        $exposed=$snapshot->export_state();$empty_bindings /** hash<\resolve_symbols\Symbol_Resolution,int> */ = []; $exposed->source_bindings=$empty_bindings;Probe::check($snapshot->source_binding($function->symbol_id)===$binding);
        $retained_view=$snapshot->view();$store->accept($callable);Probe::check(($retained_view->context_for($callable->context_id)===null)&&($store->view()->context_for($callable->context_id)===$callable));
    }
}
