<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Exercise every optional-field combination without requiring a recursive AST traversal. */
function check_binding_invariants(): void
{
	foreach ([binding_kind::unresolved, binding_kind::declaration, binding_kind::assignment] as $kind)
	{
		for ($mask = 0; $mask < 16; $mask++)
		{
			$binding = new binding_structure();
			$binding->syntax_kind = $kind;
			$binding->type_syntax = ($mask & 1) !== 0 ? Syntax_Nodes::make(node_kind::identifier, 0, 1) : null;
			$binding->target = ($mask & 2) !== 0 ? Syntax_Nodes::make(node_kind::identifier, 0, 1) : null;
			$binding->value = ($mask & 4) !== 0 ? Syntax_Nodes::make(node_kind::identifier, 0, 1) : null;
			$binding->equals_token_index = ($mask & 8) !== 0 ? 2 : null;

			// Enumerate valid shapes independently of the validator's predicates.
			$accepted = match ($kind) {
				binding_kind::unresolved => [12],
				binding_kind::declaration => [1, 13],
				binding_kind::assignment => [12, 14],
			};
			$valid = true;
			try {
				Syntax_Nodes::validate_payload(node_kind::variable_binding_statement, $binding);
			}
			catch (\LogicException $error) {
				$valid = false;
			}
			if ($valid !== in_array($mask, $accepted, true)) {
				throw new \LogicException('Unexpected binding acceptance: ' . $kind->name . '/' . $mask);
			}
		}
	}
}

/** Require mode and ampersand presence to agree in both directions. */
function check_parameter_invariants(): void
{
	foreach ([passing_mode::value, passing_mode::reference] as $mode)
	{
		foreach ([null, 0] as $index)
		{
			$parameter = new parameter_structure();
			$parameter->mode = $mode;
			$parameter->reference_token_index = $index;
			$valid = true;
			try {
				Syntax_Nodes::validate_payload(node_kind::parameter_declaration, $parameter);
			}
			catch (\LogicException $error) {
				$valid = false;
			}
			$expected = ($mode === passing_mode::value) ? ($index === null) : ($index === 0);
			if ($valid !== $expected) {
				throw new \LogicException('Unexpected parameter acceptance');
			}
		}
	}
}

/** Kind/payload disagreement must fail before a node can be linked or published. */
function check_node_construction(): void
{
	$payload = new integer_literal_structure();
	$literal = Syntax_Nodes::make(node_kind::integer_literal, 2, 3, $payload);
	if (($literal->kind() !== node_kind::integer_literal) || ($literal->payload() !== $payload)) {
		throw new \LogicException('Node construction lost its kind or specialization identity');
	}
	foreach ([node_kind::identifier, node_kind::variable_reference, node_kind::return_statement] as $kind)
	{
		try {
			Syntax_Nodes::make($kind, 2, 3, $payload);
			throw new \RuntimeException('Mismatched node specialization accepted');
		}
		catch (\LogicException $expected) {
		}
	}
}

check_node_construction();
check_binding_invariants();
check_parameter_invariants();
echo "AST invariants: 48 binding combinations and four parameter combinations passed\n";

// Rejected graph edits must leave all links unchanged.
$parent = Syntax_Nodes::make(node_kind::block, 0, 3, new block_structure(new scope()));
$first = Syntax_Nodes::make(node_kind::identifier, 0, 1, null);
$last = Syntax_Nodes::make(node_kind::integer_literal, 2, 3, null);
$children = new Storage();
$children->append($first);
$children->append($last);
ast_node::link_children($parent, $children);
$before = serialize($parent);
$attempts = [[$parent, $children], [$first, $children]];
$cycle = new Storage();
$cycle->append($parent);
$attempts[] = [$last, $cycle];
$duplicate = new Storage();
$unused = Syntax_Nodes::make(node_kind::identifier, 0, 1, null);
$duplicate->append($unused);
$duplicate->append($unused);
$attempts[] = [$last, $duplicate];
foreach ($attempts as [$owner, $members])
{
	try {
		ast_node::link_children($owner, $members);
		throw new \RuntimeException('Invalid AST graph edit accepted');
	}
	catch (\LogicException $expected) {
	}
	if (serialize($parent) !== $before) {
		throw new \RuntimeException('Rejected AST edit changed links');
	}
}
if (!(new \ReflectionClass(ast_node::class))->isFinal()) {
	throw new \RuntimeException('Common AST node must be final');
}
$children->remove(0);
if (($parent->first_child() !== $first) || ($last->prev() !== $first)) {
	throw new \RuntimeException('Changing input membership changed linked children');
}
echo "AST links: common final node, cycle/duplicate/reparent rejection and membership independence passed\n";
