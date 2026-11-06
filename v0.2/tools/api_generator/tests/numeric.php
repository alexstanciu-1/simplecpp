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
        requireThat(!in_array($cast['target'], ['float64', 'bool_t', 'string'], true)
            && !in_array($cast['source'], ['float64', 'bool_t', 'string'], true), 'Byte acquired a non-integer cast');
    }
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
requireThat(str_contains($before['public/scpp/integers.hpp'], 'class uint8 final') && str_contains($before['public/scpp/integers.hpp'], 'using byte = uint8;'), 'No canonical uint8 and byte alias');
requireThat(!str_contains($before['public/scpp/integers.hpp'], 'class int32'), 'Optional width leaked into core');
echo "Numeric module and canonical byte alias contracts passed\n";

// Native operator publication is explicit; constructors remain core.
$withoutBridge = decode(encode($core), 'core');
unset($withoutBridge->headers->native_bridge);
rejects(fn() => prepareRuntime($meta, $withoutBridge, $facts), 'native_bridge header');
$withoutBridge->operators = array_values(array_filter($withoutBridge->operators,
    fn(object $request): bool => count(array_filter($request->operands, fn(mixed $operand): bool => is_object($operand))) === 0));
$bridgeFree = render(prepareRuntime($meta, $withoutBridge, $facts));
requireThat(!isset($bridgeFree['public/scpp/native_bridge.hpp']), 'Bridge emitted without explicit header request');
requireThat(isset($before['public/scpp/native_bridge.hpp']), 'Explicit bridge request was ignored');
requireThat(str_contains($bridgeFree['public/scpp/integers.hpp'], 'explicit int64(int value)'), 'Bridge removal disabled native construction');
echo "Optional native bridge contracts passed\n";
