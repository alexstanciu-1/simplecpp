<?php
declare(strict_types=1);
namespace resolution_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function file(string $path, string $text): \parse\Parse_Result {
        $source = new \read_sources\Source_Buffer(); $source->path = $path; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); } return $file;
    }
    public static function collect(array $files /** vector<\parse\Parse_Result> */, \collect_symbols\Symbol_Store $previous): \collect_symbols\Symbol_Store {
        $frontends = new \parse\Frontend_Set(); $frontends->entry_index = 0;
        foreach ($files as $file) { $frontends->add($file); }
        $refresh = \collect_symbols\Declaration_Collector::collect($frontends,$previous,false);
        if (!$refresh->valid) { throw new \LogicException($refresh->error_reason); } return $refresh->current;
    }
    public static function catalog(): \type_model\Type_Catalog { return \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json')); }
    private static function resolve(\collect_symbols\Symbol_Store $symbols, \resolve_symbols\Resolution_Set $previous,
        \type_model\Type_Catalog $catalog, bool $full): \resolve_symbols\Resolution_Set {
        $update = \resolve_symbols\Symbol_Resolver::resolve($symbols,$previous,$catalog,$full);
        if (!$update->valid()) { throw new \LogicException($update->error_reason); } return $update->result();
    }
    private static function same_set(\resolve_symbols\Resolution_Set $left, \resolve_symbols\Resolution_Set $right): bool { return $left === $right; }
    public static function run(): void {
        $a = Probe::file('/a.phs','return answer() + identity<int>(1);');
        $b = Probe::file('/b.phs','function answer(): int { return 1; }');
        $c = Probe::file('/c.phs','function spare(): int { return 9; }');
        $d = Probe::file('/d.phs','struct Box { public int $x; } template<typename T> function identity($x T): T { return $x; }');
        $files /** vector<\parse\Parse_Result> */ = [$a,$b,$c,$d]; $symbols = Probe::collect($files,new \collect_symbols\Symbol_Store(1));
        $catalog = Probe::catalog(); $empty = new \resolve_symbols\Resolution_Set(null);
        $plan = new \resolve_symbols\Resolution_Plan($symbols,$empty,$catalog,false); Probe::check($plan->task_count() === 8);
        $results /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
        $position = $plan->task_count();
        while ($position > 0) { $position = $position-1; $worker = new \resolve_symbols\Resolution_Worker($symbols,$plan->task_at($position),$catalog); $results[] = $worker->run()->result(); }
        $join = new \resolve_symbols\Resolution_Join($plan); $cold = $join->join($results);
        Probe::check($cold->size() === 8); Probe::check($empty->size() === 0);
        for ($i = 0; $i < $cold->size(); $i++) {
            Probe::check($cold->at($i)->owner === $symbols->record_at($i));
            Probe::check($cold->declaration_for($cold->at($i)->owner->symbol_id) === $cold->at($i)->owner);
            Probe::check(\resolve_symbols\Binding_Coverage::complete($cold->at($i)));
        }
        $warm_plan = new \resolve_symbols\Resolution_Plan($symbols,$cold,$catalog,false); Probe::check($warm_plan->task_count() === 0);
        $warm = Probe::resolve($symbols,$cold,$catalog,false); Probe::check(!Probe::same_set($warm,$cold));
        for ($i = 0; $i < $warm->size(); $i++) { Probe::check($warm->at($i) === $cold->at($i)); }
        $full_plan = new \resolve_symbols\Resolution_Plan($symbols,$cold,$catalog,true); Probe::check($full_plan->task_count() === 8);
        $full = Probe::resolve($symbols,$cold,$catalog,true);
        for ($i = 0; $i < $full->size(); $i++) {
            Probe::check($full->at($i) !== $cold->at($i));
            Probe::check($full->at($i)->calls_count() === $cold->at($i)->calls_count());
            Probe::check($full->at($i)->names_count() === $cold->at($i)->names_count());
        }
        $entry = $symbols->entry_symbol_id('/a.phs');
        $answer_edit = Probe::file('/b.phs','function answer(): int { return 2; }');
        $edited_files /** vector<\parse\Parse_Result> */ = [$a,$answer_edit,$c,$d]; $edited = Probe::collect($edited_files,$symbols);
        $edited_plan = new \resolve_symbols\Resolution_Plan($edited,$cold,$catalog,false); Probe::check($edited_plan->task_count() === 2);
        Probe::check(!$edited_plan->selected($entry)); $edited_names = Probe::resolve($edited,$cold,$catalog,false);
        Probe::check($edited_names->for_symbol($entry) === $cold->for_symbol($entry));
        $template_edit = Probe::file('/d.phs','struct Box { public int $x; } template<typename T> function identity($x T): T { return $x + $x; }');
        $template_files /** vector<\parse\Parse_Result> */ = [$a,$b,$c,$template_edit]; $templates = Probe::collect($template_files,$symbols);
        $template_plan = new \resolve_symbols\Resolution_Plan($templates,$cold,$catalog,false); Probe::check($template_plan->task_count() === 4); Probe::check($template_plan->selected($entry));
        $new_templates = Probe::resolve($templates,$cold,$catalog,false);
        Probe::check($new_templates->for_symbol($entry) !== $cold->for_symbol($entry));
        Probe::check($new_templates->for_symbol($entry)->applications_at(0)->definition === $templates->symbol_by_id($templates->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0)));
        $removed_files /** vector<\parse\Parse_Result> */ = [$a,$b,$d]; $removed = Probe::collect($removed_files,$symbols);
        $remove_plan = new \resolve_symbols\Resolution_Plan($removed,$cold,$catalog,false); Probe::check($remove_plan->task_count() === 0);
        $without_spare = Probe::resolve($removed,$cold,$catalog,false); Probe::check($without_spare->size() === 6);
        Probe::check($without_spare->for_symbol($symbols->find_symbol('spare',\collect_symbols\SYMBOL_FUNCTION,0)) === null);
        Probe::check($cold->size() === 8);
        $fresh_catalog = Probe::catalog(); $catalog_plan = new \resolve_symbols\Resolution_Plan($symbols,$cold,$fresh_catalog,false);
        Probe::check($catalog_plan->task_count() === 4);
        $fresh_names = Probe::resolve($symbols,$cold,$fresh_catalog,false);
        Probe::check($fresh_names->for_symbol($entry)->names_at(0)->provided_type === $fresh_catalog->entry_return_type);
        Probe::check($cold->for_symbol($entry)->names_at(0)->provided_type === $catalog->entry_return_type);
        $constant_file = Probe::file('/constant.phs','const answer: int = 5;');
        $constant_files /** vector<\parse\Parse_Result> */ = [$a,$b,$c,$d,$constant_file]; $constant_symbols = Probe::collect($constant_files,$symbols);
        $constant_plan = new \resolve_symbols\Resolution_Plan($constant_symbols,$cold,$catalog,false); Probe::check($constant_plan->selected($entry));
        $failed = \resolve_symbols\Symbol_Resolver::resolve($constant_symbols,$cold,$catalog,false);
        Probe::check(!$failed->valid()); Probe::check($failed->error_reason === 'Calling a constant is not implemented'); Probe::check($failed->error_path === '/a.phs');
        $bad = false; try { $failed->result(); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
        Probe::check(Probe::resolve($symbols,$cold,$catalog,false)->for_symbol($entry) === $cold->for_symbol($entry));
        $no_results /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
        $bad = false; try { $join->join($no_results); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
        $duplicates /** vector<\resolve_symbols\Symbol_Resolution> */ = [$results[0],$results[0]];
        $bad = false; try { $join->join($duplicates); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
        $warm_join = new \resolve_symbols\Resolution_Join($warm_plan);
        $bad = false; try { $warm_join->join($results); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
        $bad = false; try { $empty->declaration_for(1); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
        $empty_symbols = new \collect_symbols\Symbol_Store($symbols->next_symbol_id());
        $none = Probe::resolve($empty_symbols,$cold,$catalog,false); Probe::check($none->size() === 0);
        Probe::check($none->for_symbol($entry) === null);
    }
    public static function changed(\resolve_symbols\Symbol_Resolution $result, int $mode): \resolve_symbols\Symbol_Resolution {
        $calls /** vector<\resolve_symbols\Symbol_Binding> */ = [];
        for ($i = 0; $i < $result->calls_count(); $i++) {
            $row = $result->calls_at($i);
            if ($mode !== 4) { $calls[] = $row; }
        }
        $scopes /** vector<\resolve_symbols\Lexical_Scope> */ = [];
        for ($i = 0; $i < $result->scopes_count(); $i++) {
            $row = $result->scopes_at($i);
            $scopes[] = $row;
        }
        $locals /** vector<\resolve_symbols\Local_Record> */ = [];
        for ($i = 0; $i < $result->locals_count(); $i++) {
            $row = $result->locals_at($i);
            if (($mode === 8) && ($i === 1)) { $row->scope_id = 1; }
            $locals[] = $row;
        }
        $uses /** vector<\resolve_symbols\Local_Binding> */ = [];
        for ($i = 0; $i < $result->uses_count(); $i++) {
            $row = $result->uses_at($i);
            if (($mode === 3) && ($i === 0)) { $row->access = \resolve_symbols\LOCAL_WRITE; }
            if (($mode === 9) && ($i === 0)) { $row->local_id = 2; }
            if ($mode !== 2) { $uses[] = $row; }
        }
        $parameters /** vector<\resolve_symbols\Template_Parameter> */ = [];
        for ($i = 0; $i < $result->parameters_count(); $i++) {
            $row = $result->parameters_at($i);
            $parameters[] = $row;
        }
        $constants /** vector<\resolve_symbols\Scoped_Constant> */ = [];
        for ($i = 0; $i < $result->constants_count(); $i++) {
            $row = $result->constants_at($i);
            if ($mode !== 6) { $constants[] = $row; }
        }
        $members /** vector<\resolve_symbols\Member_Call_Binding> */ = [];
        for ($i = 0; $i < $result->members_count(); $i++) {
            $row = $result->members_at($i);
            if ($mode !== 7) { $members[] = $row; }
        }
        $names /** vector<\resolve_symbols\Name_Binding> */ = [];
        for ($i = 0; $i < $result->names_count(); $i++) {
            $row = $result->names_at($i);
            if ($mode === 10) { $row = new \resolve_symbols\Name_Binding($row->use_node_id,\resolve_symbols\NAME_TYPE,\resolve_symbols\REFERENCE_PROVIDED_TYPE,0,Probe::catalog()->entry_return_type); }
            if ($mode !== 1) { $names[] = $row; }
        }
        $applications /** vector<\resolve_symbols\Template_Application_Binding> */ = [];
        for ($i = 0; $i < $result->applications_count(); $i++) {
            $row = $result->applications_at($i);
            if ($mode !== 5) { $applications[] = $row; }
        }
        return new \resolve_symbols\Symbol_Resolution($result->owner,$calls,$scopes,$locals,$uses,$parameters,$constants,$members,$names,$applications);
    }
    public static function acceptance(): void {
        $catalog = Probe::catalog();
        $file = Probe::file('/coverage.phs','$x int = 1; { $y int = $x; $x = $y; } return answer(); function answer(): int { return 3; }');
        $files /** vector<\parse\Parse_Result> */ = [$file]; $symbols = Probe::collect($files,new \collect_symbols\Symbol_Store(1));
        $previous = Probe::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false); $entry = $previous->at(0);
        $modes /** vector<int> */ = [1,2,3,4,8,9];
        foreach ($modes as $mode) {
            $changed = Probe::changed($entry,$mode); Probe::check(!\resolve_symbols\Binding_Coverage::complete($changed));
            $batch /** vector<\resolve_symbols\Symbol_Resolution> */ = [$changed,$previous->at(1)];
            $bad = false;
            try { \resolve_symbols\Resolution_Set::publish($symbols,$catalog,$previous,$batch); } catch (\LogicException $e) { $bad = true; }
            Probe::check($bad);
        }
        Probe::check((int)$entry->uses_at(0)->access === \resolve_symbols\LOCAL_READ);
        Probe::check((int)$entry->locals_at(1)->scope_id === 2);
        $template_file = Probe::file('/template.phs','template<typename T> struct Box { public T $x; } $b Box<int> = new Box<int>();');
        $template_files /** vector<\parse\Parse_Result> */ = [$template_file]; $templates = Probe::collect($template_files,new \collect_symbols\Symbol_Store(1));
        $template_names = Probe::resolve($templates,new \resolve_symbols\Resolution_Set(null),$catalog,false);
        Probe::check(!\resolve_symbols\Binding_Coverage::complete(Probe::changed($template_names->at(0),5)));
        $constant_file = Probe::file('/constant.phs','{ const N: int = 1; return N; }');
        $constant_files /** vector<\parse\Parse_Result> */ = [$constant_file]; $constants = Probe::collect($constant_files,new \collect_symbols\Symbol_Store(1));
        $constant_names = Probe::resolve($constants,new \resolve_symbols\Resolution_Set(null),$catalog,false);
        Probe::check(!\resolve_symbols\Binding_Coverage::complete(Probe::changed($constant_names->at(0),6)));
        $member_file = Probe::file('/member.phs','struct Box { public function get(): int { return 1; } } $b Box = new Box(); return $b->get();');
        $member_files /** vector<\parse\Parse_Result> */ = [$member_file]; $members = Probe::collect($member_files,new \collect_symbols\Symbol_Store(1));
        $member_names = Probe::resolve($members,new \resolve_symbols\Resolution_Set(null),$catalog,false);
        Probe::check(!\resolve_symbols\Binding_Coverage::complete(Probe::changed($member_names->at(0),7)));
        $shadow_file = Probe::file('/shadow.phs','template<typename int> struct Box { public int $x; }');
        $shadow_files /** vector<\parse\Parse_Result> */ = [$shadow_file]; $shadow_symbols = Probe::collect($shadow_files,new \collect_symbols\Symbol_Store(1));
        $shadow_names = Probe::resolve($shadow_symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);
        Probe::check(!\resolve_symbols\Binding_Coverage::complete(Probe::changed($shadow_names->at(1),10)));
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i = 0; $i < $catalog->size(); $i++) { $definitions[] = $catalog->definition_at($i); }
        $base = $catalog->entry_return_type;
        $definitions[] = new \type_model\Named_Definition('Box','',$base->representation,$base->lifetime,$base->signed,'',false,false,false);
        $collision = new \type_model\Type_Catalog('probe','collision','language_values',$definitions,$catalog->integer_literal_type,$base,$catalog->boolean_type);
        $collision_plan = new \resolve_symbols\Resolution_Plan($templates,$template_names,$collision,false);
        Probe::check($collision_plan->task_count() === 1);
        $failed = \resolve_symbols\Symbol_Resolver::resolve($templates,$template_names,$collision,false);
        Probe::check(!$failed->valid()); Probe::check($failed->error_reason === 'Duplicate source/provider type: Box');
    }
    public static function bad(string $path, string $reason): void {
        $file = Probe::file('/bad.phs',fs_read_text($path)); $files /** vector<\parse\Parse_Result> */ = [$file];
        $symbols = Probe::collect($files,new \collect_symbols\Symbol_Store(1));
        $update = \resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),Probe::catalog(),false);
        Probe::check(!$update->valid()); Probe::check($update->error_reason === $reason);
    }
    public static function all_good(string $path, int $owners): void {
        $file = Probe::file('/good.phs',fs_read_text($path)); $files /** vector<\parse\Parse_Result> */ = [$file];
        $symbols = Probe::collect($files,new \collect_symbols\Symbol_Store(1));
        $names = Probe::resolve($symbols,new \resolve_symbols\Resolution_Set(null),Probe::catalog(),false);
        Probe::check($names->size() === $owners);
    }
}
