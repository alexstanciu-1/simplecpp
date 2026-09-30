<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$converted = $converter->convert('<?php $target->items = /** vector<int> */ []; $target->names = /** hash<string> */ [];', 'empty.php');
foreach (['new vector<int>()', 'new hash<string>()'] as $expected) {
    if (!str_contains($converted, $expected)) { throw new RuntimeException('Missing construction: ' . $expected); }
}
foreach (['/** vector<int> */ [1]', '/** int */ []', '/** Storage<Row> */ []', '/** hash<int, shared<Row>> */ []', '/** vector< */ []'] as $expression) {
    try { $converter->convert('<?php $target->items = ' . $expression . ';', 'invalid.php'); }
    catch (RuntimeException $error) { continue; }
    throw new RuntimeException('Accepted unsupported empty-literal annotation: ' . $expression);
}
echo "Empty container annotations: explicit construction and unsupported forms rejected\n";
