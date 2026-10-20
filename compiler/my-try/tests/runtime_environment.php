<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function environment_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/environment', '/environment', '/environment');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
}

$cases = [
	'environment' => <<<'PHS'
$text = "preserved";
if (take($text) = getenv("SCPP_ENV_PROBE_MISSING")) { return 1; }
echo $text, "|";
if (!take($text) = getenv("SCPP_ENV_PROBE_EMPTY")) { return 2; }
echo strlen($text), "|";
$present string = getenv("SCPP_ENV_PROBE_VALUE");
echo $present, "|";
$fallback string = getenv("SCPP_ENV_PROBE_MISSING") ?? "fallback";
$empty string = getenv("SCPP_ENV_PROBE_EMPTY") ?? "wrong";
echo $fallback, "|", strlen($empty);
return 0;
PHS,
	'shell' => <<<'PHS'
$args = cli_argv(); $command string = $args[1];
$text = "preserved";
if (!take($text) = shell_exec($command)) { return 1; }
echo strlen($text), ":", $text;
return 0;
PHS,
	'evaluation' => <<<'PHS'
function key(int &$calls): string { $calls++; return "SCPP_ENV_PROBE_EMPTY"; }
$calls = 0; $value string = getenv(key($calls)); echo $calls;
return 0;
PHS,
	'shadow' => 'function getenv(int $n): int { return $n; } function shell_exec(int $n): int { return $n; } return getenv(shell_exec(0));',
];
foreach ($cases as $name => $source) {
	environment_compile($source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}
foreach (['getenv();', 'getenv(1);', 'getenv("x", true);', 'shell_exec();', 'shell_exec(false);',
	'shell_exec("x", "y");', 'process_start("x");'] as $source) {
	$failed = false;
	try { environment_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid call emitted: ' . $source); }
}
echo "Runtime environment passed\n";
