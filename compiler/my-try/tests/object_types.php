<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function object_types_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/object-types', '/object-types', '/object-types');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return native_record_export();
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	$handle = $kind === 'class' ? 'B' : 'shared<B>';
	$child = $kind === 'class' ? 'C' : 'shared<C>';
	$both = $kind === 'class' ? 'Both' : 'shared<Both>';
	$prefix = 'interface I { function read(): int; } ' . $kind . ' B implements I { int $n = 1; function read(): int { return $this->n; } } '
		. $kind . ' C extends B { int $extra = 9; } ' . $kind . ' D extends B {} ';
	foreach ([
		'identity' => '$c CHILD = new C(); $b HANDLE = $c; $out = object_cast($b, C::class); $out->n = 9; return $c->n;',
		'tests' => '$c CHILD = new C(); $b HANDLE = $c; if (!$b instanceof C) { return 101; } if ($b instanceof D) { return 102; } if (!($b instanceof B)) { return 103; } return 9;',
		'empty_test' => '$b HANDLE; return $b instanceof C ? 101 : 9;',
		'interface' => '$c CHILD = new C(); $i I = $c; if (!($i instanceof C)) { return 101; } $out = object_cast($i, C::class); return $out->extra;',
		'upcast' => '$c CHILD = new C(); $b = object_cast($c, B::class); $b->n = 9; return $c->n;',
		'single_evaluation' => 'function produce(int &$n): HANDLE { $n++; $c CHILD = new C(); return $c; } $n = 0; $out = object_cast(produce($n), C::class); if (!(produce($n) instanceof C)) { return 101; } return $n === 2 ? $out->extra : 102;',
		'empty_failure' => '$b HANDLE; $out = object_cast($b, C::class); return 101;',
		'mismatch_failure' => '$d ' . ($kind === 'class' ? 'D' : 'shared<D>') . ' = new D(); $b HANDLE = $d; $out = object_cast($b, C::class); return 101;',
		'cross_identity' => 'interface J { function write(int $v): void; } ' . $kind . ' Both extends B implements J { function write(int $v): void { $this->n = $v; } } $both BOTH = new Both(); $b HANDLE = $both; $i I = $b; $j = object_cast($i, J::class); $j->write(9); return $b->n;',
		'cross_record_interface' => 'interface J {} ' . $kind . ' Both extends B implements J {} $both BOTH = new Both(); $b HANDLE = $both; if (!($b instanceof J)) { return 101; } $j = object_cast($b, J::class); $back = object_cast($j, B::class); $back->n = 9; return $b->n;',
		'cross_no_implementer' => 'interface J {} $i I; return $i instanceof J ? 101 : 9;',
		'cross_empty_failure' => 'interface J {} $i I; $j = object_cast($i, J::class); return 101;',
		'cross_mismatch_failure' => 'interface J {} $b HANDLE = new B(); $i I = $b; $j = object_cast($i, J::class); return 101;',
		'cross_mismatch_test' => 'interface J {} $b HANDLE = new B(); $i I = $b; return $i instanceof J ? 101 : 9;',
		'cross_single_evaluation' => 'interface J {} ' . $kind . ' Both extends B implements J {} function produce(int &$n): I { $n++; $both BOTH = new Both(); return $both; } $n = 0; $j = object_cast(produce($n), J::class); if (!(produce($n) instanceof J)) { return 101; } return $n === 2 ? 9 : 102;',
		'cross_final_implementer' => 'interface J {} final ' . $kind . ' Both extends B implements J {} $both BOTH = new Both(); $i I = $both; $j = object_cast($i, J::class); $v = object_cast($j, Both::class); return $v instanceof J ? 9 : 101;',
	] as $name => $body) {
		$output = object_types_source($prefix . str_replace(['HANDLE', 'CHILD', 'BOTH'], [$handle, $child, $both], $body));
		if (str_contains($name, 'failure')) {
			foreach (Model::$cpp_files as $artifact) {
				if ($artifact->identity === 'entry') {
					$instrumented = str_replace('int main(int argc, char** argv)', 'int main(int argc, char** argv) try', $artifact->text)
						. 'catch (const scpp::runtime_error &e) { return e.code() == "invalid_object_cast" ? 9 : 102; }' . "\n";
					$output = str_replace($artifact->text, $instrumented, $output);
				}
			}
		}
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp'; file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 9];
		}
	}
}
foreach ([
	'class B {} class C extends B {} $b B; $c = object_cast($b, C::class);' => 'polymorphic source',
	'class B {} class C extends B {} $b B; $ok = $b instanceof C;' => 'polymorphic source',
	'class A {} class B {} $a A; $b = object_cast($a, B::class);' => 'related ancestry',
	'class C {} $c unique<C>; $v = object_cast($c, C::class);' => 'shared handle',
	'class C {} $c weak<C>; $v = $c instanceof C;' => 'shared handle',
	'struct C {} $c C; $v = object_cast($c, C::class);' => 'shared handle',
	'class C {} $v = object_cast(1, C::class);' => 'shared handle',
	'class C {} $c C; $v = object_cast($c, int::class);' => 'source class',
	'interface I {} class B {} $b B; $v = object_cast($b, I::class);' => 'polymorphic source',
	'interface I {} class B {} $b B; $v = $b instanceof I;' => 'polymorphic source',
	'interface I {} class B implements I {} class C extends B {} class D extends B {} $c C; $d = object_cast($c, D::class);' => 'related ancestry',
	'interface I {} interface J {} final class B implements I {} $b B; $v = object_cast($b, J::class);' => 'final record',
	'interface I {} final class B {} $i I; $v = object_cast($i, B::class);' => 'final record',
	'interface I {} final struct B {} $i I; $v = $i instanceof B;' => 'final record',
] as $source => $diagnostic) {
	try { object_types_source($source); throw new \LogicException('Invalid object type operation accepted'); }
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; }
	}
}
$directory = sys_get_temp_dir() . '/object-types-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$main_path = $directory . '/main.phs';
try {
	$base = 'namespace Demo; interface I { function read(): int; } class B implements I { function read(): int { return 9; } }';
	file_put_contents($base_path, $base);
	file_put_contents($child_path, 'namespace Demo; class C extends B { int $n = 9; }');
	file_put_contents($main_path, 'use Demo\\B; use Demo\\C; $b B = new C(); $c = object_cast($b, C::class); return $c->n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	foreach (['polymorphic source', 'related ancestry'] as $diagnostic) {
		$changed_path = $diagnostic === 'polymorphic source' ? $base_path : $child_path;
		$changed_text = $diagnostic === 'polymorphic source' ? str_replace(' implements I', '', $base) : 'namespace Demo; class C { int $n = 9; }';
		// Keep the operand typed as B independently of C's ancestry so the cast owns rejection.
		file_put_contents($main_path, 'use Demo\\B; use Demo\\C; $b B; $c = object_cast($b, C::class); return 9;');
		$compiler->update_cpp([$main_path]);
		file_put_contents($changed_path, $changed_text);
		foreach ([[$changed_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Object type dependency edit was not rejected'); }
			catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; } }
		}
		file_put_contents($base_path, $base);
		file_put_contents($child_path, 'namespace Demo; class C extends B { int $n = 9; }');
		file_put_contents($main_path, 'use Demo\\B; use Demo\\C; $b B = new C(); $c = object_cast($b, C::class); return $c->n;');
		$compiler->update_cpp([$base_path, $child_path, $main_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Object type repair changed output'); }
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/cross_file.cpp'; file_put_contents($path, native_record_export());
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Object type reset changed output'); }
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Object type fresh output differs'); }
	// Finality is an observed dependency in either direction, including failed retries.
	foreach (['$b B; $ok = $b instanceof J;', '$j J; $out = object_cast($j, B::class);'] as $operation) {
		$open_base = 'namespace Demo; interface I {} interface J {} class B implements I {}';
		file_put_contents($base_path, $open_base);
		file_put_contents($child_path, 'namespace Demo;');
		file_put_contents($main_path, 'use Demo\\B; use Demo\\J; ' . $operation);
		Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		$open_output = CPP_Output::encode(Model::$cpp_files);
		file_put_contents($base_path, str_replace('class B', 'final class B', $open_base));
		foreach ([[$base_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Final record cross-cast edit was not rejected'); }
			catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'final record')) { throw $error; } }
		}
		file_put_contents($base_path, $open_base); $compiler->update_cpp([$base_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $open_output) { throw new \LogicException('Finality repair changed output'); }
		Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $open_output) { throw new \LogicException('Cross-cast reset changed output'); }
		Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $open_output) { throw new \LogicException('Cross-cast fresh output differs'); }
	}
}
finally {
	Compiler_Lifecycle::reset();
	unlink($base_path); unlink($child_path); unlink($main_path); rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Checked shared object access and type tests passed\n";
