<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function cli_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/cli', '/cli', '/cli');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
}

cli_compile(<<<'PHS'
function arguments(): mixed { return cli_argv(); }
class Reader { public static function argument_count(): int { return cli_argc(); } }
$args mixed = arguments();
$alias mixed = cli_args();
echo Reader::argument_count(), "|", count($args), "|", count($alias), "\n";
foreach ($args as $arg) { $text string = $arg; echo strlen($text), ":", $text, "\n"; }
$args[0] = "changed";
$fresh mixed = cli_argv();
$original string = $fresh[0];
$alias_original string = $alias[0];
echo strlen($original), ":", $original, "\n", strlen($alias_original), ":", $alias_original, "\n";
return 0;
PHS);
$text = CPP_Probe::text(Model::$cpp_files);
if (substr_count($text, 'scpp::php::set_cli_args(argc, argv);') !== 1) {
	throw new \LogicException('Expected exactly one CLI initialization in artifact bundle');
}
file_put_contents($argv[1] . '/arguments.cpp', $text);
cli_compile('function cli_argc(int $n): int { return $n; } return cli_argc(0);');
file_put_contents($argv[1] . '/shadow.cpp', CPP_Probe::text(Model::$cpp_files));
foreach (['cli_argc(1);', 'cli_argv("x");', 'cli_args(false);', 'set_cli_args();', 'process_start("x");'] as $source) {
	$failed = false;
	try { cli_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid/internal CLI API emitted: ' . $source);
	}
}
echo "Runtime CLI passed\n";
