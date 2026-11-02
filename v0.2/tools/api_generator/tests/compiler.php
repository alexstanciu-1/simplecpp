<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/../php/input.php';
require_once __DIR__ . '/../php/target.php';
require_once __DIR__ . '/../php/scalars.php';
require_once __DIR__ . '/../php/publication.php';
require_once __DIR__ . '/../php/render_compiler.php';

$root = dirname(__DIR__, 3);
$facts = targetFacts(decode(readFile($argv[1]), 'facts'), decode(readFile($argv[2]), 'context'));
$metadata = decode(readFile($root . '/runtime/metadata/runtime.json'), 'metadata');
$files = renderCompiler($metadata, $facts);
requireThat(str_contains($files['cpp/native_values.php'], "=> '__INT64_TYPE__'"), 'Missing metadata-derived representation');
$bad = clone $metadata;
$bad->types = clone $metadata->types;
unset($bad->types->int64);
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Missing int64 was accepted');
} catch (\RuntimeException $error) {
    requireThat(str_contains($error->getMessage(), 'requires int64'), 'Unexpected rejection');
}
$work = sys_get_temp_dir() . '/scpp-compiler-generation-' . bin2hex(random_bytes(8));
mkdir($work);
try {
    publish($files, $work, false, ['php', 'json']);
    publish($files, $work, true, ['php', 'json']);
    file_put_contents($work . '/retired.php', '<?php // obsolete');
    file_put_contents($work . '/keep.txt', 'retained');
    try {
        publish($files, $work, true, ['php', 'json']);
        throw new \LogicException('Freshness ignored obsolete PHP');
    } catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), 'Obsolete generated file'), 'Unexpected freshness rejection');
    }
    publish($files, $work, false, ['php', 'json']);
    requireThat(!file_exists($work . '/retired.php') && file_exists($work . '/keep.txt'), 'Extension-scoped cleanup failed');
    require $work . '/cpp/native_values.php';
    // Exercise generated code without depending on the compiler's build publication.
    require $root . '/compiler/04_analyze/prepare/structures.php';
    $lowerer = new class {
        use \scpp\compiler\cpp_native_values { native_integer as public; native_type as public; }
    };
    requireThat($lowerer->native_type(\scpp\compiler\type_definition::int64) === '__INT64_TYPE__', 'Wrong native type');
    foreach ([0, 8, PHP_INT_MAX, PHP_INT_MIN] as $value) {
        $expected = $value === PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : $value . 'LL';
        requireThat($lowerer->native_integer($value) === $expected, 'Incorrect literal lowering');
    }
} finally {
    foreach (['cpp/native_values.php', 'inventory.json', 'retired.php', 'keep.txt'] as $path) {
        if (file_exists($work . '/' . $path)) unlink($work . '/' . $path);
    }
    rmdir($work . '/cpp');
    rmdir($work);
}
echo "Generated native lowering, metadata admission, freshness and cleanup passed\n";
