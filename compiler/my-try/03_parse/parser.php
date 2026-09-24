<?php

/*
 * Role: parse retained tokens and collect occurrences.
 * Call map: Compiler::parse -> Parser::init -> Parser::parse -> statement/expression -> Symbol_Collector.
 */
namespace scpp\compiler;

final class Parser
{
	private token_list $tokens;
	private parsed_file $syntax;
	private scope $current_scope;
	private ?scope $target_scope = null;
	private Symbol_Collector $collector;
	private int $position = 0;

	public function init(token_list $tokens, ?scope $target_scope = null): void
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
	}

	/** Build syntax and collect occurrences together; publish only after a successful file parse. */
	public function parse(): parsed_file
	{
		$this->position = 0;
		$this->syntax = new parsed_file();
		$this->syntax->tokens = $this->tokens;
		$this->current_scope = $this->target_scope ?? new scope();
		if ($this->target_scope === null) {
			$this->syntax->scopes->append($this->current_scope);
		}
		$this->collector = new Symbol_Collector($this->tokens);
		$body = new block_specialization();
		$body->scope = $this->current_scope;

		while ($this->position < count($this->tokens->tokens)) {
			$body->children->append($this->statement());
		}
		$result = $this->syntax;
		$result->root = $this->node(node_kind::file, 0, $body);
		$result->collection = $this->collector->finish($result->root);

		if (dbg) {
			echo "\nAST: " . htmlspecialchars($this->tokens->file->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			$this->dump_node($result->root, 0);
		}
		return $result;
	}

	/** Select a statement from its leading syntax; names are never looked up here. */
	private function statement(): ast_node
	{
		if ($this->text() === 'struct') {
			return $this->struct_declaration();
		}
		if (in_array($this->text(), ['function', 'template'], true)) {
			return $this->function_declaration();
		}
		if ($this->text() === 'return') {
			return $this->return_statement();
		}
		if (str_starts_with($this->text(), '$')) {
			return $this->binding_statement();
		}
		if ($this->identifier()) {
			return $this->expression_statement();
		}
		throw $this->error('Expected a declaration, call or return statement');
	}

	/** Collect a named record and retain its ordered fields; no layout work belongs in parsing. */
	private function struct_declaration(): ast_node
	{
		if ($this->current_scope->function_boundary) {
			throw $this->error('Local struct declarations are not supported yet');
		}
		$start = $this->expect('struct');
		if (!$this->identifier()) {
			throw $this->error('Expected struct name');
		}
		$record = new struct_specialization();
		$record->name_token_index = $this->position++;
		$this->expect('{');
		while ($this->text() !== '}')
		{
			$field_start = $this->position;
			if ($this->text() === 'public') {
				$this->position++;
			}
			if (!$this->identifier()) {
				throw $this->error('Expected field type');
			}
			$field = new field_specialization();
			$type_start = $this->position++;
			$field->type_syntax = $this->node(node_kind::identifier, $type_start);
			$this->collector->record($field->type_syntax, $type_start, collected_name_kind::type_reference, $this->current_scope);
			if (!str_starts_with($this->text(), '$')) {
				throw $this->error('Expected field variable name');
			}
			$field->name_token_index = $this->position++;
			$this->expect(';');
			$record->fields->append($this->node(node_kind::field_declaration, $field_start, $field));
		}
		$this->expect('}');
		$node = $this->node(node_kind::struct_declaration, $start, $record);
		$this->collector->record($node, $record->name_token_index, collected_name_kind::struct_declaration, $this->current_scope);
		return $node;
	}

	/** A statement owns its semicolon; the call remains an independent expression node. */
	private function expression_statement(): ast_node
	{
		$start = $this->position;
		$statement = new expression_statement_specialization();
		$statement->expression = $this->expression();
		$statement->semicolon_token_index = $this->expect(';');
		return $this->node(node_kind::expression_statement, $start, $statement);
	}

	/** Parse ordered parameters into the function scope owned by the parsed file. */
	private function function_declaration(): ast_node
	{
		if ($this->current_scope->function_boundary) {
			throw $this->error('Nested function declarations are not implemented');
		}
		$start = $this->position;
		$formals /** hash<int> */ = [];
		if ($this->text() === 'template')
		{
			$this->position++;
			$this->expect('<');
			do
			{
				if ($this->text() === 'typename') {
					$this->position++;
				}
				if (!$this->identifier() || isset($formals[$this->text()])) {
					throw $this->error('Expected a unique template type parameter');
				}
				$formals[$this->text()] = $this->position++;
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
			$this->expect('>');
		}
		$this->expect('function');
		if (!$this->identifier() || in_array($this->text(), ['function', 'return', 'void'], true)) {
			throw $this->error('Expected function name');
		}
		$function = new function_specialization();
		$function->name_token_index = $this->position++;
		$function->template_parameters = $formals;
		if (isset($formals[$this->tokens->tokens[$function->name_token_index]->text])) {
			throw $this->error('Template parameter conflicts with function name');
		}
		$local_scope = new scope();
		$this->syntax->scopes->append($local_scope);
		$local_scope->parent = $this->current_scope;
		$local_scope->function_boundary = true;
		$local_scope->template_parameters = array_flip(array_keys($formals));
		$this->expect('(');
		if ($this->text() !== ')')
		{
			do {
				$function->parameters->append($this->parameter($local_scope));
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$this->expect(')');
		$this->expect(':');
		if (!$this->identifier() || in_array($this->text(), ['function', 'return'], true)) {
			throw $this->error('Expected return type name');
		}
		$type_start = $this->position++;
		$function->return_type = $this->node(node_kind::identifier, $type_start);
		$this->collector->record($function->return_type, $type_start, collected_name_kind::type_reference, $local_scope);
		$function->body = $this->block($local_scope);
		$node = $this->node(node_kind::function_declaration, $start, $function);
		$this->collector->record($node, $function->name_token_index, collected_name_kind::function_declaration, $this->current_scope);
		return $node;
	}

	/** Register a typed parameter as an explicit variable declaration in the body scope. */
	private function parameter(scope $scope): ast_node
	{
		$start = $this->position;
		if (!$this->identifier() || in_array($this->text(), ['function', 'return'], true)) {
			throw $this->error('Expected parameter type');
		}
		$this->position++;
		$parameter = new parameter_specialization();
		$parameter->type_syntax = $this->node(node_kind::identifier, $start);
		$this->collector->record($parameter->type_syntax, $start, collected_name_kind::type_reference, $scope);
		if ($this->text() === '&') {
			$parameter->mode = passing_mode::reference;
			$parameter->reference_token_index = $this->position++;
		}
		if (!str_starts_with($this->text(), '$')) {
			throw $this->error('Expected parameter name');
		}
		$parameter->name_token_index = $this->position++;
		$node = $this->node(node_kind::parameter_declaration, $start, $parameter);
		$this->collector->record($node, $parameter->name_token_index, collected_name_kind::variable_declaration, $scope);
		return $node;
	}

	/** Reference the selected scope; restore enclosing context even if parsing fails. */
	private function block(scope $scope): ast_node
	{
		$start = $this->expect('{');
		$body = new block_specialization();
		$body->scope = $scope;
		$enclosing = $this->current_scope;
		$this->current_scope = $scope;
		try {
			while (($this->text() !== '}') && ($this->text() !== '')) {
				$body->children->append($this->statement());
			}
			$this->expect('}');
		}
		finally {
			$this->current_scope = $enclosing;
		}
		return $this->node(node_kind::block, $start, $body);
	}

	private function identifier(): bool
	{
		return preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $this->text()) === 1;
	}

	/** Preserve the return keyword, optional expression and terminating semicolon. */
	private function return_statement(): ast_node
	{
		$start = $this->position;
		$return = new return_specialization();
		$return->keyword_token_index = $this->position++;
		if ($this->text() !== ';') {
			$return->expression = $this->expression();
		}
		$return->semicolon_token_index = $this->expect(';');
		return $this->node(node_kind::return_statement, $start, $return);
	}

	/** Explicit type syntax identifies a declaration; untyped bindings remain unresolved. */
	private function binding_statement(): ast_node
	{
		$start = $this->position;
		$binding = new binding_specialization();
		$binding->name_token_index = $this->position++;
		if (in_array($this->text(), ['[', '->'], true)) {
			$base = $this->node(node_kind::variable_reference, $start);
			$this->collector->record($base, $start, collected_name_kind::variable_reference, $this->current_scope);
			$binding->target = $this->access_suffix($base);
			$binding->classification = binding_kind::assignment;
		}

		// A named element type may have one fixed-array suffix.
		if (($binding->target === null) && !in_array($this->text(), ['return', 'function'], true) && $this->identifier())
		{
			$type_start = $this->position++;
			$type_node = $this->node(node_kind::identifier, $type_start);
			$binding->type_syntax = $type_node;
			$this->collector->record($binding->type_syntax, $type_start, collected_name_kind::type_reference, $this->current_scope);
			$binding->classification = binding_kind::declaration;
			if ($this->text() === '[') {
				$binding->type_syntax = $this->array_type($type_node);
			}
		}
		if ($this->text() === '=') {
			$binding->equals_token_index = $this->position++;
			$binding->value = $this->expression();
		}
		elseif ($binding->type_syntax === null) {
			throw $this->error('Expected type name or = after variable name');
		}

		$binding->semicolon_token_index = $this->expect(';');
		$node = $this->node(node_kind::variable_binding_statement, $start, $binding);
		$kind = $binding->classification === binding_kind::declaration ? collected_name_kind::variable_declaration : collected_name_kind::binding;
		if ($binding->target === null) {
			$this->collector->record($node, $start, $kind, $this->current_scope);
		}
		return $node;
	}

	/** Parse literals, calls and variable/index expressions; arithmetic is not supported yet. */
	private function expression(): ast_node
	{
		if ($this->text() === '[') {
			return $this->array_literal();
		}
		if ($this->identifier()) {
			return $this->call_expression();
		}
		$start = $this->position;
		$text = $this->text();
		if (str_starts_with($text, '$')) {
			$kind = node_kind::variable_reference;
		}
		elseif (($text !== '') && (strspn($text, '0123456789') === strlen($text))) {
			$kind = node_kind::integer_literal;
		}
		else {
			throw $this->error('Expected integer literal or variable reference');
		}
		$this->position++;
		$node = $this->node($kind, $start);
		if ($kind === node_kind::variable_reference) {
			$this->collector->record($node, $start, collected_name_kind::variable_reference, $this->current_scope);
		}
		return $this->access_suffix($node);
	}

	/** Preserve the element type and literal extent independently of LLVM spelling. */
	private function array_type(ast_node $element): ast_node
	{
		$this->expect('[');
		$type = new array_type_specialization();
		$type->element_type = $element;
		$type->count = $this->expression();
		if ($type->count->kind !== node_kind::integer_literal) {
			throw $this->error('Fixed array size must be a nonnegative integer literal');
		}
		$this->expect(']');
		return $this->node(node_kind::array_type, $element->token_index, $type);
	}

	/** Keep initializer elements as syntax; preparation/lowering checks their allowed forms. */
	private function array_literal(): ast_node
	{
		$start = $this->expect('[');
		$literal = new array_literal_specialization();
		if ($this->text() !== ']')
		{
			do {
				$literal->elements->append($this->expression());
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$this->expect(']');
		return $this->node(node_kind::array_literal, $start, $literal);
	}

	/** Member and index access retain a base expression so reads, writes and references share one shape. */
	private function access_suffix(ast_node $base): ast_node
	{
		while (in_array($this->text(), ['[', '->'], true))
		{
			if ($this->text() === '->')
			{
				$this->position++;
				if (!$this->identifier()) {
					throw $this->error('Expected field name');
				}
				$field = new field_access_specialization();
				$field->base = $base;
				$field->name_token_index = $this->position++;
				$base = $this->node(node_kind::field_expression, $base->token_index, $field);
				$this->collector->record($base, $field->name_token_index, collected_name_kind::field_reference, $this->current_scope);
				continue;
			}
			$this->position++;
			$access = new index_specialization();
			$access->base = $base;
			$access->index = $this->expression();
			$this->expect(']');
			$base = $this->node(node_kind::index_expression, $base->token_index, $access);
		}
		return $base;
	}

	/** Preserve argument expressions in source order and record the unresolved call target. */
	private function call_expression(): ast_node
	{
		$start = $this->position;
		$call = new call_specialization();
		$call->name_token_index = $this->position++;
		if ($this->text() === '<')
		{
			$this->position++;
			do
			{
				if (!$this->identifier()) {
					throw $this->error('Expected explicit template type argument');
				}
				$type_start = $this->position++;
				$type = $this->node(node_kind::identifier, $type_start);
				$call->template_arguments->append($type);
				$this->collector->record($type, $type_start, collected_name_kind::type_reference, $this->current_scope);
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
			$this->expect('>');
		}
		$call->left_parenthesis_token_index = $this->expect('(');
		if ($this->text() !== ')')
		{
			do {
				$call->arguments->append($this->expression());
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$call->right_parenthesis_token_index = $this->expect(')');
		$node = $this->node(node_kind::call_expression, $start, $call);
		$this->collector->record($node, $start, collected_name_kind::function_reference, $this->current_scope);
		return $node;
	}

	/** Construct a node with its directly owned specialization. */
	private function node(node_kind $kind, int $start, ?node_interface $specialization = null): ast_node
	{
		Syntax_Nodes::validate_payload($kind, $specialization);
		$node = new ast_node();
		$node->token_index = $start;
		$node->end_token_index = $this->position;
		$node->kind = $kind;
		$node->specialization = $specialization;
		return $node;
	}

	private function text(): string
	{
		return $this->tokens->tokens[$this->position]->text ?? '';
	}

	private function expect(string $text): int
	{
		if ($this->text() !== $text) {
			throw $this->error("Expected '$text'");
		}
		return $this->position++;
	}

	private function error(string $message): \RuntimeException
	{
		$offset = $this->tokens->tokens[$this->position]->offset ?? strlen($this->tokens->content);
		return new \RuntimeException("$message at {$this->tokens->file->path}: byte $offset");
	}

	/** Display parsed nodes with their token spans and original spelling. */
	private function dump_node(ast_node $node, int $depth): void
	{
		$label = $node->kind->name;
		$payload = $node->specialization;
		if ($payload instanceof binding_specialization) {
			$label .= ' (' . $payload->classification->name . ')';
		}
		$words /** vector<string> */ = [];
		for ($index = $node->token_index; $index < $node->end_token_index; $index++) {
			$words[] = $this->tokens->tokens[$index]->text;
		}
		echo str_repeat('  ', $depth) . $label . " [{$node->token_index}, {$node->end_token_index}) " . htmlspecialchars(implode(' ', $words), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";

		if ($payload instanceof block_specialization) {
			foreach ($payload->children as $child) {
				$this->dump_node($child, $depth + 1);
			}
		}
		elseif ($payload instanceof expression_statement_specialization) {
			$this->dump_node($payload->expression, $depth + 1);
		}
		elseif ($payload instanceof function_specialization) {
			foreach ($payload->parameters as $parameter) {
				$this->dump_node($parameter, $depth + 1);
			}
			$this->dump_node($payload->return_type, $depth + 1);
			$this->dump_node($payload->body, $depth + 1);
		}
		elseif ($payload instanceof parameter_specialization) {
			$this->dump_node($payload->type_syntax, $depth + 1);
		}
		elseif ($payload instanceof call_specialization) {
			foreach ($payload->arguments as $argument) {
				$this->dump_node($argument, $depth + 1);
			}
		}
		elseif ($payload instanceof struct_specialization) {
			foreach ($payload->fields as $field) {
				$this->dump_node($field, $depth + 1);
			}
		}
		elseif ($payload instanceof field_specialization) {
			$this->dump_node($payload->type_syntax, $depth + 1);
		}
		elseif ($payload instanceof field_access_specialization) {
			$this->dump_node($payload->base, $depth + 1);
		}
		elseif ($payload instanceof array_type_specialization) {
			$this->dump_node($payload->element_type, $depth + 1);
			$this->dump_node($payload->count, $depth + 1);
		}
		elseif ($payload instanceof array_literal_specialization) {
			foreach ($payload->elements as $element) {
				$this->dump_node($element, $depth + 1);
			}
		}
		elseif ($payload instanceof index_specialization) {
			$this->dump_node($payload->base, $depth + 1);
			$this->dump_node($payload->index, $depth + 1);
		}
		elseif ($payload instanceof binding_specialization)
		{
			if ($payload->target !== null) {
				$this->dump_node($payload->target, $depth + 1);
			}
			if ($payload->type_syntax !== null) {
				$this->dump_node($payload->type_syntax, $depth + 1);
			}
			if ($payload->value !== null) {
				$this->dump_node($payload->value, $depth + 1);
			}
		}
		elseif (($payload instanceof return_specialization) && ($payload->expression !== null)) {
			$this->dump_node($payload->expression, $depth + 1);
		}
	}
}
