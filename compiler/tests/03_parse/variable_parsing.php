<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/bootstrap.php';

use compile\Phases;
use compile\Update_Context;
use read_sources\Source_Reader;
use parse\File_Parser;
use parse\Frontend_Join;
use parse\Syntax_Comparer;
use collect_symbols\Declaration_Collector;
use collect_symbols\Symbol_Comparer;

class Variable_Parsing_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    /** Require a failed operation to report the expected diagnostic. */
    public static function rejects(callable $action, string $reason): void
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $reason), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $reason);
    }

    public static function syntax(string $text): \parse\File_Frontend
    {
        return (new File_Parser(\tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(1, 'comparison.phs', 0, $text))))->parse();
    }
}

$manifest_path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest_path);
$baseline = $session->observed;
$before = serialize($baseline);
$manifest = $first->inputs->manifest;
$main = $manifest->directory . '/src/main.phs';
$value = $manifest->directory . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
$main_id = $first->inputs->sources->find_file_id($main);
$value_id = $first->inputs->sources->find_file_id($value);
$body = '$count int = 42; { $copy Named = $count; $count = $copy; } return $count;';
Variable_Parsing_Test::edit($main, $body);
Variable_Parsing_Test::edit($value, 'function value(): int { $local int = answer(); return $local; }');
$update = new Update_Context();
$sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $first->inputs->sources));
$lexical = Phases::run_tokenization($sources, $first->inputs->tokens, $update);
// Explicit changed-file batch for independent workers; compare with the phase below.
$tasks = [$lexical->tokens->for_file($main_id), $lexical->tokens->for_file($value_id)];
$fixed = serialize([$lexical, $tasks, $first->inputs->frontends]);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new File_Parser($task))->parse();
}
$join = new Frontend_Join($first->inputs->frontends, $lexical->sources, $lexical->tokens, $tasks);
$join->merge($results, 1, 1);
Variable_Parsing_Test::rejects(static fn() => $join->finish(), 'Incomplete');
$join->merge($results, 0, 1);
$parsed = $join->finish();
$selected = Phases::run_parsing($lexical->sources, $lexical->tokens, $first->inputs->frontends, $update);
Variable_Parsing_Test::check($selected->to_json() === $parsed->to_json(), 'Phase selection equals the explicit changed-file batch');
Variable_Parsing_Test::check((serialize([$lexical, $tasks, $first->inputs->frontends]) === $fixed)
    && (serialize($baseline) === $before), 'Reversed workers and segmented join preserve fixed inputs');
foreach ($first->inputs->sources->files as $file) {
    Variable_Parsing_Test::check(($selected->for_file($file->id) !== $first->inputs->frontends->for_file($file->id))
        === in_array($file->id, [$main_id, $value_id], true), 'Only edited files replace their AST');
}
$warm = Phases::run_parsing($lexical->sources, $lexical->tokens, $parsed, $update);
foreach ($lexical->sources->files as $file) {
    Variable_Parsing_Test::check($warm->for_file($file->id) === $parsed->for_file($file->id), 'Unchanged variable ASTs need no work');
}
$full_update = new Update_Context();
$full_update->full_rebuild = true;
$full = Phases::run_parsing($lexical->sources, $lexical->tokens, $parsed, $full_update);
Variable_Parsing_Test::check(($full->to_json() === $parsed->to_json())
    && ($full->for_file($main_id) !== $parsed->for_file($main_id)), 'Full selection produces equivalent ASTs through the same workers');

$collected = \Step_Test::run(new Declaration_Collector($first->symbols->current, $lexical->sources, $parsed, false));
$compared = \Step_Test::run(new Symbol_Comparer($collected, false));
Variable_Parsing_Test::check((count($compared->current->records()) === count($first->symbols->current->records()))
    && (count($compared->changes) === 2), 'Locals stay inside callable bodies without project symbol rows');
foreach ($compared->changes as $change) {
    Variable_Parsing_Test::check(($change->own_status === \collect_symbols\change_status::unchanged) && ($change->children_changed === true),
        'Local syntax changes affect child content, not the callable definition');
}

$plain = Variable_Parsing_Test::syntax($body);
$space = Variable_Parsing_Test::syntax('/* moved */ ' . str_replace(';', ";\n", $body));
$plain_before = serialize($plain);
Variable_Parsing_Test::check(Syntax_Comparer::equal($plain, $plain->entry_body_id, $space, $space->entry_body_id),
    'Variable syntax comparison ignores comments and positions');
foreach ([
        str_replace('$count', '$total', $body),
        str_replace('Named', 'Other', $body),
        str_replace('42', '43', $body),
        str_replace('$count = $copy', '$copy = $count', $body),
        str_replace(['{ ', ' }'], ['', ''], $body),
    ] as $changed) {
    $other = Variable_Parsing_Test::syntax($changed);
    Variable_Parsing_Test::check(!Syntax_Comparer::equal($plain, $plain->entry_body_id, $other, $other->entry_body_id),
        'Names, annotations, initializer values, assignment roles and block nesting contribute to logical equality');
}
Variable_Parsing_Test::check(serialize($plain) === $plain_before, 'Comparison never mutates syntax');

Variable_Parsing_Test::edit($main, '$count int = ;');
$broken_sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $lexical->sources));
$broken_tokens = Phases::run_tokenization($broken_sources, $lexical->tokens, $update);
$parsed_before = serialize($parsed);
Variable_Parsing_Test::rejects(static fn() => Phases::run_parsing($broken_tokens->sources, $broken_tokens->tokens, $parsed, $update),
    'Expected literal');
Variable_Parsing_Test::check(serialize($parsed) === $parsed_before, 'Failed parsing cannot replace an accepted frontend');
Variable_Parsing_Test::edit($main, str_replace('42', '43', $body));
$repair_sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $lexical->sources));
$repair_tokens = Phases::run_tokenization($repair_sources, $lexical->tokens, $update);
$repair = Phases::run_parsing($repair_tokens->sources, $repair_tokens->tokens, $parsed, $update);
Variable_Parsing_Test::check(($repair->for_file($main_id) !== $parsed->for_file($main_id))
    && ($repair->for_file($value_id) === $parsed->for_file($value_id)), 'Repair uses the same file replacement boundary');
Variable_Parsing_Test::rejects(static fn() => $session->compile($manifest_path), "Unknown or unsupported local type 'Named'");

Variable_Parsing_Test::check(($session->observed === $baseline) && ($session->published === null)
    && (serialize($baseline) === $before), 'Later-stage rejection preserves the entire compiler snapshot');

// Plain scalar blocks now pass lifetime analysis and the existing lowering path.
Variable_Parsing_Test::edit($value, $original_value);
Variable_Parsing_Test::edit($main, '{ return 42; }');
$nested = $session->compile($manifest_path);
$entry = $nested->types->entry->symbol->symbol_id;
Variable_Parsing_Test::check(($nested->lifetimes->for_symbol($entry)->reachable_statement_count === 1)
    && (!$nested->lifetimes->for_symbol($entry)->falls_through), 'Nested return has real downstream analysis');
Variable_Parsing_Test::edit($main, $original_main);
Variable_Parsing_Test::check($session->compile($manifest_path)->llvm->ir_by_file() === $first->llvm->ir_by_file(), 'Supported code compiles again after repair');
echo "variable parsing ok: flat ASTs, local ownership, fixed workers, joins, reuse, comparison, errors and semantic boundaries\n";
