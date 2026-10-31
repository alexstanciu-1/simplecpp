<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Emitter, Exit_Preparation, Preparation, Source_Frontend,
	diagnostic, entry_target, exit_conversion, flavor, prepared_declaration, prepared_reference,
	prepared_return, ast_return_statement, type_definition, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
$preparation = new Preparation();
$emitter = new Cpp_Emitter();
$target = new entry_target(31);
$outputs = [];
foreach ([['$ret int = 8; return $ret;', flavor::php], ['int ret = 8; return ret;', flavor::cpp],
	['let ret: int = 8; return ret;', flavor::js]] as [$text, $flavor])
{
	$collected = $frontend->collect(new source_file('entry', $text, $flavor));
	$parsed = $collected;
	verify($parsed->main()->body()->statements[1] instanceof ast_return_statement, 'Common return syntax');
	verify(str_starts_with($parsed->main()->body()->statements[1]->source_text(), 'return'), 'Return retains keyword span');
	verify($collected->main()->body()->locals[0] === $parsed->main()->body()->statements[0], 'Collection retains declaration identity');
	verify($collected->main()->body()->references[0] === $parsed->main()->body()->statements[1]->value, 'Collection retains unresolved reference');
	$program = $preparation->prepare($collected, $target);
	[$local, $return] = $program->statements;
	verify(($local instanceof prepared_declaration) && ($return instanceof prepared_return), 'Typed facts');
	verify(($return->value instanceof prepared_reference) && ($return->value->binding === $local->binding), 'Exact binding');
	verify($local->binding === $collected->main()->body()->locals[0], 'Semantic binding uses collected identity');
	verify($local->binding->type_def() === type_definition::int64, 'Canonical default int identity');
	verify(($return->value->constant === 8) && ($return->conversion === exit_conversion::proven), 'Known exit proof');
	verify(($program->statement_visits === 2) && ($program->expression_visits === 2)
		&& ($program->name_lookups === 1), 'Single-pass work counts');
	$outputs[] = $emitter->emit($program);
	try {
		$emitter->emit($parsed);
		throw new RuntimeException('Emitter accepted unprepared syntax');
	}
	catch (TypeError $expected) {
	}
}
verify(count(array_unique($outputs)) === 1, 'Flavor-independent emitted C++');
$stable = $outputs[0];

foreach ([['int ret=ret; return ret;', 'during its initialization'],
	['int ret=next; int next=8; return ret;', 'not-yet-initialized'],
	['return missing;', 'not-yet-initialized'], ['Unknown ret=8; return ret;', 'unsupported type'],
	['int ret=8; int ret=9; return ret;', 'Duplicate local'],
	['int ret=9223372036854775808; return ret;', 'outside int64'],
	['int ret=2147483648; return ret;', 'outside native int'],
	['return 2147483648;', 'outside native int'],
	['int ret=8;', 'explicit value return'], ['int ret=8; ret=9; return ret;', 'Assignments'],
	['auto ret=8; return ret;', 'Inferred declarations'], ['return 8; int ret=1;', 'after return']] as [$text, $message])
{
	$file = $frontend->collect(new source_file('bad', $text, flavor::cpp));
	try {
		$preparation->prepare($file, $target);
		throw new RuntimeException('Expected diagnostic: ' . $text);
	}
	catch (diagnostic $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		verify(($error->source === $file->tokens->source) && ($error->offset <= strlen($text)), 'Diagnostic location');
	}
}
$good = new source_file('good', 'int ret=8; return ret;', flavor::cpp);
verify($emitter->emit($preparation->prepare($frontend->collect($good), $target)) === $stable,
	'Failed preparation leaves no bindings in the next run');
$leading = $frontend->prepare(new source_file('decimal', 'int ret=0008; return ret;', flavor::cpp), $target);
verify(str_contains($emitter->emit($leading), '{8LL}'), 'Leading zeros emit decimal, never octal');
$maximum = $frontend->prepare(new source_file('max', 'int unused=9223372036854775807; return 8;', flavor::cpp), $target);
verify($maximum->statements[0]->initializer->constant === PHP_INT_MAX, 'Exact int64 maximum');
$span = $parsed->main()->body()->statements[1];
verify(Exit_Preparation::decide(null, $target, $span) === exit_conversion::checked, 'Unknown exit requires runtime check');
foreach ([$target->minimum, $target->maximum] as $boundary) {
	verify(Exit_Preparation::decide($boundary, $target, $span) === exit_conversion::proven, 'Inclusive exit boundary');
}
foreach ([$target->minimum - 1, $target->maximum + 1] as $outside) {
	try {
		Exit_Preparation::decide($outside, $target, $span);
		throw new RuntimeException('Expected exit range rejection');
	}
	catch (diagnostic $expected) {
	}
}
try {
	$frontend->prepare(new source_file('small-target', 'return 32768;', flavor::cpp), new entry_target(15));
	throw new RuntimeException('Preparation used PHP host width instead of target width');
}
catch (diagnostic $expected) {
}
verify((new entry_target(63))->minimum === PHP_INT_MIN, '64-bit target boundary computation');
echo "Entry preparation checks passed\n";
