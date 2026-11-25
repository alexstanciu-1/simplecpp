<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use parse\Syntax_Access;
use parse\File_Parser;
use read_sources\Source_Buffer;
use type_model\floating_format;
use type_model\Type_Store;
use type_model\type_context;
use tokenize\Tokenizer;

class Type_Reuse_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    // Test-owned definitions demonstrate work avoidance at the store boundary.
    // This is not a production provider loader or source type-resolution phase.
    public static function use_type(Type_Store $types, string $name, string $namespace_name,
        callable $definition, int &$definition_calls): int
    {
        $id = $types->reference_type($name, $namespace_name);
        if (!$types->is_declared($id)) {
            $types->declare_type($name, $namespace_name);
        }
        if ($types->needs_representation($id)) {
            ++$definition_calls;
            $types->set_representation($id, $definition($types));
        }
        return $id;
    }
}

$types = new Type_Store(new type_context('fixture-config', 'fixture-providers', 'fixture-target'));
$pending = $types->reference_type('Measure', 'fixture');
$pending_row = $types->type_by_id($pending);
Type_Reuse_Test::check(($types->reference_type('Measure', 'fixture') === $pending)
    && $types->needs_representation($pending)
    && ($types->type_by_id($pending) === $pending_row),
    'Repeated pending references must share identity without claiming completion');

$source = '';
for ($i = 0; $i < 200; ++$i) {
    $source .= 'function measure_' . $i . '(): Measure { return 1; } ';
    $source .= 'function ratio_' . $i . '(): Ratio { return 2; } ';
}
$file = (new File_Parser(\tokenize\File_Tokenizer::tokenize(new Source_Buffer(1, 'reuse.phs', 0, $source))))->parse();
$syntax_before = serialize($file);
$integer_calls = 0;
$float_calls = 0;
$integer = static fn(Type_Store $store): int => $store->intern_integer(32);
$float = static fn(Type_Store $store): int => $store->intern_float(floating_format::ieee_binary64);
$bindings = [];
foreach ($file->defined_entities as $declaration_id)
{
    $annotation_id = Syntax_Access::function_parts($file->syntax, $declaration_id)->return_type_id;
    $node = $file->syntax->nodes[$annotation_id - 1];
    $name = substr($source, $node->start, $node->length);

    // Fixture-only mapping. Compiler behavior does not depend on these names.
    $bindings[$annotation_id] = $name === 'Measure'
        ? Type_Reuse_Test::use_type($types, $name, 'fixture', $integer, $integer_calls)
        : Type_Reuse_Test::use_type($types, $name, 'fixture', $float, $float_calls);
}
$ratio = $types->find_type('Ratio', 'fixture');
Type_Reuse_Test::check(($integer_calls === 1) && ($float_calls === 1)
    && (count($bindings) === 400) && (count(array_unique($bindings)) === 2)
    && ($ratio !== $pending) && (serialize($file) === $syntax_before),
    'Repeated AST annotations resolve to two shared IDs with one definition each and unchanged syntax');

$before = $types->to_json();
$row = $types->type_by_id($pending);
$representation = $types->representation_for_type($pending);
$never = static function (Type_Store $store): int {
    throw new Exception('Cached definition was recomputed');
};
for ($i = 0; $i < 1000; ++$i) {
    Type_Reuse_Test::check(Type_Reuse_Test::use_type($types, 'Measure', 'fixture', $never, $integer_calls) === $pending,
        'All warm lookups retain the original type ID');
}
Type_Reuse_Test::check(($integer_calls === 1) && ($types->to_json() === $before)
    && ($types->type_by_id($pending) === $row) && ($types->representation_for_type($pending) === $representation),
    'Warm references must not construct definitions, rows or replacement representations');

// Same spelling in another namespace means another identity, even when the
// provider chooses the same representation. There is no reserved primitive range.
$other_calls = 0;
$other = Type_Reuse_Test::use_type($types, 'Measure', 'other', $integer, $other_calls);
Type_Reuse_Test::check(($other !== $pending) && ($other_calls === 1)
    && ($types->representation_for_type($other) === $representation),
    'Qualified identity and representation sharing remain separate');

// Definition changes are explicit candidate work. Previous readers retain their
// complete table; repeated invalidation of a pending record creates no new row.
$before = $types->to_json();
$candidate = clone $types;
$candidate->invalidate_definition($pending);
$invalidated_row = $candidate->type_by_id($pending);
$candidate->invalidate_definition($pending);
Type_Reuse_Test::check($candidate->needs_representation($pending)
    && ($candidate->type_by_id($pending) === $invalidated_row)
    && (!$types->needs_representation($pending)) && ($types->to_json() === $before),
    'Invalidation retains identity and never mutates the accepted snapshot');
$wide_calls = 0;
$same_id = Type_Reuse_Test::use_type($candidate, 'Measure', 'fixture',
    static fn(Type_Store $store): int => $store->intern_integer(64), $wide_calls);
Type_Reuse_Test::check(($same_id === $pending) && ($wide_calls === 1)
    && ($candidate->representation_for_type($pending)->payload->bit_width === 64)
    && ($types->representation_for_type($pending)->payload->bit_width === 32)
    && ($candidate->type_by_id($ratio) === $types->type_by_id($ratio)),
    'A changed definition keeps its ID; unrelated cached definitions remain shared');

$failed = clone $candidate;
$failed->invalidate_definition($pending);
$failed_calls = 0;
$rejected = false;
try {
    Type_Reuse_Test::use_type($failed, 'Measure', 'fixture', $never, $failed_calls);
}
catch (Exception $error) {
    $rejected = true;
}
Type_Reuse_Test::check(($rejected) && ($failed_calls === 1) && $failed->needs_representation($pending)
    && (!$candidate->needs_representation($pending)), 'Failed work must not be cached as complete');

$rejected = false;
try {
    $types->declare_type('Measure', 'fixture');
}
catch (InvalidArgumentException $error) {
    $rejected = true;
}
Type_Reuse_Test::check($rejected, 'Reference reuse must not weaken duplicate declaration checks');
foreach ([0, -1, 999999] as $id)
{
    foreach (['needs_representation', 'invalidate_definition'] as $method)
    {
        $rejected = false;
        try {
            $types->$method($id);
        }
        catch (OutOfBoundsException $error) {
            $rejected = true;
        }
        Type_Reuse_Test::check($rejected, 'Unknown IDs must fail explicitly');
    }
}

echo "type reuse ok: parsed occurrences share IDs, lazy definition work, warm cache purity, namespaces, candidate invalidation and failure isolation; signature integration is tested separately\n";
