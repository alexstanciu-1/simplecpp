<?php

/* Void ownership admission is shared by all source type application sites. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

/** Require a source rejection before emission and preserve retry semantics. */
function ownership_target_rejection(Compiler $compiler, array $paths): void
{
	$compiler->sync($paths);
	try
	{
		$compiler->prepare();
	}
	catch (\RuntimeException $error)
	{
		if (!str_contains($error->getMessage(), 'non-void target'))
		{
			throw new \LogicException('Wrong ownership target diagnostic: ' . $error->getMessage());
		}
		if (!Model::$cpp_files->is_empty())
		{
			throw new \LogicException('Invalid ownership target retained C++ artifacts');
		}
		return;
	}
	throw new \LogicException('Void ownership target passed preparation');
}

$directory = sys_get_temp_dir() . '/ownership-targets-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$results = [];
try
{
	foreach (['shared', 'unique'] as $family)
	{
		$type = $family . '<void>';
		foreach (['$p ' . $type . '; return 0;',
			'class C { ' . $type . ' $p; } return 0;',
			'function f(' . $type . ' $p): void {} return 0;',
			'function f(): ' . $type . ' { $p ' . $type . '; return $p; } return 0;',
			'$p vector<' . $type . '>; return 0;'] as $source)
		{
			file_put_contents($path, $source);
			Compiler_Lifecycle::reset();
			$compiler = new Compiler();
			$compiler->init([$directory]);
			$compiler->select_entry('main', 'main.phs');
			ownership_target_rejection($compiler, []);
		}

		$baseline = '$p ' . $family . '<int>; return 0;';
		file_put_contents($path, $baseline);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->select_entry('main', 'main.phs');
		$compiler->exec_cpp();
		$original = CPP_Output::encode(Model::$cpp_files);
		file_put_contents($path, '$p ' . $type . '; return 0;');
		ownership_target_rejection($compiler, [$path]);
		ownership_target_rejection($compiler, []);
		file_put_contents($path, $baseline);
		$compiler->update_cpp([$path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original)
		{
			throw new \LogicException('Ownership target repair did not restore baseline artifacts');
		}
	}

	foreach (['shared' => 'struct S { int $n = 9; } $p shared<S> = new S(); return $p->n;',
		'unique' => 'struct S { int $n = 9; } $p unique<S> = new S(); return $p->n;',
		'weak_void' => '$p weak<void>; return 0;'] as $name => $source)
	{
		file_put_contents($path, $source);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->select_entry('main', 'main.phs');
		$compiler->exec_cpp();
		if (isset($argv[1]))
		{
			$output = $argv[1] . '/' . $name . '.cpp';
			file_put_contents($output, native_record_export());
			$results[] = ['path' => $output, 'exit_code' => $name === 'weak_void' ? 0 : 9];
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
echo "Ownership targets: ten source rejections, two recovery sequences and three controls passed\n";
