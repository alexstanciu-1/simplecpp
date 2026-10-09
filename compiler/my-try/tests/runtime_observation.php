<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function observation_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

// Host-only concrete proof signatures; production count requires parameterized registration.
$schema = ['version' => 1, 'module' => 'observation', 'dependencies' => [], 'functions' => [], 'cpp' => []];
$types = [
	['name' => 'vector', 'arguments' => ['int']],
	['name' => 'vector', 'arguments' => [['name' => 'unique', 'arguments' => ['int']]]],
	'mixed', 'dynamic',
];
foreach ($types as $index => $type) {
	$id = 'observation.measure_' . $index;
	$schema['functions'][] = ['id' => $id, 'name' => 'measure',
		'parameters' => [['name' => 'input', 'type' => $type, 'passing' => 'observe']], 'returns' => 'int'];
	$schema['cpp'][] = ['id' => $id, 'symbol' => 'scpp::count', 'header' => 'operators/count/count.hpp'];
}
$schema['functions'][] = ['id' => 'observation.single', 'name' => 'inspect_unique',
	'parameters' => [['name' => 'input', 'type' => $types[1], 'passing' => 'observe']], 'returns' => 'int'];
$schema['cpp'][] = ['id' => 'observation.single', 'symbol' => 'scpp::count', 'header' => 'operators/count/count.hpp'];
$input = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) {
	$input->modules->add($module->name, $module);
}
$input->modules->add('observation', Runtime_Catalog_Reader::decode(json_encode($schema), 'observation.json'));
Compiler_Lifecycle::configure_runtime($input);

$invalid = $schema;
$invalid['functions'][0]['parameters'][] = ['name' => 'second', 'type' => 'int', 'passing' => 'value'];
$candidate = new runtime_catalog_input();
$candidate->modules->add('core', $input->modules['core']);
$candidate->modules->add('observation', Runtime_Catalog_Reader::decode(json_encode($invalid), 'invalid.json'));
$failed = false;
try { Compiler_Lifecycle::configure_runtime($candidate); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'one fixed readonly input'); }
observation_check($failed && Model::$runtime_metadata === $input, 'Invalid observation contract was published');

$invalid = $schema;
$invalid['functions'][1]['parameters'][0]['passing'] = 'value';
$candidate = new runtime_catalog_input();
$candidate->modules->add('core', $input->modules['core']);
$candidate->modules->add('observation', Runtime_Catalog_Reader::decode(json_encode($invalid), 'mixed-mode.json'));
$failed = false;
try { Compiler_Lifecycle::configure_runtime($candidate); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'cannot mix observation'); }
observation_check($failed && Model::$runtime_metadata === $input, 'Mixed consuming/observing overloads were published');

/** Exercise shared preparation and artifact lowering, not a test-only emitter. */
function observation_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/observation', '/observation', '/observation');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$cases = [
	'locals' => 'function inspect(const vector<int> &$v): int { return measure($v); } $v vector<int> = [1, 2]; $n = measure($v); $r = inspect($v); $first = $v[0]; if ($n !== 2 || $r !== 2 || $first !== 1) { return 1; } return 0;',
	'temporaries' => 'function produce(int &$calls): vector<int> { $calls++; return vector<int>::[1, 2, 3]; } $calls = 0; $n = measure(produce($calls)); $r = measure(vector<int>::[4]); if ($n !== 3 || $r !== 1 || $calls !== 1) { return 1; } return 0;',
	'fields' => 'class Bag { public vector<int> $items = [1, 2]; } function produce(int &$calls): Bag { $calls++; return new Bag(); } $bag = new Bag(); $n = measure($bag->items); $calls = 0; $r = measure(produce($calls)->items); if ($n !== 2 || $r !== 2 || $calls !== 1) { return 1; } return 0;',
	'noncopyable' => '$v vector<unique<int>>; $n = measure($v); $r = inspect_unique($v); $again = measure($v); if ($n !== 0 || $r !== 0 || $again !== 0) { return 1; } return 0;',
	'families' => '$m mixed = {a: 1, b: 2}; $d dynamic = [1, 2, 3]; $n = measure($m); $r = measure($d); if ($n !== 2 || $r !== 3) { return 1; } return 0;',
];
foreach ($cases as $name => $source) {
	$cpp = observation_compile($source);
	observation_check(str_contains($cpp, 'static_cast<const '), 'Observation did not lower to a const view');
	file_put_contents($argv[1] . '/' . $name . '.cpp', $cpp);
}
foreach ([
	'measure();', 'measure(1);', '$v vector<int>; measure($v, $v);',
	'$v vector<int>; $alias =& measure($v);',
	'function inspect(const vector<int> &$v): int { return 0; } inspect(vector<int>::[1]);',
] as $source) {
	$failed = false;
	try { observation_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	observation_check($failed && Model::$cpp_files->is_empty(), 'Invalid call published C++: ' . $source);
}
echo "Runtime observation passed\n";
