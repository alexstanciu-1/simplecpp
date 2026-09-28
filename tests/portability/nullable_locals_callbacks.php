<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$map = require __DIR__ . '/../../tools/php_portability/function_map.php';
$converter = new scpp\portability\Converter($map);
$source = <<<'SOURCE'
<?php
namespace proof;
final class Row { public int $value = 0; }
$row = new Row();
$optional /** nullable<Row> */ = null;
$qualified /** nullable<\proof\Row> */ = null;
$optional = $row;
$action = function () use ($row): void { $row->value = 7; };
$value = static function (): int { return 3; };
$accept = function (Row $item): void { $item->value = 9; };
SOURCE;
$output = $converter->convert($source, 'callbacks.php');
foreach (['$optional nullable<Row> = null;', '$qualified nullable<\\proof\\Row> = null;', 'function () use ($row): void', 'static function (): int', 'function (Row $item): void'] as $expected) {
    if (!str_contains($output, $expected)) {
        throw new LogicException('Missing lowering: ' . $expected . "\n" . $output);
    }
}
foreach (['nullable<mixed>', 'nullable<void>', 'nullable<array>', 'nullable<nullable<Row>>'] as $type) {
    try {
        $converter->convert('<?php $value /** ' . $type . ' */ = null;', 'rejected.php');
        throw new LogicException('Unsupported local accepted: ' . $type);
    } catch (RuntimeException $expected) {}
}
foreach (['function ($x): void {}', 'function (int &$x): void {}', 'function (int $x, int $y): void {}', 'function () use (&$row): void {}'] as $closure) {
    try {
        $converter->convert('<?php $callback = ' . $closure . ';', 'rejected.php');
        throw new LogicException('Unsupported callback accepted: ' . $closure);
    } catch (RuntimeException $expected) {}
}
// The authoring surface remains executable PHP with ordinary captured object identity.
eval(substr($source, 5));
$action();
if ($optional !== $row || $row->value !== 7 || $value() !== 3) {
    throw new LogicException('PHP closure or nullable identity changed');
}
$accept($row);
if ($row->value !== 9) { throw new LogicException('Void callback did not run'); }
echo "Nullable named locals and zero/one-argument void/value callbacks: conversion, rejection and PHP behavior passed\n";
