<?php

/* Prove typed dispatch preserves local binding, member writes and body/block source order. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function dispatch_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

foreach ([false, true] as $nested)
{
	Compiler_Lifecycle::reset();
	$source = new file();
	$source->path = 'preparation_dispatch.phs';
	$source->content = 'struct Box { int32 $value; } function put(Box &$box, int $n): int { $box->value = $n; $x = $n; $x = 7; return $x; } $box Box; return put($box, 9);';
	$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
	$parsed->root_scope()->set_parent(Model::$language_scope);
	$function = object_cast($parsed->root->declarations[1], function_node::class);
	$statements /** Storage<statement_node> */ = $function->body->statements;
	if ($nested)
	{
		// Ordinary blocks are represented already, although standalone block parsing is deferred.
		$block = new block_node();
		$block->statements = $statements;
		$block->set_span($function->body->start_token(), $function->body->end_token());
		$outer /** Storage<statement_node> */ = new Storage();
		$outer->append($block);
		$function->body->statements = $outer;
	}
	(new File_Preparation($parsed->collection, Model::$language_scope))->prepare();
	$signature = $function->require_preparation();
	dispatch_check($signature->parameters[0]->mode === passing_mode::reference, 'Parameter preparation lost reference passing');
	dispatch_check($signature->parameters[1] === $function->parameters[1]->require_preparation(), 'Signature does not share parameter facts');

	$member = object_cast(object_cast($statements[0], expression_statement_node::class)->expression, assignment_expression_node::class);
	$local = object_cast(object_cast($statements[1], expression_statement_node::class)->expression, assignment_expression_node::class);
	$repeat = object_cast(object_cast($statements[2], expression_statement_node::class)->expression, assignment_expression_node::class);
	$field = object_cast($member->target, field_access_node::class)->require_field_access_preparation();
	dispatch_check($member->require_assignment_preparation()->binding->resolved_kind === binding_kind::assignment, 'Field write introduced storage');
	dispatch_check($member->require_assignment_preparation()->binding->declaration === $field->field->declaration, 'Field write lost its declaration');
	dispatch_check($local->require_assignment_preparation()->binding->resolved_kind === binding_kind::declaration, 'First variable assignment did not declare storage');
	dispatch_check($repeat->require_assignment_preparation()->binding->resolved_kind === binding_kind::assignment, 'Repeated assignment redeclared storage');
	dispatch_check($repeat->require_assignment_preparation()->binding->declaration === $local->require_assignment_preparation()->binding->declaration, 'Local assignment lost storage identity');
	$initializer = object_cast($local->value, variable_reference_node::class)->require_variable_reference_preparation();
	dispatch_check($initializer->declaration === $signature->parameters[1]->declaration, 'Initializer lost the parameter binding');
	$return = object_cast($statements[3], return_node::class);
	$reference = object_cast($return->expression, variable_reference_node::class)->require_variable_reference_preparation();
	dispatch_check($reference->declaration === $local->require_assignment_preparation()->binding->declaration, 'Body/block traversal lost source-order locals');
}
echo "Preparation dispatch: parameter facts, local/member writes and body/block source order passed\n";
