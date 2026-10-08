<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function variance_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/variance', '/variance', '/variance');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

$results = [];
function variance_export(string $name, string $cpp, int $expected = 9): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	$path = $argv[1] . '/' . $name . '.cpp';
	file_put_contents($path, $cpp); $results[] = ['path' => $path, 'exit_code' => $expected];
}

$payloads = 'class Animal { int $n = 9; } class Dog extends Animal {} class Puppy extends Dog {} ';
foreach (['class', 'struct'] as $kind) {
	$cases = [
		'combined' => 'abstract ' . $kind . ' A { abstract function f(Dog $x): Animal; function read(Dog $x): Animal { return $this->f($x); } } '
			. $kind . ' B extends A { function f(Animal $x): Dog { $d = new Dog(); $d->n = $x->n; return $d; } } $b = new B(); $d = new Dog(); $r = $b->read($d); return $r->n;',
		'multilevel_parent' => 'abstract ' . $kind . ' A { abstract function f(Puppy $x): Animal; function read(Puppy $x): Animal { return $this->f($x); } } '
			. $kind . ' B extends A { function f(Dog $x): Dog { $d = new Dog(); $d->n = 4; return $d; } } '
			. $kind . ' C extends B { function f(Animal $x): Puppy { $p = new Puppy(); $b = parent::f($p); $n = $b->n; $p->n = $n + 5; return $p; } } $c = new C(); $p = new Puppy(); $r = $c->read($p); return $r->n;',
		'abstract_intermediate' => 'abstract ' . $kind . ' A { abstract function f(Dog $x): Animal; function read(Dog $x): Animal { return $this->f($x); } } '
			. 'abstract ' . $kind . ' B extends A { abstract function f(Animal $x): Dog; } '
			. $kind . ' C extends B { function f(Animal $x): Puppy { return new Puppy(); } } $c = new C(); $p = new Dog(); $r = $c->read($p); return $r->n;',
		'trait' => 'trait T { abstract function f(Dog $x): Animal; function read(Dog $x): Animal { return $this->f($x); } } '
			. $kind . ' C { use T; function f(Animal $x): Dog { return new Dog(); } } $c = new C(); $d = new Dog(); $r = $c->read($d); return $r->n;',
	];
	foreach ($cases as $name => $text) { variance_export($kind . '_' . $name, variance_source($payloads . $text)); }
}
foreach ([
	'interface_views' => 'interface I { function f(Dog $x): Animal; } interface J { function f(Animal $x): Dog; } class C implements I,J { function f(Animal $x): Dog { return new Dog(); } } $c = new C(); $i I = $c; $j J = $c; $d = new Dog(); $a = $i->f($d); $b = $j->f($d); $an = $a->n; $bn = $b->n; return $an + $bn - 9;',
	'interface_redeclaration' => 'interface I { function f(Dog $x): Animal; } interface J extends I { function f(Animal $x): Dog; } class C implements J { function f(Animal $x): Puppy { return new Puppy(); } } $c = new C(); $i I = $c; $d = new Dog(); $r = $i->f($d); return $r->n;',
	'inherited_forwarding' => 'interface I { function f(Dog $x): Animal; } class A { function f(Animal $x): Dog { return new Dog(); } } class B extends A implements I {} class C extends B { function f(Animal $x): Puppy { $p = new Puppy(); $p->n = 9; return $p; } } $c = new C(); $b B = $c; $i I = $c; $d = new Dog(); $r = $b->f($d); $s = $i->f($d); $rn = $r->n; $sn = $s->n; return $rn + $sn - 9;',
	'virtual_forwarding' => 'interface I { function f(Dog $x): Animal; } abstract class A { abstract function f(Animal $x): Dog; } class B extends A { function f(Animal $x): Dog { $d = new Dog(); $d->n = 1; return $d; } } class C extends B implements I {} class D extends C { function f(Animal $x): Puppy { return new Puppy(); } } $d = new D(); $a A = $d; $i I = $d; $x = new Dog(); $r = $a->f($x); $s = $i->f($x); $rn = $r->n; $sn = $s->n; return $rn + $sn - 9;',
	'identity_once_defaults' => 'class Log { static int $n = 0; } interface I { function f(Dog $x, int $n = 4): Animal; } class C implements I { Dog $saved; function __construct(set:$saved) {} function f(Animal $x, int $other = 5): Dog { Log::$n += 1; $this->saved->n = $other; return $this->saved; } } $d = new Dog(); $c = new C($d); $i I = $c; $r = $i->f($d); $a = $d->n; $s = $c->f($d); $b = $d->n; $rn = $r->n; $sn = $s->n; return ($rn === 5 && $sn === 5 && Log::$n === 2) ? $a + $b : 1;',
	'null_result' => 'interface I { function f(): Animal; } class C implements I { function f(): Dog { $d Dog; return $d; } } $c = new C(); $i I = $c; $r = $i->f(); return isset($r) ? 1 : 9;',
	'interface_payloads' => 'interface P { function score(): int; } interface Q extends P {} class DogView implements Q { function score(): int { return 9; } } interface I { function f(Q $x): P; } class C implements I { function f(P $x): Q { return new DogView(); } } $c = new C(); $i I = $c; $d = new DogView(); $r = $i->f($d); return $r->score();',
	'shared_struct_payloads' => 'struct S { int $n = 9; } struct T extends S {} interface I { function f(shared<T> $x): shared<S>; } class C implements I { function f(shared<S> $x): shared<T> { $t shared<T> = new T(); return $t; } } $c = new C(); $i I = $c; $t shared<T> = new T(); $r = $i->f($t); return $r->n;',
	'self_result' => 'abstract class A { abstract function f(): A; } class B extends A { function f(): B { return new B(); } } $b = new B(); $a A = $b; $r = $a->f(); return $r instanceof B ? 9 : 1;',
	'trait_abstract_slot' => 'trait T { abstract function f(Dog $x): Animal; function read(Dog $x): Animal { return $this->f($x); } } abstract class A { use T; } class C extends A { function f(Animal $x): Dog { return new Dog(); } } $c = new C(); $a A = $c; $d = new Dog(); $r = $a->read($d); return $r->n;',
	'reference_result' => 'interface I { function &f(Dog $x, int &$n): int; } class C implements I { function &f(Animal $x, int &$n): int { return $n; } } $c = new C(); $i I = $c; $d = new Dog(); $n = 1; $alias =& $i->f($d, $n); $alias = 9; return $n;',
	'final_forwarding' => 'interface I { function f(Dog $x): Animal; } abstract class A { abstract function f(Animal $x): Dog; } class B extends A { final function f(Animal $x): Dog { return new Dog(); } } class C extends B implements I {} $c = new C(); $a A = $c; $i I = $c; $d = new Dog(); $r = $a->f($d); $s = $i->f($d); $rn = $r->n; $sn = $s->n; return $rn + $sn - 9;',
] as $name => $text) { variance_export($name, variance_source($payloads . $text)); }

foreach ([
	'interface I { function f(Animal $x): Animal; } class C implements I { function f(Dog $x): Dog { return new Dog(); } }',
	'interface I { function f(Dog $x): Dog; } class C implements I { function f(Animal $x): Animal { return new Animal(); } }',
	'interface I { function f(Dog &$x): Animal; } class C implements I { function f(Animal &$x): Dog { return new Dog(); } }',
	'interface I { function f(Dog ...$x): Animal; } class C implements I { function f(Animal ...$x): Dog { return new Dog(); } }',
	'interface I { function f(Animal $x): Animal; } interface J extends I { function f(Dog $x): Dog; }',
	'class A { static function f(Dog $x): Animal { return $x; } } class B extends A { static function f(Animal $x): Dog { return new Dog(); } }',
	'abstract class A { abstract function f(): Animal; } class B extends A { final function f(): Dog { return new Dog(); } } class C extends B { function f(): Puppy { return new Puppy(); } }',
] as $text) {
	try { variance_source($payloads . $text); throw new \LogicException('Invalid variance accepted'); }
	catch (\RuntimeException $expected) {}
}

// Cross-file signature and ancestry changes must rebuild bindings, including failed retry/repair.
$directory = sys_get_temp_dir() . '/variance-' . bin2hex(random_bytes(6)); mkdir($directory);
$types = $directory . '/types.phs'; $contract_path = $directory . '/contract.phs';
$implementation = $directory . '/implementation.phs'; $main = $directory . '/main.phs';
try {
	file_put_contents($types, $payloads);
	$contract_text = 'interface I { function f(Dog $x): Animal; }';
	file_put_contents($contract_path, $contract_text);
	file_put_contents($implementation, 'class C implements I { function f(Animal $x): Dog { return new Dog(); } }');
	file_put_contents($main, '$c = new C(); $i I = $c; $d = new Dog(); $r = $i->f($d); return $r->n;');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$record = Model::$global_scope->source_types_named('C')[0]; $work = $record->contract_work;
	foreach ([[$types, str_replace('class Dog extends Animal', 'class Dog', $payloads), $payloads],
		[$contract_path, str_replace(': Animal', ': Puppy', $contract_text), $contract_text]] as [$path, $invalid, $valid]) {
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid incremental variance accepted'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($path, $valid); $compiler->update_cpp([$path]);
		if ($record->contract_work !== $work || CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Variance repair differs'); }
	}
	file_put_contents($implementation, 'class C implements I { function f(Animal $x): Puppy { return new Puppy(); } }');
	$compiler->update_cpp([$implementation]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	variance_export('cross_file_edit', native_record_export());
	Compiler_Lifecycle::reset();
	if ($work->facts !== null || count($work->dependencies) !== 0) { throw new \LogicException('Reset retained variance facts'); }
	$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh variance differs'); }
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$types, $contract_path, $implementation, $main] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Shared callable variance, dispatch bindings and incremental repair passed\n";
