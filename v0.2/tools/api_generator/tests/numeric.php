<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/contracts.php';

$core = decode(readFile($root . '/runtime/metadata/publication.json'), 'core');
$meta = decode($metadata, 'metadata');
$plan = prepareRuntime($meta, $core, $facts);
requireThat(array_column($plan['types'], 'name') === ['uint8', 'int64'], 'Core integer/byte surface grew');
requireThat(hasValue($plan, 'float64'), 'Core float64 missing');
foreach ($plan['conversions'] as $cast) {
    if ($cast['target'] === 'uint8' || $cast['source'] === 'uint8') {
        requireThat(!in_array($cast['target'], ['string'], true)
            && !in_array($cast['source'], ['string'], true), 'Byte acquired a deferred text cast');
    }
}
foreach (['float64', 'bool_t'] as $type) {
    requireThat(hasCast($plan, 'uint8', $type) && hasCast($plan, $type, 'uint8'), 'Missing numeric family conversion');
}
foreach (array_keys(object($meta->types)) as $type) {
    $p = clone $core;
    $p->types = ['byte', 'int64'];
    if ($type !== 'int64') $p->types[] = $type;
    if (!in_array($type, ['int64', 'uint8'], true)) rejects(fn() => prepareRuntime($meta, $p, $facts), 'numeric module');
    $p->modules = ['numeric'];
    $prepared = prepareRuntime($meta, $p, $facts);
    requireThat(count($prepared['types']) === (in_array($type, ['int64', 'uint8'], true) ? 2 : 3), 'Numeric activation emitted unrequested types');
    if ($type === 'uint8') requireThat(!hasType($prepared, 'byte') && hasType($prepared, 'uint8') && $prepared['aliases']->byte === 'uint8', 'Byte alias lost');
}
$p = clone $core; $p->modules = ['numeric', 'numeric'];
rejects(fn() => prepareRuntime($meta, $p, $facts), 'Duplicate module');
$p = clone $core; $p->modules = ['unknown'];
rejects(fn() => prepareRuntime($meta, $p, $facts), 'Unknown scalar module');
foreach (['float16', 'float32', 'float128', 'char8'] as $type) {
    $p = clone $core; $p->types[] = $type; $p->modules = ['numeric'];
    rejects(fn() => prepareRuntime($meta, $p, $facts), 'Unknown published');
}
$both = decode(encode($core), 'core');
$both->types[] = 'uint8';
foreach ($core->operators as $request) {
    if (in_array('byte', $request->operands, true)) {
        $copy = clone $request;
        $copy->operands = array_map(static fn($name) => $name === 'byte' ? 'uint8' : $name, $request->operands);
        $both->operators[] = $copy;
    }
}
requireThat(encode(prepareRuntime($meta, $both, $facts)) === encode($plan), 'Alias publication created duplicate definitions');
$before = render($plan);
requireThat(str_contains($before['public/scpp/integers.hpp'], 'using uint8 = __UINT8_TYPE__') && str_contains($before['public/scpp/integers.hpp'], 'using byte = uint8;'), 'No canonical uint8 and byte alias');
requireThat(!str_contains($before['public/scpp/integers.hpp'], 'using int32 ='), 'Optional width leaked into core');
echo "Numeric module and canonical byte alias contracts passed\n";

// Native arithmetic is builtin; the old operator bridge is retired.
requireThat(!isset($before['public/scpp/native_bridge.hpp']), 'Retired native bridge emitted');
requireThat(!str_contains($before['public/scpp/numeric.hpp'], 'operator'), 'Scalar overloads survived alias migration');
