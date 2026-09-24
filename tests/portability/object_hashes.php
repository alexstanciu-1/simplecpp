<?php
// PHP behavior and real hash type lowering only; no native compilation.
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
require_once __DIR__ . '/../../generators/php/src/Support/S2SException.php';
require_once __DIR__ . '/../../generators/php/src/Lowering/TypeMapper.php';
function ensure(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$source = <<<'SOURCE'
<?php
namespace identity_proof;
final class Key { public string $name = 'same'; }
final class Row { public int $number = 0; }
final class Index {
    public \SplObjectStorage $rows /** hash<Row, shared<Key>> */;
    public function build(Key $first, Key $second): \SplObjectStorage /** hash<Row, shared<Key>> */ {
        $rows /** hash<Row, shared<Key>> */ = new \SplObjectStorage /** hash<Row, shared<Key>> */();
        $left = new Row(); $left->number = 7;
        $right = new Row(); $right->number = 9;
        $rows[$first] = $left; $rows[$second] = $right;
        return $rows;
    }
}
$first = new Key(); $second = new Key();
$alias = $first;
$index = new Index();
$index->rows = $index->build($first, $second);
$first->name = 'changed';
echo $index->rows[$alias]->number, ':', $index->rows[$second]->number, "\n";
$retained = $index->rows[$first];
$replacement = new Row(); $replacement->number = 11;
$index->rows[$first] = $replacement;
unset($index->rows[$second]);
echo $retained->number, ':', $index->rows[$alias]->number, ':', isset($index->rows[$second]) ? 'bad' : 'removed', "\n";
SOURCE;
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$out = $converter->convert($source, 'identity.php');
ensure(!str_contains($out, 'SplObjectStorage'), 'PHP carrier leaked into native source');
ensure(str_contains($out, 'public $rows hash<Row, shared<Key>>;'), 'Missing typed property');
ensure(str_contains($out, '$rows hash<Row, shared<Key>> = [];'), 'Missing typed initialization');
ensure(str_contains($out, '): hash<Row, shared<Key>>'), 'Missing typed return');
ob_start(); eval(substr($source, 5)); $trace = ob_get_clean();
ensure($trace === "7:9\n7:11:removed\n", 'Object identity changed: ' . $trace);
$mapper = new Scpp\S2S\Lowering\TypeMapper();
$mapped = $mapper->mapDeclaredType('hash<Row, shared<Key>>');
ensure($mapped === 'hash_t<shared_p<Row>, shared_p<Key>>', 'Unexpected native hash mapping: ' . $mapped);
ensure($mapper->mapDeclaredType('hash<int, shared<Key>>') === 'hash_t<int_t<>, shared_p<Key>>', 'Wrong scalar-value map');
foreach ([
    'class Bad { public array $rows /** hash<Row, shared<Key>> */; }',
    'class Bad { public \\SplObjectStorage $rows; }',
    'class Bad { public \\SplObjectStorage $rows /** hash<Row, string> */; }',
    '$rows = new \\SplObjectStorage();',
    '$rows /** hash<Row, shared<int>> */ = new \\SplObjectStorage /** hash<Row, shared<int>> */();',
    '$rows /** hash<Row, shared<Key>> */ = new \\SplObjectStorage /** hash<Row, shared<Key>> */(4);',
] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad.php'); }
    catch (RuntimeException $error) { ensure(str_contains($error->getMessage(), 'bad.php'), 'Missing location'); continue; }
    throw new RuntimeException('Accepted invalid object hash: ' . $body);
}
echo "Object hashes: identity, replacement, removal, typed boundaries and native type mapping passed\n";
