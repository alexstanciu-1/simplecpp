<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function definition(flavor $flavor, string $name, string $parameters, string $type, string $body): string
{
	return ($flavor === flavor::cpp ? "$type $name($parameters)" : "function $name($parameters): $type") . '{' . $body . '}';
}

function local(flavor $flavor, string $name, string $type, string $value): string
{
	return match ($flavor) {
		flavor::php => "$$name $type=$value;",
		flavor::cpp => "$type $name=$value;",
		flavor::js => "let $name: $type=$value;",
	};
}

/** Require a source diagnostic for unsupported comparisons and invalidation failures. */
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

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('comparisons', $source, $flavor), new entry_target(31));
	$entry = $prepare('if (1 < 2) { echo 1; } return 8;');
	verify($entry->body()->expression_visits === 5, 'Comparison operands must each be prepared once');
	$code = '';
	$expected = '';
	foreach (['1 == 1' => true, '1 != 1' => false, '1 < 2' => true, '2 <= 2' => true,
		'2 > 1' => true, '1 >= 2' => false, '1.5 == 1.5' => true, '1.5 != 1.5' => false,
		'-1.5 < 0.0' => true, '2.0 <= 2.0' => true, '2.0 > 1.0' => true, '1.0 >= 2.0' => false,
		'-0.0 == 0.0' => true, 'true == true' => true, 'false == true' => false, 'false != true' => true,
		'1 < 2 == 3 < 4' => true, '(1 < 2) != (3 > 4)' => true, '((1)) == (1)' => true] as $expression => $truth) {
		$code .= 'if (' . $expression . ') { echo "T"; } else { echo "F"; }';
		$expected .= $truth ? 'T' : 'F';
	}
	$code .= $decl('b', 'byte', '8') . $decl('c', 'byte', '9')
		. 'if (' . $var('b') . ' != ' . $var('c') . ') { echo "B"; }'
		. 'if (' . $var('b') . ' == ' . $var('b') . ') { echo "E"; }';
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	$code .= $fn('change', $ref, 'int', 'echo "R"; ' . $var('v') . '=9; return 1;')
		. $fn('left', '', 'int', 'echo "L"; return 1;')
		. $decl('x', 'int', '1')
		. 'if (' . $var('x') . ' == change(' . $var('x') . ')) { echo "S"; }'
		. 'if (left() == change(' . $var('x') . ')) { echo "O"; }'
		. $decl('flag', 'bool', '(1 < 2)') . $var('flag') . '=2 > 3;'
		. 'while (' . $var('x') . ' == 9) { echo "W"; ' . $var('x') . '=10; }'
		. 'do { echo "D"; } while (' . $var('x') . ' < 10);'
		. $fn('check', $flavor === flavor::php ? 'bool $v' : ($flavor === flavor::cpp ? 'bool v' : 'v: bool'),
			'bool', 'return ' . $var('v') . ' == true;')
		. 'if (check(8 <= 9)) { echo "P"; } return 8;';
	$entry = $prepare($code);
	$binary = $entry->body()->statements[0]->arms[0]->condition;
	verify($binary->left->parent === $binary && $binary->right->parent === $binary, 'Broken operand ownership');
	verify(Syntax_Inspection::file($entry->file)['statements'][0]['arms'][0]['condition']['kind'] === 'binary', 'Syntax inspection');
	Compilation_Inspection::body($entry->body());
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . 'BERSLROWDP')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	foreach (['1 == 1.0', '1 < 1.0', 'true == 1', 'true < false', 'null == null', '"a" == "a"', '1 < 2 < 3'] as $invalid) {
		rejects(fn() => $prepare('if (' . $invalid . ') {} return 8;'), 'Unsupported binary');
	}
	rejects(fn() => $prepare($decl('b', 'byte', '1') . 'if (' . $var('b') . ' < ' . $var('b') . ') {} return 8;'), 'Unsupported binary');
	rejects(fn() => $prepare($decl('b', 'byte', '1') . 'if (' . $var('b') . ' == 1) {} return 8;'), 'Unsupported binary');
	rejects(fn() => $prepare($decl('x', 'bool', $var('x') . ' == true') . 'return 8;'), 'during its initialization');
	$directory = sys_get_temp_dir() . '/scpp-comparisons-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'if (value() == 8) { echo "Y"; } return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('value', '', $type, 'return ' . $value . ';'));
		};
		$write('int', '8');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$write('float', '8.0');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported binary');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed after signature change');
		$write('int64', '8');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Comparison recovery failed');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed on recovery');
		$write('int64', '800');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', 'if (value() == 8) { echo "cross"; } return 8;', flavor::php),
	'cpp' => new source_file('cpp', 'int64 value() { return 8; }', flavor::cpp),
], new entry_target(31));
$units = array_values(Cpp_Program::emit($multi)->files);
$fixtures['multifile'] = ['cpp' => array_pop($units), 'extra_cpp' => $units, 'stdout_hex' => bin2hex('cross')];
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Generated comparisons, sequencing, single-pass preparation and incremental recovery passed\n";
