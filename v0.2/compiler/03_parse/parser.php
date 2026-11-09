<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Parser extension point for flavor-specific statements and function headers; all flavors produce the same AST families.
 */
interface statement_grammar {
	public function statement(Parser $parser, Body_Collection $collector): ast_statement;
	public function function_start(Parser $parser): bool;
	public function function_header(Parser $parser): ast_function_declaration;
}

/** Shared syntax construction; flavor grammars select declaration and assignment intent. */
final class Parser
{
	/** PROPERTIES */
	/** Native owning; parser-local cursor. */
	public readonly Syntax_Cursor $cursor;
	/** Native owning; flavor-specific syntax adapter shares the parser cursor. */
	private readonly Namespace_Syntax $names;
	private bool $started = false;
	/** Native owning until returned to the frontend. */
	private ast_file_node $root;
	/** Native non-owning; coordinator outlives parsing. */
	private ?declaration_sink $sink;

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens, ?declaration_sink $sink = null)
	{
		$this->cursor = new Syntax_Cursor($tokens);
		$this->names = new Namespace_Syntax($this->cursor);
		$this->sink = $sink;
	}

	/** METHODS */
	/** Own one source parse and its local collection; failure exposes neither partial result. */
	public function parse(): ast_file_node
	{
		if ($this->started) {
			throw new \LogicException('A parser instance processes one source once');
		}
		$this->started = true;
		$namespace = null;
		$braced = false;
		if ($this->cursor->keyword('namespace')) {
			$namespace = $this->names->declaration();
			$braced = $this->cursor->is(token_kind::lbrace);
			$delimiter = $braced ? token_kind::lbrace : token_kind::semicolon;
			if ((!$braced) && ($this->cursor->tokens->source->flavor !== flavor::php)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected { after namespace');
			}
			$this->cursor->take($delimiter, $braced ? '{' : ';');
		}
		$this->root = new ast_file_node($this->cursor->tokens, $namespace);
		$body_start = $this->cursor->position();
		$collector = new Body_Collection($this->root->scope);
		$declarations = new Scope_Collection($this->root->scope);
		$grammar = match ($this->cursor->tokens->source->flavor) {
			flavor::cpp => new Cpp_Grammar(),
				flavor::php => new Php_Grammar(),
				flavor::js => new Js_Grammar(),
		};
		$statements = [];
		$nodes = [];
		$imports = [];
		while ((!$this->cursor->at_end()) && ((!$braced) || (!$this->cursor->is(token_kind::rbrace))))
		{
			if ($this->cursor->keyword('global') || $this->cursor->keyword('const'))
			{
				$global = $this->cursor->keyword('const') ? Constant_Syntax::read($this) : Global_Syntax::read($this);
				$global->attach($this->root);
				$this->sink?->discovered($global);
				$declarations->import($global);
				$nodes[] = $global;
				continue;
			}
			if ($this->cursor->keyword('use')) {
				foreach (Import_Syntax::read($this->cursor, $this->names) as $import) {
					$import->attach($this->root);
					$imports[] = $import;
				}
				continue;
			}
			if ($grammar->function_start($this)) {
				$function = $this->function_declaration($grammar);
				$declarations->import($function);
				$nodes[] = $function;
				continue;
			}
			$statement = $this->statement($grammar, $collector);
			$collector->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$body_end = $this->cursor->position();
		if ($braced) {
			$this->cursor->take(token_kind::rbrace, '}');
			if (!$this->cursor->at_end()) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
					'Only one namespace body per file is supported');
			}
		}
		if ($statements !== [])
		{
			$main = new ast_function_declaration('main',
				new ast_type_reference($body_start, $body_start, 'int'), $body_start, $body_start, true, $namespace);
			$main->attach($this->root);
			$main->complete_body($collector->body($body_start, $body_end, $statements));
			$declarations->import($main);
			array_unshift($nodes, $main);
		}
		/** @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration)[] $heads */
		$heads = $declarations->publish_references()->heads();
		if (isset($heads['func:main'])) {
			$heads = ['func:main' => $heads['func:main']] + $heads;
		}
		$this->root->complete($heads, $nodes, Declaration_Diagnostics::file($nodes), $imports);
		return $this->root;
	}

	/** Share body grammar while keeping function definitions out of executable statement inventories. */
	private function statement(statement_grammar $grammar, Body_Collection $collector): ast_statement
	{
		if ($this->cursor->keyword('global') || $this->cursor->keyword('const')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				($this->cursor->keyword('const') ? 'Constant' : 'Global') . ' declarations are only allowed at file/namespace level');
		}
		if ($this->cursor->keyword('namespace') || $this->cursor->keyword('export') || $this->cursor->keyword('use')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Nested/additional namespaces, body-local use and export are not supported in this slice');
		}
		if ($this->cursor->keyword('for')) {
			return $this->for_loop($grammar, $collector);
		}
		if ($this->cursor->keyword('while') || $this->cursor->keyword('do')) {
			return $this->loop($grammar, $collector);
		}
		if ($this->cursor->keyword('break') || $this->cursor->keyword('continue')) {
			$is_break = $this->cursor->keyword('break');
			$start = $this->cursor->position();
			$this->cursor->take_keyword($is_break ? 'break' : 'continue');
			$end = $this->cursor->take(token_kind::semicolon, '; (only unlabelled break/continue are supported)') + 1;
			return $is_break ? new ast_break_statement($start, $end) : new ast_continue_statement($start, $end);
		}
		if ($this->cursor->keyword('if')) {
			return $this->conditional($grammar, $collector);
		}
		if ($this->cursor->keyword('echo')) {
			return $this->echo_statement();
		}
		if ($grammar->function_start($this)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Nested functions are not supported');
		}
		if ($this->cursor->keyword('return')) {
			return $this->return_statement();
		}
		if ($this->names->call_start() || ($this->cursor->tokens->source->flavor === flavor::cpp
			&& $this->cursor->is(token_kind::identifier) && $this->cursor->next_is(token_kind::dot))) {
			$value = $this->primary();
			if (!($value instanceof ast_call_expression)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected call statement');
			}
			$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
			return new ast_call_statement($value->start, $end, $value);
		}
		$name_kind = $this->cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
		if ($this->cursor->is($name_kind) && $this->cursor->next_is(token_kind::lbracket)) {
			$start = $this->cursor->position();
			$name = $this->cursor->name($name_kind);
			$receiver = new ast_name_expression($name, $start, $start + 1);
			$this->cursor->take(token_kind::lbracket, '[');
			$index = $this->cursor->is(token_kind::rbracket) ? null : $this->value();
			$this->cursor->take(token_kind::rbracket, ']');
			$this->cursor->take(token_kind::assign, '=');
			$value = $this->assignment_value();
			$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
			return $index === null ? new ast_append_statement($receiver, $value, $end)
				: new ast_index_assignment($receiver, $index, $value, $end);
		}
		return $this->update_start() ? $this->update_statement(token_kind::semicolon) : $grammar->statement($this, $collector);
	}

	/** Parse only braced alternatives; each body gets its own lexical collector and shared storage IDs. */
	private function conditional(statement_grammar $grammar, Body_Collection $collector): ast_if_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('if');
		$arms = [];
		$conditional = true;
		do {
			$arm_start = $this->cursor->position();
			$condition = null;
			if ($conditional) {
				$this->cursor->take(token_kind::lparen, '(');
				$condition = $this->value();
				$this->cursor->take(token_kind::rparen, ')');
			}
			$body = $this->braced_block($grammar, $collector, '{ (braced conditional body)');
			$arms[] = new ast_if_arm($arm_start, $body->end, $condition, $body);
			if (!$conditional) {
				break;
			}
			if ($this->cursor->keyword('elseif')) {
				$this->cursor->take_keyword('elseif');
			}
			elseif ($this->cursor->keyword('else')) {
				$this->cursor->take_keyword('else');
				$conditional = $this->cursor->keyword('if');
				if ($conditional) {
					$this->cursor->take_keyword('if');
				}
			}
			else {
				break;
			}
		} while (true);
		return new ast_if_statement($start, $this->cursor->position(), $arms);
	}

	/** Parse a lexical block once for conditional and loop bodies. */
	private function braced_block(statement_grammar $grammar, Body_Collection $collector, string $expected): ast_block
	{
		$start = $this->cursor->take(token_kind::lbrace, $expected);
		$child = $collector->child();
		$statements = [];
		while (!$this->cursor->is(token_kind::rbrace)) {
			if ($this->cursor->at_end()) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar, $child);
			$child->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$end = $this->cursor->take(token_kind::rbrace, '}') + 1;
		return $child->block($start, $end, $statements);
	}

	/** Parse one initializer, condition and assignment update in a header-local scope. */
	private function for_loop(statement_grammar $grammar, Body_Collection $collector): ast_for_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('for');
		$this->cursor->take(token_kind::lparen, '(');
		$header = $collector->child();
		$initializer = $this->update_start() ? $this->update_statement(token_kind::semicolon) : $grammar->statement($this, $header);
		if (!($initializer instanceof ast_local_declaration) && !($initializer instanceof ast_storage_write)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'For initializer requires a declaration or assignment');
		}
		$header->statement($initializer, 0);
		$condition = $this->value();
		$this->cursor->take(token_kind::semicolon, '; (for condition separator)');
		if ($this->update_start()) {
			$update = $this->update_statement(token_kind::rparen);
		}
		else {
			$update_start = $this->cursor->position();
			$name = $this->cursor->name($this->cursor->tokens->source->flavor === flavor::php
				? token_kind::variable : token_kind::identifier);
			$update = $this->assignment($update_start, $name, assignment_intent::existing_binding, token_kind::rparen);
		}
		$body = $this->braced_block($grammar, $header, '{ (braced for body)');
		return new ast_for_statement($start, $initializer, $condition, $update, $body, $header->publish_scope());
	}

	/** Parse pre-test/post-test loops with a required braced body and explicit do-while terminator. */
	private function loop(statement_grammar $grammar, Body_Collection $collector): ast_loop_statement
	{
		$start = $this->cursor->position();
		$post_test = $this->cursor->keyword('do');
		$this->cursor->take_keyword($post_test ? 'do' : 'while');
		if ($post_test) {
			$body = $this->braced_block($grammar, $collector, '{ (braced loop body)');
			$this->cursor->take_keyword('while');
		}
		$this->cursor->take(token_kind::lparen, '(');
		$condition = $this->value();
		$this->cursor->take(token_kind::rparen, ')');
		if ($post_test) {
			$end = $this->cursor->take(token_kind::semicolon, '; (do-while terminator)') + 1;
			return new ast_do_while_statement($start, $end, $condition, $body);
		}
		$body = $this->braced_block($grammar, $collector, '{ (braced loop body)');
		return new ast_while_statement($start, $body->end, $condition, $body);
	}

	/** Discover the stable header before parsing its body; local collection never enters the global index. */
	private function function_declaration(statement_grammar $grammar): ast_function_declaration
	{
		$function = $grammar->function_header($this);
		$function->attach($this->root);
		$source = $this->cursor->tokens->source;
		if ($function->short_name === 'main') {
			throw new diagnostic($source, $this->root->byte_at($function->name_position()),
				'Explicit main is not supported yet');
		}
		$this->sink?->discovered($function);
		$body_start = $this->cursor->take(token_kind::lbrace, '{');
		$body_collector = new Body_Collection($this->root->scope, $function->parameters);
		$statements = [];
		while (!$this->cursor->is(token_kind::rbrace))
		{
			if ($this->cursor->at_end()) {
				throw new diagnostic($source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar, $body_collector);
			$body_collector->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$end = $this->cursor->take(token_kind::rbrace, '}') + 1;
		$function->complete_body($body_collector->body($body_start, $end, $statements));
		return $function;
	}

	/** PHP and JS share the explicit function-keyword/colon-return header grammar. */
	public function named_function_header(): ast_function_declaration
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('function');
		$name = $this->cursor->name(token_kind::identifier);
		$parameters = $this->parameters();
		$this->cursor->take(token_kind::colon, ': (explicit return type)');
		$type = $this->return_type();
		return new ast_function_declaration($name, $type,
			$start, $this->cursor->position(), false, $this->root->scope->namespace, $parameters);
	}

	public function source_namespace(): ?string
	{
		return $this->root->scope->namespace;
	}

	/**
	 * Parse explicit positional passing modes and optional literal-default syntax.
	 * @return ast_parameter_declaration[]
	 */
	public function parameters(): array /** list<ast_parameter_declaration> */
	{
		$this->cursor->take(token_kind::lparen, '(');
		$parameters = [];
		while (!$this->cursor->is(token_kind::rparen)) {
			$start = $this->cursor->position();
			if ($this->cursor->tokens->source->flavor === flavor::js) {
				$mode = $this->parameter_mode();
				$name = $this->cursor->name(token_kind::identifier);
				$this->cursor->take(token_kind::colon, ': (explicit parameter type)');
				$type = $this->return_type();
			}
			else {
				$type = $this->return_type();
				$mode = $this->parameter_mode();
				$name = $this->cursor->name($this->cursor->tokens->source->flavor === flavor::php
					? token_kind::variable : token_kind::identifier);
			}
			$default = null;
			if ($this->cursor->is(token_kind::assign)) {
				$this->cursor->take(token_kind::assign, '=');
				$default = $this->value();
			}
			$parameters[] = new ast_parameter_declaration($name, $type, count($parameters), $start, $this->cursor->position(), $default, $mode);
			if (!$this->cursor->is(token_kind::comma)) {
				break;
			}
			$this->cursor->take(token_kind::comma, ',');
			if ($this->cursor->is(token_kind::rparen)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Trailing parameter comma is deferred');
			}
		}
		$this->cursor->take(token_kind::rparen, ') (typed parameters)');
		return $parameters;
	}

	/** The reference marker precedes the parameter name in each flavor. */
	private function parameter_mode(): passing_mode
	{
		if ($this->cursor->is(token_kind::ampersand)) {
			$this->cursor->take(token_kind::ampersand, '&');
			return passing_mode::reference;
		}
		return passing_mode::value;
	}

	/** Calls own ordinary value arguments; nested calls use the same parser and AST node. */
	private function call(): ast_call_expression
	{
		$start = $this->cursor->position();
		$name = $this->names->path(true, call: true);
		$member = $this->cursor->tokens->source->flavor === flavor::js
			&& !str_starts_with($name, '\\') && str_contains($name, '\\');
		$receiver = null;
		if ($member) {
			$prefix = substr($name, 0, strrpos($name, '\\'));
			$receiver = new ast_name_expression($prefix, $start, $this->cursor->position() - 2, str_contains($prefix, '\\'));
		}
		return $this->call_arguments($start, $name, $member, $receiver);
	}

	/** Parse the common argument list after a function path or member name. */
	private function call_arguments(int $start /** uint32 */, string $name, bool $member, ?ast_expression $receiver): ast_call_expression
	{
		$this->cursor->take(token_kind::lparen, '(');
		$arguments = [];
		while (!$this->cursor->is(token_kind::rparen)) {
			$arguments[] = $this->value();
			if (!$this->cursor->is(token_kind::comma)) {
				break;
			}
			$this->cursor->take(token_kind::comma, ',');
			if ($this->cursor->is(token_kind::rparen)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Trailing argument comma is deferred');
			}
		}
		$this->cursor->take(token_kind::rparen, ')');
		return new ast_call_expression($start, $this->cursor->position(), $name, $member, $arguments, $receiver);
	}

	public function return_type(): ast_type_reference
	{
		return $this->type_reference(false, true);
	}

	/** All flavors share return syntax; analysis distinguishes void bodies from the integer entry. */
	private function return_statement(): ast_return_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('return');
		$value = $this->cursor->is(token_kind::semicolon) ? null : $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_return_statement($start, $end, $value);
	}

	/** Type keywords and identifiers share generated registration; preparation owns argument admission. */
	public function named_type(): ast_type_reference
	{
		return $this->type_reference();
	}

	/** Build immutable ordered argument chains without an intermediate array; commas belong to the containing type. */
	private function type_reference(bool $argument = false, bool $allow_void = false): ast_type_reference
	{
		$start = $this->cursor->position();
		if ($this->cursor->is(token_kind::question)) {
			$this->cursor->take(token_kind::question, '?');
			$payload = $this->type_reference(false, false);
			$end = $this->cursor->position();
			$next = null;
			if ($argument && $this->cursor->is(token_kind::comma)) {
				$this->cursor->take(token_kind::comma, ',');
				$next = $this->type_reference(true, true);
			}
			return new ast_type_reference($start, $end, 'nullable', $payload, $next);
		}
		$word = $this->cursor->tokens->text_at($start);
		if ($this->cursor->is(token_kind::keyword)
			&& ((type_registry::resolve($word) !== null) || ($allow_void && $word === 'void'))) {
			$this->cursor->take_keyword($word);
		}
		else {
			$word = $this->cursor->name(token_kind::identifier);
		}
		$child = null;
		if ($this->cursor->is(token_kind::less)) {
			$this->cursor->take(token_kind::less, '<');
			$child = $this->type_reference(true, true);
			$this->cursor->take(token_kind::greater, '>');
		}
		$end = $this->cursor->position();
		$next = null;
		if ($argument && $this->cursor->is(token_kind::comma)) {
			$this->cursor->take(token_kind::comma, ',');
			$next = $this->type_reference(true, true);
		}
		return new ast_type_reference($start, $end, $word, $child, $next);
	}

	/** Parse ordered comma-separated echo operands. */
	private function echo_statement(): ast_echo_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('echo');
		$values = [$this->value()];
		while ($this->cursor->is(token_kind::comma)) {
			$this->cursor->take(token_kind::comma, ',');
			$values[] = $this->value();
		}
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_echo_statement($start, $end, $values);
	}

	/** Parse logical, comparison and arithmetic precedence; grouping uses the same expression parser. */
	public function value(int $minimum_precedence /** uint8 */ = 0): ast_expression
	{
		$left = $this->primary();
		while (!$this->cursor->at_end()) {
			$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
			$operator = match ($kind) {
				token_kind::keyword => $this->cursor->keyword('xor') ? binary_operator::logical_xor : null,
				token_kind::logical_and => binary_operator::logical_and,
				token_kind::logical_or => binary_operator::logical_or,
				token_kind::plus => binary_operator::add,
			token_kind::dot => $this->cursor->tokens->source->flavor === flavor::php ? binary_operator::concatenate : null,
				token_kind::minus => binary_operator::subtract,
				token_kind::star => binary_operator::multiply,
				token_kind::slash => binary_operator::divide,
				token_kind::percent => binary_operator::remainder,
				token_kind::ampersand => binary_operator::bit_and,
				token_kind::pipe => binary_operator::bit_or,
				token_kind::caret => binary_operator::bit_xor,
				token_kind::power => binary_operator::power,
				token_kind::spaceship => binary_operator::compare,
				token_kind::identical => binary_operator::identical,
				token_kind::not_identical => binary_operator::not_identical,
				token_kind::equal => binary_operator::equal,
				token_kind::not_equal => binary_operator::not_equal,
				token_kind::less => binary_operator::less,
				token_kind::less_equal => binary_operator::less_equal,
				token_kind::greater => binary_operator::greater,
				token_kind::greater_equal => binary_operator::greater_equal,
				default => null,
			};
			$shift = (($kind === token_kind::less) || ($kind === token_kind::greater))
				? $this->cursor->tokens->operator_text_at($this->cursor->position()) : '';
			if ($shift === '<<' || $shift === '>>') {
				$operator = $shift === '<<' ? binary_operator::shift_left : binary_operator::shift_right;
			}
			if (($operator === null) || ($operator->precedence() < $minimum_precedence)) {
				break;
			}
			$this->cursor->take($kind, 'binary operator');
			if ($shift === '<<' || $shift === '>>') {
				$this->cursor->take($kind, 'shift operator');
			}
			$left = new ast_binary_expression($left, $operator, $this->value($operator->precedence() + ($operator === binary_operator::power ? 0 : 1)));
		}
		return $left;
	}

	/** Bounded scalar cast lookahead; preserve grouping when no unary operand follows, e.g. (null). */
	private function cast_ahead(): bool
	{
		$tokens = $this->cursor->tokens;
		$start = $this->cursor->position();
		if (!$this->cursor->is(token_kind::lparen)
			|| (($tokens->tokens[$start + 2]->kind ?? null) !== token_kind::rparen)) {
			return false;
		}
		$type = $tokens->text_at($start + 1);
		if ((type_registry::resolve($type) === null) && ($type !== 'void')) {
			return false;
		}
		$kind = $tokens->tokens[$start + 3]->kind ?? null;
		return in_array($kind, [token_kind::identifier, token_kind::variable, token_kind::keyword,
			token_kind::integer, token_kind::floating, token_kind::single_string, token_kind::double_string, token_kind::template_string,
			token_kind::lparen, token_kind::bang, token_kind::tilde, token_kind::backslash, token_kind::scope], true)
			|| ((($kind === token_kind::minus) || ($kind === token_kind::plus))
				&& in_array($tokens->tokens[$start + 4]->kind ?? null, [token_kind::integer, token_kind::floating], true));
	}

	/** Low-precedence xor requires grouping until value-producing assignments are implemented. */
	public function assignment_value(): ast_expression
	{
		$value = $this->value(1);
		if ($this->cursor->keyword('xor')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Parenthesize xor in an initializer or assignment RHS');
		}
		return $value;
	}

	/** Admit generated core scalar literals and flavor-correct names without resolving declarations. */
	private function primary(): ast_expression
	{
		$value = $this->atom();
		while (true) {
			if ($this->cursor->tokens->source->flavor !== flavor::php && $this->cursor->is(token_kind::dot)) {
				$this->cursor->take(token_kind::dot, '.');
				$name = $this->cursor->name(token_kind::identifier);
				$value = $this->call_arguments($value->start, $name, true, $value);
				continue;
			}
			if (!$this->cursor->is(token_kind::lbracket)) {
				break;
			}
			$this->cursor->take(token_kind::lbracket, '[');
			$index = $this->value();
			$end = $this->cursor->take(token_kind::rbracket, ']') + 1;
			$value = new ast_index_expression($value, $index, $end);
		}
		return $value;
	}

	/** Parse one primary or prefix expression before postfix indexing. */
	private function atom(): ast_expression
	{
		if (!$this->cursor->at_end() && ($this->cursor->tokens->text_at($this->cursor->position()) === 'isset')) {
			$start = $this->cursor->position();
			$this->cursor->take($this->cursor->tokens->tokens[$start]->kind, 'isset');
			$this->cursor->take(token_kind::lparen, '(');
			$operand = $this->value();
			$this->cursor->take(token_kind::rparen, ')');
			return new ast_isset_expression($start, $this->cursor->position(), $operand);
		}
		if ($this->cursor->is(token_kind::lbracket)) {
			$start = $this->cursor->take(token_kind::lbracket, '[');
			$end = $this->cursor->take(token_kind::rbracket, '] (only empty typed collection initializers are supported)') + 1;
			return new ast_empty_collection_expression($start, $end);
		}
		if ($this->cursor->is(token_kind::tilde) || (($this->cursor->is(token_kind::plus) || $this->cursor->is(token_kind::minus))
			&& !$this->cursor->next_is(token_kind::floating))) {
			$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
			$start = $this->cursor->take($kind, 'unary sign');
			$operator = match ($kind) {
				token_kind::plus => unary_operator::positive,
				token_kind::minus => unary_operator::negative,
				token_kind::tilde => unary_operator::bit_not,
			};
			$operand = $this->value(binary_operator::power->precedence());
			return new ast_unary_expression($start, $this->cursor->position(), $operator, $operand);
		}
		if ($this->cursor->is(token_kind::bang)) {
			$start = $this->cursor->take(token_kind::bang, '!');
			$operand = $this->value(binary_operator::power->precedence());
			return new ast_not_expression($start, $this->cursor->position(), $operand);
		}
		if ($this->cast_ahead()) {
			$start = $this->cursor->take(token_kind::lparen, '(');
			$type = $this->cursor->tokens->text_at($this->cursor->position());
			$type_start = $this->cursor->position();
			$this->cursor->take($this->cursor->tokens->tokens[$type_start]->kind, 'cast type');
			$type = new ast_type_reference($type_start, $this->cursor->position(), $type);
			$this->cursor->take(token_kind::rparen, ')');
			$operand = $this->value(binary_operator::power->precedence());
			return new ast_cast_expression($start, $this->cursor->position(), $type, $operand);
		}
		if ($this->cursor->is(token_kind::lparen)) {
			$this->cursor->take(token_kind::lparen, '(');
			$value = $this->value();
			$this->cursor->take(token_kind::rparen, ')');
			return $value;
		}
		if ($this->names->call_start()) {
			return $this->call();
		}
		$start = $this->cursor->position();
		if ($this->cursor->is(token_kind::single_string) || $this->cursor->is(token_kind::double_string)
			|| $this->cursor->is(token_kind::template_string)) {
			$kind = $this->cursor->tokens->tokens[$start]->kind;
			$this->cursor->take($kind, 'string literal');
			if ($kind === token_kind::template_string || ($kind === token_kind::double_string && $this->cursor->tokens->source->flavor === flavor::php)) {
				return Interpolation::parse($this->cursor->tokens, $start);
			}
			return new ast_string_expression($start, $start + 1);
		}
		if (($this->cursor->is(token_kind::minus) || $this->cursor->is(token_kind::plus))
			&& $this->cursor->next_is(token_kind::floating)) {
			$this->cursor->take($this->cursor->is(token_kind::minus) ? token_kind::minus : token_kind::plus, 'sign');
		}
		if ($this->cursor->is(token_kind::floating)) {
			$this->cursor->take(token_kind::floating, 'floating literal');
			return new ast_float_expression($start, $this->cursor->position());
		}
		if ($this->cursor->is(token_kind::integer)) {
			$position = $this->cursor->take(token_kind::integer, 'integer');
			return new ast_integer_expression($position, $position + 1);
		}
		$start = $this->cursor->position();
		$word = $this->cursor->at_end() ? '' : $this->cursor->tokens->text_at($start);
		if (type_registry::literal($word) !== null) {
			$kind = $this->cursor->tokens->tokens[$start]->kind;
			$this->cursor->take($kind, 'scalar literal');
			return new ast_keyword_expression($word, $start, $start + 1);
		}
		$constant_only = false;
		$name = $this->names->variable_path($constant_only);
		$member_syntax = ($this->cursor->tokens->source->flavor === flavor::js)
			&& (!str_starts_with($name, '\\')) && str_contains($name, '\\');
		return new ast_name_expression($name, $start, $this->cursor->position(), $member_syntax, $constant_only);
	}

	/** $start: token position. */
	public function declaration(int $start /** uint32 */, string $name, ?ast_type_reference $type_reference): ast_local_declaration
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->assignment_value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_local_declaration($start, $end, $name, $type_reference, $value);
	}

	/** Recognize discarded-value update syntax only at statement and header boundaries. */
	private function update_start(): bool
	{
		return $this->cursor->is(token_kind::increment) || $this->cursor->is(token_kind::decrement)
			|| $this->cursor->next_is(token_kind::increment) || $this->cursor->next_is(token_kind::decrement);
	}

	/** Parse one established simple-name update; expression-result use is deliberately not part of primary(). */
	private function update_statement(token_kind $terminator): ast_update_statement
	{
		$cursor = $this->cursor;
		$start = $cursor->position();
		$postfix = !$cursor->is(token_kind::increment) && !$cursor->is(token_kind::decrement);
		$kind = $cursor->tokens->tokens[$start + ($postfix ? 1 : 0)]->kind;
		if (!$postfix) {
			$cursor->take($kind, 'update operator');
		}
		$name = $cursor->name($cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier);
		if ($postfix) {
			$cursor->take($kind, 'update operator');
		}
		$end = $cursor->take($terminator, $terminator->label()) + 1;
		return new ast_update_statement($start, $end, $name,
			$kind === token_kind::increment ? update_operator::increment : update_operator::decrement, $postfix);
	}

	/** Inspect only assignment spelling; the shared map does not authorize operand types. */
	public function compound_operator(): ?binary_operator
	{
		$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind ?? null;
		if (($kind === token_kind::concat_assign) && ($this->cursor->tokens->source->flavor !== flavor::php)) {
			return null;
		}
		return $kind === null ? null : binary_operator::compound($kind);
	}

	/** $start: token position. */
	public function assignment(int $start /** uint32 */, string $name, assignment_intent $intent,
		token_kind $terminator = token_kind::semicolon): ast_assignment_statement
	{
		$operator = $this->compound_operator();
		$kind = $operator === null ? token_kind::assign : $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
		$this->cursor->take($kind, 'assignment operator');
		$value = $this->assignment_value();
		$end = $this->cursor->take($terminator, $terminator->label()) + 1;
		return new ast_assignment_statement($start, $end, $name, $value, $intent, $operator);
	}
}
