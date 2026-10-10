<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function protocol_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$directory = $argv[1];
$input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['core']);
$schema = json_decode(file_get_contents(__DIR__ . '/runtime_protocols.json'), true, 512, JSON_THROW_ON_ERROR);
$input->modules->add('protocol_proof', Runtime_Catalog_Reader::decode(json_encode($schema), 'protocol-proof.json'));
Compiler_Lifecycle::configure_runtime($input);

// Neither opt-in implies the other; installation is independent of source compilation.
foreach (['indexing', 'iteration'] as $omitted) {
	$independent = $schema;
	unset($independent['types'][0][$omitted]);
	$candidate = new runtime_catalog_input();
	$candidate->modules->add('core', $input->modules['core']);
	$candidate->modules->add('protocol_proof', Runtime_Catalog_Reader::decode(json_encode($independent), 'independent.json'));
	$types = Type_Definition_Registration::install(new Type_Registry());
	Runtime_Type_Registration::install($candidate, $types);
	$definition = $types->definition('tray');
	$indexing = Indexing_Registration::install($types, $candidate);
	$iteration = Iteration_Registration::install($types, $candidate);
	protocol_check(($indexing->lookup($definition) === null) === ($omitted === 'indexing')
		&& ($iteration->lookup($definition) === null) === ($omitted === 'iteration'), 'Protocol opt-ins were coupled');
}

$invalids = [];
$bad = $schema; $bad['types'][0]['iteration']['value'] = ['parameter'=>'Unknown']; $invalids[] = [$bad, 'Unknown runtime protocol parameter'];
$bad = $schema; $bad['types'][0]['iteration']['key'] = 'void'; $invalids[] = [$bad, 'requires a storable type'];
$bad = $schema; unset($bad['types'][0]['iteration']['key']); $invalids[] = [$bad, 'Missing runtime iteration field'];
$bad = $schema; $bad['types'][0]['indexing']['write'] = false; $invalids[] = [$bad, 'append requires indexed write'];
$bad = $schema; $bad['types'][0]['indexing']['append'] = 'invented'; $invalids[] = [$bad, 'Unknown runtime indexing append'];
$bad = $schema; $bad['types'][0]['indexing']['path'] = ['parameter'=>'Unknown']; $invalids[] = [$bad, 'Unknown runtime protocol parameter'];
$bad = $schema; $bad['types'][0]['indexing']['extra'] = true; $invalids[] = [$bad, 'Unknown runtime indexing field'];
foreach ($invalids as [$bad, $message]) {
	$failed = false;
	try {
		$candidate = new runtime_catalog_input();
		$candidate->modules->add('core', $input->modules['core']);
		$candidate->modules->add('protocol_proof', Runtime_Catalog_Reader::decode(json_encode($bad), 'bad.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	}
	catch (\Throwable $error) { $failed = str_contains($error->getMessage(), $message); }
	protocol_check($failed && Model::$runtime_metadata === $input, 'Invalid metadata published: ' . $message);
}

function protocol_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/runtime-protocols', '/runtime-protocols', '/runtime-protocols');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	protocol_check($text === CPP_Probe::text(Model::$cpp_files), 'No-op changed protocol output');
	return $text;
}

$source = <<<'PHS'
$numbers = make_tray(3);
$numbers[] = 5;
$copy = $numbers;
$copy[0] = 9;
$original = $numbers[0];
$changed = $copy[0];
$sum = 0;
foreach ($numbers as $key => $value) { $sum += $key + $value; }
$strings = make_tray("ok");
$strings[] = "yes";
$length = 0;
foreach ($strings as $value) { $size = strlen($value); $length += $size; }
if ($original !== 3 || $changed !== 9 || $sum !== 9 || $length !== 5) { return 1; }
return 0;
PHS;
file_put_contents($directory . '/protocols.cpp', protocol_compile($source));
$old_definition = Model::$type_catalog->definition('tray');
protocol_check(Model::$indexing_contracts->lookup($old_definition) !== null
	&& Model::$iteration_contracts->lookup($old_definition) !== null, 'Missing provider protocols');
Compiler_Lifecycle::reset_syntax();
protocol_check(Model::$runtime_metadata === $input && Model::$type_catalog->definition('tray') !== $old_definition
	&& Model::$indexing_contracts->lookup($old_definition) === null, 'Protocol reset reused old canonical identity');

foreach ([
	'$v = make_tray(1); $v["wrong"] = 2;',
	'$v = make_tray(1); $v[] = "wrong";',
	'$v = make_tray(1); $present = isset($v[0]);',
	'$v = make_tray(1); unset($v[0]);',
	'$v tray<int> = [1, 2];',
] as $invalid) {
	$failed = false;
	try { protocol_compile($invalid); }
	catch (\RuntimeException $error) { $failed = true; }
	protocol_check($failed && Model::$cpp_files->is_empty(), 'Unregistered operation accepted: ' . $invalid);
}
protocol_check(protocol_compile($source) !== '', 'Protocol preparation did not recover');
echo "Runtime protocol metadata passed\n";
