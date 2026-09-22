<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Custom_Lifecycle_Test
{
    /** Capture native events and status; expected traces come from independent C++ source. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start custom lifecycle proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }
}

$root = getcwd() . '/custom-lifecycle';
Files::directory($root . '/definitions');
Files::directory($root . '/src');
Files::write($root . '/observer.hpp', <<<'CPP'
#pragma once
#include <scpp/vector_t.hpp>
#include <cstdint>
#include <cstdio>
#include <cstdlib>
namespace proof {
inline int live = 0;
struct item { std::int32_t value; };
struct alignas(64) sequence {
    scpp::vector_t<item> values;
    const sequence *identity;
    sequence() : values{{7}}, identity(this) { ++live; std::puts("field+"); }
    sequence(const sequence &other) : values(other.values), identity(this) { other.check(); ++live; std::puts("field-copy"); }
    void check() const { if (identity != this || reinterpret_cast<std::uintptr_t>(this) % 64) std::abort(); }
    ~sequence() { check(); --live; std::puts("field-"); }
    std::int64_t read() const { check(); return values.at(std::size_t(0)).value; }
};
inline std::int64_t event(std::int64_t value) { std::printf("body:%lld\n", (long long)value); return 0; }
inline std::int64_t finished() { if (live) std::abort(); return 0; }
}
CPP);
$types = [
    ['id' => 'integer', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
        'language_type' => ['name' => 'int', 'namespace' => '']],
    ['id' => 'sequence', 'kind' => 'runtime_value', 'cpp_name' => 'proof::sequence', 'header' => 'observer.hpp',
        'storage' => 'inline', 'struct_field' => true,
        'lifecycle' => ['default_construct' => 'sequence.default', 'copy_construct' => 'sequence.copy', 'destroy' => 'sequence.destroy'],
        'language_type' => ['name' => 'sequence', 'namespace' => '']],
];
$operations = [
    ['id' => 'sequence.default', 'kind' => 'construct', 'type' => 'sequence', 'parameters' => [], 'error_policy' => 'terminate'],
    ['id' => 'sequence.copy', 'kind' => 'copy_construct', 'type' => 'sequence', 'error_policy' => 'terminate'],
    ['id' => 'sequence.destroy', 'kind' => 'destroy', 'type' => 'sequence', 'error_policy' => 'terminate'],
    ['id' => 'sequence.read', 'kind' => 'const_method', 'type' => 'sequence', 'member' => 'read',
        'result_type' => 'integer', 'borrow_scope' => 'call', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'read', 'namespace' => '']],
    ['id' => 'event', 'kind' => 'free_function', 'cpp_name' => 'proof::event', 'header' => 'observer.hpp',
        'parameters' => ['integer'], 'result_type' => 'integer',
        'error_policy' => 'terminate', 'expose_as' => ['name' => 'event', 'namespace' => '']],
    ['id' => 'finished', 'kind' => 'free_function', 'cpp_name' => 'proof::finished', 'header' => 'observer.hpp',
        'parameters' => [], 'result_type' => 'integer', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'finished', 'namespace' => '']],
];
// A native field may be copyable without exposing a default constructor to source composition.
$types[] = ['id' => 'copy_only', 'kind' => 'runtime_value', 'cpp_name' => 'proof::sequence', 'header' => 'observer.hpp',
    'storage' => 'inline', 'struct_field' => true,
    'lifecycle' => ['copy_construct' => 'copy_only.copy', 'destroy' => 'copy_only.destroy'],
    'language_type' => ['name' => 'copy_only', 'namespace' => '']];
$operations[] = ['id' => 'copy_only.copy', 'kind' => 'copy_construct', 'type' => 'copy_only', 'error_policy' => 'terminate'];
$operations[] = ['id' => 'copy_only.destroy', 'kind' => 'destroy', 'type' => 'copy_only', 'error_policy' => 'terminate'];
Files::write_json($root . '/definitions/observer.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['include_directories'] = array_map(static fn($path) => realpath(dirname(__DIR__, 2) . '/src-runtime-preparation/' . $path), $config['include_directories']);
$config['include_directories'][] = $root;
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$package = Files::json($root . '/runtime/package/manifest.json');
$manifest = $root . '/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/src/main.phs', 'return exercise() + finished();');
$source = <<<'PHS'
const ONE: int32 = 1;
const TEN: int32 = 10;
const TWENTY: int32 = 20;
const THIRTY: int32 = 30;
const NEXT: int32 = 31;
struct scratch { public sequence $value; }
struct leaf {
    public int32 $tag;
    public sequence $resource;
    public function __construct(): void {
        event($this->tag);
        $this->initialize();
        event(read($this->resource));
        if ($this->tag) { return; }
        event(88);
    }
    public function initialize(): void { $this->tag = ONE; }
    public function __destruct(): void {
        $local scratch;
        event(TEN + $this->tag);
        if ($this->tag) { return; }
        event(99);
    }
    public const function value(): int { return read($this->resource); }
}
struct box_leaf {
    public leaf $child;
    public int32 $marker;
    public function __construct(): void { event(TWENTY + $this->marker); }
    public function __destruct(): void { event(THIRTY + $this->marker); }
}
struct box_i32 {
    public int32 $child;
    public int32 $marker;
    public function __construct(): void { event(TWENTY + $this->marker); }
    public function __destruct(): void { event(THIRTY + $this->marker); }
}
struct outer {
    public box_leaf $first;
    public box_leaf $others[2];
    public function __construct(): void { event(40); }
    public function __destruct(): void { event(50); }
}
function exercise(): int {
    $a outer;
    $b outer = $a;
    $b->first->child->tag = ONE + ONE;
    new box_leaf();
    $plain box_i32;
    $single leaf;
    return $single->value();
}
PHS;
$path = $root . '/src/types.phs';
Files::write($path, $source);
Files::write($root . '/oracle.cpp', <<<'CPP'
#include "observer.hpp"
struct leaf {
    std::int32_t tag{};
    proof::sequence resource;
    leaf() { proof::event(tag); initialize(); proof::event(resource.read()); if (tag) return; proof::event(88); }
    void initialize() { tag = 1; }
    ~leaf() { proof::sequence local; proof::event(10 + tag); if (tag) return; proof::event(99); }
    std::int64_t value() const { return resource.read(); }
};
template<typename T> struct box {
    T child{};
    std::int32_t marker{};
    box() { proof::event(20 + marker); }
    ~box() {
#if BODY_EDIT
        proof::event(31 + marker);
#else
        proof::event(30 + marker);
#endif
    }
};
struct outer {
    box<leaf> first;
    box<leaf> others[2];
    outer() { proof::event(40); }
    ~outer() { proof::event(50); }
};
static std::int64_t exercise() {
    outer a{};
    outer b = a;
    b.first.child.tag = 2;
    (void)box<leaf>{};
    box<std::int32_t> plain{};
    leaf single{};
    return single.value();
}
int main() { return exercise() + proof::finished(); }
CPP);
$expected = [];
foreach ([0, 1] as $edit)
{
    $command = [$package['link_driver']['executable'], '--driver-mode=g++', '-std=c++23', '-DBODY_EDIT=' . $edit];
    foreach ($config['include_directories'] as $include) {
        $command[] = '-I' . $include;
    }
    [$status, , $error] = Custom_Lifecycle_Test::run([...$command, $root . '/oracle.cpp', '-o', $root . '/oracle']);
    Check::check($status === 0, 'Build native custom lifecycle oracle: ' . $error);
    $expected[] = Custom_Lifecycle_Test::run([$root . '/oracle']);
}
$session = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime');
$first = $session->compile($manifest, $root . '/program');
$actual = Custom_Lifecycle_Test::run([$root . '/program']);
Check::check($actual === $expected[0], 'Custom lifecycle matches C++ ordering and receiver identity: ' . json_encode([$actual, $expected[0]]));
Check::check($actual[0] === 7, 'Managed receiver method returns its live field value');
$before = serialize($first);
$operations = $first->types->types->lifecycle_operations();
$custom = array_filter($operations, static fn($operation) => $operation->body_symbol_id !== 0);
Check::check(count($custom) === 8, 'Ordinary and two concrete receivers prepare complete custom operations');
Check::check(count($first->backend->lifecycle_bodies) === count($custom), 'Each custom operation imports one concrete checked body');
foreach ($first->lifetimes->bodies() as $body) {
    // Receiver lifetime is borrowed; field cleanup belongs to the complete operation.
    foreach ($body->cleanups as $cleanup) {
        Check::check(($cleanup->subject !== \analyze_lifetimes\cleanup_subject::local)
            || !$body->body->local_passing($cleanup->subject_id)->is_borrow(), 'Borrowed receivers never acquire local cleanup');
    }
}

// Source body references remain explicit in debug exports, without copying syntax into plans.
$export = json_decode($first->backend->to_json(), true, flags: JSON_THROW_ON_ERROR);
$exported = [];
foreach ($export['abi_targets'] as $target) {
    if (($target['lifecycle_operation']['body_symbol_id'] ?? 0) !== 0) {
        $exported[$target['link_name']] = $target['lifecycle_operation']['body_symbol_id'];
    }
}
Check::check(count($exported) === count($custom), 'Every custom lifecycle declaration is exported');
foreach ($custom as $operation) {
    Check::check(($exported[$operation->link_name] ?? null) === $operation->body_symbol_id, 'Export preserves exact source identity');
}

// Even equal-looking symbol stores cannot replace the fixed source identity snapshot in a record task.
$task = \resolve_types\Record_Preparation::select($first->symbols->current, $first->types->catalog,
    $first->types->types, true, $first->resolutions)[0];
$stale = new \resolve_types\record_task($task->input, $task->catalog, $task->names, symbols: clone $first->symbols->current);
Check::rejects(static fn() => (new \resolve_types\Record_Join(clone $first->types->types, [$stale], $first->symbols->current))->join([]), 'Stale record task');

// Selected lifecycle workers accept arbitrary completion order, with complete body imports.
$tasks = array_map(static fn($operation) => new \emit_llvm\lifecycle_emission_task($operation, $first->backend), $operations);
$results = array_map(\emit_llvm\Lifecycle_Emission::emit(...), $tasks);
$join = new \emit_llvm\Lifecycle_Emission_Join($first->backend, [], $tasks);
$accepted = $join->join(array_reverse($results));
Check::check(array_map(static fn($row) => $row->ir, $accepted) === array_map(static fn($row) => $row->ir, $first->llvm->lifecycle),
    'Generated custom lifecycle joins deterministically');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
foreach ($results as $index => $result)
{
    if ($result->task->operation->body_symbol_id !== 0)
    {
        $invalid = $results;
        $references = $result->references;
        unset($references[$first->backend->lifecycle_bodies[$result->task->operation->link_name]->link_name]);
        $invalid[$index] = new \emit_llvm\emitted_lifecycle($result->task, $result->ir, $references);
        Check::rejects(static fn() => $join->join($invalid), 'Missing or stale');
        break;
    }
}

// Link complete operations and source bodies across file modules through ThinLTO.
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Files::write($modules[count($modules) - 1], $ir);
}
[$status, , $error] = Custom_Lifecycle_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$modules,
    ...$first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
Check::check(($status === 0) && (Custom_Lifecycle_Test::run([$root . '/optimized']) === $expected[0]),
    'Custom bodies and complete operations preserve behavior under O1/ThinLTO: ' . $error);

// A custom source body edit replaces both demanded bodies, without changing field plans or layouts.
Check::edit($path, str_replace('event(THIRTY + $this->marker);', 'event(NEXT + $this->marker);', $source));
$second = $session->compile($manifest, $root . '/program');
Check::check(Custom_Lifecycle_Test::run([$root . '/program']) === $expected[1], 'Edited source destructor reaches every demanded receiver');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend->layouts === $first->backend->layouts)
    && ($second->types->types->lifecycle_operations() === $operations) && (serialize($first) === $before),
    'Body edit retains type plans, target layouts and old snapshot purity');

// Invalid lifecycle declarations and explicit calls fail at source boundaries.
foreach ([
    ['struct invalid { public int32 $field; public function __construct(): int { return 0; } }', 'must return void'],
    ['struct invalid { public int32 $field; public function __destruct($arg int): void { } }', 'no explicit parameters'],
    ['struct invalid { public int32 $field; public const function __destruct(): void { } }', 'mutable receiver'],
    [$source . '$x leaf; $x->__destruct();', 'cannot be called explicitly'],
    [$source . '$x leaf; $x->__construct();', 'cannot be called explicitly'],
] as [$invalid, $message]) {
    Files::write($root . '/negative.phs', $invalid . ' return 0;');
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))
        ->compile($root . '/negative.phs'), $message);
    Check::check($error instanceof \diagnostics\Source_Error, 'Invalid lifecycle produces a source diagnostic');
}
Check::check(serialize($first) === $before, 'Rejected custom lifecycle inputs preserve retained state');
// A custom copier default-initializes managed fields; automatic enclosing copies invoke it recursively.
$custom_copy = <<<'PHS'
const ONE: int32 = 1;
struct copied {
    public int32 $tag;
    public sequence $resource;
    public function __construct(): void { event(100); $this->tag = ONE; }
    public function __copy_construct(const copied &$source): void
    {
        event($this->tag);
        event(read($this->resource));
        $this->tag = $source->tag + ONE;
        event(200);
    }
    public function __destruct(): void { event($this->tag); }
}
struct holder { public copied $children[2]; }
function exercise(): int { $a holder; $b holder = $a; return 0; }
return exercise() + finished();
PHS;
Files::write($root . '/copy.phs', $custom_copy);
$copy = (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))->compile($root . '/copy.phs', $root . '/copy');
$copy_trace = "field+\nbody:100\nfield+\nbody:100\n"
    . "field+\nbody:0\nbody:7\nbody:200\nfield+\nbody:0\nbody:7\nbody:200\n"
    . "body:2\nfield-\nbody:2\nfield-\nbody:1\nfield-\nbody:1\nfield-\n";
Check::check(Custom_Lifecycle_Test::run([$root . '/copy']) === [0, $copy_trace, ''],
    'Custom copy defaults fields, skips enclosing default body and composes through fixed arrays');
foreach ($copy->types->types->lifecycle_operations() as $operation) {
    if (($operation->kind === \type_model\lifecycle_operation_kind::copy_construct) && ($operation->body_symbol_id !== 0)) {
        foreach ($operation->members as $member) {
            Check::check($member->kind === \type_model\lifecycle_operation_kind::default_construct, 'Custom copy exports its field initialization role');
        }
    }
}
// Custom assignment owns live fields: managed fields are neither initialized nor implicitly assigned.
$assignment_method = <<<'PHS'
    public function __copy_assign(const copied &$source): void
    {
        event($this->tag);
        $this->tag = $source->tag + ONE;
        event(300);
    }
PHS;
$custom_assignment = str_replace('    public function __destruct(): void { event($this->tag); }',
    $assignment_method . "\n    public function __destruct(): void { event(\$this->tag); }", $custom_copy);
$custom_assignment = str_replace('$b holder = $a; return 0;', '$b holder = $a; $b = $a; $a = $a; return 0;', $custom_assignment);
Files::write($root . '/assignment.phs', $custom_assignment);
(new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))->compile($root . '/assignment.phs', $root . '/assignment');
$assignment_trace = str_replace("body:2\nfield-\nbody:2\nfield-\nbody:1\nfield-\nbody:1\nfield-\n",
    "body:2\nbody:300\nbody:2\nbody:300\nbody:1\nbody:300\nbody:1\nbody:300\nbody:2\nfield-\nbody:2\nfield-\nbody:2\nfield-\nbody:2\nfield-\n", $copy_trace);
Check::check(Custom_Lifecycle_Test::run([$root . '/assignment']) === [0, $assignment_trace, ''],
    'Custom assignment preserves managed field identity and composes through array fields in declaration order');
// Value assignment stays available independently of a custom copy constructor.
Files::write($root . '/value-assignment.phs', <<<'PHS'
const ONE: int32 = 1;
struct independent {
    public int32 $value;
    public function __copy_construct(const independent &$source): void { $this->value = $source->value + ONE; }
}
$a independent;
$a->value = ONE;
$b independent = $a;
$b = $a;
$b = $b;
return $b->value;
PHS);
$value_assignment = (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))
    ->compile($root . '/value-assignment.phs', $root . '/value-assignment');
Check::check(Custom_Lifecycle_Test::run([$root . '/value-assignment']) === [1, '', ''],
    'Trivial field assignment preserves its value path without invoking custom copy construction');
// Zero-default fields still need owned temporary storage when a custom destructor observes the object.
Files::write($root . '/zero-owned.phs', <<<'PHS'
struct zero_owned {
    public int32 $value;
    public function __destruct(): void { event($this->value); }
}
new zero_owned();
$local zero_owned;
$copy zero_owned = $local;
$copy = $local;
return 0;
PHS);
(new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))
    ->compile($root . '/zero-owned.phs', $root . '/zero-owned');
Check::check(Custom_Lifecycle_Test::run([$root . '/zero-owned']) === [0, "body:0\nbody:0\nbody:0\n", ''],
    'Zero-default managed values retain temporary and local cleanup independently of value copying and assignment');
Files::write($root . '/missing-default.phs', <<<'PHS'
struct invalid {
    public copy_only $field;
    public function __copy_construct(const invalid &$source): void { }
}
return 0;
PHS);
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))
    ->compile($root . '/missing-default.phs'), 'field default initialization');
echo "custom lifecycle ok: field/body ordering, managed receivers, nested records, early returns, custom copies and body replacement\n";
