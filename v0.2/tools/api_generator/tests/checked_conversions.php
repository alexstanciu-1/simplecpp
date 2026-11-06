<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/../php/input.php';
require_once __DIR__ . '/../php/target.php';
require_once __DIR__ . '/../php/scalars.php';
require_once __DIR__ . '/../php/families.php';
require_once __DIR__ . '/../php/render.php';
require_once __DIR__ . '/../php/render_sequences.php';
require_once __DIR__ . '/../php/render_modules.php';

$root = dirname(__DIR__, 3);
$metadata = decode(readFile($root . '/runtime/metadata/runtime.json'), 'metadata');
$publication = decode(readFile($root . '/runtime/metadata/publication.json'), 'publication');
$facts = targetFacts(decode(readFile($argv[1]), 'facts'), decode(readFile($argv[2]), 'context'));
$plan = prepareRuntime($metadata, $publication, $facts);
$files = render($plan);
$header = $files['public/scpp/conversions.hpp'];
requireThat(count($plan['checked_conversions']['functions']) === 12, 'Core numeric conversion surface changed');
requireThat(!str_contains($header, 'null_t') && !str_contains($header, 'to_string'), 'Unrelated conversion admitted');
requireThat(!str_contains($header, 'template') && !str_contains($header, '#include'), 'Conversion header gained implementation dependencies');
requireThat(str_contains($header, 'void checked_int64_to_uint8(double) = delete;'), 'Floating input narrowing is not fenced');
$native = $facts['fixed_integers']['int64']['native_name'];
requireThat(!str_contains($header, "void checked_int64_to_uint8($native) = delete;"), 'Primary native alias was deleted');

// Changing metadata changes the named ABI without editing the renderer or scalar algorithms.
$renamed = unserialize(serialize($metadata));
$renamed->checked_conversions->header = 'scpp/named_numeric.hpp';
$renamed->checked_conversions->symbol_prefix = 'convert_';
$renamedFiles = render(prepareRuntime($renamed, $publication, $facts));
requireThat(!isset($renamedFiles['public/scpp/conversions.hpp'])
    && str_contains($renamedFiles['public/scpp/named_numeric.hpp'], 'convert_float64_to_int64'), 'Named binding policy ignored');

// Removing a scalar cast policy removes helpers as well as wrappers; there is no second admission matrix.
$limited = unserialize(serialize($metadata));
$limited->scalar_casts = array_values(array_diff($limited->scalar_casts, ['floating_to_integer_truncate_checked']));
$limitedFiles = render(prepareRuntime($limited, $publication, $facts));
requireThat(!str_contains($limitedFiles['public/scpp/conversions.hpp'], 'checked_float64_to_int64')
    && str_contains($limitedFiles['public/scpp/conversions.hpp'], 'checked_int64_to_float64'), 'Conversion permission removal ignored');

// Publication follows demand, not all registered optional numeric widths.
requireThat(!str_contains($header, 'checked_int16_to_int64'), 'Unpublished numeric width leaked');
foreach (['prefix', 'path', 'collision'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'prefix' => $bad->checked_conversions->symbol_prefix = 'bad()',
        'path' => $bad->checked_conversions->header = '../private.hpp',
        'collision' => $bad->checked_conversions->header = $publication->headers->types,
    };
    try {
        prepareRuntime($bad, $publication, $facts);
        throw new \LogicException('Invalid checked conversion binding accepted');
    } catch (\RuntimeException $expected) {}
}

// This migration slice is additive: existing wrapper artifacts remain byte-identical.
$without = unserialize(serialize($metadata));
unset($without->checked_conversions);
$oldFiles = render(prepareRuntime($without, $publication, $facts));
foreach ($oldFiles as $path => $contents) {
    if (in_array($path, ['src/runtime.cpp', 'prepared.json', 'inventory.json'], true)) continue;
    requireThat($contents === $files[$path], 'Named helper publication changed an existing API: ' . $path);
}
requireThat(!isset($oldFiles['public/scpp/conversions.hpp']), 'Removed helper policy still emitted');
echo "Named conversion bindings, admission, input guards and unchanged wrapper APIs passed\n";
