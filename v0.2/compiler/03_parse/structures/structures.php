<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A checked half-open source interval; no token spelling or decoded value is copied. */
final readonly class syntax_span
{
	public source_file $source;
	public int $start;
	public int $end;

	public function __construct(source_file $source, int $start, int $end)
	{
		$this->source = $source;
		$this->start = $start;
		$this->end = $end;

		if (($start < 0) || ($end < $start) || ($end > strlen($source->content))) {
			throw new \InvalidArgumentException('Syntax span is outside its source');
		}
	}

	public function text(): string
	{
		return substr($this->source->content, $this->start, $this->end - $this->start);
	}
}

/** Logical name and original spelling remain distinct, notably for PHP's leading dollar. */
final readonly class syntax_name
{
	public string $text;
	public syntax_span $span;

	public function __construct(string $text, syntax_span $span)
	{
		$this->text = $text;
		$this->span = $span;
	}
}

/** A written type name, not a resolved semantic type. */
final readonly class named_type_syntax
{
	public syntax_name $name;

	public function __construct(syntax_name $name)
	{
		$this->name = $name;
	}
}

abstract readonly class syntax_expression
{
	public syntax_span $span;

	public function __construct(syntax_span $span)
	{
		$this->span = $span;
	}
}

/** Analysis selects the default type and checks range; parsing retains exact decimal spelling. */
final readonly class integer_expression extends syntax_expression {}

final readonly class name_expression extends syntax_expression
{
	public syntax_name $name;

	public function __construct(syntax_name $name)
	{
		$this->name = $name;

		parent::__construct($name->span);
	}
}

enum assignment_intent: int
{
	case existing_binding = 1;
	case declare_or_assign = 2;
}

abstract readonly class syntax_statement
{
	public syntax_span $span;

	public function __construct(syntax_span $span)
	{
		$this->span = $span;
	}
}

final readonly class local_declaration extends syntax_statement
{
	public syntax_name $name;
	public ?named_type_syntax $annotation;
	public syntax_expression $initializer;

	public function __construct(syntax_span $span, syntax_name $name,
		?named_type_syntax $annotation, syntax_expression $initializer)
	{
		$this->name = $name;
		$this->annotation = $annotation;
		$this->initializer = $initializer;

		parent::__construct($span);
	}
}

final readonly class assignment_statement extends syntax_statement
{
	public syntax_name $name;
	public syntax_expression $value;
	public assignment_intent $intent;

	public function __construct(syntax_span $span, syntax_name $name,
		syntax_expression $value, assignment_intent $intent)
	{
		$this->name = $name;
		$this->value = $value;
		$this->intent = $intent;

		parent::__construct($span);
	}
}

/** Complete syntax publication retains the token generation and its per-file flavor. */
final readonly class parsed_file
{
	public token_list $tokens;
	public array $statements;

	/** @param list<syntax_statement> $statements */
	public function __construct(token_list $tokens, array $statements)
	{
		$this->tokens = $tokens;
		$this->statements = $statements;

		if (!array_is_list($statements)) {
			throw new \InvalidArgumentException('Statements must be a list');
		}
		$end = 0;
		foreach ($statements as $statement) {
			if (!($statement instanceof syntax_statement) || ($statement->span->source !== $tokens->source)
				|| ($statement->span->start < $end)) {
				throw new \InvalidArgumentException('Statements must be ordered and belong to the retained source');
			}
			$end = $statement->span->end;
		}
	}
}
