<?php

/* Prepare the straight-line integer slice without mutating source syntax or scopes. */
namespace scpp\compiler;

final class File_Preparation
{
	private prepared_file $result;
	/** Invocation-local declaration index; never published into parsed source scopes. */
	private scope $locals;
	private type_definition $integer;
	private array $occurrences /** hash<collected_name, int> */ = [];

	/** Every invocation owns fresh results and source-order declaration state. */
	public function __construct(collected_file $source, scope $language_scope)
	{
		$this->result = new prepared_file();
		$this->result->source = $source;
		$this->locals = new scope();
		$this->integer = Language_Types::integer($language_scope);
		$entries /** Storage<collected_name> */ = $source->entries;
		foreach ($entries as $entry) {
			if ($entry->changes !== \scpp\compiler\SYNC_DELETED) {
				$this->occurrences[$entry->token_index] = $entry;
			}
		}
	}

	/** This slice handles one top-level body; other constructs remain explicit blockers. */
	public function prepare(): prepared_file
	{
		$body = Syntax_Nodes::block_data($this->result->source->root);
		$children /** Storage<ast_node> */ = $body->children;
		foreach ($children as $node)
		{
			if ($node->kind === node_kind::variable_binding_statement) {
				$this->binding($node);
			}
			elseif ($node->kind === node_kind::return_statement) {
				$return_node = Syntax_Nodes::return_data($node);
				if ($return_node->expression !== null) {
					$this->expression(object_cast($return_node->expression, ast_node::class));
				}
			}
			else {
				throw new \RuntimeException('S2S preparation does not support this statement yet');
			}
		}
		return $this->result;
	}

	/** Explicit types resolve through source scopes; inference uses the initializer's type. */
	private function binding(ast_node $node): void
	{
		$syntax = Syntax_Nodes::binding_data($node);
		if (($syntax->target !== null) || ($syntax->value === null)) {
			throw new \RuntimeException('S2S currently requires a local binding with an initializer');
		}
		$entry = $this->occurrences[(int) $syntax->name_token_index];
		$value = $this->expression(object_cast($syntax->value, ast_node::class));
		$binding = new prepared_binding();
		$binding->syntax = $node;
		$binding->initializer = $value;
		$binding->type = $value->type;
		$previous /** vector<collected_name> */ = $this->locals->variables_named($entry->name);
		if ($syntax->type_syntax !== null)
		{
			$type_node = object_cast($syntax->type_syntax, ast_node::class);
			if ($type_node->kind !== node_kind::identifier) {
				throw new \RuntimeException('S2S constructed types are not supported yet');
			}
			$tokens /** Storage<token> */ = $this->result->source->source->tokens;
			$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
			$types = Scope_Lookup::types($lexical_scope, $tokens[(int) $type_node->token_index]->text());
			if (q_count($types) !== 1) {
				throw new \RuntimeException('S2S needs one resolved local type');
			}
			$binding->type = $types[0];
		}
		if (($syntax->type_syntax !== null) || (q_count($previous) === 0)) {
			$binding->classification = binding_kind::declaration;
			$binding->declaration = $entry;
			$this->locals->register($entry);
		}
		else
		{
			if (q_count($previous) !== 1) {
				throw new \RuntimeException('S2S needs one local assignment target');
			}
			$binding->classification = binding_kind::assignment;
			$binding->declaration = $previous[0];
			$binding->type = $this->result->bindings[$previous[0]->token_index]->type;
		}
		if (($binding->type !== $this->integer) || ($value->type !== $this->integer)) {
			throw new \RuntimeException('S2S binding lowering currently supports canonical int only');
		}
		$this->result->bindings[(int) $node->token_index] = $binding;
	}

	/** Literal and reference expressions share resolved type identity before emission. */
	private function expression(ast_node $node): prepared_expression
	{
		$value = new prepared_expression();
		$value->syntax = $node;
		if ($node->kind === node_kind::integer_literal) {
			$tokens /** Storage<token> */ = $this->result->source->source->tokens;
			$value->literal = Integer_Literals::decimal($tokens[(int) $node->token_index]->text());
			$value->type = $this->integer;
		}
		elseif ($node->kind === node_kind::variable_reference)
		{
			$entry = $this->occurrences[(int) $node->token_index];
			$targets /** vector<collected_name> */ = $this->locals->variables_named($entry->name);
			if (q_count($targets) !== 1) {
				throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
			}
			$value->declaration = $targets[0];
			$value->type = $this->result->bindings[$targets[0]->token_index]->type;
		}
		else {
			throw new \RuntimeException('S2S expression lowering is not implemented for this form');
		}
		$this->result->expressions[(int) $node->token_index] = $value;
		return $value;
	}
}
