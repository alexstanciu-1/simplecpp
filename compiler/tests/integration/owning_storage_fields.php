<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Owning_Field_Test
{
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

}

$root = getcwd() . '/owning-field-proof';
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
Files::write($root . '/observer.hpp', <<<'CPP'
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
Files::write($root . '/src/main.phs', 'return exercise() + finished();');
$source = <<<'PHS'
const VALUE: int32 = 7;
struct owner {
    public slots<int32> $buffer;
    public slots<int32> $spare;
    public function __construct(): void {
        slots_allocate<int32>($this->buffer, 1);
        slots_push<int32>($this->buffer, VALUE);
        slots_allocate<int32>($this->spare, 1);
        slots_push<int32>($this->spare, VALUE);
    }
    public const function read(): int { return $this->buffer[0]; }
    public function replace(): void {
        $next slots<int32>;
        slots_allocate<int32>($next, 1);
        slots_push<int32>($next, VALUE);
        slots_pop<int32>($this->buffer);
        slots_release<int32>($this->buffer);
        slots_transfer<int32>($next, $this->buffer);
    }
    public function __destruct(): void {
        while (slots_count<int32>($this->buffer)) {
            slots_pop<int32>($this->buffer);
        }
        slots_release<int32>($this->buffer);
        slots_pop<int32>($this->spare);
        slots_release<int32>($this->spare);
    }
}
struct envelope { public owner $first; public owner $second; }
function exercise(): int {
    $x owner;
    $nested envelope;
    $x->replace();
    $x->read();
    new owner();
    return $x->buffer[0] + $nested->second->spare[0];
}
PHS;
$path = $root . '/src/body.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$first = $session->compile($manifest, $root . '/program');
Check::check(Owning_Field_Test::run([$root . '/program']) === [113, '', ''], 'Owning field construction, replacement and destruction execute');

$owner_definition = $first->types->types->definition_for_type($first->types->types->find_type('owner', ''));
Check::rejects(static fn() => new \type_model\array_type_definition($owner_definition, 2), 'Arrays of allocation owners');

// A locally valid lifecycle can still require resource states the native payload profile cannot promise.
$construction = [$owner_definition->lifetime->default_constructor];
$verified = \prepare_backend\Export_Verification::prepare($construction, $first->types->types, $first->bodies, $first->lifetimes, [], true);
Check::check(count($verified) === 1, 'Export construction accepts its declared empty destination precondition');
Check::rejects(static fn() => \prepare_backend\Export_Verification::prepare([$owner_definition->lifetime->destructor],
    $first->types->types, $first->bodies, $first->lifetimes, [], true), 'stronger ownership preconditions');

// Summary preparation is selected, private, order-independent and provenance checked.
$accepted = $first->lifetimes->ownership_results;
$tasks = array_map(static fn($result) => $result->task, array_values($accepted));
$outputs = array_map(\analyze_lifetimes\Ownership_Worker::prepare(...), $tasks);
$joined = (new \analyze_lifetimes\Ownership_Join($tasks))->join(array_reverse($outputs));
foreach ($accepted as $key => $result) {
    Check::check($joined[$key]->summary == $result->summary, 'Private preparation preserves meaning: ' . $key);
}
$join = new \analyze_lifetimes\Ownership_Join($tasks);
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$outputs, $outputs[0]]), 'duplicate');
$stale = new \analyze_lifetimes\ownership_task($tasks[0]->key, $tasks[0]->subject, $tasks[0]->dependencies);
$bad = $outputs;
$bad[0] = \analyze_lifetimes\Ownership_Worker::prepare($stale);
Check::rejects(static fn() => $join->join($bad), 'stale');
$bad = $outputs;
$bad[0] = new \analyze_lifetimes\ownership_result($tasks[0], new \analyze_lifetimes\ownership_summary());
Check::rejects(static fn() => $join->join($bad), 'Incomplete');

// One successful body edit retains the caller when ownership meaning is unchanged.
$before = serialize($first);
Check::edit($path, str_replace('slots_push<int32>($next, VALUE);', 'slots_push<int32>($next, VALUE); $unused int = 1;', $source));
$second = $session->compile($manifest, $root . '/program');
Check::check(Owning_Field_Test::run([$root . '/program']) === [113, '', ''], 'One owning-method body increment executes');
Check::check((!$second->inputs->context->full_rebuild) && (serialize($first) === $before), 'Owning increment preserves the retained snapshot');
$reused = 0;
foreach ($second->lifetimes->ownership_results as $key => $result) {
    if (($accepted[$key] ?? null) === $result) {
        ++$reused;
    }
}
Check::check($reused > 0, 'Unchanged ownership contracts avoid caller reanalysis');

// Optimized execution consumes the same generated addresses and complete source lifecycle.
$package = Files::json($runtime . '/package/manifest.json');
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Files::write($modules[count($modules) - 1], $ir);
}
[$status, , $error] = Owning_Field_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$modules,
    ...$first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
Check::check(($status === 0) && (Owning_Field_Test::run([$root . '/optimized']) === [113, '', '']), 'Owning fields O1/ThinLTO: ' . $error);

// A separate baseline proves that a changed callee contract invalidates an unchanged caller.
$increment = $root . '/increment.phs';
$increment_source = <<<'PHS'
struct box {
    public slots<int32> $data;
    public function prepare(): void { }
    public function use(): void { slots_allocate<int32>($this->data, 1); slots_release<int32>($this->data); }
}
$x box;
$x->prepare();
$x->use();
return 0;
PHS;
Files::write($increment, $increment_source);
$changed = new \compile\Compiler_Session(runtime_package_path: $runtime);
$baseline = $changed->compile($increment);
$snapshot = serialize($baseline);
Check::edit($increment, str_replace('function prepare(): void { }', 'function prepare(): void { slots_allocate<int32>($this->data, 1); }', $increment_source));
Check::rejects(static fn() => $changed->compile($increment), 'ownership requirements');
Check::check(serialize($baseline) === $snapshot, 'Failed summary-dependent increment preserves its prior snapshot');

$prefix = substr($source, 0, strpos($source, 'function exercise'));
foreach ([
    ['$x owner; $y owner = $x; return 0;', 'copy'],
    ['struct bad { public slots<int32> $data; public function __construct(): void { slots_allocate<int32>($this->data, 1); } } new bad(); return 0;', 'Temporary destruction'],
    ['struct bad { public slots<int32> $data; public int32 $flag; public function __construct(): void { slots_allocate<int32>($this->data, 1); } public function __destruct(): void { if ($this->flag) { return; } slots_release<int32>($this->data); } } $x bad; return 0;', 'ownership requirements'],
    ['$x owner; slots_transfer<int32>($x->buffer, $x->buffer); return 0;', 'distinct empty'],
    ['$x owner; slots_transfer<int32>($x->buffer, $x->spare); return 0;', 'distinct empty'],
    ['struct bad { public owner $values[2]; } return 0;', 'Arrays of allocation owners'],
    ['struct bad { public slots<int32> $data; public function __construct(): void { slots_allocate<int32>($this->data, 1); } } $x bad; return 0;', 'ownership requirements'],
    ['struct bad { public slots<int32> $data; public const function mutate(): void { slots_allocate<int32>($this->data, 1); } } $x bad; $x->mutate(); return 0;', 'const'],
    ['struct bad { public slots<int32> $data; public function recurse(): void { $this->recurse(); } } $x bad; $x->recurse(); return 0;', 'Recursive ownership'],
] as [$invalid, $message]) {
    Files::write($root . '/negative.phs', $prefix . $invalid);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/negative.phs'), $message);
}

// The same receiver field cannot be replaced while a borrowed element remains an argument.
$borrow_source = <<<'PHS'
struct item { public int32 $value; }
struct holder {
    public slots<item> $data;
    public function __construct(): void { slots_allocate<item>($this->data, 1); $i item; slots_push<item>($this->data, $i); }
    public function reset(): int { slots_pop<item>($this->data); slots_release<item>($this->data); return 0; }
    public function consume(const item &$arg): int { $this->reset(); return $arg->value; }
}
function consume(const item &$arg, $value int): int { return $arg->value; }
$h holder;
PHS;
foreach (['return consume($h->data[0], $h->reset());', 'return $h->consume($h->data[0]);'] as $tail) {
    Files::write($root . '/negative.phs', $borrow_source . $tail);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/negative.phs'), 'active call borrow');
}
// Borrowing one field permits mutation of a disjoint field, even in the same source call.
$disjoint = str_replace('public slots<item> $data;', 'public slots<item> $data; public slots<item> $other;', $borrow_source);
$disjoint = str_replace('slots_push<item>($this->data, $i);', 'slots_push<item>($this->data, $i); slots_allocate<item>($this->other, 1); slots_push<item>($this->other, $i);', $disjoint);
$disjoint = str_replace('slots_pop<item>($this->data); slots_release<item>($this->data); return 0;', 'slots_pop<item>($this->other); slots_release<item>($this->other); return 0;', $disjoint);
$disjoint = str_replace('public function reset()', 'public function __destruct(): void { slots_pop<item>($this->data); slots_release<item>($this->data); } public function reset()', $disjoint);
Files::write($root . '/disjoint.phs', $disjoint . '$h->consume($h->data[0]); return 0;');
(new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/disjoint.phs', $root . '/disjoint');
Check::check(Owning_Field_Test::run([$root . '/disjoint']) === [0, '', ''], 'Disjoint field mutation preserves a borrowed element');
echo "owning storage field tests passed\n";
