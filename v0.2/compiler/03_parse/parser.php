<?php

declare(strict_types=1);
namespace scpp\compiler;

interface statement_grammar
{
	public function statement(Parser $parser): syntax_statement;
}

/** Shared syntax construction; flavor grammars select declaration and assignment intent. */
final class Parser
{
	public readonly Syntax_Cursor $cursor;

	public function __construct(token_list $tokens)
	{
		$this->cursor = new Syntax_Cursor($tokens);
	}

	/** Parsing publishes a complete file or throws a source-located diagnostic. */
	public function parse(): parsed_file
	{
		$grammar = match ($this->cursor->tokens->source->flavor) {
			flavor::cpp => new Cpp_Grammar(),
			flavor::php => new Php_Grammar(),
			flavor::js => new Js_Grammar(),
		};
		$statements = [];
		while (!$this->cursor->at_end()) {
			$statements[] = $grammar->statement($this);
		}
		return new parsed_file($this->cursor->tokens, $statements);
	}

	public function named_type(): named_type_syntax
	{
		return new named_type_syntax($this->cursor->name(token_kind::identifier));
	}

	/** This first expression slice admits only integer literals and flavor-correct names. */
	public function value(): syntax_expression
	{
		if ($this->cursor->is(token_kind::integer)) {
			return new integer_expression($this->cursor->take(token_kind::integer, 'integer'));
		}
		$kind = $this->cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
		if ($this->cursor->is($kind)) {
			return new name_expression($this->cursor->name($kind));
		}
		throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected integer or name');
	}

	public function declaration(int $start /** uint32 */, syntax_name $name, ?named_type_syntax $annotation): local_declaration
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';')->end;
		return new local_declaration(new syntax_span($this->cursor->tokens->source, $start, $end), $name, $annotation, $value);
	}

	public function assignment(int $start /** uint32 */, syntax_name $name, assignment_intent $intent): assignment_statement
	{
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';')->end;
		return new assignment_statement(new syntax_span($this->cursor->tokens->source, $start, $end), $name, $value, $intent);
	}
}
