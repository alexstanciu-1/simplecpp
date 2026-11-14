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
foreach (flavor::cases() as $flavor) {
	$v = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . ' ' . $type . ' = ' . $value . ';',
		flavor::cpp => $type . ' ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . ':' . $type . ' = ' . $value . ';',
	};
	$param = static fn(string $type, string $name): string => $flavor === flavor::js ? $name . ':' . $type : $type . ' ' . $v($name);
	$function = static fn(string $type, string $name, string $params, string $body): string =>
		($flavor === flavor::cpp ? $type . ' ' . $name . '(' . $params . ')' : 'function ' . $name . '(' . $params . '):' . $type) . '{' . $body . '}';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('collection-remove', $text, $flavor), new entry_target(31));
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
		$is_string = str_contains($type, '<string');
		$key_type = str_ends_with($type, ',string>') ? 'string' : 'int';
		$keys = $key_type === 'string' ? ['"a\\0x"', '"b"', '"c"'] : ['0', '1', '2'];
		$values = $is_string ? ['"a"', '"b"', '"c"'] : ['10', '20', '30'];
		$insert = static function(string $place) use ($is_vector, $keys, $values): string {
			$text = '';
			foreach ($keys as $i => $key) $text .= $place . '[' . ($is_vector ? '' : $key) . ']=' . $values[$i] . ';';
			return $text;
		};
		$remove = static fn(string $place, string $key): string => $place . $member . 'remove(' . $key . ')';
		$result = static fn(string $place, string $key): string => 'flag(' . $remove($place, $key) . ')';
		$path = static fn(string $name): string => $v($name) . $member . 'items';
		$model = $record('Box', $field($type, 'items'));
		$helpers = $function('int', 'flag', $param('bool', 'value'), 'if (' . $v('value') . ') {return 1;}return 0;')
			. $function('shared<Box>', 'make', '', $decl('shared<Box>', 'p', 'new Box()') . $insert($path('p')) . 'return ' . $v('p') . ';')
			. $function('shared<Box>', 'receiver', $param('shared<Box>', 'p'), 'echo "R";return ' . $v('p') . ';')
			. $function($key_type, 'key_replace', $ref('shared<Box>', 'p'), 'echo "K";' . $v('p') . '=make();return ' . $keys[1] . ';')
			. $function('void', 'by_copy', $param($type, 'items'), $remove($v('items'), $keys[1]) . ';')
			. $function('void', 'by_ref', $ref($type, 'items'), $remove($v('items'), $keys[1]) . ';')
			. $function($type, 'temporary', '', $decl($type, 'items', '[]') . $insert($v('items')) . 'return ' . $v('items') . ';');
		$local = match ($flavor) {
			flavor::php => '$a Box;', flavor::cpp => 'Box a;', flavor::js => 'let a:Box;',
		};
		$remaining = $is_vector ? '1' : $keys[2];
		$main = $decl($type, 'items', '[]') . 'echo ' . $result($v('items'), $keys[0]) . ',"|";'
			. $insert($v('items')) . $decl($type, 'copy', $v('items'))
			. 'echo ' . $result($v('items'), $keys[1]) . ',count(' . $v('items') . '),count(' . $v('copy') . '),"|";'
			. 'echo ' . $v('items') . '[' . $keys[0] . '],",",' . $v('items') . '[' . $remaining . '],"|";'
			. 'by_copy(' . $v('copy') . ');echo count(' . $v('copy') . ');by_ref(' . $v('copy') . ');echo count(' . $v('copy') . '),"|";'
			. $decl('shared<Box>', 'p', 'make()') . $decl('shared<Box>', 'q', $v('p'))
			. 'echo ' . $result('receiver(' . $v('p') . ')' . $member . 'items', 'key_replace(' . $v('p') . ')') . ',"|";'
			. 'echo count(' . $path('q') . '),count(' . $path('p') . '),"|";'
			// No other strong observer: argument replacement must not destroy the selected allocation.
			. $v('q') . '=null;echo ' . $result($path('p'), 'key_replace(' . $v('p') . ')') . ',"|";'
			. 'echo ' . $result('make()' . $member . 'items', $keys[1]) . ',' . $result('temporary()', $keys[1]) . ',"|";'
			. $local . $insert($path('a')) . $decl('Box', 'b', $v('a')) . $result($path('a'), $keys[1]) . ';'
			. 'echo count(' . $path('a') . '),count(' . $path('b') . '),"|";'
			. 'echo ' . $result($v('items'), $key_type === 'string' ? '"missing"' : '-1') . ','
			. $result($v('items'), $key_type === 'string' ? '"a"' : '9223372036854775807') . ',"|";'
			. 'echo ' . $result($v('items'), $keys[0]) . ',count(' . $v('items') . '),'
			. $result($v('items'), $is_vector ? '0' : $keys[2]) . ',' . $result($v('items'), $keys[0]) . ',count(' . $v('items') . '),"|";'
			. $insert($v('items')) . 'echo ' . $result($v('items'), $keys[2]) . ',count(' . $v('items') . ');return 8;';
		$expected = '0|123|' . ($is_string ? 'a,c' : '10,30') . '|32|RK1|23|K1|11|23|00|11100|12';
		$start = hrtime(true); $entry = $prepare($model . $helpers . $main); $before = serialize($entry);
		scpp\compiler\Syntax_Inspection::file($entry->file); scpp\compiler\Compilation_Inspection::program($entry);
		$cpp = (new Cpp_Emitter())->emit($entry); verify($before === serialize($entry), 'Lowering mutated facts');
		$name = $flavor->name . '_' . preg_replace('/[^a-z]+/', '_', $type);
		$fixtures[$name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected), 'compiler_ms' => (hrtime(true) - $start) / 1e6];
		foreach (['', $keys[0] . ',' . $keys[1]] as $args) {
			rejects(fn() => $prepare($decl($type, 'items', '[]') . $remove($v('items'), $args) . ';return 8;'), 'Argument count');
		}
		rejects(fn() => $prepare($decl($type, 'items', '[]') . $remove($v('items'), $key_type === 'string' ? '0' : '"x"') . ';return 8;'), 'remove requires');
		$private = match ($flavor) {
			flavor::php => str_replace('public ' . $type, 'private ' . $type, $model),
			flavor::cpp => $record('Box', 'private: ' . $field($type, 'items')),
			flavor::js => $record('Box', 'private ' . $field($type, 'items')),
		};
		rejects(fn() => $prepare($private . $decl('shared<Box>', 'p', 'new Box()') . $remove($path('p'), $keys[0]) . ';return 8;'), 'private');
		$bad = $prepare($model . $helpers . $decl('shared<Box>', 'p', 'null') . $remove($path('p'), 'key_replace(' . $v('p') . ')') . ';return 8;');
		$fixtures[$name . '_empty_owner'] = ['cpp' => (new Cpp_Emitter())->emit($bad), 'stdout_hex' => '', 'catch_runtime_error' => true];
		if (!in_array($type, ['vector<int>', 'hash<int,string>'], true)) continue;
		$directory = sys_get_temp_dir() . '/scpp-remove-' . bin2hex(random_bytes(8)); mkdir($directory);
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
echo "Collection removal: methods, receiver retention, copies and incremental recovery passed\n";
