<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection, ast_if_statement, ast_statement_block};

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

/** Require a source diagnostic, including unreachable arms and unsupported conditions. */
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
	$p = $flavor === flavor::php ? 'bool $flag' : ($flavor === flavor::cpp ? 'bool flag' : 'flag: bool');
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('conditions', $source, $flavor), new entry_target(31));
	// The global i_0 and the first local i have identical C++ spellings in different scopes.
	$names = 'global ' . $decl('i_0', 'int', '2')
		. $fn('_X', '', 'int', 'return 3;') . $fn('Z5fX', '', 'int', 'return 4;')
		. $fn('co_await', '', 'int', 'return 5;')
		. $decl('i', 'int', $var('i_0')) . 'echo ' . $var('i') . ';'
		. 'if (true) {' . $decl('i', 'int', '7') . 'echo ' . $var('i') . ';}'
		. 'echo ' . $var('i') . ', ' . $var('i_0') . ', _X(), Z5fX(), co_await(); return 8;';
	$cpp = (new Cpp_Emitter())->emit($prepare($names));
	foreach (['var_i_0 = ::App::var_i_0;', 'var_i_1 = 7;', 'Z5fX_()', 'Z5a5fX_()', 'co_await_()'] as $name) {
		verify(str_contains($cpp, $name), 'Missing C++ spelling ' . $name);
	}
	$fixtures[$flavor->name . '_names'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('2722345')];
	$single_pass = $prepare('if (true) { echo 1; } elseif (false) { echo 2; } else { echo 3; } return 8;');
	verify($single_pass->body()->statement_visits === 5 && $single_pass->body()->expression_visits === 6,
		'Conditions and arm statements must be prepared exactly once');
	$code = '';
	$expected = '';
	foreach (['true' => true, 'false' => false, '0' => false, '8' => true, '0.0' => false, '-0.0' => false,
		'1.25' => true, '""' => false, '"0"' => false, '"false"' => true, '"00"' => true, '"yes"' => true, '"\\0"' => true] as $literal => $truth) {
		$code .= 'if (' . $literal . ') { echo "T"; } else { echo "F"; }';
		$expected .= $truth ? 'T' : 'F';
	}
	$code .= $fn('first', '', 'bool', 'echo "A"; return false;')
		. $fn('second', '', 'string', 'echo "B"; return "yes";')
		. $fn('third', '', 'bool', 'echo "WRONG"; return true;')
		. $fn('choose', $p, 'int', 'if (' . $var('flag') . ') { return 1; } else { if ("0") { return 2; } else { return 8; } }')
		. 'if (first()) { echo "WRONG"; } elseif (second()) { echo "C"; } else if (third()) { echo "WRONG"; } else { echo "WRONG"; }'
		. $decl('outer', 'int', '4') . 'if (true) {' . $decl('outer', 'int', '5') . 'echo ' . $var('outer') . '; } else {'
		. $decl('outer', 'int', '6') . '} echo ' . $var('outer') . ';'
		. 'if (false) { ' . $var('outer') . '=7; } else { ' . $var('outer') . '=9; } echo ' . $var('outer') . ';'
		. 'if (false) { echo "WRONG"; } elseif (false) { echo "WRONG"; } else if (true) { echo "E"; }'
		. 'return choose(false);';
	$entry = $prepare($code);
	verify($entry->body() instanceof ast_statement_block, 'Function body does not share block owner');
	$first = $entry->body()->statements[0];
	verify($first instanceof ast_if_statement && $first->arms[0]->body->parent === $first->arms[0], 'Broken arm ownership');
	verify($first->arms[0]->body->scope->parent === $entry->body()->scope, 'Branch scope parent');
	verify(Syntax_Inspection::file($entry->file)['statements'][0]['kind'] === 'if', 'Syntax inspection lost branch');
	verify(Compilation_Inspection::body($entry->body())['statements'][0]['kind'] === 'if', 'Preparation inspection lost branch');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . 'ABC549E')];
	$visits = $entry->body()->statement_visits;
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp && $again->body()->statement_visits === $visits, 'Reset/reprepare differs');
	foreach ([
		['if (null) {} return 8;', 'Unsupported condition type'],
		[$fn('nothing', '', 'void', '') . 'if (nothing()) {} return 8;', 'Void call'],
		['if (false) { missing(); } return 8;', 'Unknown function'],
		['if (true) { return 8; }', 'Entry slice requires'],
		[$fn('bad', $p, 'int', 'if (' . $var('flag') . ') { return 8; }') . 'return 8;', 'requires a value return'],
		['if (true) {' . $decl('inside', 'int', '1') . '} echo ' . $var('inside') . '; return 8;', 'Unknown or not-yet-initialized'],
		['if (true) {' . $decl('x', 'int', '1') . $decl('x', 'int', '2') . '} return 8;', 'Duplicate local'],
		[$decl('x', 'int', '1') . 'if (true) {' . $decl('x', 'int', $var('x')) . '} return 8;', 'during its initialization'],
		['if (true) echo 1; return 8;', 'braced conditional body'],
		['if (true) { return 8; } else { return 9; } echo 1;', 'Statements after return'],
	] as [$invalid, $message]) {
		rejects(fn() => $prepare($invalid), $message);
	}
	// Neither a sibling arm nor a join may retain the first arm's narrowed range knowledge.
	$danger = $decl('x', 'int', '300') . 'if (false) {' . $var('x') . '=8; } else {'
		. $decl('b', 'byte', $var('x')) . '} return 0;';
	$fixtures[$flavor->name . '_sibling'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($danger)), 'stdout_hex' => '', 'catch_out_of_range' => true];
	$danger = $decl('x', 'int', '8') . 'if (true) {' . $var('x') . '=300; }'
		. $decl('b', 'byte', $var('x')) . 'return 0;';
	$fixtures[$flavor->name . '_join'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($danger)), 'stdout_hex' => '', 'catch_out_of_range' => true];
	$directory = sys_get_temp_dir() . '/scpp-conditions-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'if (true) { if (predicate()) { echo "Y"; } } return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('predicate', '', $type, 'return ' . $value . ';'));
		};
		$write('bool', 'true');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$write('string', '"yes"');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Condition signature edit did not invalidate caller');
		verify($session->entry->entry->ast === $caller, 'Nested caller reparsed');
		$condition = $caller->main()->body()->statements[0]->arms[0]->body->statements[0]->arms[0]->condition;
		verify($condition->type_def()->name === 'string', 'Nested condition has stale type');
		$write('string', '"false"');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		$write('null', 'null');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported condition type');
		$write('bool', 'false');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Condition recovery failed');
		verify($session->entry->entry->ast === $caller, 'Recovery reparsed caller');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', 'if (predicate()) { echo "cross"; } return choose();', flavor::php),
	'cpp' => new source_file('cpp', 'string predicate() { return "yes"; }', flavor::cpp),
	'js' => new source_file('js', 'function choose(): int { if (false) { return 1; } else { return 8; } }', flavor::js),
], new entry_target(31));
$native_files = Cpp_Program::emit($multi)->files;
$fixtures['multifile'] = ['files' => $native_files, 'stdout_hex' => bin2hex('cross')];
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Generated truthiness, braced branches/scopes, value barriers and incremental recovery passed\n";
