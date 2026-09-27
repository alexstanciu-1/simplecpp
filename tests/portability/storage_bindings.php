<?php
// Focused executable-PHP and structural lowering proof; deliberately no native build.
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
require_once __DIR__ . '/../../compiler/my-try/helpers/storage_abstract.php';
require_once __DIR__ . '/../../compiler/my-try/helpers/storage.php';
require_once __DIR__ . '/../../compiler/my-try/helpers/keyed_storage.php';
require_once __DIR__ . '/../../compiler/my-try/helpers/key_storage_list.php';

function ensure(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$source = <<<'SOURCE'
<?php
namespace scpp\compiler;
final class Binding_Row { public int $value = 0; }
final class Binding_Constructor {
    public function __construct(public Storage $items /** Storage<Binding_Row> */) {}
}
final class Binding_Root {
    public static Storage $rows /** Storage<Binding_Row> */;
    public Keyed_Storage $names /** Keyed_Storage<Binding_Row> */;
    public ?Storage $optional /** Storage<Binding_Row> */ = null;
    public function __construct() {
        $this->names = new Keyed_Storage /** Keyed_Storage<Binding_Row> */(4);
    }
    public function optional_alias(?Storage $rows /** Storage<Binding_Row> */ = null): ?Storage /** Storage<Binding_Row> */ { return $rows; }
    public function alias(Storage $rows /** Storage<Binding_Row> */): Storage /** Storage<Binding_Row> */ { return $rows; }
}
$rows /** Storage<Binding_Row> */ = new Storage(8);
$row = new Binding_Row();
$row->value = 7;
$rows[] = $row;
$root = new Binding_Root();
Binding_Root::$rows = $root->alias($rows);
$root->names['01'] = $rows[0];
$root->names['1'] = $row;
$root->names['01']->value = 9;
unset(Binding_Root::$rows[0]);
$position = $rows->append(new Binding_Row());
echo $root->names['1']->value, ':', $position, ':', isset($rows[0]) ? 'bad' : 'hole', "\n";
foreach ($root->names as $key => $record) { echo $key, ':', $record->value, "\n"; }
echo isset(Binding_Root::$rows[$position]) ? 'alias' : 'bad', ':', $row->value, "\n";
SOURCE;
$out = $converter->convert($source, 'storage.php');
foreach (['public static $rows Storage<Binding_Row>;', 'public $names Keyed_Storage<Binding_Row>;',
          'new Storage<Binding_Row>(8)', 'new Keyed_Storage<Binding_Row>(4)',
          'function alias($rows Storage<Binding_Row>): Storage<Binding_Row>',
          'nullable<Storage<Binding_Row>> = null', 'function optional_alias($rows nullable<Storage<Binding_Row>> = null): nullable<Storage<Binding_Row>>', 'function __construct($items Storage<Binding_Row>)', 'unset(Binding_Root::$rows[0])'] as $fragment) {
    ensure(str_contains($out, $fragment), 'Missing lowering: ' . $fragment);
}
ob_start();
eval(substr($source, 5));
$trace = ob_get_clean();
ensure($trace === "9:1:hole\n01:9\n1:9\nalias:9\n", 'Wrong shared collection behavior: ' . $trace);
// Both spellings must produce identical PHS, including constructor arguments.
$explicit = str_replace('new Storage(8)', 'new Storage /** Storage<Binding_Row> */(8)', $source);
ensure($out === $converter->convert($explicit, 'storage.php'), 'Destination reuse changed emitted PHS');
foreach ([
    '$names /** Keyed_Storage<Row> */ = new Keyed_Storage(4);',
    '$rows /** Storage<other\\Row> */ = /* initializer */ new Storage();',
] as $body) {
    $converted = $converter->convert('<?php ' . $body, 'reuse.php');
    ensure(str_contains($converted, 'new Keyed_Storage<Row>(4)') || str_contains($converted, 'new Storage<other\\Row>()'), 'Missing reused construction type');
}
// An explicit construction annotation retains precedence; target checks assignability.
$override = $converter->convert('<?php $rows /** Storage<Row> */ = new Storage /** Storage<Other> */();', 'explicit.php');
ensure(str_contains($override, 'new Storage<Other>()'), 'Explicit construction annotation was overwritten');
$bad = [
    'class Bad { public Storage $rows; }',
    'interface Bad { public function items(): Storage /** Storage<Row> */; }',
    'interface Bad { public function accept(Storage $items /** Storage<Row> */): void; }',
    'class Bad { public array $rows /** Storage<Row> */; }',
    'class Bad { public Storage $rows /** Keyed_Storage<Row> */; }',
    '$rows = new Storage();',
    '$rows /** Storage<Row> */ = new Keyed_Storage();',
    '$rows /** Storage<Row> */ = []; $other = new Storage();',
    '$rows /** Storage<Row> */; $rows = new Storage();',
    '$rows /** Storage<Row> */ = new Storage(); $other = new Storage();',
    '$rows /** Storage<Row> */ = new Storage(new Storage());',
    '$rows /** Storage<Row> */ = [new Storage()];',
    '$rows /** Storage<Row> */ = true ? new Storage() : null;',
    'class Bad { public Storage $rows /** Storage<Row> */; public function run(): void { $x = new Storage(); } }',
    '$rows = new Storage /** Keyed_Storage<Row> */();',
    '$rows /** Storage<int> */ = new Storage /** Storage<int> */();',
    '$rows /** Storage<vector<Row>> */ = new Storage /** Storage<vector<Row>> */();',
    '$rows /** Storage<Row, string> */ = new Storage /** Storage<Row, string> */();',
    '$rows /** vector<Storage> */ = [];',
];
foreach ($bad as $body) {
    try { $converter->convert('<?php ' . $body, 'bad.php'); }
    catch (RuntimeException $error) {
        ensure(str_contains($error->getMessage(), 'bad.php'), 'Missing rejection attribution');
        continue;
    }
    throw new RuntimeException('Accepted invalid collection boundary: ' . $body);
}
echo "Storage bindings: declarations, constructors, aliases, keys, holes and rejections passed\n";

// The duplicate-key wrapper has the same explicit annotation boundary, a distinct API.
$grouped = <<<'SOURCE'
<?php
namespace scpp\compiler;
final class Grouped_Row { public int $value = 0; }
final class Grouped_Root {
    public Key_Storage_List $groups /** Key_Storage_List<Grouped_Row> */;
    public function __construct() { $this->groups = new Key_Storage_List /** Key_Storage_List<Grouped_Row> */(); }
    public function alias(Key_Storage_List $groups /** Key_Storage_List<Grouped_Row> */): Key_Storage_List /** Key_Storage_List<Grouped_Row> */ { return $groups; }
}
$groups /** Key_Storage_List<Grouped_Row> */ = new Key_Storage_List();
$row = new Grouped_Row();
$groups->add('same', $row);
$groups->add('same', $row);
SOURCE;
$lowered = $converter->convert($grouped, 'grouped.php');
foreach (['public $groups Key_Storage_List<Grouped_Row>;', 'new Key_Storage_List<Grouped_Row>()', 'function alias($groups Key_Storage_List<Grouped_Row>): Key_Storage_List<Grouped_Row>'] as $fragment) {
    ensure(str_contains($lowered, $fragment), 'Missing grouped collection lowering: ' . $fragment);
}
eval(substr($grouped, 5));
ensure($groups->named('same') === [$row, $row], 'Repeated identity was deduplicated');
$copied = $groups->items();
array_pop($copied);
ensure(count($groups->items()) === 2, 'Snapshot leaked membership mutation');
foreach (['Key_Storage_List<int>', 'Key_Storage_List<Storage<Row>>', 'Key_Storage_List<Row, string>'] as $invalid) {
    try { $converter->convert('<?php $rows /** ' . $invalid . ' */ = new Key_Storage_List();', 'bad.php'); }
    catch (RuntimeException $expected) { continue; }
    throw new RuntimeException('Accepted invalid grouped collection: ' . $invalid);
}
echo "Key_Storage_List bindings: typed boundaries, duplicate identity and snapshots passed\n";
