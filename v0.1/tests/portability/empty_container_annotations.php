<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$map = require __DIR__ . '/../../tools/php_portability/function_map.php';
foreach (['v0.1', 'v0.2'] as $target) {
    $converter = new scpp\portability\Converter($map, $target);
    $converted = $converter->convert('<?php $target->items = /** vector<int> */ []; $target->names = /** hash<string> */ [];', 'empty.php');
    $expected = $target === 'v0.1' ? ['new vector<int>()', 'new hash<string>()'] : ['vector<int>::[]', 'hash<string>::[]'];
    foreach ($expected as $text) {
        if (!str_contains($converted, $text)) { throw new RuntimeException('Missing construction: ' . $text); }
    }
    foreach ([
        '$items /** vector<int> */ = []; $items[] = 3;',
        '$rows /** vector<vector<int>> */ = [[], []];',
        '$rows /** vector<vector<int>> */ = ([([]), []]);',
        '$rows /** hash<vector<int>> */ = ["a" => []];',
        'class Box { public array $items /** vector<int> */ = []; public function use_items(array $values /** vector<int> */ = []): void {} }',
    ] as $source) { $converter->convert('<?php ' . $source, 'valid.php'); }
    foreach (['/** vector<int> */ [1]', '/** int */ []', '/** Storage<Row> */ []', '/** hash<int, shared<Row>> */ []', '/** vector< */ []'] as $expression) {
        try { $converter->convert('<?php $target->items = ' . $expression . ';', 'invalid.php'); }
        catch (RuntimeException $error) { continue; }
        throw new RuntimeException('Accepted unsupported empty-literal annotation: ' . $expression);
    }
    foreach (['$items = [];', '$target->items = [];', '$target->use_items([]);', 'return [];',
        '$rows /** vector<int> */ = [$target->read([])];',
    ] as $source) {
        try { $converter->convert('<?php ' . $source, 'untyped.php'); }
        catch (RuntimeException $error) {
            if (!str_contains($error->getMessage(), 'empty container expression requires')) { throw $error; }
            continue;
        }
        throw new RuntimeException('Accepted empty expression without explicit type intent: ' . $source);
    }
}
echo "Empty container annotations: both routes, declaration/nested intent and untyped rejection passed\n";
