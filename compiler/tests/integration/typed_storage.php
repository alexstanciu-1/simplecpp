<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Storage_Test
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
    /** Refresh integrity records so malformed metadata is tested at the semantic adapter boundary. */
    public static function metadata(string $runtime, array $metadata): void
    {
        Files::write_json($runtime . '/package/metadata.json', $metadata);
        $manifest = Files::json($runtime . '/package/manifest.json');
        $manifest['artifacts']['metadata.json'] = hash_file('sha256', $runtime . '/package/metadata.json');
        Files::write_json($runtime . '/package/manifest.json', $manifest);
        $pointer = Files::json($runtime . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $runtime . '/package/manifest.json');
        Files::write_json($runtime . '/current.json', $pointer);
    }
}

$root = getcwd() . '/storage-proof';
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
Files::write_json($root . '/definitions/storage.json', $definitions);
$config = Files::json($preparation . '/config.json');
$config['include_directories'] = [$preparation . '/include'];
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$runtime = $root . '/runtime';
$manifest = $root . '/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/src/main.phs', 'return exercise() + scalar(VALUE);');
Files::write($root . '/src/types.phs', <<<'PHS'
struct item { public int32 $value; public int32 $extra[2]; }
const VALUE: int32 = 7;
function read(const item &$value): int { return $value->value + $value->extra[1]; }
function scalar($value int32): int {
    $s slots<int32>;
    slots_allocate<int32>($s, 2);
    slots_push<int32>($s, $value);
    $result int = $s[0];
    slots_pop<int32>($s);
    slots_release<int32>($s);
    return $result;
}
PHS);
$source = <<<'PHS'
function exercise(): int {
    $s slots<item>;
    $t slots<item>;
    $x item;
    $x->value = VALUE;
    $x->extra[1] = VALUE;
    slots_allocate<item>($s, 3);
    slots_push<item>($s, $x);
    slots_push<item>($s, $s[0]);
    slots_transfer<item>($s, $t);
    $t[1]->value = VALUE;
    $copy item = $t[0];
    $result int = read($t[1]) + read($copy) + slots_count<item>($t);
    slots_pop<item>($t);
    slots_pop<item>($t);
    slots_release<item>($t);
    slots_release<item>($s);
    return $result + slots_count<item>($s);
}
PHS;
$path = $root . '/src/body.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$first = $session->compile($manifest, $root . '/program');
Check::check(Storage_Test::run([$root . '/program']) === [37, '', ''], 'Typed scalar/record storage, nested fields, transfer, borrowing and copying execute');
$before = serialize($first);
$artifacts = Files::read($runtime . '/current.json');
Check::edit($path, str_replace('$result + slots_count', '$result + 1 + slots_count', $source));
$second = $session->compile($manifest, $root . '/program');
Check::check(Storage_Test::run([$root . '/program']) === [38, '', ''], 'One body increment replaces typed operations');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend->layouts === $first->backend->layouts)
    && ($second->backend->storage_targets === $first->backend->storage_targets)
    && (serialize($first) === $before) && (Files::read($runtime . '/current.json') === $artifacts), 'Increment retains layouts, primitives, prepared artifacts and old snapshot');

// ABI work is selected up front, executes privately, and accepts arbitrary completion order.
$tasks = \prepare_backend\Storage_Preparation::select($first->backend->runtime, $first->backend->configuration, null, true);
$results = array_map(\prepare_backend\Storage_Preparation::prepare(...), $tasks);
$join = new \prepare_backend\Storage_Join($first->backend->runtime, $first->backend->configuration, null, $tasks);
Check::check($join->join(array_reverse($results)) == $first->backend->storage_targets, 'Reordered native ABI results join deterministically');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
$bad_target = new \prepare_backend\abi_target($results[0]->target->link_name, 'ccc', 'i8', []);
$bad = $results;
$bad[0] = new \prepare_backend\prepared_storage_primitive($tasks[0], $bad_target);
Check::rejects(static fn() => $join->join($bad), 'stale');
$none = \prepare_backend\Storage_Preparation::select($first->backend->runtime, $first->backend->configuration, $first->backend, false);
Check::check($none === [], 'Unchanged native protocol selects no repeated preparation');

$prefix = 'struct item { public int32 $value; } const VALUE: int32 = 7; ';
foreach ([
    ['template<typename T> struct rejected { public slots<T> $storage; } return 0;', 'provider storage family'],
    ['template<typename T> function rejected(): int { $s slots<T>; return 0; } return 0;', 'provider storage family'],
    ['$s slots<int32>; return $s[0];', 'requires an owned allocation'],
    ['$s slots<int32>; slots_allocate<int32>($s, 2); slots_push<int32>($s, 7); slots_release<int32>($s); return 0;', 'conversion'],
    ['$s slots<int32>; $t slots<item>; slots_allocate<int32>($s, 1); slots_transfer<int32>($s, $t); return 0;', 'argument'],
    ['$s slots<int32>; $t slots<int32> = $s; return 0;', 'copy'],
    ['$s slots<int32>; slots_allocate<int32>($s, 1); return 0;', 'must be released'],
    ['$s slots<slots<int32>>; return 0;', 'no compiler-tracked allocation ownership'],
    ['$s slots<int32, int32>; return 0;', 'type argument count mismatch'],
    ['$s slots<int32>; $s->append(VALUE); return 0;', 'source record'],
] as [$invalid, $message]) {
    Files::write($root . '/negative.phs', $prefix . $invalid);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/negative.phs'), $message);
    Check::check($error instanceof \diagnostics\Source_Error, 'Invalid storage use reports a source location');
}

// Each failure is dynamic, with all static owner obligations otherwise discharged.
foreach ([
    ['slots_allocate<int32>($s, 9223372036854775807);', 'overflow'],
    ['slots_allocate<int32>($s, 0);', 'extent'],
    ['slots_allocate<int32>($s, 1); slots_push<int32>($s, VALUE); slots_push<int32>($s, VALUE); slots_pop<int32>($s);', 'full'],
    ['slots_allocate<int32>($s, 1); slots_pop<int32>($s);', 'index'],
    ['slots_allocate<int32>($s, 1); slots_push<int32>($s, VALUE);', 'live'],
    ['slots_allocate<int32>($s, 1); $result int = $s[0];', ''],
    ['slots_allocate<int32>($s, 1); $i int = 9223372036854775807 + 1; $result int = $s[$i];', ''],
    ['slots_allocate<int32>($s, 1); slots_push<int32>($s, VALUE); $result int = $s[2]; slots_pop<int32>($s);', ''],
] as $index => [$body, $message]) {
    Files::write($root . '/failure.phs', $prefix . '$s slots<int32>; ' . $body . ' slots_release<int32>($s); return 0;');
    (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/failure.phs', $root . '/failure');
    [$status, , $error] = Storage_Test::run([$root . '/failure']);
    Check::check(($status !== 0) && (($message === '') || str_contains($error, $message)), 'Dynamic prefix guard ' . $index . ': ' . $error);
}

// Optimized execution consumes the same emitted modules and native bridge protocol.
$package = Files::json($runtime . '/package/manifest.json');
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Files::write($modules[count($modules) - 1], $ir);
}
[$status, , $error] = Storage_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$modules,
    ...$first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
Check::check(($status === 0) && (Storage_Test::run([$root . '/optimized']) === [37, '', '']), 'Typed storage at O1/ThinLTO: ' . $error);

// Fail closed at both JSON boundaries, including validly checksummed but invalid imported metadata.
$bad = $definitions;
unset($bad['types'][0]['storage_family']['primitives']['commit']);
Files::write_json($root . '/definitions/storage.json', $bad);
Check::rejects(static fn() => new \runtime_preparation\Definitions($root . '/definitions'), 'storage primitives');
$bad = $definitions;
$bad['operations'][2]['expose_as'] = ['name' => 'raw_address', 'namespace' => ''];
Files::write_json($root . '/definitions/storage.json', $bad);
Check::rejects(static fn() => new \runtime_preparation\Definitions($root . '/definitions'), 'internal primitives');
Files::write_json($root . '/definitions/storage.json', $definitions);
$metadata = Files::json($runtime . '/package/metadata.json');
$indices = array_flip(array_column($metadata['operations'], 'id'));
$bad = $metadata;
$bad['operations'][$indices['element_storage.at']]['abi']['return_type'] = 'i64';
Storage_Test::metadata($runtime, $bad);
Check::rejects(static fn() => \load_runtime\Package_Adapter::open($runtime, \Step_Test::run(new \load_runtime\Language_Types())), 'address');
Storage_Test::metadata($runtime, $metadata);
Check::check(serialize($first) === $before, 'Proofs preserve the retained compilation');
echo "typed storage ok: generic metadata, target layout, prefix guards, native joins, increment and ThinLTO\n";
