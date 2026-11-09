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
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('family_conversions', $code, $flavor), new entry_target(31));
	verify(\scpp\compiler\type_registry::resolve('byte') === \scpp\compiler\type_registry::resolve('uint8'), 'Aliases do not share identity');
	verify(\scpp\compiler\type_registry::resolve('byte')->name === 'uint8', 'Wrong canonical name');
	$param = static fn(string $type, string $name): string => match ($flavor) {
		flavor::php => $type . ' $' . $name, flavor::cpp => $type . ' ' . $name, flavor::js => $name . ': ' . $type,
	};
	$v = $var('v');
	$ref = match ($flavor) {
		flavor::php => 'byte &$v', flavor::cpp => 'byte &v', flavor::js => '&v: byte',
	};
	$global = 'global ' . str_replace($x, $var('global_byte'), $decl('byte', '255.9'));
	$constant = $flavor === flavor::js ? 'const BOOL_BYTE: byte = true;' : 'const byte BOOL_BYTE = true;';
	$code = $global . $constant . $fn('to_byte', $param('float', 'v'), 'byte', 'return ' . $v . ';')
		. $fn('to_bool', $param('byte', 'v'), 'bool', 'return ' . $v . ';')
		. $fn('to_float', $param('byte', 'v'), 'float', 'return ' . $v . ';')
		. $fn('from_bool', $param('bool', 'v'), 'byte', 'return ' . $v . ';')
		. $fn('byte_default', $param('byte', 'v') . '=255.9', 'byte', 'return ' . $v . ';')
		. $fn('bool_default', $param('byte', 'v') . '=true', 'byte', 'return ' . $v . ';')
		. $decl('byte', 'true') . 'if (' . $x . '==1) { echo "Y"; }'
		. 'if (to_byte(255.9)==255 && to_byte(-0.9)==0 && to_byte(-0.0)==0) { echo "Y"; }'
		. 'if (to_bool((byte)255) && !to_bool((byte)0)) { echo "Y"; }'
		. 'if (to_float((byte)255)==255.0 && from_bool(true)==1 && from_bool(false)==0) { echo "Y"; }'
		. 'if (byte_default()==255 && bool_default()==1) { echo "Y"; }'
		. 'if (byte_default(true)==1 && byte_default(8.75)==8) { echo "Y"; }'
		. 'if (((byte)255.9)==255 && ((bool)(byte)255) && ((float)(byte)255)==255.0) { echo "Y"; }'
		. 'if (' . $var('global_byte') . '==255 && BOOL_BYTE==1) { echo "Y"; } return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('YYYYYYYY')];
	$known = $prepare($decl('byte', '255.9') . 'return 8;');
	$local = $known->body()->statements[0];
	verify($local->initialization() === \scpp\compiler\value_conversion::floating_integer_proven
		&& $local->constant() === 255, 'Known float narrowing facts');
	verify($known->body()->expression_visits === 2, 'Conversion added a preparation traversal');
	foreach (['256.0', '1e100'] as $outside) rejects(fn() => $prepare($decl('byte', $outside) . 'return 8;'), 'outside uint8');
	rejects(fn() => $prepare($decl('byte', '1') . $x . '=true; return 8;'), 'implicit conversion');
	rejects(fn() => $prepare($decl('byte', '1') . $x . '=8.75; return 8;'), 'implicit conversion');
	rejects(fn() => $prepare($fn('reference', $ref, 'void', 'return;') . $decl('bool', 'true') . 'reference(' . $x . '); return 8;'), 'exact storage type');
	foreach (['(byte)1 + true'] as $expression) {
		rejects(fn() => $prepare('if (' . $expression . ') {} return 8;'), 'Unsupported binary');
	}
	foreach (['null', '"1"'] as $expression) rejects(fn() => $prepare($decl('byte', $expression) . 'return 8;'), 'conversion');
	$source = $fn('float_to_byte', $param('float', 'v'), 'byte', 'return ' . $v . ';')
		. $fn('byte_to_float', $param('byte', 'v'), 'float', 'return ' . $v . ';')
		. $fn('byte_to_bool', $param('byte', 'v'), 'bool', 'return ' . $v . ';')
		. $fn('bool_to_byte', $param('bool', 'v'), 'byte', 'return ' . $v . ';');
	$observer = file_get_contents(__DIR__ . '/family_conversions_observer.cpp');
	foreach (['float_to_byte', 'byte_to_float', 'byte_to_bool', 'bool_to_byte'] as $name) {
		$observer = str_replace('@' . $name . '@', '::App::scpp_function_' . bin2hex($name), $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$failure = $fn('outside', '', 'float', 'echo "X"; return 256.0;') . $decl('byte', 'outside()') . 'return 8;';
	$fixtures[$flavor->name . '_once'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
		'stdout_hex' => bin2hex('X'), 'catch_out_of_range' => true];
	$directory = sys_get_temp_dir() . '/scpp-family-conversions-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, $decl('byte', 'value()') . 'echo (int)' . $x . '; return 8;');
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
		foreach ([['float', '8.75'], ['bool', 'true'], ['byte', '8'], ['string', '"bad"'], ['uint8', '256'], ['float', '1e309'], ['float64', '8.75']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'string') || ($expression === '256') || ($expression === '1e309')) {
				$message = $type === 'string' ? 'conversion' : ($expression === '1e309' ? 'finite' : 'outside uint8');
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] >= 1 && $result['preparation']['prepared'] <= 2, 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental differs from fresh output');
				$fixtures[$flavor->name . '_recovery_' . count($fixtures)] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex($type === 'bool' ? '1' : '8')];
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
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Deletion recovery differs from fresh');
		file_put_contents($directory . '/main.' . $flavor->name,
			$decl('bool', 'value()') . 'if (' . $x . ') { echo "Y"; } return 8;');
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
echo "Family conversions: active numeric boundaries and incremental/fresh recovery passed\n";
