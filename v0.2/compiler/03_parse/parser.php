<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Parser extension point for flavor-specific statements and function headers; all flavors produce the same AST families.
 */
interface statement_grammar
{
	public function statement(Parser $parser): ast_statement;
	public function function_start(Parser $parser): bool;
	public function function_header(Parser $parser): ast_function_declaration;
}

/** Shared syntax construction; flavor grammars select declaration and assignment intent. */
final class Parser
{
	public readonly Syntax_Cursor $cursor;
	private bool $started = false;
	private ast_file_node $root;
	private ?declaration_sink $sink;

	public function __construct(token_list $tokens, ?declaration_sink $sink = null)
	{
		$this->cursor = new Syntax_Cursor($tokens);
		$this->root = new ast_file_node($tokens);
		$this->sink = $sink;
	}

	/** Own one source parse and its local collection; failure exposes neither partial result. */
	public function parse(): ast_file_node
	{
		if ($this->started) {
			throw new \LogicException('A parser instance processes one source once');
		}
		$this->started = true;
		$collector = new Symbol_Collector();
		$grammar = match ($this->cursor->tokens->source->flavor) {
			flavor::cpp => new Cpp_Grammar(),
			flavor::php => new Php_Grammar(),
			flavor::js => new Js_Grammar(),
		};
		$statements = [];
		$functions = [];
		while (!$this->cursor->at_end())
		{
			if ($grammar->function_start($this)) {
				$function = $this->function_declaration($grammar, $functions);
				$functions['func:' . $function->declaration_key()] = $function;
				continue;
			}
			$statement = $this->statement($grammar);
			$collector->statement($statement, count($statements));
			$statements[] = $statement;
		}
		if ($statements !== []) {
			$main = new ast_function_declaration('main',
				'int', 0, 0, true);
			$main->attach($this->root);
			$main->complete_body($collector->body(0, count($this->cursor->tokens->tokens), $statements));
			$functions = ['func:main' => $main] + $functions;
		}
		$this->root->complete($functions);
		return $this->root;
	}

	/** Share body grammar while keeping function definitions out of executable statement inventories. */
	private function statement(statement_grammar $grammar): ast_statement
	{
		if ($this->cursor->keyword('echo')) {
			return $this->echo_statement();
		}
		if ($grammar->function_start($this)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Nested functions are not supported');
		}
		if ($this->cursor->keyword('return')) {
			return $this->return_statement();
		}
		if ($this->cursor->is(token_kind::identifier) && $this->cursor->next_is(token_kind::lparen))
		{
			$start = $this->cursor->position();
			$name = $this->cursor->name(token_kind::identifier);
			$this->cursor->take(token_kind::lparen, '(');
			$this->cursor->take(token_kind::rparen, ') (calls take no arguments in this slice)');
			$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
			return new ast_call_statement($start, $end, $name);
		}
		return $grammar->statement($this);
	}

	/** Discover the stable header before parsing its body; local collection never enters the global index. */
	private function function_declaration(statement_grammar $grammar, array $declarations /** array<string,ast_function_declaration> */): ast_function_declaration
	{
		$function = $grammar->function_header($this);
		$function->attach($this->root);
		$source = $this->cursor->tokens->source;
		if ($function->name === 'main') {
			throw new diagnostic($source, $this->root->byte_at($function->name_position()), 'Explicit main is not supported yet');
		}
		if (isset($declarations['func:' . $function->declaration_key()])) {
			throw new diagnostic($source, $this->root->byte_at($function->name_position()), 'Duplicate function: ' . $function->name);
		}
		$this->sink?->discovered($function);
		$body_start = $this->cursor->take(token_kind::lbrace, '{');
		$body_collector = new Symbol_Collector();
		$statements = [];
		while (!$this->cursor->is(token_kind::rbrace))
		{
			if ($this->cursor->at_end()) {
				throw new diagnostic($source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar);
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
		$this->empty_parameters();
		$this->cursor->take(token_kind::colon, ': (explicit return type)');
		$type = $this->return_type();
		return new ast_function_declaration($name, $type,
			$start, $this->cursor->position());
	}

	public function empty_parameters(): void
	{
		$this->cursor->take(token_kind::lparen, '(');
		$this->cursor->take(token_kind::rparen, ') (functions take no parameters in this slice)');
	}

	public function return_type(): string
	{
		if ($this->cursor->keyword('void')) {
			$start = $this->cursor->take(token_kind::keyword, 'void');
			return 'void';
		}
		return $this->named_type();
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

	public function named_type(): string
	{
		return $this->cursor->name(token_kind::identifier);
	}

	/** Keep string admission bounded to echo while preserving the ordinary integer/name value path. */
	private function echo_statement(): ast_echo_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('echo');
		if ($this->cursor->is(token_kind::single_string) || $this->cursor->is(token_kind::double_string)) {
			$kind = $this->cursor->is(token_kind::single_string) ? token_kind::single_string : token_kind::double_string;
			$position = $this->cursor->take($kind, 'string literal');
			$value = new ast_string_expression($position, $position + 1);
		}
		else {
			$value = $this->value();
		}
		$end = $this->cursor->take(token_kind::semicolon, '; (echo currently accepts one operand)') + 1;
		return new ast_echo_statement($start, $end, $value);
	}

	/** This first expression slice admits only integer literals and flavor-correct names. */
	public function value(): ast_expression
	{
		if ($this->cursor->is(token_kind::integer)) {
			$position = $this->cursor->take(token_kind::integer, 'integer');
			return new ast_integer_expression($position, $position + 1);
		}
		$kind = $this->cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
		if ($this->cursor->is($kind)) {
			$start = $this->cursor->position();
			return new ast_name_expression($this->cursor->name($kind), $start, $this->cursor->position());
		}
		throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected integer or name');
	}

	public function declaration(int $start /** uint32; token position */, string $name, ?string $type_name): ast_local_declaration
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_local_declaration($start, $end, $name, $type_name, $value);
	}

	public function assignment(int $start /** uint32; token position */, string $name, assignment_intent $intent): ast_assignment_statement
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_assignment_statement($start, $end, $name, $value, $intent);
	}
}
