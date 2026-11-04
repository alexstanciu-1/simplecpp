<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Parser extension point for flavor-specific statements and function headers; all flavors produce the same AST families.
 */
interface statement_grammar {
	public function statement(Parser $parser): ast_statement;
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
			if ($this->cursor->keyword('global'))
			{
				$global = Global_Syntax::read($this);
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
			$statement = $this->statement($grammar);
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
				'int', $body_start, $body_start, true, $namespace);
			$main->attach($this->root);
			$main->complete_body($collector->body($body_start, $body_end, $statements));
			$declarations->import($main);
			array_unshift($nodes, $main);
		}
		/** @var (ast_function_declaration|ast_global_declaration)[] $heads */
		$heads = $declarations->publish_references()->heads();
		if (isset($heads['func:main'])) {
			$heads = ['func:main' => $heads['func:main']] + $heads;
		}
		$this->root->complete($heads, $nodes, Declaration_Diagnostics::file($nodes), $imports);
		return $this->root;
	}

	/** Share body grammar while keeping function definitions out of executable statement inventories. */
	private function statement(statement_grammar $grammar): ast_statement
	{
		if ($this->cursor->keyword('global')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Global declarations are only allowed at file/namespace level');
		}
		if ($this->cursor->keyword('namespace') || $this->cursor->keyword('export') || $this->cursor->keyword('use')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Nested/additional namespaces, body-local use and export are not supported in this slice');
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
		if ($this->names->call_start())
		{
			$start = $this->cursor->position();
			$name = $this->names->path(true);
			$this->cursor->take(token_kind::lparen, '(');
			$this->cursor->take(token_kind::rparen, ') (calls take no arguments in this slice)');
			$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
			$member_syntax = ($this->cursor->tokens->source->flavor === flavor::js)
				&& (!str_starts_with($name, '\\')) && str_contains($name, '\\');
			return new ast_call_statement($start, $end, $name, $member_syntax);
		}
		return $grammar->statement($this);
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
		$body_collector = new Body_Collection($this->root->scope);
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
			$start, $this->cursor->position(), false, $this->root->scope->namespace);
	}

	public function source_namespace(): ?string
	{
		return $this->root->scope->namespace;
	}

	public function empty_parameters(): void
	{
		$this->cursor->take(token_kind::lparen, '(');
		$this->cursor->take(token_kind::rparen, ') (functions take no parameters in this slice)');
	}

	public function return_type(): string
	{
		if ($this->cursor->keyword('void')) {
			$this->cursor->take(token_kind::keyword, 'void');
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

	/** Type keywords and identifiers share generated registration; unknown identifiers remain diagnosable later. */
	public function named_type(): string
	{
		if ($this->cursor->is(token_kind::keyword)) {
			$word = $this->cursor->tokens->text_at($this->cursor->position());
			if (type_registry::resolve($word) !== null) {
				$this->cursor->take_keyword($word);
				return $word;
			}
		}
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

	/** Admit generated core scalar literals and flavor-correct names without resolving declarations. */
	public function value(): ast_expression
	{
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
		$name = $this->names->variable_path();
		$member_syntax = ($this->cursor->tokens->source->flavor === flavor::js)
			&& (!str_starts_with($name, '\\')) && str_contains($name, '\\');
		return new ast_name_expression($name, $start, $this->cursor->position(), $member_syntax);
	}

	/** $start: token position. */
	public function declaration(int $start /** uint32 */, string $name, ?string $type_name): ast_local_declaration
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_local_declaration($start, $end, $name, $type_name, $value);
	}

	/** $start: token position. */
	public function assignment(int $start /** uint32 */, string $name, assignment_intent $intent): ast_assignment_statement
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_assignment_statement($start, $end, $name, $value, $intent);
	}
}
