<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Source_Frontend, Preparation, Function_Preparation, Cpp_Emitter,
		Cpp_Program, Program_Preparation, entry_target, flavor, source_file, diagnostic, type_int64};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Assert that an unsettled or invalidated fact rejects access. */
function unavailable(Closure $read): void
{
	try {
		$read();
		throw new RuntimeException('Incomplete or stale facts were accepted');
	}
	catch (LogicException $expected) {
	}
}
$frontend = new Source_Frontend();
$file = $frontend->parse(new source_file('entry',
		'void ping() { echo ""; return; } int value=8; echo value; ping(); return value;', flavor::cpp));
$body = $file->main()->body();
[$local, $echo, $call, $return] = $body->statements;
/** @var \scpp\compiler\ast_local_declaration $local */
/** @var \scpp\compiler\ast_echo_statement $echo */
/** @var \scpp\compiler\ast_call_statement $call */
/** @var \scpp\compiler\ast_return_statement $return */
/** @var \scpp\compiler\ast_string_expression $string */
$string = $file->functions()[0]->body()->statements[0]->values[0];
unavailable(fn() => $local->initializer->integer());
unavailable(fn() => $echo->values[0]->local_def());
unavailable(fn() => $echo->values[0]->constant());
unavailable(fn() => $string->bytes());
unavailable(fn() => $return->conversion());
$program = Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31));
verify(($program->entry->body() === $body) && ($body->statements[0] === $local), 'Preparation replaced AST identity');
verify(($local->initializer->integer() === 8) && ($string->bytes() === ''), 'Literal facts were lost');
verify(($echo->values[0]->local_def() === $local) && ($return->value->local_def() === $local),
	'References lost their declaration');
$before = serialize($file);
$expected = (new Cpp_Emitter())->emit($program->entry);
Cpp_Program::emit($program);
verify(serialize($file) === $before, 'Lowering mutated facts');
// An occurrence retains its value fact, rather than rereading the declaration's later value.
$local->reset_preparation();
$local->settle_type(type_int64::instance(), 9);
verify($echo->values[0]->constant() === 8, 'Reference value was not occurrence-specific');
Function_Preparation::reset($file->main());
unavailable(fn() => $local->initializer->integer());
unavailable(fn() => $echo->values[0]->local_def());
unavailable(fn() => $return->conversion());
unavailable(fn() => $call->function_def());
unavailable(fn() => (new Cpp_Emitter())->emit($program->entry));
$again = Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31));
verify((new Cpp_Emitter())->emit($again->entry) === $expected, 'Reanalysis changed output');
unavailable(fn() => Cpp_Program::emit($program));

// A new target can fail after expression facts have been written; no body/file success may leak.
$large = $frontend->parse(new source_file('large', 'int value=32768; return value;', flavor::cpp));
$old = (new Preparation())->prepare($large, new entry_target(31));
try {
	(new Preparation())->prepare($large, new entry_target(15));
	throw new RuntimeException('Expected target rejection');
}
catch (diagnostic $expected_error) {
}
unavailable(fn() => $large->main()->body()->require_prepared());
unavailable(fn() => $large->main()->body()->statements[1]->conversion());
unavailable(fn() => (new Cpp_Emitter())->emit($old));
$recovered = (new Preparation())->prepare($large, new entry_target(31));
verify($recovered->body()->statements[1]->value->constant() === 32768, 'Failed run poisoned subsequent preparation');

// Successful source bodies must not permit a whole-program handle after another source fails.
$bad = $frontend->parse(new source_file('bad', 'void bad() { echo missing; }', flavor::cpp));
try {
	Program_Preparation::prepare('entry', ['entry' => $file, 'bad' => $bad], new entry_target(31));
	throw new RuntimeException('Expected missing local');
}
catch (diagnostic $expected_error) {
}
unavailable(fn() => $file->prepared_revision());
unavailable(fn() => Cpp_Program::emit($again));
echo "AST-owned facts, occurrence values, readiness, failed reanalysis, stale handles and lowering purity passed\n";
