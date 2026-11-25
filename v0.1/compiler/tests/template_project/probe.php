<?php
declare(strict_types=1);
namespace template_project_test;
final class Probe {
    public static function file(string $path, string $text): \parse\Parse_Result {
        $source = new \read_sources\Source_Buffer(); $source->path = $path; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        return $file;
    }
    public static function collect(array $files /** vector<\parse\Parse_Result> */, \collect_symbols\Symbol_Store $previous): \collect_symbols\Symbol_Store {
        $frontends = new \parse\Frontend_Set(); foreach ($files as $file) { $frontends->add($file); } $frontends->entry_index = 0;
        $result = \collect_symbols\Declaration_Collector::collect($frontends,$previous,false);
        if (!$result->valid) { throw new \LogicException($result->error_reason); }
        return $result->current;
    }
    public static function empty_set(): \check_templates\Template_Set {
        $none /** vector<\check_templates\Definition_Result> */ = []; return new \check_templates\Template_Set($none,0);
    }
    public static function resolve(\collect_symbols\Symbol_Store $symbols, \resolve_symbols\Resolution_Set $previous, \type_model\Type_Catalog $catalog): \resolve_symbols\Resolution_Set {
        return \resolve_symbols\Symbol_Resolver::resolve($symbols,$previous,$catalog,false)->result();
    }
    public static function run(string $text): void {
        $cases = json_read($text); $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $mode = $cases->at($ci)->text(); $ok = true;
            $main = Probe::file('/main.phs','return 0;');
            $first = Probe::file('/first.phs','template<typename T> function identity($x T): T { return $x; }');
            $second = Probe::file('/second.phs','template<typename T> function forward($x T): T { return identity<T>($x); }');
            $helper = Probe::file('/helper.phs','function helper(): int { return 1; }');
            $files /** vector<\parse\Parse_Result> */ = [$main,$first,$second,$helper];
            $symbols = Probe::collect($files,new \collect_symbols\Symbol_Store(1));
            $names = Probe::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog);
            $empty = Probe::empty_set();
            $cold = \check_templates\Template_Checker::check($symbols,$names,$catalog,$empty,false)->result();
            $identity = $symbols->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,'');
            $forward = $symbols->find_symbol('forward',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,'');
            $old = $cold->for_definition($identity);
            if ($old === null) { throw new \LogicException('Missing baseline definition'); }
            if ($mode === 'cold') { $ok = ($cold->size() === 2) && ($cold->selected_count === 2); }
            elseif (($mode === 'warm') || ($mode === 'full')) {
                $next = \check_templates\Template_Checker::check($symbols,$names,$catalog,$cold,$mode === 'full')->result();
                if ($mode === 'warm') { $ok = ($next->selected_count === 0) && ($next->for_definition($identity) === $old); }
                else { $ok = ($next->selected_count === 2) && ($next->for_definition($identity) !== $old); }
            } elseif (($mode === 'ordinary_edit') || ($mode === 'removed_template') || ($mode === 'added_template') || ($mode === 'changed_dependency') || ($mode === 'removed_dependency')) {
                $next_files /** vector<\parse\Parse_Result> */ = [$main,$first];
                if ($mode !== 'removed_template') { $next_files[] = $second; }
                if ($mode !== 'removed_dependency') { $next_files[] = $helper; }
                if ($mode === 'ordinary_edit') { $next_files[0] = Probe::file('/main.phs','return 1;'); }
                if ($mode === 'changed_dependency') { $next_files[1] = Probe::file('/first.phs','template<typename T> function identity($x T): T { $y T = $x; return $y; }'); }
                if ($mode === 'added_template') { $next_files[] = Probe::file('/extra.phs','template<typename T> function extra($x T): T { return $x; }'); }
                $previous = $cold;
                if ($mode === 'removed_dependency') {
                    $helper_id = $symbols->find_symbol('helper',\collect_symbols\SYMBOL_FUNCTION,0,'');
                    $deps /** vector<\collect_symbols\Symbol_Record> */ = [$old->task->owner,$symbols->symbol_by_id($helper_id)];
                    $binding_rows /** vector<\resolve_symbols\Symbol_Resolution> */ = [$old->task->bindings];
                    $with_dependency = new \check_templates\Definition_Result($old->task,$catalog,$deps,$binding_rows,1);
                    $other = $cold->for_definition($forward); if ($other === null) { throw new \LogicException('Missing forward'); }
                    $previous_rows /** vector<\check_templates\Definition_Result> */ = [$with_dependency,$other];
                    $previous = new \check_templates\Template_Set($previous_rows,2);
                }
                $next_symbols = Probe::collect($next_files,$symbols); $next_names = Probe::resolve($next_symbols,$names,$catalog);
                $next = \check_templates\Template_Checker::check($next_symbols,$next_names,$catalog,$previous,false)->result();
                if ($mode === 'ordinary_edit') { $ok = ($next->selected_count === 0) && ($next->for_definition($identity) === $old); }
                elseif ($mode === 'removed_template') { $ok = ($next->size() === 1) && ($next->for_definition($forward) === null) && ($next->selected_count === 0); }
                elseif ($mode === 'added_template') { $ok = ($next->size() === 3) && ($next->selected_count === 1) && ($next->for_definition($identity) === $old); }
                elseif ($mode === 'changed_dependency') { $ok = ($next->selected_count === 2) && ($next->for_definition($forward) !== $cold->for_definition($forward)); }
                else { $ok = ($next->selected_count === 1) && ($next->for_definition($identity) !== $old); }
            } elseif ($mode === 'failed_body') {
                $bad_files /** vector<\parse\Parse_Result> */ = [$main,$first,Probe::file('/bad.phs','template<typename T> function bad($x T): int { echo $x; return 0; }')];
                $bad_symbols = Probe::collect($bad_files,$symbols); $bad_names = Probe::resolve($bad_symbols,$names,$catalog);
                $failed = \check_templates\Template_Checker::check($bad_symbols,$bad_names,$catalog,$cold,false);
                $ok = !$failed->valid(); $diagnostic = $failed->diagnostic;
                if ($diagnostic === null) { $ok = false; }
                else { $ok = $ok && ($diagnostic->path === '/bad.phs') && ($diagnostic->length > 0); }
                $rejected = false; try { $failed->result(); } catch (\LogicException $error) { $rejected = true; } $ok = $ok && $rejected;
            } elseif ($mode === 'no_templates') {
                $plain /** vector<\parse\Parse_Result> */ = [$main,$helper]; $plain_symbols = Probe::collect($plain,$symbols); $plain_names = Probe::resolve($plain_symbols,$names,$catalog);
                $next = \check_templates\Template_Checker::check($plain_symbols,$plain_names,$catalog,$cold,false)->result(); $ok = ($next->size() === 0) && ($next->selected_count === 0);
            } elseif (($mode === 'missing_bindings') || ($mode === 'stale_bindings')) {
                $target_names = new \resolve_symbols\Resolution_Set(null); $target_symbols = $symbols;
                if ($mode === 'stale_bindings') { $changed_files /** vector<\parse\Parse_Result> */ = [$main,Probe::file('/first.phs',$first->tokens->source->content),$second,$helper]; $target_symbols = Probe::collect($changed_files,$symbols); $target_names = $names; }
                $rejected = false;
                try { $plan = new \check_templates\Template_Plan($target_symbols,$target_names,$catalog,$cold,false); }
                catch (\LogicException $error) { $rejected = true; }
                $ok = $rejected;
            } elseif (($mode === 'invalid_outcome') || ($mode === 'empty_outcome')) {
                $rejected = false;
                try {
                    if ($mode === 'empty_outcome') { $update = new \check_templates\Template_Update(null,null); }
                    else { $update = new \check_templates\Template_Update($cold,new \check_templates\Template_Diagnostic('/x',0,1,'bad')); }
                } catch (\InvalidArgumentException $error) { $rejected = true; }
                $ok = $rejected;
            } else {
                $plan = new \check_templates\Template_Plan($symbols,$names,$catalog,$empty,true);
                $results /** vector<\check_templates\Definition_Result> */ = [];
                for ($i = 0; $i < $plan->task_count(); $i++) { $worker = \check_templates\Template_Worker::create($plan->task_at($i),$symbols,$names,$catalog); $results[] = $worker->check(); }
                $batch = $results;
                if ($mode === 'reverse') { $batch = [$results[1],$results[0]]; }
                elseif ($mode === 'missing') { $batch = [$results[0]]; }
                elseif ($mode === 'duplicate') { $batch[] = $results[0]; }
                else {
                    $task = $results[0]->task; $deps /** vector<\collect_symbols\Symbol_Record> */ = [$task->owner]; $binding_rows /** vector<\resolve_symbols\Symbol_Resolution> */ = [$task->bindings]; $visits = 1; $result_catalog = $catalog;
                    if ($mode === 'foreign_task') { $task = new \check_templates\Definition_Task($task->owner,$task->bindings); }
                    if ($mode === 'no_owner') { $deps = []; }
                    if ($mode === 'no_binding') { $binding_rows = []; }
                    if ($mode === 'zero_visits') { $visits = 0; }
                    if ($mode === 'stale_catalog') { $result_catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json')); }
                    $batch[0] = new \check_templates\Definition_Result($task,$result_catalog,$deps,$binding_rows,$visits);
                }
                $join = new \check_templates\Template_Join($plan); $rejected = false;
                try { $joined = $join->join($batch); if ($mode === 'reverse') { $ok = ($joined->for_definition($identity) === $results[0]) && ($joined->for_definition($forward) === $results[1]); } }
                catch (\LogicException $error) { $rejected = true; }
                if ($mode === 'reverse') { $ok = $ok && !$rejected; } else { $ok = $rejected; }
            }
            if (!$ok) { throw new \LogicException('Template project case failed: ' . $mode); }
            echo "true\n";
        }
    }
}
