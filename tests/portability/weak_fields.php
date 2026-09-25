<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
require_once __DIR__ . '/../../tools/php_portability/runtime/bootstrap.php';
require_once __DIR__ . '/../../generators/php/bin/bootstrap.php';
function ensure(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$source = <<<'SOURCE'
<?php
namespace weak_fields_proof;
final class Row { public int $number = 7; }
final class Observer {
    public Row $target /** weak<Row> */;
    public ?Row $parent /** weak<Row> */ = null;
    public function read(): Row {
        return object_cast(weakref_get($this->target), Row::class);
    }
}
$row = new Row();
$observer = new Observer();
$observer->target = $row;
$held = $observer->read();
$held->number = 9;
echo $held === $row ? 'same' : 'copy', ':', $row->number, ':', weakref_get($observer->parent) === null ? 'absent' : 'present', "\n";
SOURCE;
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$out = $converter->convert($source, 'weak.php');
ensure(str_contains($out, 'public $target weak<Row>;'), 'Required weak field missing');
ensure(str_contains($out, 'public $parent weak<Row> = null;'), 'Empty weak field missing');
ob_start();
eval(substr($source, 5));
ensure(ob_get_clean() === "same:9:absent\n", 'PHP identity facade changed behavior');
foreach ([
    'class Bad { public int $value /** weak<int> */; }',
    'class Bad { public Row $value /** weak<Other> */; }',
    'class Bad { public Row $value /** weak<vector<Row>> */; }',
    'class Bad { public Row $value /** weak<Row> */ = null; }',
    'class Bad { public ?Row $value /** weak<Row> */; }',
] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad.php'); }
    catch (RuntimeException $error) { continue; }
    throw new RuntimeException('Accepted malformed weak field: ' . $body);
}
// The current scope graph must keep these declarations weak in converted source.
foreach ([
    '03_parse/structures.php' => 'public $parent weak<scope> = null;',
    '03_parse/structures_specialization.php' => 'public $scope weak<scope>;',
    '04_analyze/collect/structures.php' => 'public $scope weak<scope>;',
] as $path => $declaration) {
    $converted = $converter->convert(file_get_contents(__DIR__ . '/../../compiler/my-try/' . $path), $path);
    ensure(str_contains($converted, $declaration), 'Scope link lost its weak annotation: ' . $path);
}
$scopeOutput = $converter->convert(file_get_contents(__DIR__ . '/../../compiler/my-try/03_parse/structures.php'), 'structures.php');
ensure(str_contains($scopeOutput, 'public $publication weak<scope> = null;'), 'File scope publication link is not weak');
$lookup = (new Scpp\S2S\Stan\StanDependencyResolver())->buildResolutionLookup([]);
ensure(isset($lookup['function|weakref_get']), 'STAN does not recognize the runtime weak acquisition primitive');
$result = (new Scpp\S2S\Transpiler())->transpile('/tmp/weak-fields-proof.phs', false, false, $out);
ensure($result->errors === [], 'Generator rejected weak fields');
$header = implode("\n", $result->headerLines);
$cpp = implode("\n", $result->sourceLines);
ensure(str_contains($header, 'weak_p<Row> target;'), 'Native target is not weak');
ensure(str_contains($header, 'weak_p<Row> parent'), 'Native parent is not weak');
ensure(str_contains($cpp, 'weakref_get('), 'Missing native acquisition');
ensure(!str_contains($header . $cpp, 'unsupported-'), 'Unsupported native placeholder');
echo "Weak fields: PHP identity facade, declaration rejection and native spelling passed; no native compilation\n";
