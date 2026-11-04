<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{Source_Frontend, Symbol_Publication, Program_Preparation, Syntax_Inspection,
		Compilation_Inspection, ast_function_declaration, diagnostic_group, entry_target, flavor, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
foreach ([
		[flavor::cpp,
			'void f() { int x=1; int x=2; } void other() {} void f() {} void f() {} int y=1; int y=2; return 8;'],
		[flavor::php,
			'function f(): void { $x int=1; $x int=2; } function other(): void {} function f(): void {} function f(): void {} $y int=1; $y int=2; return 8;'],
		[flavor::js,
			'function f(): void { let x: int=1; let x: int=2; } function other(): void {} function f(): void {} function f(): void {} let y: int=1; let y: int=2; return 8;'],
	] as [$flavor, $text])
{
	$publisher = new Symbol_Publication();
	$file = $frontend->collect(new source_file('a', $text, $flavor), $publisher);
	$functions = $file->functions();
	verify(array_map(static fn(ast_function_declaration $f): string => $f->name, $functions) === ['f', 'other', 'f',
			'f'], 'Lost source order or later declarations');
	$head = $file->declarations['func:f'];
	verify(($head === $functions[0]) && ($head->duplicate_element() === null),
		'File index must not own a function chain');
	foreach ($functions as $function) {
		verify(($function->parent === $file) && ($function->body()->parent === $function),
			'Duplicate syntax lost ownership');
	}
	$locals = array_values($head->body()->locals);
	verify(($locals[0]->duplicate_element() === $locals[1]) && ($locals[1]->duplicate_element() === null),
		'Wrong local chain');
	verify(($locals[0]->id === 0) && ($locals[1]->id === 1), 'Lost local identity');
	verify(count($file->diagnostics) === 4, 'Did not collect all source diagnostics');
	verify(count(Syntax_Inspection::file($file)['functions']) === 4, 'Inspection omitted duplicate bodies');
	verify(count(Compilation_Inspection::collection($file)['diagnostics']) === 4, 'Collection lost errors');
	$symbols = $publisher->publish(['a' => $file]);
	verify(($symbols->declarations['func:f'] === $head) && ($head->duplicate_element() === $functions[2])
		&& ($functions[2]->duplicate_element() === $functions[3]), 'Publication lost duplicate chain');
	$b = $frontend->collect(new source_file('b', 'void f() {}', \scpp\compiler\flavor::cpp));
	$messages = [];
	foreach ([['a' => $file, 'b' => $b], ['b' => $b, 'a' => $file]] as $files)
	{
		try {
			Program_Preparation::prepare('a', $files, new entry_target(31));
			throw new RuntimeException('Ambiguous program reached preparation');
		}
		catch (diagnostic_group $errors)
		{
			verify(count($errors->diagnostics) === 5, 'Did not report every duplicate exactly once');
			foreach ($errors->diagnostics as $error) {
				verify(str_contains($error->getMessage(), 'first declaration at a:'),
					'Missing original location');
			}
			$messages[] = $errors->getMessage();
		}
	}
	verify($messages[0] === $messages[1], 'Diagnostics depend on source arrival order');
	verify(($head->duplicate_element() === $functions[2]) && ($functions[3]->duplicate_element() === $b->declarations['func:f']),
		'Declaring scope did not join cross-file duplicates');
	verify((count($file->functions()) === 4) && (count($b->functions()) === 1),
		'File traversal followed a foreign duplicate');
	try {
		$head->return_type_def();
		throw new RuntimeException('Ambiguous declaration was settled');
	}
	catch (LogicException $expected) {
	}
}
$file = $frontend->parse(new source_file('many', str_repeat('void f() {} ', 1000), flavor::cpp));
verify((count($file->functions()) === 1000) && (count($file->diagnostics) === 999), 'Long chain lost declarations');
$file = $frontend->parse(new source_file('scopes', 'void f() { int x=1; } void g() { int x=2; }', flavor::cpp));
verify($file->diagnostics === [], 'Local names leaked across scopes');
echo "Duplicate chains, retained syntax, batched errors, deterministic publication and preparation barrier passed\n";
