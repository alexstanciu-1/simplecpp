<?php

declare(strict_types=1);
namespace scpp\compiler;

/** CLI inspection owns presentation; syntax records do not depend on host serialization. */
final class Syntax_Inspection
{
	/**
	 * Inspect executable statements separately from source-owned function definitions.
	 *
	 * @return mixed[]
	 */
	public static function file(ast_file_node $file): array /** array<string,mixed> */
	{
		$rows = [];
		foreach (($file->main()?->body()->statements ?? []) as $statement) {
			$rows[] = self::statement($statement);
		}
		$functions = [];
		foreach ($file->functions() as $function)
		{
			if ($function->is_private) {
				continue;
			}
			$body = array_map(self::statement(...), $function->body()->statements);
			$functions[] = ['name' => self::name($function, $function->name, $function->name_position()),
				'return_type' => self::name($function, $function->return_type_name, $function->return_type_position()),
				'body' => $body, 'header' => ['start' => $function->byte_start(), 'end' => $function->root()->byte_at($function->header_end)],
				'body_span' => self::span($function->body())];
		}
		$result = ['flavor' => $file->tokens->source->flavor->value, 'statements' => $rows, 'functions' => $functions];
		if ($file->diagnostics !== []) {
			$result['diagnostics'] = array_map(static fn(diagnostic $error): string => $error->getMessage(),
				$file->diagnostics);
		}
		return $result;
	}

	/** @return mixed[] */
	private static function span(ast_node $span): array /** array{start:uint32,end:uint32} */
	{
		return ['start' => $span->byte_start(), 'end' => $span->byte_end()];
	}

	/**
	 * $position: token position.
	 *
	 * @return mixed[]
	 */
	private static function name(ast_node $owner, string $name, int $position /** uint32 */): array /** array{text:string,span:array{start:uint32,end:uint32}} */
	{
		$token = $owner->root()->tokens->tokens[$position];
		return ['text' => $name, 'span' => ['start' => $token->offset, 'end' => $token->offset + $token->length]];
	}

	/**
	 * Serialize the supported expression shape without consuming prepared facts.
	 *
	 * Return: tagged expression JSON fields.
	 *
	 * @return mixed[]
	 */
	private static function expression(ast_expression $expression): array /** array<string,mixed> */
	{
		if ($expression instanceof ast_string_expression) {
			return ['kind' => 'string', 'spelling_hex' => bin2hex($expression->source_text()), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_integer_expression) {
			return ['kind' => 'integer', 'spelling' => $expression->source_text(), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_name_expression) {
			return ['kind' => 'name', 'name' => self::name($expression, $expression->name, $expression->start),
				'span' => self::span($expression)];
		}
		throw new \LogicException('Unhandled expression inspection');
	}

	/**
	 * Serialize each supported statement and its syntax children for inspection.
	 *
	 * Return: tagged statement JSON fields.
	 *
	 * @return mixed[]
	 */
	private static function statement(ast_statement $statement): array /** array<string,mixed> */
	{
		if ($statement instanceof ast_echo_statement) {
			return ['kind' => 'echo', 'value' => self::expression($statement->value), 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_return_statement) {
			return ['kind' => 'return', 'value' => $statement->value === null ? null : self::expression($statement->value),
				'span' => self::span($statement)];
		}
		if ($statement instanceof ast_call_statement) {
			return ['kind' => 'call', 'name' => self::name($statement, $statement->name, $statement instanceof ast_local_declaration ? $statement->name_position() : $statement->start),
				'span' => self::span($statement)];
		}
		if ($statement instanceof ast_local_declaration) {
			return ['kind' => 'local', 'name' => self::name($statement, $statement->name, $statement instanceof ast_local_declaration ? $statement->name_position() : $statement->start),
				'type' => $statement->type_name === null ? null : self::name($statement, $statement->type_name,
					$statement->type_position()),
				'initializer' => self::expression($statement->initializer), 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_assignment_statement) {
			return ['kind' => 'assignment', 'name' => self::name($statement, $statement->name, $statement instanceof ast_local_declaration ? $statement->name_position() : $statement->start),
				'intent' => $statement->intent->name, 'value' => self::expression($statement->value),
				'span' => self::span($statement)];
		}
		throw new \LogicException('Unhandled statement inspection');
	}
}
