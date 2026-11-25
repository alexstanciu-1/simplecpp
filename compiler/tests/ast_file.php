<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Source_Frontend, Symbol_Publication, Preparation, type_int64, diagnostic,
		entry_target, flavor, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
foreach ([
		[flavor::php, 'function ping(): void {} ping(); return 8; function pong(): void {}'],
		[flavor::cpp, 'void ping() {} ping(); return 8; void pong() {}'],
		[flavor::js, 'function ping(): void {} ping(); return 8; function pong(): void {}'],
	] as [$flavor, $text])
{
	$file = $frontend->collect(new source_file('entry', $text, $flavor));
	$ast = $file;
	verify(($file instanceof scpp\compiler\ast_file_node) && ($file->parent === null),
		'Frontend must return the file root directly');
	verify(array_keys($ast->declarations) === ['func:main', 'func:ping', 'func:pong'],
		'Wrong declaration order/keys');
	$main = $ast->main();
	verify(($main === $ast->declarations['func:main']) && ($main->is_private) && ($main->is_implicit_entry),
		'Entry identity or visibility differs');
	verify($main->body()->statements[0]->source_text() === 'ping();', 'Statement source span changed');
	$symbols = Symbol_Publication::completed(['entry' => $file]);
	verify(array_keys($symbols->declarations) === ['func:ping', 'func:pong'],
		'Private entry leaked into global scope');
	verify($symbols->declarations['func:ping'] === $ast->declarations['func:ping'],
		'Global index wraps/copies AST identity');
	$prepared = (new Preparation())->prepare($file, new entry_target(31));
	verify($prepared->file === $file, 'Preparation must retain the same file root');
	verify($main->return_type_def() === type_int64::instance(), 'Entry contract lost integer return');
	$main->body()->require_prepared();
	verify($main->body() === $prepared->body(), 'Entry does not use the original AST body');
	$again = (new Preparation())->prepare($file, new entry_target(31));
	verify($again->body() === $main->body(), 'Reanalysis replaced the AST body');
	try {
		$prepared->body();
		throw new RuntimeException('Old preparation handle remained valid');
	}
	catch (LogicException $expected) {
	}

}
$empty = $frontend->collect(new source_file('empty', '', flavor::cpp));
verify($empty->declarations === [], 'Empty source acquired an entry');
$library = $frontend->collect(new source_file('library', 'void ping() {}', flavor::cpp));
verify($library->main() === null, 'Declaration-only source acquired an entry');
foreach (['void ping() {} void ping() {}', 'void main() {}', 'main(); return 8;'] as $text)
{
	try {
		$frontend->prepare(new source_file('invalid', $text, flavor::cpp), new entry_target(31));
		throw new RuntimeException('Accepted duplicate, explicit main, or call to private entry');
	}
	catch (diagnostic|\scpp\compiler\diagnostic_group $expected) {
	}
}
echo "AST ownership, keyed identity, private entry, shared body facts and reanalysis passed\n";
