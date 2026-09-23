<?php
declare(strict_types=1);
namespace provider_record_bindings_test;
final class Probe {
    private static function record(string $name, string $namespace_name): \type_model\Record_Declaration {
        $fields /** vector<\type_model\Field_Declaration> */ = [];
        return new \type_model\Record_Declaration($name,$namespace_name,$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
    }
    private static function catalog(\type_model\Type_Catalog $base, array $records /** vector<\type_model\Record_Declaration> */): \type_model\Type_Catalog {
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i = 0; $i < $base->size(); $i++) { $definitions[] = $base->definition_at($i); }
        return new \type_model\Type_Catalog($base->provider,$base->content_key,$base->representation_scope,$definitions,$base->integer_literal_type,$base->entry_return_type,$base->boolean_type,$records);
    }
    private static function collect(string $text): \collect_symbols\Symbol_Store {
        $source = new \read_sources\Source_Buffer(); $source->path = '/record.phs'; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0;
        return \collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false)->current;
    }
    public static function run(string $text): void {
        $cases = json_read($text); $base = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        for ($index = 0; $index < $cases->size(); $index++) {
            $input = $cases->at($index); $mode = $input->member('mode')->text(); $n = $input->member('n')->integer(); $ok = true;
            $record = Probe::record('Record',''); $other = Probe::record('Record','other');
            $records /** vector<\type_model\Record_Declaration> */ = [$record,$other]; $catalog = Probe::catalog($base,$records);
            $symbols = Probe::collect('function get($value Record): Record { return $value; }');
            $binding = \resolve_symbols\Declaration_Lookup::find($symbols,$catalog,'Record',1,\resolve_symbols\NAME_TYPE,'');
            if ($binding === null) { throw new \LogicException('Missing provided record binding'); }
            if ($mode === 'lookup') {
                $ok = ($binding->kind === \resolve_symbols\REFERENCE_PROVIDED_RECORD) && ($binding->provided_record === $record)
                    && ($binding->provided_type === null) && ($binding->target_id === 0);
            } elseif ($mode === 'namespace') {
                $qualified = \resolve_symbols\Declaration_Lookup::find($symbols,$catalog,'Record',1,\resolve_symbols\NAME_TYPE,'other');
                if ($qualified === null) { throw new \LogicException('Missing qualified record'); }
                $ok = ($qualified->provided_record === $other) && !$binding->same_target($qualified)
                    && (\resolve_symbols\Declaration_Lookup::find($symbols,$catalog,'Record',1,\resolve_symbols\NAME_TYPE,'absent') === null);
            } elseif ($mode === 'roles') {
                $ok = (\resolve_symbols\Declaration_Lookup::find($symbols,$catalog,'Record',1,\resolve_symbols\NAME_VALUE,'') === null)
                    && (\resolve_symbols\Declaration_Lookup::find($symbols,$catalog,'Record',1,\resolve_symbols\NAME_TYPE_FAMILY,'') === null);
            } elseif ($mode === 'identity') {
                $same = new \resolve_symbols\Name_Binding(20,\resolve_symbols\NAME_TYPE,\resolve_symbols\REFERENCE_PROVIDED_RECORD,0,null,$record);
                $changed = new \resolve_symbols\Name_Binding(20,\resolve_symbols\NAME_TYPE,\resolve_symbols\REFERENCE_PROVIDED_RECORD,0,null,Probe::record('Record',''));
                $ok = $binding->same_target($same) && !$binding->same_target($changed);
            } elseif ($mode === 'invalid') {
                $rejected = false;
                try {
                    if ($n === 0) { $bad = new \resolve_symbols\Name_Binding(1,1,3,0,null,null); }
                    elseif ($n === 1) { $bad = new \resolve_symbols\Name_Binding(1,1,3,0,$base->entry_return_type,$record); }
                    elseif ($n === 2) { $bad = new \resolve_symbols\Name_Binding(1,1,3,1,null,$record); }
                    elseif ($n === 3) { $bad = new \resolve_symbols\Name_Binding(1,2,3,0,null,$record); }
                    elseif ($n === 4) { $bad = new \resolve_symbols\Name_Binding(1,1,2,0,$base->entry_return_type,$record); }
                    elseif ($n === 5) { $bad = new \resolve_symbols\Name_Binding(1,1,1,1,null,$record); }
                    elseif ($n === 6) { $bad = new \resolve_symbols\Name_Binding(1,1,99,1,null,$record); }
                    else { $bad = new \resolve_symbols\Name_Binding(0,1,3,0,null,$record); }
                } catch (\InvalidArgumentException $error) { $rejected = true; }
                $ok = $rejected;
            } else {
                $empty = new \resolve_symbols\Resolution_Set(null);
                if (($mode === 'source_collision') || ($mode === 'collision_invalidates')) {
                    $symbols = Probe::collect('struct Record { public int $value; }');
                    $previous = $empty;
                    if ($mode === 'collision_invalidates') { $previous = \resolve_symbols\Symbol_Resolver::resolve($symbols,$empty,$base,false)->result(); }
                    $plan = new \resolve_symbols\Resolution_Plan($symbols,$previous,$catalog,false);
                    $failed = \resolve_symbols\Symbol_Resolver::resolve($symbols,$previous,$catalog,false);
                    $expected_tasks = 2; if ($mode === 'collision_invalidates') { $expected_tasks = 1; }
                    $ok = !$failed->valid() && ($failed->error_reason === 'Duplicate source/provider type: Record') && ($plan->task_count() === $expected_tasks);
                } else {
                    $names = \resolve_symbols\Symbol_Resolver::resolve($symbols,$empty,$catalog,false)->result();
                    $get = $symbols->find_symbol('get',\collect_symbols\SYMBOL_FUNCTION,0,''); $resolved = $names->for_symbol($get);
                    if ($resolved === null) { throw new \LogicException('Missing source result'); }
                    $ok = ($resolved->names_count() === 2) && ($resolved->names_at(0)->provided_record === $record) && ($resolved->names_at(1)->provided_record === $record);
                    if ($mode !== 'resolution') {
                        $next_records /** vector<\type_model\Record_Declaration> */ = [];
                        if ($mode === 'replace') { $next_records[] = Probe::record('Record',''); }
                        elseif ($mode !== 'remove') { $next_records[] = $record; }
                        if ($mode === 'unused_replace') { $next_records[] = Probe::record('Record','other'); }
                        else { $next_records[] = $other; }
                        $next = Probe::catalog($base,$next_records);
                        $plan = new \resolve_symbols\Resolution_Plan($symbols,$names,$next,false);
                        $reuses = ($mode === 'reuse') || ($mode === 'unused_replace');
                        $expected_tasks = 1; if ($reuses) { $expected_tasks = 0; }
                        $ok = $ok && ($plan->task_count() === $expected_tasks);
                        $updated = \resolve_symbols\Symbol_Resolver::resolve($symbols,$names,$next,false);
                        if ($mode === 'remove') { $ok = $ok && !$updated->valid(); }
                        else {
                            $current = $updated->result()->for_symbol($get);
                            $ok = $ok && (($current === $resolved) === $reuses);
                            if ($current === null) { throw new \LogicException('Missing current record result'); }
                            $ok = $ok && ($current->names_at(0)->provided_record === $next_records[0]);
                        }
                        $ok = $ok && ($resolved->names_at(0)->provided_record === $record);
                    }
                }
            }
            echo $ok ? "true\n" : "false\n";
        }
    }
}
