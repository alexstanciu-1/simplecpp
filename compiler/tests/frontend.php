<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Parser, Program_Frontend, Source_Frontend, diagnostic, entry_target,
		flavor, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
foreach ([
		[flavor::cpp, 'int a=8; a=a; int b=a; return b;'],
		[flavor::php, '$a int=8; $a=$a; $b int=$a; return $b;'],
		[flavor::js, 'let a: int=8; a=a; let b: int=a; return b;'],
	] as [$flavor, $text])
{
	$source = new source_file('source', $text, $flavor);
	$parser = new Parser($frontend->scan($source));
	$file = $parser->parse();
	verify($file->tokens->source === $source, 'Source snapshot replaced');
	verify(array_keys($file->main()->body()->locals) === [0, 2], 'Declaration positions lost');
	verify(($file->main()->body()->locals[0]->id === 0) && ($file->main()->body()->locals[2]->id === 1),
		'Declaration ordinals changed');
	verify($file->main()->body()->locals[0] === $file->main()->body()->statements[0],
		'First declaration was copied');
	verify($file->main()->body()->locals[2] === $file->main()->body()->statements[2],
		'Second declaration was copied');
	verify($file->main()->body()->references === [$file->main()->body()->statements[1]->value,
			$file->main()->body()->statements[2]->initializer, $file->main()->body()->statements[3]->value],
		'Unresolved reads must retain AST identity and source order');
	try {
		$parser->parse();
		throw new RuntimeException('Parser reused its consumed cursor');
	}
	catch (LogicException $expected) {
	}
}

// The body ends at the delimiter. The enclosing parser consumes it after the body loop.
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$parser = new Parser($frontend->scan(new source_file('namespace', 'namespace N { return 8; }', $flavor)));
	$body = $parser->parse()->main()->body();
	verify($parser->cursor->tokens->text_at($body->end) === '}', 'Namespace brace became part of the body');
	verify($parser->cursor->position() === $body->end + 1 && $parser->cursor->at_end(), 'Namespace brace was not consumed once');
	$parser = new Parser($frontend->scan(new source_file('missing', 'namespace N { return 8;', $flavor)));
	try {
		$parser->parse();
		throw new RuntimeException('Accepted namespace without a closing brace');
	}
	catch (diagnostic $error) {
		verify($parser->cursor->at_end() && str_ends_with($error->getMessage(), 'Expected }'), 'Expected closing brace at EOF');
	}
}

// Collection retains duplicate candidates; semantic analysis still owns rejection.
$duplicates = $frontend->collect(new source_file('duplicates', 'int a=8; int a=9; return a;', flavor::cpp));
verify(count($duplicates->main()->body()->locals) === 2, 'Collection silently merged duplicate locals');
verify($duplicates->main()->body()->locals[0] !== $duplicates->main()->body()->locals[1],
	'Duplicate names share declaration identity');

$bad = new source_file('bad', 'int a=8; int b=;', flavor::cpp);
$parser = new Parser($frontend->scan($bad));
try {
	$parser->parse();
	throw new RuntimeException('Partial parse was published');
}
catch (diagnostic $error) {
	verify($error->source === $bad, 'Lost failed-source ownership');
}
try {
	$parser->parse();
	throw new RuntimeException('Failed parser could publish a partial collection on retry');
}
catch (LogicException $expected) {
}

$next = $frontend->collect(new source_file('next', 'int a=9; return a;', flavor::cpp));
verify((count($next->main()->body()->locals) === 1) && ($next->main()->body()->locals[0]->id === 0),
	'Local state leaked between sources');
verify($next->main()->body()->locals[0] !== $duplicates->main()->body()->locals[0],
	'Independent sources share local declarations');
$empty = $frontend->collect(new source_file('empty', '', flavor::php));
verify($empty->declarations === [], 'Empty source retained earlier collection');
unset($frontend, $parser);
verify($next->main()->body()->statements[1]->value === $next->main()->body()->references[0],
	'Result depends on processing-worker lifetime');

// All frontend work must succeed before semantic preparation starts in any source.
$entry = new source_file('entry', 'return missing;', flavor::cpp);
try {
	(new Program_Frontend())->prepare_sources('entry', ['entry' => $entry, 'bad' => $bad], new entry_target(31));
	throw new RuntimeException('Malformed secondary source accepted');
}
catch (diagnostic $error) {
	verify($error->source === $bad, 'Entry preparation ran before all sources finished parsing');
}
echo "Frontend collection, source ownership and failure boundaries passed\n";
