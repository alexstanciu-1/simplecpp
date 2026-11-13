<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, entry_target, diagnostic, diagnostic_group, Incremental_Project, Source_Frontend, source_file, Cpp_Emitter};

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
	$function = static fn(string $name, string $type, string $body): string =>
		($flavor === flavor::cpp ? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('echo', $text, $flavor), new entry_target(31));
	$text = $function('first', 'int', 'echo "F"; return 10;')
		. $function('second', 'string', 'echo "S"; return "e\0nd";')
		. 'echo "A\0", first(), second(), "\n"; return 8;';
	$entry = $prepare($text);
	$echo = $entry->body()->statements[0];
	verify(count($echo->values) === 4, 'Missing echo operands');
	foreach ($echo->values as $value) {
		verify($value->parent === $echo, 'Missing echo parent');
	}
	$before = serialize($entry);
	$fixtures[$flavor->name . '_order'] = ['cpp' => (new Cpp_Emitter())->emit($entry), 'stdout_hex' => bin2hex("A\0F10Se\0nd\n")];
	verify(str_contains($fixtures[$flavor->name . '_order']['cpp'], 'scpp::echo_string(second_());'), 'Expected direct string output');
	verify(serialize($entry) === $before, 'Output lowering mutated facts');
	$failure = $function('bad', 'int', 'echo "B"; return 1 / 0;')
		. $function('later', 'int', 'echo "L"; return 2;')
		. 'echo "before", bad(), later(); return 8;';
	$fixtures[$flavor->name . '_failure'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
		'stdout_hex' => bin2hex('beforeB'), 'catch_domain_error' => 'division_by_zero'];

	foreach (['true', 'null', '1.5', '(byte) 8'] as $value) {
		rejects(fn() => $prepare('echo "prefix", ' . $value . ', "suffix"; return 8;'), 'Echo currently supports only int64 and string values');
	}
	foreach (['echo;', 'echo 1,;'] as $invalid) {
		rejects(fn() => $prepare($invalid), '');
	}
	$directory = sys_get_temp_dir() . '/scpp-echo-chain-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'echo ">", value(), "<"; return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $value) use ($function, $path, &$stamp): void {
			file_put_contents($path, $type === '' ? '' : $function('value', $type, 'return ' . $value . ';'));
			touch($path, ++$stamp);
		};
		$write('int', '8');
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['string', '"B"'], ['bool', 'true'], ['string', '"C"'], ['', ''], ['int', '10']] as [$type, $value]) {
			$write($type, $value);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($type === 'bool' || $type === '') {
				rejects(fn() => $project->run(), '');
				rejects(fn() => $project->run(), '');
				rejects(fn() => $fresh->run(), '');
			} else {
				$project->run();
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Echo recovery differs from fresh output');
				$fixtures[$flavor->name . '_recovery_' . $value] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('>' . trim($value, '"') . '<')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged echo caller reparsed');
		}
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Chained echo ownership, admission, purity and incremental/fresh recovery passed\n";
