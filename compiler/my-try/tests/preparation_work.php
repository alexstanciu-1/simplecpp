<?php

/* Prove work roles, typed relationships and reuse without manufacturing body symbols. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function work_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

Compiler_Lifecycle::reset();
$source = new file();
$source->path = 'preparation_work.phs';
$source->content = 'struct Box { int32 $value; } function read(Box &$box): int { return $box->value; } $box Box; return read($box);';
$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
$parsed->root_scope()->set_parent(Model::$language_scope);
$collection = $parsed->collection;
$count = count($collection->entries);
$prepared = (new File_Preparation($collection, Model::$language_scope))->prepare();
$record = object_cast($parsed->root->declarations[0], struct_node::class);
$function = object_cast($parsed->root->declarations[1], function_node::class);
$layout = $record->occurrence()->preparation_work_owner();
$signature = $function->occurrence()->preparation_work_owner();
$body = $function->body->work();
$entry = $parsed->root->body->work();
work_check($layout instanceof record_definition_work, 'Record was not assigned layout work');
work_check($signature instanceof function_signature_work, 'Function was not assigned signature work');
work_check($body instanceof function_body_work, 'Function body was not assigned body work');
work_check($entry instanceof file_body_work, 'File body was not assigned file-body work');
work_check($entry->declaration() === null, 'File body acquired a named declaration');
work_check($body->declaration() === $signature->declaration(), 'Body and signature lost the shared function identity');
work_check($signature->required_declaration() === $signature->function_definition(), 'Required and specialized signature access lost identity');
work_check($body->function_definition() === $signature->function_definition(), 'Specialized body access lost function identity');
work_check($layout->declaration() === $layout->required_declaration(), 'Optional and required record access lost identity');
work_check($layout->required_declaration() === $layout->record_definition(), 'Specialized record access lost identity');
work_check($signature !== $body, 'Signature and body work collapsed');
work_check(count($collection->entries) === $count, 'Preparation manufactured body symbols');
foreach ([$layout, $signature, $body, $entry] as $work) {
	work_check($work->source === $collection, 'Work source does not match its canonical declaration');
	work_check(!property_exists($work, 'kind') && !property_exists($work, 'declaration'), 'Mutable tag/nullable declaration storage survived');
	work_check($work->state === preparation_state::ready, 'Work did not settle');
}
$return = object_cast($parsed->root->body->statements[1], return_node::class);
$call = object_cast($return->expression, call_node::class)->require_call_preparation();
work_check($call->declaration === $function->occurrence(), 'Call lost its typed function identity');
$version = $body->version;
(new File_Preparation($collection, Model::$language_scope))->prepare();
work_check($body === $function->body->work() && $body->version === $version, 'No-op preparation replaced or rebuilt body work');

// Wrong-role relationships fail at the typed constructor/attachment boundary.
$rejected = 0;
try {
	$invalid_body = new function_body_work($record->occurrence());
}
catch (\TypeError $expected) {
	$rejected++;
}
try {
	$invalid_record = new record_definition_work($function->occurrence());
}
catch (\TypeError $expected) {
	$rejected++;
}
try {
	$function->body->attach_work($signature);
}
catch (\TypeError $expected) {
	$rejected++;
}
work_check($rejected === 3, 'Invalid work-role combination was accepted');
work_check($function->body->work() === $body, 'Rejected attachment changed retained body work');
echo "Preparation work: typed roles, canonical relationships, no-op identity and invalid-role rejection passed\n";
