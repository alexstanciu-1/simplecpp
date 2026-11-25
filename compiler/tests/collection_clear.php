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
	$param = static fn(string $type, string $name): string => $flavor === flavor::js ? $name . ':' . $type : $type . ' ' . $v($name);
	$function = static fn(string $type, string $name, string $params, string $body): string =>
		($flavor === flavor::cpp ? $type . ' ' . $name . '(' . $params . ')' : 'function ' . $name . '(' . $params . '):' . $type) . '{' . $body . '}';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('collection-clear', $text, $flavor), new entry_target(31));
	$member = $flavor === flavor::js ? '.' : '->';
	$field = static fn(string $type, string $name): string => match ($flavor) {
		flavor::php => 'public ' . $type . ' $' . $name . ';',
		flavor::cpp => $type . ' ' . $name . ';',
		flavor::js => $name . ':' . $type . ';',
	};
	$record = static fn(string $name, string $fields): string => 'struct ' . $name . '{' . $fields . '}' . ($flavor === flavor::cpp ? ';' : '');
	$ref = static fn(string $type, string $name): string => $flavor === flavor::js ? '&' . $name . ':' . $type : $type . ' &' . $v($name);
	foreach (['vector<int>', 'vector<string>', 'hash<int,int>', 'hash<int,string>', 'hash<string,int>', 'hash<string,string>'] as $type) {
		$is_vector = str_starts_with($type, 'vector');
		$key = str_ends_with($type, ',string>') ? '"key"' : '0';
		$value = str_contains($type, '<string') ? '"a long heap-backed string payload for clear"' : '10';
		$insert = static fn(string $place): string => $place . '[' . ($is_vector ? '' : $key) . ']=' . $value . ';';
		$clear = static fn(string $place): string => $place . $member . 'clear();';
		$path = static fn(string $name): string => $v($name) . $member . 'items';
		$model = $record('Box', $field($type, 'items'));
		$helpers = $function('shared<Box>', 'make', '', $decl('shared<Box>', 'p', 'new Box()') . $insert($path('p')) . 'return ' . $v('p') . ';')
			. $function('shared<Box>', 'receiver', $param('shared<Box>', 'p'), 'echo "R";return ' . $v('p') . ';')
			. $function('void', 'by_copy', $param($type, 'items'), $clear($v('items')))
			. $function('void', 'by_ref', $ref($type, 'items'), $clear($v('items')))
			. $function($type, 'temporary', '', $decl($type, 'items', '[]') . $insert($v('items')) . 'return ' . $v('items') . ';');
		$local = match ($flavor) {
			flavor::php => '$a Box;', flavor::cpp => 'Box a;', flavor::js => 'let a:Box;',
		};
		$main = $decl($type, 'items', '[]') . $clear($v('items')) . $insert($v('items'))
			. $decl($type, 'copy', $v('items')) . $clear($v('items')) . $clear($v('items'))
			. 'echo count(' . $v('items') . '),count(' . $v('copy') . '),"|";'
			. $insert($v('items')) . 'by_copy(' . $v('items') . ');echo count(' . $v('items') . ');'
			. 'by_ref(' . $v('items') . ');echo count(' . $v('items') . '),"|";'
			. $decl('shared<Box>', 'p', 'make()') . $decl('shared<Box>', 'q', $v('p'))
			. $clear('receiver(' . $v('p') . ')' . $member . 'items')
			. 'echo count(' . $path('q') . '),"|";' . $clear('make()' . $member . 'items') . $clear('temporary()')
			. $local . $insert($path('a')) . $decl('Box', 'b', $v('a')) . $clear($path('a'))
			. 'echo count(' . $path('a') . '),count(' . $path('b') . ');return 8;';
		$expected = '01|10|R0|01';
		$start = hrtime(true); $entry = $prepare($model . $helpers . $main); $before = serialize($entry);
		scpp\compiler\Syntax_Inspection::file($entry->file); scpp\compiler\Compilation_Inspection::program($entry);
		$cpp = (new Cpp_Emitter())->emit($entry); verify($before === serialize($entry), 'Lowering mutated facts');
		$name = $flavor->name . '_' . preg_replace('/[^a-z]+/', '_', $type);
		$fixtures[$name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected), 'compiler_ms' => (hrtime(true) - $start) / 1e6];
		rejects(fn() => $prepare($decl($type, 'items', '[]') . $v('items') . $member . 'clear(1);return 8;'), 'Argument count');
		rejects(fn() => $prepare($decl($type, 'items', '[]') . $decl('int', 'n', $v('items') . $member . 'clear()') . 'return 8;'), 'Void');
		$private = match ($flavor) {
			flavor::php => str_replace('public ' . $type, 'private ' . $type, $model),
			flavor::cpp => $record('Box', 'private: ' . $field($type, 'items')),
			flavor::js => $record('Box', 'private ' . $field($type, 'items')),
		};
		rejects(fn() => $prepare($private . $decl('shared<Box>', 'p', 'new Box()') . $clear($path('p')) . 'return 8;'), 'private');
		$bad = $prepare($model . $decl('shared<Box>', 'p', 'null') . $clear($path('p')) . 'return 8;');
		$fixtures[$name . '_empty_owner'] = ['cpp' => (new Cpp_Emitter())->emit($bad), 'stdout_hex' => '', 'catch_runtime_error' => true];
		// Both families exercise watched receiver edits, failures without edits, deletion and recovery.
		if (!in_array($type, ['vector<int>', 'hash<int,string>'], true)) continue;
		$directory = sys_get_temp_dir() . '/scpp-clear-' . bin2hex(random_bytes(8)); mkdir($directory);
		try {
			$provider = $directory . '/library.' . $flavor->name; file_put_contents($directory . '/main.' . $flavor->name, $main);
			file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => ['main.' . $flavor->name, basename($provider)]]));
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp = time(); $caller = null;
			$initial = $model . $helpers;
			foreach ([$initial, str_replace('echo "R"', 'echo "E"', $initial), $record('Box', $field('int', 'items')) . $helpers, $helpers, $initial] as $index => $text) {
				file_put_contents($provider, $text); touch($provider, ++$stamp);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($index === 2 || $index === 3) {
					rejects(fn() => $project->run(), ''); rejects(fn() => $project->run(), ''); rejects(fn() => $fresh->run(), ''); continue;
				}
				$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
				verify($caller === $project->session()->entry->entry->ast, 'Caller reparsed');
				$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Fresh differs');
				$fixtures[$name . '_edit_' . $index] = ['files' => $files, 'stdout_hex' => bin2hex($index === 1 ? str_replace('R', 'E', $expected) : $expected)];
				verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Collection clear: methods, expression receivers, copies, references and incremental recovery passed\n";
