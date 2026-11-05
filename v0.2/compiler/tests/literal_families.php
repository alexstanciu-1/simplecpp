<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, entry_target, diagnostic, diagnostic_group, Incremental_Project, Source_Frontend, source_file, type_registry};

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

/** Flavor-specific local declaration; global declarations reuse this spelling with a prefix. */
function declaration(flavor $flavor, ?string $type, string $value): string
{
	return match ($flavor) {
		flavor::php => '$item' . ($type === null ? '' : ' ' . $type) . ' = ' . $value . ';',
		flavor::cpp => ($type ?? 'auto') . ' item = ' . $value . ';',
		flavor::js => 'let item' . ($type === null ? '' : ': ' . $type) . ' = ' . $value . ';',
	};
}

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('family', $text, $flavor), new entry_target(31));
	foreach (['int' => '8', 'float' => '8.5', 'bool' => 'true', 'null' => 'null', 'string' => '"A\0B"'] as $type => $literal) {
		$entry = $prepare(declaration($flavor, $type, $literal) . 'return 8;');
		$value = $entry->body()->statements[0]->initializer;
		verify(type_registry::literal_type($value) === type_registry::resolve($type), 'Wrong literal-family identity');
		verify($value->type_def() === type_registry::literal_type($value), 'Settled literal identity differs');
	}
	verify(type_registry::literal('false') === type_registry::resolve('bool'), 'False keyword owner');
	verify(type_registry::literal('unknown') === null, 'Unknown literal spelling admitted');
	$directory = sys_get_temp_dir() . '/scpp-literal-family-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name]], JSON_THROW_ON_ERROR));
		$path = $directory . '/main.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $value) use ($path, $flavor, &$stamp): void {
			file_put_contents($path, declaration($flavor, $type, $value) . 'echo "ok"; return 8;');
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('int', '8');
		$project->run();
		foreach ([['int', '9223372036854775808', 'range'], ['float', '-0.0', null],
			['float', '1e309', 'finite'], ['bool', 'false', null], ['null', 'null', null],
			['string', '"bad\q"', 'escape'], ['string', '"A\0B"', null], ['int', '9', null]] as [$type, $literal, $error]) {
			$write($type, $literal);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($error !== null) {
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $project->run(), $error);
				rejects(fn() => $fresh->run(), $error);
			} else {
				$project->run();
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Family recovery differs from fresh compilation');
				$fixtures[$flavor->name . '_' . $type] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('ok')];
			}
		}
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Literal-family identity, diagnostics and incremental/fresh recovery passed in all flavors\n";
