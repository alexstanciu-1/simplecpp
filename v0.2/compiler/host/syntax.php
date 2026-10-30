<?php

declare(strict_types=1);
namespace scpp\compiler;

/** CLI inspection owns presentation; syntax records do not depend on host serialization. */
final class Syntax_Inspection
{
	public static function file(parsed_file $file): array /** array{flavor:string,statements:list<array<string,mixed>>} */
	{
		$rows = [];
		foreach ($file->statements as $statement) {
			$rows[] = self::statement($statement);
		}
		return ['flavor' => $file->tokens->source->flavor->value, 'statements' => $rows];
	}

	private static function span(syntax_span $span): array /** array{start:uint32,end:uint32} */
	{
		return ['start' => $span->start, 'end' => $span->end];
	}

	private static function name(syntax_name $name): array /** array{text:string,span:array{start:uint32,end:uint32}} */
	{
		return ['text' => $name->text, 'span' => self::span($name->span)];
	}

	private static function expression(syntax_expression $expression): array /** array<string,mixed>; tagged expression JSON fields */
	{
		if ($expression instanceof integer_expression) {
			return ['kind' => 'integer', 'spelling' => $expression->span->text(), 'span' => self::span($expression->span)];
		}
		if ($expression instanceof name_expression) {
			return ['kind' => 'name', 'name' => self::name($expression->name), 'span' => self::span($expression->span)];
		}
		throw new \LogicException('Unhandled expression inspection');
	}

	private static function statement(syntax_statement $statement): array /** array<string,mixed>; tagged statement JSON fields */
	{
		if ($statement instanceof local_declaration) {
			return ['kind' => 'local', 'name' => self::name($statement->name),
				'type' => $statement->annotation === null ? null : self::name($statement->annotation->name),
				'initializer' => self::expression($statement->initializer), 'span' => self::span($statement->span)];
		}
		if ($statement instanceof assignment_statement) {
			return ['kind' => 'assignment', 'name' => self::name($statement->name),
				'intent' => $statement->intent->name, 'value' => self::expression($statement->value),
				'span' => self::span($statement->span)];
		}
		throw new \LogicException('Unhandled statement inspection');
	}
}
