<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

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
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $params, string $type, string $body): string =>
		($flavor === flavor::cpp ? "$type $name($params)" : "function $name($params): $type") . '{' . $body . '}';
	$decl = static fn(string $value): string => match ($flavor) {
		flavor::php => '$x float=' . $value . ';',
		flavor::cpp => 'float x=' . $value . ';',
		flavor::js => 'let x: float=' . $value . ';',
	};
	$param = match ($flavor) {
		flavor::php => 'float64 $v',
		flavor::cpp => 'float64 v',
		flavor::js => 'v: float64',
	};
	$ref = match ($flavor) {
		flavor::php => 'float &$v',
		flavor::cpp => 'float &v',
		flavor::js => '&v: float',
	};
	$x = $var('x');
	$v = $var('v');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('float_updates', $code, $flavor), new entry_target(31));
	$entry = $prepare($decl('8.0') . $x . '++; return 8;');
	verify($entry->body()->expression_visits === 2, 'Updates must not synthesize operand visits');
	$write = $entry->body()->statements[1];
	verify($write->local_def() === $entry->body()->statements[0], 'Lost storage binding');
	$code = $decl('8.0');
	foreach (['++' . $x, $x . '++', '--' . $x, $x . '--'] as $form) {
		$code .= $form . ';';
	}
	$code .= 'if (' . $x . '==8.0) { echo "A"; }'
		. $fn('bump', $ref, 'void', $v . '++; return;')
		. $fn('copy', $param, 'float', '--' . $v . '; return ' . $v . ';')
		. 'bump(' . $x . '); if (' . $x . '==9.0 && copy(' . $x . ')==8.0 && ' . $x . '==9.0) { echo "B"; }'
		. $x . '=0.0; for (++' . $x . ';' . $x . '<4.0;' . $x . '++) { echo "F"; }'
		. 'for (' . $x . '--;' . $x . '>0.0;--' . $x . ') { echo "H"; }'
		. 'return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	verify(!str_contains($cpp, 'scpp/arithmetic.hpp'), 'Updates added an unnecessary helper');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('ABFFFHHH')];
	foreach (['echo ' . $x . '++;', 'echo ++' . $x . ';', '++' . $x . '++;'] as $invalid) {
		rejects(fn() => $prepare($decl('1.0') . $invalid . 'return 8;'), 'Expected');
	}
	// A previous literal must not survive the update as a known-value conversion fact.
	$entry = $prepare($decl('9223372036854775808.0') . $x . '++; echo (int)' . $x . '; return 8;');
	$fixtures[$flavor->name . '_stale_fact'] = ['cpp' => (new Cpp_Emitter())->emit($entry),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	$source = '';
	$forms = ['pre_inc' => '++' . $v, 'post_inc' => $v . '++', 'pre_dec' => '--' . $v, 'post_dec' => $v . '--'];
	foreach ($forms as $name => $form) {
		$source .= $fn($name, $ref, 'void', $form . '; return;');
	}
	$observer = file_get_contents(__DIR__ . '/float_updates_observer.cpp');
	foreach (array_keys($forms) as $name) {
		$observer = str_replace('@' . $name . '@', 'scpp_function_' . bin2hex($name), $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$inferred = match ($flavor) {
		flavor::php => '$x=value();',
		flavor::cpp => 'auto x=value();',
		flavor::js => 'let x=value();',
	};
	$directory = sys_get_temp_dir() . '/scpp-float-updates-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, $inferred . $x . '++; if (' . $x . '==9.0) { echo "Y"; } return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $expression) use ($path, $fn, &$stamp): void {
			file_put_contents($path, $fn('value', '', $type, 'return ' . $expression . ';'));
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('float', '8.0');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['float', '16.0 / 2.0'], ['bool', 'true'], ['float', '4.0 * 2.0'],
			['float', '1e309'], ['float64', '+(10.0) - 2.0']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '1e309')) {
				$message = $type === 'bool' ? 'Unsupported update type' : 'finite';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($expression === '16.0 / 2.0' ? 1 : 2), 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental differs from fresh output');
				$fixtures[$flavor->name . '_recovery'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		}
		file_put_contents($directory . '/main.' . $flavor->name,
			$inferred . '--' . $x . '; if (' . $x . '==7.0) { echo "Y"; } return 8;');
		touch($directory . '/main.' . $flavor->name, ++$stamp);
		verify($project->run()['preparation']['prepared'] === 1, 'Operator edit prepared unchanged provider');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Operator edit differs from fresh output');
		$fixtures[$flavor->name . '_operator_edit'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	}
	finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Float updates: shared write path, single-pass preparation and incremental/fresh recovery passed\n";
