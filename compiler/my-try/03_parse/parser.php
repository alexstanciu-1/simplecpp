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
	private ?parsed_file $parsed_result = null;

	public function __construct(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null)
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
		$this->previous = $previous;
		$this->global = $global;
	}

	/** Select one file update; prior declarations are mutable identities, not a snapshot. */
	public function init(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null): void
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
		$this->previous = $previous;
		$this->global = $global;
		$this->parsed_result = null;
	}

	public function parse(): parsed_file
	{
		$run = new Parser_Run($this->tokens, $this->target_scope, $this->previous, $this->global);
		$this->parsed_result = $run->result();
		return $run->parse();
	}

	/** Failed files keep partial declaration identities for the next update, but remain incomplete. */
	public function result(): ?parsed_file
	{
		return $this->parsed_result;
	}
}

/** One file worker; collector methods execute synchronously inside this parser. */
final class Parser_Run
{
	private token_list $tokens;
	private bool $reuse_previous = false;
	private Storage $scopes /** Storage<scope> */;
	private scope $current_scope;
	private scope $file_scope;
	private Symbol_Collector $collector;
	private parsed_file $parsed;
	private int $position = 0;

	/** Establish the retained file and symbol inventory before any declaration can be registered. */
	public function __construct(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null)
	{
		if ($previous !== null) {
			if (($tokens !== $previous->tokens) && ($tokens->first_token === 0) && ($tokens->content_offset === 0)) {
				Token_Cleanup::file($previous);
				Token_Buffer::append($tokens, $previous->tokens);
			}
		}
		$tokens->retained_ranges = new Storage /** Storage<retained_token_range> */();
		$this->tokens = $tokens;
		$this->position = $tokens->first_token;
		$this->scopes = new Storage /** Storage<scope> */();
		$file_scope /** scope */ = $previous !== null ? $previous->root_scope() : ($target_scope ?? new scope());
		if ($previous === null) {
			$root = new file_node($file_scope);
			$root->set_span(0, 0);
			$root->body = new function_body_node($file_scope);
			$root->body->set_span(0, 0);
			$new_collection = new collected_file($tokens);
			$new_collection->root = $root;
			$this->parsed = new parsed_file($tokens, $root, $new_collection, new Storage /** Storage<scope> */());
		}
		else
		{
			$this->parsed = $previous;
			if ($previous->complete && $previous->collection->parse_complete) {
				$this->reuse_previous = true;
			}
		}
		$this->file_scope = $file_scope;
		if ($global !== null) {
			$file_scope->set_parent($global);
			$file_scope->set_publication($global);
		}
		$this->current_scope = new scope();
		$this->current_scope->set_parent($file_scope);
		$this->retain_scope($file_scope);
		$this->retain_scope($this->current_scope);
		$this->parsed->complete = false;
		$this->parsed->collection->parse_complete = false;

		$this->parsed->tokens = $tokens;
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

	/** Keep old and new scopes owned on failure; select the next successful file inventory separately. */
	private function retain_scope(scope $retained_scope): void
	{
		$selected /** Storage<scope> */ = $this->scopes;
		$selected->append($retained_scope);
		$owned /** Storage<scope> */ = $this->parsed->scopes;
		$owned->append($retained_scope);
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
		$body_changed = !$this->reuse_previous;
		$index = 0;
		while ($this->position < $this->tokens->end_token)
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
				$this->retain_range($old, $temporary->start_token());
			}
			$body = $old_body;
			$this->retain_scope($body->local_scope());
		}
		else {
			$this->transfer_body_work($old_body, $body);
		}
		$body->syntax_changed = $body_changed;
		$body->set_span($this->tokens->first_token, $this->position);
		$root->body = $body;
		$root->declarations = $declarations;
		$root->set_span($this->tokens->first_token, $this->position);
		$this->collector->finish($root);
		// Release obsolete scopes only after all selected syntax and collection links are published.
		$this->parsed->scopes = $this->scopes;
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
		for ($index = $this->position; $index < $this->tokens->end_token; $index++)
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
		return $this->tokens->end_token;
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
	}

	/** Retain indexes during compilation; collection and deferred cleanup use this interval. */
	private function retain_range(ast_node $node, int $current_first): void
	{
		$ranges /** Storage<retained_token_range> */ = $this->tokens->retained_ranges;
		$ranges->append(new retained_token_range($node->start_token(), $node->end_token(), $current_first));
	}

	/** Existing half-open token bounds identify the exact body bytes, including internal whitespace. */
	private function same_body_text(int $old_start, int $old_end, int $start, int $end): bool
	{
		if ((!$this->reuse_previous) || ($old_start < 0)) {
			return false;
		}
		if (($old_end <= $old_start) || ($end <= $start)) {
			return false;
		}
		$current /** Storage<token> */ = $this->tokens->tokens;
		$old_first = $current[$old_start];
		$old_last = $current[$old_end - 1];
		$first = $current[$start];
		$last = $current[$end - 1];
		$old_length = (int)$old_last->offset + (int)$old_last->length - (int)$old_first->offset;
		$length = (int)$last->offset + (int)$last->length - (int)$first->offset;
		if ($old_length !== $length) {
			return false;
		}
		return string_byte_slice($this->tokens->content, (int)$old_first->offset, $old_length) === string_byte_slice($this->tokens->content, (int)$first->offset, $length);
	}

	/** Token spellings preserve literal accuracy and ignore source offset/whitespace changes. */
	private function same_tokens(int $old_start, int $old_end, int $start, int $end): bool
	{
		if ((!$this->reuse_previous) || ($old_start < 0)) {
			return false;
		}
		if (($old_end - $old_start) !== ($end - $start)) {
			return false;
		}
		while ($start < $end) {
			if ($this->tokens->text_at($old_start) !== $this->tokens->text_at($start)) {
				return false;
			}
			$old_start++;
			$start++;
		}
		return true;
	}

	/** Refresh source spans without replacing a matched declaration. */
	private function finish_declaration(ast_node $node, int $start, bool $same): void
	{
		$entry = object_cast($node->optional_occurrence(), collected_name::class);
		if (!$same && ($entry->change_status !== change_state::added)) {
			$entry->change_status = change_state::changed;
		}
		$node->set_span($start, $this->position);
		if (!$same) {
			$work = $entry->preparation_work_owner();
			if ($work !== null) {
				$work->state = preparation_state::pending;
				$work->change_status = change_state::changed;
			}
		}
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
		if (string_byte_starts_with($this->text(), '$'))
		{
			$next = $this->text_at_offset(1);
			if (Source_Text::identifier($next) && ($next !== 'and') && ($next !== 'or') && ($next !== 'xor')) {
				return $this->variable_declaration();
			}
			return $this->expression_statement();
		}
		return $this->expression_statement();
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
		$previous = $this->collector->previous_struct($this->file_scope, $name);
		$record /** struct_node */ = $previous ?? new struct_node();
		if ($previous === null) {
			$record->set_span($start, $start);
		}
		$old_start = $record->start_token();
		if ($previous !== null) {
			if (object_cast($record->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $record->end_token();
		$record->name = $name;
		$record->collect($this->collector, $this->file_scope, $name_index);
		$record->member_scope()->set_parent($this->file_scope);
		$fields /** Storage<field_node> */ = new Storage();
		$this->expect('{');
		while ($this->text() !== '}') {
			$fields->append($this->field_declaration($record->member_scope()));
		}
		$this->expect('}');
		$record->fields = $fields;
		$this->finish_declaration($record, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
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
		$type = $this->type_syntax($scope);
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected field variable name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous_field($scope, $name);
		$field /** field_node */ = $previous ?? new field_node();
		if ($previous === null) {
			$field->set_span($start, $start);
		}
		$old_start = $field->start_token();
		if ($previous !== null) {
			if (object_cast($field->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $field->end_token();
		$field->type_syntax = $type;
		$field->name = $name;
		$field->collect($this->collector, $scope, $name_index);
		$this->expect(';');
		$this->finish_declaration($field, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
		return $field;
	}

	/** A statement owns its semicolon; its value expression keeps its existing syntax shape. */
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

		$previous = $this->collector->previous_function($this->file_scope, $function_name);
		$function /** function_node */ = $previous ?? new function_node();
		if ($previous === null) {
			$function->set_span($start, $start);
		}
		$old_start = $function->start_token();
		if ($previous !== null) {
			if (object_cast($function->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_body_start = $old_start;
		$old_body_end = $old_start;
		if ($previous !== null) {
			if (($this->reuse_previous) && ($old_start >= 0)) {
				$old_body_start = $function->body->start_token();
				$old_body_end = $function->body->end_token();
			}
		}
		$function->name = $function_name;
		$function->collect($this->collector, $this->file_scope, $name_token_index);
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

		$return_type = $this->type_syntax($local_scope);

		$body_start = $this->position;
		$body_end = $this->body_end();
		$body_changed = !$this->same_body_text($old_body_start, $old_body_end, $body_start, $body_end);
		$selected_scope /** scope */ = !$body_changed ? $function->body->local_scope() : new scope();
		if ($body_changed) {
			$selected_scope->set_parent($local_scope);
			$selected_scope->mark_function();
		}
		$this->retain_scope($selected_scope);
		$body /** function_body_node */ = !$body_changed ? $function->body : $this->block($selected_scope);
		if (!$body_changed) {
			$this->retain_range($body, $body_start);
			$this->position = $body_end;
		}
		else {
			if ($previous !== null) {
				if ($function->has_parsed_body()) {
					$this->transfer_body_work($function->body, $body);
				}
			}
		}

		$function->return_type = $return_type;
		$body->syntax_changed = $body_changed;
		$function->set_parsed_body($body);
		$function->parameters = $parameters;
		$function->template_parameters = $formals;
		$this->finish_declaration($function, $start, $this->same_tokens($old_start, $old_body_start, $start, $body_start));
		return $function;
	}

	/** Register a typed parameter as an explicit variable declaration in the body scope. */
	private function parameter(scope $scope): parameter_node
	{
		$start = $this->position;
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return'))) {
			throw new \RuntimeException($this->error_message('Expected parameter type'));
		}
		$type = $this->type_syntax($scope);
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
		$previous = $this->collector->previous_parameter($scope, $name);
		$parameter /** parameter_node */ = $previous ?? new parameter_node();
		if ($previous === null) {
			$parameter->set_span($start, $start);
		}
		$old_start = $parameter->start_token();
		if ($previous !== null) {
			if (object_cast($parameter->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $parameter->end_token();
		$parameter->type_syntax = $type;
		$parameter->mode = $mode;

		$parameter->name = $name;
		$parameter->collect($this->collector, $scope, $name_index);
		$this->finish_declaration($parameter, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
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
		$return_statement = new return_node();
		$this->position++;
		if ($this->text() !== ';') {
			$return_statement->expression = $this->expression();
		}
		$this->expect(';');
		$this->finish_node($return_statement, $start);
		return $return_statement;
	}

	/** Explicit type syntax declares storage; plain writes use the expression grammar. */
	private function variable_declaration(): statement_node
	{
		$start = $this->position++;
		$declaration = new variable_declaration_node();
		$declaration->name = $this->name_at($start);
		$type = $this->type_syntax($this->current_scope);
		$declaration->type_syntax = $this->text() === '['
			? object_cast($this->array_type($type), type_node::class)
			: object_cast($type, type_node::class);
		if ($this->text() === '=') {
			$this->position++;
			$declaration->initializer = $this->expression();
		}
		$this->expect(';');
		$this->finish_node($declaration, $start);
		$declaration->collect($this->collector, $this->current_scope, $start);
		return $declaration;
	}

	/** One precedence ladder includes right-associative writes and weaker keyword logic. */
	private function expression(): expression_node
	{
		return $this->binary_expression(1);
	}

	/** Assignments recurse at the same precedence; other binary operators associate left. */
	private function binary_expression(int $minimum_precedence): expression_node
	{
		$start = $this->position;
		$left = $this->unary_expression();
		while (true)
		{
			$precedence = $this->binary_precedence();
			if ($precedence < $minimum_precedence) {
				break;
			}
			$operator_text = $this->tokens->operator_text_at($this->position);
			$operator_token_index = $this->position++;
			if (($operator_text === '<<') || ($operator_text === '>>')) {
				$this->position++;
			}
			if ($precedence === 4)
			{
				if ($operator_text === '=') {
					if (!($left instanceof assignable_expression_node)) {
						throw new \RuntimeException($this->error_message('Assignment requires an assignable target'));
					}
					$assignment = new assignment_expression_node();
					$assignment->target = object_cast($left, assignable_expression_node::class);
					$assignment->value = $this->binary_expression($precedence);
					$this->finish_node($assignment, $start);
					$left = $assignment;
				}
				else {
					$compound = new compound_assignment_expression_node();
					$compound->target = $left;
					$compound->operator_token_index = $operator_token_index;
					$compound->value = $this->binary_expression($precedence);
					$this->finish_node($compound, $start);
					$left = $compound;
				}
				continue;
			}
			$binary = new binary_expression_node();
			$binary->left = $left;
			$binary->operator_token_index = $operator_token_index;
			$binary->right = $this->binary_expression($precedence + 1);
			$this->finish_node($binary, $start);
			$left = $binary;
		}
		return $left;
	}

	/** Zero ends the expression; only admitted operators receive a precedence level. */
	private function binary_precedence(): int
	{
		if ($this->position >= $this->tokens->end_token) {
			return 0;
		}
		$text = $this->tokens->operator_text_at($this->position);
		if ($text === 'or') {
			return 1;
		}
		if ($text === 'xor') {
			return 2;
		}
		if ($text === 'and') {
			return 3;
		}
		if (($text === '=') || ($text === '+=') || ($text === '-=') || ($text === '*=')
			|| ($text === '/=') || ($text === '%=') || ($text === '.=')
			|| ($text === '&=') || ($text === '|=') || ($text === '^=')
			|| ($text === '<<=') || ($text === '>>=')) {
			return 4;
		}
		if ($text === '||') {
			return 5;
		}
		if ($text === '&&') {
			return 6;
		}
		if ($text === '|') {
			return 7;
		}
		if ($text === '^') {
			return 8;
		}
		if ($text === '&') {
			return 9;
		}
		if (($text === '==') || ($text === '!=') || ($text === '===') || ($text === '!==') || ($text === '<=>')) {
			return 10;
		}
		if (($text === '<') || ($text === '<=') || ($text === '>') || ($text === '>=')) {
			return 11;
		}
		if ($text === '.') {
			return 12;
		}
		if (($text === '<<') || ($text === '>>')) {
			return 13;
		}
		if (($text === '+') || ($text === '-')) {
			return 14;
		}
		if (($text === '*') || ($text === '/') || ($text === '%')) {
			return 15;
		}
		return 0;
	}

	/** Prefix operators nest right-to-left; exponentiation binds inside their operand. */
	private function unary_expression(): expression_node
	{
		$text = $this->text();
		if (($text === '++') || ($text === '--'))
		{
			$start = $this->position++;
			$mutation = new mutation_expression_node();
			$mutation->operator_token_index = $start;
			$mutation->target = $this->unary_expression();
			$this->finish_node($mutation, $start);
			return $mutation;
		}
		if (($text !== '+') && ($text !== '-') && ($text !== '~') && ($text !== '!')) {
			return $this->power_expression();
		}
		$start = $this->position++;
		$unary = new unary_expression_node();
		$unary->operator_token_index = $start;
		$unary->operand = $this->unary_expression();
		$this->finish_node($unary, $start);
		return $unary;
	}

	/** Power associates right-to-left and accepts a signed exponent through unary parsing. */
	private function power_expression(): expression_node
	{
		$start = $this->position;
		$left = $this->postfix_expression();
		if ($this->text() !== '**') {
			return $left;
		}
		$power = new binary_expression_node();
		$power->left = $left;
		$power->operator_token_index = $this->position++;
		$power->right = $this->unary_expression();
		$this->finish_node($power, $start);
		return $power;
	}

	/** Postfix mutation binds to the complete primary/access expression, before prefix operators. */
	private function postfix_expression(): expression_node
	{
		$start = $this->position;
		$target = $this->primary_expression();
		while (($this->text() === '++') || ($this->text() === '--'))
		{
			$mutation = new mutation_expression_node();
			$mutation->target = $target;
			$mutation->operator_token_index = $this->position++;
			$mutation->postfix = true;
			$this->finish_node($mutation, $start);
			$target = $mutation;
		}
		return $target;
	}

	/** Parse literals, calls, variables and normalized grouping. */
	private function primary_expression(): expression_node
	{
		if ($this->text() === '(')
		{
			if ($this->cast_ahead()) {
				return $this->cast_expression();
			}
			$group_start = $this->position++;
			$inner = $this->expression();
			$this->expect(')');
			return $this->access_suffix($inner, $group_start);
		}
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
			if ($this->call_ahead()) {
				return $this->call_expression();
			}
			$start = $this->position++;
			$constant = new constant_reference_node();
			$constant->name = $this->name_at($start);
			$this->finish_node($constant, $start);
			$constant->collect($this->collector, $this->current_scope, $start);
			return $this->access_suffix($constant, $start);
		}
		$start = $this->position;
		$text = $this->text();
		$this->position++;
		$node /** expression_node */;
		if (string_byte_starts_with($text, '$')) {
			$variable = $this->variable_reference($start);
			// Parentheses normalize away; a grouped bare variable keeps its write role.
			$offset = 0;
			while ($this->text_at_offset($offset) === ')') {
				$offset++;
			}
			if ($this->text_at_offset($offset) === '=') {
				$variable->collect_write($this->collector, $this->current_scope, $start);
			}
			else {
				$variable->collect($this->collector, $this->current_scope, $start);
			}
			$node = $variable;
		}
		elseif (($text !== '') && Source_Text::digits($text)) {
			$node = new integer_literal_node();
			$this->finish_node($node, $start);
		}
		elseif (($text !== '') && ((string_byte_at($text, 0) === 39) || (string_byte_at($text, 0) === 34))) {
			$node = $this->string_expression($start, $text);
		}
		elseif (($text !== '.') && Source_Text::floating($text)) {
			$node = new float_literal_node();
			$this->finish_node($node, $start);
		}
		else {
			throw new \RuntimeException($this->error_message('Expected scalar literal or variable reference'));
		}
		return $this->access_suffix($node, $start);
	}

	/** Split only double-quoted variable insertions; all offsets stay relative to the source token. */
	private function string_expression(int $start, string $text): expression_node
	{
		$literal = new string_literal_node();
		$this->finish_node($literal, $start);
		if (string_byte_at($text, 0) !== 34) {
			return $literal;
		}
		$interpolation = new interpolated_string_node();
		$this->finish_node($interpolation, $start);
		$parts /** Storage<interpolation_part_node> */ = $interpolation->parts;
		$end = string_byte_len($text) - 1;
		$segment = 1;
		$offset = 1;
		$insertions = 0;
		while ($offset < $end)
		{
			$byte = string_byte_at($text, $offset);
			if ($byte === 92) {
				$offset += 2;
				continue;
			}
			$braced = ($byte === 123) && (string_byte_at($text, $offset + 1) === 36);
			if (($byte !== 36) && !$braced) {
				$offset++;
				continue;
			}
			$first = $offset;
			$variable_start = $braced ? $offset + 1 : $offset;
			$name_start = $variable_start + 1;
			$initial = string_byte_at($text, $name_start);
			if (!Source_Text::letter($initial)) {
				if ($braced || ($initial === 123) || ($initial === 36) || ($initial >= 128)) {
					throw new \RuntimeException($this->error_message('Unsupported interpolation; expected $name or {$name}', $start, $first));
				}
				$offset++;
				continue;
			}
			$offset = $name_start + 1;
			while (Source_Text::letter(string_byte_at($text, $offset)) || Source_Text::digit(string_byte_at($text, $offset))) {
				$offset++;
			}
			$name = string_byte_slice($text, $name_start, $offset - $name_start);
			if ($braced) {
				if (string_byte_at($text, $offset) !== 125) {
					throw new \RuntimeException($this->error_message('Unsupported braced interpolation; expected {$name}', $start, $first));
				}
				$offset++;
			}
			elseif ((string_byte_at($text, $offset) === 91) || (string_byte_at($text, $offset) === 40)
				|| (string_byte_slice($text, $offset, 2) === '->') || (string_byte_at($text, $offset) >= 128)) {
				throw new \RuntimeException($this->error_message('Unsupported interpolation suffix; use {$name} before literal suffix text', $start, $first));
			}
			$this->interpolation_text($interpolation, $start, $segment, $first - $segment);
			$variable = new variable_reference_node();
			$variable->name = $name;
			$this->finish_node($variable, $start);
			$variable->collect($this->collector, $this->current_scope, $start);
			$part = new interpolation_value_node();
			$part->expression = $variable;
			$part->byte_offset = $first;
			$part->byte_length = $offset - $first;
			$this->finish_node($part, $start);
			$parts->append($part);
			$insertions++;
			$segment = $offset;
		}
		if ($insertions === 0) {
			return $literal;
		}
		$this->interpolation_text($interpolation, $start, $segment, $end - $segment);
		return $interpolation;
	}

	/** Keep literal source bytes separate from decoded preparation facts. */
	private function interpolation_text(interpolated_string_node $parent, int $start,
		int $offset, int $length): void
	{
		if ($length === 0) {
			return;
		}
		$part = new interpolation_text_node();
		$part->byte_offset = $offset;
		$part->byte_length = $length;
		$this->finish_node($part, $start);
		$parts /** Storage<interpolation_part_node> */ = $parent->parts;
		$parts->append($part);
	}

	/** Recognize cast syntax without moving the parser or collecting speculative type names. */
	private function cast_ahead(): bool
	{
		$end = $this->type_syntax_end(1);
		if ($end < 0) {
			return false;
		}
		if ($this->text_at_offset($end) !== ')') {
			return false;
		}
		$operand = $this->text_at_offset($end + 1);
		if (($operand === '') || ($operand === 'and') || ($operand === 'or') || ($operand === 'xor')) {
			return false;
		}
		$first = string_byte_at($operand, 0);
		return Source_Text::identifier($operand) || Source_Text::digit($first)
			|| ($first === 36) || ($first === 39) || ($first === 34)
			|| ($operand === '!') || ($operand === '~')
			|| ($operand === '(') || ($operand === '[') || (($first === 46) && (string_byte_len($operand) > 1));
	}

	/** Distinguish named/template calls from constants followed by relational operators. */
	private function call_ahead(): bool
	{
		if ($this->text_at_offset(1) === '(') {
			return true;
		}
		$end = $this->type_syntax_end(0);
		if ($end < 0) {
			return false;
		}
		return $this->text_at_offset($end) === '(';
	}

	/** Scan the existing name / name<type,...> grammar; -1 means no complete type form. */
	private function type_syntax_end(int $offset): int
	{
		if (!Source_Text::identifier($this->text_at_offset($offset))) {
			return -1;
		}
		$offset++;
		if ($this->text_at_offset($offset) !== '<') {
			return $offset;
		}
		do
		{
			$offset = $this->type_syntax_end($offset + 1);
			if ($offset < 0) {
				return -1;
			}
		}
		while ($this->text_at_offset($offset) === ',');
		if ($this->text_at_offset($offset) !== '>') {
			return -1;
		}
		return $offset + 1;
	}

	/** Parse one general source cast; the type registry decides its target meaning later. */
	private function cast_expression(): expression_node
	{
		$start = $this->expect('(');
		$cast = new cast_expression_node();
		$cast->target_type = $this->type_syntax($this->current_scope);
		$this->expect(')');
		$cast->operand = $this->unary_expression();
		$this->finish_node($cast, $start);
		return $this->access_suffix($cast, $start);
	}

	/** Preserve the element type and literal extent independently of LLVM spelling. */
	private function array_type(type_node $element): array_type_node
	{
		$this->expect('[');
		$type = new array_type_node();
		$type->element_type = $element;
		$count_expression = $this->expression();
		if (!($count_expression instanceof integer_literal_node)) {
			throw new \RuntimeException($this->error_message('Fixed array size must be a nonnegative integer literal'));
		}
		$type->count = object_cast($count_expression, integer_literal_node::class);
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
	private function access_suffix(expression_node $base, int $start): expression_node
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
				$this->finish_node($field, $start);
				$base = $field;
				$field->collect($this->collector, $this->current_scope, $field_index);
				continue;
			}
			$this->position++;
			$access = new index_node();
			$access->base = $base;
			$access->index = $this->expression();
			$this->expect(']');
			$this->finish_node($access, $start);
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
				$type->collect($this->collector, $this->current_scope, $type_start);
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
		$call->collect($this->collector, $this->current_scope, $start);
		return $call;
	}

	/** Complete source provenance without erasing concrete types. */
	private function finish_node(ast_node $node, int $start): void
	{
		$node->set_span($start, $this->position);
	}

	/** Type names retain spelling independently of token generations. */
	private function named_type(int $start): named_type_node
	{
		$node = new named_type_node();
		$node->name = $this->name_at($start);
		$this->finish_node($node, $start);
		return $node;
	}

	/** Parse one named type, template application or registered type-use modifier. */
	private function type_syntax(scope $lookup_scope): type_node
	{
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected type name'));
		}
		$start = $this->position++;
		$name = $this->name_at($start);
		$modifier = Model::$type_catalog->source()->modifier_or_null($name);
		if (($modifier !== null) && ($this->text() === '<'))
		{
			$this->position++;
			$modified = new type_use_modifier_node();
			$modified->name = $name;
			$modified->modifier = $modifier->kind();
			$modified->operand = $this->type_syntax($lookup_scope);
			$this->expect('>');
			$this->finish_node($modified, $start);
			return $modified;
		}
		$definition = $this->named_type($start);
		$definition->collect($this->collector, $lookup_scope, $start);
		if ($this->text() !== '<') {
			return $definition;
		}

		$application = new template_application_type_node();
		$application->definition = $definition;
		$arguments /** Storage<type_node> */ = $application->arguments;
		$this->position++;
		if ($this->text() === '>') {
			throw new \RuntimeException($this->error_message('Template type application requires an argument'));
		}
		do {
			$arguments->append($this->type_syntax($lookup_scope));
			if ($this->text() !== ',') {
				break;
			}
			$this->position++;
		}
		while (true);
		$this->expect('>');
		$this->finish_node($application, $start);
		return $application;
	}

	/** A variable occurrence is classified as a read or write by its parser context. */
	private function variable_reference(int $start): variable_reference_node
	{
		$node = new variable_reference_node();
		$node->name = $this->name_at($start);
		$this->finish_node($node, $start);
		return $node;
	}

	private function name_at(int $index): string
	{
		$text = $this->tokens->text_at($index);
		return string_byte_starts_with($text, '$') ? string_byte_slice($text, 1, string_byte_len($text) - 1) : $text;
	}

	private function text(): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		if ($this->position >= $this->tokens->end_token) {
			return '';
		}
		return $token_rows[$this->position]->text();
	}

	private function text_at_offset(int $offset): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		$index = $this->position + $offset;
		if ($index >= $this->tokens->end_token) {
			return '';
		}
		return $token_rows[$index]->text();
	}

	private function expect(string $text): int
	{
		if ($this->text() !== $text) {
			throw new \RuntimeException($this->error_message(("Expected '" . $text . "'")));
		}
		return $this->position++;
	}

	/** Describe the current source position without publishing incomplete syntax. */
	private function error_message(string $message, int $token_index = -1, int $within_token = 0): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		if ($token_index < 0) {
			$token_index = $this->position;
		}
		$offset = string_byte_len($this->tokens->content) - $this->tokens->content_offset;
		if (isset($token_rows[$token_index])) {
			$offset = (int)$token_rows[$token_index]->offset - $this->tokens->content_offset + $within_token;
		}
		return $message . " at " . $this->tokens->file->path . ": byte " . $offset;
	}
}
