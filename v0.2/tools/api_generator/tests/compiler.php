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
requireThat(!isset($files['cpp/native_values.php']), 'Retired emitter trait still generated');
requireThat(str_contains($files['types/int64.php'], "return '__INT64_TYPE__'"), 'Missing metadata-derived representation');
$bad = clone $metadata;
$bad->types = clone $metadata->types;
unset($bad->types->int64);
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Missing int64 was accepted');
} catch (\RuntimeException $error) {
    requireThat(str_contains($error->getMessage(), 'requires int64'), 'Unexpected rejection');
}
requireThat(decimalPowerOfTwo(7) === '128' && decimalPredecessor('128') === '127', 'Integer boundary algorithm');
requireThat(decimalPowerOfTwo(63) === '9223372036854775808', 'Exact signed-width magnitude');
foreach ([
    ['inactive', 'default runtime'], ['overflow', 'overflow policy'], ['radix', 'radix'],
    ['suffix', 'suffix'], ['source', 'int64 metadata'], ['alias', 'only int/int64'],
] as [$change, $message]) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'inactive' => $bad->compiler->modules->{'default-runtime'}->always_available = false,
        'overflow' => $bad->compiler->integer_literals->overflow = 'wrap',
        'radix' => $bad->compiler->integer_literals->radix = 16,
        'suffix' => $bad->compiler->integer_literals->cpp_suffix = 'ULL',
        'source' => $bad->source_types->int = 'int32',
        'alias' => $bad->source_types->bool = 'int64',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Unsupported compiler metadata accepted');
    } catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), $message), $error->getMessage());
    }
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
    // Exercise freshly generated definitions without depending on compiler build output.
    require $root . '/compiler/types/definition.php';
    require $work . '/types/int64.php';
    require $work . '/types/registry.php';
    require $root . '/compiler/03_parse/structures/abstract.php';
    require $root . '/compiler/03_parse/structures/common.php';
    $type = \scpp\compiler\type_int64::instance();
    requireThat(\scpp\compiler\type_registry::resolve('int') === $type && \scpp\compiler\type_registry::resolve('int64') === $type, 'Registration identity differs');
    requireThat(\scpp\compiler\type_registry::resolve('bool') === null, 'Out-of-scope type registered');
    requireThat($type::MINIMUM === PHP_INT_MIN && $type::MAXIMUM === PHP_INT_MAX, 'Generated bounds differ');
    requireThat($type->cpp_type() === '__INT64_TYPE__', 'Wrong native type');
    requireThat($type->accepts_name('int') && $type->accepts_name('int64'), 'Missing canonical source aliases');
    foreach ([0, 8, PHP_INT_MAX, PHP_INT_MIN] as $value) {
        $expected = $value === PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : $value . 'LL';
        $literal = new \scpp\compiler\ast_integer_expression(0, 1);
        $literal->settle_integer($value);
        requireThat($type->lower_cpp_literal($literal) === $expected, 'Incorrect literal lowering');
    }
} finally {
    foreach (['types/int64.php', 'types/registry.php', 'inventory.json', 'retired.php', 'keep.txt'] as $path) {
        if (file_exists($work . '/' . $path)) unlink($work . '/' . $path);
    }
    rmdir($work . '/types');
    rmdir($work);
}
echo "Generated native lowering, metadata admission, freshness and cleanup passed\n";
