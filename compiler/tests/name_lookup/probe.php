<?php
declare(strict_types=1);
namespace lookup_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $source = new \read_sources\Source_Buffer(); $source->path = '/lookup.phs';
        $source->content = 'function answer(): int { return 42; } const N: int = 3; struct Box { public int $x; public function size(): int { return 1; } } template<typename T> struct Bag { public T $x; } template<typename T> function identity($x T): T { return $x; }';
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0;
        $refresh = \collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false);
        if (!$refresh->valid) { throw new \LogicException($refresh->error_reason); }
        $store = $refresh->current;
        $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $integer = \resolve_symbols\Declaration_Lookup::find($store,$catalog,'int',1,\resolve_symbols\NAME_TYPE,'');
        Probe::check($integer !== null);
        Probe::check($integer->provided_type === $catalog->entry_return_type);
        Probe::check($integer->target_id === 0);
        Probe::check($integer->kind === \resolve_symbols\REFERENCE_PROVIDED_TYPE);
        $box = \resolve_symbols\Declaration_Lookup::find($store,$catalog,'Box',2,\resolve_symbols\NAME_TYPE,'');
        Probe::check($box->target_id === $store->find_symbol('Box',\collect_symbols\SYMBOL_STRUCT,0,''));
        Probe::check($box->provided_type === null);
        Probe::check($box->kind === \resolve_symbols\REFERENCE_SOURCE_TYPE);
        $bag = \resolve_symbols\Declaration_Lookup::find($store,$catalog,'Bag',3,\resolve_symbols\NAME_TYPE_FAMILY,'');
        Probe::check($bag->target_id === $store->find_symbol('Bag',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));
        Probe::check($bag->kind === \resolve_symbols\REFERENCE_TEMPLATE_TYPE);
        $constant = \resolve_symbols\Declaration_Lookup::find($store,$catalog,'N',4,\resolve_symbols\NAME_VALUE,'');
        Probe::check($constant->target_id === $store->find_symbol('N',\collect_symbols\SYMBOL_CONSTANT,0,''));
        Probe::check($constant->kind === \resolve_symbols\REFERENCE_PROJECT_CONSTANT);
        Probe::check(\resolve_symbols\Declaration_Lookup::find($store,$catalog,'N',1,\resolve_symbols\NAME_TYPE,'') === null);
        Probe::check(\resolve_symbols\Declaration_Lookup::find($store,$catalog,'Box',1,\resolve_symbols\NAME_VALUE,'') === null);
        Probe::check(\resolve_symbols\Declaration_Lookup::find($store,$catalog,'Bag',1,\resolve_symbols\NAME_TYPE,'') === null);
        Probe::check(\resolve_symbols\Declaration_Lookup::find($store,$catalog,'Box',1,\resolve_symbols\NAME_TYPE_FAMILY,'') === null);
        Probe::check(\resolve_symbols\Declaration_Lookup::find($store,$catalog,'Int',1,\resolve_symbols\NAME_TYPE,'') === null);
        Probe::check(\resolve_symbols\Declaration_Lookup::find($store,$catalog,'missing',1,\resolve_symbols\NAME_VALUE,'') === null);
        $other = \resolve_symbols\Declaration_Lookup::find($store,$catalog,'int',90,\resolve_symbols\NAME_TYPE,'');
        Probe::check($integer->same_target($other));
        $fresh = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $rebound = \resolve_symbols\Declaration_Lookup::find($store,$fresh,'int',1,\resolve_symbols\NAME_TYPE,'');
        Probe::check(!$integer->same_target($rebound));
        Probe::check(!$box->same_target($bag));
        $zero = new \resolve_symbols\Name_Binding(1,\resolve_symbols\NAME_TYPE,\resolve_symbols\REFERENCE_TEMPLATE_PARAMETER,0,null);
        Probe::check($zero->target_id === 0);
        foreach ($store->child_symbol_ids(0) as $id) {
            $record = $store->symbol_by_id($id);
            if ($record->kind() === \collect_symbols\SYMBOL_FUNCTION) {
                Probe::check(\resolve_symbols\Function_Lookup::find($record,(int)$record->source_fact()->name_node_id,$store) === $id);
            }
            if ($record->kind() === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) {
                Probe::check(\resolve_symbols\Function_Lookup::find($record,(int)$record->source_fact()->name_node_id,$store) === $id);
            }
        }
        $member = $store->symbol_by_id($store->find_symbol('size',\collect_symbols\SYMBOL_FUNCTION,$box->target_id,''));
        Probe::check(\resolve_symbols\Function_Lookup::find($member,(int)$member->source_fact()->name_node_id,$store) === 0);
        $bad = false;
        try { \resolve_symbols\Declaration_Lookup::find($store,$catalog,'missing',1,0,''); } catch (\InvalidArgumentException $e) { $bad = true; }
        Probe::check($bad);
        $bad = false;
        try { \resolve_symbols\Function_Lookup::find($member,(int)$member->source_fact()->body_node_id,$store); } catch (\InvalidArgumentException $e) { $bad = true; }
        Probe::check($bad);
    }
    public static function invalid(int $node, int $role, int $kind, int $target): void {
        $bad = false;
        try { $binding = new \resolve_symbols\Name_Binding($node,$role,$kind,$target,null); } catch (\InvalidArgumentException $e) { $bad = true; }
        Probe::check($bad);
    }
}
