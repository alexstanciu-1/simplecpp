<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use collect_symbols\symbol_kind;
use compile\Compiler_Session;
use load_runtime\Language_Types;
use resolve_types\Signature_Resolver;
use type_model\Type_Store;

class Return_Types_Test
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
    public static function rejects(callable $action, string $reason): Throwable
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $reason), $error->getMessage());
            return $error;
        }
        throw new Exception('Expected error: ' . $reason);
    }

    public static function return_id(\resolve_types\Type_Resolution $result, int $symbol): int
    {
        return $result->types->representation_by_id($result->for_symbol($symbol)->representation_id)->payload->return_type;
    }
}

// Count actual store completions in the real join, not a simulated resolver.
class Counting_Types extends Type_Store
{
    public int $completions = 0;

    public function set_representation(int $type_id, int $representation_id): void
    {
        ++$this->completions;
        parent::set_representation($type_id, $representation_id);
    }
}

$manifest = '../fixtures/three_files/project.json';
$value_path = realpath('../fixtures/three_files/src/nested/value.phs');
$catalog_path = getcwd() . '/language-types.json';
$catalog_text = file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json');

// Reject metadata we cannot interpret instead of silently advertising a contract.
$catalog_data = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
foreach ([
        ['name' => 'bad', 'namespace' => '', 'kind' => 'integer', 'bit_width' => 0, 'signed' => true, 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']],
        ['name' => 'bad', 'namespace' => '', 'kind' => 'integer', 'bit_width' => '64', 'signed' => true, 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']],
        ['name' => 'bad', 'namespace' => '', 'kind' => 'floating_point', 'format' => 'unknown', 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']],
        ['name' => 'bad', 'namespace' => '', 'kind' => 'void', 'can_add' => true, 'lifetime' => null],
        ['name' => 'bad', 'namespace' => '', 'kind' => 'structure'],
        $catalog_data['types'][0],
    ] as $invalid_definition) {
    $invalid_catalog = $catalog_data;
    $invalid_catalog['types'][] = $invalid_definition;
    Return_Types_Test::rejects(static fn() => \load_runtime\Catalog_Syntax::parse(json_encode($invalid_catalog, JSON_THROW_ON_ERROR)), '');
}
file_put_contents($catalog_path, $catalog_text);
Return_Types_Test::edit('../fixtures/three_files/src/answer.phs', 'function answer(): int { value(); return 42; }');
$session = new Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest);
$symbols = $first->symbols->current;
$entry = $first->types->entry;
$answer = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$value = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$int = Return_Types_Test::return_id($first->types, $answer);
Return_Types_Test::check((!$first->completed) && ($first->stopped_before === 'build_native')
    && (Return_Types_Test::return_id($first->types, $value) === $int)
    && ($first->types->types->representation_for_type($int)->payload->bit_width === 64)
    && ($first->types->catalog->find_type('int', '')->signed === true),
    'Cross-file annotations share the authoritative signed 64-bit int identity');
foreach ($symbols->records() as $symbol) {
    if ($symbol->kind === symbol_kind::file_entry) {
        Return_Types_Test::check(($first->types->for_symbol($symbol->symbol_id) !== null) === ($symbol === $entry->symbol), 'Only the selected implicit entry has an execution contract');
    }
}
$before = serialize($first);
$warm = $session->compile($manifest);
Return_Types_Test::check(($warm->types->for_symbol($answer) === $first->types->for_symbol($answer))
    && ($warm->types->for_symbol($value) === $first->types->for_symbol($value))
    && ($warm->types->catalog === $first->types->catalog)
    && (Signature_Resolver::select($warm->symbols->current, $first->types, $warm->types->types, false, $entry) === []),
    'Unchanged inputs reuse signatures/catalog and select no signature work');

// Separate workers resolve against a fixed catalog; only the join interns types.
$counted = new Counting_Types($first->types->types->context);
$tasks = Signature_Resolver::select($symbols, null, $counted, true, $entry);
// Mirror phase-owned literal/result roles before fixed signature workers run.
\resolve_types\Type_Cache::materialize($counted, $first->types->catalog->integer_literal_type);
\resolve_types\Type_Cache::materialize($counted, $first->types->catalog->boolean_type);
$empty_before = $counted->to_json();
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = Signature_Resolver::resolve($symbols, $first->types->catalog, $task, $entry, $first->resolutions);
}
Return_Types_Test::check($counted->to_json() === $empty_before, 'Workers must not write the type table');
$joined = (new \resolve_types\Signature_Join($symbols, $first->types->catalog, $counted, null, $tasks, $entry, $first->resolutions))->join($results);
$joined = new \resolve_types\Type_Resolution($counted, $first->types->catalog, $entry, $joined, [], $first->resolutions);
Return_Types_Test::check(($counted->completions === 2) && ($joined->to_json() === $first->types->to_json())
    && (serialize($first) === $before), 'One materialization per role/type serves repeated annotations with deterministic join and pure inputs');
foreach ([[], [...$results, $results[0]]] as $bad) {
    $candidate = new Type_Store($counted->context);
    $before_bad = $candidate->to_json();
    Return_Types_Test::rejects(static fn() => (new \resolve_types\Signature_Join($symbols, $first->types->catalog, $candidate, null, $tasks, $entry, $first->resolutions))->join($bad),
        $bad === [] ? 'Incomplete' : 'duplicate');
    Return_Types_Test::check($candidate->to_json() === $before_bad, 'Invalid result sets cannot partially populate the candidate');
}
$bad = new \resolve_types\signature_request($results[0]->symbol, $results[0]->return_annotation_id,
    $first->types->catalog->find_type('float', ''));
Return_Types_Test::rejects(static fn() => (new \resolve_types\Signature_Join($symbols, $first->types->catalog, new Type_Store($counted->context), null, $tasks, $entry, $first->resolutions))->join([$bad, $results[1]]), 'Stale');
Return_Types_Test::rejects(static fn() => (new \resolve_types\Signature_Join($symbols, $first->types->catalog, $first->types->types, $first->types, [], $entry, $first->resolutions))->join([]), 'separate candidate');

Return_Types_Test::edit($value_path, 'function value(): uint32 { return value(); }');
$edited = $session->compile($manifest);
$unsigned = Return_Types_Test::return_id($edited->types, $value);
Return_Types_Test::check(($unsigned !== $int) && ($edited->types->types->type_by_id($unsigned)->name === 'uint32')
    && ($edited->types->types->representation_for_type($unsigned)->payload->bit_width === 32)
    && ($edited->types->catalog->find_type('uint32', '')->signed === false)
    && ($edited->inputs->context->full_rebuild)
    && ($edited->types->for_symbol($answer) !== $warm->types->for_symbol($answer))
    && ($edited->types->for_symbol($value)->syntax === $edited->symbols->current->symbol_by_id($value)->frontend->syntax)
    && (serialize($first) === $before), 'Annotation edits select full work, refresh signature anchors and preserve previous snapshots');
Return_Types_Test::edit($value_path, '/* moved */ function value(): uint32 { value(); return value(); }');
$body = $session->compile($manifest);
Return_Types_Test::check(($body->types->for_symbol($value) !== $edited->types->for_symbol($value))
    && ($body->types->for_symbol($value)->representation_id === $edited->types->for_symbol($value)->representation_id),
    'Reparsed bodies refresh syntax anchors but share the unchanged signature representation');

$retained = [$session->observed?->inputs, $session->observed?->symbols, $session->observed?->resolutions, $session->observed?->types];
$retained_before = serialize($retained);
$unknown = 'function value(): Missing { return 1; }';
Return_Types_Test::edit($value_path, $unknown);
$error = Return_Types_Test::rejects(static fn() => $session->compile($manifest), "Unknown or unsupported return type 'Missing'");
Return_Types_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $value_path)
    && ($error->start === strpos($unknown, 'Missing')) && ($error->length === 7)
    && ([$session->observed?->inputs, $session->observed?->symbols, $session->observed?->resolutions, $session->observed?->types] === $retained)
    && (serialize($retained) === $retained_before), 'Unknown types have exact source diagnostics and cannot advance any retained stage');
Return_Types_Test::edit($value_path, 'function value(): float { return value(); } function empty_value(): void { return; }');
$repaired = $session->compile($manifest);
$empty = $repaired->symbols->current->find_symbol('empty_value', '', symbol_kind::function_symbol);
Return_Types_Test::check(($repaired->inputs->context->full_rebuild)
    && ($repaired->types->types->find_type('uint32') === 0)
    && ($body->types->types->find_type('uint32') !== 0)
    && ($repaired->types->types->context == $body->types->types->context),
    'Admission-triggered full selection starts a fresh type lineage even when configuration is unchanged');
Return_Types_Test::check(($repaired->types->types->representation_for_type(Return_Types_Test::return_id($repaired->types, $value))->payload->bit_width() === 64)
    && ($repaired->types->types->representation_for_type(Return_Types_Test::return_id($repaired->types, $empty))->kind === \type_model\representation_kind::void_type)
    && ($repaired->stopped_before === 'build_native'),
    'Repair resolves float/void signatures and checks valid recursive and bare-return bodies');

// Any provider change selects full work before source/token/parse selection.
$data = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
$data['types'][] = ['name' => 'Metric', 'namespace' => '', 'kind' => 'integer', 'bit_width' => 23, 'signed' => true, 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']];
file_put_contents($catalog_path, json_encode($data, JSON_THROW_ON_ERROR));
$provider_changed = $session->compile($manifest);
Return_Types_Test::check(($provider_changed->inputs->context->full_rebuild)
    && ($provider_changed->types->for_symbol($answer) !== $repaired->types->for_symbol($answer))
    && ($provider_changed->symbols->current->symbol_by_id($answer)->frontend !== $repaired->symbols->current->symbol_by_id($answer)->frontend),
    'Changed catalog invalidates type facts and selects all frontend work before workers start');
Return_Types_Test::edit($value_path, 'function value(): Metric { return value(); }');
$metric = $session->compile($manifest);
Return_Types_Test::check($metric->types->types->representation_for_type(Return_Types_Test::return_id($metric->types, $value))->payload->bit_width === 23,
    'A test-supplied name/width follows the same catalog and resolution path');
$fresh = (new Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest);
Return_Types_Test::check($fresh->types->types->representation_for_type(Return_Types_Test::return_id($fresh->types, $value))->payload
    == $metric->types->types->representation_for_type(Return_Types_Test::return_id($metric->types, $value))->payload,
    'Refreshed and fresh builds resolve the same final meaning independent of local ID order');
$accepted = $session->observed?->types;
file_put_contents($catalog_path, $catalog_text); // Removes Metric from the provider.
Return_Types_Test::rejects(static fn() => $session->compile($manifest), "return type 'Metric'");
Return_Types_Test::check($session->observed?->types === $accepted, 'Removed provider definitions cannot survive a reset cache');
Return_Types_Test::edit($value_path, 'function value(): int { return 42; }');
$final = $session->compile($manifest);
Return_Types_Test::check(($final->inputs->context->full_rebuild) && ($final->types->types->find_type('Metric') === 0),
    'Repair after provider removal adopts only current definitions');
file_put_contents($catalog_path, '{broken');
Return_Types_Test::rejects(static fn() => $session->compile($manifest), 'Invalid language type catalog');
Return_Types_Test::check($session->observed?->types === $final->types, 'Invalid catalogs preserve retained results');
file_put_contents($catalog_path, $catalog_text);
Return_Types_Test::check($session->compile($manifest)->types->for_symbol($answer) === $final->types->for_symbol($answer), 'Catalog repair reuses the accepted baseline');

$export = json_decode($final->to_json(), true, 512, JSON_THROW_ON_ERROR);
Return_Types_Test::check((count($export['types']['signatures']) === 3) && ($export['stopped_before'] === 'build_native')
    && ($session->generation === 0), 'Debug output includes actual signatures without publishing unfinished compilation');
echo "return types ok: authoritative types, cross-file reuse, independent workers, one materialization, joins, edits, source diagnostics, rollback/repair, catalog invalidation, fresh equivalence and separate signature/body ownership\n";
