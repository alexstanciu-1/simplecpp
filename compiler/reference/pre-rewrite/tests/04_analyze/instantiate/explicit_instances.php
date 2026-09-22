<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use instantiate\Application_Worker;
use instantiate\Instance_Join;
use instantiate\Instance_Set;
use instantiate\application_task;
use instantiate\application_result;
use instantiate\instance_context;
use instantiate\template_argument;

final class Instances_Test
{
    /** Execute the produced program and retain its observable exit status. */
    public static function run(string $output): int
    {
        exec(escapeshellarg($output), $lines, $status);
        return $status;
    }

    /** Create a real isolated project, keeping stable definitions separate from body edits. */
    public static function project(string $root, string $main, string $definitions = ''): void
    {
        mkdir($root);
        file_put_contents($root . '/project.json', json_encode(['source_folders' => ['.'], 'entry' => 'main.phs'], JSON_THROW_ON_ERROR));
        file_put_contents($root . '/main.phs', $main);
        file_put_contents($root . '/definitions.phs', $definitions);
    }
}

$definitions = <<<'PHS'
const COUNT = 3;
const ALSO = 4;
template<typename A, typename B, int N>
struct sample { public A $left; public B $right; }
template<typename T>
struct marker { public int32 $value; }
template<typename T>
function identity($value T): T { return $value; }
template<typename T>
function forward($value T): T { return identity<T>($value); }
template<int N>
function number(): int { return N; }
template<typename T>
constexpr function unused($value T): T { return $value; }
PHS;
$main = <<<'PHS'
$p sample<int32, int32, COUNT> = new sample<int32, int32, 3>();
$tag marker<marker<int32>> = new marker<marker<int32>>();
$zero int = forward<int32>($p->left);
identity<int32>($p->right);
return identity<int>(COUNT) + number<4>() + $zero;
PHS;
$root = getcwd() . '/instances';
Instances_Test::project($root, $main, $definitions);
$session = new \compile\Compiler_Session();
$output = getcwd() . '/instances-program';
$first = $session->compile($root . '/project.json', $output);
Check::check(Instances_Test::run($output) === 7, 'Explicit type/value specializations execute through ordinary native stages');
$instances = $first->types->instances;
$by_name = [];
foreach ($instances->contexts as $context) {
    $by_name[$context->definition->name][] = $context;
    Check::check($context->definition->frontend->syntax === $first->resolutions->for_symbol($context->definition->symbol_id)->syntax,
        'Concrete instances retain the original AST and definition bindings');
}
Check::check((count($by_name['sample']) === 1) && (count($by_name['marker']) === 2)
    && (count($by_name['identity']) === 2) && !isset($by_name['unused']),
    'Constant/literal requests deduplicate, nested arguments materialize and unused functions stay definitions');
$identities = $by_name['identity'];
$left = $first->types->for_callable($identities[0]->context_id);
$right = $first->types->for_callable($identities[1]->context_id);
Check::check(($left->symbol_id === $right->symbol_id) && ($left->callable_id !== $right->callable_id)
    && ($left->representation_id !== $right->representation_id)
    && ($first->backend->binding_for($left->callable_id)->link_name !== $first->backend->binding_for($right->callable_id)->link_name),
    'One definition owns distinct concrete signatures, bodies and native linkage');
Check::check($first->types->for_symbol($left->symbol_id) === null, 'A template definition is never a concrete callable');
$before = serialize($first);
Check::check(str_contains($first->types->to_json(), '"instances"') && (serialize($first) === $before),
    'Exports include instance arguments without changing retained inputs');

// Literal workers are selected independently; keyed joins accept arbitrary arrival order.
$constant_tasks = $instances->constant_owners;
$constant_results = [];
foreach (array_reverse($constant_tasks, true) as $id => $owner) {
    $constant_results[$id] = \instantiate\Constant_Worker::resolve($owner, $first->resolutions, $first->types->catalog);
}
$constant_join = new \instantiate\Constant_Join($constant_tasks, $first->types->catalog);
$constants = $constant_join->join($constant_results);
Check::check(array_keys($constants) === array_keys($constant_tasks), 'Literal join restores selected order');
foreach ($constants as $id => $constant) {
    Check::check(($constant->type === $instances->constants[$id]->type) && ($constant->value === $instances->constants[$id]->value),
        'Private literal results preserve the typed constant contract');
}
Check::rejects(static fn() => $constant_join->join([]), 'Incomplete');

// Fixed application workers and joins use the same contracts as the preparation stage.
$entry = $first->types->entry->symbol;
$context = new instance_context($entry);
$tasks = [];
foreach ($first->resolutions->for_symbol($entry->symbol_id)->applications as $application) {
    $tasks[] = new application_task($context, $application);
}
$view = new \resolve_types\Definition_View($first->types->catalog, $first->types->types);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = Application_Worker::run($task, $first->resolutions, $view, $first->types->catalog, $instances);
}
Check::check(serialize($first) === $before, 'Application workers have no shared writes');
$candidate = clone $first->types->types;
$joined = (new Instance_Join(new \instantiate\Instance_Store($instances), $tasks, $candidate, $first->resolutions, $view,
    $first->types->catalog, $instances))->join($results)->snapshot();
Check::check(($joined->uses === $instances->uses) && ($joined->next_id === $instances->next_id)
    && (serialize($first) === $before), 'Reversed results retain exact instance identities and pure prior snapshots');
foreach ([[], [...$results, $results[0]]] as $bad) {
    Check::rejects(static fn() => (new Instance_Join(new \instantiate\Instance_Store($instances), $tasks, clone $candidate, $first->resolutions,
        $view, $first->types->catalog))->join($bad), '');
}
$forged = $results;
$last = $forged[0];
$forged[0] = new application_result($last->task, []);
Check::rejects(static fn() => (new Instance_Join(new \instantiate\Instance_Store($instances), $tasks, clone $candidate, $first->resolutions,
    $view, $first->types->catalog))->join($forged), 'Stale application');
$forged[0] = new application_result($last->task, null, [$last->task->application->use_node_id]);
Check::rejects(static fn() => (new Instance_Join(new \instantiate\Instance_Store($instances), $tasks, clone $candidate, $first->resolutions,
    $view, $first->types->catalog))->join($forged), 'Stale application');
$unrelated = new \type_model\Type_Store($candidate->context);
Check::rejects(static fn() => (new Instance_Join(new \instantiate\Instance_Store($instances), $tasks, $unrelated, $first->resolutions,
    $view, $first->types->catalog))->join($results), 'lineage');
Check::check(serialize($first) === $before, 'Rejected worker output leaves the retained program untouched');

// One body increment demands a new integer specialization and removes the obsolete callable.
Check::edit($root . '/main.phs', str_replace('number<4>()', 'number<6>()', $main));
$next = $session->compile($root . '/project.json', $output);
Check::check((Instances_Test::run($output) === 9) && (!$next->inputs->context->full_rebuild),
    'An ordinary body edit can replace its demanded specialization through the incremental stages');
foreach ($identities as $identity) {
    Check::check(($next->types->instances->contexts[$identity->context_id] === $identity)
        && ($next->bodies->for_callable($identity->context_id) === $first->bodies->for_callable($identity->context_id)),
        'Unchanged concrete contexts and bodies remain reusable');
}
$old_number = $by_name['number'][0]->context_id;
Check::check(($next->types->for_callable($old_number) === null) && ($next->bodies->for_callable($old_number) === null)
    && ($next->backend->binding_for($old_number) === null) && (serialize($first) === $before),
    'Removed demands lose current executable contributions without recycling IDs or mutating prior snapshots');

// Existing recursion is a signature/body dependency, not a cycle requiring constant execution.
$recursive = getcwd() . '/recursive-instance';
Instances_Test::project($recursive, 'return recur<int>(0);',
    'template<typename T> function recur($x T): T { if (false) { return recur<T>($x); } return $x; }');
$recursive_session = new \compile\Compiler_Session();
$recursive_result = $recursive_session->compile($recursive . '/project.json', getcwd() . '/recursive-program');
Check::check((Instances_Test::run(getcwd() . '/recursive-program') === 0) && (count($recursive_result->types->instances->contexts) === 1),
    'Recursive calls reuse their reserved concrete identity');

// A changed preparation budget invalidates even a published generation with unchanged source.
$limited_root = getcwd() . '/limited-instances';
Instances_Test::project($limited_root, 'return number<1>() + number<2>();',
    'template<int N> function number(): int { return N; }');
$policy_path = getcwd() . '/instance-limits.json';
file_put_contents($policy_path, '{"max_instances": 2}');
$limited = new \compile\Compiler_Session(instantiation_policy_path: $policy_path);
$limited_output = getcwd() . '/limited-program';
$accepted = $limited->compile($limited_root . '/project.json', $limited_output);
Check::check(Instances_Test::run($limited_output) === 3, 'The configured budget accepts its exact bound');
file_put_contents($policy_path, '{"max_instances": 1}');
$error = Check::rejects(static fn() => $limited->compile($limited_root . '/project.json', $limited_output), 'instance limit');
Check::check(($error instanceof \diagnostics\Source_Error) && ($limited->published->types === $accepted->types)
    && (Instances_Test::run($limited_output) === 3), 'Policy changes force selection and cannot publish excess instances');
file_put_contents($policy_path, '{"max_instances": "2"}');
Check::rejects(static fn() => \instantiate\Instantiation_Policy::load($policy_path), 'Invalid instantiation limit');

// Unsupported semantics remain source diagnostics, never silent runtime fallback.
$invalid = [
    ['const N = 1 + 2; return 0;', '', 'integer literal'],
    ['const N = 999999999999999999999999999999999999; return 0;', '', 'outside the range'],
    ['return f<1 + 2>();', 'template<int N> function f(): int { return N; }', 'constant evaluation is not implemented'],
    ['return f<3>();', 'template<int32 N> function f(): int { return N; }', 'integer contract'],
    ['return f<int>(1);', 'template<typename T> constexpr function f($v T): T { return $v; }', 'not implemented'],
    ['return f<int>(1);', 'template<typename T> consteval function f($v T): T { return $v; }', 'not implemented'],
    ['return f<int>(1);', 'template<typename T> function f($v T): T { if consteval { return $v; } else { return $v; } }', 'Unsupported'],
    ['$x box<int>; return 0;', 'template<typename T> struct box { public T $value; }', 'unsupported struct field'],
    ['$x box<int32>; return 0;', 'template<typename T> struct box { public box<T> $value; }', 'prerequisite'],
    ['return f<int>();', 'template<typename T> function f(): int { const N = 1; return N; }', 'Unsupported'],
];
foreach ($invalid as $index => [$source, $definitions, $reason]) {
    $path = getcwd() . '/invalid-instance-' . $index;
    Instances_Test::project($path, $source, $definitions);
    $failed = new \compile\Compiler_Session();
    $error = Check::rejects(static fn() => $failed->compile($path . '/project.json'), $reason);
    Check::check(($error instanceof \diagnostics\Source_Error) && ($failed->published === null), 'Invalid or unsupported instantiation cannot publish');
}
echo "explicit instances ok: literal constants, distinct callable identities, shared structural preparation, nested/repeated demands, native execution, recursion, private workers/joins and one incremental demand replacement\n";
