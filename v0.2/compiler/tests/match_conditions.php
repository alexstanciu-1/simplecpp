<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter,
	Function_Preparation, Preparation, Syntax_Inspection, Compilation_Inspection,
	ast_match_expression, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

foreach (flavor::cases() as $flavor) {
	$entry = (new Source_Frontend())->prepare(new source_file('conditions',
		'return match(true){default=>3,false=>1,true=>2};', $flavor), new entry_target(31));
	$match = $entry->body()->statements[0]->value;
	verify($match instanceof ast_match_expression, 'Reuse the existing match expression');
	verify(ast_match_expression::is_condition_subject($match->subject), 'Literal true identifies condition syntax');
	verify($entry->body()->expression_visits === 7, 'Prepare every expression exactly once');
	verify($match->subject->parent === $match, 'Owned subject parent');
	foreach ($match->arms as $arm) {
		verify($arm->parent === $match && $arm->result->parent === $arm, 'Owned arm/result parents');
		if ($arm->label !== null) verify($arm->label->parent === $arm, 'Owned condition parent');
	}
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	verify(serialize($entry) === $before, 'Emission and inspection must not mutate syntax/facts');
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify($again->body()->expression_visits === 7, 'Repreparation must remain single-pass');
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare must preserve output');
	$local = match ($flavor) {
		flavor::php => '$ready bool=true; return match($ready){default=>1};',
		flavor::cpp => 'bool ready=true; return match(ready){default=>1};',
		flavor::js => 'let ready:bool=true; return match(ready){default=>1};',
	};
	try {
		(new Source_Frontend())->prepare(new source_file('unsupported', $local, $flavor), new entry_target(31));
		throw new RuntimeException('A known local value must not change match syntax mode');
	} catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), 'literal true'), $error->getMessage());
	}
}
echo "Match conditions: ownership, single-pass preparation, pure emission and reset passed\n";
