<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Growing_List_Test
{
    /** Render two concrete fixture records; this is test data generation, not compiler specialization. */
    private static function source(string $source): string
    {
        $start = strpos($source, 'struct ELEMENT_LIST {');
        if ($start === false) {
            return $source;
        }
        $opening = strpos($source, '{', $start);
        $depth = 1;
        $end = $opening + 1;
        while ($depth > 0) {
            $depth += ($source[$end] === '{') ? 1 : (($source[$end] === '}') ? -1 : 0);
            ++$end;
        }
        $record = substr($source, $start, $end - $start);
        $records = [];
        foreach (['list_i32' => 'int32', 'list_byte' => 'uint8'] as $name => $element) {
            $records[] = str_replace(['ELEMENT_LIST', 'ELEMENT_TYPE'], [$name, $element], $record);
        }
        return substr($source, 0, $start) . implode("\n", $records) . substr($source, $end);
    }

    public static function write(string $path, string $source): void
    {
        Files::write($path, self::source($source));
    }

    public static function edit(string $path, string $source): void
    {
        Check::edit($path, self::source($source));
    }

    /** Run native proofs without exposing compiler-private descriptors to source code. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start storage proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

    /** Parameter/path maps describe simultaneous call contracts, never execution order inside the callee. */
    public static function reordered_dependencies(array $dependencies): array
    {
        $reordered = [];
        foreach ($dependencies as $key => $summary) {
            $parameters = [];
            foreach (array_reverse($summary->parameters, true) as $position => $fields) {
                $parameters[$position] = array_reverse($fields, true);
            }
            $reordered[$key] = new \analyze_lifetimes\ownership_summary($parameters, array_reverse($summary->distinct, true));
        }
        return $reordered;
    }

}

$root = getcwd() . '/growing-list-proof';
Files::directory($root . '/src');
Files::directory($root . '/definitions');
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
$definitions = Files::json($preparation . '/definitions/element_storage.json');
$scalars = Files::json($preparation . '/definitions/scalars.json');
$definitions['types'][] = $scalars['types'][0];
$definitions['types'][] = ['id' => 'void', 'cpp_name' => 'void', 'header' => 'cstddef', 'kind' => 'void',
    'language_type' => ['name' => 'void', 'namespace' => '']];
// A separate hidden native void alias must not replace the exposed semantic void.
$definitions['types'][] = ['id' => 'zz_hidden_void', 'cpp_name' => 'void', 'header' => 'cstddef', 'kind' => 'void'];
// Rename the whole source surface; compiler behavior must follow family metadata.
$definitions['types'][0]['language_type']['name'] = 'slots';
foreach ($definitions['types'][0]['storage_family']['operations'] as $role => $_) {
    $definitions['types'][0]['storage_family']['operations'][$role] = 'slots_' . $role;
}
Growing_List_Test::write($root . '/observer.hpp', <<<'CPP'
#pragma once
#include <scpp_provider/element_storage.hpp>
#include <unordered_set>
#include <cstdlib>
namespace proof {
inline std::unordered_set<void*> live;
inline std::int64_t acquisitions = 0, releases = 0;
inline void allocate(scpp_provider::element_storage& owner, std::int64_t n, std::int64_t size, std::int64_t alignment) {
    scpp_provider::storage_allocate(owner, n, size, alignment);
    if (!live.insert(owner.memory.address).second) std::abort();
    ++acquisitions;
}
inline void release(scpp_provider::element_storage& owner) {
    if (!owner.memory.address || live.erase(owner.memory.address) != 1) std::abort();
    scpp_provider::storage_release(owner);
    ++releases;
}
inline std::int64_t finished() {
    if (!live.empty() || acquisitions != releases) std::abort();
    return acquisitions * 10 + releases;
}
}
CPP);
foreach ($definitions['operations'] as &$operation) {
    if (in_array($operation['id'], ['element_storage.allocate', 'element_storage.release'], true)) {
        $operation['cpp_name'] = 'proof::' . substr($operation['id'], strlen('element_storage.'));
        $operation['header'] = 'observer.hpp';
    }
}
unset($operation);
$definitions['operations'][] = ['id' => 'finished', 'kind' => 'free_function', 'cpp_name' => 'proof::finished',
    'header' => 'observer.hpp', 'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'finished', 'namespace' => '']];
Files::write_json($root . '/definitions/storage.json', $definitions);
$config = Files::json($preparation . '/config.json');
$config['include_directories'] = [$preparation . '/include', $root];
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$runtime = $root . '/runtime';
$manifest = $root . '/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
$list = <<<'PHS'
// Concrete source fixtures: policy belongs here; configured slots supply storage mechanics.
struct ELEMENT_LIST {
    public slots<ELEMENT_TYPE> $data;

    public function __construct(): void
    {
        slots_allocate<ELEMENT_TYPE>($this->data, 1);
    }

    /** Copy the live prefix in order, append, then replace the old allocation. */
    public function append($value ELEMENT_TYPE): void
    {
        $length int = slots_count<ELEMENT_TYPE>($this->data);
        $replacement slots<ELEMENT_TYPE>;
        slots_allocate<ELEMENT_TYPE>($replacement, $length + 1);
        $index int = 0;
        while ($index < $length) {
            slots_push<ELEMENT_TYPE>($replacement, $this->data[$index]);
            $index = $index + 1;
        }
        slots_push<ELEMENT_TYPE>($replacement, $value);

        $this->clear();
        slots_release<ELEMENT_TYPE>($this->data);
        slots_transfer<ELEMENT_TYPE>($replacement, $this->data);
    }

    public const function count(): int
    {
        return slots_count<ELEMENT_TYPE>($this->data);
    }

    public const function get($index int): ELEMENT_TYPE
    {
        return $this->data[$index];
    }

    public function set($index int, $value ELEMENT_TYPE): void
    {
        $this->data[$index] = $value;
    }

    public function clear(): void
    {
        while (slots_count<ELEMENT_TYPE>($this->data)) {
            slots_pop<ELEMENT_TYPE>($this->data);
        }
    }

    public function __destruct(): void
    {
        $this->clear();
        slots_release<ELEMENT_TYPE>($this->data);
    }
}
PHS;
$workload = <<<'PHS'
const FIRST: int32 = 11;
const SECOND: int32 = 22;
const THIRD: int32 = 33;
const REPLACEMENT: int32 = 44;
const SMALL: uint8 = 7;
const LARGE: uint8 = 9;

function early(): int
{
    $values list_i32;
    $values->append(FIRST);
    $values->append(SECOND);
    if ($values->count()) {
        return $values->get(1);
    }
    return 0;
}

/** Exercise empty state, growth, order, scalar self-append, writes and normal/early cleanup. */
function exercise(): int
{
    $empty list_i32;
    if ($empty->count()) {
        return 100;
    }
    $values list_i32;
    $values->append(FIRST);
    $values->append(SECOND);
    $values->append(THIRD);
    $values->append($values->get(0));
    $values->set(1, REPLACEMENT);
    if ($values->count() < 4) {
        return 101;
    }
    if (4 < $values->count()) {
        return 102;
    }

    $bytes list_byte;
    $bytes->append(SMALL);
    $bytes->append(LARGE);
    if ($bytes->get(0) < LARGE) { } else { return 103; }
    if ($bytes->get(1) < LARGE) { return 104; }
    if (LARGE < $bytes->get(1)) { return 105; }
    if ($bytes->count() < 2) { return 106; }
    if (2 < $bytes->count()) { return 107; }

    $early int = early();
    if ($early < 22) { return 108; }
    if (22 < $early) { return 109; }
    new list_byte();
    return $values->get(0) + $values->get(1) + $values->get(2) + $values->get(3);
}
PHS;
$list_path = $root . '/src/list.phs';
Growing_List_Test::write($list_path, $list);
Growing_List_Test::write($root . '/src/workload.phs', $workload);
Growing_List_Test::write($root . '/src/main.phs', 'return exercise() + finished();');
$session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$first = $session->compile($manifest, $root . '/program');
Check::check(Growing_List_Test::run([$root . '/program']) === [242, '', ''], 'Two source-list instances preserve order and release all thirteen allocations exactly once');
$before = serialize($first);
$runtime_before = Files::read($runtime . '/current.json');
$contexts = $first->types->instances->contexts;
$append = [];
foreach ($contexts as $context) {
    if (($context->definition->owner_symbol_id !== 0) && ($context->definition->name === 'append')) {
        $append[] = $context->context_id;
    }
}
Check::check(count($append) === 2, 'Two concrete source records prepare ordinary append implementations');
foreach ($append as $id) {
    $body = $first->bodies->for_callable($id);
    Check::check((count($body->blocks) > 1) && isset($first->lifetimes->ownership_results['body:' . $id]),
        'Source append owns its checked loop and accepted ownership contract');
}

// Every list body runs through the same selected ownership worker and accepting join.
$ownership = $first->lifetimes->ownership_results;
$tasks = array_map(static fn($result) => $result->task, array_values($ownership));
$results = array_map(\analyze_lifetimes\Ownership_Worker::prepare(...), $tasks);
$accepted = (new \analyze_lifetimes\Ownership_Join($tasks))->join(array_reverse($results));
foreach ($ownership as $key => $result) {
    Check::check($accepted[$key]->summary == $result->summary, 'Reordered source-list ownership results retain meaning');
}

// A demanded method edit reuses source identities, layouts, prepared runtime files and unchanged ownership meaning.
Growing_List_Test::edit($list_path, str_replace('$this->data[$index] = $value;', '$this->data[0] = $value;', $list));
$second = $session->compile($manifest, $root . '/program');
Check::check(Growing_List_Test::run([$root . '/program']) === [253, '', ''], 'One list method-body increment changes native execution');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend->layouts === $first->backend->layouts)
    && ($second->types->instances->keys === $first->types->instances->keys)
    && ($second->backend->storage_targets === $first->backend->storage_targets)
    && (Files::read($runtime . '/current.json') === $runtime_before) && (serialize($first) === $before),
    'List increment reuses stable preparation and preserves its retained snapshot');
$caller = $first->symbols->current->find_symbol('exercise', '', \collect_symbols\symbol_kind::function_symbol);
Check::check($second->lifetimes->ownership_results['body:' . $caller]->summary
    === $first->lifetimes->ownership_results['body:' . $caller]->summary,
    'Equal caller ownership meaning survives refreshed declaration bindings');

$package = Files::json($runtime . '/package/manifest.json');
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Growing_List_Test::write($modules[count($modules) - 1], $ir);
}
[$status, , $error] = Growing_List_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$modules,
    ...$first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
Check::check(($status === 0) && (Growing_List_Test::run([$root . '/optimized']) === [242, '', '']), 'Growing source list O1/ThinLTO: ' . $error);

foreach ([
    ['$a list_i32; $b list_i32 = $a; return 0;', 'copy'],
    ['$a list_i32; $a->append(SMALL); return 0;', 'conversion'],
    ['$a list_i32; $a = $a; return 0;', 'assignment'],
    ['function bad(const list_i32 &$value): void { $value->append(FIRST); } return 0;', 'const'],
] as [$invalid, $message]) {
    Growing_List_Test::write($root . '/negative.phs', $list . $workload . $invalid);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/negative.phs'), $message);
}
foreach (['$a list_i32; return $a->get(0);', '$a list_i32; $a->append(FIRST); return $a->get(1);'] as $invalid) {
    Growing_List_Test::write($root . '/bounds.phs', $list . $workload . $invalid);
    (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/bounds.phs', $root . '/bounds');
    Check::check(Growing_List_Test::run([$root . '/bounds'])[0] !== 0, 'Empty and one-past live-prefix reads stop through the native bound');
}
// Custom copying owns policy in source; the bridge only supplies element storage mechanics.
$copy_body = <<<'PHS'
    /** Default-initialized fields receive a fresh allocation; the source remains borrowed. */
    public function __copy_construct(const ELEMENT_LIST &$source): void
    {
        $length int = $source->count();
        slots_allocate<ELEMENT_TYPE>($this->data, $length + 1);
        $index int = 0;
        while ($index < $length) {
            slots_push<ELEMENT_TYPE>($this->data, $source->get($index));
            $index = $index + 1;
        }
    }

PHS;
$copy_list = str_replace('    public function __construct()', $copy_body . '    public function __construct()', $list);
$copy_workload = <<<'PHS'
const FIRST: int32 = 11;
const SECOND: int32 = 22;
const REPLACEMENT: int32 = 44;
const SMALL: uint8 = 7;
struct nested { public list_i32 $child; }
function inspect(const list_i32 &$value): int { return $value->get(0); }
function inspect_pair(const list_i32 &$left, const list_i32 &$right): int
{
    return $left->get(0) + $right->get(0);
}
function exercise(): int
{
    $a list_i32;
    $a->append(FIRST);
    $a->append(SECOND);
    $b list_i32 = $a;
    $b->set(0, REPLACEMENT);
    $small list_byte;
    $small->append(SMALL);
    $small_copy list_byte = $small;
    if ($small_copy->get(0) < SMALL) { return 101; }
    if (SMALL < $small_copy->get(0)) { return 102; }
    $outer nested;
    slots_push<int32>($outer->child->data, FIRST);
    $outer_copy nested = $outer;
    $outer_copy->child->data[0] = SECOND;
    $second_value int = $b->get(1);
    $nested_sum int = $outer->child->data[0] + $outer_copy->child->data[0];
    if ($a->count()) {
        return inspect($a) + inspect_pair($a, $b) + $second_value + 7 + $nested_sum;
    }
    return 0;
}
PHS;
Growing_List_Test::write($list_path, $copy_list);
Growing_List_Test::write($root . '/src/workload.phs', $copy_workload);
$copy_session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$copy_first = $copy_session->compile($manifest, $root . '/copied');
Check::check(Growing_List_Test::run([$root . '/copied']) === [227, '', ''],
    'Copies preserve order, own independent allocations, borrow const sources and release nine allocations once');
$copy_before = serialize($copy_first);
$copy_results = $copy_first->lifetimes->ownership_results;
$copy_tasks = array_map(static fn($result) => $result->task, array_values($copy_results));
$copy_outputs = array_map(\analyze_lifetimes\Ownership_Worker::prepare(...), $copy_tasks);
$copy_accepted = (new \analyze_lifetimes\Ownership_Join($copy_tasks))->join(array_reverse($copy_outputs));
foreach ($copy_results as $key => $result) {
    Check::check($copy_accepted[$key]->summary == $result->summary, 'Copy ownership joins accept reordered private results');
}
Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join($copy_tasks))->join(array_slice($copy_outputs, 1)), 'Incomplete');
Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join($copy_tasks))->join([...$copy_outputs, $copy_outputs[0]]), 'duplicate');

foreach ($copy_outputs as $output)
{
    if (!str_starts_with($output->task->key, 'copy:')) {
        continue;
    }
    $parameters = $output->summary->parameters;
    unset($parameters[1]);
    $missing_source = new \analyze_lifetimes\ownership_result($output->task, new \analyze_lifetimes\ownership_summary($parameters));
    Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join([$output->task]))->join([$missing_source]), 'Incomplete');
    $parameters = $output->summary->parameters;
    $path = array_key_first($parameters[1]);
    $parameters[1][$path] = new \analyze_lifetimes\resource_transition(3, \analyze_lifetimes\Resource_States::EMPTY_VALUE, true);
    $mutating_source = new \analyze_lifetimes\ownership_result($output->task, new \analyze_lifetimes\ownership_summary($parameters));
    Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join([$output->task]))->join([$mutating_source]), 'Invalid');
    break;
}

// Editing only the copier changes demanded machine code, while retaining the fixed type and runtime inputs.
Growing_List_Test::edit($list_path, str_replace('$source->get($index)', '$source->get(0)', $copy_list));
$copy_second = $copy_session->compile($manifest, $root . '/copied');
Check::check(Growing_List_Test::run([$root . '/copied']) === [216, '', ''], 'One copier body edit changes the copied second element');
Check::check(!$copy_second->inputs->context->full_rebuild, 'Custom copy body edit remains incremental');
Check::check($copy_second->backend->layouts === $copy_first->backend->layouts, 'Custom copy increment retains target layouts');
Check::check($copy_second->types->instances->keys === $copy_first->types->instances->keys, 'Custom copy increment retains instance identities');
Check::check(Files::read($runtime . '/current.json') === $runtime_before, 'Custom copy increment preserves the runtime package');
Check::check(serialize($copy_first) === $copy_before, 'Custom copy workers and increment preserve the old snapshot');

foreach ($copy_results as $key => $result) {
    if (str_starts_with($key, 'copy:')) {
        Check::check($copy_second->lifetimes->ownership_results[$key]->summary === $result->summary,
            'Changed copy bodies retain equal ownership-summary meaning');
    }
}

$copy_modules = [];
foreach ($copy_first->llvm->ir_by_file() as $id => $ir) {
    $copy_modules[] = $root . '/copy-module-' . $id . '.ll';
    Growing_List_Test::write($copy_modules[count($copy_modules) - 1], $ir);
}
[$status, , $error] = Growing_List_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$copy_modules,
    ...$copy_first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$copy_first->backend->runtime->link_arguments, '-o', $root . '/copy-optimized']);
Check::check(($status === 0) && (Growing_List_Test::run([$root . '/copy-optimized']) === [227, '', '']), 'Custom copy O1/ThinLTO: ' . $error);

$alias_method = <<<'PHS'
    public function replace_from(const ELEMENT_LIST &$other): void
    {
        $value ELEMENT_TYPE = $other->get(0);
        $this->append($value);
    }
PHS;
$alias_list = str_replace('    public function __construct()', $alias_method . "\n    public function __construct()", $copy_list);
foreach ([
    [str_replace('const ELEMENT_LIST &$source', 'ELEMENT_LIST &$source', $copy_list), 'return 0;', 'const reference'],
    [str_replace($copy_body, '    public function __copy_construct(const nested &$source): void { }' . "\n", $copy_list), 'return 0;', 'exact const source'],
    [$copy_list, '$a list_i32; $a->__copy_construct($a); return 0;', 'cannot be called explicitly'],
    [$copy_list, '$a list_i32; $b list_i32; $b = $a; return 0;', 'assignment'],
    [$copy_list, '$a list_i32; $a->clear(); slots_release<int32>($a->data); $b list_i32 = $a; return 0;', 'ownership requirements'],
    [str_replace('$length int = $source->count();', '$source->clear(); $length int = $source->count();', $copy_list), 'return 0;', 'const'],
    [str_replace('slots_allocate<ELEMENT_TYPE>($this->data, $length + 1);', '', $copy_list), 'return 0;', 'ownership'],
] as [$definition, $invalid, $message]) {
    Growing_List_Test::write($root . '/copy-negative.phs', $definition . $copy_workload . $invalid);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/copy-negative.phs'), $message);
}
// Assignment has live source/destination storage and its own source-defined policy.
$assignment_body = <<<'PHS'
    /** Copy into replacement storage before touching the live destination. */
    public function __copy_assign(const ELEMENT_LIST &$source): void
    {
        $length int = $source->count();
        $replacement slots<ELEMENT_TYPE>;
        slots_allocate<ELEMENT_TYPE>($replacement, $length + 1);
        $index int = 0;
        while ($index < $length) {
            slots_push<ELEMENT_TYPE>($replacement, $source->get($index));
            $index = $index + 1;
        }
        $this->clear();
        slots_release<ELEMENT_TYPE>($this->data);
        slots_transfer<ELEMENT_TYPE>($replacement, $this->data);
    }
    public function forward(const ELEMENT_LIST &$source): void { $this = $source; }
    public function replace_one($value ELEMENT_TYPE): int { $this->clear(); $this->append($value); return 0; }
    public const function after(const ELEMENT_LIST &$source, $ignored int): ELEMENT_TYPE { return $source->get(0); }

    /** Deliberately invalid for aliases although the final ownership state is unchanged. */
    public function read_after_release(const ELEMENT_LIST &$source): ELEMENT_TYPE
    {
        $this->clear();
        slots_release<ELEMENT_TYPE>($this->data);
        $result ELEMENT_TYPE = $source->get(0);
        slots_allocate<ELEMENT_TYPE>($this->data, 1);
        return $result;
    }
    public function forward_bad(const ELEMENT_LIST &$source): ELEMENT_TYPE { return $this->read_after_release($source); }
PHS;
$assigned_list = str_replace('    public function __construct()', $assignment_body . "\n    public function __construct()", $copy_list);
$assignment_workload = <<<'PHS'
const FIRST: int32 = 11;
const SECOND: int32 = 22;
const LAST: int32 = 44;
const SMALL: uint8 = 7;
struct nested { public list_i32 $child; }
function exercise(): int
{
    $a list_i32;
    $a->append(FIRST);
    $a->append(SECOND);
    $b list_i32;
    $b->append(LAST);
    $b = $a;
    $a = $a;
    $a->forward($a);
    $b->set(0, LAST);
    $small list_byte;
    $small->append(SMALL);
    $small_destination list_byte;
    $small_destination = $small;
    if ($small_destination->get(0) < SMALL) { return 101; }
    if (SMALL < $small_destination->get(0)) { return 102; }
    $outer nested;
    slots_push<int32>($outer->child->data, FIRST);
    $outer_destination nested;
    $outer_destination = $outer;
    $outer_destination->child = $b;
    $stable int32 = $a->after($a, $a->replace_one(LAST));
    if ($stable < LAST) { return 103; }
    if (LAST < $stable) { return 104; }
    return $b->get(0) + $b->get(1) + $outer->child->data[0] + $outer_destination->child->data[1];
}
PHS;
Growing_List_Test::write($list_path, $assigned_list);
Growing_List_Test::write($root . '/src/workload.phs', $assignment_workload);
$assignment_session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$assignment_first = $assignment_session->compile($manifest, $root . '/assigned');
Check::check(Growing_List_Test::run([$root . '/assigned']) === [30, '', ''],
    'Assignment, self-assignment, forwarding, nested/projected destinations and stable object borrows release seventeen allocations once');
$assignment_before = serialize($assignment_first);
$assignment_tasks = array_map(static fn($result) => $result->task, array_values($assignment_first->lifetimes->ownership_results));
$assignment_results = array_map(\analyze_lifetimes\Ownership_Worker::prepare(...), $assignment_tasks);
(new \analyze_lifetimes\Ownership_Join($assignment_tasks))->join(array_reverse($assignment_results));

// Permuting contract maps must preserve alias validity, flow facts and stable summary reuse.
$permuted_tasks = [];
$permuted_results = [];
foreach ($assignment_tasks as $task)
{
    $permuted = new \analyze_lifetimes\ownership_task($task->key, $task->subject,
        Growing_List_Test::reordered_dependencies($task->dependencies));
    $before_task = serialize($permuted);
    $result = \analyze_lifetimes\Ownership_Worker::prepare($permuted);
    $original = $assignment_first->lifetimes->ownership_results[$task->key];
    Check::check(($result->summary == $original->summary) && ($result->allocations == $original->allocations),
        'Parameter/path order cannot change ownership meaning or propagated block facts');
    Check::check(serialize($permuted) === $before_task, 'Solving and validation preserve fixed worker inputs');
    $permuted_tasks[] = $permuted;
    $permuted_results[] = $result;
}
$permuted_accepted = (new \analyze_lifetimes\Ownership_Join($permuted_tasks, $assignment_first->lifetimes->ownership_results))
    ->join(array_reverse($permuted_results));
foreach ($permuted_accepted as $key => $result) {
    Check::check($result->summary === $assignment_first->lifetimes->ownership_results[$key]->summary,
        'Equal contract meaning retains summary identity after reordered preparation');
}

// Alias constraints are accepted only when their endpoints belong to the fixed summary.
foreach ($assignment_results as $output)
{
    if ($output->summary->distinct === []) {
        continue;
    }
    $pair = array_values($output->summary->distinct)[0];
    $pair[1] = '999:missing';
    sort($pair, SORT_STRING);
    $invalid_summary = new \analyze_lifetimes\ownership_summary($output->summary->parameters, [implode('|', $pair) => $pair]);
    $invalid_output = new \analyze_lifetimes\ownership_result($output->task, $invalid_summary, $output->allocations);
    Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join([$output->task]))->join([$invalid_output]), 'Unknown ownership alias endpoint');
    break;
}

Growing_List_Test::edit($list_path, str_replace($assignment_body, str_replace('$source->get($index)', '$source->get(0)', $assignment_body), $assigned_list));
$assignment_second = $assignment_session->compile($manifest, $root . '/assigned');
Check::check(Growing_List_Test::run([$root . '/assigned']) === [41, '', ''], 'One assignment-body edit changes destination contents');
Check::check((!$assignment_second->inputs->context->full_rebuild)
    && ($assignment_second->backend->layouts === $assignment_first->backend->layouts)
    && ($assignment_second->types->instances->keys === $assignment_first->types->instances->keys)
    && (Files::read($runtime . '/current.json') === $runtime_before) && (serialize($assignment_first) === $assignment_before),
    'Assignment increment preserves layouts, concrete identities, prepared runtime and retained snapshot');
$assignment_modules = [];
foreach ($assignment_first->llvm->ir_by_file() as $id => $ir) {
    $assignment_modules[] = $root . '/assignment-module-' . $id . '.ll';
    Growing_List_Test::write($assignment_modules[count($assignment_modules) - 1], $ir);
}
[$status, , $error] = Growing_List_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$assignment_modules,
    ...$assignment_first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$assignment_first->backend->runtime->link_arguments, '-o', $root . '/assignment-optimized']);
Check::check(($status === 0) && (Growing_List_Test::run([$root . '/assignment-optimized']) === [30, '', '']), 'Assignment O1/ThinLTO: ' . $error);

// Aliases are accepted from proved access order, not from lifecycle or method spelling.
foreach (['$a->read_after_release($a);', '$a->forward_bad($a);'] as $invalid) {
    Growing_List_Test::write($root . '/assignment-negative.phs', $assigned_list . $assignment_workload . '$a list_i32; $a->append(FIRST); ' . $invalid . ' return 0;');
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/assignment-negative.phs'), 'Aliased arguments');
}
Growing_List_Test::write($root . '/safe-alias.phs', $alias_list . $copy_workload . 'function check_alias(): int { $a list_i32; $a->append(FIRST); $a->replace_from($a); return $a->count(); } return check_alias() + finished();');
(new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/safe-alias.phs', $root . '/safe-alias');
Check::check(Growing_List_Test::run([$root . '/safe-alias']) === [35, '', ''], 'Read-before-replacement permits ordinary method aliases');
// Access-order edits change alias requirements even when required and final resource states agree.
$ordered_method = <<<'PHS'
    public function ordered(const ELEMENT_LIST &$source): ELEMENT_TYPE
    {
        $result ELEMENT_TYPE = $source->get(0);
        $this->clear();
        slots_release<ELEMENT_TYPE>($this->data);
        slots_allocate<ELEMENT_TYPE>($this->data, 1);
        return $result;
    }
PHS;
$ordered_list = str_replace('    public function __construct()', $ordered_method . "\n    public function __construct()", $list);
$ordered_source = $ordered_list . <<<'PHS'
const FIRST: int32 = 11;
function exercise(): int { $a list_i32; $a->append(FIRST); return $a->ordered($a); }
return exercise() + finished();
PHS;
$ordered_path = $root . '/ordered.phs';
Growing_List_Test::write($ordered_path, $ordered_source);
$ordered_session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$ordered_first = $ordered_session->compile($ordered_path, $root . '/ordered');
Check::check(Growing_List_Test::run([$root . '/ordered']) === [44, '', ''], 'A stable object alias can be read before its storage is released');
$ordered_before = serialize($ordered_first);
$read_late = str_replace('        $result ELEMENT_TYPE = $source->get(0);' . "\n", '', $ordered_method);
$read_late = str_replace('        slots_allocate<ELEMENT_TYPE>($this->data, 1);', '        $result ELEMENT_TYPE = $source->get(0);' . "\n        slots_allocate<ELEMENT_TYPE>(\$this->data, 1);", $read_late);
Growing_List_Test::edit($ordered_path, str_replace($ordered_method, $read_late, $ordered_source));
Check::rejects(static fn() => $ordered_session->compile($ordered_path), 'Aliased arguments');
Check::check(serialize($ordered_first) === $ordered_before, 'Rejected alias-dependent increment preserves its accepted snapshot');

foreach ([
    ['$a list_i32; $a->__copy_assign($a); return 0;', 'cannot be called explicitly'],
    ['$a list_i32; $a = new list_i32(); return 0;', 'existing source object'],
] as [$invalid, $message]) {
    Growing_List_Test::write($root . '/assignment-negative.phs', $assigned_list . $assignment_workload . $invalid);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/assignment-negative.phs'), $message);
}
echo "growing source list, copy and assignment tests passed\n";

// Owned source results preserve field resource facts across calls and one body replacement.
Growing_List_Test::write($list_path, $copy_list);
$return_workload = <<<'PHS'
const FIRST: int32 = 11;
const SECOND: int32 = 22;
const OTHER: int32 = 33;
struct returned_wrapper { public list_i32 $child; }
function make_nested(): returned_wrapper
{
    $value returned_wrapper;
    slots_push<int32>($value->child->data, FIRST);
    return $value;
}
function make_list(): list_i32
{
    $value list_i32;
    $value->append(FIRST);
    return $value;
}
function forward_list(): list_i32 { return make_list(); }
function clone_list(const list_i32 &$value): list_i32 { return $value; }
function exercise(): int
{
    $nested returned_wrapper = make_nested();
    if (slots_count<int32>($nested->child->data) < 1) { return 101; }
    $value list_i32 = forward_list();
    $copy list_i32 = clone_list($value);
    $copy->set(0, OTHER);
    forward_list();
    if ($copy->get(0) < OTHER) { return 100; }
    return $value->get(0);
}
PHS;
Growing_List_Test::write($root . '/src/workload.phs', $return_workload);
Growing_List_Test::write($root . '/src/main.phs', '$result int = exercise(); finished(); return $result;');
$return_session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$return_first = $return_session->compile($manifest, $root . '/returned');
Check::check(Growing_List_Test::run([$root . '/returned']) === [11, '', ''], 'Owned source list returns preserve independent allocations and release all owners');
$return_snapshot = serialize($return_first);
Growing_List_Test::edit($root . '/src/workload.phs', str_replace('append(FIRST)', 'append(SECOND)', $return_workload));
$return_second = $return_session->compile($manifest, $root . '/returned');
Check::check(Growing_List_Test::run([$root . '/returned']) === [22, '', ''], 'One owned-result body edit replaces execution');
Check::check(serialize($return_first) === $return_snapshot, 'Owned-result update preserves its previous snapshot');
Check::check($return_first->backend === $return_second->backend, 'Owned-result body edit reuses layouts and prepared ABI');
echo "owned list results and one increment: ok\n";

$return_tasks = array_map(static fn($result) => $result->task, array_values($return_first->lifetimes->ownership_results));
$return_outputs = array_map(\analyze_lifetimes\Ownership_Worker::prepare(...), array_reverse($return_tasks));
$return_join = new \analyze_lifetimes\Ownership_Join($return_tasks, $return_first->lifetimes->ownership_results);
$accepted_returns = $return_join->join($return_outputs);
foreach ($accepted_returns as $key => $result) {
    Check::check($result->summary === $return_first->lifetimes->ownership_results[$key]->summary,
        'Reordered owned-result work retains equal summary identity');
}
foreach ($return_outputs as $output)
{
    if ($output->summary->result === []) {
        continue;
    }
    $missing = new \analyze_lifetimes\ownership_summary($output->summary->parameters, $output->summary->distinct);
    $invalid = new \analyze_lifetimes\ownership_result($output->task, $missing, $output->allocations);
    Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join([$output->task]))->join([$invalid]), 'Incomplete owned result');
    $states = array_fill_keys(array_keys($output->summary->result), \analyze_lifetimes\Resource_States::IDENTITY);
    $invalid = new \analyze_lifetimes\ownership_result($output->task,
        new \analyze_lifetimes\ownership_summary($output->summary->parameters, $output->summary->distinct, $states), $output->allocations);
    Check::rejects(static fn() => (new \analyze_lifetimes\Ownership_Join([$output->task]))->join([$invalid]), 'Invalid owned result');
    break;
}
Check::check(serialize($return_first) === $return_snapshot, 'Owned-result summary joins preserve retained inputs');

// Existing resource analysis requires stable local/subobject operands for summarized borrows.
Growing_List_Test::write($root . '/temporary-owner.phs', $copy_list . $return_workload
    . '$value list_i32 = clone_list(make_list()); return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/temporary-owner.phs'),
    'temporary record borrowing is unsupported');
