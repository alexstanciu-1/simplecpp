<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';
function check(bool $value, string $reason): void { if (!$value) { throw new LogicException($reason); } }
function project(string $text): \tokenize\Lexical_Project {
    $source = new \read_sources\Source_Buffer(); $source->path = '/main.phs'; $source->content = $text;
    $current = new \tokenize\Lexical_Project(); $current->buffers[] = \tokenize\File_Tokenizer::tokenize($source);
    return $current;
}
$initial = project('return 1;');
$previous = \parse\Parser::parse($initial, new \parse\Frontend_Set(), false);
$before = serialize([$initial, $previous]);
$current = project('return 2;'); $current_before = serialize($current);
$plan = \parse\Parser_Selection::select($current, $previous, false);
$join = new \parse\Frontend_Join($plan);
try { $join->finish(); } catch (LogicException $error) {}
$prepared = serialize($join);
$result = \parse\File_Parser::parse($current->buffers[0]);
try { $join->merge([$result, $result], 0, 2); throw new RuntimeException('Expected duplicate rejection'); } catch (LogicException $error) {}
check(serialize($join) === $prepared, 'Rejected segment preserves prepared accumulator');
$output = $join->join([$result]); $published = serialize($output);
check(serialize($join->finish()) === $published, 'Repeated finish is deterministic');
check($output->files[0] === $result, 'Join retains exact worker result');
check(serialize($current) === $current_before, 'Current tokens/source are unchanged');
check(serialize([$initial, $previous]) === $before, 'Previous tokens/source/trees are unchanged');
$equal_bytes = project('return 1;');
$rebound = \parse\Parser::parse($equal_bytes, $previous, false);
check($rebound->files[0]->tree === $previous->files[0]->tree && $rebound->files[0]->tokens === $equal_bytes->buffers[0], 'Reuse combines old immutable syntax with current input owner');
check(serialize([$initial, $previous]) === $before, 'Rebinding preserves previous snapshot');
$failure = \parse\Parser::parse(project('return; function broken(): int {'), $previous, false);
check(!$failure->valid && $failure->files === [] && $failure->entry_index === -1, 'Failed project exposes no partial membership');
check(serialize([$initial, $previous]) === $before, 'Later failure preserves previous snapshot');
echo "Parser project purity: 9 assertions passed\n";
