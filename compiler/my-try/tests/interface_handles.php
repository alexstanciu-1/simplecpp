<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function handle_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/handles', '/handles', '/handles');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}
$results = [];
function handle_export(string $name, string $cpp, int $expected): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	if ($name === 'absent_call') {
		foreach (Model::$cpp_files as $artifact) {
			if ($artifact->identity === 'entry') {
				$instrumented = str_replace('int main()', 'int main() try', $artifact->text)
					. 'catch (const scpp::runtime_error& error) { return std::string(error.what()).find("requires a present shared pointer") != std::string::npos ? 0 : 2; }' . "\n";
				$cpp = str_replace($artifact->text, $instrumented, $cpp);
			}
		}
	}
	$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => $expected];
}
$basic = 'interface I { function read(): int; } class C implements I { int $n = 9; function read(): int { return $this->n; } } ';
foreach ([
	'local' => [$basic . '$c = new C(); $i I = $c; return $i->read();', 9],
	'construction' => [$basic . '$i I = new C(); return $i->read();', 9],
	'assignment' => [$basic . '$i I; $c = new C(); $i = $c; return $i->read();', 9],
	'argument_return' => [$basic . 'function produce(): I { return new C(); } function read(I $i): int { return $i->read(); } $c = new C(); $a = read($c); $b = read(produce()); return $a + $b;', 18],
	'identity' => [$basic . '$c = new C(); $i I = $c; $copy = $i; $c->n = 7; $i = null; return $copy->read();', 7],
	'absent' => [$basic . '$i I; $n = isset($i) ? 100 : 4; if ($i) { return 101; } $i = null; return $n;', 4],
	'absent_call' => [$basic . '$i I; return $i->read();', 0],
	'nullable_spelling' => [$basic . '$i ?I; $c = new C(); $i = $c; $j nullable<I> = $i; return $j->read();', 9],
	'empty_interface' => ['interface I {} class C implements I {} $i I = new C(); return isset($i) ? 9 : 0;', 9],
	'inherited_implementation' => ['interface I { function read(): int; } class B { function read(): int { return 9; } } class C extends B implements I {} $i I = new C(); return $i->read();', 9],
	'interface_parent' => ['interface I { function read(): int; } interface J extends I {} class C implements J { function read(): int { return 9; } } $j J = new C(); $i I = $j; return $i->read();', 9],
	'diamond' => ['interface I { function read(): int; } interface L extends I {} interface R extends I {} interface D extends L, R {} class C implements D { function read(): int { return 9; } } $d D = new C(); $i I = $d; $a = $i->read(); $b = $d->read(); return $a + $b;', 18],
	'defaults' => ['interface I { function read(int $n = 4): int; } class C implements I { function read(int $n = 5): int { return $n; } } $c = new C(); $i I = $c; $a = $i->read(); $b = $c->read(); return $a + $b;', 9],
	'abstract_body' => ['interface I { function read(): int; } abstract class B implements I { function invoke(): int { return $this->read(); } } class C extends B { function read(): int { return 9; } } $c = new C(); return $c->invoke();', 9],
	'abstract_struct' => ['interface I { function read(): int; } abstract struct B implements I { function invoke(): int { return $this->read(); } } struct C extends B { function read(): int { return 9; } } $c = new C(); return $c->invoke();', 9],
	'shared_struct' => ['interface I { function read(): int; } struct C implements I { function read(): int { return 9; } } $c shared<C> = new C(); $i I = $c; return $i->read();', 9],
	'property_container' => [$basic . 'data class H { I $i; } $c = new C(); $h H = {i: $c}; $items vector<I> = [$c]; $i = $items[0]; $a = $h->i->read(); $b = $i->read(); return $a + $b;', 18],
	'reference' => ['interface I { function &select(int &$n): int; } class C implements I { function &select(int &$n): int { return $n; } } $i I = new C(); $n = 1; $a =& $i->select($n); $a = 9; return $n;', 9],
	'variadic' => ['interface I { function sum(int ...$ns): int; } class C implements I { function sum(int ...$ns): int { $sum = 0; foreach ($ns as $n) { $sum += $n; } return $sum; } } $i I = new C(); return $i->sum(4, 5);', 9],
	'handle_reference' => [$basic . 'function replace(I &$i): void { $i = new C(); } $i I; replace($i); return $i->read();', 9],
	'receiver_retention' => ['interface I { function read(int $n): int; } class C implements I { int $n = 9; function read(int $unused): int { return $this->n; } } function release(I &$i): int { $i = null; return 0; } $i I = new C(); return $i->read(release($i));', 9],
	'contract_redeclaration' => ['interface A { function read(int $n = 1): int; } interface B { function read(int $n = 2): int; } interface I extends A, B { function read(int $n = 9): int; } class C implements I { function read(int $n = 3): int { return $n; } } $i I = new C(); return $i->read();', 9],
	'cleanup' => ['interface I {} class Log { static int $n = 0; } class C implements I { function __destruct() { Log::$n += 9; } } $i I = new C(); $j = $i; $i = null; if (Log::$n !== 0) { return 101; } $j = null; return Log::$n;', 9],
] as $name => [$source, $expected]) { handle_export($name, handle_source($source), $expected); }
foreach ([
	[$basic . '$i I = new C(); $x = $i->n;', 'member access'],
	[$basic . '$i I = new C(); $c C = $i;', 'ownership boundary'],
	[$basic . 'function replace(I &$i): void {} $c = new C(); replace($c);', 'exact'],
	[$basic . '$i value<I>;', 'Interface storage'],
	[$basic . '$i shared<I>;', 'Interface ownership'],
	[$basic . '$i unique<I>;', 'Interface ownership'],
	[$basic . '$i weak<I>;', 'Interface ownership'],
	[$basic . '$i I; $w = weak($i);', 'Interface weak'],
	[$basic . '$i = new I();', 'new requires'],
	[$basic . '$i I = {};', 'initialization'],
	['interface I {} struct C implements I {} $c C; $i I = $c;', 'ownership boundary'],
	['interface A { function read(int $n = 1): int; } interface B { function read(int $n = 2): int; } interface I extends A, B {} class C implements I { function read(int $n = 3): int { return $n; } } $i I = new C(); return $i->read();', 'one ordinary method'],
	['interface I { function &pick(int &$n): int; } class C implements I { function &pick(int &$n): int { $i I = new C(); return $i->pick($n); } }', 'recursive'],
	['interface I { function &pick(int &$n): int; } abstract class B implements I { function &invoke(int &$n): int { return $this->pick($n); } } class C extends B { function &pick(int &$n): int { return $this->invoke($n); } }', 'recursive'],
	['interface I { function &pick(int &$n): int; } class B { function &pick(int &$n): int { $i I = new C(); return $i->pick($n); } } class C extends B implements I {}', 'recursive'],
] as [$source, $message]) {
	try { handle_source($source); throw new \LogicException('Expected rejection: ' . $message); }
	catch (\RuntimeException $error) { if (!str_contains(strtolower($error->getMessage()), strtolower($message))) { throw $error; } }
}

$directory = sys_get_temp_dir() . '/interface-handles-' . bin2hex(random_bytes(6));
mkdir($directory);
$interface_path = $directory . '/interface.phs';
$class_path = $directory . '/class.phs';
$main_path = $directory . '/main.phs';
try {
	$interface_text = 'interface J { function read(int $n = 4): int; } interface I extends J {}';
	$class_text = 'class C implements I { function read(int $n = 5): int { return $n; } }';
	file_put_contents($interface_path, $interface_text); file_put_contents($class_path, $class_text);
	file_put_contents($main_path, '$i I = new C(); return $i->read();');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	foreach ([[$interface_path, str_replace(' extends J', '', $interface_text), $interface_text],
		[$class_path, str_replace(' implements I', '', $class_text), $class_text]] as [$path, $invalid, $valid]) {
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Interface edit survived retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($path, $valid); $compiler->update_cpp([$path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Interface repair differs'); }
	}
	file_put_contents($interface_path, str_replace('= 4', '= 8', $interface_text));
	$compiler->update_cpp([$interface_path]);
	handle_export('cross_file_defaults', native_record_export(), 8);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh interface handles differ'); }

	file_put_contents($interface_path, 'interface I { function &pick(int &$n): int; }');
	$class_text = 'class C implements I { function &pick(int &$n): int { return $n; } }';
	file_put_contents($class_path, $class_text);
	file_put_contents($main_path, '$i I = new C(); $n = 9; $a =& $i->pick($n); return $a;');
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($class_path, str_replace('return $n;', '$i I = new C(); return $i->pick($n);', $class_text));
	foreach ([[$class_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Interface cycle survived retry'); }
		catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'recursive')) { throw $error; } }
	}
	file_put_contents($class_path, $class_text); $compiler->update_cpp([$class_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Cycle repair differs'); }
	handle_export('cycle_repair', native_record_export(), 9);
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$interface_path, $class_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Interface handles, calls and reference-cycle checks passed\n";
