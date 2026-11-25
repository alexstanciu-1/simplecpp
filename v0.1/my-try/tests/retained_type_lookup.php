<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$directory = $argv[1];
mkdir($directory);
$reader = $directory . '/reader.phs';
$writer = $directory . '/writer.phs';
$declarations = 'class Kept {} enum Kept_Enum:int { case First = 1; }';
file_put_contents($reader, $declarations);
file_put_contents($writer, 'class Added_0 {}');
Compiler_Lifecycle::reset();
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->init([$directory]);
$compiler->tokenize(); $compiler->parse();
$record = Model::$global_scope->source_types_named('Kept')[0];
$enumeration = Model::$global_scope->source_types_named('Kept_Enum')[0];
for ($round = 1; $round <= 8; $round++) {
	file_put_contents($reader, str_repeat("\n", $round) . $declarations);
	file_put_contents($writer, 'class Added_' . $round . ' {}');
	$compiler->sync([$reader, $writer]);
	if ((Model::$global_scope->source_types_named('Kept')[0] !== $record)
		|| (Model::$global_scope->source_types_named('Kept_Enum')[0] !== $enumeration)) {
		throw new \LogicException('Concurrent-mode parsing replaced a retained source identity');
	}
	if (count(Model::$global_scope->source_types_named('Added_' . $round)) !== 1) {
		throw new \LogicException('New registration was not published');
	}
}
file_put_contents($reader, "\n" . $declarations);
file_put_contents($writer, 'class Broken {');
$failed = false;
try { $compiler->sync([$reader, $writer]); }
catch (\RuntimeException $error) { $failed = true; }
if (!$failed) { throw new \LogicException('Invalid source unexpectedly parsed'); }
file_put_contents($writer, 'class Repaired {}');
$compiler->sync([$writer]);
if ((Model::$global_scope->source_types_named('Kept')[0] !== $record)
	|| (Model::$global_scope->source_types_named('Kept_Enum')[0] !== $enumeration)
	|| count(Model::$global_scope->source_types_named('Repaired')) !== 1) {
	throw new \LogicException('Failure/retry lost retained identities or publication');
}
// No batch publication context is needed by standalone parsing.
$file = new file(); $file->path = 'standalone.phs'; $file->content = 'class Standalone {}';
$parsed = (new Parser((new Tokenizer($file))->tokenize()))->parse();
if (!$parsed->complete) { throw new \LogicException('Standalone parser needs a task context'); }
echo "Retained class/enum lookup, new publication, failure/retry and standalone parsing passed\n";
