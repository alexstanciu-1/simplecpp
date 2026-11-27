<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Recognizes PHP-flavor declarations and writes while preserving their unresolved binding intent.
 *
 * PHP-flavor syntax:
 * ```text
 * $value int = 3;
 * function ping(): void {}
 * ```
 */
final class Php_Grammar implements statement_grammar
{
	public function function_start(Parser $parser): bool
	{
		return $parser->cursor->keyword('function') && !$parser->cursor->next_is(token_kind::less);
	}

	public function function_header(Parser $parser, bool $static = false, bool $private = false, bool $protected = false, ?string $alias = null): ast_function_declaration
	{
		return $parser->named_function_header(static: $static, private: $private, protected: $protected, alias: $alias);
	}

	/** First bare writes declare locals; prior body-local declarations make subsequent writes assignments. */
	public function statement(Parser $parser, Body_Collection $collector, ?token_kind $terminator = token_kind::semicolon): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		$name = $cursor->binding_name(token_kind::variable);
		if ($parser->compound_operator() !== null) {
			return $parser->assignment($start, $name, assignment_intent::existing_binding, $terminator);
		}
		if ($cursor->is(token_kind::assign)) {
			if ($terminator === token_kind::semicolon && $cursor->next_is(token_kind::variable)
				&& ($cursor->tokens->tokens[$cursor->position() + 2]->kind ?? null) === token_kind::assign) {
				return $this->assignment_chain($parser, $collector, $start, $name);
			}
			if ($cursor->next_is(token_kind::ampersand) || !$collector->has_local($name)) {
				return $parser->declaration($start, $name, null, $terminator);
			}
			return $parser->assignment($start, $name, assignment_intent::declare_or_assign, $terminator);
		}
		return $parser->declaration($start, $name, $parser->named_type(), $terminator);
	}

	/** Collect ordinary writes in execution order. Each outer RHS reads the preceding destination. */
	private function assignment_chain(Parser $parser, Body_Collection $collector, int $start /** uint32 */, string $name): ast_block_statement
	{
		$cursor = $parser->cursor;
		$targets = [];
		$position = $start;
		while (true) {
			if (!$collector->has_local($name)) {
				throw new diagnostic($cursor->tokens->source, $cursor->tokens->tokens[$position]->offset, 'Chained assignment requires an existing local: $' . $name);
			}
			if (!$cursor->next_is(token_kind::variable) || ($cursor->tokens->tokens[$cursor->position() + 2]->kind ?? null) !== token_kind::assign) break;
			$targets[] = [$position, $name];
			$cursor->take(token_kind::assign, '=');
			$position = $cursor->position();
			$name = $cursor->binding_name(token_kind::variable);
		}
		$write = $parser->assignment($position, $name, assignment_intent::existing_binding);
		$end = $write->end;
		$writes = [$write];
		while ($targets !== []) {
			[$outer, $destination] = array_pop($targets);
			$read = new ast_name_expression($name, $position, $position + 1);
			$writes[] = new ast_assignment_statement($outer, $position + 1, $destination, $read, assignment_intent::existing_binding);
			$name = $destination;
			$position = $outer;
		}
		$child = $collector->child();
		foreach ($writes as $i => $statement) $child->statement($statement, $i);
		return new ast_block_statement($child->block($start, $end, $writes));
	}
}
