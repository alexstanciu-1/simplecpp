<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, type_registry, type_bool, type_null,
	type_byte, type_int64, value_conversion, Cpp_Emitter, diagnostic, diagnostic_group, Syntax_Inspection,
	Compilation_Inspection, Project_Compiler, Incremental_Preparation};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require semantic diagnostics, never host type errors or accidental backend admission. */
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

function variable(flavor $flavor, string $name): string
{
	return ($flavor === flavor::php ? '$' : '') . $name;
}

function declaration(flavor $flavor, string $type, string $name, string $value): string
{
	return match ($flavor) {
		flavor::cpp => "$type $name = $value;",
		flavor::php => '$' . "$name $type = $value;",
		flavor::js => "let $name: $type = $value;",
	};
}

verify(type_registry::resolve('null') === type_null::instance(), 'Null registration');
verify(type_registry::resolve('bool') === type_bool::instance(), 'Bool registration');
verify(type_registry::resolve('byte') === type_byte::instance(), 'Byte registration');
verify((type_byte::MINIMUM === 0) && (type_byte::MAXIMUM === 255), 'Byte limits');
$generated = [];
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => variable($flavor, $name);
	$decl = static fn(string $type, string $name, string $value): string => declaration($flavor, $type, $name, $value);
	$global = 'global ' . $decl('int', 'external', '8');
	$source = $global . 'global ' . $decl('bool', 'enabled', 'true') . 'global ' . $decl('null', 'empty', 'null')
		. 'global ' . $decl('byte', 'stored', '255')
		. $decl('bool', 'off', 'false') . $decl('null', 'nothing', 'null')
		. $decl('int', 'zero', $var('off')) . $decl('bool', 'on', '8')
		. $decl('int', 'one', $var('on')) . $decl('byte', 'small', '255')
		. $decl('int', 'wide', $var('small')) . $decl('byte', 'checked', $var('external'))
		. $decl('int', 'result', $var('checked'))
		. $decl('bool', 'runtime_bool', $var('external')) . $decl('int', 'runtime_int', $var('runtime_bool'))
		. $decl('null', 'copy_null', $var('nothing'))
		. 'echo ' . $var('zero') . '; echo ' . $var('one') . '; echo ' . $var('wide') . '; echo '
		. $var('result') . '; echo ' . $var('runtime_int') . '; return ' . $var('result') . ';';
	$frontend = new Source_Frontend();
	$prepare = static fn(string $code) => $frontend->prepare(new source_file('scalar', $code, $flavor), new entry_target(31));
	$entry = $prepare($source);
	verify(Syntax_Inspection::file($entry->file)['statements'][0]['initializer']['kind'] === 'keyword_literal', 'Literal syntax inspection');
	$locals = array_values($entry->body()->locals);
	verify(($locals[0]->constant() === false) && $locals[0]->constant_known(), 'False lost');
	verify(($locals[1]->constant() === null) && $locals[1]->constant_known(), 'Known null lost');
	verify(($locals[2]->constant() === 0) && ($locals[3]->constant() === true), 'Bool/int constant conversion');
	verify(($locals[4]->constant() === 1) && ($locals[6]->constant() === 255), 'Integer conversion facts');
	verify(!$locals[7]->constant_known(), 'Global initializer folded into consumer');
	verify($locals[7]->initialization() === value_conversion::integer_checked, 'Unknown byte lacks check');
	verify($locals[5]->initialization() === value_conversion::integer_proven, 'Known byte check not discharged');
	verify($locals[11]->constant_known(), 'Null local propagation');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	verify(str_contains($cpp, '#include <scpp/integers.hpp>'), 'Checked conversion lacks public include');
	verify(str_contains($cpp, 'scpp::byte{'), 'Checked conversion missing');
	$inspection = Compilation_Inspection::program($entry);
	verify($inspection['statements'][1]['initializer']['constant_known'] === true, 'Inspection loses known null');
	verify($inspection['statements'][0]['initializer']['constant'] === false, 'Inspection loses false');
	$generated[$flavor->value] = ['source' => $source, 'cpp' => $cpp];
	foreach (['256', '9223372036854775807'] as $outside) {
		rejects(fn() => $prepare($decl('byte', 'bad', $outside) . 'return 8;'), 'outside byte');
	}
	rejects(fn() => $prepare('global ' . $decl('byte', 'bad', '-1') . 'return 8;'), 'outside byte');
	rejects(fn() => $prepare($decl('byte', 'bad', 'true') . 'return 8;'), 'conversion');
	rejects(fn() => $prepare($decl('int', 'bad', 'null') . 'return 8;'), 'conversion');
	rejects(fn() => $prepare($decl('null', 'bad', '0') . 'return 8;'), 'conversion');
	rejects(fn() => $prepare('return true;'), 'requires int64');
	rejects(fn() => $prepare('echo false; return 8;'), 'Echo currently');
	rejects(fn() => $prepare($decl('byte', 'bad', '1') . 'echo ' . $var('bad') . '; return 8;'), 'Echo currently');
	$integer = $locals[3]->initializer;
	rejects(fn() => type_bool::instance()->prepare_conversion($integer, false), 'implicit conversion');
	rejects(fn() => type_byte::instance()->prepare_conversion($integer, false), 'implicit conversion');
	$implicit = type_int64::instance()->prepare_conversion($integer, false);
	verify($implicit === value_conversion::identity, 'Implicit identity rejected');
	$null = $locals[1]->initializer;
	$null->reset_preparation();
	try {
		$null->constant_known();
		throw new RuntimeException('Reset null looks prepared');
	}
	catch (LogicException $expected) {
	}
	type_registry::prepare_literal($null);
	verify($null->constant_known() && ($null->constant() === null), 'Null re-preparation');
}
verify(count(array_unique(array_column($generated, 'cpp'))) === 1, 'Flavor lowering differs');

// Inferred global type edits must invalidate unchanged readers, including int -> bool -> null.
$directory = sys_get_temp_dir() . '/scpp-scalar-incremental-' . bin2hex(random_bytes(8));
mkdir($directory);
try
{
	file_put_contents($directory . '/module.json', '{"entry":"main.php"}');
	file_put_contents($directory . '/main.php', '$n int = $external; return $n;');
	file_put_contents($directory . '/lib.php', 'global $external = 8;');
	$compiler = new Project_Compiler();
	$session = $compiler->frontend($directory);
	Incremental_Preparation::prepare($session, new entry_target(31));
	$caller = $session->entry->entry->ast;
	file_put_contents($directory . '/lib.php', 'global $external = false;');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['prepared'] === 1, 'Inferred type change missed');
	verify($session->entry->entry->ast === $caller, 'Unchanged AST copied');
	verify(array_values($caller->main()->body()->locals)[0]->initialization() === value_conversion::boolean_integer, 'Stale conversion cache');
	file_put_contents($directory . '/lib.php', 'global $external = null;');
	$session = $compiler->frontend($directory, $session);
	rejects(fn() => Incremental_Preparation::prepare($session, new entry_target(31)), 'conversion');
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($generated, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Core scalar declarations, checked conversions, null facts, purity and incremental invalidation passed\n";
