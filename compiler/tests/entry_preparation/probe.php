<?php
declare(strict_types=1);
namespace entry_test;
final class Probe {
    private static function check(bool $value): void { echo $value ? "true\n" : "false\n"; }
    private static function file(string $path, string $text): \parse\Parse_Result {
        $source = new \read_sources\Source_Buffer(); $source->path = $path; $source->content = $text;
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        return $file;
    }
    private static function project(string $main, string $support): \parse\Frontend_Set {
        $out = new \parse\Frontend_Set();
        $out->add(Probe::file('/not-on-disk/main.phs', $main));
        $out->add(Probe::file('/not-on-disk/support.phs', $support));
        $out->entry_index = 0;
        return $out;
    }
    private static function collect(\parse\Frontend_Set $files): \collect_symbols\Symbol_Refresh {
        return \collect_symbols\Declaration_Collector::collect($files, new \collect_symbols\Symbol_Store(1), false);
    }
    private static function rejects(\parse\Frontend_Set $files, \collect_symbols\Symbol_Refresh $symbols): bool {
        try { \resolve_types\Entry_Preparation::prepare($files, $symbols); }
        catch (\LogicException $error) { return true; }
        return false;
    }
    private static function no_symbol(\resolve_types\Entry_Selection $selection): bool {
        try { $selection->symbol(); } catch (\LogicException $error) { return true; }
        return false;
    }
    public static function run(): void {
        $files = Probe::project('return answer();', 'function answer(): int { return 42; } const N=3; struct S { public int $x; public function f(): int { return 1; } }');
        $collection = Probe::collect($files);
        $entry = \resolve_types\Entry_Preparation::prepare($files, $collection);
        Probe::check($entry->valid);
        Probe::check($entry->symbol() === $collection->current->symbol_by_id($collection->current->entry_symbol_id('/not-on-disk/main.phs')));
        Probe::check($entry->symbol()->frontend === $files->files[0]);
        Probe::check((int)$entry->symbol()->declaration->declaration_node_id === 0);
        Probe::check((int)$entry->symbol()->declaration->body_node_id === $files->files[0]->entry);
        Probe::check($entry->symbol()->name === '');
        Probe::check($entry->error_path === '');
        $warm = \resolve_types\Entry_Preparation::prepare($files, $collection);
        Probe::check($warm->symbol() === $entry->symbol());
        $full = \collect_symbols\Declaration_Collector::collect($files, $collection->current, true);
        $full_entry = \resolve_types\Entry_Preparation::prepare($files, $full);
        Probe::check($full_entry->symbol()->symbol_id === $entry->symbol()->symbol_id);
        Probe::check($full_entry->symbol() !== $entry->symbol());
        $reordered = new \parse\Frontend_Set();
        $reordered->add($files->files[1]); $reordered->add($files->files[0]); $reordered->entry_index = 1;
        Probe::check(\resolve_types\Entry_Preparation::prepare($reordered, $collection)->symbol() === $entry->symbol());
        $empty = Probe::project('', '/* only a comment */');
        $empty_symbols = Probe::collect($empty);
        Probe::check(\resolve_types\Entry_Preparation::prepare($empty, $empty_symbols)->valid);
        $empty->entry_index = 1;
        $switched = \resolve_types\Entry_Preparation::prepare($empty, $empty_symbols);
        Probe::check($switched->symbol()->frontend === $empty->files[1]);
        Probe::check($switched->symbol()->symbol_id !== $empty_symbols->current->entry_symbol_id('/not-on-disk/main.phs'));
        // This phase does not type-check entry returns or resolve references.
        $untyped = Probe::project('return unknown_name;', '');
        Probe::check(\resolve_types\Entry_Preparation::prepare($untyped, Probe::collect($untyped))->valid);
        $bad = Probe::project('return 0;', '/* é */ return 1; echo 2;');
        $bad_symbols = Probe::collect($bad);
        $failure = \resolve_types\Entry_Preparation::prepare($bad, $bad_symbols);
        Probe::check(!$failure->valid);
        Probe::check($failure->error_path === '/not-on-disk/support.phs');
        Probe::check($failure->error_start === 9);
        Probe::check($failure->error_length === 9);
        Probe::check($failure->error_reason === 'Executable top-level code outside the manifest entry is unsupported; initialization ordering is not defined');
        Probe::check(Probe::no_symbol($failure));
        Probe::check($bad_symbols->current->size() === 2);
        $bad->entry_index = 1;
        $reverse = \resolve_types\Entry_Preparation::prepare($bad, $bad_symbols);
        Probe::check(!$reverse->valid);
        Probe::check($reverse->error_path === '/not-on-disk/main.phs');
        $moved = Probe::project('', 'return 9;');
        $moved_symbols = Probe::collect($moved);
        Probe::check(!\resolve_types\Entry_Preparation::prepare($moved, $moved_symbols)->valid);
        $moved->entry_index = 1;
        Probe::check(\resolve_types\Entry_Preparation::prepare($moved, $moved_symbols)->valid);
        $block = Probe::project('', '{}');
        Probe::check(!\resolve_types\Entry_Preparation::prepare($block, Probe::collect($block))->valid);
        $branch = Probe::project('', 'if (false) { echo 1; }');
        Probe::check(!\resolve_types\Entry_Preparation::prepare($branch, Probe::collect($branch))->valid);
        $repaired = Probe::project('return answer();', 'function answer(): int { return 3; }');
        $repair_symbols = \collect_symbols\Declaration_Collector::collect($repaired, $collection->current, false);
        $repair = \resolve_types\Entry_Preparation::prepare($repaired, $repair_symbols);
        Probe::check($repair->valid);
        Probe::check($repair->symbol()->symbol_id === $entry->symbol()->symbol_id);
        Probe::check($repair->symbol()->frontend === $repaired->files[0]);
        Probe::check($entry->symbol()->frontend === $files->files[0]);
        Probe::check(Probe::rejects($repaired, $collection));
        Probe::check(Probe::rejects($files, $repair_symbols));
        $missing = new \parse\Frontend_Set();
        Probe::check(Probe::rejects($missing, $collection));
        $files->entry_index = -1;
        Probe::check(Probe::rejects($files, $collection));
        $files->entry_index = 2;
        Probe::check(Probe::rejects($files, $collection));
        $files->entry_index = 0;
        $files->valid = false;
        Probe::check(Probe::rejects($files, $collection));
        $files->valid = true;
        $collection->valid = false;
        Probe::check(Probe::rejects($files, $collection));
        $collection->valid = true;
        $subset = new \parse\Frontend_Set(); $subset->add($files->files[0]); $subset->entry_index = 0;
        Probe::check(Probe::rejects($subset, $collection));
        $subset_symbols = Probe::collect($subset);
        Probe::check(Probe::rejects($files, $subset_symbols));
        $files->files[] = $files->files[0];
        Probe::check(Probe::rejects($files, $collection));
        $truncated = Probe::project('return 0;', '');
        $truncated_symbols = Probe::collect($truncated);
        $one /** vector<\parse\Parse_Result> */ = [];
        $one[] = $truncated->files[0];
        $truncated->files = $one;
        Probe::check(Probe::rejects($truncated, $truncated_symbols));
        $named_rejected = false;
        try {
            $wrong = new \resolve_types\Entry_Selection($collection->current, $collection->current->find_symbol('answer', \collect_symbols\SYMBOL_FUNCTION, 0));
        } catch (\LogicException $error) { $named_rejected = true; }
        Probe::check($named_rejected);
    }
}
