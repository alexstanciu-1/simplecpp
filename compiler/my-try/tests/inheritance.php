<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function inheritance_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/inheritance', '/inheritance', '/inheritance');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	$cases = [
		'property' => ['int $n = 3;', 'int $m = 4;', '$c = new C(); $c->n += 2; $n = $c->n; $m = $c->m; return $n + $m;', 9],
		'method' => ['int $n = 3; public function get(): int { return $this->n; }', '', '$c = new C(); return $c->get();', 3],
		'protected' => ['protected int $n = 7; protected function get(): int { return $this->n; }', 'public function read(): int { return $this->get(); }', '$c = new C(); return $c->read();', 7],
		'static' => ['protected static int $n = 2; protected const N = 3; protected static function get(): int { return self::$n; }', 'public static function read(): int { self::$n = 6; $v = self::get(); return $v + self::N; }', 'return C::read();', 9],
		'shared_static' => ['public static int $n = 2;', '', 'C::$n = 9; return B::$n;', 9],
		'constant' => ['public const N = 9;', '', 'return C::N;', 9],
		'private_owner' => ['private int $n = 8; public function get(): int { return $this->n; }', '', '$c = new C(); return $c->get();', 8],
		'categories' => ['int $same = 3;', 'public function same(): int { return 6; }', '$c = new C(); $v = $c->same(); $p = $c->same; return $v + $p;', 9],
		'constructors' => ['int $n; public function __construct() { $this->n = 4; }', 'int $m; public function __construct() { $v = $this->n; $this->m = $v + 1; }', '$c = new C(); $n = $c->n; $m = $c->m; return $n + $m;', 9],
		'base_constructor' => ['int $n; public function __construct() { $this->n = 9; }', '', '$c = new C(); return $c->n;', 9],
		'child_arguments' => ['int $n = 3;', 'int $m; public function __construct(set:$m) {}', '$c = new C(6); $n = $c->n; $m = $c->m; return $n + $m;', 9],
		'inherited_arguments' => ['int $n; public function __construct(set:$n = 9) {}', 'int $m = 1;', '$c = new C(); return $c->n;', 9],
		'parent_arguments' => ['int $n; public function __construct(set:$n) {}', 'int $m; public function __construct(int $n, set:$m) { parent::__construct($n); }', '$c = new C(4, 5); $n = $c->n; $m = $c->m; return $n + $m;', 9],
		'parent_default' => ['int $n; public function __construct(set:$n = 9) {}', 'public function __construct() {}', '$c = new C(); return $c->n;', 9],
		'parent_method' => ['protected function read(): int { return 9; }', 'public function get(): int { return parent::read(); }', '$c = new C(); return $c->get();', 9],
		'parent_static' => ['protected static int $n = 2; protected const N = 3; protected static function read(): int { return self::$n; }', 'public static function get(): int { parent::$n = 6; $v = parent::read(); return $v + parent::N; }', 'return C::get();', 9],
	];
	if ($kind === 'class') {
		$cases['protected_peer'] = ['protected int $n = 9;', 'public function read(C $other): int { return $other->n; }', '$c = new C(); $d = new C(); return $c->read($d);', 9];
	}
	foreach ($cases as $name => [$base_members, $child_members, $program, $expected]) {
		$output = inheritance_source($kind . ' B { ' . $base_members . ' } ' . $kind . ' C extends B { ' . $child_members . ' } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}
foreach ([
	'multilevel' => ['class B { int $n = 9; } class M extends B {} final class C extends M {} $c = new C(); return $c->n;', 9],
	'inline_copy' => ['struct B { int $n = 4; } struct C extends B {} $c = new C(); $d = $c; $d->n = 9; return $c->n;', 4],
	'inline_class_copy' => ['class B { int $n = 4; } class C extends B {} $c value<C> = new C(); $d = $c; $d->n = 9; return $c->n;', 4],
	'absent_qualifier' => ['class B { static int $n = 3; static function get(): int { return self::$n; } } class C extends B {} $c C; $c::$n = 9; return $c::get();', 9],
	'protected_descendant' => ['class B { protected int $n = 9; } class C extends B { function read(D $d): int { return $d->n; } } class D extends C {} $c = new C(); $d = new D(); return $c->read($d);', 9],
	'destruction_order' => ['class Log { static int $n = 0; } class B { function __destruct() { Log::$n = Log::$n * 10 + 1; } } class C extends B { function __destruct() { Log::$n = Log::$n * 10 + 2; } } $c = new C(); $c = null; return Log::$n;', 21],
	'parent_order' => ['class Log { static int $n = 0; static function next(): int { self::$n += 1; return self::$n; } } class B { int $n; function __construct(int $a, int $b) { $this->n = $a * 10 + $b; } } class C extends B { function __construct() { parent::__construct(Log::next(), Log::next()); } } $c = new C(); return $c->n;', 12],
	'inherited_multilevel' => ['namespace Types; class B { int $n; function __construct(set:$n = 9) {} } class M extends B {} class C extends M {} $c = new C(); return $c->n;', 9],
	'parent_inherited_constructor' => ['class B { int $n; function __construct(set:$n = 9) {} } class M extends B {} class C extends M { function __construct() { parent::__construct(); } } $c = new C(); return $c->n;', 9],
	'parent_noncopyable_base' => ['struct B { unique<int> $p; int $n; function __construct(set:$n) {} } struct C extends B { function __construct() { parent::__construct(9); } } $c = new C(); return $c->n;', 9],
	'inherited_implicit_default' => ['struct B { int $n; function __construct() { $this->n = 9; } } struct C extends B {} $c C; return $c->n;', 9],
	'inherited_descendant_parameter' => ['class B { int $n; function __construct(C $other) { $this->n = 9; } } class C extends B {} $other C; $c = new C($other); return $c->n;', 9],
	'parent_unique_argument' => ['class B { int $n; function __construct(unique<int> $input) { $taken = move($input); $this->n = isset($taken) ? 1 : 9; } } class C extends B { function __construct(unique<int> $input) { parent::__construct(move($input)); } } $p unique<int>; $c = new C(move($p)); return $c->n;', 9],
	'parent_reference_return' => ['class B { protected function &select(int &$n): int { return $n; } } class C extends B { function &get(int &$n): int { return parent::select($n); } } $c = new C(); $n = 1; $v =& $c->get($n); $v = 9; return $n;', 9],
] as $name => [$source, $expected]) {
	$output = inheritance_source($source);
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => $expected];
	}
}
foreach ([
	['class B { protected int $n; } class C extends B {} $c = new C(); return $c->n;', 'Protected'],
	['class B { private int $n; } class C extends B { function read(): int { return $this->n; } }', 'private property'],
	['class B { protected int $n; } class C extends B { function read(B $b): int { return $b->n; } }', 'descendant receiver'],
	['class B { protected function get(): int { return 1; } } class C extends B { function read(B $b): int { return $b->get(); } }', 'descendant receiver'],
	['class B { protected int $n; } class S extends B {} class C extends B { function read(S $s): int { return $s->n; } }', 'descendant receiver'],
	['class B { int $n; } class C extends B { int $n; }', 'redeclaration'],
	['class B { private function get(): int { return 1; } } class C extends B { function get(): int { return 2; } }', 'redeclaration'],
	['class B { const N = 1; } class C extends B { const N = 2; }', 'redeclaration'],
	['class B { public function __construct(int $n) {} } class C extends B { public function __construct() {} }', 'argument'],
	['class B { public function __construct() {} } class C extends B { public function __construct() { $n = 1; parent::__construct(); } }', 'ordinary method'],
	['class B { public function __construct() {} } class C extends B { public function __construct() { parent::__construct(); parent::__construct(); } }', 'ordinary method'],
	['class B { public function __construct(int $n) {} } class C extends B { int $n = 1; public function __construct() { parent::__construct($this->n); } }', 'instance body'],
	['class B { protected function read(): int { return 1; } } class C extends B { public static function get(): int { return parent::read(); } } return C::get();', 'initialized $this'],
	['class B { private function read(): int { return 1; } } class C extends B { public function get(): int { return parent::read(); } }', 'Private method'],
	['class B { function __construct() {} } class C extends B { function __construct() { if (true) { parent::__construct(); } } }', 'ordinary method'],
	['class B { function __construct() {} } class C extends B { function get(): void { parent::__construct(); } }', 'ordinary method'],
	['class C { function get(): int { return parent::read(); } }', 'concrete base'],
	['class B { int $n; } data class C extends B {}', 'Synthesized derived data requires'],
	['struct B { unique<int> $p; } struct C extends B {} $c = new C(); $d = $c;', 'copyable_value'],
] as [$source, $message]) {
	try { inheritance_source($source); throw new \LogicException('Expected rejection: ' . $message); }
	catch (\RuntimeException $error) {
		if (!str_contains(strtolower($error->getMessage()), strtolower($message))) { throw $error; }
	}
}
$directory = sys_get_temp_dir() . '/inheritance-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$main_path = $directory . '/main.phs';
try {
	$base_text = 'namespace Types; class B { int $n = 7; static int $v = 9; }';
	$child_text = 'namespace Types; class C extends B { int $own; }';
	file_put_contents($base_path, $base_text);
	file_put_contents($child_path, $child_text);
	file_put_contents($main_path, 'use Types\\C; $c = new C(); $n = $c->n; return $n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	foreach ([str_replace('int $n', 'private int $n', $base_text),
		str_replace('int $n', 'int $own; int $n', $base_text),
		str_replace('class B', 'final class B', $base_text)] as $invalid) {
		file_put_contents($base_path, $invalid);
		foreach ([[$base_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid inheritance survived update/retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($base_path, $base_text);
		$compiler->update_cpp([$base_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Inheritance repair changed output'); }
	}
	file_put_contents($base_path, str_replace('= 7', '= 8', $base_text));
	$compiler->update_cpp([$base_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh/incremental inheritance disagrees'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/cross_file.cpp';
		file_put_contents($path, native_record_export());
		$results[] = ['path' => $path, 'exit_code' => 8];
	}
	// Construction initialization is a body fragment, never signature text.
	$base_text = 'namespace Types; class B { int $n; function __construct(set:$n = 7) {} }';
	$child_text = 'namespace Types; class C extends B { function __construct() { parent::__construct(8); } }';
	file_put_contents($base_path, $base_text);
	file_put_contents($child_path, $child_text);
	$compiler->update_cpp([$base_path, $child_path]);
	$record = object_cast(Model::$namespaces->find('Types')->published_scope()->source_types_named('C')[0], collected_record::class);
	$method = $record->syntax()->methods[0];
	$signature = $method->occurrence()->preparation_work_owner();
	$signature_version = $signature->version;
	$body = $method->require_body()->work();
	$body_version = $body->version;
	$caller = Model::$prepared_entry->executable_body()->work();
	$caller_version = $caller->version;
	$headers = [];
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header) { $headers[$artifact->file_name] = $artifact->text; }
	}
	$child_text = str_replace('(8)', '(9)', $child_text);
	file_put_contents($child_path, $child_text);
	$compiler->update_cpp([$child_path]);
	if (($signature->version !== $signature_version) || ($body->version !== $body_version + 1)
		|| ($caller->version !== $caller_version)) { throw new \LogicException('Parent initializer edit invalidated the wrong work'); }
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header) {
			if ($headers[$artifact->file_name] !== $artifact->text) { throw new \LogicException('Parent initializer edit changed a header'); }
		}
	}
	$incremental = CPP_Output::encode(Model::$cpp_files);
	if (isset($argv[1])) {
		$path = $argv[1] . '/parent_body_edit.cpp';
		file_put_contents($path, native_record_export());
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh parent initialization differs'); }
	file_put_contents($child_path, str_replace('parent::__construct(9);', '', $child_text));
	$compiler->update_cpp([$child_path]);
	file_put_contents($base_path, str_replace('= 7', '= 6', $base_text));
	$compiler->update_cpp([$base_path]);
	if (isset($argv[1])) {
		$path = $argv[1] . '/parent_default_edit.cpp';
		file_put_contents($path, native_record_export());
		$results[] = ['path' => $path, 'exit_code' => 6];
	}
	file_put_contents($base_path, str_replace(' = 7', '', $base_text));
	foreach ([[$base_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Missing base argument survived update/retry'); }
		catch (\RuntimeException $expected) {}
	}
	file_put_contents($base_path, $base_text);
	$compiler->update_cpp([$base_path]);
	// Removing the child constructor selects the original inherited signature.
	file_put_contents($child_path, 'namespace Types; class C extends B {}');
	$compiler->update_cpp([$child_path]);
	if (isset($argv[1])) {
		$path = $argv[1] . '/inherited_constructor_edit.cpp';
		file_put_contents($path, native_record_export());
		$results[] = ['path' => $path, 'exit_code' => 7];
	}
	$incremental = CPP_Output::encode(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Inherited constructor fresh/incremental disagreement'); }
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Concrete class/struct inheritance and protected receiver admission passed\n";
