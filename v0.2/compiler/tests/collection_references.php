<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

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
	$ref = static fn(string $type, string $name): string => $flavor === flavor::js ? '&' . $name . ':' . $type : $type . ' &' . $v($name);
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('collection-references', $text, $flavor), new entry_target(31));
	foreach (['vector<int>', 'vector<string>', 'hash<int,int>', 'hash<int,string>', 'hash<string,int>', 'hash<string,string>'] as $type) {
		$string = str_starts_with($type, 'vector<string') || str_starts_with($type, 'hash<string');
		$key = str_ends_with($type, ',string>') ? '"key"' : '0';
		$first = $string ? '"long heap-backed value with\\0NUL"' : '7';
		$second = $string ? '"changed"' : '9';
		$bytes = $string ? "long heap-backed value with\0NUL" : '7';
		$changed = $string ? 'changed' : '9';
		$slot = $v('x') . '[' . $key . ']';
		$init = str_starts_with($type, 'vector') ? $v('x') . '[]' : $slot;
		$factory = $function($type, 'make', '', $decl($type, 'x', '[]') . $init . '=' . $first . ';return ' . $v('x') . ';');
		$mutation = $function('void', 'mutate', $ref($type, 'x'), $slot . '=' . $second . ';');
		$provider = $factory . $mutation
			. $function('void', 'replace', $ref($type, 'x'), $v('x') . '=make();')
			. $function('void', 'forward', $ref($type, 'x'), 'mutate(' . $v('x') . ');')
			. $function('void', 'isolate', $param($type, 'x'), 'forward(' . $v('x') . ');')
			. $function('void', 'alias', $ref($type, 'x') . ',' . $ref($type, 'y'), $v('x') . '=make();' . $v('y') . '[' . $key . ']=' . $second . ';');
		$ctor_body = 'forward(' . $v('x') . ');';
		$method_body = 'replace(' . $v('x') . ');';
		$model = match ($flavor) {
			flavor::php => 'struct Box {public function __construct(' . $param($type, 'x') . '){' . $ctor_body . '}public '
				. $function('void', 'reset', $ref($type, 'x'), $method_body) . '}',
			flavor::cpp => 'struct Box {Box(' . $param($type, 'x') . '){' . $ctor_body . '}'
				. $function('void', 'reset', $ref($type, 'x'), $method_body) . '};',
			flavor::js => 'struct Box {constructor(' . $param($type, 'x') . '){' . $ctor_body . '}reset('
				. $ref($type, 'x') . '):void {' . $method_body . '}}',
		};
		$observe = 'echo ' . $v('a') . '[' . $key . '],"|";';
		$main = $decl(str_replace('int', 'int64', $type), 'a', 'make()') . $decl($type, 'b', $v('a'))
			. 'isolate(' . $v('a') . ');' . $observe . 'forward(' . $v('a') . ');' . $observe
			. 'replace(' . $v('a') . ');' . $observe . 'alias(' . $v('a') . ',' . $v('a') . ');' . $observe
			. 'echo ' . $v('b') . '[' . $key . '],"|";replace(' . $v('a') . ');'
			. $decl('shared<Box>', 'box', 'new Box(' . $v('a') . ')') . $observe
			. $v('a') . '[' . $key . ']=' . $second . ';'
			. $v('box') . ($flavor === flavor::js ? '.' : '->') . 'reset(' . $v('a') . ');' . $observe;
		$tail = '';
		if (str_starts_with($type, 'vector')) {
			$provider .= $function('void', 'append', $ref($type, 'x'), $v('x') . '[]=' . $first . ';');
			$main .= 'append(' . $v('a') . ');echo count(' . $v('a') . ');';
			$tail = '2';
		}
		$main .= 'return 8;';
		$expected = static fn(string $effect): string => implode('|', [$bytes, $effect, $bytes, $changed, $bytes, $bytes, $bytes]) . '|' . $tail;
		$start = hrtime(true);
		$entry = $prepare($provider . $model . $main); $before = serialize($entry);
		$cpp = (new Cpp_Emitter())->emit($entry);
		verify($before === serialize($entry), 'Lowering mutated prepared facts');
		$name = $flavor->name . '_' . preg_replace('/[^a-z]+/', '_', $type);
		$fixtures[$name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected($changed)), 'compiler_ms' => (hrtime(true)-$start)/1e6];
		foreach (['make()', $decl($type, 'a', 'make()') . '@' . $v('a') . '[' . $key . ']'] as $invalid) {
			$parts = explode('@', $invalid);
			$prefix = count($parts) === 2 ? $parts[0] : '';
			$argument = $parts[count($parts)-1];
			rejects(fn() => $prepare($provider . $prefix . 'mutate(' . $argument . ');return 8;'), 'named local or parameter');
		}
		$wrong = $type === 'vector<int>' ? 'vector<string>' : 'vector<int>';
		rejects(fn() => $prepare($provider . $decl($wrong, 'a', '[]') . 'mutate(' . $v('a') . ');return 8;'), 'exact storage type');
		$property = match ($flavor) {
			flavor::php => 'struct Bad {public int $n;} $bad Bad; mutate($bad->n);',
			flavor::cpp => 'struct Bad {int n;}; Bad bad; mutate(bad->n);',
			flavor::js => 'struct Bad {n:int;} let bad:Bad; mutate(bad.n);',
		};
		rejects(fn() => $prepare($provider . $property . 'return 8;'), 'named local or parameter');
		rejects(fn() => $prepare($function('void', 'bad', $ref($type, 'x') . ' = null', 'return;') . 'return 8;'), 'Reference parameter defaults');
		$directory = sys_get_temp_dir() . '/scpp-collection-ref-' . bin2hex(random_bytes(8)); mkdir($directory);
		try {
			$path = $directory . '/library.' . $flavor->name;
			file_put_contents($directory . '/main.' . $flavor->name, $main);
			file_put_contents($directory . '/model.' . $flavor->name, $model);
			file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name, 'sources' => ['main.' . $flavor->name, 'model.' . $flavor->name, basename($path)]]));
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp=time(); $caller=null;
			$edits = [$provider, str_replace($mutation, str_replace($second, $first, $mutation), $provider),
				str_replace($mutation, str_replace($ref($type, 'x'), $param($type, 'x'), $mutation), $provider),
				str_replace($mutation, $function('void', 'mutate', $ref($wrong, 'x'), 'return;'), $provider),
				str_replace($mutation, '', $provider), $provider];
			foreach ($edits as $index => $text) {
				file_put_contents($path, $text); touch($path, ++$stamp);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($index === 3 || $index === 4) {
					$error = $index === 3 ? 'exact storage type' : 'Unknown';
					rejects(fn() => $project->run(), $error); rejects(fn() => $project->run(), $error); rejects(fn() => $fresh->run(), $error);
					continue;
				}
				$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
				verify($caller === $project->session()->entry->entry->ast, 'Caller reparsed');
				$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Fresh differs');
				$fixtures[$name . '_edit_' . $index] = ['files' => $files, 'stdout_hex' => bin2hex($expected($index === 1 || $index === 2 ? $bytes : $changed))];
				verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Collection references: write-back, replacement, forwarding, aliasing and incremental recovery passed\n";
