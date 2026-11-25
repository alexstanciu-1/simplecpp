<?php
declare(strict_types=1);
namespace symbol_origins_test;
final class Fixtures {
    public static function providers(string $namespace_name): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $empty_parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $result = new \type_model\Semantic_Result(\type_model\Type_Reference::provided('p','void'),\type_model\RESULT_NONE);
        $signature = new \type_model\Semantic_Signature($empty_parameters,$result);
        $abi_parameters /** vector<\type_model\Runtime_Abi_Position> */ = [];
        $abi = new \type_model\Runtime_Callable_Abi('call_link','ccc',null,$abi_parameters);
        $callable = new \type_model\Runtime_Callable('p','call','call',$namespace_name,$signature,$abi);
        $life_operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$life_operations);
        $descriptor = new \type_model\Named_Definition('descriptor','',\type_model\Representation::opaque(16,8),$life,null,'',false,false,true);
        $counter = new \type_model\Named_Definition('counter','',\type_model\Representation::integer(64),$life,false,'',false,false,true);
        $void_type = new \type_model\Named_Definition('void','',\type_model\Representation::void_type(),null,null,'',false,false,false);
        $primitives /** hash<\type_model\Storage_Primitive> */ = []; $operations /** hash<string> */ = [];
        $operations['allocate'] = 'allocate';
        $storage = new \type_model\Storage_Family('p','storage',$descriptor,$counter,$void_type,$primitives,$operations,'Storage',$namespace_name);
        $function = new \type_model\Storage_Function($storage,0,'allocate',$namespace_name,'p','alloc');
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE)];
        $arguments /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::parameter('["p","family"]',0)];
        $self = \type_model\Type_Reference::family('["p","family"]',$arguments);
        $method_parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $method_signature = new \type_model\Semantic_Signature($method_parameters,$result);
        $requirements /** vector<\type_model\Capability_Requirement> */ = []; $effects /** vector<\type_model\Element_Effect> */ = [];
        $operation = new \type_model\Family_Operation('inspect',$method_signature,$requirements,$effects,0,\type_model\Type_Reference::named('inspect',''));
        $family_operations /** hash<\type_model\Family_Operation> */ = []; $family_operations['inspect'] = $operation;
        $lifecycle /** hash<string> */ = []; $mappings /** hash<\type_model\Type_Reference> */ = [];
        $family_definition = new \type_model\Family_Definition('p','family',$parameters,$family_operations,$lifecycle,\type_model\Type_Reference::named('Family',$namespace_name));
        $family = new \type_model\Family_Declaration($family_definition,$mappings); $method = new \type_model\Family_Method($family,$operation);
        $values /** vector<\collect_symbols\Provider_Declaration> */ = [
            new \collect_symbols\Provider_Declaration($callable,null,null,null,null),
            new \collect_symbols\Provider_Declaration(null,$storage,null,null,null),
            new \collect_symbols\Provider_Declaration(null,null,$function,null,null),
            new \collect_symbols\Provider_Declaration(null,null,null,$family,null),
            new \collect_symbols\Provider_Declaration(null,null,null,null,$method)];
        return $values;
    }
}
final class Probe {
    public static function files(string $text): \parse\Frontend_Set {
        $source = new \read_sources\Source_Buffer(); $source->path = '/main.phs'; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0; return $files;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $mode = $cases->at($index)->text(); $ok = true;
            $providers = Fixtures::providers('');
            $files = Probe::files('function local(): int { return 1; } return call();');
            $empty = new \collect_symbols\Symbol_Store(1);
            $cold = \collect_symbols\Declaration_Collector::with_providers($files,$empty,false,$providers);
            $store = $cold->current;
            $call = $store->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'');
            $family = $store->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,'');
            $method = $store->find_symbol('inspect',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,$family,'');
            if ($mode === 'membership') {
                $ok = $cold->valid && ($store->size() === 7) && (q_count($cold->changes) === 7)
                    && ($call === 3) && ($family === 6) && ($method === 7)
                    && (q_count($store->file_symbol_ids('/main.phs')) === 2)
                    && (q_count($store->file_symbol_ids('')) === 0)
                    && (q_count($store->child_symbol_ids($family)) === 1)
                    && ($store->symbol_by_id($call)->provider()->callable() === $providers[0]->callable())
                    && $store->symbol_by_id($method)->receiver_const()
                    && !$store->symbol_by_id($method)->has_executable_body() && ($empty->size() === 0);
            } elseif ($mode === 'origin_guards') {
                $rejections = 0;
                try { $unused = $store->symbol_by_id($call)->source_frontend(); } catch (\LogicException $error) { $rejections++; }
                try { $unused_fact = $store->symbol_by_id($call)->source_fact(); } catch (\LogicException $error) { $rejections++; }
                try { $unused_provider = $store->symbol_by_id(1)->provider(); } catch (\LogicException $error) { $rejections++; }
                $ok = ($rejections === 3) && $store->symbol_by_id(1)->is_source();
            } elseif (($mode === 'warm') || ($mode === 'full') || ($mode === 'member_wrappers') || ($mode === 'new_owners')) {
                $next = $providers;
                if ($mode === 'member_wrappers') {
                    $storage = $providers[2]->storage_function(); $member = $providers[4]->method();
                    $next[2] = new \collect_symbols\Provider_Declaration(null,null,new \type_model\Storage_Function($storage->family,$storage->role,$storage->name,$storage->namespace_name,$storage->provider,$storage->id),null,null);
                    $next[4] = new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($member->family,$member->operation));
                }
                if ($mode === 'new_owners') { $next = Fixtures::providers(''); }
                $updated = \collect_symbols\Declaration_Collector::with_providers($files,$store,$mode === 'full',$next);
                $expected_changes = 0; if ($mode === 'full') { $expected_changes = 2; } if ($mode === 'new_owners') { $expected_changes = 5; }
                $ok = q_count($updated->changes) === $expected_changes;
                for ($position = 2; $position < 7; $position++) {
                    $before = $store->record_at($position); $after = $updated->current->record_at($position);
                    if (($before->symbol_id !== $after->symbol_id) || (($before === $after) !== ($mode !== 'new_owners'))) { $ok = false; }
                }
            } elseif ($mode === 'removal') {
                $removed = \collect_symbols\Declaration_Collector::collect($files,$store,false);
                $ok = ($removed->current->size() === 2) && (q_count($removed->changes) === 5) && ($store->size() === 7);
                foreach ($removed->changes as $change) { if ((int)$change->status !== \collect_symbols\CHANGE_REMOVED) { $ok = false; } }
            } elseif ($mode === 'namespace') {
                $other = Fixtures::providers('other:scope'); $both = $providers;
                foreach ($other as $declaration) { $both[] = $declaration; }
                $qualified = \collect_symbols\Declaration_Collector::with_providers($files,$store,false,$both)->current;
                $other_call = $qualified->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'other:scope');
                $other_family = $qualified->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,'other:scope');
                $ok = ($qualified->size() === 12) && ($other_call !== $call) && ($other_family !== $family)
                    && ($qualified->find_symbol('inspect',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,$other_family,'other:scope') !== 0)
                    && ($qualified->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'other') === 0)
                    && ($qualified->symbol_by_id($call) === $store->symbol_by_id($call));
            } elseif (($mode === 'duplicate_provider') || ($mode === 'duplicate_source')) {
                $duplicate = $providers; $input = $files;
                if ($mode === 'duplicate_provider') { $duplicate[] = $providers[0]; }
                else { $input = Probe::files('function call(): int { return 1; }'); }
                $rejected = false;
                try { $bad = \collect_symbols\Declaration_Collector::with_providers($input,$store,false,$duplicate); }
                catch (\RuntimeException $error) { $rejected = true; }
                $ok = $rejected && ($store->size() === 7) && ($store->next_symbol_id() === 8);
            } elseif (($mode === 'wrong_owner') || ($mode === 'missing_owner')) {
                $candidate = new \collect_symbols\Symbol_Store(100); $other = Fixtures::providers('');
                $candidate->add(\collect_symbols\Symbol_Record::from_provider(1,0,$other[3]));
                $owner = 1; if ($mode === 'missing_owner') { $owner = 0; }
                $rejected = false;
                try { $candidate->add(\collect_symbols\Symbol_Record::from_provider(2,$owner,$providers[4])); }
                catch (\LogicException $error) { $rejected = true; }
                $ok = $rejected && ($candidate->size() === 1) && !$candidate->contains(2) && (q_count($candidate->child_symbol_ids(1)) === 0);
            } elseif ($mode === 'invalid_origin') {
                $rejections = 0; $source = $store->symbol_by_id(1)->source();
                try { $bad_none = new \collect_symbols\Symbol_Record(1,0,'','',null,null); } catch (\InvalidArgumentException $error) { $rejections++; }
                try { $bad_both = new \collect_symbols\Symbol_Record(1,0,'call','',$source,$providers[0]); } catch (\InvalidArgumentException $error) { $rejections++; }
                try { $bad_name = new \collect_symbols\Symbol_Record(1,0,'other','',null,$providers[0]); } catch (\InvalidArgumentException $error) { $rejections++; }
                $ok = $rejections === 3;
            } elseif (($mode === 'source_to_provider') || ($mode === 'provider_to_source')) {
                $source_files = Probe::files('function call(): int { return 1; }');
                $source_only = \collect_symbols\Declaration_Collector::collect($source_files,$empty,false);
                $source_id = $source_only->current->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'');
                $provider_only_files = Probe::files('return call();');
                $provider_only = \collect_symbols\Declaration_Collector::with_providers($provider_only_files,$source_only->current,false,$providers);
                $provider_id = $provider_only->current->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'');
                $ok = ($provider_id === $source_id) && !$provider_only->current->symbol_by_id($provider_id)->is_source()
                    && $source_only->current->symbol_by_id($source_id)->is_source();
                if ($mode === 'provider_to_source') {
                    $back = \collect_symbols\Declaration_Collector::collect($source_files,$provider_only->current,false);
                    $ok = $ok && ($back->current->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'') === $source_id)
                        && $back->current->symbol_by_id($source_id)->is_source() && ($back->current->size() === 2);
                }
            } elseif ($mode === 'entry_selection') {
                $entry = \resolve_types\Entry_Preparation::prepare($files,$cold); $ok = $entry->symbol() === $store->symbol_by_id(1);
            } else {
                $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
                $baseline = new \resolve_symbols\Resolution_Set(null);
                if (($mode === 'template_resolution') || ($mode === 'template_incremental') || ($mode === 'template_arity') || ($mode === 'family_resolution')) {
                    $source_text = 'return allocate<int>();';
                    if ($mode === 'template_arity') { $source_text = 'return allocate<int,int>();'; }
                    if ($mode === 'family_resolution') { $source_text = 'function consume($value Family<int>): int { return 1; }'; }
                    $files = Probe::files($source_text);
                    $store = \collect_symbols\Declaration_Collector::with_providers($files,$empty,false,$providers)->current;
                }
                if ($mode === 'template_permissions') {
                    $definitions /** vector<\check_templates\Definition_Result> */ = [];
                    $permissions = new \check_templates\Template_Set($definitions,0);
                    $permissions->require_definition($store->symbol_by_id($family),$baseline,$catalog);
                    $template_files = Probe::files('template<typename T> struct LocalFamily { public T $value; }');
                    $source_templates = \collect_symbols\Declaration_Collector::collect($template_files,$empty,false)->current;
                    $template_id = $source_templates->find_symbol('LocalFamily',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,'');
                    $rejected = false;
                    try { $permissions->require_definition($source_templates->symbol_by_id($template_id),$baseline,$catalog); }
                    catch (\LogicException $error) { $rejected = true; }
                    $ok = $rejected;
                } elseif ($mode === 'template_arity') {
                    $failure = \resolve_symbols\Symbol_Resolver::resolve($store,$baseline,$catalog,false);
                    $ok = !$failure->valid() && ($failure->error_reason === 'Provider family type argument count mismatch');
                } elseif ($mode === 'provider_worker_rejected') {
                    $rejected = false;
                    try { $worker = new \resolve_symbols\Resolution_Worker($store,$store->symbol_by_id($call),$catalog); $bad_result = $worker->run(); }
                    catch (\LogicException $error) { $rejected = true; }
                    $ok = $rejected;
                } else {
                    $names = \resolve_symbols\Symbol_Resolver::resolve($store,$baseline,$catalog,false)->result();
                    $expected_count = 2; if (($mode === 'template_resolution') || ($mode === 'template_incremental')) { $expected_count = 1; }
                    $ok = ($names->size() === $expected_count) && ($names->for_symbol($store->find_symbol('call',\collect_symbols\SYMBOL_FUNCTION,0,'')) === null);
                    if ($mode === 'template_incremental') {
                        $changed_providers = Fixtures::providers('');
                        $changed = \collect_symbols\Declaration_Collector::with_providers($files,$store,false,$changed_providers);
                        $changed_plan = new \resolve_symbols\Resolution_Plan($changed->current,$names,$catalog,false);
                        $changed_names = \resolve_symbols\Symbol_Resolver::resolve($changed->current,$names,$catalog,false)->result();
                        $ok = $ok && ($changed_plan->task_count() === 1) && ($changed_names->for_symbol(1) !== $names->for_symbol(1));
                    }
                    if ($mode === 'incremental_resolution') {
                        $warm = \collect_symbols\Declaration_Collector::with_providers($files,$store,false,$providers);
                        $plan = new \resolve_symbols\Resolution_Plan($warm->current,$names,$catalog,false);
                        $again = \resolve_symbols\Symbol_Resolver::resolve($warm->current,$names,$catalog,false)->result();
                        $ok = $ok && ($plan->task_count() === 0) && ($again->for_symbol(1) === $names->for_symbol(1));
                    }
                }
            }
            echo $ok ? "true\n" : "false\n";
        }
    }
}
