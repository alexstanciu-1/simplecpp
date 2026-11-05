<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection, ast_while_statement, ast_do_while_statement};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function definition(flavor $flavor, string $name, string $parameters, string $type, string $body): string
{
	return ($flavor === flavor::cpp ? "$type $name($parameters)" : "function $name($parameters): $type") . '{' . $body . '}';
}

function local(flavor $flavor, string $name, string $type, string $value): string
{
	return match ($flavor) {
		flavor::php => "$$name $type=$value;",
		flavor::cpp => "$type $name=$value;",
		flavor::js => "let $name: $type=$value;",
	};
}

/** Check unsupported syntax or semantic boundaries before invoking native compilation. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $params, string $type, string $body): string => definition($flavor, $name, $params, $type, $body);
	$param = $flavor === flavor::php ? 'bool &$run' : ($flavor === flavor::cpp ? 'bool &run' : '&run: bool');
	$byte_param = $flavor === flavor::php ? 'byte $n' : ($flavor === flavor::cpp ? 'byte n' : 'n: byte');
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('loops', $source, $flavor), new entry_target(31));
	$once = $prepare('while (false) { echo 1; } do { echo 2; } while (false); return 8;');
	verify($once->body()->statement_visits === 5 && $once->body()->expression_visits === 5, 'Loop syntax was prepared more than once');
	verify($once->body()->statements[0] instanceof ast_while_statement && $once->body()->statements[1] instanceof ast_do_while_statement, 'Loop node identity');
	$loop = $once->body()->statements[1];
	verify($loop->condition->parent === $loop && $loop->body->parent === $loop, 'Loop syntax ownership');
	verify($loop->body->scope->parent === $once->body()->scope, 'Loop body scope parent');
	verify(str_ends_with($loop->source_text(), ';'), 'Do-while terminator omitted from span');
	verify(Syntax_Inspection::file($once->file)['statements'][1]['kind'] === 'do_while', 'Syntax inspection');
	verify(Compilation_Inspection::body($once->body())['statements'][0]['kind'] === 'while', 'Preparation inspection');

	$test = $fn('test', $param, 'string', 'echo "C"; if (' . $var('run') . ') {' . $var('run') . '=false; return "yes"; } else { return "0"; }');
	$code = $test . $fn('finish', '', 'int', 'do { return 8; } while (false);')
		. 'while (false) { echo "WRONG"; } do { echo "O"; } while (false);'
		. $decl('run', 'bool', 'true') . 'while (test(' . $var('run') . ')) { echo "W"; }'
		. $var('run') . '=true; do { echo "D"; } while (test(' . $var('run') . '));'
		. $decl('outer', 'bool', 'true') . $decl('twice', 'bool', 'true')
		. 'while (' . $var('outer') . ') {' . $decl('inner', 'bool', 'true')
		. 'do { echo "N";' . $var('inner') . '=false; } while (' . $var('inner') . ');'
		. 'if (' . $var('twice') . ') {' . $var('twice') . '=false; } else {' . $var('outer') . '=false; } }'
		. $decl('shadow', 'bool', 'false') . 'do {' . $decl('shadow', 'bool', 'true') . 'echo "S"; } while (' . $var('shadow') . ');';
	foreach (['int' => ['8', '0'], 'float' => ['1.5', '-0.0'], 'string' => ['"false"', '"0"']] as $type => [$value, $zero]) {
		$code .= $decl('v_' . $type, $type, $value) . 'while (' . $var('v_' . $type) . ') { echo "T";' . $var('v_' . $type) . '=' . $zero . '; }';
	}
	$code .= 'return finish();';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Loop lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('OCWCDCDCNNSTTT')];
	foreach ($entry->file->functions() as $function) {
		Function_Preparation::reset($function);
	}
	Function_Preparation::reset_body($entry->file->main()->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Loop reset/reprepare differs');

	foreach ([
		['while (null) {} return 8;', 'Unsupported condition type'],
		[$decl('b', 'byte', '1') . 'do {} while (' . $var('b') . '); return 8;', 'Unsupported condition type'],
		['do { return 8; } while (null);', 'Unsupported condition type'],
		['while (false) { missing(); } return 8;', 'Unknown function'],
		['while (true) { return 8; }', 'Entry slice requires'],
		[$fn('bad', '', 'int', 'while (true) { return 8; }') . 'return 8;', 'requires a value return'],
		['do {' . $decl('inside', 'bool', 'false') . '} while (' . $var('inside') . '); return 8;', 'Unknown or not-yet-initialized'],
		['while (false) {' . $decl('inside', 'int', '8') . '} echo ' . $var('inside') . '; return 8;', 'Unknown or not-yet-initialized'],
		['while (false) {' . $decl('x', 'int', '1') . $decl('x', 'int', '2') . '} return 8;', 'Duplicate local'],
		['break; return 8;', 'requires an enclosing loop'],
		['continue; return 8;', 'requires an enclosing loop'],
		['while (false) echo 1; return 8;', 'braced loop body'],
		['do echo 1; while (false); return 8;', 'braced loop body'],
		['do {} while (false) return 8;', 'do-while terminator'],
	] as [$invalid, $message]) {
		rejects(fn() => $prepare($invalid), $message);
	}
	// A second iteration must not reuse the initializer's byte-range proof.
	$setup = $decl('x', 'int', '8') . $decl('run', 'bool', 'true');
	$body = $decl('narrow', 'byte', $var('x')) . $var('x') . '=300;';
	$gate = $fn('gate', $byte_param . ',' . $param, 'bool', $decl('prior', 'bool', $var('run')) . $var('run') . '=false; return ' . $var('prior') . ';');
	$cases = [
		'while_backedge' => $setup . $decl('twice', 'bool', 'true') . 'while (' . $var('run') . ') {' . $body
			. 'if (' . $var('twice') . ') {' . $var('twice') . '=false; } else {' . $var('run') . '=false; }}',
		'do_backedge' => $test . $setup . 'do {' . $body . '} while (test(' . $var('run') . '));',
		'while_test' => $gate . $setup . 'while (gate(' . $var('x') . ',' . $var('run') . ')) {' . $var('x') . '=300;}',
		'do_test' => $gate . $setup . 'do {' . $var('x') . '=300;} while (gate(' . $var('x') . ',' . $var('run') . '));',
		'join' => $setup . 'while (' . $var('run') . ') {' . $var('x') . '=300;' . $var('run') . '=false;}' . $decl('narrow', 'byte', $var('x')),
	];
	foreach ($cases as $label => $source) {
		$fixtures[$flavor->name . '_' . $label] = ['cpp' => (new Cpp_Emitter())->emit($prepare($source . 'echo "WRONG"; return 0;')),
			'stdout_hex' => $label === 'do_backedge' ? bin2hex('C') : '', 'catch_out_of_range' => true];
	}
	$directory = sys_get_temp_dir() . '/scpp-loops-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'while (predicate()) { do { nested(); } while (predicate()); } return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('predicate', '', $type, 'return ' . $value . ';') . $fn('nested', '', 'void', ''));
		};
		$write('bool', 'false');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$nested = $caller->main()->body()->statements[0]->body->statements[0]->body->statements[0];
		verify($session->dependency_watches->observer_count($session->scope->entry('func:nested')) === 1, 'Nested loop call lost watcher');
		$write('string', '""');
		$session = $compiler->frontend($directory, $session);
		$report = Incremental_Preparation::prepare($session, $target);
		verify($report['prepared'] === 3, 'Condition type edit did not invalidate caller: ' . json_encode($report));
		verify($session->entry->entry->ast === $caller && $nested->function_def() === $session->scope->entry('func:nested')->declaration(), 'Caller or stable target changed');
		$write('string', '"00"');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		$write('byte', '8');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported condition type');
		$write('bool', 'false');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 3, 'Loop condition recovery failed');
		verify($session->entry->entry->ast === $caller, 'Recovery reparsed caller');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 3, 'No-op rebuilt loop');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', '$run bool=true; while (predicate($run)) { echo "cross"; } return finish();', flavor::php),
	'cpp' => new source_file('cpp', 'bool predicate(bool &run) { bool prior=run; run=false; return prior; }', flavor::cpp),
	'js' => new source_file('js', 'function finish(): int { do { return 8; } while (false); }', flavor::js),
], new entry_target(31));
$native_files = Cpp_Program::emit($multi)->files;
$fixtures['multifile'] = ['files' => $native_files, 'stdout_hex' => bin2hex('cross')];
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Braced loops, generated truthiness, single-pass preparation, scope and incremental recovery passed\n";
