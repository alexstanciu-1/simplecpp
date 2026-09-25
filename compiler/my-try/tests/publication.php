<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Build a private parse result without touching compiler roots. */
function parse_private(string $name, string $content): parsed_file
{
	$input = new file();
	$input->path = $name;
	$input->content = $content;
	return (new Parser((new Tokenizer($input))->tokenize()))->parse();
}

Model::reset();
$first = parse_private('first.phs', 'function exported(): int { $private int = 4; return $private; }');
$second = parse_private('second.phs', 'return exported();');
if (!Model::$syntax_files->is_empty() || !empty(Model::$global_scope->functions)) {
	throw new \LogicException('Private parsing published global state');
}
// Deliberately publish the use before its definition.
Compiler::publish_parsed($second);
Compiler::publish_parsed($first);
if (count(Model::$global_scope->functions['exported']) !== 1 || isset(Model::$global_scope->variables['private'])) {
	throw new \LogicException('Publication lost an export or exposed function locals');
}
$resolved = (new Name_Preparation())->prepare($second->collection);
$entry = $second->collection->entries[$second->collection->function_references[0]];
if ($resolved->function_references[$entry->token_index]->file !== $first->collection) {
	throw new \LogicException('Publication changed cross-file identity');
}
try {
	Compiler::publish_parsed($first);
	throw new \RuntimeException('Duplicate publication accepted');
}
catch (\LogicException $expected) {
}
if (count(Model::$global_scope->functions['exported']) !== 1) {
	throw new \LogicException('Duplicate publication damaged indexes');
}
// A local definition must not hide a conflicting published declaration.
$duplicate = parse_private('duplicate.phs', 'function exported(): int { return 2; } return exported();');
Compiler::publish_parsed($duplicate);
try {
	(new Name_Preparation())->prepare($duplicate->collection);
	throw new \RuntimeException('File ownership hid a global duplicate');
}
catch (\RuntimeException $expected) {
	if (!str_contains($expected->getMessage(), 'Expected one function target')) {
		throw $expected;
	}
}
echo "Publication: isolation, reversed completion, visibility, identity and duplicate rejection passed\n";


$queue = new Source_Work_Queue();
$queue->enqueue($first->tokens->file, $first->tokens);
$queue->enqueue($second->tokens->file, $second->tokens);
$items = $queue->items();
$a = $items[0];
$b = $items[1];
$queue->start($a);
$queue->start($b);
$queue->complete($b);
if ($queue->finished()) {
	throw new \LogicException('Queue crossed barrier with running work');
}
$foreign = new Source_Work_Queue();
$foreign->enqueue($first->tokens->file, $first->tokens);
$d = $foreign->items()[0];
$foreign->start($d);
try {
	$queue->complete($d);
	throw new \RuntimeException('Foreign completion accepted');
}
catch (\LogicException $expected) {
}
$queue->complete($a);
if (!$queue->finished()) {
	throw new \LogicException('Completed queue did not reach barrier');
}
try {
	$queue->complete($a);
	throw new \RuntimeException('Repeated completion accepted');
}
catch (\LogicException $expected) {
}
try {
	$queue->enqueue($first->tokens->file, $first->tokens);
	throw new \RuntimeException('Enqueue after sealing accepted');
}
catch (\LogicException $expected) {
}
$foreign->fail($d);
if ($foreign->finished()) {
	throw new \LogicException('Failed queue crossed successful barrier');
}
echo "Queue: out-of-order completion, sealed membership, ownership and failure barrier passed\n";

$unique = new Source_Work_Queue();
$unique->enqueue($first->tokens->file);
try {
	$unique->enqueue($first->tokens->file);
	throw new \RuntimeException('Duplicate source worker accepted');
}
catch (\LogicException $expected) {
}
try {
	$unique->enqueue($second->tokens->file, $first->tokens);
	throw new \RuntimeException('Mismatched snapshot accepted');
}
catch (\LogicException $expected) {
}
echo "Queue: exclusive source ownership and snapshot provenance passed\n";
