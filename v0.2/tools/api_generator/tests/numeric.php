<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/contracts.php';

$core = decode(readFile($root . '/runtime/metadata/publication.json'), 'core');
$meta = decode($metadata, 'metadata');
$plan = prepareRuntime($meta, $core, $facts);
requireThat(array_column($plan['types'], 'name') === ['byte', 'int64'], 'Core integer/byte surface grew');
requireThat(hasValue($plan, 'float64'), 'Core float64 missing');
foreach ($plan['conversions'] as $cast) {
    if ($cast['target'] === 'byte' || $cast['source'] === 'byte') {
        requireThat(!in_array($cast['target'], ['float64', 'bool_t', 'string'], true)
            && !in_array($cast['source'], ['float64', 'bool_t', 'string'], true), 'Byte acquired a non-integer cast');
    }
}
foreach (array_keys(object($meta->types)) as $type) {
    $p = clone $core;
    $p->types = ['byte', 'int64'];
    if ($type !== 'int64') $p->types[] = $type;
    if ($type !== 'int64') rejects(fn() => prepareRuntime($meta, $p, $facts), 'numeric module');
    $p->modules = ['numeric'];
    $prepared = prepareRuntime($meta, $p, $facts);
    requireThat(count($prepared['types']) === ($type === 'int64' ? 2 : 3), 'Numeric activation emitted unrequested types');
    if ($type === 'uint8') requireThat(hasType($prepared, 'byte') && hasType($prepared, 'uint8'), 'Byte was aliased to uint8');
}
$p = clone $core; $p->modules = ['numeric', 'numeric'];
rejects(fn() => prepareRuntime($meta, $p, $facts), 'Duplicate module');
$p = clone $core; $p->modules = ['unknown'];
rejects(fn() => prepareRuntime($meta, $p, $facts), 'Unknown scalar module');
foreach (['float16', 'float32', 'float128', 'char8'] as $type) {
    $p = clone $core; $p->types[] = $type; $p->modules = ['numeric'];
    rejects(fn() => prepareRuntime($meta, $p, $facts), 'Unknown published');
}
foreach ([['add', ['byte', 'byte']], ['subtract', ['byte', 'int64']], ['multiply', ['int64', 'byte']],
    ['positive', ['byte']], ['prefix_increment', ['byte']], ['less', ['byte', 'byte']],
    ['bit_and', ['byte', 'int64']], ['shift_left', ['int64', 'byte']], ['add', ['float64', 'byte']]] as [$operation, $operands]) {
    $p = clone $core; $p->operators = [(object)['operation' => $operation, 'operands' => $operands]];
    rejects(fn() => prepareRuntime($meta, $p, $facts), 'Byte supports only');
}
$before = render($plan);
requireThat(str_contains($before['public/scpp/integers.hpp'], 'class byte final'), 'No byte public wrapper');
requireThat(!str_contains($before['public/scpp/integers.hpp'], 'class int32'), 'Optional width leaked into core');
echo "Numeric module and distinct byte contracts passed\n";
