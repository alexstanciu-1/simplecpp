<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

/** Exercise admission directly, without allowing emission to diagnose the source. */
function duplicate_type_rejection(Compiler $compiler, array $paths, string $name): void
{
	$compiler->sync($paths);
	try
	{
		$compiler->prepare();
	}
	catch (\RuntimeException $error)
	{
		if (!str_contains($error->getMessage(), 'Duplicate type declaration: ' . $name)) {
			throw new \LogicException('Wrong duplicate diagnostic: ' . $error->getMessage());
		}
		if (!Model::$cpp_files->is_empty()) {
			throw new \LogicException('Duplicate types retained published C++ artifacts');
		}
		return;
	}
	throw new \LogicException('Duplicate type passed semantic preparation');
}

/** Fresh compiler and source discovery, including files not referenced by the entry. */
function duplicate_type_compiler(string $directory): Compiler
{
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	return $compiler;
}

$directory = sys_get_temp_dir() . '/duplicate-types-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$other = $directory . '/other.phs';
$kinds = ['class' => 'class Item {}', 'struct' => 'struct Item { int $n; }',
	'interface' => 'interface Item {}', 'trait' => 'trait Item {}',
	'enum' => 'enum Item { case One; }'];
$rejections = 0;
$executions = [];
try
{
	// Both orders of every collision exercise all entry paths, including unused traits.
	foreach ($kinds as $first)
	{
		foreach ($kinds as $second)
		{
			file_put_contents($path, 'namespace App; ' . $first . ' ' . $second . ' return 0;');
			duplicate_type_rejection(duplicate_type_compiler($directory), [], 'App\\Item');
			$rejections++;
		}
	}
	file_put_contents($path, 'namespace App { class Item {} } namespace App { class Item {} return 0; }');
	duplicate_type_rejection(duplicate_type_compiler($directory), [], 'App\\Item');
	$rejections++;

	foreach ($kinds as $kind => $declaration)
	{
		$baseline = 'namespace App; ' . $declaration . ' return 0;';
		file_put_contents($path, $baseline);
		file_put_contents($other, 'namespace App;');
		$compiler = duplicate_type_compiler($directory);
		$compiler->exec_cpp();
		$original = CPP_Output::encode(Model::$cpp_files);

		// The original declaration is unchanged; only another file's pool contribution changes.
		file_put_contents($other, 'namespace App; ' . $declaration);
		duplicate_type_rejection($compiler, [$other], 'App\\Item');
		duplicate_type_rejection($compiler, [], 'App\\Item');
		file_put_contents($other, 'namespace App; ' . str_replace('Item', 'Other', $declaration));
		$compiler->update_cpp([$other]);
		$renamed = CPP_Output::encode(Model::$cpp_files);
		$fresh = duplicate_type_compiler($directory);
		$fresh->exec_cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $renamed) {
			throw new \LogicException('Renamed ' . $kind . ' differs from fresh compilation');
		}

		// Removal recovery from an already successful compiler, followed by unchanged retry.
		file_put_contents($other, 'namespace App; ' . $declaration);
		duplicate_type_rejection($fresh, [$other], 'App\\Item');
		duplicate_type_rejection($fresh, [], 'App\\Item');
		unlink($other);
		$fresh->update_cpp([$other]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) {
			throw new \LogicException('Removing duplicate ' . $kind . ' did not restore baseline');
		}
		file_put_contents($other, 'namespace App; ' . $declaration);
		duplicate_type_rejection(duplicate_type_compiler($directory), [], 'App\\Item');
		unlink($other);
	}

	$controls = [
		'distinct_namespaces' => 'namespace A { class Item {} } namespace B { class Item {} return 0; }',
		'distinct_kinds' => 'class Item {} function Item(): int { return 7; } return Item();',
		'language_shadow' => 'struct vector { int $n; } return 0;',
		'repeated_import' => 'namespace Lib { class Item {} } namespace App { use Lib\\Item; use Lib\\Item; return 0; }',
	];
	foreach ($controls as $name => $source)
	{
		file_put_contents($path, $source);
		$compiler = duplicate_type_compiler($directory);
		$compiler->exec_cpp();
		if (isset($argv[1]))
		{
			$output = $argv[1] . '/' . $name . '.cpp';
			file_put_contents($output, native_record_export());
			$executions[] = ['path' => $output, 'exit_code' => $name === 'distinct_kinds' ? 7 : 0];
		}
	}
}
finally
{
	if (is_file($other)) { unlink($other); }
	unlink($path);
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1] . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT) . "\n");
}
echo "Duplicate types: $rejections local rejections, five cross-file recovery sequences and four controls passed\n";
