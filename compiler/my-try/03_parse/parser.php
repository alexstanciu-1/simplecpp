<?php

/*
 * Role: parse retained tokens and collect occurrences.
 * Call map: Compiler::parse -> Parser::parse -> Parser_Run::parse -> statement/expression -> Symbol_Collector.
 */
namespace scpp\compiler;

final class Parser
{
	private token_list $tokens;
	private ?scope $target_scope = null;

	public function __construct(token_list $tokens, ?scope $target_scope = null)
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
	}

	/** Select the next input; each parse owns independent transient state. */
	public function init(token_list $tokens, ?scope $target_scope = null): void
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
	}

	public function parse(): parsed_file
	{
		return (new Parser_Run($this->tokens, $this->target_scope))->parse();
	}
}

/** One parse invocation; required fields exist before any parsing method runs. */
final class Parser_Run
{
	private token_list $tokens;
	private parsed_file $syntax;
	private scope $current_scope;
	private Symbol_Collector $collector;
	private int $position = 0;

	/** Create a parse candidate and own its root scope only when no caller scope was supplied. */
	public function __construct(token_list $tokens, ?scope $target_scope = null)
	{
		$this->tokens = $tokens;
		$this->syntax = new parsed_file();
		$this->syntax->tokens = $tokens;
		$current_scope /** scope */ = $target_scope ?? new scope();
		$this->current_scope = $current_scope;
		if ($target_scope === null) {
			$scopes /** Storage<scope> */ = $this->syntax->scopes;
			$scopes->append($this->current_scope);
		}
		$this->collector = new Symbol_Collector($tokens);
	}

	/** Publish the completed file only after successful parsing and collection. */
	public function parse(): parsed_file
	{
		$body = new block_structure();
		$children /** Storage<ast_node> */ = $body->children;
		$body->scope = $this->current_scope;
		while ($this->position < q_count($this->tokens->tokens)) {
			$children->append($this->statement());
		}
		$result = $this->syntax;
		$result->root = $this->payload_node(node_kind::file, 0, $body);
		$result->collection = $this->collector->finish($result->root);
		return $result;
	}

	/** Select a statement from its leading syntax; names are never looked up here. */
	private function statement(): ast_node
	{
		if ($this->text() === 'struct') {
			return $this->struct_declaration();
		}
		if ((($this->text() === 'function') || ($this->text() === 'template'))) {
			return $this->function_declaration();
		}
		if ($this->text() === 'return') {
			return $this->return_statement();
		}
		if (string_byte_starts_with($this->text(), '$')) {
			return $this->binding_statement();
		}
		if ($this->identifier()) {
			return $this->expression_statement();
		}
		throw new \RuntimeException($this->error_message('Expected a declaration, call or return statement'));
	}

	/** Collect a named record and retain its ordered fields; no layout work belongs in parsing. */
	private function struct_declaration(): ast_node
	{
		if ($this->current_scope->is_function()) {
			throw new \RuntimeException($this->error_message('Local struct declarations are not supported yet'));
		}
		$start = $this->expect('struct');
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected struct name'));
		}
		$record = new struct_structure();
		$fields /** Storage<ast_node> */ = $record->fields;
		$record->name_token_index = $this->position++;
		$this->expect('{');
		while ($this->text() !== '}')
		{
			$field_start = $this->position;
			if ($this->text() === 'public') {
				$this->position++;
			}
			if (!$this->identifier()) {
				throw new \RuntimeException($this->error_message('Expected field type'));
			}
			$field = new field_structure();
			$type_start = $this->position++;
			$field->type_syntax = $this->node(node_kind::identifier, $type_start);
			$this->collector->record($field->type_syntax, $type_start, collected_name_kind::type_reference, $this->current_scope);
			if (!string_byte_starts_with($this->text(), '$')) {
				throw new \RuntimeException($this->error_message('Expected field variable name'));
			}
			$field->name_token_index = $this->position++;
			$this->expect(';');
			$field_node = $this->payload_node(node_kind::field_declaration, $field_start, $field);
			$fields->append($field_node);
			$this->collector->record($field_node, $field->name_token_index, collected_name_kind::field_declaration, $this->current_scope);
		}
		$this->expect('}');
		$node = $this->payload_node(node_kind::struct_declaration, $start, $record);
		$this->collector->record($node, $record->name_token_index, collected_name_kind::struct_declaration, $this->current_scope);
		return $node;
	}

	/** A statement owns its semicolon; the call remains an independent expression node. */
	private function expression_statement(): ast_node
	{
		$start = $this->position;
		$statement = new expression_statement_structure();
		$statement->expression = $this->expression();
		$statement->semicolon_token_index = $this->expect(';');
		return $this->payload_node(node_kind::expression_statement, $start, $statement);
	}

	/** Parse ordered parameters into the function scope owned by the parsed file. */
	private function function_declaration(): ast_node
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		if ($this->current_scope->is_function()) {
			throw new \RuntimeException($this->error_message('Nested function declarations are not implemented'));
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
				$formal_name = $this->text();
				if (!$this->identifier() || isset($formals[$formal_name])) {
					throw new \RuntimeException($this->error_message('Expected a unique template type parameter'));
				}
				$formals[$formal_name] = $this->position;
				$this->position++;
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
			$this->expect('>');
		}
		$this->expect('function');
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return') || ($this->text() === 'void'))) {
			throw new \RuntimeException($this->error_message('Expected function name'));
		}
		$function = new function_structure();
		$parameters /** Storage<ast_node> */ = $function->parameters;
		$function->name_token_index = $this->position++;
		$function->template_parameters = $formals;
		$function_name = $token_rows[$function->name_token_index]->text();
		if (isset($formals[$function_name])) {
			throw new \RuntimeException($this->error_message('Template parameter conflicts with function name'));
		}
		$local_scope = new scope();
		$scopes /** Storage<scope> */ = $this->syntax->scopes;
		$scopes->append($local_scope);
		$local_scope->set_parent($this->current_scope);
		$local_scope->mark_function();
		$slots /** hash<int> */ = [];
		$slot = 0;
		foreach ($formals as $name => $token_index) {
			$slots[$name] = $slot++;
		}
		$local_scope->set_templates($slots);
		$this->expect('(');
		if ($this->text() !== ')')
		{
			do {
				$parameters->append($this->parameter($local_scope));
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$this->expect(')');
		$this->expect(':');
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return'))) {
			throw new \RuntimeException($this->error_message('Expected return type name'));
		}
		$type_start = $this->position++;
		$function->return_type = $this->node(node_kind::identifier, $type_start);
		$this->collector->record($function->return_type, $type_start, collected_name_kind::type_reference, $local_scope);
		$function->body = $this->block($local_scope);
		$node = $this->payload_node(node_kind::function_declaration, $start, $function);
		$this->collector->record($node, $function->name_token_index, collected_name_kind::function_declaration, $this->current_scope);
		return $node;
	}

	/** Register a typed parameter as an explicit variable declaration in the body scope. */
	private function parameter(scope $scope): ast_node
	{
		$start = $this->position;
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return'))) {
			throw new \RuntimeException($this->error_message('Expected parameter type'));
		}
		$this->position++;
		$parameter = new parameter_structure();
		$parameter->type_syntax = $this->node(node_kind::identifier, $start);
		$this->collector->record($parameter->type_syntax, $start, collected_name_kind::type_reference, $scope);
		if ($this->text() === '&') {
			$parameter->mode = passing_mode::reference;
			$parameter->reference_token_index = $this->position++;
		}
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected parameter name'));
		}
		$parameter->name_token_index = $this->position++;
		$node = $this->payload_node(node_kind::parameter_declaration, $start, $parameter);
		$this->collector->record($node, $parameter->name_token_index, collected_name_kind::variable_declaration, $scope);
		return $node;
	}

	/** Reference the selected scope; restore enclosing context even if parsing fails. */
	private function block(scope $scope): ast_node
	{
		$start = $this->expect('{');
		$body = new block_structure();
		$children /** Storage<ast_node> */ = $body->children;
		$body->scope = $scope;
		$enclosing = $this->current_scope;
		$this->current_scope = $scope;
		try {
			while (($this->text() !== '}') && ($this->text() !== '')) {
				$children->append($this->statement());
			}
			$this->expect('}');
		}
		finally {
			$this->current_scope = $enclosing;
		}
		return $this->payload_node(node_kind::block, $start, $body);
	}

	private function identifier(): bool
	{
		return Source_Text::identifier($this->text());
	}

	/** Preserve the return keyword, optional expression and terminating semicolon. */
	private function return_statement(): ast_node
	{
		$start = $this->position;
		$return_node = new return_structure();
		$return_node->keyword_token_index = $this->position++;
		if ($this->text() !== ';') {
			$return_node->expression = $this->expression();
		}
		$return_node->semicolon_token_index = $this->expect(';');
		return $this->payload_node(node_kind::return_statement, $start, $return_node);
	}

	/** Explicit type syntax identifies a declaration; untyped bindings remain unresolved. */
	private function binding_statement(): ast_node
	{
		$start = $this->position;
		$binding = new binding_structure();
		$binding->name_token_index = $this->position++;
		if ((($this->text() === '[') || ($this->text() === '->'))) {
			$base = $this->node(node_kind::variable_reference, $start);
			$this->collector->record($base, $start, collected_name_kind::variable_reference, $this->current_scope);
			$binding->target = $this->access_suffix($base);
			$binding->classification = binding_kind::assignment;
		}

		// A named element type may have one fixed-array suffix.
		if (($binding->target === null) && !(($this->text() === 'return') || ($this->text() === 'function')) && $this->identifier())
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
			throw new \RuntimeException($this->error_message('Expected type name or = after variable name'));
		}

		$binding->semicolon_token_index = $this->expect(';');
		$node = $this->payload_node(node_kind::variable_binding_statement, $start, $binding);
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
		$kind = node_kind::variable_reference;
		if (!string_byte_starts_with($text, '$'))
		{
			if (($text !== '') && Source_Text::digits($text)) {
				$kind = node_kind::integer_literal;
			}
			else {
				throw new \RuntimeException($this->error_message('Expected integer literal or variable reference'));
			}
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
		$type = new array_type_structure();
		$type->element_type = $element;
		$type->count = $this->expression();
		if ($type->count->kind !== node_kind::integer_literal) {
			throw new \RuntimeException($this->error_message('Fixed array size must be a nonnegative integer literal'));
		}
		$this->expect(']');
		return $this->payload_node(node_kind::array_type, (int) $element->token_index, $type);
	}

	/** Keep initializer elements as syntax; preparation/lowering checks their allowed forms. */
	private function array_literal(): ast_node
	{
		$start = $this->expect('[');
		$literal = new array_literal_structure();
		$elements /** Storage<ast_node> */ = $literal->elements;
		if ($this->text() !== ']')
		{
			do {
				$elements->append($this->expression());
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$this->expect(']');
		return $this->payload_node(node_kind::array_literal, $start, $literal);
	}

	/** Member and index access retain a base expression so reads, writes and references share one shape. */
	private function access_suffix(ast_node $base): ast_node
	{
		while ((($this->text() === '[') || ($this->text() === '->')))
		{
			if ($this->text() === '->')
			{
				$this->position++;
				if (!$this->identifier()) {
					throw new \RuntimeException($this->error_message('Expected field name'));
				}
				$field = new field_access_structure();
				$field->base = $base;
				$field->name_token_index = $this->position++;
				$base = $this->payload_node(node_kind::field_expression, (int) $base->token_index, $field);
				$this->collector->record($base, $field->name_token_index, collected_name_kind::field_reference, $this->current_scope);
				continue;
			}
			$this->position++;
			$access = new index_structure();
			$access->base = $base;
			$access->index = $this->expression();
			$this->expect(']');
			$base = $this->payload_node(node_kind::index_expression, (int) $base->token_index, $access);
		}
		return $base;
	}

	/** Preserve argument expressions in source order and record the unresolved call target. */
	private function call_expression(): ast_node
	{
		$start = $this->position;
		$call = new call_structure();
		$template_arguments /** Storage<ast_node> */ = $call->template_arguments;
		$arguments /** Storage<ast_node> */ = $call->arguments;
		$call->name_token_index = $this->position++;
		if ($this->text() === '<')
		{
			$this->position++;
			do
			{
				if (!$this->identifier()) {
					throw new \RuntimeException($this->error_message('Expected explicit template type argument'));
				}
				$type_start = $this->position++;
				$type = $this->node(node_kind::identifier, $type_start);
				$template_arguments->append($type);
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
				$arguments->append($this->expression());
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$call->right_parenthesis_token_index = $this->expect(')');
		$node = $this->payload_node(node_kind::call_expression, $start, $call);
		$this->collector->record($node, $start, collected_name_kind::function_reference, $this->current_scope);
		return $node;
	}

	/** Stabilize the concrete payload as an interface before nullable wrapping. */
	private function payload_node(node_kind $kind, int $start, node_structure $structure): ast_node
	{
		return $this->node($kind, $start, $structure);
	}

	/** Construct the concrete node, attach its extra data and publish navigation links. */
	private function node(node_kind $kind, int $start, ?node_structure $structure = null): ast_node
	{
		Syntax_Nodes::validate_payload($kind, $structure);
		$node = Syntax_Nodes::allocate($kind);
		$node->initialize($start, $this->position, $structure);
		$children /** Storage<ast_node> */ = Syntax_Nodes::child_nodes($node);
		ast_node::link_children($node, $children);
		return $node;
	}

	private function text(): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		if (!isset($token_rows[$this->position])) {
			return '';
		}
		return $token_rows[$this->position]->text();
	}

	private function expect(string $text): int
	{
		if ($this->text() !== $text) {
			throw new \RuntimeException($this->error_message(("Expected '" . $text . "'")));
		}
		return $this->position++;
	}

	/** Describe the current source position without publishing incomplete syntax. */
	private function error_message(string $message): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		$offset = string_byte_len($this->tokens->content);
		if (isset($token_rows[$this->position])) {
			$offset = $token_rows[$this->position]->offset;
		}
		return $message . " at " . $this->tokens->file->path . ": byte " . $offset;
	}
}
