<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use collect_symbols\symbol_kind;
use parse\Syntax_Access as Syntax;
use resolve_symbols\reference_kind;
use resolve_symbols\name_role;

/** Test snapshots use the real frontend, collection and binding stages, with no concrete preparation. */
final class template_binding_snapshot
{
    public function __construct(public readonly \compile\Input_Snapshot $inputs,
        public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names,
        public readonly \type_model\Type_Catalog $catalog)
    {
    }
}

final class Template_Binding_Test
{
    /** Build selected frontend/name work, preserving the previous snapshot for the one-increment proof. */
    public static function bind(string $root, ?template_binding_snapshot $previous = null,
        ?\type_model\Type_Catalog $catalog = null): template_binding_snapshot
    {
        [$input, $symbols, $catalog] = self::prepare($root, $previous, $catalog);
        $names = \compile\Phases::run_symbols($symbols, $previous?->names ?? new \resolve_symbols\Resolution_Set(), $input->context, $catalog);
        return new template_binding_snapshot($input, $symbols, $names, $catalog);
    }

    /** Prepare fixed declarations/catalog before selecting binding work; no semantic result is invented. */
    public static function prepare(string $root, ?template_binding_snapshot $previous = null,
        ?\type_model\Type_Catalog $catalog = null): array
    {
        $input = new \compile\Input_Snapshot();
        $input->context = new \compile\Update_Context();
        $input->context->full_rebuild = $previous === null;
        $input->manifest = Step_Test::run(new \read_manifest\Manifest_Reader($root . '/project.json'));
        $sources = Step_Test::run(new \read_sources\Source_Discovery($input->manifest,
            $previous?->inputs->sources ?? new \read_sources\Source_Set()));
        $lexical = \compile\Phases::run_tokenization($sources, $previous?->inputs->tokens ?? new \tokenize\Token_Set(), $input->context);
        $input->sources = $lexical->sources;
        $input->tokens = $lexical->tokens;
        $input->frontends = \compile\Phases::run_parsing($input->sources, $input->tokens,
            $previous?->inputs->frontends ?? new \parse\Frontend_Set(), $input->context);
        $symbols = Step_Test::run(new \collect_symbols\Declaration_Collector($previous?->symbols ?? new \collect_symbols\Symbol_Store(),
            $input->sources, $input->frontends, $input->context->full_rebuild))->current;
        $catalog ??= $previous?->catalog ?? Step_Test::run(new \load_runtime\Language_Types());
        return [$input, $symbols, $catalog];
    }

    /** Make a self-contained source project for a binding or diagnostic proof. */
    public static function project(string $root, string $source): void
    {
        mkdir($root);
        file_put_contents($root . '/project.json', json_encode(['source_folders' => ['.'], 'entry' => 'main.phs'], JSON_THROW_ON_ERROR));
        file_put_contents($root . '/main.phs', $source);
    }

    /** Clone only the result envelope for deliberately invalid worker-result proofs. */
    public static function replacement(\resolve_symbols\Symbol_Resolution $r, ?array $names = null,
        ?array $parameters = null, ?array $applications = null, ?array $constants = null): \resolve_symbols\Symbol_Resolution
    {
        return new \resolve_symbols\Symbol_Resolution($r->symbol_id, $r->syntax, $r->bindings,
            $r->scopes, $r->locals, $r->local_bindings, $names ?? $r->name_bindings,
            $parameters ?? $r->template_parameters, $constants ?? $r->constants, $applications ?? $r->applications);
    }
}

$root = getcwd() . '/template-bindings';
Template_Binding_Test::project($root, '$item plain = new plain(); return identity($item->value) + 7;');
$definitions = <<<'PHS'
template<typename Key, typename Value>
struct dictionary { public Key $key; public Value $value; }
template<typename T, int N>
struct sample { public T $value; public dictionary<T, plain> $items; }
template<typename T, T N>
struct dependent_value { public T $value; }
template<typename T>
struct recursive { public recursive<T> $next; }
template<typename T, int N>
constexpr function choose($value T): T
{
    const LOCAL: int = N + 1;
    if constexpr (LOCAL) { $copy T = $value; return helper<T>($copy); }
    else if consteval { return $value; }
    else { return $value; }
}
template<typename T>
function read_member($value T): int { return $value->member; }
template<typename T>
function helper($value T): T { return $value; }
struct plain { public int32 $value; }
function identity($value int): int { return $value; }
PHS;
file_put_contents($root . '/definitions.phs', $definitions);
$first = Template_Binding_Test::bind($root);
$before = serialize($first);
$symbols = $first->symbols;
$names = $first->names;
$catalog = $first->catalog;
$sample_id = $symbols->find_symbol('sample', '', symbol_kind::template_struct);
$sample = $symbols->symbol_by_id($sample_id);
$sample_names = $names->for_symbol($sample_id);
$dictionary_id = $symbols->find_symbol('dictionary', '', symbol_kind::template_struct);
$dictionary_names = $names->for_symbol($dictionary_id);
Check::check(($sample_id !== 0) && ($sample_id !== $dictionary_id) && !$sample->has_executable_body()
    && ($symbols->find_symbol('sample', '', symbol_kind::struct_symbol) === 0), 'Template definitions have project identities without pretending to be concrete types');
Check::check((count($sample_names->template_parameters) === 2) && ($sample_names->scopes === [])
    && ($sample_names->template_parameters[0]->type_syntax_id === 0)
    && ($sample_names->template_parameters[1]->type_syntax_id !== 0), 'Ordered template parameter scopes are separate from runtime block/local scopes');
$references = array_values(array_filter($sample_names->name_bindings, static fn($b) => $b->kind === reference_kind::template_parameter));
Check::check((count($references) === 2) && ($references[0]->target === 0) && ($references[1]->target === 0),
    'Repeated T occurrences refer to one owner-relative parameter position');
Check::check(($dictionary_names->name_bindings[0]->target === 0)
    && ($dictionary_names->symbol_id !== $sample_names->symbol_id), 'The same parameter position in two definitions is not the same identity');
Check::check((count($sample_names->applications) === 1) && ($sample_names->applications[0]->definition === $symbols->symbol_by_id($dictionary_id)),
    'Applications retain the formal contract they matched without creating an instance');
$provided = array_values(array_filter($sample_names->name_bindings, static fn($b) => $b->kind === reference_kind::provided_type));
Check::check((count($provided) === 1) && ($provided[0]->target === $catalog->find_type('int', '')), 'Concrete parameter annotations bind the authoritative provider declaration');
$plain_id = $symbols->find_symbol('plain', '', symbol_kind::struct_symbol);
$source_types = array_values(array_filter($sample_names->name_bindings, static fn($b) => $b->kind === reference_kind::source_type));
Check::check((count($source_types) === 1) && ($source_types[0]->target === $plain_id), 'Forward source type names bind before layout preparation');
$dependent = $names->for_symbol($symbols->find_symbol('dependent_value', '', symbol_kind::template_struct));
Check::check($dependent->name_bindings[0]->kind === reference_kind::template_parameter, 'A later value-parameter annotation can reference an earlier type parameter');
$choose_id = $symbols->find_symbol('choose', '', symbol_kind::template_function);
$choose = $names->for_symbol($choose_id);
Check::check(($choose->parameter_count === 1) && (count($choose->template_parameters) === 2)
    && (count($choose->constants) === 1) && (count($choose->applications) === 1), 'Template parameters, runtime locals and local constants preserve distinct roles');
Check::check(count(array_filter($choose->name_bindings, static fn($b) => $b->kind === reference_kind::local_constant)) === 1,
    'Compile-time condition names bind the nearest constant without evaluating it');
Check::check(str_contains($names->to_json(), 'template_parameter') && (serialize($first) === $before), 'Exports preserve shared ASTs and provider inputs');

// Independent selected work and acceptance use the same path as serial execution.
$tasks = Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, new \resolve_symbols\Resolution_Set(), true, $catalog);
$results = array_map(static fn($task) => (new \resolve_symbols\Resolution_Worker($symbols, $task, $catalog))->run(), array_reverse($tasks));
$joined = (new \resolve_symbols\Resolution_Join(new \resolve_symbols\Resolution_Set(), $symbols, $tasks, $catalog))->join($results);
Check::check(($joined->to_json() === $names->to_json()) && (serialize($first) === $before), 'Reversed workers produce deterministic accepted bindings without shared writes');
Check::check(Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $names, false, $catalog) === [], 'Warm binding inputs select no work');
foreach ([[], [...$results, $results[0]]] as $bad) {
    Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, $tasks, $catalog))->join($bad), '');
}
foreach ([Template_Binding_Test::replacement($sample_names, names: []),
    Template_Binding_Test::replacement($sample_names, parameters: []),
    Template_Binding_Test::replacement($sample_names, applications: [])] as $bad) {
    $error = Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, [$sample], $catalog))->join([$bad]), 'stale');
    Check::check($error instanceof LogicException, 'Incomplete declaration binding is a join protocol error');
}
$without_constants = Template_Binding_Test::replacement($choose, constants: []);
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols,
    [$symbols->symbol_by_id($choose_id)], $catalog))->join([$without_constants]), 'stale');
Check::check(serialize($first) === $before, 'Rejected batches preserve retained snapshots');

// One template-body edit replaces its file's bindings while retaining identities and other files.
Check::edit($root . '/definitions.phs', str_replace('N + 1', 'N + 2', $definitions));
$next = Template_Binding_Test::bind($root, $first);
$main_id = $symbols->entry_symbol_id($first->inputs->sources->entry_file()->id);
Check::check(($next->symbols->find_symbol('choose', '', symbol_kind::template_function) === $choose_id)
    && ($next->names->for_symbol($choose_id) !== $choose)
    && ($next->names->for_symbol($main_id) === $names->for_symbol($main_id))
    && (serialize($first) === $before), 'One replacement preserves definition identity and unselected bindings, and never edits the original AST');
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $next->symbols,
    [$next->symbols->symbol_by_id($choose_id)], $catalog))->join([$choose]), 'stale');

// Formal roles are a dependency even when the using file and its bound target ID are unchanged.
$dependency_root = getcwd() . '/template-dependency';
Template_Binding_Test::project($dependency_root,
    'template<typename T> struct use_family { public family<T> $value; } return 0;');
file_put_contents($dependency_root . '/family.phs', 'template<typename T> struct family { public T $value; }');
$dependency = Template_Binding_Test::bind($dependency_root);
$using_id = $dependency->symbols->find_symbol('use_family', '', symbol_kind::template_struct);
$dependency_before = serialize($dependency);
Check::edit($dependency_root . '/family.phs', 'template<int N> struct family { public int32 $value; }');
[$changed_input, $changed_symbols, $changed_catalog] = Template_Binding_Test::prepare($dependency_root, $dependency);
$selected = Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $changed_symbols, $dependency->names, false, $changed_catalog);
Check::check(in_array($using_id, array_map(static fn($task) => $task->symbol_id, $selected), true)
    && ($changed_symbols->symbol_by_id($using_id) === $dependency->symbols->symbol_by_id($using_id)),
    'A changed formal parameter kind selects an unchanged template use');
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($dependency->names, $changed_symbols,
    [$changed_symbols->symbol_by_id($using_id)], $changed_catalog))->join([$dependency->names->for_symbol($using_id)]), 'stale');
Check::rejects(static fn() => \compile\Phases::run_symbols($changed_symbols, $dependency->names,
    $changed_input->context, $changed_catalog), 'wrong name role');
Check::check(serialize($dependency) === $dependency_before, 'A failed dependent replacement preserves the retained definition and use');

// Equal spellings in another provider snapshot cannot authorize stale bound definition objects.
$other_catalog = \load_runtime\Catalog_Syntax::parse(file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json'));
Check::check(Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $names, false, $other_catalog) !== [],
    'Provider declaration identity participates in binding selection');
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, [$sample], $other_catalog))->join([$sample_names]), 'stale');

// Binding follows nested applications iteratively, and constants retain declaration identities without execution.
$deep_root = getcwd() . '/deep-template-bindings';
$depth = 2000;
Template_Binding_Test::project($deep_root, 'template<typename T> struct item { public T $value; } '
    . 'template<typename T> struct nested { public ' . str_repeat('item<', $depth) . 'T'
    . str_repeat('>', $depth) . ' $value; } const FIRST = SECOND + 1; const SECOND = 2; return 0;');
$deep = Template_Binding_Test::bind($deep_root);
$nested = $deep->names->for_symbol($deep->symbols->find_symbol('nested', '', symbol_kind::template_struct));
Check::check(count($nested->applications) === $depth, 'Deep family uses bind without recursive traversal or concrete instances');
$constant = $deep->names->for_symbol($deep->symbols->find_symbol('FIRST', '', symbol_kind::constant_symbol));
Check::check(($constant->name_bindings[0]->kind === reference_kind::project_constant)
    && ($constant->name_bindings[0]->target === $deep->symbols->find_symbol('SECOND', '', symbol_kind::constant_symbol)),
    'Forward project constants bind by declaration identity without evaluation');

// Binding above preserves unsupported syntax. The public pipeline must now reject it even when unused.
Check::rejects(static fn() => (new \compile\Compiler_Session())->compile($root . '/project.json'), 'Unsupported statement');
$native_definitions = <<<'PHS'
template<typename T, int N> struct sample { public T $value; }
template<typename T, int N> function choose($value T): T { return $value; }
struct plain { public int32 $value; }
function identity($value int): int { return $value; }
PHS;
Check::edit($root . '/definitions.phs', $native_definitions);

// Valid unused definitions receive permission checks without concrete layouts or executable signatures.
$session = new \compile\Compiler_Session();
$output = getcwd() . '/template-program';
$native = $session->compile($root . '/project.json', $output);
exec(escapeshellarg($output), $ignored, $status);
Check::check(($status === 7) && ($native->types->types->find_type('sample') === 0)
    && ($native->types->for_symbol($native->symbols->current->find_symbol('choose', '', symbol_kind::template_function)) === null),
    'Ordinary values execute through shared annotation bindings; templates remain definitions');
$native_before = serialize($native);
Check::edit($root . '/main.phs', '$item plain = new plain(); return identity($item->value) + 9;');
$increment = $session->compile($root . '/project.json', $output);
exec(escapeshellarg($output), $ignored, $status);
Check::check(($status === 9) && (!$increment->inputs->context->full_rebuild) && (serialize($native) === $native_before),
    'One ordinary body increment remains incremental with unchanged template definitions present');

// Definition binding is not instantiation/evaluation; demanded unsupported work must stop explicitly.
$invalid = [
    ['template<typename T, typename T> struct box { public T $value; }', 'Duplicate or conflicting template parameter'],
    ['template<typename box> struct box { public int $value; }', 'conflicting template parameter'],
    ['template<T N, typename T> struct box { public int $value; }', 'Unknown or unsupported template parameter type'],
    ['template<int N> struct box { public N $value; }', 'wrong name role'],
    ['template<typename T> function f(): int { return T; }', 'wrong name role'],
    ['template<typename T> struct box { public Missing $value; }', 'Unknown or unsupported field type'],
    ['template<typename T> function f(): int { return missing(); }', 'Unknown function'],
    ['template<typename T> function f(): int { if constexpr (false) { return missing(); } return 0; }', 'Unknown function'],
    ['template<typename T> function f(): int { const N = N; return 0; }', 'cannot read itself'],
    ['template<typename T> function f(): int { const N = 1; return N(); }', 'Calling a constant'],
    ['template<typename T> function f(): int { { const N = 1; } return N; }', 'Unknown constant'],
    ['template<typename T> function f($x T): T { const T = 1; return $x; }', 'conflicting constant'],
    ['template<typename T> struct a { public T $x; public T $x; }', 'Duplicate field'],
    ['template<typename T> struct a { public T $x; } struct a { public int $x; }', 'Duplicate'],
    ['template<typename T> struct int { public T $x; }', 'Duplicate source/provider type'],
    ['template<typename T> struct a { public T $x; } template<typename T> struct b { public a<T, T> $x; }', 'argument count'],
    ['template<int N> struct a { public int $x; } template<typename T> struct b { public a<T> $x; }', 'wrong name role'],
    ['template<typename T> struct a { public T $x; } template<typename T> struct b { public a<1> $x; }', 'Expected type argument'],
];
foreach ($invalid as $index => [$source, $reason]) {
    $path = getcwd() . '/invalid-template-' . $index;
    Template_Binding_Test::project($path, $source . ' return 0;');
    $error = Check::rejects(static fn() => Template_Binding_Test::bind($path), $reason);
    Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $path . '/main.phs'), 'Known-invalid names have source diagnostics before instantiation');
}
foreach (['const N = 1 + 1; return 0;'] as $index => $source) {
    $path = getcwd() . '/unsupported-meta-' . $index;
    Template_Binding_Test::project($path, $source);
    $session = new \compile\Compiler_Session();
    $error = Check::rejects(static fn() => $session->compile($path . '/project.json'), 'not implemented');
    Check::check(($error instanceof \diagnostics\Source_Error) && ($session->published === null), 'Unimplemented expression evaluation cannot publish fake success');
}
echo "template bindings ok: shared declaration lookup, parameter scopes, source/provider references, formal dependencies, private workers/joins, native coexistence and one-increment proofs\n";
