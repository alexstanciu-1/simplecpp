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
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('policy', $text, $flavor), new entry_target(31));
	foreach (['int' => '8', 'bool' => 'true', 'byte' => '8', 'float' => '8.5', 'null' => 'null', 'string' => '"text"'] as $type => $literal) {
		$definition = type_registry::resolve($type);
		verify($definition->supports_inferred_local() === ($type !== 'null'), 'Local capability changed');
		verify($definition->supports_global_storage() === ($type !== 'string'), 'Global capability changed');
		$prepare(declaration($flavor, $type, $literal) . 'return 8;');
		$inferred = declaration($flavor, null, $type === 'byte' ? '(byte) 8' : $literal) . 'return 8;';
		if ($type === 'null') rejects(fn() => $prepare($inferred), 'Cannot infer a local type from null; write an explicit type');
		else $prepare($inferred);
		$global = 'global ' . declaration($flavor, $type, $type === 'string' ? '8' : $literal) . 'return 8;';
		if ($type === 'string') rejects(fn() => $prepare($global), 'String globals are deferred');
		else $prepare($global);
	}
	foreach (['local', 'global'] as $kind) {
		$directory = sys_get_temp_dir() . '/scpp-declaration-policy-' . bin2hex(random_bytes(8));
		mkdir($directory);
		try {
			file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name]], JSON_THROW_ON_ERROR));
			$path = $directory . '/main.' . $flavor->name;
			$stamp = time();
			$write = static function (string $value, ?string $type = null) use ($path, $flavor, $kind, &$stamp): void {
				file_put_contents($path, ($kind === 'global' ? 'global ' : '') . declaration($flavor, $type, $value) . 'echo "ok"; return 8;');
				touch($path, ++$stamp);
			};
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
			$write('8');
			$project->run();
			foreach (['fail', 'recover', 'fail', 'recover'] as $step) {
				$write($step === 'fail' && $kind === 'local' ? 'null' : '9', $step === 'fail' && $kind === 'global' ? 'string' : null);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($step === 'fail') {
					$message = $kind === 'local' ? 'Cannot infer a local type from null; write an explicit type' : 'String globals are deferred';
					rejects(fn() => $project->run(), $message);
					rejects(fn() => $project->run(), $message);
					rejects(fn() => $fresh->run(), $message);
				} else {
					$project->run();
					$fresh->run();
					verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Policy recovery differs from fresh compilation');
					$fixtures[$flavor->name . '_' . $kind] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('ok')];
				}
			}
			verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
		} finally {
			remove_tree($directory);
		}
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Declaration admission, diagnostics and incremental/fresh recovery passed in all flavors\n";
