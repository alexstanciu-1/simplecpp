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
			$binding = new binding_specialization();
			$binding->classification = $kind;
			$binding->type_syntax = ($mask & 1) !== 0 ? new ast_node() : null;
			$binding->target = ($mask & 2) !== 0 ? new ast_node() : null;
			$binding->value = ($mask & 4) !== 0 ? new ast_node() : null;
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
			$parameter = new parameter_specialization();
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

check_binding_invariants();
check_parameter_invariants();
echo "AST invariants: 48 binding combinations and four parameter combinations passed\n";
