<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Source_Frontend, Symbol_Publication, Function_Preparation, Preparation,
		Program_Preparation, Cpp_Emitter, ast_node, ast_call_statement, diagnostic, entry_target, flavor,
		source_file,
		type_definition};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Assert that a declaration fact is unavailable until preparation settles it. */
function unresolved(Closure $read): void
{
	try {
		$read();
		throw new RuntimeException('Unsettled fact was readable');
	}
	catch (LogicException $expected) {
	}
}

$frontend = new Source_Frontend();
$file = $frontend->collect(new source_file('facts',
		'void ping() { int inside = 1; } int first = 3; int64 second = first; ping(); return second;', flavor::cpp));
$ast = $file;
$ping = $ast->declarations['func:ping'];
$main = $ast->main();
[$first, $second, $call, $return] = $main->body()->statements;
verify(($first->name === 'first') && ($first->type_name === 'int'), 'Declaration lost written names');
verify($ping->return_type_name === 'void', 'Function lost written return type');
unresolved(fn() => $first->type_def());
unresolved(fn() => $ping->return_type_def());
unresolved(fn() => $call->function_def());
$callables = Function_Preparation::declarations(Symbol_Publication::completed(['facts' => $file]));
verify(($callables->resolve('func:ping') === $ping) && ($ping->return_type_def() === type_definition::void),
	'Declaration phase lost AST identity');
unresolved(fn() => $ping->body()->require_prepared());
unresolved(fn() => $ping->body()->locals[0]->type_def());
(new Preparation())->function_body($main->body(), $callables, new entry_target(31));
verify(($first->type_def() === $second->type_def()) && ($first->type_def() === type_definition::int64),
	'Type aliases did not resolve to one identity');
verify($main->body()->statements[1]->initializer->local_def() === $first, 'Reference does not link to its declaration');
verify(($main->body()->statements[2] === $call) && ($call instanceof ast_call_statement) && ($call->function_def() === $ping),
	'Call symbol does not resolve to its declaration');
unresolved(fn() => $ping->body()->require_prepared());
Function_Preparation::reset($main);
unresolved(fn() => $first->type_def());
unresolved(fn() => $call->function_def());
$program = Program_Preparation::prepare('facts', ['facts' => $file], new entry_target(31));
$before = serialize($program);
(new Cpp_Emitter())->emit($program->entry);
verify(serialize($program) === $before, 'Lowering changed declaration facts');
foreach ([['auto x = 3; return 8;', flavor::cpp], ['let x = 3; return 8;', flavor::js]] as [$text, $flavor])
{
	$missing = $frontend->collect(new source_file('missing', $text, $flavor));
	$local = $missing->main()->body()->locals[0];
	verify($local->type_name === null, 'Missing written type was fabricated');
	try {
		(new Preparation())->prepare($missing, new entry_target(31));
		throw new RuntimeException('This structural slice accidentally enabled inference');
	}
	catch (diagnostic $expected) {
	}
	unresolved(fn() => $local->type_def());
}
foreach (get_declared_classes() as $class) {
	if (is_subclass_of($class, ast_node::class)) {
		verify(str_starts_with((new ReflectionClass($class))->getShortName(), 'ast_'),
			'AST class prefix missing');
	}
}
echo "Declaration-owned facts, direct references, phase boundaries, reset and lowering purity passed\n";
