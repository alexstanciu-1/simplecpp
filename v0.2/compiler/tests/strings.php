<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, type_string,
	type_registry, diagnostic, diagnostic_group, ast_string_expression, Project_Compiler, Incremental_Preparation};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

function declaration(flavor $flavor, string $type, string $name, string $value): string
{
	return match ($flavor) {
		flavor::cpp => "$type $name = $value;",
		flavor::php => '$' . "$name $type = $value;",
		flavor::js => "let $name: $type = $value;",
	};
}

verify(type_registry::resolve('string') === type_string::instance(), 'Missing canonical string');
$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$decl = static fn(string $type, string $name, string $value): string => declaration($flavor, $type, $name, $value);
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(
		new source_file('strings', $code, $flavor), new entry_target(31));
	$code = $decl('string', 'first', '"héllo\\0world\\n"') . $decl('string', 'copy', $var('first'))
		. $decl('string', 'empty', '""') . $decl('string', 'single', "'a\\nb'")
		. 'echo ' . $var('first') . '; echo ' . $var('copy') . '; echo ' . $var('empty')
		. '; echo ' . $var('single') . '; echo "!"; return 8;';
	$entry = $prepare($code);
	$locals = array_values($entry->body()->locals);
	verify($locals[0]->initializer instanceof ast_string_expression, 'String syntax missing');
	verify($locals[0]->initializer->bytes() === "héllo\0world\n", 'Decoded bytes differ');
	verify($locals[2]->initializer->bytes() === '', 'Empty literal lost');
	verify($locals[1]->initializer->local_def() === $locals[0], 'Copy must retain its declaration binding');
	foreach ($locals as $local) {
		verify($local->type_def() === type_string::instance(), 'String type not settled');
		verify(!$local->constant_known() && ($local->constant() === null), 'Bytes copied into scalar facts');
	}
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	verify(str_contains($cpp, '#include <scpp/string.hpp>'), 'Missing public string API');
	verify(str_contains($cpp, 'scpp::string var_copy_1 = var_first_0;'), 'Copy is not owning storage');
	$direct = (new Cpp_Emitter())->emit($prepare('echo "direct"; return 8;'));
	verify(!str_contains($direct, 'scpp::string') && !str_contains($direct, '<scpp/string.hpp>'), 'Direct echo allocated storage');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("héllo\0world\nhéllo\0world\na\\nb!")];
	foreach ([
		$decl('string', 'bad', '8'), $decl('int', 'bad', '"8"'),
		$decl('bool', 'bad', '"true"'), $decl('float', 'bad', '"8.5"'),
		'global ' . $decl('string', 'bad', '"x"'), 'global ' . $decl('string', 'bad', '1'),
		'return "x";', $decl('string', 'bad', match ($flavor) {
			flavor::php => '"$missing"', flavor::js => '`${missing}`', flavor::cpp => 'f"{missing}"',
		}), $decl('string', 'bad', '"\\q"'),
	] as $invalid) {
		try {
			$prepare($invalid . 'return 8;');
		}
		catch (diagnostic|diagnostic_group $error) {
			continue;
		}
		throw new RuntimeException('Invalid string program accepted: ' . $invalid);
	}
	$literal = $locals[0]->initializer;
	$literal->reset_preparation();
	try {
		$literal->bytes();
		throw new RuntimeException('Reset retained bytes');
	}
	catch (LogicException $error) {
		verify($error->getMessage() === 'String is not decoded', 'Unexpected reset error');
	}
	type_registry::prepare_literal($literal);
	verify($literal->bytes() === "héllo\0world\n", 'Repreparation changed bytes');
}
$directory = sys_get_temp_dir() . '/scpp-strings-' . bin2hex(random_bytes(8));
mkdir($directory);
try
{
	file_put_contents($directory . '/module.json', '{"entry":"main.php"}');
	file_put_contents($directory . '/main.php', 'show(); return 8;');
	file_put_contents($directory . '/lib.php', 'function show(): void { $s string = "old"; echo $s; }');
	$compiler = new Project_Compiler();
	$session = $compiler->frontend($directory);
	Incremental_Preparation::prepare($session, new entry_target(31));
	$caller = $session->entry->entry->ast;
	$old_body = $session->scope->resolve('func:show')->body();
	file_put_contents($directory . '/lib.php', 'function show(): void { $s string = "new bytes"; echo $s; }');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['prepared'] === 1, 'String edit scope');
	verify($session->entry->entry->ast === $caller, 'Unchanged caller reparsed');
	$body = $session->scope->resolve('func:show')->body();
	verify($body !== $old_body, 'Changed string body reused');
	verify(array_values($body->locals)[0]->initializer->bytes() === 'new bytes', 'Stale decoded bytes');
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
echo "String locals, copies, bytes, boundaries, reset and pure lowering passed in all flavors\n";
