<?php

/* Plain record storage matrix: class identity, struct values and safe member boundaries. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Prepare through the real publication boundary and retain separate generated artifacts. */
function records_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/records', '/records', '/records');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$prefix = 'class C { public int $x; } struct S { public int $x; } ';
$cases = [
	'class_shared' => '$a = new C(); $a->x = 7; $b = $a; $b->x = 9; return $a->x;',
	'struct_value' => '$a = new S(); $a->x = 7; $b = $a; $b->x = 9; return $a->x;',
	'class_inline' => '$a value<C> = new C(); $a->x = 7; $b = $a; $b->x = 9; return $a->x;',
	'struct_shared' => '$a shared<S> = new S(); $a->x = 7; $b = $a; $b->x = 9; return $a->x;',
	'class_unique' => '$a unique<C> = new C(); $a->x = 9; $b = move($a); return $b->x;',
	'struct_unique' => '$a unique<S> = new S(); $a->x = 9; $b = move($a); return $b->x;',
	'class_weak' => '$a = new C(); $a->x = 9; $w weak<C> = weak($a); $b = lock($w); $a = null; return $b->x;',
	'struct_weak' => '$a shared<S> = new S(); $a->x = 9; $w weak<S> = weak($a); $b = lock($w); $a = null; return $b->x;',
	'class_expired' => '$a = new C(); $w = weak($a); $a = null; if (lock($w)) { return 1; } return 0;',
	'class_recursive' => 'class Node { public Node $next; public int $n; } $a = new Node(); $a->n = 9; return $a->n;',
	'nested_handle_copy' => 'struct Holder { C $item; } $h = new Holder(); $h->item = new C(); $h->item->x = 7; $b = $h; $b->item->x = 9; return $h->item->x;',
	'nested_inline_copy' => 'class Holder { public S $item; } $h value<Holder> = new Holder(); $h->item->x = 7; $b = $h; $b->item->x = 9; return $h->item->x;',
	'struct_expired' => '$a shared<S> = new S(); $w = weak($a); $a = null; if (lock($w)) { return 1; } return 0;',
	'inline_reference' => 'function bump(int &$n): void { $n += 2; } $a value<C> = new C(); $a->x = 7; bump($a->x); return $a->x;',
	'class_boundary' => 'function change(C $a): C { $a->x = 9; return $a; } $a = new C(); $a->x = 7; $b = change($a); return $a->x;',
	'struct_boundary' => 'function change(S $a): S { $a->x = 9; return $a; } $a = new S(); $a->x = 7; $b = change($a); return $a->x;',
];
$results = [];
foreach ($cases as $name => $source) {
	$output = records_source($prefix . $source);
	if (str_contains($output, 'shared_p<::scpp::shared_p<')) {
		throw new \LogicException('Double class handle emitted');
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$expected = in_array($name, ['struct_value', 'class_inline', 'struct_boundary', 'nested_inline_copy'], true) ? 7 : 9;
		if (in_array($name, ['class_expired', 'struct_expired'], true)) { $expected = 0; }
		$results[] = ['path' => $path, 'exit_code' => $expected];
	}
}
$rejections = [
	['$a shared<C>;', 'repeats the declaration'],
	['$a value<S>;', 'repeats the declaration'],
	['$a weak<C> = new C();', 'cannot allocate'],
	['$a weak<S> = new S();', 'cannot allocate'],
	['$a = new C(); $w = weak($a); return $w->x;', 'explicit lock'],
	['$a = new S(); $w = weak($a);', 'requires shared'],
	['$a = new C(); $alias =& $a->x;', 'heap-backed'],
	['function f(int &$n): void {} $a = new C(); f($a->x);', 'heap-backed'],
	['$a = new C(); $a->missing = 1;', 'resolved record property'],
	['class Bad { public int $x; public int $x; }', 'Duplicate property'],
	['$a = new C(1);', 'constructor arguments'],
	['$a value<C> = new C(); $b C = $a;', 'ownership boundary'],
	['class NC { public unique<int> $u; } $a value<NC> = new NC(); $b = $a;', 'copyable'],
	['$a unique<value<C>>;', 'unmodified payload'],
	['$a value<unique<C>>;', 'Value modifier'],
	['$a shared<unique<S>>;', 'Direct ownership'],
	['class Holder { public S $item; } $h = new Holder(); $alias =& $h->item->x;', 'heap-backed'],
	['function f(const C &$a): void { $a->x = 1; }', 'read-only'],
];
foreach ($rejections as [$source, $message]) {
	try {
		records_source($prefix . $source);
		throw new \LogicException('Unexpected acceptance: ' . $source);
	}
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), $message)) {
			throw new \LogicException($source . ': ' . $error->getMessage());
		}
	}
}

// Keep declaration identity across body edits; replacing class with struct changes storage identity.
$directory = sys_get_temp_dir() . '/record-incremental-' . bin2hex(random_bytes(6));
mkdir($directory);
$types_path = $directory . '/types.phs';
$main_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { public int $x; }';
	file_put_contents($types_path, $declaration);
	file_put_contents($main_path, 'use Types\\C; $a = new C(); $a->x = 7; $b = $a; $b->x = 9; return $a->x;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$before = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($types_path, 'namespace Types; class C { public int $renamed; }');
	foreach ([[$types_path], []] as $changes) {
		try {
			$compiler->update_cpp($changes);
			throw new \LogicException('Changed property was not invalidated');
		}
		catch (\RuntimeException $error) {
			if (!str_contains($error->getMessage(), 'resolved record property')) { throw $error; }
		}
	}
	file_put_contents($types_path, $declaration);
	$compiler->update_cpp([$types_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $before) {
		throw new \LogicException('Property repair differs from original output');
	}
	file_put_contents($types_path, str_replace('class C', 'struct C', $declaration));
	$compiler->update_cpp([$types_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	if ($incremental === $before) { throw new \LogicException('Declaration-kind edit retained shared storage'); }
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) {
		throw new \LogicException('Class/struct replacement differs from fresh build');
	}
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) {
		throw new \LogicException('Record preparation reset changed output');
	}
}
finally {
	unlink($types_path);
	unlink($main_path);
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1] . '/executions.json', json_encode($results));
}
echo "record storage matrix passes\n";
