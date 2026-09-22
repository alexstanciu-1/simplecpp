<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/bootstrap.php';

use compile\Phases;
use compile\Update_Context;
use read_sources\Source_Reader;
use tokenize\Tokenizer;

class Variable_Tokens_Test
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

    public static function rejects(callable $action, string $reason): void
    {
        try {
            $action();
        }
        catch (\diagnostics\Source_Error $error) {
            self::check(str_contains($error->getMessage(), $reason), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $reason);
    }
}

$path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$first = $session->compile($path);
$baseline = $session->observed;
$before = serialize($baseline);
$manifest = $first->inputs->manifest;
$main = $manifest->directory . '/src/main.phs';
$value = $manifest->directory . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
$main_id = $first->inputs->sources->find_file_id($main);
$value_id = $first->inputs->sources->find_file_id($value);
Variable_Tokens_Test::edit($main, '$count int = 42; { $next int = $count; $count = $next; } return $count;');
Variable_Tokens_Test::edit($value, 'function value(): int { $result int = 42; return $result; }');

$sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $first->inputs->sources));
$candidate = Phases::run_tokenization($sources, $first->inputs->tokens, new Update_Context());
$tasks = \tokenize\Token_Selection::select($candidate->sources, $first->inputs->tokens, false);
Variable_Tokens_Test::check(count($tasks) === 2, 'Only changed files require token work');
$fixed = serialize([$candidate->sources, $tasks]);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = \tokenize\File_Tokenizer::tokenize($task);
}
$joined = (new \tokenize\Token_Join($candidate->sources, $first->inputs->tokens, $tasks))->join($results);
Variable_Tokens_Test::check(($joined->to_json() === $candidate->tokens->to_json())
    && (serialize([$candidate->sources, $tasks]) === $fixed) && (serialize($baseline) === $before),
    'Independent workers join deterministically without changing source or retained compiler data');
foreach ($first->inputs->sources->files as $file) {
    $changed = in_array($file->id, [$main_id, $value_id], true);
    Variable_Tokens_Test::check(($candidate->tokens->for_file($file->id) !== $first->inputs->tokens->for_file($file->id)) === $changed,
        'Unchanged files retain their token buffers');
}
$export = json_decode($candidate->tokens->for_file($main_id)->to_json(), true, 512, JSON_THROW_ON_ERROR);
Variable_Tokens_Test::check(($export['tokens'][0]['kind'] === 'variable_name') && ($export['tokens'][0]['text'] === '$count')
    && ($export['tokens'][2]['kind'] === 'assignment'), 'Real file reads export variable and assignment tokens');

$warm_sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $candidate->sources));
$warm = Phases::run_tokenization($warm_sources, $candidate->tokens, new Update_Context());
Variable_Tokens_Test::check((\tokenize\Token_Selection::select($warm->sources, $warm->tokens, false) === [])
    && ($warm->tokens->for_file($main_id) === $candidate->tokens->for_file($main_id)), 'Unchanged variable source selects no token work');
$full_update = new Update_Context();
$full_update->full_rebuild = true;
$full = Phases::run_tokenization($warm->sources, $warm->tokens, $full_update);
Variable_Tokens_Test::check(($full->tokens->to_json() === $candidate->tokens->to_json())
    && ($full->tokens->for_file($main_id) !== $candidate->tokens->for_file($main_id)), 'Full selection uses the same workers and produces equivalent tokens');

$compiled = $session->compile($path);
Variable_Tokens_Test::check(($compiled->llvm !== null) && (serialize($baseline) === $before),
    'Variable tokens now traverse the complete LLVM pipeline without mutating earlier snapshots');

Variable_Tokens_Test::edit($main, '$9bad int = 42;');
$broken_sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $candidate->sources));
$candidate_before = serialize($candidate);
Variable_Tokens_Test::rejects(static fn() => Phases::run_tokenization($broken_sources, $candidate->tokens, new Update_Context()),
    'Expected ASCII variable name');
Variable_Tokens_Test::check(serialize($candidate) === $candidate_before, 'Malformed variable spelling cannot mutate the prior lexical result');
Variable_Tokens_Test::edit($main, '$repaired int = 43; return $repaired;');
$repair_sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $candidate->sources));
$repair = Phases::run_tokenization($repair_sources, $candidate->tokens, new Update_Context());
Variable_Tokens_Test::check(str_contains($repair->tokens->for_file($main_id)->to_json(), '$repaired')
    && ($repair->tokens->for_file($value_id) === $candidate->tokens->for_file($value_id)), 'Repair replaces the failed file through the same selected phase');

Variable_Tokens_Test::edit($main, $original_main);
Variable_Tokens_Test::edit($value, $original_value);
$restored = $session->compile($path);
Variable_Tokens_Test::check(($restored->llvm->ir_by_file() === $first->llvm->ir_by_file()) && (serialize($baseline) === $before),
    'The session still compiles the supported subset after failure and repair');
echo "variable tokens ok: real file reads, exact exports, fixed workers, selective/full reuse, rejection boundaries and repair\n";
