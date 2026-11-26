<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/preparation_snapshot.php';

use scpp\compiler\{Source_Frontend, Symbol_Publication, Function_Preparation, Preparation,
		Program_Preparation, Cpp_Emitter, ast_node, ast_call_statement, diagnostic, entry_target, flavor, ast_file_node, ast_function_body, ast_function_declaration, scope,
		source_file,
		type_int64, type_void};

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
verify(($first->short_name === 'first') && ($first->type_reference?->name === 'int'), 'Declaration lost written names');
verify($ping->return_type_reference?->name === 'void', 'Function lost written return type');
unresolved(fn() => $first->type_def());
unresolved(fn() => $ping->return_type_def());
unresolved(fn() => $call->function_def());
$callables = Function_Preparation::declarations(Symbol_Publication::completed(['facts' => $file]));
verify(($callables->resolve('func:ping') === $ping) && ($ping->return_type_def() === type_void::instance()),
	'Declaration phase lost AST identity');
unresolved(fn() => $ping->body()->require_prepared());
unresolved(fn() => $ping->body()->locals[0]->type_def());
$main->settle_return_type(type_int64::instance());
(new Preparation())->function_body($main->body(), $callables, new entry_target(31));
verify(($first->type_def() === $second->type_def()) && ($first->type_def() === type_int64::instance()),
	'Type aliases did not resolve to one identity');
verify($main->body()->statements[1]->initializer->local_def() === $first, 'Reference does not link to its declaration');
verify(($main->body()->statements[2] === $call) && ($call instanceof ast_call_statement) && ($call->function_def() === $ping),
	'Call symbol does not resolve to its declaration');
unresolved(fn() => $ping->body()->require_prepared());
Function_Preparation::reset($main);
unresolved(fn() => $first->type_def());
unresolved(fn() => $call->function_def());
$program = Program_Preparation::prepare('facts', ['facts' => $file], new entry_target(31));
$snapshot = new Preparation_Snapshot();
$before = $snapshot->capture($program);
(new Cpp_Emitter())->emit($program->entry);
verify($snapshot->capture($program) === $before, 'Lowering changed declaration facts');
foreach ([['auto x = 3; return 8;', flavor::cpp], ['let x = 3; return 8;', flavor::js]] as [$text, $flavor])
{
	$missing = $frontend->collect(new source_file('missing', $text, $flavor));
	$local = $missing->main()->body()->locals[0];
	verify($local->type_reference === null, 'Missing written type was fabricated');
	unresolved(fn() => $local->type_def());
	(new Preparation())->prepare($missing, new entry_target(31));
	verify($local->type_def() === type_int64::instance(), 'Initializer type was not inferred');
	verify($local->type_reference === null, 'Inference changed written syntax');
}
// Completing a header must validate before updating the candidate body's parent.
$draft = new ast_function_declaration('draft', null, 0, 3);
$early = new ast_function_body(2, 4, [], [], [], new scope());
unresolved(fn() => $draft->complete_body($early));
verify(!isset($early->parent) && !isset($draft->end) && $draft->optional_body() === null,
	'An invalid range partly completed a declaration');
$body = new ast_function_body(3, 5, [], [], [], new scope());
$draft->complete_body($body);
verify($draft->end === 5 && $draft->body() === $body && $body->parent === $draft, 'Concrete publication lost syntax identity');
$extra = new ast_function_body(3, 6, [], [], [], new scope());
unresolved(fn() => $draft->complete_body($extra));
verify(!isset($extra->parent) && $draft->end === 5 && $draft->body() === $body, 'Second completion changed ownership');

$abstract = new ast_function_declaration('contract', null, 0, 3);
$abstract->is_abstract = true;
$root = new ast_file_node($file->tokens);
$abstract->attach($root);
unresolved(fn() => $root->complete(['func:contract' => $abstract], [$abstract]));
verify(!isset($root->declaration_nodes), 'Failed file completion published an unfinished abstract header');
unresolved(fn() => $abstract->complete_abstract(2));
unresolved(fn() => $abstract->complete_body($extra));
verify(!isset($abstract->end) && !isset($extra->parent), 'Invalid abstract completion changed syntax');
$abstract->complete_abstract(4);
unresolved(fn() => $abstract->complete_abstract(5));
unresolved(fn() => $abstract->complete_body($extra));
$root->complete(['func:contract' => $abstract], [$abstract]);
verify($abstract->end === 4 && $abstract->optional_body() === null && !isset($extra->parent),
	'Completed abstract declaration acquired a second end or body');

foreach (get_declared_classes() as $class) {
	if (is_subclass_of($class, ast_node::class)) {
		verify(str_starts_with((new ReflectionClass($class))->getShortName(), 'ast_'),
			'AST class prefix missing');
	}
}
echo "Declaration-owned facts, direct references, phase boundaries, reset and lowering purity passed\n";
