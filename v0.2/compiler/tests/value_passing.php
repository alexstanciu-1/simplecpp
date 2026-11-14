<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_availability, Program_Preparation, Symbol_Publication};

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
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('value-passing', $text, $flavor), new entry_target(31));
	foreach (['vector<int>', 'vector<string>', 'hash<int,int>', 'hash<int,string>', 'hash<string,int>', 'hash<string,string>'] as $type) {
		$string = str_starts_with($type, 'vector<string') || str_starts_with($type, 'hash<string');
		$key = str_ends_with($type, ',string>') ? '"key"' : '0';
		$first = $string ? '"long heap-backed value with\\0NUL"' : '7';
		$second = $string ? '"changed"' : '9';
		$bytes = $string ? "long heap-backed value with\0NUL" : '7';
		$changed = $string ? 'changed' : '9';
		$slot = $v('x') . '[' . $key . ']';
		$init = str_starts_with($type, 'vector') ? $v('x') . '[]' : $slot;
		$factory = static fn(string $literal): string => $function($type, 'make', '', $decl($type, 'x', '[]') . $init . '=' . $literal . ';return ' . $v('x') . ';');
		$alter_body = $slot . '=' . $second . ';return ' . $v('x') . ';';
		$provider = $factory($first) . $function($type, 'alter', $param($type, 'x') . ',' . $param('int', 'unused'), $alter_body)
			. $function($type, 'forward', $param($type, 'x'), 'return ' . $v('x') . ';');
		$method = match ($flavor) {
			flavor::php => 'public ' . $function($type, 'copy', $param($type, 'x'), $alter_body),
			flavor::cpp => $function($type, 'copy', $param($type, 'x'), $alter_body),
			flavor::js => 'copy(' . $param($type, 'x') . '):' . $type . '{' . $alter_body . '}',
		};
		$ctor_body = $slot . '=' . $second . ';';
		$ctor = match ($flavor) {
			flavor::php => 'public function __construct(' . $param($type, 'x') . '){' . $ctor_body . '}',
			flavor::cpp => 'Box(' . $param($type, 'x') . '){' . $ctor_body . '}',
			flavor::js => 'constructor(' . $param($type, 'x') . '){' . $ctor_body . '}',
		};
		$model = 'struct Box {' . $ctor . $method . '}' . ($flavor === flavor::cpp ? ';' : '');
		$main = $decl($type, 'a', 'make()') . $decl($type, 'b', 'alter(' . $v('a') . ',1)')
			. $decl($type, 'c', 'forward(make())') . $decl('shared<Box>', 'box', 'new Box(' . $v('a') . ')')
			. $decl($type, 'd', $v('box') . ($flavor === flavor::js ? '.' : '->') . 'copy(' . $v('a') . ')')
			. $v('c') . '=' . $v('b') . ';' . $v('b') . '[' . $key . ']=' . $first . ';'
			. 'echo ' . $v('a') . '[' . $key . '],"|",' . $v('c') . '[' . $key . '],"|",' . $v('d') . '[' . $key . '];return 8;';
		$start = hrtime(true);
		$entry = $prepare($provider . $model . $main);
		$before = serialize($entry);
		$cpp = (new Cpp_Emitter())->emit($entry);
		verify($before === serialize($entry), 'Lowering mutated prepared facts');
		$name = $flavor->name . '_' . preg_replace('/[^a-z]+/', '_', $type);
		$fixtures[$name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($bytes . '|' . $changed . '|' . $changed), 'compiler_ms' => (hrtime(true)-$start)/1e6];
		$reference = str_replace($param($type, 'x'), $flavor === flavor::js ? '&x:' . $type : $type . ' &' . $v('x'), $function('void', 'bad', $param($type, 'x'), 'return;'));
		$prepare($reference . 'return 8;');
		$directory = sys_get_temp_dir() . '/scpp-value-passing-' . bin2hex(random_bytes(8)); mkdir($directory);
		try {
			$path = $directory . '/library.' . $flavor->name;
			file_put_contents($directory . '/main.' . $flavor->name, $main);
			file_put_contents($directory . '/model.' . $flavor->name, $model);
			file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => ['main.' . $flavor->name, 'model.' . $flavor->name, basename($path)]]));
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp=time(); $caller=null;
			foreach ([$provider, str_replace($factory($first), $factory($second), $provider),
				str_replace($factory($first), $function('int', 'make', '', 'return 2;'), $provider), '', $provider] as $index => $text) {
				file_put_contents($path, $text); touch($path, ++$stamp);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($index === 2 || $index === 3) {
					$error = $index === 2 ? 'conversion' : 'Unknown';
					rejects(fn() => $project->run(), $error); rejects(fn() => $project->run(), $error); rejects(fn() => $fresh->run(), $error);
					continue;
				}
				$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
				verify($caller === $project->session()->entry->entry->ast, 'Caller reparsed');
				$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Fresh differs');
				$fixtures[$name . '_edit_' . $index] = ['files' => $files, 'stdout_hex' => bin2hex(($index === 1 ? $changed : $bytes) . '|' . $changed . '|' . $changed)];
				verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Common value passing: calls, methods, constructors, copies and incremental recovery passed\n";
