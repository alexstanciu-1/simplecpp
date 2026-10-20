<?php

/* Authored constructors share class/struct execution and ordinary callable lifecycle. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

/** Export partitioned artifacts; the Python runner owns generated native execution. */
function constructors_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/constructors', '/constructors', '/constructors');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return native_record_export();
}

$results = [];
foreach ([['class', 'value'], ['struct', 'shared'], ['class', 'unique'], ['struct', 'unique']] as [$record_kind, $storage_kind]) {
	$prefix = $record_kind . ' C { public int $n = 9; } ';
	$explicit = constructors_source($prefix . '$c ' . $storage_kind . '<C> = new C(); return $c->n;');
	$short = constructors_source($prefix . '$c ' . $storage_kind . ' = new C(); return $c->n;');
	if ($short !== $explicit) { throw new \LogicException('Storage shortcut differs from explicit construction'); }
}
foreach (['class', 'struct'] as $kind) {
	$cases = [
		'shortcut' => ['public $n = 9; public function __construct(set:$n) {}', '$c ' . ($kind === 'class' ? 'value' : 'shared') . ' = (new C()); return $c->n;', 9],
		'shortcut_unique' => ['public int $n; public function __construct(set:$n) {}', '$c unique = new C(9); return $c->n;', 9],
		'shortcut_order' => ['public int $n; public function __construct(int $a, int $b) { $this->n = $a * 10 + $b; }', 'function bump(int &$n): int { $n++; return $n; } $n = 0; $c unique = new C(bump($n), bump($n)); return $c->n;', 12],
		'positional' => ['public int $n; public function __construct(int $input) { $this->n = $input; }', '$c = new C(9); return $c->n;', 9],
		'set' => ['public int $n; public function __construct(set:$n) { $n = 3; }', '$c = new C(9); return $c->n;', 9],
		'body' => ['public int $n; public function __construct(set:$n) { $this->n = $n + 2; }', '$c = new C(7); return $c->n;', 9],
		'inherited_default' => ['public int $n = 9; public function __construct(set:$n) {}', '$c = new C(); return $c->n;', 9],
		'override_default' => ['public int $n = 7; public function __construct(set:$n = 9) {}', '$c = new C(); return $c->n;', 9],
		'private' => ['private int $age; public int $n; public function __construct(set:$age) { $this->n = $this->age; }', '$c = new C(9); return $c->n;', 9],
		'private_factory' => ['public int $n; private function __construct(set:$n) {} public static function make(): C { return new C(9); }', '$c = C::make(); return $c->n;', 9],
		'private_data_factory' => ['public int $n; private function __construct(set:$n) {} public static function make(): C { $c C = {n:9}; return $c; }', '$c = C::make(); return $c->n;', 9],
		'private_unique_factory' => ['public int $n; private function __construct(set:$n) {} public static function make(): int { $c unique<C> = new C(9); return $c->n; }', 'return C::make();', 9],
		'data_parameters' => ['public int $n; public function __construct(int $input) { $this->n = $input; }', '$c C = {input: 9}; return $c->n;', 9],
		'data_set' => ['public int $n; public function __construct(set:$n = 7) { $this->n = $n + 2; }', '$c C = {}; return $c->n;', 9],
		'container_default' => ['public vector<int> $v = [9]; public function __construct(set:$v) {}', '$c = new C(); $v = $c->v; return $v[0];', 9],
		'container_override' => ['public vector<int> $v = [7]; public function __construct(set:$v = [9]) {}', '$c = new C(); $v = $c->v; return $v[0];', 9],
		'suppressed_default' => ['public hash<int> $h = {x:1,x:2}; public function __construct(set:$h) {}', '$c C = {h: {ok:9}}; $h = $c->h; return $h["ok"];', 9],
		'private_synthetic' => ['private int $secret = 3; public int $n = 9;', '$c C = {}; return $c->n;', 9],
		'unique' => ['public int $n; public function __construct(set:$n) {}', '$c unique<C> = new C(9); return $c->n;', 9],
		'data_unique' => ['public int $n; public function __construct(set:$n) {}', '$c unique<C> = {n:9}; return $c->n;', 9],
		'storage' => ['public int $n; public function __construct(set:$n) {}', '$c ' . ($kind === 'class' ? 'value<C>' : 'shared<C>') . ' = new C(9); return $c->n;', 9],
		'data_storage' => ['public int $n; public function __construct(set:$n) {}', '$c ' . ($kind === 'class' ? 'value<C>' : 'shared<C>') . ' = {n:9}; return $c->n;', 9],
		'order' => ['public int $n; public function __construct(int $a, int $b) { $this->n = $a * 10 + $b; }', 'function bump(int &$n): int { $n += 1; return $n; } $n = 0; $c = new C(bump($n), bump($n)); return $c->n;', 12],
		'data_order' => ['public int $n; public function __construct(int $a, int $b) { $this->n = $a * 10 + $b; }', 'function bump(int &$n): int { $n += 1; return $n; } $n = 0; $c C = {b:bump($n), a:bump($n)}; return $c->n;', 21],
		'shared_handle' => ['public C $peer; public int $n; public function __construct(set:$n, C $other) { $this->peer = $other; }', '$none C; $a = new C(7, $none); $b = new C(9, $a); $b->peer->n = 8; return $a->n;', 8],
		'nullable' => ['public ?int $n = null; public function __construct(set:$n) {}', '$c = new C(); return $c->n ?? 9;', 9],
		'fresh_default' => ['public dynamic $d = {n:9}; public function __construct(set:$d) {}', '$a = new C(); $b = new C(); $da = $a->d; $db = $b->d; $da["n"] = 7; $n int = $db["n"]; return $n;', 9],
	];
	if ($kind === 'struct') { unset($cases['shared_handle']); }
	foreach (['private', 'protected'] as $visibility) {
		foreach (['$c = new C();', '$c C = {};'] as $consumer) {
			try {
				constructors_source('data ' . $kind . ' C { ' . $visibility . ' function __construct() {} } ' . $consumer);
				throw new \LogicException('Constructor visibility bypass');
			}
			catch (\RuntimeException $error) {
				if (!str_contains(strtolower($error->getMessage()), $visibility . ' member')) { throw $error; }
			}
		}
	}
	foreach ($cases as $name => [$body, $program, $expected]) {
		$output = constructors_source('data ' . $kind . ' C { ' . $body . ' } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}

foreach ([
	'contextual_set' => ['data class set { int $n; } data class C { set $item; function __construct(set $item) { $this->item = $item; } } $c C = {item:{n:9}}; return $c->item->n;', 9],
	'enum_set' => ['enum E { case A; case B; } data struct C { public E $e; function __construct(set:$e) {} } $c = new C(E::B); $e = $c->e; return $e === E::B ? 9 : 1;', 9],
	'no_data_opt_in' => ['class C { public int $n; function __construct(set:$n) {} } $c = new C(9); return $c->n;', 9],
] as $name => [$source, $expected]) {
	$output = constructors_source($source);
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => $expected];
	}
}

foreach ([
	['class C { int $n; function __construct(set:$n) {} } $c = new C();', 'missing a required argument'],
	['class C {} $c shared = new C();', 'repeats'],
	['struct C {} $c value = new C();', 'repeats'],
	['class C {} $c unique;', 'direct new'],
	['class C {} function make(): C { return new C(); } $c value = make();', 'direct new'],
	['class C {} $a = new C(); $c unique = $a;', 'direct new'],
	['class C {} $c weak = new C();', 'explicit arguments'],
	['class C { value $v; }', 'resolved type'],
	['function f(unique $v): void {}', 'explicit arguments'],
	['data class C { int $n; function __construct(set:$n) {} } $c C = {};', 'missing required'],
	['data class C { int $n; function __construct(int $input) {} } $c C = {n: 1};', 'unknown record'],
	['class C { function __construct(set:$n) {} }', 'existing instance property'],
	['class C { function __construct(): void {} }', 'without a return type'],
	['class C { private function __construct() {} } $c = new C();', 'Private member'],
	['class C { private int $n; function __construct(set:$n) {} } $c = new C(1); return $c->n;', 'private property'],
	['data class C { private int $n = 1; } $c C = {n: 2};', 'unknown record'],
	['class C { function __construct(int &$n) {} }', 'fixed by-value'],
	['class C { function __construct() {} function __construct() {} }', 'Duplicate constructor'],
	['struct C { function __construct(int $n = 1) {} } $c C;', 'explicit initializer'],
	['class C { function __construct() { $this = 1; } }', 'cannot be declared'],
	['class C { function __construct() { return 1; } }', 'void'],
	['class C { int $x; function __construct(set:$x) {} } $c = new C("wrong");', 'value boundary'],
	['data class C { int $x; function __construct(set:$x) {} } $c C = {x:1, x:2};', 'duplicate record'],
	['class C { int $x; function __construct(set:$x = 1, int $y) {} }', 'required'],
	['enum E { case A; } data class C { private E $e; }', 'Non-public property'],
] as [$source, $message]) {
	try {
		constructors_source($source);
		throw new \LogicException('Unexpected constructor acceptance: ' . $source);
	}
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), $message)) { throw new \LogicException('Wrong diagnostic: ' . $error->getMessage()); }
	}
}

// Constructor bodies do not invalidate callers or type headers; signatures and defaults do.
$directory = sys_get_temp_dir() . '/constructors-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; data class C { private int $hidden = 1; public vector<int> $v = [1]; public int $n; public function __construct(set:$n = 7) { $this->n = $n + 2; } }';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, 'use Types\C; $c C = {}; return $c->n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$record = object_cast(Model::$namespaces->find('Types')->published_scope()->source_types_named('C')[0], collected_record::class);
	$method = $record->syntax()->methods[0];
	$signature = $method->occurrence()->preparation_work_owner();
	$body = $method->require_body()->work();
	$signature_version = $signature->version;
	$body_version = $body->version;
	$caller = Model::$prepared_entry->executable_body()->work();
	$caller_version = $caller->version;
	$headers = [];
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header) { $headers[$artifact->file_name] = $artifact->text; }
	}
	file_put_contents($type_path, str_replace('$n + 2', '$n + 3', $declaration));
	$compiler->update_cpp([$type_path]);
	if (($method !== $record->syntax()->methods[0]) || ($signature->version !== $signature_version)
		|| ($body !== $method->require_body()->work()) || ($body->version !== $body_version + 1)
		|| ($caller->version !== $caller_version)) {
		throw new \LogicException('Body-only constructor edit lost work identity or invalidated its signature');
	}
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header) {
			if ($headers[$artifact->file_name] !== $artifact->text) { throw new \LogicException('Body-only constructor edit changed a header'); }
		}
	}
	foreach ([str_replace('set:$n = 7', 'set:$missing', $declaration), str_replace('public function', 'private function', $declaration)] as $invalid) {
		file_put_contents($type_path, $invalid);
		foreach ([[$type_path], []] as $changes) {
			try {
				$compiler->update_cpp($changes);
				throw new \LogicException('Invalid constructor survived preparation/retry');
			}
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($type_path, $declaration);
		$compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Constructor repair differs from original'); }
	}
	file_put_contents($type_path, str_replace('set:$n = 7', 'set:$n = 9', $declaration));
	$compiler->update_cpp([$type_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	if ($incremental === $original) { throw new \LogicException('Constructor default edit did not reach omitted caller'); }
	Compiler_Lifecycle::reset();
	$fresh = new Compiler(new cpp_lowering_options(false));
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh constructor output differs from incremental'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Constructor preparation reset changed output'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/incremental.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 11];
	}
	file_put_contents($entry_path, 'use Types\\C; $c value = new C(); return $c->n;');
	$fresh->update_cpp([$entry_path]);
	$shortcut = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($type_path, str_replace('data class C', 'data struct C', str_replace('set:$n = 7', 'set:$n = 9', $declaration)));
	foreach ([[$type_path], []] as $changes) {
		try { $fresh->update_cpp($changes); throw new \LogicException('Shortcut retained redundant storage after declaration-kind edit'); }
		catch (\RuntimeException $expected) {}
	}
	file_put_contents($type_path, str_replace('set:$n = 7', 'set:$n = 9', $declaration));
	$fresh->update_cpp([$type_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $shortcut) { throw new \LogicException('Shortcut repair changed output'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $shortcut) { throw new \LogicException('Shortcut preparation reset changed output'); }
	file_put_contents($entry_path, 'use Types\\C; $c C = {}; return $c->n;');
	$fresh->update_cpp([$entry_path]);
	$current_record = object_cast(Model::$namespaces->find('Types')->published_scope()->source_types_named('C')[0], collected_record::class);
	$removed_method = $current_record->syntax()->methods[0];
	$removed_signature = $removed_method->occurrence()->preparation_work_owner();
	$removed_body = $removed_method->require_body()->work();
	file_put_contents($type_path, 'namespace Types; data class C { private int $hidden = 1; public int $n; }');
	$fresh->update_cpp([$type_path]);
	if (isset(Model::$cpp_output_program->fragments[$removed_signature]) || isset(Model::$cpp_output_program->fragments[$removed_body])) {
		throw new \LogicException('Removed constructor retained backend fragments');
	}
	file_put_contents($type_path, str_replace('set:$n = 7', 'set:$n = 9', $declaration));
	$fresh->update_cpp([$type_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Readded constructor differs from fresh output'); }
	$factory = 'namespace Types; data class C { int $n; public function __construct(set:$n) {} static function make(): C { $c C = {n:9}; return $c; } }';
	file_put_contents($type_path, $factory);
	file_put_contents($entry_path, 'use Types\\C; $c = C::make(); return $c->n;');
	$fresh->update_cpp([$type_path, $entry_path]);
	foreach (['private', 'protected', 'public'] as $visibility) {
		file_put_contents($type_path, str_replace('public function __construct', $visibility . ' function __construct', $factory));
		$fresh->update_cpp([$type_path]);
		$edited = CPP_Output::encode(Model::$cpp_files);
		if (isset($argv[1])) {
			$path = $argv[1] . '/visibility_' . $visibility . '.cpp';
			file_put_contents($path, native_record_export());
			$results[] = ['path' => $path, 'exit_code' => 9];
		}
		Compiler_Lifecycle::reset();
		$fresh = new Compiler(new cpp_lowering_options(false)); $fresh->init([$directory]); $fresh->select_entry('main', 'main.phs'); $fresh->exec_cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $edited) { throw new \LogicException('Visibility edit differs from fresh construction'); }
	}
}
finally {
	unlink($type_path);
	unlink($entry_path);
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n");
}
echo "Constructor execution exports and preparation rejection passed\n";
