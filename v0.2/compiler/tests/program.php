<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Program, Program_Frontend, diagnostic, entry_target, flavor, program_sources, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Program_Frontend();
$target = new entry_target(31);
$sources = [
	'main.scpp' => new source_file('main', '$ret int=8; return $ret;', flavor::php),
	'a/notes.scpp' => new source_file('a/notes', '// empty', flavor::cpp),
	'b/notes.scpp' => new source_file('b/notes', '/* empty */', flavor::js),
];
$program = $frontend->prepare(new program_sources('main.scpp', $sources), $target);
verify($program->entry->file === $program->files['main.scpp'], 'Entry uses the existing AST file root');
verify($program->files['a/notes.scpp']->tokens->source === $sources['a/notes.scpp'], 'Secondary source identity');
verify($program->entry->statement_visits === 2, 'Entry prepared once');
$first = Cpp_Program::emit($program);
$second = Cpp_Program::emit($frontend->prepare(new program_sources('main.scpp', array_reverse($sources, true)), $target));
verify($first->files === $second->files, 'Input reordering changed deterministic C++ output');
verify($first->source_units === $second->source_units, 'Input order changed source identities');
verify(count(array_unique($first->source_units)) === 3, 'Same-basename sources collided');
verify(count($first->files) === 4, 'One unit per source plus entry adapter');
try {
	Cpp_Program::emit($program->entry);
	throw new RuntimeException('Program emitter accepted a standalone entry');
}
catch (TypeError $expected) {
}
$bad = $sources;
$bad['b/notes.scpp'] = new source_file('bad', 'return 9;', flavor::js);
try {
	$frontend->prepare(new program_sources('main.scpp', $bad), $target);
	throw new RuntimeException('Non-entry executable code was accepted');
}
catch (diagnostic $error) {
	verify($error->source === $bad['b/notes.scpp'], 'Non-entry diagnostic points to wrong file');
}
try {
	$frontend->prepare(new program_sources('missing.scpp', $sources), $target);
	throw new LogicException('Missing entry was accepted');
}
catch (RuntimeException $error) {
	verify(str_contains($error->getMessage(), 'not in the source inventory'), 'Wrong entry diagnostic');
}
verify(Cpp_Program::emit($frontend->prepare(new program_sources('main.scpp', $sources), $target))->files === $first->files,
	'Failure leaked program membership into a later run');

// Empty-file scaling is a whole-program foundation proof, not an incremental claim.
$measurements = [];
foreach ([10, 100, 1000] as $count)
{
	$many = ['main.scpp' => $sources['main.scpp']];
	for ($index = 0; $index < $count; $index++) {
		$path = 'empty/' . $index . '.scpp';
		$many[$path] = new source_file($path, '// empty', flavor::cpp);
	}
	$start = hrtime(true);
	$prepared = $frontend->prepare(new program_sources('main.scpp', $many), $target);
	$elapsed = (hrtime(true) - $start) / 1_000_000;
	verify((count($prepared->files) === ($count + 1)) && ($prepared->entry->expression_visits === 2), 'Program work shape');
	$measurements[] = ['secondary_files' => $count, 'prepare_pipeline_ms' => round($elapsed, 3)];
}
echo json_encode($measurements, JSON_THROW_ON_ERROR) . "\nProgram preparation checks passed\n";
