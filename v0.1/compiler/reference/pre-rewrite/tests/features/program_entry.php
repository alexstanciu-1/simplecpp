<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

use check_bodies\Body_Checker;
use check_bodies\Body_Set;
use collect_symbols\symbol_kind;
use resolve_types\Entry_Resolver;
use resolve_types\Signature_Resolver;
use type_model\Type_Store;

class Program_Entry_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = is_file($path) ? filemtime($path) : time();
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    /** Require a rejected operation with the expected diagnostic. */
    public static function rejects(callable $action, string $message): Throwable
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), $error->getMessage());
            return $error;
        }
        throw new Exception('Expected error: ' . $message);
    }

    public static function baseline(\compile\Compiler_Session $session): array
    {
        return [$session->observed];
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath('../fixtures/three_files');
$main_path = $root . '/src/main.phs';
$answer_path = $root . '/src/answer.phs';
$value_path = $root . '/src/nested/value.phs';
$catalog_path = getcwd() . '/language-types.json';
$catalog_text = file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json');
file_put_contents($catalog_path, $catalog_text);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest);
$symbols = $first->symbols->current;
$entry = $first->types->entry;
$entry_id = $entry->symbol->symbol_id;
$answer = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$value = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$signature = $first->types->for_symbol($entry_id);
$body = $first->bodies->for_symbol($entry_id);
Program_Entry_Test::check(($entry->symbol->frontend->tokens->source->path === $main_path)
    && ($signature->declaration_node_id === 0) && ($signature->return_annotation_id === 0)
    && ($signature->body_node_id === $entry->symbol->body_node_id)
    && ($signature->representation_id === $first->types->for_symbol($answer)->representation_id)
    && ($entry->return_type === $first->types->catalog->find_type('int', ''))
    && ($body->calls[0]->target_callable_id === $answer) && ($body->calls[0]->result_value_id === 1)
    && ($body->values[0]->kind === \check_bodies\value_kind::call_result) && ($body->values[0]->payload === 1) && ($body->statements[0]->value_id === 1),
    'Manifest entry has a real shared int callable contract and uses the ordinary call/return checker without fabricated AST nodes');
Program_Entry_Test::check((count($first->types->signatures()) === 3) && (!$first->completed)
    && ($first->stopped_before === 'build_native') && ($session->generation === 0),
    'Only functions and the selected entry have execution contracts; native work is still pending');
foreach ($symbols->records() as $symbol) {
    if (($symbol->kind === symbol_kind::file_entry) && ($symbol !== $entry->symbol)) {
        Program_Entry_Test::check(($first->types->for_symbol($symbol->symbol_id) === null)
            && ($first->bodies->for_symbol($symbol->symbol_id) === null), 'Empty supporting file bodies have no executable contribution');
    }
}
$before = serialize($first);

// Semantic entry preparation reads the discovered snapshot, even if the path disappears later.
Program_Entry_Test::check(rename($main_path, $main_path . '.saved'), 'Temporarily move entry fixture');
try {
    clearstatcache(true);
    $prepared = \Step_Test::run(new \resolve_types\Entry_Resolver($first->inputs->sources, $symbols, $first->types->catalog));
    Program_Entry_Test::check(($prepared->symbol === $entry->symbol)
        && ($first->inputs->sources->entry_file()->id === $entry->symbol->frontend->source_file_id)
        && (serialize($first) === $before), 'Entry identity comes from discovery without new filesystem access');
}
finally {
    rename($main_path . '.saved', $main_path);
    clearstatcache(true);
}
$warm = $session->compile($manifest);
Program_Entry_Test::check(($warm->types->for_symbol($entry_id) === $signature) && ($warm->bodies->for_symbol($entry_id) === $body),
    'Warm updates reuse the implicit callable just like a named function');

$tasks = Signature_Resolver::select($symbols, null, new Type_Store($first->types->types->context), true, $entry);
$requests = [];
foreach (array_reverse($tasks) as $task) {
    $requests[] = Signature_Resolver::resolve($symbols, $first->types->catalog, $task, $entry, $first->resolutions);
}
$candidate = new Type_Store($first->types->types->context);
// Match the phase's prepared expression-result roles before the signature join.
\resolve_types\Type_Cache::materialize($candidate, $first->types->catalog->integer_literal_type);
\resolve_types\Type_Cache::materialize($candidate, $first->types->catalog->boolean_type);
$joined = (new \resolve_types\Signature_Join($symbols, $first->types->catalog, $candidate, null, $tasks, $entry, $first->resolutions))->join($requests);
$joined = new \resolve_types\Type_Resolution($candidate, $first->types->catalog, $entry, $joined, [], $first->resolutions);
Program_Entry_Test::check(($joined->to_json() === $first->types->to_json()) && (serialize($first) === $before),
    'Mixed named/implicit signature workers remain independent and deterministic');
$body_tasks = \Step_Test::select(Body_Checker::class, $symbols, $first->resolutions, $first->types, new Body_Set(), true);
$results = [];
foreach (array_reverse($body_tasks) as $task) {
    $results[] = (new \check_bodies\Body_Worker($task))->check();
}
Program_Entry_Test::check(((new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types, new Body_Set(), $body_tasks))->join($results)->to_json()
        === $first->bodies->to_json()) && (serialize($first) === $before), 'The same body workers and join cover the project entry');
$bad_requests = array_map(static fn($request) => $request->symbol === $entry->symbol
    ? new \resolve_types\signature_request($request->symbol, 1, $request->definition) : $request, $requests);
$candidate = new Type_Store($first->types->types->context);
$candidate_before = $candidate->to_json();
Program_Entry_Test::rejects(static fn() => (new \resolve_types\Signature_Join($symbols, $first->types->catalog, $candidate, null, $tasks, $entry, $first->resolutions))->join($bad_requests), 'Stale entry signature');
Program_Entry_Test::check($candidate->to_json() === $candidate_before, 'Bad implicit origins cannot partially populate the type candidate');

Program_Entry_Test::edit($value_path, 'function value(): int { return 77; }');
$body_edit = $session->compile($manifest);
Program_Entry_Test::check(($body_edit->bodies->for_symbol($entry_id) === $body) && ($body_edit->bodies->for_symbol($answer) === $first->bodies->for_symbol($answer))
    && ($body_edit->bodies->for_symbol($value) !== $first->bodies->for_symbol($value)), 'A callee body edit preserves both unchanged callers, including the entry');
Program_Entry_Test::edit($main_path, 'answer(); return 8;');
$entry_edit = $session->compile($manifest);
Program_Entry_Test::check(($entry_edit->bodies->for_symbol($entry_id) !== $body)
    && (count($entry_edit->bodies->for_symbol($entry_id)->statements) === 2)
    && ($entry_edit->bodies->for_symbol($answer) === $body_edit->bodies->for_symbol($answer))
    && (!$entry_edit->inputs->context->full_rebuild), 'Entry-body edits use the same selective work and statement loop');

$baseline = Program_Entry_Test::baseline($session);
$baseline_dump = serialize($baseline);
foreach ([['', 'without returning a value'], ['return;', 'A value is required'],
        ['return 9223372036854775808;', 'outside the range'], ['return absent();', "Unknown function 'absent'"]] as [$source, $message]) {
    Program_Entry_Test::edit($main_path, $source);
    $error = Program_Entry_Test::rejects(static fn() => $session->compile($manifest), $message);
    Program_Entry_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main_path)
        && (Program_Entry_Test::baseline($session) === $baseline) && (serialize($baseline) === $baseline_dump),
        'Invalid entry returns/names preserve every accepted stage');
}
Program_Entry_Test::edit($main_path, 'return answer();');
$repaired = $session->compile($manifest);
$retained = Program_Entry_Test::baseline($session);
Program_Entry_Test::edit($answer_path, 'function answer(): uint32 { return answer(); }');
$error = Program_Entry_Test::rejects(static fn() => $session->compile($manifest), 'Unsupported implicit return conversion from uint32 to int');
Program_Entry_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main_path)
    && ($error->start === strlen('return ')) && (Program_Entry_Test::baseline($session) === $retained),
    'A callee signature edit invalidates and diagnoses the unchanged project entry');
Program_Entry_Test::edit($answer_path, 'function answer(): int { return value(); }');
$session->compile($manifest);

foreach (['return 1;', 'absent();'] as $statement)
{
    Program_Entry_Test::edit($value_path, 'function value(): int { return 77; } ' . $statement);
    $retained = Program_Entry_Test::baseline($session);
    $error = Program_Entry_Test::rejects(static fn() => $session->compile($manifest), 'Executable top-level code outside the manifest entry is unsupported');
    Program_Entry_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $value_path)
        && ($error->length === strlen($statement)) && (Program_Entry_Test::baseline($session) === $retained),
        'Unsupported supporting-file execution is diagnosed before name/body checks and cannot silently disappear');
}
Program_Entry_Test::edit($value_path, 'function value(): int { return 77; }');
$session->compile($manifest);

// Selection follows the manifest, not a special filename or enumeration order.
$configuration = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
$configuration['entry'] = 'src/nested/value.phs';
Program_Entry_Test::edit($manifest, json_encode($configuration, JSON_THROW_ON_ERROR));
Program_Entry_Test::edit($main_path, '');
Program_Entry_Test::edit($value_path, 'return answer(); function value(): int { return 77; }');
$moved = $session->compile($manifest);
$new_entry = $moved->types->entry->symbol->symbol_id;
Program_Entry_Test::check(($moved->inputs->context->full_rebuild) && ($new_entry !== $entry_id)
    && ($moved->types->entry->symbol->frontend->tokens->source->path === $value_path)
    && ($moved->types->for_symbol($entry_id) === null) && ($moved->bodies->for_symbol($entry_id) === null),
    'Manifest changes select full work and retire the former entry execution contract');
Program_Entry_Test::rejects(static fn() => (new \resolve_types\Signature_Join($moved->symbols->current, $moved->types->catalog, clone $moved->types->types, $moved->types, [], $entry, $first->resolutions))->join([]), 'Stale entry contract');
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest);
Program_Entry_Test::check(($fresh->types->to_json() === $moved->types->to_json()) && ($fresh->bodies->to_json() === $moved->bodies->to_json()),
    'A fresh build has identical contracts and checked bodies after entry replacement');

// Native exit width/ABI is not inferred from the language integer representation.
$export = json_decode($moved->to_json(), true, 512, JSON_THROW_ON_ERROR);
Program_Entry_Test::check(($export['types']['entry_symbol_id'] === $new_entry)
    && (count($export['bodies']) === 3) && ($export['stopped_before'] === 'build_native'), 'Debug output identifies the language entry and true stopping point');
$data = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
$data['entry_return_type']['name'] = 'float';
Program_Entry_Test::rejects(static fn() => \load_runtime\Catalog_Syntax::parse(json_encode($data, JSON_THROW_ON_ERROR)), 'Entry return type must refer to a defined integer');
echo "program entry ok: manifest selection, shared callable/body path, fixed workers, reuse, edits, diagnostics, rollback/repair, supporting-file policy, entry replacement and exports\n";
