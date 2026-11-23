<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';
require_once __DIR__ . '/../host/syntax.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require semantic diagnostics before native compilation. */
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

/** Read only emitted native artifacts for incremental/fresh equivalence. @return string[] */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
		$path = $item->getPathname();
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['cpp', 'hpp'], true)) {
			$files[substr($path, strlen($directory) + 1)] = file_get_contents($path);
		}
	}
	ksort($files);
	return $files;
}

/** Remove the isolated proof tree, including emitted bundles. */
function remove_tree(string $path): void
{
	foreach (new DirectoryIterator($path) as $item) {
		if (!$item->isDot()) {
			$item->isDir() ? remove_tree($item->getPathname()) : unlink($item->getPathname());
		}
	}
	rmdir($path);
}

$fixtures = [];
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$v = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . ' ' . $type . ' = ' . $value . ';',
		flavor::cpp => $type . ' ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . ':' . $type . ' = ' . $value . ';',
	};
	$function = static fn(string $type, string $name, string $body): string =>
		($flavor === flavor::cpp ? $type . ' ' . $name . '()' : 'function ' . $name . '():' . $type) . '{' . $body . '}';
	$member = $flavor === flavor::js ? '.' : '->';
	$loop = static fn(string $source, string $body, bool $key = false): string =>
		'foreach (' . $source . ' as ' . ($key ? $v('key') . ' => ' : '') . $v('value') . ') {' . $body . '}';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('foreach', $text, $flavor), new entry_target(31));
	$emit = static function (string $name, string $text, string $expected, bool $failure = false) use (&$fixtures, $prepare, $flavor): void {
		$start = hrtime(true); $entry = $prepare($text);
		$before = serialize($entry); $cpp = (new Cpp_Emitter())->emit($entry);
		verify($before === serialize($entry), 'Emission changed AST facts');
		verify(!str_contains($cpp, '[&]'), 'Foreach introduced a lambda');
		$fixtures[$flavor->name . '_' . $name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected),
			'compiler_ms' => (hrtime(true) - $start) / 1e6, 'catch_runtime_error' => $failure];
	};
	foreach (['vector<int>', 'hash<int,string>', 'mixed', 'dynamic'] as $index => $type) {
		$keyed = $index !== 0;
		$put = static fn(string $name, string $key, int $value): string => $v($name) . '[' . ($keyed ? '"' . $key . '"' : '') . ']=' . $value . ';';
		$setup = $decl($type, 'items', '[]') . $put('items', 'a', 10) . $put('items', 'b', 20);
		// Existing int destination exercises normal checked extraction for boxed elements.
		$body = $decl('int', 'value', '99') . $loop($v('items'), 'echo ' . $v('value') . ';') . 'echo ' . $v('value') . ';return 8;';
		$emit('family_' . $index, $setup . $body, '102020');
		$emit('keys_' . $index, $setup . $loop($v('items'), 'echo (string)' . $v('key') . ';', true) . 'return 8;', $keyed ? 'ab' : '01');
		if ($index < 2) {
			$pairs = 'echo ' . $v('key') . ',":",' . $v('value') . ',";";';
			$emit('pairs_' . $index, $setup . $loop($v('items'), $pairs, true) . 'return 8;',
				$keyed ? 'a:10;b:20;' : '0:10;1:20;');
		}
		// Capture the initial physical limit; appended entries do not extend this traversal.
		$emit('grow_' . $index, $setup . $decl('int', 'value', '0')
			. $loop($v('items'), 'echo ' . $v('value') . ';' . $put('items', 'extra', 30)) . 'return 8;', '1020');
		// Replace the live binding with a smaller collection between advances.
		$replacement = $decl($type, 'other', '[]') . $put('other', 'z', 40);
		$emit('replace_' . $index, $setup . $replacement . $decl('int', 'value', '0')
			. $loop($v('items'), 'echo ' . $v('value') . ';' . $v('items') . '=' . $v('other') . ';') . 'return 8;', '10');
		$helpers = $function($type, 'make', $setup . 'return ' . $v('items') . ';');
		$main = $decl('int', 'value', '0') . $loop('make()', 'echo ' . $v('value') . ';') . 'return 8;';
		$emit('temporary_' . $index, $helpers . $main, '1020');
		if ($index >= 2) {
			$emit('scalar_' . $index, $setup . $decl('int', 'value', '0')
				. $loop($v('items'), 'echo ' . $v('value') . ';' . $v('items') . '=3;') . 'return 8;', '10', true);
			$emit('null_' . $index, $decl($type, 'items', 'null') . $loop($v('items'), '') . 'return 8;', '', true);
		}
		// Keep a caller AST across provider signature edits, deletion, repeated failures and recovery.
		$directory = sys_get_temp_dir() . '/scpp-foreach-' . bin2hex(random_bytes(8)); mkdir($directory);
		try {
			$provider = $directory . '/library.' . $flavor->name;
			file_put_contents($directory . '/main.' . $flavor->name, $main);
			file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name,
				'sources' => ['main.' . $flavor->name, basename($provider)]]));
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp = time(); $caller = null;
			foreach ([$helpers, str_replace('10', '11', $helpers), $function('int', 'make', 'return 8;'), '', $helpers] as $step => $text) {
				file_put_contents($provider, $text); touch($provider, ++$stamp);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($step === 2 || $step === 3) {
					rejects(fn() => $project->run(), ''); rejects(fn() => $project->run(), ''); rejects(fn() => $fresh->run(), ''); continue;
				}
				$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
				verify($caller === $project->session()->entry->entry->ast, 'Caller AST was replaced');
				$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Fresh artifacts differ');
				$fixtures[$flavor->name . '_incremental_' . $index . '_' . $step] = ['files' => $files, 'stdout_hex' => bin2hex($step === 1 ? '1120' : '1020')];
				verify($project->run()['preparation']['prepared'] === 0, 'No-op re-prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
	$setup = $decl('vector<int>', 'items', '[]') . $v('items') . '[]=1;' . $v('items') . '[]=2;' . $v('items') . '[]=3;';
	$field = match ($flavor) {
		flavor::php => 'public vector<int> $items;',
		flavor::cpp => 'vector<int> items;',
		flavor::js => 'items:vector<int>;',
	};
	$record = 'struct Box {' . $field . '}' . ($flavor === flavor::cpp ? ';' : '');
	$path = $v('owner') . $member . 'items';
	$owner_setup = $record . $decl('shared<Box>', 'owner', 'new Box()') . $path . '[]=5;' . $path . '[]=6;';
	$emit('owner_release', $owner_setup . $loop($path, $v('owner') . '=null;echo ' . $v('value') . ';') . 'return 8;', '56');
	$emit('owner_replace', $owner_setup . $loop($path, $v('owner') . '=new Box();echo ' . $v('value') . ';') . 'return 8;', '56');
	$emit('owner_empty', $record . $decl('shared<Box>', 'owner', 'null') . $loop($path, '') . 'return 8;', '', true);
	$emit('holes', $decl('hash<int,int>', 'items', '[]') . $v('items') . '[0]=1;' . $v('items') . '[1]=2;'
		. $v('items') . '[2]=3;' . $v('items') . $member . 'remove(1);'
		. $loop($v('items'), 'echo ' . $v('value') . ';', true) . 'return 8;', '13');
	$emit('string_copy', $decl('vector<string>', 'items', '[]') . $v('items') . '[]="long original string payload";'
		. $loop($v('items'), $v('value') . '[0]=65;echo ' . $v('value') . ';')
		. 'echo ' . $v('items') . '[0];return 8;', 'Aong original string payloadlong original string payload');
	$emit('transfers', $setup . $loop($v('items'), 'if (' . $v('value') . '==1) { continue; } echo ' . $v('value') . ';break;') . 'return 8;', '2');
	$emit('clear', $setup . $loop($v('items'), 'echo ' . $v('value') . ';' . $v('items') . $member . 'clear();') . 'return 8;', '1');
	$emit('nested', $setup . $loop($v('items'), 'foreach (' . $v('items') . ' as ' . $v('inner') . ') {echo ' . $v('inner') . ';}') . 'return 8;', '123123123');
	$emit('empty', $decl('vector<int>', 'items', '[]') . $decl('int', 'value', '99') . $loop($v('items'), '') . 'echo ' . $v('value') . ';return 8;', '99');
	rejects(fn() => $prepare($decl('int', 'n', '1') . $loop($v('n'), '') . 'return 8;'), 'iterable');
	rejects(fn() => $prepare($setup . $loop($v('items'), '') . 'echo ' . $v('value') . ';return 8;'), 'Unknown');
	rejects(fn() => $prepare($setup . 'foreach (' . $v('items') . ' as &' . $v('value') . ') {} return 8;'), 'Expected');
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Foreach: all families, mutation, scopes and incremental recovery passed\n";
