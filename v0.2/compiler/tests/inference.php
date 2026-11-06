<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	ast_local_declaration, ast_assignment_statement, type_registry, Function_Preparation, Preparation,
	Project_Compiler, Incremental_Preparation};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

function local(flavor $flavor, string $name, string $value, ?string $type = null): string
{
	return match ($flavor) {
		flavor::cpp => ($type ?? 'auto') . " $name = $value;",
		flavor::php => '$' . $name . ($type === null ? '' : ' ' . $type) . " = $value;",
		flavor::js => 'let ' . $name . ($type === null ? '' : ': ' . $type) . " = $value;",
	};
}

/** Keep negative proofs source-diagnostic specific rather than accepting implementation failures. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected rejection: ' . $message);
}

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$decl = static fn(string $name, string $value, ?string $type = null): string => local($flavor, $name, $value, $type);
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('inference', $code, $flavor), new entry_target(31));
	$code = $decl('number', '8') . $decl('fraction', '8.75') . $decl('flag', 'true')
		. $decl('text', '"N\\0W"') . $decl('small', '9', 'byte') . $decl('copy_byte', $var('small'))
		. $decl('copy_int', $var('number')) . $decl('copy_text', $var('text'))
		. $decl('nothing', 'null', 'null') . $decl('empty', '""')
		. $var('number') . ' = 7; ' . $var('text') . ' = "changed";';
	$function = ($flavor === flavor::cpp ? 'void show()' : 'function show(): void')
		. '{' . $decl('number', '4') . 'echo ' . $var('number') . ';}';
	$code .= 'show(); echo ' . $var('number') . '; echo ' . $var('copy_text') . '; echo ' . $var('empty') . ';'
		. $decl('byte_int', $var('copy_byte'), 'int') . 'echo ' . $var('byte_int') . ';'
		. $decl('float_int', $var('fraction'), 'int') . 'echo ' . $var('float_int') . ';'
		. $decl('bool_int', $var('flag'), 'int') . 'echo ' . $var('bool_int') . '; return ' . $var('copy_int') . ';';
	$entry = $prepare($function . $code);
	$locals = array_values($entry->body()->locals);
	foreach (['int', 'float', 'bool', 'string', 'byte', 'byte', 'int', 'string', 'null', 'string'] as $index => $type) {
		verify($locals[$index]->type_def() === type_registry::resolve($type), 'Wrong inferred type');
		if (!in_array($index, [4, 8], true)) {
			verify($locals[$index]->type_reference === null, 'Inference invented written type syntax');
		}
	}
	verify($entry->body()->statements[10] instanceof ast_assignment_statement, 'Later PHP write became another declaration');
	verify($entry->body()->statements[11] instanceof ast_assignment_statement, 'String reassignment became another declaration');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated inferred facts');
	verify(!str_contains($cpp, 'auto local_'), 'C++ was asked to infer source types');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("47N\0W981")];
	Function_Preparation::reset_body($entry->body());
	$entry = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($entry) === $cpp, 'Inference changed after reset/repreparation');
	rejects(fn() => $prepare($decl('bad', 'null') . 'return 8;'), 'Cannot infer a local type from null');
	rejects(fn() => $prepare($decl('bad', 'null') . $var('bad') . ' = 8; return 8;'), 'Cannot infer');
	rejects(fn() => $prepare($decl('n', 'null', 'null') . $decl('bad', $var('n')) . 'return 8;'), 'Cannot infer');
	rejects(fn() => $prepare($decl('bad', $var('bad')) . 'return 8;'), 'during its initialization');
	rejects(fn() => $prepare($decl('bad', $var('later')) . $decl('later', '8') . 'return 8;'), 'not-yet-initialized');
	rejects(fn() => $prepare($decl('n', '8') . $var('n') . ' = "changed"; return 8;'), 'Unsupported implicit conversion');
	rejects(fn() => $prepare($decl('n', '8') . $decl('n', '9', 'int') . 'return 8;'), 'Duplicate local');
	// A byte reference infers byte; a small literal still infers int64, so this implicit write rejects.
	rejects(fn() => $prepare($decl('b', '8', 'byte') . $decl('c', $var('b')) . $var('c') . ' = 9; return 8;'), 'Unsupported implicit conversion');
	$global = $prepare('global ' . $decl('external', '8', 'int') . $decl('copy', $var('external')) . 'return ' . $var('copy') . ';');
	verify(!$global->body()->locals[0]->constant_known(), 'Global initializer folded into inferred local');
}

// PHP local classification is independent of global names and interleaved function bodies.
$frontend = new Source_Frontend();
$file = $frontend->collect(new source_file('collection', 'global $same int=4; $same=8; function f(): void { $same=3; $same=7; } $same=9; return $same;', flavor::php));
verify(count($file->main()->body()->locals) === 1, 'Main collection leaked function/global locals');
verify($file->main()->body()->statements[0] instanceof ast_local_declaration, 'Global suppressed local declaration');
verify($file->main()->body()->statements[1] instanceof ast_assignment_statement, 'Function parse lost main collection');
$entry = (new Preparation())->prepare($file, new entry_target(31));
verify($entry->body()->statements[2]->value->constant() === 9, 'Bare write did not shadow global');
rejects(fn() => $frontend->prepare(new source_file('self', 'global $same int=4; $same=$same; return 8;', flavor::php), new entry_target(31)), 'during its initialization');

$directory = sys_get_temp_dir() . '/scpp-inference-' . bin2hex(random_bytes(8));
mkdir($directory);
try
{
	file_put_contents($directory . '/module.json', '{"entry":"main.php"}');
	file_put_contents($directory . '/main.php', '$copy=$external; $ret int=$copy; return $ret;');
	file_put_contents($directory . '/lib.php', 'global $external=8;');
	$compiler = new Project_Compiler();
	$session = $compiler->frontend($directory);
	Incremental_Preparation::prepare($session, new entry_target(31));
	$caller = $session->entry->entry->ast;
	file_put_contents($directory . '/lib.php', 'global $external=8.75;');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['prepared'] === 1, 'Inferred consumer not invalidated');
	verify($session->entry->entry->ast === $caller, 'Unchanged inferred consumer reparsed');
	verify($caller->main()->body()->locals[0]->type_def() === type_registry::resolve('float'), 'Stale inferred type');
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Inferred locals: types, null rejection, collection, visibility, fixed types and incremental invalidation passed\n";
