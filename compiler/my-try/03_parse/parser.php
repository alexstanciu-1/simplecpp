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
			$root = new file_node($file_scope);
			$root->set_span(0, 0);
			$root->body = new function_body_node($file_scope);
			$root->body->set_span(0, 0);
			$collection = new collected_file($tokens);
			$collection->root = $root;
			$this->parsed = new parsed_file($tokens, $root, $collection, $this->scopes);
		}
		else
		{
			$this->parsed = $previous;
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
		$old_body = $root->body;
		$old_statements /** Storage<statement_node> */ = $old_body->statements;
		$body = new function_body_node($this->current_scope);
		$statements /** Storage<statement_node> */ = $body->statements;
		$declarations /** Storage<declaration_node> */ = new Storage();
		$body_changed = $this->old_tokens === null;
		$index = 0;
		while ($this->position < q_count($this->tokens->tokens))
		{
			$start = $this->position;
			$node = $this->statement();
			if ($node instanceof declaration_node) {
				$declarations->append(object_cast($node, declaration_node::class));
				continue;
			}
			$statement = object_cast($node, statement_node::class);
			$statements->append($statement);
			if (!isset($old_statements[$index])) {
				$body_changed = true;
			}
			else {
				$old = $old_statements[$index];
				if (!$this->same_tokens($old->start_token(), $old->end_token(), $start, $this->position)) {
					$body_changed = true;
				}
			}
			$index++;
		}
		if ($index !== q_count($old_statements)) {
			$body_changed = true;
		}
		if (!$body_changed) {
			foreach ($statements as $index => $temporary) {
				$old = $old_statements[$index];
				$this->retain_tree($temporary, 0, true);
				$this->retain_tree($old, $temporary->start_token() - $old->start_token(), false);
			}
			$body = $old_body;
			$scopes /** Storage<scope> */ = $this->scopes;
			$scopes->append($body->local_scope());
		}
		else {
			$this->transfer_body_work($old_body, $body);
		}
		$body->syntax_changed = $body_changed;
		$body->set_span(0, $this->position);
		$root->body = $body;
		$root->declarations = $declarations;
		$root->set_span(0, $this->position);
		Syntax_Attachment::publish($body);
		Syntax_Attachment::publish($root);
		$this->collector->finish($root);
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
	private function transfer_body_work(function_body_node $old, function_body_node $body): void
	{
		$work = $old->detach_work();
		if ($work !== null) {
			$work->state = preparation_state::pending;
			$work->change_status = change_state::changed;
			$body->attach_work($work);
		}
		$old->set_inspection_parent(null);
	}

	/** Relocate kept syntax/occurrences together, or retire the temporary replacement's occurrences. */
	private function retain_tree(ast_node $node, int $delta, bool $discard): void
	{
		$node->maintain(new Syntax_Relocation($this->collector, $delta, $discard));
	}

	/** Declarations do not participate in the executable-body order comparison. */


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
	private function finish_declaration(ast_node $node, int $start, bool $same): void
	{
		$entry = object_cast($node->optional_occurrence(), collected_name::class);
		if (!$same && ($entry->change_status !== change_state::added)) {
			$entry->change_status = change_state::changed;
		}
		$node->set_span($start, $this->position);
		if (!$same) {
			if ($entry->preparation !== null) {
				$entry->preparation->state = preparation_state::pending;
				$entry->preparation->change_status = change_state::changed;
			}
		}
		Syntax_Attachment::publish($node);
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
	private function struct_declaration(): struct_node
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
			$node = new struct_node();
			$node->set_span($start, $start);
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$old_start = $node->start_token();
		if ($previous !== null) {
			if (object_cast($node->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $node->end_token();
		$record = object_cast($node, struct_node::class);
		$record->name = $name;
		$this->collector->declaration($node, $name_index, collected_name_kind::struct_declaration, $this->file_scope, $name, $previous !== null);
		$record->member_scope()->set_parent($this->file_scope);
		$fields /** Storage<field_node> */ = new Storage();
		$this->expect('{');
		while ($this->text() !== '}') {
			$fields->append($this->field_declaration($record->member_scope()));
		}
		$this->expect('}');
		$record->fields = $fields;
		$this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
		return $record;
	}

	/** Fields match by their own spelling inside the structure, never by a project-wide name. */
	private function field_declaration(scope $scope): field_node
	{
		$start = $this->position;
		if ($this->text() === 'public') {
			$this->position++;
		}
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected field type'));
		}
		$type_start = $this->position++;
		$type = $this->named_type($type_start);
		$this->record_name($type, $type_start, collected_name_kind::type_reference, $scope);
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected field variable name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous($scope, $name, collected_name_kind::field_declaration);
		if ($previous === null) {
			$node = new field_node();
			$node->set_span($start, $start);
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$old_start = $node->start_token();
		if ($previous !== null) {
			if (object_cast($node->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $node->end_token();
		$field = object_cast($node, field_node::class);
		$field->type_syntax = $type;
		$field->name = $name;
		$this->collector->declaration($node, $name_index, collected_name_kind::field_declaration, $scope, $name, $previous !== null);
		$this->expect(';');
		$this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
		return $field;
	}

	/** A statement owns its semicolon; the call remains an independent expression node. */
	private function expression_statement(): expression_statement_node
	{
		$start = $this->position;
		$statement = new expression_statement_node();
		$statement->expression = $this->expression();
		$this->expect(';');
		$this->finish_node($statement, $start);
		return $statement;
	}

	/** Parse ordered parameters into the function scope owned by the parsed file. */
	private function function_declaration(): function_node
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
		$parameters /** Storage<parameter_node> */ = new Storage();
		$name_token_index = $this->position++;
		$function_name = $token_rows[$name_token_index]->text();
		if (isset($formals[$function_name])) {
			throw new \RuntimeException($this->error_message('Template parameter conflicts with function name'));
		}

		$previous = $this->collector->previous($this->file_scope, $function_name, collected_name_kind::function_declaration);
		if ($previous === null) {
			$node = new function_node();
			$node->set_span($start, $start);
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$function = object_cast($node, function_node::class);
		$old_start = $node->start_token();
		if ($previous !== null) {
			if (object_cast($node->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_body_start = $old_start;
		$old_body_end = $old_start;
		if ($previous !== null) {
			if (($this->old_tokens !== null) && ($old_start >= 0)) {
				$old_body_start = $function->body->start_token();
				$old_body_end = $function->body->end_token();
			}
		}
		$this->collector->declaration($node, $name_token_index, collected_name_kind::function_declaration, $this->file_scope, $function_name, $previous !== null);
		$local_scope = $function->signature_scope();
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
		$return_type = $this->named_type($type_start);
		$this->record_name($return_type, $type_start, collected_name_kind::type_reference, $local_scope);

		$body_start = $this->position;
		$body_end = $this->body_end();
		$body_changed = !$this->same_body_text($old_body_start, $old_body_end, $body_start, $body_end);
		$scopes /** Storage<scope> */ = $this->scopes;
		if (!$body_changed) {
			$body = $function->body;
			$body_scope = $body->local_scope();
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
			if ($previous !== null && isset($function->body)) {
				$this->transfer_body_work($function->body, $body);
			}
		}

		$function->return_type = $return_type;
		$body->syntax_changed = $body_changed;
		$function->body = $body;
		$function->parameters = $parameters;
		$function->name = $function_name;
		$function->template_parameters = $formals;
		$this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_body_start, $start, $body_start));
		return $function;
	}

	/** Register a typed parameter as an explicit variable declaration in the body scope. */
	private function parameter(scope $scope): parameter_node
	{
		$start = $this->position;
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return'))) {
			throw new \RuntimeException($this->error_message('Expected parameter type'));
		}
		$this->position++;
		$type = $this->named_type($start);
		$this->record_name($type, $start, collected_name_kind::type_reference, $scope);
		$mode = passing_mode::value;
		if ($this->text() === '&') {
			$mode = passing_mode::reference;
			$this->position++;
		}
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected parameter name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous($scope, $name, collected_name_kind::variable_declaration);
		if ($previous === null) {
			$node = new parameter_node();
			$node->set_span($start, $start);
		}
		else {
			$node /** ast_node */ = $previous;
		}
		$old_start = $node->start_token();
		if ($previous !== null) {
			if (object_cast($node->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $node->end_token();
		$parameter = object_cast($node, parameter_node::class);
		$parameter->type_syntax = $type;
		$parameter->mode = $mode;

		$parameter->name = $name;
		$this->collector->declaration($node, $name_index, collected_name_kind::variable_declaration, $scope, $name, $previous !== null);
		$this->finish_declaration($node, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
		return $parameter;
	}

	/** Reference the selected scope; restore enclosing context even if parsing fails. */
	private function block(scope $scope): function_body_node
	{
		$start = $this->expect('{');
		$body = new function_body_node($scope);
		$children /** Storage<statement_node> */ = $body->statements;
		$enclosing = $this->current_scope;
		$this->current_scope = $scope;
		try {
			while (($this->text() !== '}') && ($this->text() !== '')) {
				$statement = $this->statement();
				$children->append(object_cast($statement, statement_node::class));
			}
			$this->expect('}');
		}
		finally {
			$this->current_scope = $enclosing;
		}
		$this->finish_node($body, $start);
		return $body;
	}

	private function identifier(): bool
	{
		return Source_Text::identifier($this->text());
	}

	/** Preserve the return keyword, optional expression and terminating semicolon. */
	private function return_statement(): return_node
	{
		$start = $this->position;
		$return_node = new return_node();
		$this->position++;
		if ($this->text() !== ';') {
			$return_node->expression = $this->expression();
		}
		$this->expect(';');
		$this->finish_node($return_node, $start);
		return $return_node;
	}

	/** Explicit type syntax declares a variable; plain writes always use assignment expressions. */
	private function binding_statement(): statement_node
	{
		$start = $this->position++;
		$name = $this->name_at($start);
		if ($this->identifier() && ($this->text() !== 'return') && ($this->text() !== 'function'))
		{
			$declaration = new variable_declaration_node();
			$declaration->name = $name;
			$type_start = $this->position++;
			$type = $this->named_type($type_start);
			$this->record_name($type, $type_start, collected_name_kind::type_reference, $this->current_scope);
			$declaration->type_syntax = $this->text() === '[' ? $this->array_type($type) : $type;
			if ($this->text() === '=') {
				$this->position++;
				$declaration->initializer = $this->expression();
			}
			$this->expect(';');
			$this->finish_node($declaration, $start);
			$this->record_name($declaration, $start, collected_name_kind::variable_declaration, $this->current_scope);
			return $declaration;
		}
		$target = $this->variable_reference($start);
		$target->name = $name;
		$assignment = new assignment_expression_node();
		if (($this->text() === '[') || ($this->text() === '->')) {
			$this->record_name($target, $start, collected_name_kind::variable_reference, $this->current_scope);
			$assignment->target = object_cast($this->access_suffix($target), assignable_expression_node::class);
		}
		else {
			$this->record_name($target, $start, collected_name_kind::binding, $this->current_scope);
			$assignment->target = $target;
		}
		if ($this->text() !== '=') {
			throw new \RuntimeException($this->error_message('Expected type name or = after variable name'));
		}
		$this->position++;
		$assignment->value = $this->expression();
		$this->finish_node($assignment, $start);
		$this->expect(';');
		$statement = new expression_statement_node();
		$statement->expression = $assignment;
		$this->finish_node($statement, $start);
		return $statement;
	}

	/** Parse literals, calls and variable/index expressions; arithmetic is not supported yet. */
	private function expression(): expression_node
	{
		if (($this->text() === 'true') || ($this->text() === 'false')) {
			$start = $this->position;
			$literal = new boolean_literal_node();
			$literal->value = $this->text() === 'true';
			$this->position++;
			$this->finish_node($literal, $start);
		return $literal;
		}
		if ($this->text() === '[') {
			return $this->array_literal();
		}
		if ($this->identifier()) {
			return $this->call_expression();
		}
		$start = $this->position;
		$text = $this->text();
		$this->position++;
		$node /** expression_node */;
		if (string_byte_starts_with($text, '$')) {
			$node = $this->variable_reference($start);
			$this->record_name($node, $start, collected_name_kind::variable_reference, $this->current_scope);
		}
		elseif (($text !== '') && Source_Text::digits($text)) {
			$node = new integer_literal_node();
			$this->finish_node($node, $start);
		}
		elseif (Source_Text::floating($text)) {
			$node = new float_literal_node();
			$this->finish_node($node, $start);
		}
		else {
			throw new \RuntimeException($this->error_message('Expected scalar literal or variable reference'));
		}
		return $this->access_suffix($node);
	}

	/** Preserve the element type and literal extent independently of LLVM spelling. */
	private function array_type(type_node $element): array_type_node
	{
		$this->expect('[');
		$type = new array_type_node();
		$type->element_type = $element;
		$type->count = $this->expression();
		if ($type->count->kind() !== node_kind::integer_literal) {
			throw new \RuntimeException($this->error_message('Fixed array size must be a nonnegative integer literal'));
		}
		$this->expect(']');
		$this->finish_node($type, $element->start_token());
		return $type;
	}

	/** Keep initializer elements as syntax; preparation/lowering checks their allowed forms. */
	private function array_literal(): array_literal_node
	{
		$start = $this->expect('[');
		$literal = new array_literal_node();
		$elements /** Storage<expression_node> */ = $literal->elements;
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
		$this->finish_node($literal, $start);
		return $literal;
	}

	/** Member and index access retain a base expression so reads, writes and references share one shape. */
	private function access_suffix(expression_node $base): expression_node
	{
		while ((($this->text() === '[') || ($this->text() === '->')))
		{
			if ($this->text() === '->')
			{
				$this->position++;
				if (!$this->identifier()) {
					throw new \RuntimeException($this->error_message('Expected field name'));
				}
				$field = new field_access_node();
				$field->base = $base;
				$field_index = $this->position++;
				$field->name = $this->name_at($field_index);
				$this->finish_node($field, $base->start_token());
				$base = $field;
				$this->record_name($base, $field_index, collected_name_kind::field_reference, $this->current_scope);
				continue;
			}
			$this->position++;
			$access = new index_node();
			$access->base = $base;
			$access->index = $this->expression();
			$this->expect(']');
			$this->finish_node($access, $base->start_token());
			$base = $access;
		}
		return $base;
	}

	/** Preserve argument expressions in source order and record the unresolved call target. */
	private function call_expression(): call_node
	{
		$start = $this->position;
		$call = new call_node();
		$template_arguments /** Storage<named_type_node> */ = $call->template_arguments;
		$arguments /** Storage<expression_node> */ = $call->arguments;
		$call->name = $this->name_at($this->position++);
		if ($this->text() === '<')
		{
			$this->position++;
			do
			{
				if (!$this->identifier()) {
					throw new \RuntimeException($this->error_message('Expected explicit template type argument'));
				}
				$type_start = $this->position++;
				$type = $this->named_type($type_start);
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
		$this->expect('(');
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
		$this->expect(')');
		$this->finish_node($call, $start);
		$this->record_name($call, $start, collected_name_kind::function_reference, $this->current_scope);
		return $call;
	}

	/** Complete source provenance and inspection links without erasing concrete types. */
	private function finish_node(ast_node $node, int $start): void
	{
		$node->set_span($start, $this->position);
		Syntax_Attachment::publish($node);
	}

	/** Type names retain spelling independently of token generations. */
	private function named_type(int $start): named_type_node
	{
		$node = new named_type_node();
		$node->name = $this->name_at($start);
		$this->finish_node($node, $start);
		return $node;
	}

	/** A variable occurrence is classified as a read or write by its parser context. */
	private function variable_reference(int $start): variable_reference_node
	{
		$node = new variable_reference_node();
		$node->name = $this->name_at($start);
		$this->finish_node($node, $start);
		return $node;
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
