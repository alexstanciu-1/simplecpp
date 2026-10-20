<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function memory_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/memory', '/memory', '/memory');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
}

$cases = [
	'usage' => <<<'PHS'
echo memory_get_usage(), "|", memory_get_usage(false), "|", memory_get_usage(true), "|";
echo memory_get_peak_usage(), "|", memory_get_peak_usage(false), "|", memory_get_peak_usage(true);
return 0;
PHS,
	'evaluation' => <<<'PHS'
function flag(int &$calls): bool { $calls++; return true; }
$calls = 0;
$current int = memory_get_usage(flag($calls));
$peak int = memory_get_peak_usage(flag($calls));
if ($current < 0 || $peak < 0) { return 1; }
echo $calls;
return 0;
PHS,
	'shadow' => 'function memory_get_usage(int $value): int { return $value; } return memory_get_usage(0);',
];
foreach ($cases as $name => $source) {
	memory_compile($source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}
foreach (['memory_get_usage(1);', 'memory_get_peak_usage("true");',
	'memory_get_usage(true, false);', 'memory_get_peak_usage(true, false);',
	'hash_string("abc");', 'stable_hash_string_u64("abc");', 'stable_hash_bytes_u64("abc");'] as $source) {
	$failed = false;
	try { memory_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid/held utility call emitted: ' . $source);
	}
}
Compiler_Lifecycle::reset();
$metadata = Model::$runtime_metadata;
$original = Model::$runtime_callables->function_definition('core.memory_get_usage.0');
Compiler_Lifecycle::reset_syntax();
if ($metadata !== Model::$runtime_metadata || $original === Model::$runtime_callables->function_definition('core.memory_get_usage.0')) {
	throw new \LogicException('Memory catalog reset did not preserve metadata with fresh identities');
}
echo "Runtime memory passed\n";
