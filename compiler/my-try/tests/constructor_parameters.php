<?php

/* Constructor inputs use ordinary by-value admission before C++ emission. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

/** Require semantic rejection even for an unused constructor signature. */
function constructor_parameter_rejection(Compiler $compiler, array $paths): void
{
	$compiler->sync($paths);
	try
	{
		$compiler->prepare();
	}
	catch (\RuntimeException $error)
	{
		if (!str_contains($error->getMessage(), 'Abstract type cannot be stored or returned as an inline value'))
		{
			throw new \LogicException('Wrong constructor parameter diagnostic: ' . $error->getMessage());
		}
		if (!Model::$cpp_files->is_empty())
		{
			throw new \LogicException('Rejected constructor retained generated artifacts');
		}
		return;
	}
	throw new \LogicException('Abstract constructor parameter passed preparation');
}

$directory = sys_get_temp_dir() . '/constructor-parameters-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$results = [];
try
{
	foreach (['class', 'struct'] as $owner_kind)
	{
		foreach (['abstract struct A { abstract function score(): int; }' => 'A',
			'abstract class A { abstract function score(): int; }' => 'value<A>',
			'abstract struct A {}' => 'A'] as $declaration => $input_type)
		{
			foreach (['', $owner_kind . ' D extends C {}'] as $derived)
			{
				file_put_contents($path, $declaration . ' ' . $owner_kind . ' C { function __construct(' . $input_type . ' $input) {} } ' . $derived . ' return 0;');
				Compiler_Lifecycle::reset();
				$compiler = new Compiler();
				$compiler->init([$directory]);
				$compiler->select_entry('main', 'main.phs');
				constructor_parameter_rejection($compiler, []);
			}
		}
	}

	// An unchanged inherited signature must observe concrete-to-abstract input edits.
	foreach (['class', 'struct'] as $owner_kind)
	{
		$baseline = 'struct A { int $n = 9; } ' . $owner_kind . ' C { int $n; function __construct(A $input) { $this->n = $input->n; } } ' . $owner_kind . ' D extends C {} return 9;';
		file_put_contents($path, $baseline);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->select_entry('main', 'main.phs');
		$compiler->exec_cpp();
		$original = CPP_Output::encode(Model::$cpp_files);
		file_put_contents($path, str_replace('struct A {', 'abstract struct A {', $baseline));
		constructor_parameter_rejection($compiler, [$path]);
		constructor_parameter_rejection($compiler, []);
		file_put_contents($path, $baseline);
		$compiler->update_cpp([$path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original)
		{
			throw new \LogicException('Constructor input repair did not restore baseline artifacts');
		}
		file_put_contents($path, str_replace('return 9;', '$a = new A(); $d = new D($a); return $d->n;', $baseline));
		$compiler->update_cpp([$path]);
		if (isset($argv[1]))
		{
			$output = $argv[1] . '/' . $owner_kind . '_concrete_inherited.cpp';
			file_put_contents($output, native_record_export());
			$results[] = ['path' => $output, 'exit_code' => 9];
		}
	}

	foreach (['class', 'struct'] as $owner_kind)
	{
		file_put_contents($path, 'abstract class A { abstract function score(): int; } class B extends A { function score(): int { return 9; } } ' . $owner_kind . ' C { int $n; function __construct(A $input) { $this->n = $input->score(); } } ' . $owner_kind . ' D extends C {} $b = new B(); $d = new D($b); return $d->n;');
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->select_entry('main', 'main.phs');
		$compiler->exec_cpp();
		if (isset($argv[1]))
		{
			$output = $argv[1] . '/' . $owner_kind . '_abstract_handle.cpp';
			file_put_contents($output, native_record_export());
			$results[] = ['path' => $output, 'exit_code' => 9];
		}
	}
}
finally
{
	unlink($path);
	rmdir($directory);
}
if (isset($argv[1]))
{
	file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n");
}
echo "Constructor parameter admission: 12 rejections, two recovery sequences, four controls passed\n";
