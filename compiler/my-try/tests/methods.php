<?php

/* Fixed instance calls share functions' argument/body contracts and explicit receiver lifetimes. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function methods_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/methods', '/methods', '/methods');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'basic' => ['public int $n = 2; function add(int $x = 7): int { $old = $this->n; $this->n = $old + $x; return $this->n; }', '$c = new C(); return $c->add();', 9],
		'private' => ['private int $n = 9; private function read(): int { return $this->n; } public function get(): int { return $this->read(); }', '$c = new C(); return $c->get();', 9],
		'same_name' => ['public int $get = 9; function get(): int { return $this->get; }', '$c = new C(); return $c->get();', 9],
		'void' => ['public int $n; function put(int $n): void { $this->n = $n; }', '$c = new C(); $c->put(9); return $c->n;', 9],
		'constructor_call' => ['int $n; function __construct() { $this->put(9); } private function put(int $n): void { $this->n = $n; } function get(): int { return $this->n; }', '$c = new C(); return $c->get();', 9],
		'recursive' => ['function count(int $n): int { if ($n === 0) { return 0; } $previous = $this->count($n - 1); return 1 + $previous; }', '$c = new C(); return $c->count(9);', 9],
		'order' => ['function join(int $a, int $b): int { return $a * 10 + $b; }', 'function bump(int &$n): int { $n += 1; return $n; } $n = 0; $c = new C(); return $c->join(bump($n), bump($n));', 12],
		'storage' => ['int $n; function put(int $n): void { $this->n = $n; }', '$c ' . ($kind === 'class' ? 'value<C>' : 'shared<C>') . ' = new C(); $c->put(9); return $c->n;', 9],
		'alias' => ['int $n; function put(int $n): void { $this->n = $n; }', '$c = new C(); $a =& $c; $a->put(9); return $c->n;', 9],
		'data' => ['int $n; function get(): int { return $this->n; }', '$c C = {n:9}; return $c->get();', 9],
	] as $name => [$body, $program, $expected]) {
		$output = methods_source('data ' . $kind . ' C { ' . $body . ' } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}

foreach ([
	'held_receiver' => ['class C { function get(int $n): int { return $n; } } function drop(C &$c): int { $c = null; return 9; } $c = new C(); $w = weak($c); $n = $c->get(drop($c)); if (lock($w)) { return 1; } return $n;', 9],
	'receiver_once' => ['class C { function get(int $n): int { return $n; } } function pick(int &$n): C { $n += 1; return new C(); } function bump(int &$n): int { $n += 1; return $n; } $n = 0; $r = pick($n)->get(bump($n)); return $r;', 2],
	'locked' => ['class C { function get(): int { return 9; } } $c = new C(); $w = weak($c); return lock($w)->get();', 9],
	'absent' => ['class C { function get(): int { return 9; } } $c C; return $c->get();', 0],
	'private_peer' => ['class C { private function get(): int { return 9; } function read(C $other): int { return $other->get(); } } $c = new C(); return $c->read($c);', 9],
] as $name => [$source, $expected]) {
	$output = methods_source($source);
	if ($name === 'absent') {
		// Test-only process boundary proves the checked exception, not a crash exit code.
		foreach (Model::$cpp_files as $artifact) {
			if ($artifact->identity === 'entry') {
				$instrumented = str_replace('int main()', 'int main() try', $artifact->text)
					. 'catch (const scpp::runtime_error& error) { return std::string(error.what()).find("requires a present shared pointer") != std::string::npos ? 0 : 2; }' . "\n";
				$output = str_replace($artifact->text, $instrumented, $output);
			}
		}
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => $expected];
	}
}

foreach ([
	['class C { function get(): int { return 1; } } $c unique<C> = new C(); return $c->get();', 'lifetime integration'],
	['class C { function get(): int { return 1; } } function f(const C &$c): int { return $c->get(); }', 'read-only'],
	['struct C { function get(): int { return 1; } } struct D { C $c; } $d = new D(); return $d->c->get();', 'Projected'],
	['class C { private function get(): int { return 1; } } $c = new C(); return $c->get();', 'Private method'],
	['class C { function get(): int { return 1; } } $c = new C(); return $c->missing();', 'one ordinary method'],
	['class C { function get(int $n): int { return $n; } } $c = new C(); return $c->get();', 'missing a required'],
	['class C { function get(): int { return 1; } function get(): int { return 2; } }', 'Duplicate method'],
	['class C { static function get(): int { return 1; } }', 'Expected field'],
	['class C { function get(int &$n): int { return $n; } }', 'fixed by-value'],
	['class C { int $n; function get(set:$n): int { return $n; } }', 'fixed by-value'],
	['class C { function get(): int {} }', 'without returning'],
	['class C { function get(int $n): int { return $n; } } function consume(unique<C> $c): int { return 1; } $c unique<C> = new C(); return $c->get(consume(move($c)));', 'lifetime integration'],
	['class C { function get(): int { return 1; } } $c = new C(); $w = weak($c); return $w->get();', 'lifetime integration'],
	['struct C { function get(): int { return 1; } } return (new C())->get();', 'stable whole storage'],
	['class C { function go(int $n): int { return f($n); } } function &f(int &$n): int { $c = new C(); $v = $c->go($n); return $n; }', 'recursive reference-return'],
] as [$source, $message]) {
	$failed = false;
	try { methods_source($source); }
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), $message)) { throw $error; }
		$failed = true;
	}
	if (!$failed) { throw new \RuntimeException('Expected method rejection: ' . $message); }
}

$directory = sys_get_temp_dir() . '/methods-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { public function get(int $n = 7): int { return $n + 2; } }';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, 'use Types\C; $c = new C(); return $c->get();');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$record = object_cast(Model::$namespaces->find('Types')->published_scope()->source_types_named('C')[0], collected_record::class);
	$method = $record->syntax()->methods[0];
	$signature = $method->occurrence()->preparation_work_owner();
	$version = $signature->version;
	$caller = Model::$prepared_entry->executable_body()->work();
	$caller_version = $caller->version;
	$headers = [];
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header) { $headers[$artifact->file_name] = $artifact->text; }
	}
	file_put_contents($type_path, str_replace('$n + 2', '$n + 3', $declaration));
	$compiler->update_cpp([$type_path]);
	if (($signature->version !== $version) || ($caller->version !== $caller_version)) {
		throw new \LogicException('Method body edit invalidated signature or caller');
	}
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header) {
			if ($headers[$artifact->file_name] !== $artifact->text) { throw new \LogicException('Method body edit changed header'); }
		}
	}
	foreach ([str_replace('public function', 'private function', $declaration), str_replace('get(', 'missing(', $declaration)] as $invalid) {
		file_put_contents($type_path, $invalid);
		foreach ([[$type_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid method survived preparation/retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($type_path, $declaration);
		$compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Method repair changed output'); }
	}
	file_put_contents($type_path, str_replace('$n = 7', '$n = 9', $declaration));
	$compiler->update_cpp([$type_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	if ($incremental === $original) { throw new \LogicException('Method default failed to invalidate caller'); }
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh/incremental method mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Method reset changed output'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/incremental.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 11];
	}
	$current = object_cast(Model::$namespaces->find('Types')->published_scope()->source_types_named('C')[0], collected_record::class);
	$removed = $current->syntax()->methods[0];
	$removed_signature = $removed->occurrence()->preparation_work_owner();
	$removed_body = $removed->require_body()->work();
	file_put_contents($type_path, 'namespace Types; class C {}');
	file_put_contents($entry_path, 'return 0;');
	$fresh->update_cpp([$type_path, $entry_path]);
	if (isset(Model::$cpp_output_program->fragments[$removed_signature]) || isset(Model::$cpp_output_program->fragments[$removed_body])) {
		throw new \LogicException('Retired method retained fragments');
	}
	file_put_contents($type_path, str_replace('$n = 7', '$n = 9', $declaration));
	file_put_contents($entry_path, 'use Types\C; $c = new C(); return $c->get();');
	$fresh->update_cpp([$type_path, $entry_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Readded method changed output'); }
}
finally {
	unlink($type_path);
	unlink($entry_path);
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "methods: PASS\n";
