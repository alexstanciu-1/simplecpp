<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function constant_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function constant_project(array $sources): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/constants', '/constants', '/constants');
	foreach ($sources as $path => $text) {
		$file = new file(); $file->path = $path; $file->content = $text;
		Source_Registry::add($module, $file);
	}
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(); $compiler->select_entry('main', 'main.phs');
	return $compiler;
}

function constant_edit(Compiler $compiler, source_record $record, string $text): void
{
	$record->file->content = $text; $record->file->changes = SYNC_CHANGED;
	$record->changes = change_state::changed;
	$compiler->tokenize(); $compiler->parse();
}

function constant_failure(Compiler $compiler, string $message): void
{
	$failed = false;
	try { $compiler->prepare(true); }
	catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), $message); }
	constant_check($failed, 'Missing rejection: ' . $message);
}

$cases = [
	'forward' => ['main.phs' => 'return ANSWER; const ANSWER = NEXT; const NEXT = 7;'],
	'scalar' => ['main.phs' => 'const FLAG = true; const TEXT = "a\0b"; const RATE = -(-1.5); const NEG = -7; const POS = +NEG; if (FLAG) { if (TEXT) { if (RATE) { if (POS === -7) { return 7; } } } } return 1;'],
	'imports' => ['main.phs' => 'namespace App; use const Lib\{LIMIT as size, TITLE}; use Lib\FLAG; const LOCAL = size; function read(): int { return LOCAL; } if (FLAG) { if (TITLE) { return read(); } } return 1;',
		'lib.phs' => 'namespace Lib; const LIMIT = 7; const TITLE = "ok"; const FLAG = true;'],
	'qualified' => ['main.phs' => 'namespace App; const ANSWER = Sub\VALUE; return ANSWER;',
		'lib.phs' => 'namespace App\Sub; const VALUE = \Root\VALUE;', 'other.phs' => 'namespace Root; const VALUE = 7;'],
	'reopened' => ['main.phs' => 'namespace App { const VALUE = 7; } namespace App { return VALUE; }'],
	'labels' => ['main.phs' => 'const LABEL = -7; const ANSWER = 7; $v = LABEL; switch ($v) { case LABEL: return match ($v) { LABEL => ANSWER, default => 1 }; default: return 1; }'],
	'exact' => ['main.phs' => 'const MAX = PHP_INT_MAX; const MIN = -9223372036854775808; if (MAX === 9223372036854775807 && MIN < 0) { return 7; } return 1;'],
];
$outputs = [];
foreach ($cases as $name => $sources) {
	$compiler = constant_project($sources); $compiler->exec_cpp();
	if ($name === 'scalar') {
		$constants = Model::$global_scope;
		constant_check(object_cast($constants->constants_named('TEXT')[0]->value(), string_constant_value::class)->value === "a\0b", 'String bytes lost');
		constant_check(object_cast($constants->constants_named('RATE')[0]->value(), float_constant_value::class)->decimal === '1.5', 'Floating signs lost');
	}
	$outputs['constant_' . $name] = CPP_Output::encode(Model::$cpp_files);
}

foreach ([
	'const A = B; const B = A;' => 'Cyclic',
	'const A = A;' => 'Cyclic',
	'const A = 1; const A = 2;' => 'duplicate constant',
	'const A = MISSING;' => 'resolved constant',
	'const A = 1 + 2;' => 'constant initializer',
	'function f(): int { return 1; } const A = f();' => 'constant initializer',
	'const A = null;' => 'constant initializer',
	'const A = [1];' => 'constant initializer',
	'const A = false_sentinel;' => 'source constants require',
	'const A = -true;' => 'numeric sign requires',
	'const A = !false;' => 'numeric unary signs',
	'const A = -(-9223372036854775808);' => 'negation exceeds',
] as $text => $message) {
	$compiler = constant_project(['main.phs' => $text]);
	$compiler->tokenize(); $compiler->parse(); constant_failure($compiler, $message);
}

// Constant consumers use ordinary declaration dependencies, including transitive aliases.
$main = 'namespace App; use const Lib\LIMIT; const ANSWER = LIMIT; function read(): int { return ANSWER; } return read();';
$library = 'namespace Lib; const LIMIT = 7;';
$compiler = constant_project(['main.phs' => $main, 'lib.phs' => $library]); $compiler->exec_cpp();
$source = Model::sources()[0]; $lib = Model::sources()[1];
$alias = $source->parsed->root->single_body()->declarations[0];
$definition = $lib->parsed->root->single_body()->declarations[0];
$identity = $definition->occurrence(); $work = $identity->preparation; $version = $work->version;
$initializer = $definition->initializer;
$original = CPP_Output::encode(Model::$cpp_files);
constant_edit($compiler, $lib, $library); $compiler->prepare(true); $compiler->cpp();
constant_check($definition->initializer === $initializer && $identity->preparation === $work && $work->version === $version,
	'No-op replaced constant syntax/work');
constant_check(CPP_Output::encode(Model::$cpp_files) === $original, 'No-op changed output');
$compiler->cleanup_tokens(); $compiler->prepare(true); $compiler->cpp();
constant_check(CPP_Output::encode(Model::$cpp_files) === $original, 'Token cleanup changed constant facts/output');

constant_edit($compiler, $lib, 'namespace Lib; const LIMIT = 9;');
$failed = false;
try { $compiler->cpp(); } catch (\RuntimeException $error) { $failed = true; }
constant_check($failed, 'Stale constant output accepted');
$compiler->prepare(true); $compiler->cpp();
constant_check($definition->occurrence() === $identity && $identity->preparation === $work, 'Edit replaced constant identity');
constant_check(object_cast($alias->require_preparation(), integer_constant_value::class)->decimal === '9', 'Transitive consumer retained old value');
constant_check(CPP_Output::encode(Model::$cpp_files) !== $original, 'Value edit left stale output');

// A type edit invalidates the unchanged signature/body consumer, then recovers cleanly.
constant_edit($compiler, $lib, 'namespace Lib; const LIMIT = "bad";');
$type_failed = false;
try { $compiler->prepare(true); } catch (\RuntimeException $error) { $type_failed = true; }
constant_check($type_failed, 'Type edit failed to invalidate int return');
constant_edit($compiler, $lib, $library); $compiler->prepare(true); $compiler->cpp();
constant_check(CPP_Output::encode(Model::$cpp_files) === $original, 'Type-failure repair differs from clean output');

constant_edit($compiler, $lib, 'namespace Lib;'); constant_failure($compiler, 'import');
constant_check($work->change_status === change_state::deleted && count($work->dependents) === 0,
	'Removed constant retained dependency edges');
constant_edit($compiler, $lib, $library); $compiler->prepare(true); $compiler->cpp();
constant_check(CPP_Output::encode(Model::$cpp_files) === $original, 'Deletion repair differs from clean output');

// Existing ready declarations can acquire a cycle on edit; retry must rebuild the graph.
$compiler = constant_project(['main.phs' => 'const A = B; const B = 7; return A;']); $compiler->exec_cpp();
$source = Model::sources()[0];
constant_edit($compiler, $source, 'const A = B; const B = A; return A;'); constant_failure($compiler, 'Cyclic');
constant_edit($compiler, $source, 'const A = B; const B = 7; return A;'); $compiler->prepare(true); $compiler->cpp();
$outputs['constant_cycle_repair'] = CPP_Output::encode(Model::$cpp_files);

// Constant declaration must not be silently dropped by the parked backend.
$failed = false;
try { $compiler->llvm(); } catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'Source constants'); }
constant_check($failed, 'LLVM silently accepted source constants');

// A failed new declaration has a valid partial identity that the next parse can repair.
$compiler = constant_project(['main.phs' => 'return 7;']); $compiler->exec_cpp();
$source = Model::sources()[0]; $parse_failed = false;
try { constant_edit($compiler, $source, 'const NEW_VALUE = ; return 7;'); }
catch (\RuntimeException $error) { $parse_failed = true; }
constant_check($parse_failed, 'Malformed initializer passed');
constant_edit($compiler, $source, 'const NEW_VALUE = 7; return NEW_VALUE;'); $compiler->prepare(true); $compiler->cpp();
$outputs['constant_parse_repair'] = CPP_Output::encode(Model::$cpp_files);

$compiler = constant_project(['main.phs' => 'namespace A; const UNUSED = 1; return 7;',
	'lib.phs' => 'namespace A; const UNUSED = 2;']);
$compiler->tokenize(); $compiler->parse(); constant_failure($compiler, 'duplicate constant');

// A previously absent qualified name observes the exact pool, then recovers on addition.
$compiler = constant_project(['main.phs' => 'return \Later\VALUE;', 'lib.phs' => 'namespace Later;']);
$compiler->tokenize(); $compiler->parse(); constant_failure($compiler, 'resolved constant');
$lib = Model::sources()[1];
constant_edit($compiler, $lib, 'namespace Later; const VALUE = 7;'); $compiler->prepare(true); $compiler->cpp();
$outputs['constant_missing_repair'] = CPP_Output::encode(Model::$cpp_files);
constant_edit($compiler, $lib, 'namespace Elsewhere; const VALUE = 7;'); constant_failure($compiler, 'resolved constant');
constant_check(Model::$namespaces->find('Later')->published_scope()->constants_named('VALUE') === [],
	'Namespace move left an old constant publication');

// Same effective value keeps dependent work settled even when initializer syntax changes.
$compiler = constant_project(['main.phs' => 'const VALUE = 7; return VALUE;']); $compiler->exec_cpp();
$source = Model::sources()[0]; $body = $source->parsed->root->single_body()->body;
$version = $body->work()->version;
constant_edit($compiler, $source, 'const VALUE = +7; return VALUE;'); $compiler->prepare(true); $compiler->cpp();
constant_check($body->work()->version === $version, 'Equal constant value rebuilt its consumer');

// CH08 boundaries are explicit non-admission, not accidental successful emission.
foreach ([
	'require "lib.phs";', 'require_once "lib.phs";', 'include "lib.phs";', 'include_once "lib.phs";',
	'namespace A { namespace B { const VALUE = 1; } }',
	'namespace A; return 1; namespace B; return 2;',
] as $text) {
	$compiler = constant_project(['main.phs' => $text]); $rejected = false;
	try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { $rejected = true; }
	constant_check($rejected, 'Unsupported CH08 boundary emitted: ' . $text);
}

if (isset($argv[2])) { file_put_contents($argv[2], json_encode($cases, JSON_THROW_ON_ERROR)); }
if (isset($argv[1])) {
	foreach ($outputs as $name => $output) { file_put_contents($argv[1] . '/' . $name . '.json', $output); }
}
echo "Source constants: scalar values, lookup/imports, labels, rejection, no-op/edit/deletion and cycle recovery passed\n";
