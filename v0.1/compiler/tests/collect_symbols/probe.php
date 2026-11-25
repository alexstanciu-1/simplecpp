<?php
declare(strict_types=1);
namespace collection_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function file(string $path, string $text): \parse\Parse_Result {
        $source = new \read_sources\Source_Buffer();
        $source->path = $path; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        return $file;
    }
    private static function project(\parse\Parse_Result $a, \parse\Parse_Result $b): \parse\Frontend_Set {
        $out = new \parse\Frontend_Set(); $out->add($a); $out->add($b); $out->entry_index = 0; return $out;
    }
    private static function one(string $text): \collect_symbols\Symbol_Refresh {
        $files = new \parse\Frontend_Set(); $files->add(Probe::file('/test.phs', $text)); $files->entry_index = 0;
        return \collect_symbols\Declaration_Collector::collect($files, new \collect_symbols\Symbol_Store(1), false);
    }
    public static function run(): void {
        $a = Probe::file('/a.phs', 'function answer(): int { return 42; } const N: int = 3; struct Box { public int $x; public const function size(): int { return 1; } }');
        $b = Probe::file('/b.phs', 'template<typename T> struct Bag { public T $x; public function get(): T { return $this->x; } } template<typename T> constexpr function identity($x T): T { return $x; }');
        $facts = \collect_symbols\File_Collector::collect_file($a);
        Probe::check($facts->valid);
        Probe::check(q_count($facts->rows) === 5);
        Probe::check($facts->frontend === $a);
        Probe::check((int)$facts->rows[0]->body_node_id === $a->entry);
        Probe::check((int)$facts->rows[0]->declaration_node_id === 0);
        Probe::check(\collect_symbols\File_Collector::name_text($a, (int)$facts->rows[1]->name_node_id) === 'answer');
        Probe::check((int)$facts->rows[4]->owner_declaration_node_id === (int)$facts->rows[3]->declaration_node_id);
        Probe::check($facts->rows[4]->receiver_const);
        $frontends = Probe::project($a, $b);
        $empty = new \collect_symbols\Symbol_Store(1);
        $cold = \collect_symbols\Declaration_Collector::collect($frontends, $empty, false);
        $store = $cold->current;
        Probe::check($cold->valid);
        Probe::check($store->size() === 9);
        Probe::check(q_count($cold->changes) === 9);
        Probe::check($empty->size() === 0);
        Probe::check($empty->next_symbol_id() === 1);
        Probe::check($store->next_symbol_id() === 10);
        $answer = $store->find_symbol('answer', \collect_symbols\SYMBOL_FUNCTION, 0,'');
        $box = $store->find_symbol('Box', \collect_symbols\SYMBOL_STRUCT, 0,'');
        $bag = $store->find_symbol('Bag', \collect_symbols\SYMBOL_TEMPLATE_STRUCT, 0,'');
        $size = $store->find_symbol('size', \collect_symbols\SYMBOL_FUNCTION, $box,'');
        $get = $store->find_symbol('get', \collect_symbols\SYMBOL_TEMPLATE_FUNCTION, $bag,'');
        Probe::check($answer > 0);
        Probe::check($size > 0);
        Probe::check($get > 0);
        Probe::check($store->find_symbol('get', \collect_symbols\SYMBOL_FUNCTION, $bag,'') === 0);
        Probe::check($store->find_symbol('Answer', \collect_symbols\SYMBOL_FUNCTION, 0,'') === 0);
        Probe::check($store->find_symbol('', \collect_symbols\SYMBOL_FILE_ENTRY, 0,'') === 0);
        Probe::check($store->entry_symbol_id('/a.phs') === 1);
        Probe::check($store->entry_symbol_id('/missing') === 0);
        Probe::check(q_count($store->file_symbol_ids('/a.phs')) === 5);
        Probe::check(q_count($store->child_symbol_ids($box)) === 1);
        Probe::check(q_count($store->child_symbol_ids(0)) === 7);
        Probe::check(q_count($store->child_symbol_ids(999)) === 0);
        Probe::check(q_count($store->file_symbol_ids('/missing')) === 0);
        Probe::check($store->symbol_by_id($size)->source_fact()->receiver_const);
        Probe::check($store->symbol_by_id($size)->has_executable_body());
        Probe::check(!$store->symbol_by_id($get)->has_executable_body());
        Probe::check($store->symbol_by_id($get)->is_template());
        Probe::check((int)$store->symbol_by_id($get)->source_fact()->template_parameters_node_id === (int)$store->symbol_by_id($bag)->source_fact()->template_parameters_node_id);
        foreach ($cold->changes as $change) { Probe::check((int)$change->status === \collect_symbols\CHANGE_ADDED); }
        $warm = \collect_symbols\Declaration_Collector::collect($frontends, $store, false);
        Probe::check(q_count($warm->changes) === 0);
        Probe::check($warm->current->symbol_by_id($answer) === $store->symbol_by_id($answer));
        $full = \collect_symbols\Declaration_Collector::collect($frontends, $store, true);
        Probe::check($full->current->find_symbol('answer', \collect_symbols\SYMBOL_FUNCTION, 0,'') === $answer);
        Probe::check(q_count($full->changes) === 9);
        foreach ($full->changes as $change) { Probe::check((int)$change->status === \collect_symbols\CHANGE_UNCOMPARED); }
        $edited_a = Probe::file('/a.phs', 'function answer(): int { return 43; }');
        $edited = \collect_symbols\Declaration_Collector::collect(Probe::project($edited_a, $b), $store, false);
        Probe::check($edited->valid);
        Probe::check($edited->current->find_symbol('answer', \collect_symbols\SYMBOL_FUNCTION, 0,'') === $answer);
        Probe::check($edited->current->symbol_by_id($answer)->source_frontend() === $edited_a);
        Probe::check($edited->current->symbol_by_id($get) === $store->symbol_by_id($get));
        Probe::check(!$edited->current->contains($box));
        Probe::check(q_count($edited->changes) === 5);
        Probe::check((int)$edited->changes[0]->status === \collect_symbols\CHANGE_UNCOMPARED);
        Probe::check((int)$edited->changes[2]->status === \collect_symbols\CHANGE_REMOVED);
        Probe::check($store->size() === 9);
        Probe::check($store->symbol_by_id($answer)->source_frontend() === $a);
        $moved = \collect_symbols\Declaration_Collector::collect(Probe::project($b, Probe::file('/moved.phs', $a->tokens->source->content)), $store, true);
        Probe::check($moved->valid);
        Probe::check($moved->current->find_symbol('answer', \collect_symbols\SYMBOL_FUNCTION, 0,'') === $answer);
        Probe::check($moved->current->find_symbol('size', \collect_symbols\SYMBOL_FUNCTION, $box,'') === $size);
        Probe::check($moved->current->entry_symbol_id('/a.phs') === 0);
        Probe::check($moved->current->entry_symbol_id('/moved.phs') >= $store->next_symbol_id());
        $deleted = \collect_symbols\Declaration_Collector::collect(new \parse\Frontend_Set(), $store, false);
        Probe::check($deleted->current->size() === 0);
        Probe::check(q_count($deleted->changes) === 9);
        $readded = \collect_symbols\Declaration_Collector::collect($frontends, $deleted->current, false);
        Probe::check($readded->current->find_symbol('answer', \collect_symbols\SYMBOL_FUNCTION, 0,'') >= 10);
        $dupe_file = Probe::file('/dupe.phs', 'function answer(): int {}');
        $dupe = \collect_symbols\Declaration_Collector::collect(Probe::project($a, $dupe_file), $store, false);
        Probe::check(!$dupe->valid);
        Probe::check($dupe->current->size() === 0);
        Probe::check(q_count($dupe->changes) === 0);
        Probe::check($dupe->error_path === '/dupe.phs');
        Probe::check($dupe->error_start === 9);
        Probe::check($dupe->error_length === 6);
        Probe::check($dupe->error_reason === 'Duplicate declaration answer; first defined at /a.phs:0');
        Probe::check($dupe->current->next_symbol_id() === 10);
        $reverse_dupe = \collect_symbols\Declaration_Collector::collect(Probe::project($dupe_file, $a), $store, false);
        Probe::check(!$reverse_dupe->valid);
        Probe::check($reverse_dupe->error_path === '/a.phs');
        Probe::check(!Probe::one('function f(): int {} template<typename T> function f(): T {}')->valid);
        Probe::check(!Probe::one('struct S {} template<typename T> struct S {}')->valid);
        Probe::check(Probe::one('function S(): int {} struct S {} const S=1;')->valid);
        Probe::check(!Probe::one('struct S { public function f(): int {} public function f(): int {} }')->valid);
        Probe::check(Probe::one('struct A { public function f(): int {} } struct B { public function f(): int {} }')->valid);
        $unsupported = Probe::one('constexpr function f(): int {}');
        Probe::check(!$unsupported->valid);
        Probe::check($unsupported->error_start === 0);
        Probe::check($unsupported->error_reason === 'Unsupported declaration kind: 30');
        Probe::check($unsupported->current->size() === 0);
        Probe::check(!Probe::one('consteval function f(): int {}')->valid);
        $unknown = false;
        try { $store->symbol_by_id(0); } catch (\LogicException $error) { $unknown = true; }
        Probe::check($unknown);
        $limit = new \collect_symbols\Symbol_Store(4294967295);
        Probe::check($limit->allocate_id() === 4294967295);
        $exhausted = false;
        try { $limit->allocate_id(); } catch (\RuntimeException $error) { $exhausted = true; }
        Probe::check($exhausted);
        Probe::check($limit->next_symbol_id() === 4294967296);
        $invalid = false;
        try { $bad = new \collect_symbols\Symbol_Store(0); } catch (\LogicException $error) { $invalid = true; }
        Probe::check($invalid);
    }
}
