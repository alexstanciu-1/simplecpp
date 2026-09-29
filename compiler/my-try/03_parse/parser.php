<?php

/*
 * Role: parse retained tokens and collect occurrences.
 * Call map: Source_Frontend::run -> Parser::parse -> Parser_Run::parse -> statement/expression -> Symbol_Collector.
 */
namespace scpp\compiler;

final class Parser
{
	private token_list $tokens;
	private ?scope $target_scope = null;
	private ?parsed_file $previous = null;
	private ?scope $global = null;
	private ?parsed_file $result = null;

	public function __construct(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null)
	{
		$this->init($tokens, $target_scope, $previous, $global);
	}

	/** Select one file update; prior declarations are mutable identities, not a snapshot. */
	public function init(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null): void
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
		$this->previous = $previous;
		$this->global = $global;
		$this->result = null;
	}

	public function parse(): parsed_file
	{
		$run = new Parser_Run($this->tokens, $this->target_scope, $this->previous, $this->global);
		$this->result = $run->result();
		return $run->parse();
	}

	/** Failed files keep partial declaration identities for the next update, but remain incomplete. */
	public function result(): ?parsed_file
	{
		return $this->result;
	}
}

/** One file worker; collector methods execute synchronously inside this parser. */
final class Parser_Run
{
	private token_list $tokens;
	private ?token_list $old_tokens = null;
	private ?Storage $old_scopes /** Storage<scope> */ = null;
	private ?scope $old_body_scope = null;
	private Storage $scopes /** Storage<scope> */;
	private scope $current_scope;
	private scope $file_scope;
	private Symbol_Collector $collector;
	private parsed_file $parsed;
	private int $position = 0;

	/** Establish the retained file and symbol inventory before any declaration can be registered. */
	public function __construct(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null)
	{
		$this->tokens = $tokens;
		$this->scopes = new Storage /** Storage<scope> */();
		if ($previous === null) {
			$file_scope = $target_scope ?? new scope();
			$root = Syntax_Nodes::make(node_kind::file, 0, 0, new block_structure($file_scope));
			$collection = new collected_file($tokens);
			$collection->root = $root;
			$this->parsed = new parsed_file($tokens, $root, $collection, $this->scopes);
		}
		else
		{
			$this->parsed = $previous;
			$this->old_scopes = $previous->scopes;
			$this->old_body_scope = $previous->body_scope;
			$file_scope = $previous->root_scope();
			if ($previous->complete && $previous->collection->parse_complete) {
				$this->old_tokens = $previous->tokens;
			}
		}
		$this->file_scope = $file_scope;
		if ($global !== null) {
			$file_scope->set_parent($global);
			$file_scope->set_publication($global);
		}
		$this->current_scope = new scope();
		$this->current_scope->set_parent($file_scope);
		$scopes /** Storage<scope> */ = $this->scopes;
		$scopes->append($file_scope);
		$scopes->append($this->current_scope);
		$this->parsed->complete = false;
		$this->parsed->collection->parse_complete = false;
		$this->parsed->body_scope = $this->current_scope;
		$this->parsed->tokens = $tokens;
		$this->parsed->scopes = $this->scopes;
		$collection = $this->parsed->collection;
		$revision = (int)$collection->revision + 1;
		if ($revision > 4294967295) {
			$entries /** Storage<collected_name> */ = $collection->entries;
			foreach ($entries as $entry) {
				$entry->revision = 0;
			}
			$revision = 1;
		}
		$this->collector = new Symbol_Collector($collection, $tokens, $file_scope, $global, $revision);
	}

	public function result(): parsed_file
	{
		return $this->parsed;
	}

	/** Compare executable statements in order while pulling retained declarations into the new tree. */
	public function parse(): parsed_file
	{
		$root = $this->parsed->root;
		$old_cursor = $root->first_child();
		$children /** Storage<ast_node> */ = new Storage();
		$body_changed = $this->old_tokens === null;
		while ($this->position < q_count($this->tokens->tokens))
		{
			$start = $this->position;
			$node = $this->statement();
			$children->append($node);
			if (($node->kind() === node_kind::function_declaration) || ($node->kind() === node_kind::struct_declaration)) {
				continue;
			}
			$old_cursor = $this->next_executable($old_cursor);
			if ($old_cursor === null) {
				$body_changed = true;
			}
			else {
				$old /** ast_node */ = $old_cursor;
				if (!$this->same_tokens((int)$old->token_index, (int)$old->end_token_index, $start, $this->position)) {
					$body_changed = true;
				}
				$old_cursor = $old->next();
			}
		}
		if ($this->next_executable($old_cursor) !== null) {
			$body_changed = true;
		}
		if (!$body_changed) {
			$children = $this->retain_file_body($root, $children);
		}
		elseif ($this->parsed->collection->body_preparation !== null) {
			$this->parsed->collection->body_preparation->state = preparation_state::pending;
			$this->parsed->collection->body_preparation->change_status = change_state::changed;
		}
		$root->detach_children();
		$structure = object_cast($root->payload(), block_structure::class);
		$structure->children = $children;
		$root->end_token_index = $this->position;
		ast_node::link_children($root, $children);
		$this->collector->finish($root);
		$this->parsed->body_changed = $body_changed;
		$this->parsed->complete = true;
		$this->parsed->collection->parse_complete = true;
		return $this->parsed;
	}

	/** Match braces only to delimit comparison; changed bodies still go through normal parsing. */
	private function body_end(): int
	{
		if ($this->text() !== '{') {
			return $this->position;
		}
		$depth = 0;
		$rows /** Storage<token> */ = $this->tokens->tokens;
		for ($index = $this->position; $index < q_count($rows); $index++)
		{
			$text = $rows[$index]->text();
			if ($text === '{') {
				$depth++;
			}
			elseif ($text === '}') {
				$depth = $depth - 1;
				if ($depth === 0) {
					return $index + 1;
				}
			}
		}
		return q_count($rows);
	}

	/** Reuse the entire file executable body only when every statement matched. */
	private function retain_file_body(ast_node $root, Storage $children /** Storage<ast_node> */): Storage /** Storage<ast_node> */
	{
		$result /** Storage<ast_node> */ = new Storage();
		$cursor = $root->first_child();
		foreach ($children as $node)
		{
			if (($node->kind() === node_kind::function_declaration) || ($node->kind() === node_kind::struct_declaration)) {
				$result->append($node);
				continue;
			}
			$old = object_cast($this->next_executable($cursor), ast_node::class);
			$cursor = $old->next();
			$this->retain_tree($node, 0, true);
			$this->retain_tree($old, (int)$node->token_index - (int)$old->token_index, false);
			$result->append($old);
		}
		if ($this->old_body_scope !== null) {
			$scope /** scope */ = $this->old_body_scope;
			$scopes /** Storage<scope> */ = $this->scopes;
			$scopes->append($scope);
			$this->parsed->body_scope = $scope;
		}
		return $result;
	}

	/** Relocate kept syntax/occurrences together, or retire the temporary replacement's occurrences. */
	private function retain_tree(ast_node $node, int $delta, bool $discard): void
	{
		$entry = $node->payload()->optional_occurrence();
		if ($entry !== null)
		{
			$occurrence /** collected_name */ = $entry;
			if ($discard) {
				$occurrence->revision = 0;
			}
			else {
				$this->collector->retain($occurrence, $delta);
			}
		}
		if (!$discard) {
			$node->token_index = $node->token_index + $delta;
			$node->end_token_index = $node->end_token_index + $delta;
			$node->payload()->shift_tokens($delta);
		}
		$child = $node->first_child();
		while ($child !== null) {
			$current /** ast_node */ = $child;
			$this->retain_tree($current, $delta, $discard);
			$child = $current->next();
		}
	}

	/** Declarations do not participate in the executable-body order comparison. */
	private function next_executable(?ast_node $cursor): ?ast_node
	{
		while ($cursor !== null) {
			$node /** ast_node */ = $cursor;
			if (($node->kind() !== node_kind::function_declaration) && ($node->kind() !== node_kind::struct_declaration)) {
				return $node;
			}
			$cursor = $node->next();
		}
		return null;
	}

	/** Existing half-open token bounds identify the exact body bytes, including internal whitespace. */
	private function same_body_text(int $old_start, int $old_end, int $start, int $end): bool
	{
		if (($this->old_tokens === null) || ($old_start < 0)) {
			return false;
		}
		if (($old_end <= $old_start) || ($end <= $start)) {
			return false;
		}
		$old /** token_list */ = $this->old_tokens;
		$previous /** Storage<token> */ = $old->tokens;
		$current /** Storage<token> */ = $this->tokens->tokens;
		$old_first = $previous[$old_start];
		$old_last = $previous[$old_end - 1];
		$first = $current[$start];
		$last = $current[$end - 1];
		$old_length = (int)$old_last->offset + (int)$old_last->length - (int)$old_first->offset;
		$length = (int)$last->offset + (int)$last->length - (int)$first->offset;
		if ($old_length !== $length) {
			return false;
		}
		return string_byte_slice($old->content, (int)$old_first->offset, $old_length) === string_byte_slice($this->tokens->content, (int)$first->offset, $length);
	}

	/** Token spellings preserve literal accuracy and ignore source offset/whitespace changes. */
	private function same_tokens(int $old_start, int $old_end, int $start, int $end): bool
	{
		if (($this->old_tokens === null) || ($old_start < 0)) {
			return false;
		}
		if (($old_end - $old_start) !== ($end - $start)) {
			return false;
		}
		$old /** token_list */ = $this->old_tokens;
		while ($start < $end) {
			if ($old->text_at($old_start) !== $this->tokens->text_at($start)) {
				return false;
			}
			$old_start++;
			$start++;
		}
		return true;
	}

	/** Refresh spans and direct links without replacing a matched declaration or specialization. */
	private function finish_declaration(ast_node $node, int $start, bool $same): ast_node
	{
		$entry = $node->payload()->occurrence();
		if (!$same && ($entry->change_status !== change_state::added)) {
			$entry->change_status = change_state::changed;
		}
		$node->token_index = $start;
		$node->end_token_index = $this->position;
		if (!$same) {
			if ($entry->preparation !== null) {
				$entry->preparation->state = preparation_state::pending;
				$entry->preparation->change_status = change_state::changed;
			}
		}
		$node->detach_children();
		$children /** Storage<ast_node> */ = new Storage();
		$node->payload()->append_children($children);
		ast_node::link_children($node, $children);
		return $node;
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

	/** Register the record immediately, then reconcile fields within its retained member scope. */
	private function struct_declaration(): ast_node
	{
		if ($this->current_scope->is_function()) {
			throw new \RuntimeException($this->error_message('Local struct declarations are not supported yet'));
		}
		$start = $this->expect('struct');
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected struct name'));
		}
		$name_index = $this->position++;
		$name = $this->tokens->text_at($name_index);
		$previous = $this->collector->previous($this->file_scope, $name, collected_name_kind::struct_declaration);
		if ($previous === null) {
			$node = new ast_node(node_kind::struct_declaration, $start, $start, new struct_structure());
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$old_start = (int)$node->token_index;
		if ($previous !== null) {
			if ($node->payload()->occurrence()->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = (int)$node->end_token_index;
		$record = Syntax_Nodes::struct_data($node);
		$record->name_token_index = $name_index;
		$this->collector->declaration($node, $name_index, collected_name_kind::struct_declaration, $this->file_scope, $name, $previous !== null);
		$record->member_scope->set_parent($this->file_scope);
		$fields /** Storage<ast_node> */ = new Storage();
		$this->expect('{');
		while ($this->text() !== '}') {
			$fields->append($this->field_declaration($record->member_scope));
		}
		$this->expect('}');
		$record->fields = $fields;
		return $this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
	}

	/** Fields match by their own spelling inside the structure, never by a project-wide name. */
	private function field_declaration(scope $scope): ast_node
	{
		$start = $this->position;
		if ($this->text() === 'public') {
			$this->position++;
		}
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected field type'));
		}
		$type_start = $this->position++;
		$type = $this->node(node_kind::identifier, $type_start);
		$this->record_name($type, $type_start, collected_name_kind::type_reference, $scope);
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected field variable name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous($scope, $name, collected_name_kind::field_declaration);
		if ($previous === null) {
			$node = new ast_node(node_kind::field_declaration, $start, $start, new field_structure($type));
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$old_start = (int)$node->token_index;
		if ($previous !== null) {
			if ($node->payload()->occurrence()->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = (int)$node->end_token_index;
		$field = object_cast($node->payload(), field_structure::class);
		$field->type_syntax = $type;
		$field->name_token_index = $name_index;
		$this->collector->declaration($node, $name_index, collected_name_kind::field_declaration, $scope, $name, $previous !== null);
		$this->expect(';');
		return $this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
	}

	/** A statement owns its semicolon; the call remains an independent expression node. */
	private function expression_statement(): ast_node
	{
		$start = $this->position;
		$statement = new expression_statement_structure($this->expression());
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
		$parameters /** Storage<ast_node> */ = new Storage();
		$name_token_index = $this->position++;
		$function_name = $token_rows[$name_token_index]->text();
		if (isset($formals[$function_name])) {
			throw new \RuntimeException($this->error_message('Template parameter conflicts with function name'));
		}

		$previous = $this->collector->previous($this->file_scope, $function_name, collected_name_kind::function_declaration);
		if ($previous === null) {
			$node = new ast_node(node_kind::function_declaration, $start, $start, new function_structure());
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$function = Syntax_Nodes::function_data($node);
		$old_start = (int)$node->token_index;
		if ($previous !== null) {
			if ($node->payload()->occurrence()->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_body_start = $old_start;
		$old_body_end = $old_start;
		if ($previous !== null) {
			if (($this->old_tokens !== null) && ($old_start >= 0)) {
				$old_body_start = (int)$function->body->token_index;
				$old_body_end = (int)$function->body->end_token_index;
			}
		}
		$this->collector->declaration($node, $name_token_index, collected_name_kind::function_declaration, $this->file_scope, $function_name, $previous !== null);
		$local_scope = $function->signature_scope;
		$local_scope->set_parent($this->file_scope);
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
		$return_type = $this->node(node_kind::identifier, $type_start);
		$this->record_name($return_type, $type_start, collected_name_kind::type_reference, $local_scope);

		$body_start = $this->position;
		$body_end = $this->body_end();
		$function->body_changed = !$this->same_body_text($old_body_start, $old_body_end, $body_start, $body_end);
		$scopes /** Storage<scope> */ = $this->scopes;
		if (!$function->body_changed) {
			$body = $function->body;
			$body_scope = object_cast($body->payload(), block_structure::class)->lexical_scope();
			$scopes->append($body_scope);
			$this->retain_tree($body, $body_start - $old_body_start, false);
			$this->position = $body_end;
		}
		else
		{
			$body_scope = new scope();
			$body_scope->set_parent($local_scope);
			$body_scope->mark_function();
			$scopes->append($body_scope);
			$body = $this->block($body_scope);
			if ($function->body_preparation !== null) {
				$function->body_preparation->state = preparation_state::pending;
				$function->body_preparation->change_status = change_state::changed;
			}
		}

		$function->return_type = $return_type;
		$function->body = $body;
		$function->parameters = $parameters;
		$function->name_token_index = $name_token_index;
		$function->template_parameters = $formals;
		return $this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_body_start, $start, $body_start));
	}

	/** Register a typed parameter as an explicit variable declaration in the body scope. */
	private function parameter(scope $scope): ast_node
	{
		$start = $this->position;
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return'))) {
			throw new \RuntimeException($this->error_message('Expected parameter type'));
		}
		$this->position++;
		$type = $this->node(node_kind::identifier, $start);
		$this->record_name($type, $start, collected_name_kind::type_reference, $scope);
		$mode = passing_mode::value;
		$reference_index /** nullable<int> */ = null;
		if ($this->text() === '&') {
			$mode = passing_mode::reference;
			$reference_index = $this->position++;
		}
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected parameter name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous($scope, $name, collected_name_kind::variable_declaration);
		if ($previous === null) {
			$node = new ast_node(node_kind::parameter_declaration, $start, $start, new parameter_structure($type));
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$old_start = (int)$node->token_index;
		if ($previous !== null) {
			if ($node->payload()->occurrence()->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = (int)$node->end_token_index;
		$parameter = object_cast($node->payload(), parameter_structure::class);
		$parameter->type_syntax = $type;
		$parameter->mode = $mode;
		$parameter->reference_token_index = $reference_index;
		$parameter->name_token_index = $name_index;
		$this->collector->declaration($node, $name_index, collected_name_kind::variable_declaration, $scope, $name, $previous !== null);
		return $this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
	}

	/** Reference the selected scope; restore enclosing context even if parsing fails. */
	private function block(scope $scope): ast_node
	{
		$start = $this->expect('{');
		$body = new block_structure($scope);
		$children /** Storage<ast_node> */ = $body->children;
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
			$this->record_name($base, $start, collected_name_kind::variable_reference, $this->current_scope);
			$binding->target = $this->access_suffix($base);
			$binding->syntax_kind = binding_kind::assignment;
		}

		// A named element type may have one fixed-array suffix.
		if (($binding->target === null) && !(($this->text() === 'return') || ($this->text() === 'function')) && $this->identifier())
		{
			$type_start = $this->position++;
			$type_node = $this->node(node_kind::identifier, $type_start);
			$binding->type_syntax = $type_node;
			$this->record_name($binding->type_syntax, $type_start, collected_name_kind::type_reference, $this->current_scope);
			$binding->syntax_kind = binding_kind::declaration;
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
		$kind = $binding->syntax_kind === binding_kind::declaration ? collected_name_kind::variable_declaration : collected_name_kind::binding;
		if ($binding->target === null) {
			$this->record_name($node, $start, $kind, $this->current_scope);
		}
		return $node;
	}

	/** Parse literals, calls and variable/index expressions; arithmetic is not supported yet. */
	private function expression(): ast_node
	{
		if (($this->text() === 'true') || ($this->text() === 'false')) {
			$start = $this->position;
			$literal = new boolean_literal_structure($this->text() === 'true');
			$this->position++;
			return $this->payload_node(node_kind::boolean_literal, $start, $literal);
		}
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
			elseif (Source_Text::floating($text)) {
				$kind = node_kind::float_literal;
			}
			else {
				throw new \RuntimeException($this->error_message('Expected scalar literal or variable reference'));
			}
		}
		$this->position++;
		$node = $this->node($kind, $start);
		if ($kind === node_kind::variable_reference) {
			$this->record_name($node, $start, collected_name_kind::variable_reference, $this->current_scope);
		}
		return $this->access_suffix($node);
	}

	/** Preserve the element type and literal extent independently of LLVM spelling. */
	private function array_type(ast_node $element): ast_node
	{
		$this->expect('[');
		$type = new array_type_structure($element, $this->expression());
		if ($type->count->kind() !== node_kind::integer_literal) {
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
				$field = new field_access_structure($base);
				$field->name_token_index = $this->position++;
				$base = $this->payload_node(node_kind::field_expression, (int) $base->token_index, $field);
				$this->record_name($base, $field->name_token_index, collected_name_kind::field_reference, $this->current_scope);
				continue;
			}
			$this->position++;
			$access = new index_structure($base, $this->expression());
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
				$this->record_name($type, $type_start, collected_name_kind::type_reference, $this->current_scope);
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
		$this->record_name($node, $start, collected_name_kind::function_reference, $this->current_scope);
		return $node;
	}

	/** Keep the concrete specialization behind the common factory parameter type. */
	private function payload_node(node_kind $kind, int $start, node_structure $structure): ast_node
	{
		return $this->node($kind, $start, $structure);
	}

	/** Construct the concrete node, attach its extra data and publish navigation links. */
	private function node(node_kind $kind, int $start, ?node_structure $structure = null): ast_node
	{
		return Syntax_Nodes::make($kind, $start, $this->position, $structure);
	}

	/** Normalize PHS name spelling before entering the shared occurrence model. */
	private function record_name(ast_node $node, int $index, collected_name_kind $kind, scope $scope): int
	{
		$tokens /** Storage<token> */ = $this->tokens->tokens;
		$text = $tokens[$index]->text();
		$name = string_byte_starts_with($text, '$') ? string_byte_slice($text, 1, string_byte_len($text) - 1) : $text;
		return $this->collector->record($node, $index, $kind, $scope, $name);
	}

	private function name_at(int $index): string
	{
		$text = $this->tokens->text_at($index);
		return string_byte_starts_with($text, '$') ? string_byte_slice($text, 1, string_byte_len($text) - 1) : $text;
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
