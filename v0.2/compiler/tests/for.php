<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection};

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

/** Require a source diagnostic for invalid for clauses and completion failures. */
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
	$fn = static fn(string $name, string $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('for', $source, $flavor), new entry_target(31));
	$loop = static fn(string $body): string => 'for (' . $decl('i', 'int', '0') . $var('i') . '<4; '
		. $var('i') . '=' . $var('i') . '+1) {' . $body . '}';
	$entry = $prepare($loop('echo ' . $var('i') . ';') . 'return 8;');
	verify($entry->body()->statement_visits === 5 && $entry->body()->expression_visits === 9, 'For clauses must be visited once');
	$for = $entry->body()->statements[0];
	verify($for instanceof scpp\compiler\ast_for_statement, 'For AST missing');
	verify(($for->initializer->parent === $for) && ($for->update->parent === $for), 'Header ownership');
	verify(($for->scope->parent === $entry->body()->scope) && ($for->body->scope->parent === $for->scope), 'Header scope hierarchy');
	verify(count($for->scope->declarations) === 1 && count($entry->body()->scope->declarations) === 0, 'Header publication leaked');
	$parameter = match ($flavor) {
		flavor::php => 'int $v',
		flavor::cpp => 'int v',
		flavor::js => 'v: int',
	};
	$code = $fn('start', '', 'int', 'echo "I"; return 0;')
		. $fn('test', $parameter, 'bool', 'echo "T"; return ' . $var('v') . '<3;')
		. $fn('step', $parameter, 'int', 'echo "U"; return ' . $var('v') . '+1;')
		. $loop('if (' . $var('i') . '==1) { continue; } if (' . $var('i') . '==3) { break; } echo ' . $var('i') . ';')
		. 'for (' . $decl('i', 'int', 'start()') . 'test(' . $var('i') . ');' . $var('i') . '=step(' . $var('i') . ')) {'
		. 'if (' . $var('i') . '==1) { continue; } if (' . $var('i') . '==2) { break; } echo ' . $var('i') . ';} echo "X";'
		. 'for (' . $decl('outer', 'int', '0') . $var('outer') . '<2;' . $var('outer') . '=' . $var('outer') . '+1) {'
		. $loop('if (' . $var('i') . '==0) { continue; } else { break; }') . 'echo ' . $var('outer') . ';}'
		. $decl('x', 'int', '9') . 'for (' . $var('x') . '=0;' . $var('x') . '<2;' . $var('x') . '=' . $var('x') . '+1) {} echo ' . $var('x') . ';'
		. 'for (' . $decl('x', 'int', '0') . $var('x') . '<1;' . $var('x') . '=' . $var('x') . '+1) {'
		. $decl('x', 'int', '7') . 'echo ' . $var('x') . ';} echo ' . $var('x') . ';'
		. 'for (' . $decl('zero', 'int', '0') . 'false;' . $var('zero') . '=step(0)) { echo "WRONG"; } return 8;';
	$inferred = match ($flavor) {
		flavor::php => '$inferred=0;',
		flavor::cpp => 'auto inferred=0;',
		flavor::js => 'let inferred=0;',
	};
	$code = substr($code, 0, -strlen('return 8;'))
		. 'for (' . $inferred . $var('inferred') . '<1;' . $var('inferred') . '=' . $var('inferred') . '+1) { echo "N"; }'
		. 'for (' . $decl('text', 'string', '"yes"') . $var('text') . ';' . $var('text') . '="0") { echo "S"; }'
		. 'for (' . $decl('flag', 'bool', 'true') . $var('flag') . ';' . $var('flag') . '=false) { echo "B"; } return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	verify(Syntax_Inspection::file($entry->file)['statements'][0]['kind'] === 'for', 'Syntax inspection');
	verify(Compilation_Inspection::body($entry->body())['statements'][0]['kind'] === 'for', 'Fact inspection');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('02IT0UTUTX01272NSB')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	rejects(fn() => $prepare($loop('') . 'echo ' . $var('i') . '; return 8;'), 'Unknown or not-yet-initialized');
	rejects(fn() => $prepare('for (' . $decl('i', 'int', '0') . 'true;' . $var('inside') . '=1) {'
		. $decl('inside', 'int', '0') . '} return 8;'), 'Assignment requires an existing local');
	rejects(fn() => $prepare($fn('bad', '', 'int', $loop('return 8;')) . 'return 8;'), 'Non-void function requires');
	foreach (['for (;;) {}', 'for (' . $decl('i', 'int', '0') . ';' . $var('i') . '=1) {}',
		'for (' . $decl('i', 'int', '0') . 'true;) {}',
		'for (' . $decl('i', 'int', '0') . 'true;' . $var('i') . '++) {}',
		'for (' . $decl('i', 'int', '0') . 'true;' . $var('i') . '=1,' . $var('i') . '=2) {}'] as $invalid) {
		rejects(fn() => $prepare($invalid . 'return 8;'), 'Expected');
	}
	// The update sees body writes and keeps the checked conversion in the called function.
	$failure = $fn('checked', $parameter, 'int', $decl('narrow', 'byte', $var('v')) . 'return ' . $var('v') . ';')
		. $decl('x', 'int', '1') . 'for (' . $decl('i', 'int', '0') . $var('i') . '<2;'
		. $var('i') . '=checked(' . $var('x') . ')) {' . $var('x') . '=300; continue; } return 8;';
	$fixtures[$flavor->name . '_update_range'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	// Every enabled operator uses the same header path, in both initialization and update.
	$x = $var('compound');
	$flag = $var('once');
	$extra = $decl('compound', 'int', '10') . $decl('once', 'bool', 'true');
	foreach (['+=', '-=', '*=', '/=', '%='] as $operator) {
		$extra .= $x . '=10;' . $flag . '=true; for (' . $x . $operator . '2;' . $flag . ';' . $x . $operator . '2) {'
			. $flag . '=false; echo ' . $x . '; echo ",";} echo ' . $x . '; echo ";";';
	}
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	$extra .= $fn('mutate', $ref, 'int', $var('v') . '=100; echo "R"; return 1;')
		. $x . '=10; for (' . $x . '+=mutate(' . $x . ');false;' . $x . '+=mutate(' . $x . ')) {} echo ' . $x . ';'
		. 'for (' . $x . '=0;' . $x . '<2;' . $x . '+=mutate(' . $x . ')) { continue; } echo ' . $x . ';'
		. 'for (' . $x . '=0;true;' . $x . '/=0) { break; }'
		. $fn('exit_loop', '', 'int', 'for (' . $decl('i', 'int', '0') . 'true;' . $var('i') . '/=0) { return 8; } return 9;')
		. 'echo exit_loop();'
		. 'for (' . $decl('outer', 'int', '0') . $var('outer') . '<2;' . $var('outer') . '+=1) {'
		. 'for (' . $decl('inner', 'int', '0') . $var('inner') . '<2;' . $var('inner') . '+=1) { continue; }'
		. 'echo ' . $var('outer') . ';}'
		. 'for (' . $decl('i', 'int', '0') . $var('i') . '<2;' . $var('i') . '+=1) {'
		. $decl('i', 'int', '50') . $var('i') . '+=1; echo ' . $var('i') . ';} return 8;';
	$compound_entry = $prepare($extra);
	$before = serialize($compound_entry);
	$compound_cpp = (new Cpp_Emitter())->emit($compound_entry);
	verify(serialize($compound_entry) === $before, 'Header compound lowering mutated facts');
	$fixtures[$flavor->name . '_compound'] = ['cpp' => $compound_cpp,
		'stdout_hex' => bin2hex('12,14;8,6;20,40;5,2;0,0;R11RR28015151')];
	Function_Preparation::reset_body($compound_entry->body());
	$again = (new Preparation())->prepare($compound_entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $compound_cpp, 'Compound header reset/reprepare differs');
	$simple = $prepare('for (' . $decl('i', 'int', '0') . $var('i') . '<2;' . $var('i') . '+=1) {} return 8;');
	verify($simple->body()->statement_visits === 4 && $simple->body()->expression_visits === 6, 'Compound header visited more than once');
	verify(!str_contains((new Cpp_Emitter())->emit($simple), '([&]'), 'Compound header introduced a lambda');
	rejects(fn() => $prepare('for (' . $x . '+=1;false;' . $x . '+=1) {} return 8;'), 'existing local');
	rejects(fn() => $prepare('for (' . $decl('i', 'int', '0') . 'false;' . $var('i') . '+=1.0) {} return 8;'), 'Unsupported compound');
	rejects(fn() => $prepare('for (' . $decl('i', 'int', '0') . 'false;' . $var('inside') . '+=1) {'
		. $decl('inside', 'int', '1') . '} return 8;'), 'existing local');
	rejects(fn() => $prepare('for (' . $decl('i', 'int', '0') . 'false;' . $var('i') . '+=1) {} echo ' . $var('i') . '; return 8;'), 'Unknown');
	// Test-only exception observers verify no header write-back after failure; prior RHS effects remain.
	foreach ([true, false] as $in_initializer) {
		$v = $var('v');
		$failure = $v . '/=zero(' . $v . ')';
		$source = $fn('zero', $ref, 'int', $v . '=100; echo "R"; return 0;')
			. $fn('update', $ref, 'void', 'for (' . ($in_initializer ? $failure : $v . '+=0') . ';true;'
				. ($in_initializer ? $v . '+=1' : $failure) . ') { continue; } return;') . 'return 8;';
		$prepared = $prepare($source);
		$name = Cpp_Emitter::function_name($prepared->file->declarations['func:update']);
		$cpp = "#include <stdexcept>\n" . str_replace('int main()', 'int unused_entry()', (new Cpp_Emitter())->emit($prepared));
		$cpp .= "\nint main() { " . scpp\compiler\type_int64::instance()->cpp_type() . ' value=10; try { ' . $name . '(value); }'
			. ' catch (const std::domain_error&) { return value == 100 ? 8 : 91; } return 90; }';
		$fixtures[$flavor->name . ($in_initializer ? '_init_failure' : '_update_failure')] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('R')];
	}
	$directory = sys_get_temp_dir() . '/scpp-for-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		file_put_contents($main, 'for (' . $decl('i', 'int', '0') . $var('i') . '<2;' . $var('i') . '+=step(' . $var('i') . ')) { continue; } return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn, $parameter): void {
			file_put_contents($lib, $fn('step', $parameter, $type, 'return ' . $value . ';'));
		};
		$write('int', $var('v') . '+1');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		$write('string', '"bad"');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported compound');
		$write('int64', $var('v') . '+1');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'For update recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		$sources = [];
		foreach ($session->files() as $path => $file) {
			$sources[$path] = $file->source();
		}
		$program = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
		$units = array_values(Cpp_Program::emit($program)->files);
		$fixtures[$flavor->name . '_recovery'] = ['cpp' => array_pop($units), 'extra_cpp' => $units, 'stdout_hex' => ''];
		$write('int64', $var('v') . '+100');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body edit invalidated caller');
		file_put_contents($main, $loop('break;') . 'return 8;');
		$callee = $session->scope->entry('func:step')->declaration();
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'For source edit');
		verify($session->scope->entry('func:step')->declaration() === $callee, 'Source edit replaced callee');
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
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Bounded for loops, scopes, transfers and incremental recovery passed\n";
