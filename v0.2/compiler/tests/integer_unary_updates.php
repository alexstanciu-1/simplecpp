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
	$decl = static fn(string $type, string $value): string => match ($flavor) {
		flavor::php => '$x ' . $type . '=' . $value . ';',
		flavor::cpp => $type . ' x=' . $value . ';',
		flavor::js => 'let x: ' . $type . '=' . $value . ';',
	};
	$x = $var('x');
	$v = $var('v');
	$param = match ($flavor) {
		flavor::php => 'byte $v', flavor::cpp => 'byte v', flavor::js => 'v: byte',
	};
	$ref = match ($flavor) {
		flavor::php => 'uint8 &$v', flavor::cpp => 'uint8 &v', flavor::js => '&v: uint8',
	};
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('integer_unary_updates', $code, $flavor), new entry_target(31));
	$source = $fn('positive', $param, 'byte', 'return +' . $v . ';')
		. $fn('negative', $param, 'byte', 'return -' . $v . ';')
		. $fn('nested', $param, 'byte', 'return - -' . $v . ';')
		. $fn('truth', $param, 'bool', 'if (' . $v . ') { return true; } return false;')
		. $fn('logical', $param, 'bool', 'return !' . $v . ';');
	foreach (['pre_inc' => '++' . $v, 'post_inc' => $v . '++', 'pre_dec' => '--' . $v, 'post_dec' => $v . '--'] as $name => $form) {
		$source .= $fn($name, $ref, 'void', $form . '; return;');
	}
	$source .= $fn('compound', $ref, 'void', $v . '+=1; return;');
	$observer = file_get_contents(__DIR__ . '/integer_unary_updates_observer.cpp');
	foreach (['positive', 'negative', 'nested', 'truth', 'logical', 'pre_inc', 'post_inc', 'pre_dec', 'post_dec', 'compound'] as $name) {
		$observer = str_replace('@' . $name . '@', $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()', (new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$source .= $fn('effect', '', 'byte', 'echo "E"; return 1;');
	$code = $source . $decl('byte', '255') . $x . '++; if (!' . $x . ') { echo "A"; }'
		. '--' . $x . '; if (' . $x . '==255) { echo "B"; }'
		. 'if (-' . $x . '==1 && +' . $x . '==255 && - -' . $x . '==255) { echo "C"; }'
		. 'if ((-(byte)1)+1==256) { echo "D"; }'
		. 'if ((byte)0 && effect()) {} if ((byte)1 || effect()) { echo "S"; }'
		. 'if ((byte)1 && effect()) { echo "T"; } if ((byte)0 || effect()) { echo "U"; }'
		. 'if (-effect()==255) { echo "V"; }'
		. $x . '=(byte)254; while (' . $x . ') { ' . $x . '++; echo "W"; }'
		. 'do { --' . $x . '; } while (' . $x . '>253);'
		. 'for (' . $x . '=(byte)254;' . $x . ';' . $x . '++) { echo "F"; }'
		. 'post_dec(' . $x . '); if (' . $x . '==255) { echo "R"; } return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('ABCDSETEUEVWWFFR')];
	$entry = $prepare('return (int)(- +(byte)1);');
	verify($entry->body()->expression_visits === 5, 'Unary operand revisited');
	$unary = $entry->body()->statements[0]->value->operand;
	verify($unary->type_def() === \scpp\compiler\type_uint8::instance() && !$unary->constant_known(), 'Unary facts lost identity or added folding');
	$before = (new Cpp_Emitter())->emit($entry);
	$unary->reset_preparation();
	$unary->operand->type_def()->prepare_unary($unary);
	verify((new Cpp_Emitter())->emit($entry) === $before, 'Unary reset/reprepare changed output');
	$entry = $prepare($decl('byte', '255') . $x . '++; return 8;');
	verify($entry->body()->expression_visits === 2, 'Update synthesized expression work');
	$entry = $prepare($decl('byte', '255') . $x . '++; return (int)' . $x . ';');
	verify(!$entry->body()->statements[2]->value->operand->constant_known(), 'Read after update retained initializer constant');
	foreach (['echo ++' . $x . ';', 'echo ' . $x . '++;', '++1;', '++' . $x . '++;'] as $invalid) {
		rejects(fn() => $prepare($decl('byte', '1') . $invalid . 'return 8;'), 'Expected');
	}
	foreach (['bool' => 'true', 'null' => 'null', 'string' => '"x"'] as $type => $value) {
		rejects(fn() => $prepare($decl($type, $value) . $x . '++; return 8;'), 'Unsupported update');
		rejects(fn() => $prepare('return (int)(-' . $value . ');'), 'Unsupported unary');
	}
	rejects(fn() => $prepare($source . $decl('byte', '1') . 'post_inc(+' . $x . '); return 8;'), 'Reference argument');
	rejects(fn() => $prepare('if (null) {} return 8;'), 'condition');
	$directory = sys_get_temp_dir() . '/scpp-integer-unary-updates-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		$caller_source = ($flavor === flavor::php ? '$x=value();' : ($flavor === flavor::cpp ? 'auto x=value();' : 'let x=value();'))
			. '++' . $x . '; --' . $x . '; if (+' . $x . ' && - -' . $x . '==8) { echo "Y"; } return 8;';
		file_put_contents($directory . '/main.' . $flavor->name, $caller_source);
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $expression) use ($path, $fn, &$stamp): void {
			file_put_contents($path, $fn('value', '', $type, 'return ' . $expression . ';'));
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('byte', '8');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['byte', '4 * 2'], ['int', '8'], ['bool', 'true'], ['uint8', '8'], ['byte', '256'], ['int64', '8']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '256')) {
				$message = $type === 'bool' ? 'Unsupported update' : 'outside uint8';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($expression === '4 * 2' ? 1 : 2), 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental differs from fresh output');
				$fixtures[$flavor->name . '_recovery_' . count($fixtures)] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		}
		file_put_contents($path, '');
		touch($path, ++$stamp);
		rejects(fn() => $project->run(), 'value');
		rejects(fn() => $project->run(), 'value');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'value');
		$write('byte', '8');
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Deletion recovery differs from fresh');
		file_put_contents($directory . '/main.' . $flavor->name,
			'if (+value()==8 && !!value()) { echo "Y"; } return 8;');
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
echo "Integer unary/update/truthiness: single-pass preparation and incremental/fresh recovery passed\n";
