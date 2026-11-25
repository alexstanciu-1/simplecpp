<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';
function check(bool $value, string $reason): void { if (!$value) { throw new LogicException($reason); } }
function project(string $text): \parse\Frontend_Set {
    $source = new \read_sources\Source_Buffer(); $source->path = '/main.phs'; $source->content = $text;
    $out = new \parse\Frontend_Set();
    $out->add(\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source))); $out->entry_index = 0; return $out;
}
$frontends = project('struct A { public int $x; public function f(): int { return 1; } }');
$before = serialize($frontends);
$first = \collect_symbols\Declaration_Collector::collect($frontends, new \collect_symbols\Symbol_Store(1), false);
$baseline = serialize($first);
check(serialize($frontends) === $before, 'Cold extraction preserves source/tree');
$warm = \collect_symbols\Declaration_Collector::collect($frontends, $first->current, false);
check(serialize($warm->current) === serialize($first->current), 'Warm store preserves all indexes');
$full = \collect_symbols\Declaration_Collector::collect($frontends, $first->current, true);
check(serialize($full->current) === serialize($first->current), 'Full extraction has equal records/indexes');
check(serialize($first) === $baseline, 'Success preserves baseline');
$bad = project('struct A {} struct A {}'); $bad_before = serialize($bad);
$failed = \collect_symbols\Declaration_Collector::collect($bad, $first->current, false);
check(!$failed->valid && $failed->current->size() === 0 && $failed->changes === [], 'No failed partial store');
check(serialize($first) === $baseline && serialize($bad) === $bad_before, 'Failure preserves both snapshots');
$rebound = project($frontends->files[0]->tokens->source->content);
$again = \collect_symbols\Declaration_Collector::collect($rebound, $first->current, false);
check(count($again->changes) === 3 && $again->current->record_at(1)->source_frontend() === $rebound->files[0], 'Equal bytes with fresh frontend retain identity but need comparison');
check(serialize($again->current) === serialize($first->current), 'Fresh frontend yields equal source facts');
$invalid = project(''); $invalid->valid = false;
try { \collect_symbols\Declaration_Collector::collect($invalid, $first->current, false); throw new RuntimeException('Accepted invalid frontend set'); } catch (LogicException $error) {}
check(serialize($first) === $baseline, 'Invalid input preserves baseline');
echo "Source collection purity: 9 assertions passed\n";
