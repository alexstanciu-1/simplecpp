<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$catalogs = dirname(__DIR__, 3) . '/runtime/metadata/modules';
$input = Runtime_Catalog_Loader::load($catalogs, ['compiler']);
Compiler_Lifecycle::configure_runtime($input);
// Validate factory metadata before publication, then recover with a fresh valid input.
foreach (['missing' => 'factory not found', 'return' => 'exact owner type',
	'instance' => 'owner-bound static factory', 'capability' => 'formation requirement'] as $bad => $diagnostic) {
	$candidate = Runtime_Catalog_Loader::load($catalogs, ['compiler']);
	$module = $candidate->modules['compiler'];
	$type = $module->types[0];
	$factory = $module->functions[0];
	if ($bad === 'missing') { $type->construction = 'absent'; }
	elseif ($bad === 'return') { $factory->return_type->name = 'vector'; }
	elseif ($bad === 'instance') { $factory->member->static_member = false; }
	else { $type->capabilities[] = generic_contract::shared_record; }
	$failed = false;
	try { Compiler_Lifecycle::configure_runtime($candidate); }
	catch (\RuntimeException $error) {
		$failed = true;
		if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; }
	}
	if (!$failed) { throw new \LogicException('Admitted invalid provider contract: ' . $bad); }
	Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($catalogs, ['compiler']));
}
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'class Row {public int $n=1;} $s=new Storage<Row>();$r=new Row();$s[]=$r;echo count($s);';
$compiler = new Compiler();
$compiler->init([$directory]);
$cases = [
	'Storage<int>' => [' $s=new Storage<int>();', 'shared_record'],
	'Storage<value<Row>>' => ['class Row {public int $n=1;} $s=new Storage<value<Row>>();', 'shared_record'],
	'Storage<shared<Row>>' => ['class Row {public int $n=1;} $s=new Storage<shared<Row>>();', 'repeats the declaration default storage'],
	'capacity' => ['class Row {} $s=new Storage<Row>("bad");', 'runtime call requires'],
	'record' => ['class Row {} $s=new Storage<Row>();$s[]=7;', ''],
	'key' => ['class Row {} $s=new Storage<Row>();$r=$s["bad"];', ''],
	'keyed append' => ['class Row {} $s=new Keyed_Storage<Row>();$r=new Row();$s[]=$r;', ''],
	'no provider constructor' => ['$s=new vector<int>();', 'new requires'],
];
foreach ($cases as $label => $case) {
	file_put_contents($path, $case[0]);
	$failed = false;
	try { $compiler->sync([$path]); $compiler->prepare(); }
	catch (\RuntimeException $error) {
		$failed = true;
		if (($case[1] !== '') && !str_contains($error->getMessage(), $case[1])) { throw $error; }
	}
	if (!$failed) { throw new \LogicException('Admitted invalid Storage contract: ' . $label); }
	file_put_contents($path, $valid);
	$compiler->sync([$path]);
	$compiler->prepare();
	$compiler->cpp();
}
echo "Storage admission, recovery and metadata rejection passed\n";
