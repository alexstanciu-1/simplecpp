<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use load_runtime\Language_Types;
use type_model\named_type_definition;
use resolve_types\Type_Resolver;
use type_model\Type_Store;
use type_model\type_context;
use type_model\representation_kind;
use type_model\representation_record;

class Type_Definitions_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    /** Require a type-contract rejection and return its diagnostic for assertions. */
    public static function rejects(callable $action): void
    {
        try {
            $action();
        }
        catch (LogicException $error) {
            return;
        }
        throw new Exception('Expected an invalid definition to be rejected');
    }
}

// Provider fixtures differ only in meaning, not width. No compiler name cases.
$data = ['schema_version' => 1, 'provider' => 'fixture', 'representation_scope' => 'language_values', 'literal_types' => ['integer' => ['name' => 'Left', 'namespace' => 'fixture']], 'entry_return_type' => ['name' => 'Left', 'namespace' => 'fixture'], 'types' => [
        ['name' => 'Left', 'namespace' => 'fixture', 'kind' => 'integer', 'bit_width' => 23, 'signed' => true, 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']],
        ['name' => 'Right', 'namespace' => 'fixture', 'kind' => 'integer', 'bit_width' => 23, 'signed' => false, 'lifetime' => ['copy' => 'value', 'cleanup' => 'none']],
    ]];
$catalog = \load_runtime\Catalog_Syntax::parse(json_encode($data, JSON_THROW_ON_ERROR));
$types = new Type_Store(new type_context('fixture', $catalog->content_key, $catalog->representation_scope));
$left = \resolve_types\Type_Cache::materialize($types, $catalog->find_type('Left', 'fixture'));
$right = \resolve_types\Type_Cache::materialize($types, $catalog->find_type('Right', 'fixture'));
Type_Definitions_Test::check(($left !== $right)
    && ($types->representation_for_type($left) === $types->representation_for_type($right))
    && ($types->definition_for_type($left)->signed === true)
    && ($types->definition_for_type($right)->signed === false)
    && ($types->definition_for_type($left) === $catalog->find_type('Left', 'fixture')),
    'Canonical IDs expose shared complete definitions while equal-width shapes remain shared');
$before = serialize($types);
$row = $types->type_by_id($left);
\resolve_types\Type_Cache::materialize($types, $catalog->find_type('Left', 'fixture'));
Type_Definitions_Test::check(($types->type_by_id($left) === $row) && (serialize($types) === $before),
    'Repeated materialization keeps the same row and definition');

$replacement = new named_type_definition('Left', 'fixture', $types->representation_for_type($left), $catalog->find_type('Left', 'fixture')->lifetime, false);
Type_Definitions_Test::rejects(static fn() => $types->bind_definition($left, $replacement));
Type_Definitions_Test::rejects(static fn() => $types->bind_definition($left, $types->definition_for_type($right)));
$wider = $types->intern_integer(24);
$before = serialize($types);
Type_Definitions_Test::rejects(static fn() => $types->set_representation($left, $wider));
Type_Definitions_Test::check(serialize($types) === $before, 'Invalid binding/replacement must not modify the type');
$candidate = clone $types;
$candidate->invalidate_definition($left);
Type_Definitions_Test::rejects(static fn() => $candidate->definition_for_type($left));
Type_Definitions_Test::check($candidate->needs_representation($left) && $candidate->is_declared($left),
    'Invalidation clears semantic facts and representation together, retaining identity/declaration');
\resolve_types\Type_Cache::materialize($candidate, $replacement);
Type_Definitions_Test::check(($candidate->definition_for_type($left)->signed === false)
    && ($candidate->representation_for_type($left) === $types->representation_for_type($left))
    && ($candidate->type_by_id($right) === $types->type_by_id($right))
    && (serialize($types) === $before), 'A semantic-only change replaces the candidate without mutating prior meaning');
Type_Definitions_Test::rejects(static fn() => new named_type_definition('MissingSign', '', $types->representation_for_type($left), $catalog->find_type('Left', 'fixture')->lifetime));
Type_Definitions_Test::rejects(static fn() => new named_type_definition('VoidWithSign', '', new representation_record(representation_kind::void_type, null), null, false));
Type_Definitions_Test::rejects(static fn() => new named_type_definition('MissingLifetime', '', $types->representation_for_type($left), null, true));
Type_Definitions_Test::rejects(static fn() => new named_type_definition('VoidWithLifetime', '', new representation_record(representation_kind::void_type, null), $catalog->find_type('Left', 'fixture')->lifetime));
Type_Definitions_Test::check(($types->definition_for_type($left)->lifetime === $catalog->find_type('Left', 'fixture')->lifetime)
    && ($types->definition_for_type($left)->lifetime->copy === \type_model\copy_kind::value)
    && ($types->definition_for_type($right)->lifetime->cleanup === \type_model\cleanup_kind::none),
    'Unfamiliar provider names and widths expose explicit shared lifetime facts');

foreach ([null, [], ['copy' => 'value'], ['cleanup' => 'none'], ['copy' => 'managed', 'cleanup' => 'none'],
        ['copy' => 'value', 'cleanup' => 'destroy'], ['copy' => true, 'cleanup' => 'none'],
        ['copy' => 'value', 'cleanup' => 'none', 'extra' => true]] as $invalid_lifetime) {
    $invalid = $data;
    $invalid['types'][0]['lifetime'] = $invalid_lifetime;
    Type_Definitions_Test::rejects(static fn() => \load_runtime\Catalog_Syntax::parse(json_encode($invalid, JSON_THROW_ON_ERROR)));
}
$invalid = $data;
unset($invalid['types'][0]['lifetime']);
Type_Definitions_Test::rejects(static fn() => \load_runtime\Catalog_Syntax::parse(json_encode($invalid, JSON_THROW_ON_ERROR)));

foreach ([null, 1, 'yes', []] as $capability) {
    $invalid = $data;
    $invalid['types'][0]['struct_field'] = $capability;
    Type_Definitions_Test::rejects(static fn() => \load_runtime\Catalog_Syntax::parse(json_encode($invalid, JSON_THROW_ON_ERROR)));
}

// Real source-to-signature integration, using unchanged source files. A provider
// signedness edit must invalidate results even though the representation is equal.
$catalog_path = getcwd() . '/language-types.json';
$catalog_text = file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json');
$bundled = \load_runtime\Catalog_Syntax::parse($catalog_text);
foreach (['int', 'uint32', 'float'] as $name) {
    $definition = $bundled->find_type($name, '');
    Type_Definitions_Test::check(($definition->lifetime->copy === \type_model\copy_kind::value)
        && ($definition->lifetime->cleanup === \type_model\cleanup_kind::none), 'Every supported scalar has explicit value-copy/no-cleanup facts');
}
Type_Definitions_Test::check($bundled->find_type('void', '')->lifetime === null, 'Void has no value lifetime');
file_put_contents($catalog_path, $catalog_text);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile('../fixtures/three_files/project.json');
$answer = $first->symbols->current->find_symbol('answer', '', \collect_symbols\symbol_kind::function_symbol);
$signature = $first->types->for_symbol($answer);
$id = $first->types->types->representation_by_id($signature->representation_id)->payload->return_type;
Type_Definitions_Test::check(($first->types->types->definition_for_type($id) === $first->types->catalog->find_type('int', ''))
    && ($first->types->types->definition_for_type($id)->signed === true), 'Source signatures expose authoritative meaning by ID');
$retained = serialize($first);
$warm = $session->compile('../fixtures/three_files/project.json');
Type_Definitions_Test::check($warm->types->types->type_by_id($id) === $first->types->types->type_by_id($id),
    'Warm builds share complete immutable type rows');
$invalid_catalog = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
foreach ($invalid_catalog['types'] as &$definition) {
    if ($definition['name'] === 'int') {
        $definition['lifetime']['cleanup'] = 'destroy';
    }
}
unset($definition);
file_put_contents($catalog_path, json_encode($invalid_catalog, JSON_THROW_ON_ERROR));
try {
    $session->compile('../fixtures/three_files/project.json');
    throw new Exception('Expected unsupported lifetime contract rejection');
}
catch (RuntimeException $error) {
    Type_Definitions_Test::check(str_contains($error->getMessage(), 'Unsupported lifetime contract'), $error->getMessage());
}
Type_Definitions_Test::check(($session->observed?->types === $warm->types) && ($session->observed?->bodies === $warm->bodies)
    && (serialize($first) === $retained), 'Unsupported provider lifetime edits cannot leak into retained outputs');
file_put_contents($catalog_path, $catalog_text);
$repair = $session->compile('../fixtures/three_files/project.json');
Type_Definitions_Test::check(($repair->types->types->type_by_id($id) === $first->types->types->type_by_id($id))
    && ($repair->bodies->for_symbol($answer) === $first->bodies->for_symbol($answer)),
    'Repair reuses the last accepted shared definition and body');
$changed_catalog = json_decode($catalog_text, true, 512, JSON_THROW_ON_ERROR);
foreach ($changed_catalog['types'] as &$definition) {
    if ($definition['name'] === 'int') {
        $definition['signed'] = false;
    }
}
unset($definition);
file_put_contents($catalog_path, json_encode($changed_catalog, JSON_THROW_ON_ERROR));
$changed = $session->compile('../fixtures/three_files/project.json');
$new_id = $changed->types->types->find_type('int');
Type_Definitions_Test::check(($changed->inputs->context->full_rebuild)
    && ($changed->types->for_symbol($answer) !== $signature)
    && ($changed->types->types->definition_for_type($new_id)->signed === false)
    && ($changed->types->types->representation_for_type($new_id) == $first->types->types->representation_for_type($id))
    && (serialize($first) === $retained), 'Signedness-only edits use full selection and cannot preserve stale semantic results');
$export = json_decode($changed->to_json(), true, 512, JSON_THROW_ON_ERROR);
Type_Definitions_Test::check($export['types']['types']['types'][$new_id - 1]['definition']['signed'] === false,
    'Existing debug output exposes the complete bound definition');
Type_Definitions_Test::check($export['types']['types']['types'][$new_id - 1]['definition']['lifetime'] === ['copy' => 'value', 'cleanup' => 'none', 'destructor' => null, 'copy_constructor' => null,
        'construction' => 'zero', 'default_constructor' => null, 'assignment' => 'value', 'copy_assignment' => null, 'expiring' => 'value', 'move_constructor' => null],
    'Debug output exposes lifetime facts through the shared definition');
echo "type definitions ok: signedness by ID, shared shapes and definitions, coherent invalidation, pure candidates, pipeline reuse/provider edits and exports\n";
