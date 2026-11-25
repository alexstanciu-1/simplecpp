<?php
declare(strict_types=1);
namespace lexical_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function store(string $text): \collect_symbols\Symbol_Store {
        $source = new \read_sources\Source_Buffer(); $source->path = '/names.phs'; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException('Parse: ' . $file->error_reason); }
        $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0;
        $refresh = \collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false);
        if (!$refresh->valid) { throw new \LogicException('Collect: ' . $refresh->error_reason); }
        return $refresh->current;
    }
    private static function catalog(): \type_model\Type_Catalog { return \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json')); }
    public static function bind(\collect_symbols\Symbol_Store $store, int $id): \resolve_symbols\Symbol_Resolution {
        $worker = new \resolve_symbols\Resolution_Worker($store,$store->symbol_by_id($id),Probe::catalog());
        $attempt = $worker->run(); if (!$attempt->valid()) { throw new \LogicException($attempt->error_reason); } return $attempt->result();
    }
    public static function run(): void {
        $source = '$x int = answer(); { $y int32 = $x; $x = $y; { $x uint32 = value(); $x = $x; } $y = $x; } { $x int = 8; $x; } return $x; function answer(): int { $x int = 42; return $x; } function value(): int { return 3; }';
        $store = Probe::store($source); $entry = $store->entry_symbol_id('/names.phs'); $result = Probe::bind($store,$entry);
        Probe::check($result->owner === $store->symbol_by_id($entry));
        Probe::check($result->scopes_count() === 4); Probe::check($result->locals_count() === 4); Probe::check($result->uses_count() === 9);
        $parents /** vector<int> */ = [0,1,2,1]; $scopes /** vector<int> */ = [1,2,3,4];
        foreach ($parents as $index => $parent) {
            $scope = $result->scopes_at($index); Probe::check((int)$scope->parent_scope_id === $parent);
            Probe::check($result->scope_for_block((int)$scope->block_node_id) === $index+1);
            $local = $result->locals_at($index); Probe::check((int)$local->scope_id === $scopes[$index]);
            Probe::check($result->local_for_declaration((int)$local->declaration_node_id) === $index+1);
        }
        $ids /** vector<int> */ = [1,1,2,3,3,2,1,4,1]; $access /** vector<int> */ = [1,2,1,2,1,2,1,1,1];
        foreach ($ids as $index => $id) {
            $use = $result->uses_at($index); Probe::check((int)$use->local_id === $id); Probe::check((int)$use->access === $access[$index]);
            Probe::check((int)$result->binding_for((int)$use->use_node_id)->local_id === $id);
        }
        Probe::check($result->calls_count() === 2);
        Probe::check((int)$result->calls_at(0)->target_symbol_id === $store->find_symbol('answer',\collect_symbols\SYMBOL_FUNCTION,0,''));
        Probe::check((int)$result->calls_at(1)->target_symbol_id === $store->find_symbol('value',\collect_symbols\SYMBOL_FUNCTION,0,''));
        Probe::check($result->runtime_parameter_count() === 0);
        $answer = Probe::bind($store,$store->find_symbol('answer',\collect_symbols\SYMBOL_FUNCTION,0,''));
        Probe::check($answer->locals_count() === 1); Probe::check((int)$answer->uses_at(0)->local_id === 1);
        $copy = $result->locals_at(0); $copy->scope_id = 999; Probe::check((int)$result->locals_at(0)->scope_id === 1);
        $copy_scope = $result->scopes_at(0); $copy_scope->parent_scope_id = 999; Probe::check((int)$result->scopes_at(0)->parent_scope_id === 0);
        $copy_use = $result->uses_at(0); $copy_use->local_id = 999; Probe::check((int)$result->uses_at(0)->local_id === 1);
        $worker = new \resolve_symbols\Resolution_Worker($store,$store->symbol_by_id($entry),Probe::catalog()); $worker->run();
        $bad = false; try { $worker->run(); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
        $bad = false; try { $result->local_for(0); } catch (\InvalidArgumentException $e) { $bad = true; } Probe::check($bad);
        $bad = false; try { $result->binding_for(0); } catch (\InvalidArgumentException $e) { $bad = true; } Probe::check($bad);
        $bad = false; try { $result->parameter_for(1); } catch (\InvalidArgumentException $e) { $bad = true; } Probe::check($bad);
        $control = Probe::store('$x int = 1; if ($x) { $y int = $x; } else { $y int = $x; } while ($x) { $x = $x; } return $x;');
        $branches = Probe::bind($control,$control->entry_symbol_id('/names.phs'));
        Probe::check($branches->scopes_count() === 4); Probe::check($branches->uses_count() === 7);
        Probe::check((int)$branches->uses_at(6)->local_id === 1);
        $indexed = Probe::store('$a int = 0; $i int = 1; $j int = 2; $a[$i][$j] = $a[$j][$i];');
        $places = Probe::bind($indexed,$indexed->entry_symbol_id('/names.phs'));
        $places_ids /** vector<int> */ = [1,2,3,1,3,2];
        foreach ($places_ids as $index => $id) { Probe::check((int)$places->uses_at($index)->local_id === $id); }
        Probe::check((int)$places->uses_at(0)->access === \resolve_symbols\LOCAL_WRITE);
        $method_store = Probe::store('struct Box { public int $x; public function get($n int): int { return $this->x + $n; } } $b Box = new Box(); return $b->get(2);');
        $box = $method_store->find_symbol('Box',\collect_symbols\SYMBOL_STRUCT,0,'');
        $method = Probe::bind($method_store,$method_store->find_symbol('get',\collect_symbols\SYMBOL_FUNCTION,$box,''));
        Probe::check($method->runtime_parameter_count() === 2); Probe::check($method->parameter_for(1)->receiver);
        Probe::check(!$method->parameter_for(2)->receiver); Probe::check((int)$method->uses_at(0)->local_id === 1);
        $call = Probe::bind($method_store,$method_store->entry_symbol_id('/names.phs'));
        Probe::check($call->members_count() === 1); Probe::check($call->calls_count() === 0);
        Probe::check((int)$call->binding_for((int)$call->members_at(0)->receiver_node_id)->local_id === 1);
        $templates = Probe::store('template<typename T, T N> struct Box { public T $x; } template<typename T> function identity($x T): T { return $x; } $box Box<int, 3> = new Box<int, 3>(); return identity<int>(3);');
        $template_binding = Probe::bind($templates,$templates->find_symbol('Box',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));
        Probe::check($template_binding->parameters_count() === 2); Probe::check((int)$template_binding->parameters_at(0)->contract === \type_model\GENERIC_COPYABLE_VALUE);
        Probe::check((int)$template_binding->parameters_at(1)->contract === \type_model\GENERIC_NONE);
        Probe::check($template_binding->names_at(0)->target_id === 0); Probe::check($template_binding->names_at(1)->target_id === 0);
        $template_entry = Probe::bind($templates,$templates->entry_symbol_id('/names.phs'));
        Probe::check($template_entry->applications_count() === 3);
        Probe::check($template_entry->applications_at(0)->definition === $templates->symbol_by_id($templates->find_symbol('Box',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,'')));
        $function = Probe::bind($templates,$templates->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''));
        Probe::check($function->runtime_parameter_count() === 1); Probe::check($function->parameters_count() === 1);
        $constant_store = Probe::store('const N: int = 1; const K: int = N; { const N: int = 2; echo N; } return N;');
        $constants = Probe::bind($constant_store,$constant_store->entry_symbol_id('/names.phs'));
        Probe::check($constants->constants_count() === 1);
        $local_constant = false; $project_constant = false;
        for ($index = 0; $index < $constants->names_count(); $index++) {
            $name = $constants->names_at($index);
            if ($name->kind === \resolve_symbols\REFERENCE_LOCAL_CONSTANT) { $local_constant = true; }
            if ($name->kind === \resolve_symbols\REFERENCE_PROJECT_CONSTANT) { $project_constant = true; }
        }
        Probe::check($local_constant); Probe::check($project_constant);
        $permissions /** vector<bool> */ = [false,true,true,false,true];
        foreach ($permissions as $index => $permission) { Probe::check(\type_model\Generic_Contracts::permits(\type_model\GENERIC_COPYABLE_VALUE,$index+1) === $permission); }
    }
    public static function all_good(string $path, int $owners): void {
        $store = Probe::store(fs_read_text($path)); Probe::check($store->size() === $owners);
        for ($index = 0; $index < $store->size(); $index++) {
            $record = $store->record_at($index); $result = Probe::bind($store,$record->symbol_id);
            Probe::check($result->owner === $record);
        }
    }
    public static function deep(string $path): void {
        $store = Probe::store(fs_read_text($path)); $result = Probe::bind($store,$store->entry_symbol_id('/names.phs'));
        Probe::check($result->locals_count() === 1001); Probe::check($result->scopes_count() === 129);
        Probe::check($result->uses_count() === 1003); Probe::check((int)$result->uses_at(1001)->local_id === 1001);
        Probe::check((int)$result->uses_at(1002)->local_id === 1);
    }
    public static function bad(string $path, string $owner, int $kind, int $start, int $length, string $reason): void {
        $store = Probe::store(fs_read_text($path)); $id = $store->entry_symbol_id('/names.phs');
        if ($owner !== '') { $id = $store->find_symbol($owner,$kind,0,''); }
        $worker = new \resolve_symbols\Resolution_Worker($store,$store->symbol_by_id($id),Probe::catalog()); $attempt = $worker->run();
        Probe::check(!$attempt->valid()); Probe::check($attempt->error_path === '/names.phs');
        Probe::check($attempt->error_start === $start); Probe::check($attempt->error_length === $length); Probe::check($attempt->error_reason === $reason);
        $bad = false; try { $attempt->result(); } catch (\LogicException $e) { $bad = true; } Probe::check($bad);
    }
}
