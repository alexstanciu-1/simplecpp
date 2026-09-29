<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';
use Scpp\S2S\Transpiler;
use Scpp\S2S\Analysis\FrontEndSymbolExtractor;
use Scpp\S2S\Stan\StanExpressionTypeResolver;

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$source = <<<'PHS'
namespace Example;
class Row { public int $id = 0; }
class Roots {
    public static $rows Storage<Row> = new Storage<Row>(2);
    public $named Keyed_Storage<Row> = new Keyed_Storage<Row>();
}
function rows(Storage<Row> $list): Storage<Row> { return $list; }
$a = new Storage<Row>(1); $b = new Keyed_Storage<Row>();
$a[] = new Row();
Roots::$rows = $a;
Roots::$rows[0]->id = 2;
$old Row = Roots::$rows[0];
$b["a\0b"] = $old;
foreach ($a as $key => $row) { $row->id = $key; }
PHS;
$result = (new Transpiler(phpProfile: 'strict'))->transpile('/tmp/storage-typing.phs', sourceOverride: $source);
check($result->errors === [], implode('; ', $result->errors));
$cpp = implode("\n", $result->headerLines) . implode("\n", $result->sourceLines);
check(!str_contains($cpp, '__scpp_collection_new_'), 'Parser placeholder leaked into lowering');
check(!str_contains($cpp, 'shared_p<::scpp::compiler::'), 'Collection itself was double wrapped');
check(str_contains($cpp, 'Roots::rows.read('), 'Static root must use a required read');
check(str_contains($cpp, 'std::string("a\000b", 3)'), 'Binary literal length lost');
check(str_contains($cpp, 'b.assign('), 'Keyed write must use collection assignment');

$extractor = new FrontEndSymbolExtractor();
$analysis = static function (string $source) use ($extractor): array {
    $file = $extractor->extract('/tmp/storage-stan.phs', $source);
    return (new StanExpressionTypeResolver())->analyzeWorkspaceExpressions([$extractor->summarize($file, $source)], []);
};
$prefix = 'class Row {} $rows Storage<Row> = new Storage<Row>(); $map Keyed_Storage<Row> = new Keyed_Storage<Row>(); $row Row = new Row();';
$valid = $analysis($prefix . '$rows->append($row); $map->add("x", $row); $rows->reserve(4); $map->is_empty();');
check($valid['call_site_diagnostics'] === [], 'Valid Storage method model rejected');
foreach (['$rows->append(1);', '$map->add(1, $row);', '$map->append($row);', '$rows->reserve(true);', '$rows->replace(0);'] as $bad) {
    $result = $analysis($prefix . $bad);
    check($result['call_site_diagnostics'] !== [], 'STAN failed to reject ' . $bad);
}
echo "PASS Storage source typing, constructor annotations, static roots and STAN method contracts\n";
