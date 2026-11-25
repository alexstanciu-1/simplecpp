<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/bootstrap.php';

// All join concepts participate, including array-returning discovery/type/native
// joins. Existing stage fixtures cover nonempty, reversed and rejected batches.
class Join_Contract_Test
{
    private static array $covered = [];

    /** Host-only inventory helper: inspect the marker and invoke the concrete operation, checking the concrete owner's instance and result contract. */
    public static function accept(\compile\Join $join, array $results): mixed
    {
        self::$covered[get_class($join)] = true;
        $method = new ReflectionMethod($join, 'join');
        Step_Test::check(!$method->isStatic(), 'Acceptance belongs to the captured join context');
        Step_Test::check((string)$method->getReturnType() !== 'mixed', 'Concrete join retains a concrete result contract');
        return $method->invoke($join, $results);
    }

    /** Require an acceptance proof for every loaded join owner. */
    public static function inventory(): void
    {
        $owners = array_filter(get_declared_classes(), static fn($class) => is_subclass_of($class, \compile\Join::class));
        Step_Test::check(count($owners) === count(self::$covered), 'Every join owner has an interface acceptance proof');
        foreach ($owners as $owner) {
            Step_Test::check(isset(self::$covered[$owner]), 'Missing join proof: ' . $owner);
        }
    }
}

$session = new \compile\Compiler_Session();
$baseline = $session->compile('../fixtures/three_files/project.json', getcwd() . '/program');
$before = $baseline->to_json();
$sources = $baseline->inputs->sources;
$symbols = $baseline->symbols->current;
$types = $baseline->types;
$entry = $types->entry;
$catalog = $types->catalog;
$names = $baseline->resolutions;
$backend = $baseline->backend;
$lowered = $baseline->lowered;
$previous = $baseline->llvm;

// Runtime composition requires explicit membership; its successful nonempty batches
// are proved with prepared native packages in integration/runtime_inputs.php.
$empty_runtime = new \load_runtime\Input_Join($catalog, null, []);
$rejected = false;
try {
    Join_Contract_Test::accept($empty_runtime, []);
}
catch (LogicException $error) {
    $rejected = str_contains($error->getMessage(), 'requires selected packages');
}
Step_Test::check($rejected, 'Runtime input join rejects empty selection at acceptance, not construction');

Step_Test::check(Join_Contract_Test::accept(new \load_runtime\Family_Preparation_Join([], $catalog), []) === [],
    'Family type join accepts an empty fixed native batch');

// Empty batches still accept complete valid retained membership, with no fake tasks.
$discovery_candidate = clone $sources;
Step_Test::check(Join_Contract_Test::accept(new \read_sources\Source_Scan_Join($discovery_candidate, $sources, []), []) === [],
    'Directory join returns typed next-work rows, with no child work for an empty batch');
$read = Join_Contract_Test::accept(new \read_sources\Snapshot_Join($sources, []), []);
Step_Test::check($read->to_json() === $sources->to_json(), 'Snapshot join preserves retained bytes');
$tokens = Join_Contract_Test::accept(new \tokenize\Token_Join($sources, $baseline->inputs->tokens, []), []);
$frontends = Join_Contract_Test::accept(new \parse\Frontend_Join($baseline->inputs->frontends, $sources, $tokens, []), []);
Step_Test::check($frontends->to_json() === $baseline->inputs->frontends->to_json(), 'Batch parser join preserves retained syntax');
$collected = Join_Contract_Test::accept(new \collect_symbols\Declaration_Join($symbols, $sources, $frontends, []), []);
$compared = Join_Contract_Test::accept(new \collect_symbols\Comparison_Join($collected, []), []);
Step_Test::check($compared->current->to_json() === $symbols->to_json(), 'Declaration/comparison joins preserve symbol output');
$resolved = Join_Contract_Test::accept(new \resolve_symbols\Resolution_Join($names, $symbols, [], $catalog), []);
Step_Test::check($resolved->to_json() === $names->to_json(), 'Name join preserves resolved bindings');
$templates = Join_Contract_Test::accept(new \check_templates\Template_Join($symbols, $names, $catalog,
    $types->instances->template_checks(), []), []);
Step_Test::check($templates->definitions === [], 'Definition permission join accepts empty current membership');

// Only private candidates may be materialized; signature output feeds local joins.
$cache = \resolve_types\Type_Cache::prepare($types->types, $types->types->context, false);
Step_Test::check(Join_Contract_Test::accept(new \resolve_types\Record_Join($cache, [], $symbols), []) === $cache,
    'Record join accepts an empty selected batch without changing canonical types');
Step_Test::check(Join_Contract_Test::accept(new \instantiate\Constant_Join([], $catalog), []) === [],
    'Literal join accepts empty selected work without inventing constants');
$instances = Join_Contract_Test::accept(new \instantiate\Instance_Join(new \instantiate\Instance_Store($types->instances), [], $cache, $names,
    new \resolve_types\Definition_View($catalog, $cache), $catalog), []);
Step_Test::check($instances->snapshot()->to_array() === $types->instances->to_array(), 'Instance join preserves retained demand membership');
$members = Join_Contract_Test::accept(new \instantiate\Member_Join(new \instantiate\Instance_Store($types->instances), [], $cache, $names,
    new \resolve_types\Definition_View($catalog, $cache), $symbols, $types->instances), []);
Step_Test::check($members->snapshot()->to_array() === $types->instances->to_array(), 'Member join preserves retained demand membership');
$signatures = Join_Contract_Test::accept(new \resolve_types\Signature_Join($symbols, $catalog, $cache, $types, [], $entry, $names), []);
$locals = Join_Contract_Test::accept(new \resolve_types\Local_Type_Join($symbols, $names, $catalog, $cache, $types, [], $entry, $signatures), []);
Step_Test::check($signatures === array_values($types->signatures()), 'Signature join returns retained typed associations');
foreach ($locals as $local) {
    Step_Test::check($local instanceof \resolve_types\Local_Types, 'Local join returns Local_Types rows');
}
$bodies = Join_Contract_Test::accept(new \check_bodies\Body_Join($symbols, $names, $types, $baseline->bodies, []), []);
Step_Test::check(Join_Contract_Test::accept(new \analyze_lifetimes\Ownership_Join([]), []) === [],
    'Ownership join accepts an empty fixed batch without inventing parameter contracts');
Step_Test::check(Join_Contract_Test::accept(new \analyze_lifetimes\Export_Join([], [], []), []) === [],
    'Export verification join accepts empty current membership without inventing evidence');
$lifetimes = Join_Contract_Test::accept(new \analyze_lifetimes\Lifetime_Join($bodies, $baseline->lifetimes, []), []);
Step_Test::check(Join_Contract_Test::accept(new \prepare_backend\Lifecycle_Join(null, $backend->configuration, $backend, []), []) === [],
    'Cleanup join accepts an empty current package without inventing implicit targets');
Step_Test::check(Join_Contract_Test::accept(new \prepare_backend\Layout_Join(\prepare_backend\Layout_Preparation::capture($types->types, []), $backend->configuration, $backend->layouts, []), []) === [],
    'Layout join accepts a scalar-only compilation without inventing records');
Step_Test::check(Join_Contract_Test::accept(new \prepare_backend\Storage_Join(null, $backend->configuration, $backend, []), []) === [],
    'Storage join accepts empty current membership');
Step_Test::check(Join_Contract_Test::accept(new \prepare_backend\Source_Export_Join([], [], []), []) === [],
    'Source export join accepts empty explicit membership without inventing exports');
$bindings = Join_Contract_Test::accept(new \prepare_backend\Backend_Join($types, $backend->configuration, $backend, []), []);
$plans = Join_Contract_Test::accept(new \lower\Lowering_Join($lifetimes, $bindings, $lowered, []), []);
Step_Test::check(Join_Contract_Test::accept(new \emit_llvm\Lifecycle_Emission_Join($backend, [], []), []) === [],
    'Generated lifecycle join accepts empty selected membership');
$functions = Join_Contract_Test::accept(new \emit_llvm\Emission_Join($plans, $bindings, $previous->entry, $previous, []), []);
$program = Join_Contract_Test::accept(new \emit_llvm\Module_Join($functions, $previous, []), []);
$objects = Join_Contract_Test::accept(new \build_native\Native_Join($program, $baseline->native, []), []);
Step_Test::check(($program === $previous) && ($objects === $baseline->native->objects), 'Module/native joins retain exact accepted outputs');
Step_Test::check($baseline->to_json() === $before, 'Join construction and acceptance preserve retained input exports');
Join_Contract_Test::inventory();

// Parser construction must not validate. Both the common batch operation and
// segmented operations enforce the captured selection when first used.
$file = $sources->entry_file()->id;
$task = $tokens->for_file($file);
$invalid = new \parse\Frontend_Join($frontends, $sources, $tokens, [$task, $task]);
$rejected = false;
try {
    $invalid->join([]);
}
catch (Exception $error) {
    $rejected = str_contains($error->getMessage(), 'Duplicate');
}
Step_Test::check($rejected, 'Duplicate tasks fail acceptance, not construction');
$segmented = new \parse\Frontend_Join($frontends, $sources, $tokens, [$task]);
$segmented->merge([$frontends->for_file($file)], 0, 1);
Step_Test::check($segmented->join([])->to_json() === $frontends->to_json(), 'Common entry completes earlier accepted segments');
echo "join contracts ok: all owners, empty batches, concrete outputs, retained-input purity, native reuse and deferred parser validation\n";
