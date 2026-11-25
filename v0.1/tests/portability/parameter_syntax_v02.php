<?php
// Conversion-to-parser proof: parameter types precede names across declaration owners.
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
require_once __DIR__ . '/../../compiler/my-try/boot.php';

$source = <<<'PHP'
<?php
class Row { public int $value = 1; }
interface Reader { public function accept(?Row $row = null): void; }
trait Inputs {
    public function containers(array $rows /** vector<Row> */, array $names /** hash<int> */): void {}
}
class Example {
    use Inputs;
    public function __construct(public array $rows /** vector<Row> */, public Storage $records /** Storage<Row> */, public ?Row $selected = null) {}
    public function accept(?Row $row = null): void {}
    public function scalar(int $number, string $text, bool $enabled = false): void {}
    public static function records(Storage $rows /** Storage<Row> */, ?Storage $optional /** Storage<Row> */ = null): void {}
    public function objects(\SplObjectStorage $rows /** hash<int, shared<Row>> */): void {}
}
PHP;
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$converted = $converter->convert($source, 'parameters.php');
$directory = $argv[1];
mkdir($directory, 0777, true);
file_put_contents($directory . '/parameters.phs', $converted);
scpp\compiler\Compiler_Lifecycle::configure_runtime(scpp\compiler\Runtime_Catalog_Loader::load(
    __DIR__ . '/../../runtime/metadata/modules', ['compiler']));
$compiler = new scpp\compiler\Compiler();
$compiler->init([$directory]);
$compiler->sync([]);
echo "Converted method, trait, interface and constructor parameters parse in v0.2\n";
