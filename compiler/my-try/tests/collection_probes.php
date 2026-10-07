<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function collection_probe_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/collection_probes', '/collection_probes', '/collection_probes');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

$fixtures = [
	'strings' => ['if (!empty("")) { return 1; } if (empty("0")) { return 2; } return 0;', false],
	'collections' => ['$a vector<int> = []; $b hash<int> = {x: 0}; if (!empty($a)) { return 1; } if (empty($b)) { return 2; } if (!isset($a)) { return 3; } return 0;', false],
	'keyed' => ['$a hash<string> = {blank: "", zero: "0"}; $v vector<vector<int>> = [[], [1]]; if (!empty($a["blank"])) { return 1; } if (empty($a["zero"])) { return 2; } if (!empty($v[0])) { return 3; } if (empty($v[1])) { return 4; } return 0;', false],
	'missing_typed' => ['$a hash<string> = {}; $b = empty($a["missing"]); return 1;', true],
];
foreach (['mixed', 'dynamic'] as $family) {
	$fixtures[$family . '_collections'] = ['$a ' . $family . ' = {}; $b ' . $family . ' = {x: null}; $s ' . $family . ' = ""; if (!empty($a)) { return 1; } if (empty($b)) { return 2; } if (!empty($s)) { return 3; } return 0;', false];
	foreach (['null', 'false', '0', '1.5'] as $i => $value) {
		$fixtures[$family . '_invalid_' . $i] = ['$a ' . $family . ' = ' . $value . '; $b = empty($a); return 1;', true];
	}
	foreach (['missing' => '{}', 'null' => '{x: null}', 'scalar' => '{x: 0}'] as $name => $value) {
		$fixtures[$family . '_key_' . $name] = ['$a ' . $family . ' = ' . $value . '; $b = empty($a["x"]); return 1;', true];
	}
	$fixtures[$family . '_lazy'] = ['class Log { static int $n = 0; } function key(): string { Log::$n = Log::$n + 1; return "x"; } $a ' . $family . ' = {}; $b = empty($a["missing"][key()]); return 1;', true];
	$fixtures[$family . '_bad_key'] = ['$a ' . $family . ' = {}; $key mixed = true; $b = empty($a[$key]); return 1;', true];
}
foreach (['struct', 'class'] as $kind) {
	$type = $kind === 'struct' ? 'C' : 'value<C>';
	$fixtures[$kind . '_observations'] = [$kind . ' C { unique<int> $owned; } $v ' . $type . '; $a ?' . $type . '; if (!$v) { return 1; } if (!isset($v)) { return 2; } if ($a) { return 3; } if (isset($a)) { return 4; } return 0;', false];
	$fixtures[$kind . '_present'] = [$kind . ' C { int $n = 0; } function once(int &$n): ?' . $type . ' { $n++; $v ' . $type . '; return $v; } $n = 0; if (!once($n)) { return 1; } $a = isset(once($n)); if (!$a) { return 2; } if ($n !== 2) { return 3; } $v ' . $type . '; $p ?' . $type . ' = $v; $i = 0; while ($p) { $i++; $p = null; } return $i === 1 ? 0 : 4;', false];
}
$results = [];
foreach ($fixtures as $name => [$source, $throws]) {
	$output = collection_probe_source($source);
	if ($throws) {
		foreach (Model::$cpp_files as $artifact) {
			if ($artifact->identity === 'entry') {
				$check = str_ends_with($name, '_lazy') ? ' && record_Log::field_n.native_value() == 0' : '';
				$instrumented = str_replace('int main()', 'int main() try', $artifact->text)
					. 'catch (const scpp::runtime_error &e) { return e.code() == "invalid_empty_operand"' . $check . ' ? 0 : 2; }' . "\n";
				$output = str_replace($artifact->text, $instrumented, $output);
			}
		}
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 0];
	}
}
foreach (['null', 'false', '0', '0.0', '$v'] as $expression) {
	foreach (['?int', 'result<int>', 'shared<int>', 'unique<int>'] as $type) {
		$initial = $type === 'result<int>' ? '0' : 'null';
		try { collection_probe_source('$v ' . $type . ' = ' . $initial . '; $b = empty(' . $expression . ');'); throw new \LogicException('Unsupported empty accepted'); }
		catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'empty requires')) { throw $error; } }
	}
}
foreach (['struct C {} $v C;', 'class C {} $v value<C>;', '$v vector<int> = [];'] as $prefix) {
	$expression = str_starts_with($prefix, '$v vector') ? '$v[0]' : '$v';
	try { collection_probe_source($prefix . ' $b = empty(' . $expression . ');'); throw new \LogicException('Unsupported empty accepted'); }
	catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'empty requires')) { throw $error; } }
}
foreach (['$v ?string = "";', '$v nullable<vector<int>>;', '$v shared<vector<int>>;', '$v result<string> = "";', '$v result_or_bool<string> = "";'] as $prefix) {
	try { collection_probe_source($prefix . ' $b = empty($v);'); throw new \LogicException('Wrapper delegated emptiness'); }
	catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'empty requires')) { throw $error; } }
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "collection probes: PASS\n";
