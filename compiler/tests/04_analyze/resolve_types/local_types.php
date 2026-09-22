<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/bootstrap.php';

use compile\Phases;
use compile\Update_Context;
use resolve_types\Local_Type_Resolver as Resolver;
use resolve_types\Signature_Resolver;
use resolve_types\Type_Resolution;
use resolve_types\Type_Resolver;
use type_model\Type_Store;
use collect_symbols\symbol_kind;

class Local_Types_Test
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
        throw new Exception('Expected rejection: ' . $reason);
    }

    /** Prepare current declarations and bindings against the selected catalog. */
    public static function frontend(\compile\Compile_Result $baseline, ?\type_model\Type_Catalog $catalog = null): array
    {
        $update = new Update_Context();
        $sources = \Step_Test::run(new \read_sources\Source_Discovery($baseline->inputs->manifest, $baseline->inputs->sources));
        $lexical = Phases::run_tokenization($sources, $baseline->inputs->tokens, $update);
        $parsed = Phases::run_parsing($lexical->sources, $lexical->tokens, $baseline->inputs->frontends, $update);
        $symbols = \Step_Test::run(new \collect_symbols\Declaration_Collector($baseline->symbols->current, $lexical->sources, $parsed, false))->current;
        $names = Phases::run_symbols($symbols, $baseline->resolutions, $update, $catalog ?? $baseline->types->catalog);
        $entry = \Step_Test::run(new \resolve_types\Entry_Resolver($lexical->sources, $symbols, $baseline->types->catalog));
        return [$symbols, $names, $entry];
    }
}

class Counting_Local_Types extends Type_Store
{
    public int $completions = 0;

    public function set_representation(int $type_id, int $representation_id): void
    {
        ++$this->completions;
        parent::set_representation($type_id, $representation_id);
    }
}

$path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$baseline = $session->compile($path);
$before = serialize($baseline);
$root = $baseline->inputs->manifest->directory;
$main = $root . '/src/main.phs';
$value = $root . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);

// Deliberately incompatible initializers: this phase resolves annotations only.
Local_Types_Test::edit($main, '$x int = answer(); { $y uint32 = 42; $x = $x; } return $x;');
Local_Types_Test::edit($value, 'function value(): int { $x uint32 = 42; $y float = 42; $z int = 42; return $z; }');
[$symbols, $names, $entry] = Local_Types_Test::frontend($baseline);
$main_id = $entry->symbol->symbol_id;
$value_id = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$catalog = $baseline->types->catalog;
$candidate = new Counting_Local_Types($baseline->types->types->context);
\resolve_types\Type_Cache::materialize($candidate, $catalog->integer_literal_type);
\resolve_types\Type_Cache::materialize($candidate, $catalog->boolean_type);
$tasks = Resolver::select($symbols, $names, $candidate, null, false, $entry);
Local_Types_Test::check(count($tasks) === 2, 'Only callables containing declarations need local type work');
$fixed = serialize([$symbols, $names, $catalog, $candidate, $baseline]);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = Resolver::resolve($symbols, $names, $catalog, $task);
}
Local_Types_Test::check(serialize([$symbols, $names, $catalog, $candidate, $baseline]) === $fixed, 'Workers return definition references without materializing shared types');
$signature_tasks = Signature_Resolver::select($symbols, null, $candidate, false, $entry);
$signature_results = [];
foreach ($signature_tasks as $task) {
    $signature_results[] = Signature_Resolver::resolve($symbols, $catalog, $task, $entry, $names);
}
$contracts = (new \resolve_types\Signature_Join($symbols, $catalog, $candidate, null, $signature_tasks, $entry, $names))->join($signature_results);
$local_rows = (new \resolve_types\Local_Type_Join($symbols, $names, $catalog, $candidate, null, $tasks, $entry, $contracts))->join($results);
$types = new Type_Resolution($candidate, $catalog, $entry, $contracts, $local_rows, $names);
$int = $candidate->find_type('int');
$uint = $candidate->find_type('uint32');
$float = $candidate->find_type('float');
Local_Types_Test::check(($candidate->completions === 4) && ($types->locals_for($main_id)->type_ids === [$int, $uint])
    && ($types->locals_for($value_id)->type_ids === [$uint, $float, $int]), 'Repeated and cross-file types share one materialization per definition');
Local_Types_Test::check(($types->locals_for($main_id)->type_for(1) === $types->signature_for($main_id)->return_type)
    && ($types->locals_for($main_id)->names === $names->for_symbol($main_id)), 'Local and return annotations share IDs; local facts retain the existing binding owner');
Local_Types_Test::rejects(static fn() => $types->locals_for($main_id)->type_for(0), 'Missing resolved local type');
Local_Types_Test::check($types->locals_for($symbols->find_symbol('answer', '', symbol_kind::function_symbol)) === null, 'No empty local datasets for callables without locals');
$export = json_decode($types->to_json(), true, 512, JSON_THROW_ON_ERROR);
Local_Types_Test::check((count($export['local_types']) === 2) && ($export['local_types'][0]['locals'][0]['local_id'] === 1),
    'Exports report actual per-callable local-to-type associations');
$typed_before = serialize($types);
$warm = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $catalog, clone $candidate, $types, false, $entry, $names));
Local_Types_Test::check((Resolver::select($symbols, $names, $warm->types, $types, false, $entry) === [])
    && ($warm->locals_for($main_id) === $types->locals_for($main_id)) && ($warm->to_json() === $types->to_json()), 'Warm stage selects no local work and shares complete associations');
$full = new Update_Context();
$full->full_rebuild = true;
$fresh = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $catalog, new Type_Store($candidate->context), null, $full->full_rebuild, $entry, $names));
Local_Types_Test::check(($fresh->to_json() === $types->to_json()) && ($fresh->locals_for($main_id) !== $types->locals_for($main_id))
    && (serialize($types) === $typed_before) && (serialize($baseline) === $before), 'Fresh/full selection uses the same path with identical exports and no input mutations');

// Reject incomplete and stale joins before writing any candidate record.
foreach ([[], [...$results, $results[0]]] as $bad) {
    $empty = new Type_Store($candidate->context);
    $empty_before = $empty->to_json();
    Local_Types_Test::rejects(static fn() => (new \resolve_types\Local_Type_Join($symbols, $names, $catalog, $empty, null, $tasks, $entry, []))->join($bad),
        $bad === [] ? 'Incomplete' : 'duplicate');
    Local_Types_Test::check($empty->to_json() === $empty_before, 'Invalid batches never partly populate the type store');
}
$wrong = new \resolve_types\local_type_request($results[0]->owner, $results[0]->names,
    array_fill(0, count($results[0]->definitions), $catalog->find_type('int', '')));
Local_Types_Test::rejects(static fn() => (new \resolve_types\Local_Type_Join($symbols, $names, $catalog, new Type_Store($candidate->context), null, $tasks, $entry, []))->join([$wrong, $results[1]]), 'Stale local type definition');
Local_Types_Test::rejects(static fn() => (new \resolve_types\Local_Type_Join($symbols, $names, $catalog, $types->types, $types, [], $entry, []))->join([]), 'separate candidate');
Local_Types_Test::rejects(static fn() => new \resolve_types\Local_Types($names->for_symbol($main_id), [$int]), 'Incomplete');
Local_Types_Test::rejects(static fn() => new \resolve_types\Local_Types($names->for_symbol($main_id), [$int, 0]), 'Invalid');

// Same numeric IDs from another lineage do not establish reuse.
$other = new Type_Store($candidate->context);
\resolve_types\Type_Cache::materialize($other, $catalog->find_type('float', ''));
Local_Types_Test::check(count(Resolver::select($symbols, $names, $other, $types, false, $entry)) === 2, 'Unrelated type cache cannot reuse old local IDs');
$invalidated = clone $candidate;
$invalidated->invalidate_definition($uint);
Local_Types_Test::check(count(Resolver::select($symbols, $names, $invalidated, $types, false, $entry)) === 2,
    'Invalidating a type used only by locals selects all affected associations');
$recompleted = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $catalog, $invalidated, $types, false, $entry, $names));
Local_Types_Test::check(($recompleted->locals_for($main_id) !== $types->locals_for($main_id))
    && ($recompleted->locals_for($main_id)->type_ids === [$int, $uint])
    && (serialize($types) === $typed_before), 'Recompletion retains canonical IDs while refreshing associations without editing prior records');

// Annotation edits replace only current associations; removed locals retire theirs.
Local_Types_Test::edit($main, '$x float = 42; return 42;');
[$edited_symbols, $edited_names, $edited_entry] = Local_Types_Test::frontend($baseline);
Local_Types_Test::rejects(static fn() => (new \resolve_types\Local_Type_Join($edited_symbols, $edited_names, $catalog, clone $candidate, $types, [$edited_entry->symbol], $edited_entry, []))->join([$results[1]]), 'stale');
$edited = \Step_Test::run(new \resolve_types\Type_Resolver($edited_symbols, $catalog, clone $candidate, $types, false, $edited_entry, $edited_names));
Local_Types_Test::check(($edited->locals_for($main_id)->type_ids === [$float]) && ($types->locals_for($main_id)->type_ids === [$int, $uint]), 'Changed annotations replace associations and preserve old results');
Local_Types_Test::edit($main, $original_main);
[$removed_symbols, $removed_names, $removed_entry] = Local_Types_Test::frontend($baseline);
$removed = \Step_Test::run(new \resolve_types\Type_Resolver($removed_symbols, $catalog, clone $candidate, $types, false, $removed_entry, $removed_names));
Local_Types_Test::check($removed->locals_for($main_id) === null, 'Removing the last local removes its association container');

// Local-only provider facts are consumed from the catalog, not a consumer name table.
$data = json_decode(file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
$new = $data['types'][1];
$new['name'] = 'Counter';
$new['bit_width'] = 16;
$data['types'][] = $new;
$custom = \load_runtime\Catalog_Syntax::parse(json_encode($data, JSON_THROW_ON_ERROR));
Local_Types_Test::edit($main, '$x Counter = 42; return 42;');
[$custom_symbols, $custom_names, $unused_entry] = Local_Types_Test::frontend($baseline, $custom);
$custom_entry = new \resolve_types\entry_contract($custom_symbols->symbol_by_id($main_id), $custom->entry_return_type);
$context = new \type_model\type_context($candidate->context->configuration_key, $custom->content_key, $custom->representation_scope);
$custom_types = \Step_Test::run(new \resolve_types\Type_Resolver($custom_symbols, $custom, new Type_Store($context), $types, $full->full_rebuild, $custom_entry, $custom_names));
$counter = $custom_types->locals_for($main_id)->type_for(1);
Local_Types_Test::check(($custom_types->definition_for($counter) === $custom->find_type('Counter', ''))
    && ($custom_types->types->representation_for_type($counter)->payload->bit_width === 16), 'New authoritative named types work through the common resolver');

foreach ([['$x Missing = 42; return 42;', 'Missing', 'Unknown or unsupported local type'],
        ['$x void = 42; return 42;', 'void', 'A local requires a value type']] as [$text, $anchor, $message]) {
    Local_Types_Test::edit($main, $text);
    $error = Local_Types_Test::rejects(static fn() => $session->compile($path), $message);
    Local_Types_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main)
        && ($error->start === strpos($text, $anchor)) && ($error->length === strlen($anchor)), 'Type diagnostics anchor the annotation');
    Local_Types_Test::check((serialize($baseline) === $before) && ($session->observed?->types === $baseline->types), 'Unknown or non-value local types preserve accepted snapshots');
}
Local_Types_Test::edit($main, '$x int = 42; return $x;');
Local_Types_Test::edit($value, $original_value);
Local_Types_Test::check($session->compile($path)->llvm !== null, 'Resolved local types reach LLVM through the common path');
Local_Types_Test::edit($main, $original_main);
Local_Types_Test::edit($value, $original_value);
Local_Types_Test::check($session->compile($path)->llvm->ir_by_file() === $baseline->llvm->ir_by_file(), 'Failure followed by repair restores real compilation through the common path');
echo "local types ok: shared annotation resolution, one materialization, cross-file IDs, fixed workers, joins, reuse, edits, removal, metadata, exports and repair\n";
