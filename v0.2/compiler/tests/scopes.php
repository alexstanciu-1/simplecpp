<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{scope, Scope_Collection, Source_Frontend, Symbol_Publication, Preparation,
	entry_target, flavor, source_file, diagnostic, change_status};
function verify(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
foreach (flavor::cases() as $flavor) {
	$header = static fn(string $name): string => $flavor === flavor::cpp ? 'void ' . $name . '()' : 'function ' . $name . '(): void';
	$local = match ($flavor) { flavor::cpp => 'int x=8;', flavor::php => '$x int=8;', flavor::js => 'let x: int=8;' };
	$file = (new Source_Frontend())->collect(new source_file('scopes', 'first(); return 8;'
		. $header('first') . '{' . $local . 'second();}' . $header('second') . '{' . $local . '}', $flavor));
	$root = Symbol_Publication::completed(['source' => $file]);
	$first = $root->resolve('func:first');
	$second = $root->resolve('func:second');
	$a = $first->body()->scope;
	$b = $second->body()->scope;
	verify($root->parent === null && $file->scope->parent === $root, 'Compilation/file scope parents');
	verify($a->parent === $file->scope && $b->parent === $file->scope, 'Body scope parent');
	verify($a !== $b && $a->entry('var:x') !== $b->entry('var:x'), 'Same local names shared identities across bodies');
	verify($a->resolve('var:x') === $first->body()->locals[0], 'Body scope does not index original local AST');
	verify($root->resolve('var:x') === null && $file->scope->resolve('var:x') === null, 'Local leaked outside body');
	verify($root->resolve('func:main') === null && $file->scope->resolve('func:main') === $file->main(), 'Private main exposure/order');
	verify(array_key_first($file->declarations) === 'func:main', 'Main must remain first in AST declaration view');
	verify($a->resolve('func:second') === null, 'Parent lookup silently enabled');
	$a->entry('var:missing');
	verify(!isset($root->entries()['var:missing']), 'Child entry creation modified parent');
	$duplicate = (new Source_Frontend())->collect(new source_file('duplicates',
		$header('f') . '{' . $local . $local . '}' . $header('f') . '{}', $flavor));
	$head = $duplicate->scope->heads()['func:f'];
	verify(count($duplicate->scope->declarations['func:f']) === 2 && $head->duplicate_element() !== null, 'File scope duplicates lost');
	$body = $head->body();
	verify(count($body->scope->declarations['var:x']) === 2 && $body->locals[0]->duplicate_element() === $body->locals[1], 'Body scope duplicate chains lost');
	$chain = $head->duplicate_element();
	$aggregate = Symbol_Publication::completed(['duplicate' => $duplicate]);
	verify(count($aggregate->declarations['func:f']) === 2 && $head->duplicate_element() === $chain, 'Import relinked syntax');
	// Same publisher supports body-local and function declaration references, removal, and reactivation.
	foreach ([$first, $first->body()->locals[0]] as $node) {
		$target = new scope();
		$pending = new Scope_Collection($target);
		$pending->import($node);
		verify($target->resolve($node->symbol_key()) === null, 'Pending declaration exposed');
		verify($pending->publish() === $target, 'Publisher replaced owning scope');
		$entry = $target->entry($node->symbol_key());
		(new Scope_Collection(new scope()))->publish($target);
		verify($entry->status === change_status::removed && $entry->declaration() === null, 'Common scope removal');
		$pending = new Scope_Collection(new scope());
		$pending->import($node);
		$pending->publish($target);
		verify($target->entry($node->symbol_key()) === $entry && $entry->declaration() === $node, 'Common scope reactivation');
	}
	try {
		(new Source_Frontend())->parse(new source_file('nested', $header('outer') . '{' . $header('inner') . '{}}', $flavor));
		throw new RuntimeException('Nested function unexpectedly supported');
	} catch (diagnostic $error) { verify(str_contains($error->getMessage(), 'Nested functions'), 'Nested-function boundary changed'); }
	// Body scopes do not make a variable visible before its declaration.
	$reference = $flavor === flavor::php ? '$x' : 'x';
	try {
		$early = (new Source_Frontend())->parse(new source_file('early', 'echo ' . $reference . ';' . $local . 'return 8;', $flavor));
		(new Preparation())->prepare($early, new entry_target(31));
		throw new LogicException('Forward local reference accepted');
	} catch (diagnostic) {}
}
echo "Common scope publication, ownership, duplicates, reactivation and unchanged lookup/grammar passed\n";
