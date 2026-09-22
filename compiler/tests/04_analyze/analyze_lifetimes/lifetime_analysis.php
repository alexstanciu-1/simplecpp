<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use analyze_lifetimes\Lifetime_Analyzer;
use analyze_lifetimes\Lifetime_Set;
use analyze_lifetimes\lifetime_end;
use check_bodies\Body_Set;
use check_bodies\Checked_Body;
use collect_symbols\symbol_kind;

class Lifetime_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    public static function edit(string $path, string $source): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $source);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    public static function rejects(callable $action, string $message): Throwable
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), $error->getMessage());
            return $error;
        }
        throw new Exception('Expected rejection: ' . $message);
    }

    public static function baseline(\compile\Compiler_Session $session): array
    {
        return [$session->observed];
    }

    public static function facts(\compile\Compile_Result $result): array
    {
        $facts = [];
        foreach ($result->bodies->bodies() as $body) {
            $analysis = $result->lifetimes->for_symbol($body->owner->symbol_id);
            $facts[$body->owner->name] = [$analysis->lifetimes, $analysis->reachable_statement_count, $analysis->falls_through];
        }
        ksort($facts);
        return $facts;
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$main_path = $root . '/src/main.phs';
$answer_path = $root . '/src/answer.phs';
$value_path = $root . '/src/nested/value.phs';
$main_text = 'answer(); return answer(); answer();';
$answer_text = 'function answer(): int { value(); quiet(); stable(); return value(); quiet(); }
function stable(): int { return 7; }
function quiet(): void { value(); return; value(); }
function fall(): void { value(); quiet(); }
function empty(): void {}';
Lifetime_Test::edit($main_path, $main_text);
Lifetime_Test::edit($answer_path, $answer_text);
$catalog_path = getcwd() . '/language-types.json';
$catalog_text = file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json');
file_put_contents($catalog_path, $catalog_text);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest);
$symbols = $first->symbols->current;
$answer = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$value = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$stable = $symbols->find_symbol('stable', '', symbol_kind::function_symbol);
$quiet = $symbols->find_symbol('quiet', '', symbol_kind::function_symbol);
$fall = $symbols->find_symbol('fall', '', symbol_kind::function_symbol);
$empty = $symbols->find_symbol('empty', '', symbol_kind::function_symbol);
$entry = $first->types->entry->symbol->symbol_id;
$analyzed = $first->lifetimes->for_symbol($answer);
Lifetime_Test::check((!$first->completed) && ($first->stopped_before === 'build_native') && ($session->generation === 0),
    'Real compilation reaches lifetime analysis without claiming native completion');
Lifetime_Test::check(($analyzed->body === $first->bodies->for_symbol($answer))
    && ($analyzed->reachable_statement_count === 4) && (!$analyzed->falls_through)
    && (array_column($analyzed->lifetimes, 'value_id') === [1, 2, 3])
    && (array_column($analyzed->lifetimes, 'statement_id') === [1, 3, 4])
    && (array_column($analyzed->lifetimes, 'end') === [lifetime_end::discard, lifetime_end::discard, lifetime_end::return_copy]),
    'Call results and literals end at their real statements; returned scalars copy out and void calls have no lifetime');
$entry_result = $first->lifetimes->for_symbol($entry);
Lifetime_Test::check((count($entry_result->body->values) === 3) && (count($entry_result->lifetimes) === 2)
    && ($entry_result->reachable_statement_count === 2) && (!$entry_result->falls_through),
    'The selected entry uses the same flow path and unreachable results never acquire lifetimes');
Lifetime_Test::check((count($first->lifetimes->for_symbol($quiet)->lifetimes) === 1)
    && ($first->lifetimes->for_symbol($quiet)->reachable_statement_count === 2)
    && (!$first->lifetimes->for_symbol($quiet)->falls_through)
    && ($first->lifetimes->for_symbol($fall)->falls_through) && (count($first->lifetimes->for_symbol($fall)->lifetimes) === 1)
    && ($first->lifetimes->for_symbol($empty)->falls_through) && ($first->lifetimes->for_symbol($empty)->lifetimes === []),
    'Bare return, void fallthrough and empty bodies have explicit flow and only real scalar temporaries');
$before = serialize($first);
$warm = $session->compile($manifest);
Lifetime_Test::check((\Step_Test::select(Lifetime_Analyzer::class, $warm->bodies, $first->lifetimes, false) === [])
    && ($warm->lifetimes->for_symbol($answer) === $analyzed), 'Warm analysis selects zero work and shares immutable results');
$tasks = \Step_Test::select(Lifetime_Analyzer::class, $first->bodies, new Lifetime_Set(), true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \analyze_lifetimes\Lifetime_Worker($task))->analyze();
}
$joined = (new \analyze_lifetimes\Lifetime_Join($first->bodies, new Lifetime_Set(), $tasks))->join($results);
Lifetime_Test::check(($joined->to_json() === $first->lifetimes->to_json()) && (serialize($first) === $before),
    'Reversed independent workers join deterministically without modifying any input');
Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($first->bodies, new Lifetime_Set(), $tasks))->join([]), 'Incomplete');
Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($first->bodies, new Lifetime_Set(), $tasks))->join([...$results, $results[0]]), 'duplicate');
Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($first->bodies, new Lifetime_Set(), [$tasks[0], $tasks[0]]))->join([]), 'Duplicate');
Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($first->bodies, new Lifetime_Set(), []))->join([$results[0]]), 'Unexpected');
Lifetime_Test::check((new \analyze_lifetimes\Lifetime_Join(new Body_Set(), $first->lifetimes, []))->join([])->to_json() === '[]',
    'Removed owners are excluded independently of work selection');

Lifetime_Test::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest);
Lifetime_Test::check((!$edited->inputs->context->full_rebuild)
    && (\Step_Test::select(Lifetime_Analyzer::class, $edited->bodies, $first->lifetimes, false) === [$edited->bodies->for_symbol($value)])
    && ($edited->lifetimes->for_symbol($value) !== $first->lifetimes->for_symbol($value))
    && ($edited->lifetimes->for_symbol($answer) === $analyzed) && ($edited->lifetimes->for_symbol($entry) === $entry_result)
    && (serialize($first) === $before), 'A body edit replaces only its analysis; callers retain results under unchanged contracts');
Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($edited->bodies, $first->lifetimes, [$edited->bodies->for_symbol($value)]))->join([$first->lifetimes->for_symbol($value)]), 'stale');
Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($edited->bodies, $first->lifetimes, [$first->bodies->for_symbol($value)]))->join([]), 'stale');
$baseline = Lifetime_Test::baseline($session);
$baseline_dump = serialize($baseline);
Lifetime_Test::edit($value_path, 'function value(): uint32 { return value(); }');
Lifetime_Test::rejects(static fn() => $session->compile($manifest), 'Unsupported implicit return conversion');
Lifetime_Test::check((Lifetime_Test::baseline($session) === $baseline) && (serialize($baseline) === $baseline_dump),
    'A failed compile retains all stage snapshots and backend context');
Lifetime_Test::edit($value_path, 'function value(): int { return 44; }');
$repair = $session->compile($manifest);
Lifetime_Test::check(($repair->lifetimes->for_symbol($answer) === $analyzed)
    && ($repair->lifetimes->for_symbol($value)->body->values[0]->payload === '44'), 'Repair uses the accepted dependency baseline');
Lifetime_Test::edit($main_path, 'return 9; answer();');
$entry_edit = $session->compile($manifest);
Lifetime_Test::check(($entry_edit->lifetimes->for_symbol($entry)->reachable_statement_count === 1)
    && (count($entry_edit->lifetimes->for_symbol($entry)->lifetimes) === 1)
    && ($entry_edit->lifetimes->for_symbol($answer) === $analyzed), 'An entry edit replaces its own reachable lifetimes');

// Source-independent worker boundary: an invalid value cannot acquire a lifetime
// merely because its type has a representation. Void is explicitly no-value.
$body = $repair->bodies->for_symbol($value);
$type_id = $body->values[0]->type_id;
$dependencies = $body->type_dependencies;
$dependencies[$type_id] = $repair->types->types->type_by_id($repair->types->types->find_type('void'));
$invalid_body = new Checked_Body($body->owner, $body->names, $body->values, $body->calls,
    $body->statements, $body->falls_through, $dependencies, $body->signature_dependencies, $body->local_types, $body->scopes, $body->arguments, $body->blocks);
$error = Lifetime_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker($invalid_body))->analyze(), 'Value type has no lifetime contract');
Lifetime_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $value_path)
    && ($error->start === strpos(file_get_contents($value_path), '44')) && ($error->length === 2)
    && (serialize($first) === $before), 'The lifetime worker rejects absent contracts with a real source anchor and no input mutations');

// Non-default names and widths use exactly the same shared metadata contract.
$data = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
foreach ($data['types'] as &$definition) {
    if ($definition['name'] === 'int') {
        $definition['name'] = 'Counter';
        $definition['bit_width'] = 17;
    }
}
unset($definition);
$data['literal_types']['integer']['name'] = 'Counter';
$data['entry_return_type']['name'] = 'Counter';
file_put_contents($catalog_path, json_encode($data, JSON_THROW_ON_ERROR));
Lifetime_Test::edit($answer_path, str_replace(': int', ': Counter', $answer_text));
Lifetime_Test::edit($value_path, 'function value(): Counter { return 44; }');
$provider_edit = $session->compile($manifest);
Lifetime_Test::check(($provider_edit->inputs->context->full_rebuild)
    && ($provider_edit->lifetimes->for_symbol($value)->lifetimes[0]->end === lifetime_end::return_copy)
    && ($provider_edit->lifetimes->for_symbol($entry) !== $entry_edit->lifetimes->for_symbol($entry)),
    'Provider changes select the same analysis for all bodies, with no type-name or width heuristics');
Lifetime_Test::edit($answer_path, str_replace('function empty(): void {}', '', str_replace(': int', ': Counter', $answer_text)));
$removed = $session->compile($manifest);
Lifetime_Test::check($removed->lifetimes->for_symbol($empty) === null, 'Removed functions leave no stale lifetime results');
Lifetime_Test::edit($value_path, 'function value(): Counter { ' . str_repeat('value();', 1500) . 'return 44;' . str_repeat('value();', 500) . '}');
$large = $session->compile($manifest);
Lifetime_Test::check((count($large->lifetimes->for_symbol($value)->lifetimes) === 1501)
    && ($large->lifetimes->for_symbol($value)->reachable_statement_count === 1501)
    && (count($large->bodies->for_symbol($value)->values) === 2001), 'Linear analysis handles large bodies and does not visit unreachable call results');
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest);
Lifetime_Test::check(Lifetime_Test::facts($large) == Lifetime_Test::facts($fresh), 'Fresh and incremental analysis agree on actual lifetime facts');
$export = json_decode($large->to_json(), true, 512, JSON_THROW_ON_ERROR);
$export_by_symbol = array_column($export['lifetimes'], null, 'symbol_id');
Lifetime_Test::check((count($export['lifetimes']) === count($export['bodies'])) && ($export['stopped_before'] === 'build_native')
    && ($export_by_symbol[$entry]['lifetimes'][0]['end'] === 'return_copy'), 'Debug output exposes real per-value analysis and the new stopping point');
echo "lifetime analysis ok: reachable temporaries, scalar contracts, copies/discards, void/entry flow, pure workers, joins, reuse, edits, removal, rollback/repair, metadata, large bodies and exports\n";
