<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Emitter, Cpp_Program, Function_Preparation, Preparation, Program_Frontend,
	Source_Frontend, Symbol_Publication, ast_function_declaration, declaration_sink, diagnostic,
	entry_target, flavor, ast_call_statement, prepared_void_return, program_sources, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Observe the real discovery boundary without reading mutable body fields. */
final class Discovery_Probe implements declaration_sink
{
	public Symbol_Publication $publication;
	public array $seen /** list<ast_function_declaration> */ = [];

	public function __construct()
	{
		$this->publication = new Symbol_Publication();
	}

	/** Discovery precedes body completion and only makes the candidate pending. */
	public function discovered(ast_function_declaration $function): void
	{
		try {
			$function->body();
			throw new RuntimeException('Discovery was deferred until body completion');
		}
		catch (LogicException $expected) {
		}
		$this->seen[] = $function;
		$this->publication->discovered($function);
	}
}

$frontend = new Source_Frontend();
$target = new entry_target(31);
$outputs = [];
foreach ([
	[flavor::cpp, 'ping(); int ret=8; return ret; void ping() { int local=9; pong(); return; } void pong() {}'],
	[flavor::php, 'ping(); $ret int=8; return $ret; function ping(): void { $local int=9; pong(); return; } function pong(): void {}'],
	[flavor::js, 'ping(); let ret: int=8; return ret; function ping(): void { let local: int=9; pong(); return; } function pong(): void {}'],
] as [$flavor, $text])
{
	$probe = new Discovery_Probe();
	$file = $frontend->collect(new source_file('source', $text, $flavor), $probe);
	verify($probe->seen === $file->functions(), 'Discovery identity differs from retained identity');
	verify(count($file->main()->body()->locals) === 1, 'Function locals leaked into entry collection');
	$symbols = $probe->publication->publish(['source' => $file]);
	verify(count($symbols->functions) === 2, 'Global index contains wrong declarations');
	$program = scpp\compiler\Program_Preparation::prepare('source', ['source' => $file], $target, $symbols);
	$entry = $program->entry;
	[$ping, $pong] = $entry->functions;
	verify($entry->statements[0] instanceof ast_call_statement, 'Missing prepared entry call');
	verify($entry->statements[0]->function_def() === $ping->declaration, 'Entry call did not bind exact callable contract');
	verify($ping->body->statements[1]->function_def() === $pong->declaration, 'Forward function call binding differs');
	verify($ping->body->statements[2] instanceof prepared_void_return, 'Bare return has wrong facts');
	verify($pong->body->statements === [], 'Empty void body requires invented statements');
	verify($file->declarations['func:ping'] === $ping->declaration, 'Signature facts not attached to declaration');
	verify($file->declarations['func:ping']->body()->preparation() === $ping->body, 'Body facts not attached to body owner');
	verify($ping->body->statements[0]->binding->id === 0, 'Body-local identity not independent');
	$outputs[] = (new Cpp_Emitter())->emit($entry);
	try {
		$probe->publication->discovered($file->functions()[0]);
		throw new RuntimeException('Late discovery was accepted');
	}
	catch (LogicException $expected) {
	}
}
verify(count(array_unique($outputs)) === 1, 'Function output differs by source flavor');

// A caller only needs the signature, even when its callee body has not been prepared.
$file = $frontend->collect(new source_file('contracts',
	'function first(): void { return 9; } function second(): void { first(); } return 8;', flavor::php));
$callables = Function_Preparation::declarations(Symbol_Publication::completed(['contracts' => $file]));
$caller = (new Preparation())->function_body($file->functions()[1]->body(), $callables);
verify($caller->statements[0]->function_def() === $file->functions()[0], 'Call required callee body');
try {
	$file->functions()[0]->body()->preparation();
	throw new RuntimeException('Call lookup prepared a foreign body');
}
catch (LogicException $expected) {
}

$recursive = $frontend->prepare(new source_file('recursive',
	'function a(): void { b(); } function b(): void { a(); } return 8;', flavor::php), $target);
verify($recursive->functions[0]->body->statements[0]->function_def() === $recursive->functions[1]->declaration, 'Mutual recursion failed');
$self = $frontend->prepare(new source_file('self', 'void a() { a(); } return 8;', flavor::cpp), $target);
verify($self->functions[0]->body->statements[0]->function_def() === $self->functions[0]->declaration, 'Self recursion failed');

foreach ([
	['function f(): void { return 8; } return 8;', 'cannot return a value'],
	['function f(): int {} return 8;', 'explicit void'],
	['function f() {} return 8;', 'explicit return type'],
	['function f($x int): void {} return 8;', 'no parameters'],
	['function f(): void {} f(8); return 8;', 'no arguments'],
	['function f(): void { function g(): void {} } return 8;', 'Nested functions'],
	['function f(): void {} function f(): void {} return 8;', 'Duplicate function'],
	['missing(); return 8;', 'Unknown function'],
	['function f(): void { missing(); } return 8;', 'Unknown function'],
	['$x int=8; function f(): void { $y int=$x; } return 8;', 'not-yet-initialized'],
	['function f(): void { $x int=8; } function g(): void { $y int=$x; } return 8;', 'not-yet-initialized'],
	['function f(): void { return; f(); } return 8;', 'after return'],
	['return;', 'Entry requires a value'],
	['function f(): void {}', 'explicit value return'],
] as [$text, $message])
{
	try {
		$frontend->prepare(new source_file('bad', $text, flavor::php), $target);
		throw new RuntimeException('Accepted invalid source: ' . $text);
	}
	catch (diagnostic $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
	}
}

$publication = new Symbol_Publication();
try {
	$frontend->collect(new source_file('partial', 'function broken(): void {', flavor::php), $publication);
	throw new RuntimeException('Partial function accepted');
}
catch (diagnostic $expected) {
}
try {
	$publication->publish([]);
	throw new RuntimeException('Failed source candidate became globally visible');
}
catch (LogicException $expected) {
}

$sources = [
	'main' => new source_file('main', 'start(); return 8;', flavor::php),
	'mid' => new source_file('mid', 'void start() { finish(); }', flavor::cpp),
	'end' => new source_file('end', 'function finish(): void { return; }', flavor::js),
];
$program = (new Program_Frontend())->prepare(new program_sources('main', $sources), $target);
verify($program->entry->statements[0]->function_def() === $program->functions['mid'][0]->declaration, 'Cross-source identity lost');
$facts_before = serialize($program);
$output = Cpp_Program::emit($program);
verify(serialize($program) === $facts_before, 'Lowering mutated semantic facts');
$reordered = (new Program_Frontend())->prepare(new program_sources('main', array_reverse($sources, true)), $target);
verify(Cpp_Program::emit($reordered)->files === $output->files, 'Input order changes function emission');
$messages = [];
$duplicates = $sources;
$duplicates['duplicate'] = new source_file('duplicate', 'void start() {}', flavor::cpp);
foreach ([$duplicates, array_reverse($duplicates, true)] as $inventory)
{
	try {
		(new Program_Frontend())->prepare(new program_sources('main', $inventory), $target);
		throw new RuntimeException('Duplicate cross-file function accepted');
	}
	catch (diagnostic $error) {
		$messages[] = $error->getMessage();
	}
}
verify(($messages[0] === $messages[1]) && str_contains($messages[0], 'Duplicate function'), 'Duplicate diagnostics depend on arrival order');
echo "Function discovery, signature/body ownership, calls, recursion and multifile semantics passed\n";
