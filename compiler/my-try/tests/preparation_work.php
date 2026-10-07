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
$record = object_cast($parsed->root->single_body()->declarations[0], type_declaration_node::class);
$function = object_cast($parsed->root->single_body()->declarations[1], function_node::class);
$layout = $record->occurrence()->preparation_work_owner();
$signature = $function->occurrence()->preparation_work_owner();
$body = $function->body->work();
$entry = $parsed->root->single_body()->body->work();
work_check($layout instanceof record_definition_work, 'Record was not assigned layout work');
work_check($signature instanceof source_callable_signature_work, 'Function was not assigned signature work');
work_check($body instanceof source_callable_body_work, 'Function body was not assigned body work');
work_check($entry instanceof file_body_work, 'File body was not assigned file-body work');
work_check($entry->declaration() === null, 'File body acquired a named declaration');
work_check($entry->executable_body() === $parsed->root->single_body()->body, 'File work lost its explicit executable body');
work_check($body->executable_body() === $function->body, 'Function work lost its explicit executable body');
work_check($body->declaration() === $signature->declaration(), 'Body and signature lost the shared function identity');
work_check($signature->required_declaration() === $signature->callable_definition(), 'Required and specialized signature access lost identity');
work_check($body->callable_definition() === $signature->callable_definition(), 'Specialized body access lost function identity');
work_check($layout->declaration() === $layout->required_declaration(), 'Optional and required record access lost identity');
work_check($layout->required_declaration() === $layout->record_definition(), 'Specialized record access lost identity');
work_check($signature !== $body, 'Signature and body work collapsed');
work_check(count($collection->entries) === $count, 'Preparation manufactured body symbols');
foreach ([$layout, $signature, $body, $entry] as $work) {
	foreach (['enqueue', 'rebuild', 'settle_members', 'retire_from'] as $hook) {
		work_check(!method_exists($work, $hook), 'Work record retained processing hook: ' . $hook);
	}
	work_check($work->source === $collection, 'Work source does not match its canonical declaration');
	work_check(!property_exists($work, 'kind') && !property_exists($work, 'declaration'), 'Mutable tag/nullable declaration storage survived');
	work_check($work->state === preparation_state::ready, 'Work did not settle');
	work_check($work->change_status === change_state::unchanged && $work->version === 1, 'Initial work did not settle exactly once');
}
work_check($record->occurrence()->change_status === change_state::unchanged, 'Record declaration did not settle');
work_check($record->properties[0]->occurrence()->change_status === change_state::unchanged, 'Record field did not settle');
work_check($function->occurrence()->change_status === change_state::unchanged, 'Function declaration did not settle');
work_check($function->parameters[0]->occurrence()->change_status === change_state::unchanged, 'Function parameter did not settle');
$return = object_cast($parsed->root->single_body()->body->statements[1], return_node::class);
$call = object_cast($return->expression, call_node::class)->require_call_preparation();
work_check(weakref_get($call->target) === $function->occurrence(), 'Call lost its typed function identity');
$version = $body->version;
(new File_Preparation($collection, Model::$language_scope))->prepare();
work_check($body === $function->body->work() && $body->version === $version, 'No-op preparation replaced or rebuilt body work');
work_check($layout === $record->occurrence()->preparation_work_owner(), 'No-op replaced record work');
work_check($signature === $function->occurrence()->preparation_work_owner(), 'No-op replaced signature work');
work_check($entry === $parsed->root->single_body()->body->work(), 'No-op replaced file-body work');
foreach ([$layout, $signature, $body, $entry] as $work) {
	work_check($work->version === 1, 'No-op rebuilt retained work');
}

// Wrong-role relationships fail at the typed constructor/attachment boundary.
$rejected = 0;
try {
	$invalid_body = new source_callable_body_work($record->occurrence());
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

// Two executable units in one source must not query the same file-root change flag.
// This exercises the body-work model without claiming namespace syntax admission.
$second_scope = new scope();
$second_body = new function_body_node($second_scope);
$second_work = new file_body_work($collection, $second_body);
$second_body->attach_work($second_work);
work_check($second_work->source === $entry->source, 'Independent body work lost its common source');
work_check(Preparation_Status::source_changed($second_work), 'Additional body change was read from the file root');
work_check(!Preparation_Status::source_changed($entry), 'Additional body change contaminated the entry body');
$second_body->syntax_changed = false;
$parsed->root->single_body()->body->syntax_changed = true;
work_check(!Preparation_Status::source_changed($second_work), 'File-root change contaminated additional body work');
work_check(Preparation_Status::source_changed($entry), 'Entry work missed its own changed body');
$parsed->root->single_body()->body->syntax_changed = false;

// Retirement dispatch must unlink every role, including both directions of dependencies.
// A member uses the same work classes, without becoming a namespace function or property.
$method = new method_node();
$method->name = 'value';
$method->set_span($record->start_token(), $record->end_token());
$method_scope = new scope();
$method_scope->set_parent($method->signature_scope());
$method->signature_scope()->set_parent($record->member_scope());
$method->set_parsed_body(new function_body_node($method_scope));
$collector = new Symbol_Collector($collection, $collection->token_snapshot(), $parsed->root_scope(), null, $collection->revision);
$collector->collect_method($method, $record->occurrence(), $record->start_token());
$record->methods->append($method);
$worker = new Preparation_Worker(Model::$language_scope);
$worker->select_callable($method);
$member_signature = $method->occurrence()->preparation_work_owner();
$member_body = $method->require_body()->work();
work_check($member_signature instanceof source_callable_signature_work, 'Member acquired a separate signature work family');
work_check($member_body instanceof source_callable_body_work, 'Member acquired a separate body work family');
work_check($member_signature->callable_definition() === $member_body->callable_definition(), 'Member work lost shared declaration identity');
work_check($member_signature !== $member_body, 'Member signature and body work collapsed');
work_check($method->occurrence()->declaring_type() === $record->occurrence(), 'Member lost its declaring type');
work_check($method->occurrence()->qualified_name() === 'Box::value', 'Member qualified identity omitted its type');
work_check(count($record->member_scope()->methods_named('value')) === 1, 'Member was not indexed');
work_check(count($record->member_scope()->functions_named('value')) === 0, 'Member leaked into free-function lookup');
work_check(count($record->member_scope()->variables_named('value')) === 1, 'Member polluted same-named property lookup');
$worker->select_callable($method);
work_check($member_signature === $method->occurrence()->preparation_work_owner(), 'Member selection replaced signature identity');
work_check($member_body === $method->require_body()->work(), 'Member selection replaced body identity');
$sources /** Storage<collected_file> */ = new Storage();
$sources->append($collection);
(new Preparation_Worker(Model::$language_scope))->discard_sources($sources);
foreach ([$layout, $signature, $body, $entry, $member_signature, $member_body] as $work) {
	work_check($work->change_status === change_state::deleted, 'Discard did not retire work');
	work_check(count($work->dependencies) === 0 && count($work->dependents) === 0 && count($work->lookups) === 0, 'Retired work retained graph registrations');
}
$collection->deleted = true;
Collection_Retirement::remove_deleted_sources($sources);
work_check(count($record->member_scope()->methods_named('value')) === 0, 'Member retirement left its scope index behind');
echo "Preparation work: passive typed roles, member settlement, no-op identity, invalid-role rejection and retirement passed\n";
