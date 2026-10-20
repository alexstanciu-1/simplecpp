<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host proof: valid source operations must not silently select native template interfaces.
$directory = $argv[1];
mkdir($directory, 0777, true);
$path = $directory . '/main.phs';
$snapshot = static function (): array {
	$result = [];
	foreach (Model::$cpp_files as $artifact) { $result[$artifact->file_name] = $artifact->text; }
	return $result;
};
$cases = [
	'local' => '$f function<int()> = fn(): int => 1; return $f();',
	'parameter' => 'function use_callback(function<int()> $f): int { return $f(); } return 0;',
	'result' => 'function callback(): function<int()> { return fn(): int => 1; } return 0;',
	'field' => 'struct Record { public function<int()> $callback; } return 0;',
	'nested' => '$items vector<hash<function<int()>>> = []; return 0;',
];
foreach ($cases as $name => $source) {
	$cases[$name] = [$source, 'std::function<', 'callable storage requires a concrete callback transport'];
}
$object_prefix = 'interface I { function read(): int; } class B implements I { function read(): int { return 1; } } class C extends B {} ';
$cases['object_test'] = [$object_prefix . '$b B; return $b instanceof C ? 1 : 0;', '::scpp::object_is<', 'object type operation requires concrete transport'];
$cases['object_cast'] = [$object_prefix . '$b B; $c = object_cast($b, C::class); return 0;', '::scpp::checked_object_cast<', 'object type operation requires concrete transport'];
$cases['derive_weak'] = ['class C {} $c C; $w = weak($c); return 0;', '::scpp::weak(', 'ownership observation requires concrete transport'];
$cases['lock_weak'] = ['class C {} $w weak<C>; $c = lock($w); return 0;', '.lock()', 'ownership observation requires concrete transport'];
$cases['throw'] = ['$e Exception; throw $e;', '::scpp::php::make_thrown(', 'throw operation requires concrete transport'];
$cases['catch'] = ['try { return 0; } catch (Exception $e) { return 1; }', '::scpp::php::catch_as<', 'catch operation requires concrete transport'];
$cases['finally'] = ['try { return 0; } finally {}', 'std::optional<', 'finally operation requires concrete transport'];
foreach ($cases as $name => [$source, $native_spelling, $diagnostic]) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $source);
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$output = $directory . '-output-' . $name;
	// Publication workers own one attempt; every retry acquires a fresh worker.
	$emit = static function (bool $changed = false) use ($compiler, $output, $path): void {
		$publication = new Output_Publication($output);
		try {
			if ($changed) { $compiler->update_cpp([$path], $publication); }
			else { $compiler->exec_cpp($publication); }
		}
		finally { $publication->close(); }
	};
	$emit();
	$ordinary = $snapshot();
	$ordinary_manifest = Output_Publication::require_ready($output);
	if (!str_contains(implode("\n", $ordinary), $native_spelling)) {
		throw new \LogicException('Missing ordinary representation: ' . $name);
	}
	$compiler->cpp_lowering->prelower_templates = true;
	$compiler->cpp_lowering->concrete_type_substitution = true;
	$rejected = false;
	try { $emit(); }
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), 'prelower_templates: ' . $diagnostic)) { throw $error; }
		$rejected = true;
	}
	if (!$rejected || $snapshot() !== []) { throw new \LogicException('Template fallback/partial output: ' . $name); }
	$ready_rejected = false;
	try { Output_Publication::require_ready($output); }
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), 'pending or failed')) { throw $error; }
		$ready_rejected = true;
	}
	if (!$ready_rejected) { throw new \LogicException('Rejected revision remained ready: ' . $name); }

	$compiler->cpp_lowering->prelower_templates = false;
	$emit();
	if ($snapshot() !== $ordinary) { throw new \LogicException('Ordinary recovery changed output: ' . $name); }
	if (Output_Publication::require_ready($output) !== $ordinary_manifest) {
		throw new \LogicException('Ordinary recovery changed manifest: ' . $name);
	}
	file_put_contents($path, '$items vector<int> = []; return 0;');
	$compiler->cpp_lowering->prelower_templates = true;
	$emit(true);
	$repaired = $snapshot();
	$repaired_manifest = Output_Publication::require_ready($output);
	if ($repaired === [] || str_contains(implode("\n", $repaired), $native_spelling)) {
		throw new \LogicException('Source repair retained unsupported output: ' . $name);
	}
	$emit();
	if ($snapshot() !== $repaired) { throw new \LogicException('Repaired replay changed output: ' . $name); }
	if (Output_Publication::require_ready($output) !== $repaired_manifest) {
		throw new \LogicException('Repaired replay changed manifest: ' . $name);
	}
	echo $name . ": ordinary, rejection, mode recovery, source repair, replay and publication readiness passed\n";
}
Compiler_Lifecycle::reset();
