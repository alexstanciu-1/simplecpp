<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, entry_target, diagnostic, diagnostic_group, Incremental_Project, Source_Frontend, source_file, Cpp_Emitter, Cpp_Exit, exit_conversion};

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

verify(Cpp_Exit::expression(exit_conversion::proven, 'value') === 'static_cast<int>(value)', 'Proven spelling changed');
verify(Cpp_Exit::expression(exit_conversion::checked, 'value') === 'scpp::entry_exit_checked(value)', 'Checked spelling changed');
$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$function = static fn(string $type, string $value): string =>
		($flavor === flavor::cpp ? "$type result()" : "function result(): $type") . '{ return ' . $value . '; }';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('entry', $text, $flavor), new entry_target(31));
	$fixtures[$flavor->name . '_proven'] = ['cpp' => (new Cpp_Emitter())->emit($prepare('return 8;')), 'stdout_hex' => ''];
	$checked = $prepare($function('int', '8') . 'return result();');
	verify($checked->body()->statements[0]->conversion() === exit_conversion::checked, 'Unknown exit lost runtime check');
	$fixtures[$flavor->name . '_checked'] = ['cpp' => (new Cpp_Emitter())->emit($checked), 'stdout_hex' => ''];
	foreach (['2147483648', '-2147483649'] as $outside) {
		if ($outside === '2147483648') {
			rejects(fn() => $prepare('return ' . $outside . ';'), 'Entry return is outside native int range');
		}
		$fixtures[$flavor->name . '_outside_' . $outside] = ['cpp' => (new Cpp_Emitter())->emit($prepare($function('int', $outside) . 'return result();')),
			'stdout_hex' => '', 'catch_out_of_range' => true];
	}
	$directory = sys_get_temp_dir() . '/scpp-entry-binding-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'return result();');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $value) use ($path, $function, &$stamp): void {
			file_put_contents($path, $type === '' ? '' : $function($type, $value));
			touch($path, ++$stamp);
		};
		$write('int', '8');
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['bool', 'true'], ['int64', '8'], ['', ''], ['int', '8']] as [$type, $value]) {
			$write($type, $value);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($type === 'bool' || $type === '') {
				rejects(fn() => $project->run(), '');
				rejects(fn() => $project->run(), '');
				rejects(fn() => $fresh->run(), '');
			} else {
				$project->run();
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Entry recovery differs from fresh compilation');
				$fixtures[$flavor->name . '_recovery'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => ''];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged entry AST replaced');
		}
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Entry binding spelling, preparation and incremental/fresh recovery passed in all flavors\n";
