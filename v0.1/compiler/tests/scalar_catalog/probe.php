<?php
declare(strict_types=1);
namespace catalog_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function bad(string $path): void {
        $bad = false;
        try { \load_runtime\Catalog_Syntax::parse(fs_read_text($path)); }
        catch (\InvalidArgumentException $error) { $bad = true; }
        catch (\JsonException $error) { $bad = true; }
        Probe::check($bad);
    }
    public static function bad_integer(string $text): void {
        $bad = false;
        try { json_read($text)->integer(); } catch (\RuntimeException $error) { $bad = true; }
        Probe::check($bad);
    }
    public static function bad_boolean(string $text): void {
        $bad = false;
        try { json_read($text)->boolean(); } catch (\RuntimeException $error) { $bad = true; }
        Probe::check($bad);
    }
    public static function run(): void {
        $content = fs_read_text('inputs/catalog.json');
        $catalog = \load_runtime\Catalog_Syntax::parse($content);
        Probe::check($catalog->size() === 7);
        Probe::check($catalog->provider === 'simple_cpp_language');
        Probe::check($catalog->representation_scope === 'language_values');
        Probe::check($catalog->content_key === 'catalog-v1:' . $content);
        Probe::check($catalog->integer_literal_type === $catalog->entry_return_type);
        Probe::check($catalog->find_type('int','') === $catalog->entry_return_type);
        Probe::check($catalog->find_type('int','elsewhere') === null);
        Probe::check($catalog->find_type('Int','') === null);
        Probe::check($catalog->entry_return_type->representation->bit_width() === 64);
        Probe::check($catalog->entry_return_type->signed === true);
        Probe::check($catalog->entry_return_type->integer_family === 'simple_cpp.integer');
        Probe::check($catalog->entry_return_type->wrapping_addition);
        Probe::check($catalog->entry_return_type->ordered_comparison);
        Probe::check(!$catalog->entry_return_type->struct_field);
        Probe::check((int)$catalog->entry_return_type->lifetime->policy()->copy === 1);
        Probe::check((int)$catalog->entry_return_type->lifetime->policy()->construction === 1);
        Probe::check((int)$catalog->entry_return_type->lifetime->policy()->assignment === 1);
        Probe::check((int)$catalog->entry_return_type->lifetime->policy()->expiring === 1);
        Probe::check($catalog->definition_at(0)->lifetime === null);
        Probe::check($catalog->definition_at(0)->signed === null);
        Probe::check($catalog->definition_at(2)->signed === false);
        Probe::check($catalog->definition_at(2)->struct_field);
        Probe::check($catalog->definition_at(3)->representation->floating_format() === 'ieee_binary64');
        Probe::check($catalog->definition_at(3)->signed === null);
        Probe::check($catalog->boolean_type === $catalog->definition_at(6));
        Probe::check($catalog->boolean_type->signed === false);
        Probe::check($catalog->boolean_type->representation->bit_width() === 1);
        $reader = new \load_runtime\Language_Types($catalog);
        Probe::check($reader->read('inputs/catalog.json') === $catalog);
        $changed = $reader->read('inputs/changed.json');
        Probe::check($changed !== $catalog);
        Probe::check($changed->entry_return_type->name === 'uint32');
        Probe::check($changed->entry_return_type->signed === false);
        Probe::check($changed->integer_literal_type->name === 'int');
        Probe::check($catalog->entry_return_type->name === 'int');
        $without_bool = $reader->read('inputs/no-boolean.json');
        Probe::check($without_bool->boolean_type === null);
        $fresh_reader = new \load_runtime\Language_Types(null);
        Probe::check($fresh_reader->read('inputs/catalog.json')->entry_return_type->name === 'int');
        $source = new \read_sources\Source_Buffer(); $source->path = '/manifest-entry.phs'; $source->content = 'return 0;';
        $frontends = new \parse\Frontend_Set();
        $frontends->add(\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source))); $frontends->entry_index = 0;
        $symbols = \collect_symbols\Declaration_Collector::collect($frontends,new \collect_symbols\Symbol_Store(1),false);
        $selection = \resolve_types\Entry_Preparation::prepare($frontends,$symbols);
        $entry = \resolve_types\Entry_Contract::bind($selection,$catalog);
        Probe::check($entry->symbol === $selection->symbol());
        Probe::check($entry->return_type === $catalog->entry_return_type);
        $new_entry = \resolve_types\Entry_Contract::bind($selection,$changed);
        Probe::check($new_entry->symbol === $entry->symbol);
        Probe::check($new_entry->return_type === $changed->entry_return_type);
        Probe::check($new_entry->return_type !== $entry->return_type);
        Probe::check($new_entry->return_type->representation->bit_width() === 32);
        Probe::check($entry->return_type->representation->bit_width() === 64);
        $bad_return = false;
        try { $bad = new \resolve_types\Entry_Contract($selection->symbol(),$catalog->definition_at(3)); }
        catch (\LogicException $error) { $bad_return = true; }
        Probe::check($bad_return);
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i /** int */ = 0; $i < $catalog->size(); ++$i) { $definitions[] = $catalog->definition_at($i); }
        $fresh = \load_runtime\Catalog_Syntax::parse($content);
        $bad_binding = false;
        try { $bad = new \type_model\Type_Catalog('provider','key','language_values',$definitions,$catalog->integer_literal_type,$fresh->entry_return_type,$catalog->boolean_type); }
        catch (\InvalidArgumentException $error) { $bad_binding = true; }
        Probe::check($bad_binding);
        Probe::check(json_read('0')->integer() === 0);
        Probe::check(json_read('-0')->integer() === 0);
        Probe::check(json_read('9223372036854775807')->integer() === 9223372036854775807);
        Probe::check(json_read('-9223372036854775808')->integer() === -9223372036854775807 - 1);
        Probe::check(json_read('true')->boolean());
        Probe::check(!json_read('false')->boolean());
    }
}
