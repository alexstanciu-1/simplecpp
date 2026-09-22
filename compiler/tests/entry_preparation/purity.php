<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';
function check(bool $value, string $reason): void { if (!$value) { throw new LogicException($reason); } }
function project(string $support): \parse\Frontend_Set {
    $out = new \parse\Frontend_Set();
    foreach (['/unopened/main.phs'=>'return 0;', '/unopened/support.phs'=>$support] as $path=>$text) {
        $source = new \read_sources\Source_Buffer(); $source->path=$path; $source->content=$text;
        $out->add(\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source)));
    }
    $out->entry_index=0; return $out;
}
$files=project('function answer(): int { return 42; }');
$symbols=\collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false);
$before=serialize([$files,$symbols]);
$entry=\resolve_types\Entry_Preparation::prepare($files,$symbols);
check($entry->valid, 'Successful preparation');
check(serialize([$files,$symbols])===$before, 'Success preserves all input/index/AST state');
$again=\resolve_types\Entry_Preparation::prepare($files,$symbols);
check($again->symbol()===$entry->symbol(), 'Repeated preparation shares exact symbol');
check(serialize($again)===serialize($entry), 'Repeated result is deterministic');
$bad=project('echo 1;');
$bad_symbols=\collect_symbols\Declaration_Collector::collect($bad,$symbols->current,false);
$bad_before=serialize([$bad,$bad_symbols]);
$failed=\resolve_types\Entry_Preparation::prepare($bad,$bad_symbols);
check(!$failed->valid, 'Supporting code rejected');
check(serialize([$bad,$bad_symbols])===$bad_before, 'Source rejection preserves current snapshot');
check(serialize([$files,$symbols])===$before, 'Source rejection preserves previous snapshot');
try { \resolve_types\Entry_Preparation::prepare($bad,$symbols); throw new RuntimeException('Stale symbols accepted'); } catch (LogicException $e) {}
check(serialize([$files,$symbols])===$before, 'Stale rejection preserves original inputs');
check($entry->symbol()->frontend===$files->files[0], 'Earlier selection remains attached to old snapshot');
echo "Entry preparation purity: 9 assertions passed\n";
