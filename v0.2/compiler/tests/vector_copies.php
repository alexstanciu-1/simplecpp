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
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . ' ' . $type . ' = ' . $value . ';',
		flavor::cpp => $type . ' ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . ':' . $type . ' = ' . $value . ';',
	};
	$infer = static fn(string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . '=' . $value . ';',
		flavor::cpp => 'auto ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . '=' . $value . ';',
	};
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('vector-copies', $source, $flavor), new entry_target(31));
	foreach (['int', 'string'] as $element) {
		$type = 'vector<' . $element . '>';
		$changed = $element === 'int' ? '99' : '"changed"';
		$a = $var('a'); $b = $var('b'); $c = $var('c'); $empty = $var('empty_vector');
		$main = $decl($type, 'a', '[]') . $a . '[]=value();' . $infer('b', $a) . $decl($element === 'int' ? 'vector<int64>' : $type, 'c', $b)
			. $b . '[0]=' . $changed . ';' . $b . '[]=' . $changed . ';'
			. 'echo ' . $a . '[0],"|",' . $b . '[0],"|",' . $c . '[0],"|",count(' . $b . '),"|";'
			. $b . '=' . $a . ';' . $a . '=' . $a . ';' . $decl($type, 'empty_vector', '[]') . $c . '=' . $empty . ';'
			. $infer('empty_copy', $empty) . $var('empty_copy') . '[]=' . $changed . ';'
			. 'echo ' . $b . '[0],"|",count(' . $b . '),"|",count(' . $c . '),"|",count(' . $a . '),"|",count(' . $empty . '),"|",count(' . $var('empty_copy') . ');return 8;';
		$function = static fn(string $return, string $literal): string => ($flavor === flavor::cpp
			? $return . ' value()' : 'function value():' . $return) . '{return ' . $literal . ';}';
		$first = $element === 'int' ? '8' : '"long heap-backed text with embedded\\0NUL"';
		$first_bytes = $element === 'int' ? '8' : "long heap-backed text with embedded\0NUL";
		$expected = static fn(string $value): string => $value . '|' . ($element === 'int' ? '99' : 'changed') . '|' . $value . '|2|' . $value . '|1|0|1|0|1';
		$start = hrtime(true);
		$entry = $prepare($function($element, $first) . $main);
		$compiler_ms = (hrtime(true)-$start)/1e6;
		$before = serialize($entry);
		$start = hrtime(true);
		$cpp = (new Cpp_Emitter())->emit($entry);
		$compiler_ms += (hrtime(true)-$start)/1e6;
		verify($before === serialize($entry), 'Lowering mutated prepared facts');
		verify(!str_contains($cpp, 'std::vector') && !str_contains($cpp, 'vector<'), 'Application contains templates');
		$fixtures[$flavor->name . '_' . $element] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected($first_bytes)), 'compiler_ms' => $compiler_ms];
		$returning = ($flavor === flavor::cpp ? $type . ' unsupported()' : 'function unsupported():' . $type)
			. '{' . $decl($type, 'a', '[]') . 'return ' . $a . ';}return 8;';
		$prepare($returning);
		$directory = sys_get_temp_dir() . '/scpp-vector-copy-' . bin2hex(random_bytes(8)); mkdir($directory);
		try {
			$entry_path = $directory . '/main.' . $flavor->name; $provider = $directory . '/value.' . $flavor->name;
			file_put_contents($directory . '/module.json', json_encode(['entry' => basename($entry_path), 'sources' => [basename($entry_path),basename($provider)]]));
			file_put_contents($entry_path, $main);
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp=time(); $caller=null;
			foreach ([[$element,$first,null],[$element,$changed,null],[$element === 'int' ? 'string' : 'int',$element === 'int' ? '"bad"' : '8','conversion'],
				[$element,null,'Unknown'],[$element,$first,null]] as $index => [$return,$literal,$error]) {
				file_put_contents($provider, $literal === null ? '' : $function($return,$literal)); touch($provider,++$stamp);
				$fresh = new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
				if ($error !== null) {
					rejects(fn() => $project->run(),$error); rejects(fn() => $project->run(),$error); rejects(fn() => $fresh->run(),$error);
					continue;
				}
				$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
				verify($caller === $project->session()->entry->entry->ast, 'Caller reparsed');
				$files = artifacts($directory.'/out'); verify($files === artifacts($directory.'/fresh'), 'Fresh output differs');
				$fixtures[$flavor->name . '_' . $element . '_edit_' . $index] = ['files' => $files, 'stdout_hex' => bin2hex($expected($index === 1 ? ($element === 'int' ? '99' : 'changed') : $first_bytes))];
				verify($project->run()['preparation']['prepared'] === 0, 'No-op prepares bodies');
			}
		} finally {
			remove_tree($directory);
		}
	}
	rejects(fn() => $prepare($decl('vector<int>','a','[]') . $decl('vector<string>','b','[]') . $var('b') . '=' . $var('a') . ';return 8;'), 'conversion');
}
if (isset($argv[1])) file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "Vector local copies: independence, assignment and incremental/fresh recovery passed\n";
