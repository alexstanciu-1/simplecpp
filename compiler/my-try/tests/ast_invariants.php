<?php

/* Verify the typed model's boundaries instead of obsolete kind/payload combinations. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Reject an invalid operation without confusing a missing rejection with the expected failure. */
function rejected(callable $operation, string $exception): void
{
	try {
		$operation();
	}
	catch (\Throwable $error) {
		if ($error instanceof $exception) {
			return;
		}
		throw $error;
	}
	throw new \LogicException('Invalid AST operation was accepted');
}

$base = new \ReflectionClass(ast_node::class);
if (!$base->isAbstract() || ($base->getProperties() !== [])) {
	throw new \LogicException('AST base must remain abstract and property-free');
}
if (method_exists(ast_node::class, 'parent') || method_exists(ast_node::class, 'set_inspection_parent')) {
	throw new \LogicException('Unused inspection parent API returned');
}
$type = new named_type_node();
$type->name = 'int';
$type->set_span(0, 1);
$literal = new integer_literal_node();
$literal->set_span(1, 2);
$target = new variable_reference_node();
$target->name = 'x';
$target->set_span(2, 3);
$assignment = new assignment_expression_node();
$assignment->target = $target;
$assignment->value = $literal;
$assignment->set_span(1, 3);
rejected(function () use ($assignment, $literal): void {
	$assignment->target = $literal;
}, \TypeError::class);
rejected(function () use ($assignment, $type): void {
	$assignment->value = $type;
}, \TypeError::class);
$function = new function_node(new scope());
rejected(function () use ($function, $literal): void {
	$function->return_type = $literal;
}, \TypeError::class);
rejected(function () use ($literal): void {
	$literal->set_span(3, 2);
}, \LogicException::class);
rejected(function () use ($literal): void {
	$literal->set_span(0, 4294967296);
}, \LogicException::class);
if (($literal->start_token() !== 1) || ($literal->end_token() !== 2)) {
	throw new \LogicException('Rejected span update changed provenance');
}
rejected(function () use ($literal): void {
	$literal->require_preparation();
}, \TypeError::class);
$entry = new collected_variable_write(new collected_file(new token_list()), $target);
$target->attach_occurrence($entry);
rejected(function () use ($target, $entry): void {
	$target->attach_occurrence($entry);
}, \LogicException::class);
if ($target->occurrence() !== $entry) {
	throw new \LogicException('Occurrence attachment lost identity');
}
$cursor = $assignment->children();
$other = $assignment->children();
if (($cursor->current() !== $target) || ($other->current() !== $target)) {
	throw new \LogicException('Inspection lost child order');
}
$cursor->next();
if (($cursor->current() !== $literal) || ($other->current() !== $target)) {
	throw new \LogicException('Inspection cursors share progress');
}
$cursor->next();
rejected(function () use ($cursor): void {
	$cursor->current();
}, \LogicException::class);
rejected(function () use ($cursor): void {
	$cursor->rewind();
}, \LogicException::class);

/** Record traversal independently of inspection iterators, including additional token sites. */
final class Maintenance_Trace implements node_maintenance_worker_i
{
	public array $nodes /** vector<ast_node> */ = [];
	public bool $recursive = false;

	public function enter(ast_node $node): void
	{
		$this->nodes[] = $node;
	}

	public function edge(ast_node $parent, ast_node $child): void
	{
		if ($this->recursive) {
			$child->maintain($this);
		}
	}

	public function token_index(int $index): int
	{
		return $index + 10;
	}
}

$binary = new binary_expression_node();
$binary->left = $assignment;
$right = new integer_literal_node();
$right->set_span(4, 5);
$binary->right = $right;
$binary->operator_token_index = 3;
$binary->set_span(1, 5);
$trace = new Maintenance_Trace();
$binary->maintain($trace);
if (($trace->nodes !== [$binary]) || ($binary->operator_token_index !== 13)) {
	throw new \LogicException('Maintenance ignored shallow traversal or an additional token site');
}
$trace->nodes = [];
$trace->recursive = true;
$binary->maintain($trace);
if (($trace->nodes !== [$binary, $assignment, $target, $literal, $right]) || ($binary->operator_token_index !== 23)) {
	throw new \LogicException('Node-owned maintenance lost grammar order or visited a node twice');
}
// Work guards use canonical owner state; no per-expression deletion fields are needed.
$source = new collected_file(new token_list());
$owner = new preparation_owner(preparation_kind::file_body, $source);
rejected(function () use ($owner): void {
	Preparation_Worker::require_active($owner);
}, \LogicException::class);
$source->parse_complete = true;
Preparation_Worker::require_active($owner);
$owner->change_status = change_state::deleted;
rejected(function () use ($owner): void {
	Preparation_Worker::require_active($owner);
}, \LogicException::class);
$owner->change_status = change_state::unchanged;
$source->deleted = true;
rejected(function () use ($owner): void {
	Preparation_Worker::require_active($owner);
}, \LogicException::class);

// Unsupported nodes still allow maintenance without fabricating semantic support.
foreach ([new punctuation_node(), new comment_node()] as $trivia) {
	$trivia->set_span(0, 1);
	Preparation_Cleanup::tree($trivia);
	if ($trivia->children()->valid()) {
		throw new \LogicException('Trivia unexpectedly owns syntax children');
	}
}
echo "AST invariants: typed fields, spans, facts, occurrences, cursors and work guards passed\n";
