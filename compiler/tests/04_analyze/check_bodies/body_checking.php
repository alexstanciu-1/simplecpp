<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use check_bodies\Body_Checker;
use check_bodies\Body_Set;
use check_bodies\Conversion_Resolver;
use check_bodies\value_kind;
use collect_symbols\symbol_kind;

class Body_Checking_Test
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

    /** Require a source failure with the expected diagnostic while retaining its exception for inspection. */
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

    /** Compare semantic body facts independently of unrelated stores' allocated IDs. */
    public static function facts(\compile\Compile_Result $result): array
    {
        $facts = [];
        foreach ($result->symbols->current->records() as $symbol)
        {
            $body = $result->bodies->for_symbol($symbol->symbol_id);
            if ($body === null) {
                continue;
            }
            $values = [];
            foreach ($body->values as $value) {
                $type = $result->types->types->definition_for_type($value->type_id);
                $values[] = [$value->source_node_id, $type, $value->kind, $value->payload];
            }
            $calls = [];
            foreach ($body->calls as $call) {
                $calls[] = [$call->source_node_id, $result->symbols->current->symbol_by_id($call->target_callable_id)->name, $call->result_value_id];
            }
            $facts[$symbol->name] = [$values, $calls, $body->statements, $body->falls_through];
        }
        ksort($facts);
        return $facts;
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath('../fixtures/three_files');
$main_path = $root . '/src/main.phs';
$answer_path = $root . '/src/answer.phs';
$value_path = $root . '/src/nested/value.phs';
Body_Checking_Test::edit($main_path, 'answer(); return 42; function stable(): int { return 7; }');
$catalog_path = getcwd() . '/language-types.json';
$catalog_text = file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json');
file_put_contents($catalog_path, $catalog_text);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest);
$symbols = $first->symbols->current;
$answer = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$value = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$stable = $symbols->find_symbol('stable', '', symbol_kind::function_symbol);
$int = $first->types->types->find_type('int');
$checked = $first->bodies->for_symbol($value);
Body_Checking_Test::check(($checked->values[0]->kind === value_kind::integer_literal) && ($checked->values[0]->payload === '42')
    && ($checked->values[0]->type_id === $int) && ($checked->statements[0]->value_id === 1)
    && (!$checked->falls_through) && ($first->bodies->for_symbol($answer)->calls[0]->target_callable_id === $value),
    'Real source literals and cross-file calls have typed values and selected return conversions');
Body_Checking_Test::check(($first->bodies->for_symbol($symbols->entry_symbol_id($first->inputs->sources->find_file_id($main_path))) !== null)
    && ($first->stopped_before === 'build_native') && (!$first->completed) && ($session->generation === 0),
    'The selected file entry shares body checking; nothing is published');
$before = serialize($first);
$warm = $session->compile($manifest);
Body_Checking_Test::check(($warm->bodies->for_symbol($answer) === $first->bodies->for_symbol($answer))
    && ($warm->bodies->for_symbol($value) === $checked)
    && (\Step_Test::select(Body_Checker::class, $warm->symbols->current, $warm->resolutions, $warm->types, $first->bodies, false) === []),
    'Unchanged bodies select no work and share retained results');
$tasks = \Step_Test::select(Body_Checker::class, $symbols, $first->resolutions, $first->types, new Body_Set(), true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \check_bodies\Body_Worker($task))->check();
}
$joined = (new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types, new Body_Set(), $tasks))->join($results);
Body_Checking_Test::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before),
    'Reversed workers produce deterministic typed outputs without modifying shared inputs');
Body_Checking_Test::rejects(static fn() => (new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types, new Body_Set(), $tasks))->join([]), 'Incomplete');
Body_Checking_Test::rejects(static fn() => (new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types, new Body_Set(), $tasks))->join([...$results, $results[0]]), 'duplicate');
Body_Checking_Test::rejects(static fn() => (new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types, new Body_Set(), [$tasks[0], $tasks[0]]))->join([]), 'Duplicate');

Body_Checking_Test::edit($value_path, 'function value(): int { return 00043; }');
$edited = $session->compile($manifest);
Body_Checking_Test::check(($edited->bodies->for_symbol($value)->values[0]->payload === '43')
    && ($edited->bodies->for_symbol($answer) === $first->bodies->for_symbol($answer))
    && ($edited->bodies->for_symbol($stable) === $first->bodies->for_symbol($stable))
    && (!$edited->inputs->context->full_rebuild) && (serialize($first) === $before),
    'Body-only edits replace the affected body while callers reuse unchanged contracts');
Body_Checking_Test::rejects(static fn() => (new \check_bodies\Body_Join($edited->symbols->current, $edited->resolutions, $edited->types, $first->bodies, [new \check_bodies\body_check_task($edited->symbols->current->symbol_by_id($value),
            $edited->resolutions->for_symbol($value), $edited->types)]))->join([$checked]), 'stale');

// The callee is valid under its new contract. The unchanged caller must fail.
$baseline = Body_Checking_Test::baseline($session);
$baseline_dump = serialize($baseline);
Body_Checking_Test::edit($value_path, 'function value(): uint32 { return value(); }');
$error = Body_Checking_Test::rejects(static fn() => $session->compile($manifest), 'Unsupported implicit return conversion from uint32 to int');
Body_Checking_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $answer_path)
    && ($error->start === strpos(file_get_contents($answer_path), 'value()')) && ($error->length === strlen('value()'))
    && (Body_Checking_Test::baseline($session) === $baseline) && (serialize($baseline) === $baseline_dump),
    'A callee contract edit rechecks an unchanged caller and failure retains all stage baselines and backend context');
Body_Checking_Test::edit($answer_path, 'function answer(): uint32 { return value(); }');
$repaired = $session->compile($manifest);
Body_Checking_Test::check(($repaired->inputs->context->full_rebuild)
    && ($repaired->bodies->for_symbol($stable) !== $edited->bodies->for_symbol($stable))
    && ($repaired->bodies->for_symbol($answer) !== $edited->bodies->for_symbol($answer)), 'Repair of changed signatures rechecks all bodies through the common full selection');
$baseline = Body_Checking_Test::baseline($session);
Body_Checking_Test::edit($value_path, 'function value(): uint32 { return 42; }');
Body_Checking_Test::rejects(static fn() => $session->compile($manifest), 'Unsupported implicit return conversion from int to uint32');
Body_Checking_Test::check(Body_Checking_Test::baseline($session) === $baseline, 'A destination annotation must not contextually retype a literal');

Body_Checking_Test::edit($answer_path, 'function answer(): int { value(); return 42; }');
foreach ([
        ['function value(): void { return 1; }', 'Cannot return a value'],
        ['function value(): int { return; }', 'A value is required'],
        ['function value(): int {}', 'without returning a value'],
        ['function value(): int { return 9223372036854775808; }', 'outside the range'],
        ['function value(): int { return ' . str_repeat('9', 70000) . '; }', 'outside the range'],
        ['function value(): int { return 1; return; }', 'A value is required'],
    ] as [$source, $message]) {
    Body_Checking_Test::edit($value_path, $source);
    Body_Checking_Test::rejects(static fn() => $session->compile($manifest), $message);
    Body_Checking_Test::check(Body_Checking_Test::baseline($session) === $baseline, 'Invalid bodies must never replace accepted stage outputs');
}
Body_Checking_Test::edit($value_path, 'function value(): int { return 9223372036854775807; }');
$maximum = $session->compile($manifest);
Body_Checking_Test::check($maximum->bodies->for_symbol($value)->values[0]->payload === '9223372036854775807',
    'Maximum signed default integer retains its exact decimal value without host conversion');
Body_Checking_Test::edit($value_path, 'function value(): void { return; } function empty_value(): void {}');
$voids = $session->compile($manifest);
$empty = $voids->symbols->current->find_symbol('empty_value', '', symbol_kind::function_symbol);
Body_Checking_Test::check((!$voids->bodies->for_symbol($value)->falls_through)
    && ($voids->bodies->for_symbol($empty)->falls_through) && ($voids->bodies->for_symbol($value)->statements[0]->value_id === 0),
    'Void bare return and empty-body fallthrough have distinct real flow results');
Body_Checking_Test::edit($answer_path, 'function answer(): int { value(); stable(); empty_value(); return 42; value(); }');
$mixed = $session->compile($manifest);
$mixed_body = $mixed->bodies->for_symbol($answer);
Body_Checking_Test::check((count($mixed_body->calls) === 4) && (count($mixed_body->values) === 2)
    && (array_column($mixed_body->calls, 'target_callable_id') === [$value, $stable, $empty, $value])
    && (array_column($mixed_body->calls, 'result_value_id') === [0, 1, 0, 0])
    && ($mixed_body->values[0]->kind === value_kind::call_result) && ($mixed_body->values[0]->payload === 2)
    && ($mixed_body->values[1]->payload === '42')
    && (array_column($mixed_body->statements, 'value_id') === [0, 1, 0, 2, 0])
    && (array_column($mixed_body->statements, 'call_start') === [0, 1, 2, 3, 3])
    && (array_column($mixed_body->statements, 'call_count') === [1, 1, 1, 0, 1]),
    'Calls retain execution order independently of optional results, including checked statements after return');
foreach ($mixed_body->calls as $call) {
    Body_Checking_Test::check($mixed_body->owner->frontend->syntax->nodes[$call->source_node_id - 1]->kind === \parse\syntax_kind::call_expression,
        'Every call preserves its real source anchor');
}
$void_id = $mixed->types->types->find_type('void');
Body_Checking_Test::check((isset($mixed_body->type_dependencies[$void_id], $mixed_body->signature_dependencies[$value]))
    && (!in_array($void_id, array_column($mixed_body->values, 'type_id'), true)),
    'No-result calls retain contract dependencies without allocating void values');
$mixed_dump = serialize($mixed);
$warm_mixed = $session->compile($manifest);
Body_Checking_Test::check($warm_mixed->bodies->for_symbol($answer) === $mixed_body, 'Unchanged no-result calls reuse the checked body');
$tasks = \Step_Test::select(Body_Checker::class, $mixed->symbols->current, $mixed->resolutions, $mixed->types, new Body_Set(), true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \check_bodies\Body_Worker($task))->check();
}
$joined = (new \check_bodies\Body_Join($mixed->symbols->current, $mixed->resolutions, $mixed->types, new Body_Set(), $tasks))->join($results);
Body_Checking_Test::check(($joined->to_json() === $mixed->bodies->to_json()) && (serialize($mixed) === $mixed_dump),
    'Separate workers preserve call order and shared input purity');
$exported_body = json_decode(json_encode($mixed_body->to_array(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
Body_Checking_Test::check(($exported_body['calls'][0]['result_value_id'] === 0)
    && ($exported_body['statements'][0]['value_id'] === 0) && ($exported_body['statements'][0]['call_count'] === 1),
    'Debug output distinguishes execution without a result from an absent expression');
Body_Checking_Test::edit($value_path, 'function value(): int { return 43; } function empty_value(): void {}');
$with_result = $session->compile($manifest);
Body_Checking_Test::check(($with_result->bodies->for_symbol($answer) !== $mixed_body)
    && (array_column($with_result->bodies->for_symbol($answer)->calls, 'result_value_id') === [1, 2, 0, 4])
    && (serialize($mixed) === $mixed_dump),
    'An unchanged caller is rechecked when a formerly void call gains a result');
Body_Checking_Test::edit($value_path, 'function value(): void { return; } function empty_value(): void {}');
$without_result = $session->compile($manifest);
Body_Checking_Test::check((Body_Checking_Test::facts($without_result) == Body_Checking_Test::facts($mixed))
    && (Body_Checking_Test::facts($without_result) == Body_Checking_Test::facts((new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest))),
    'Removing results again gives the same semantics as both the prior void body and a fresh build');
$baseline = Body_Checking_Test::baseline($session);
Body_Checking_Test::edit($answer_path, 'function answer(): void { return value(); }');
Body_Checking_Test::rejects(static fn() => $session->compile($manifest), 'Return expression produces no value');
Body_Checking_Test::edit($answer_path, 'function answer(): int { return value(); }');
Body_Checking_Test::rejects(static fn() => $session->compile($manifest), 'Return expression produces no value');
Body_Checking_Test::check(Body_Checking_Test::baseline($session) === $baseline,
    'A void call used as a return expression never becomes a bare return, and failure preserves accepted results');

// Default literal typing is provider metadata, not an int-name branch in a worker.
$data = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
$data['types'][] = ['name' => 'Counter', 'namespace' => '', 'kind' => 'integer', 'bit_width' => 9, 'signed' => true, 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']];
$data['literal_types']['integer']['name'] = 'Counter';
$data['entry_return_type']['name'] = 'Counter';
file_put_contents($catalog_path, json_encode($data, JSON_THROW_ON_ERROR));
Body_Checking_Test::edit($main_path, 'return answer(); function stable(): Counter { return 7; }');
Body_Checking_Test::edit($answer_path, 'function answer(): Counter { return value(); }');
Body_Checking_Test::edit($value_path, 'function value(): Counter { return 255; }');
$counter = $session->compile($manifest);
$counter_id = $counter->types->types->find_type('Counter');
Body_Checking_Test::check(($counter->inputs->context->full_rebuild) && ($counter->bodies->for_symbol($value)->values[0]->type_id === $counter_id),
    'Changed literal policy selects full work through the same stages and uses a provider-supplied name/width');
Body_Checking_Test::edit($value_path, 'function value(): Counter { return 256; }');
Body_Checking_Test::rejects(static fn() => $session->compile($manifest), 'outside the range of Counter');
Body_Checking_Test::edit($value_path, 'function value(): Counter { ' . str_repeat('value();', 1200) . 'return 42; }');
$many = $session->compile($manifest);
Body_Checking_Test::check((count($many->bodies->for_symbol($value)->statements) === 1201)
    && (count($many->bodies->for_symbol($value)->values) === 1201),
    'One linear statement loop and value path handle a large body without recursion across calls');
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest);

// Compare values, conversions and target names, not unrelated stores' numeric IDs.
Body_Checking_Test::check(Body_Checking_Test::facts($many) == Body_Checking_Test::facts($fresh),
    'Fresh and incremental typed bodies have the same actual values, targets and conversions');
$export = json_decode($many->to_json(), true, 512, JSON_THROW_ON_ERROR);
Body_Checking_Test::check((count($export['bodies']) === 4) && ($export['bodies'][0]['values'][0]['type_id'] > 0),
    'Debug output exposes actual typed body records and their dependencies');

$types = clone $counter->types->types;
$unsigned = new \type_model\named_type_definition('UnsignedCounter', '', $types->representation_for_type($counter_id), $types->definition_for_type($counter_id)->lifetime, false);
$unsigned_id = \resolve_types\Type_Cache::materialize($types, $unsigned);
$context = new \resolve_types\Type_Resolution($types, $counter->types->catalog, $counter->types->entry, $counter->types->signatures(), [], $counter->resolutions);
Body_Checking_Test::check(($types->representation_for_type($counter_id) === $types->representation_for_type($unsigned_id))
    && (Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($counter_id, $unsigned_id, \type_model\conversion_purpose::implicit_boundary)) === null),
    'The conversion bridge never substitutes representation equality for type compatibility');
foreach (['Missing', 'float'] as $bad_name) {
    $bad_catalog = $data;
    $bad_catalog['literal_types']['integer']['name'] = $bad_name;
    Body_Checking_Test::rejects(static fn() => \load_runtime\Catalog_Syntax::parse(json_encode($bad_catalog, JSON_THROW_ON_ERROR)),
        'Integer literal type must refer to a defined integer');
}
echo "body checking ok: real literal/call/return typing, conversion boundary, ranges, fixed workers, reuse, callee contract invalidation, rollback/repair, metadata defaults, large bodies, fresh comparison and debug exports\n";
