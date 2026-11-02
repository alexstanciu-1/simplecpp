<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Transient JSON views expose stage contracts without putting serialization on retained records. */
final class Compilation_Inspection
{
	/** Collected references deliberately have no resolved binding or type. */
	public static function collection(ast_file_node $file): array /** array<string,mixed> */
	{
		$locals = [];
		foreach (($file->main()?->body()->locals ?? []) as $position => $local) {
			$locals[] = ['id' => $local->id, 'statement' => $position, 'name' => $local->name];
		}
		$references = [];
		foreach (($file->main()?->body()->references ?? []) as $reference) {
			$references[] = ['name' => $reference->name, 'offset' => $reference->byte_start()];
		}
		$functions = [];
		foreach ($file->functions() as $id => $function) {
			$functions[] = ['id' => $id, 'name' => $function->name,
				'return_type' => $function->return_type_name,
				'body_locals' => count($function->body()->locals)];
		}
		$result = ['locals' => $locals, 'references' => $references, 'functions' => $functions];
		if ($file->diagnostics !== []) {
			$result['diagnostics'] = array_map(static fn(diagnostic $error): string => $error->getMessage(), $file->diagnostics);
		}
		return $result;
	}

	/** Report settled identities, value facts, explicit exit conversions and actual work counts. */
	public static function program(prepared_entry $program): array /** array<string,mixed> */
	{
		$result = self::body($program->body());
		$result['native_int_digits'] = $program->target->digits;
		$result['functions'] = [];
		foreach ($program->file->functions() as $function) {
			$result['functions'][] = ['name' => $function->name, 'return_type' => $function->return_type_def()->name,
				'body' => self::body($function->body())];
		}
		return $result;
	}

	/** Body views report resolved callable contracts without traversing another function's body. */
	public static function body(ast_function_body $program): array /** array<string,mixed> */
	{
		$program->require_prepared();
		$statements = [];
		foreach ($program->statements as $statement)
		{
			if ($statement instanceof ast_echo_statement) {
				$statements[] = ['kind' => 'echo', 'value' => $statement->value instanceof ast_string_expression
					? ['type' => 'string_literal', 'bytes_hex' => bin2hex($statement->value->bytes())]
					: self::value($statement->value)];
			}
			elseif ($statement instanceof ast_local_declaration) {
				$statements[] = ['kind' => 'local', 'id' => $statement->id,
					'type' => $statement->type_def()->name, 'initializer' => self::value($statement->initializer)];
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$statements[] = ['kind' => 'return', 'conversion' => $statement->conversion()->name,
					'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof ast_return_statement) {
				$statements[] = ['kind' => 'return', 'value' => null];
			}
			elseif ($statement instanceof ast_call_statement) {
				$statements[] = ['kind' => 'call', 'target' => $statement->function_def()->name,
					'return_type' => $statement->function_def()->return_type_def()->name];
			}
		}
		return ['statements' => $statements,
			'work' => ['statements' => $program->statement_visits, 'expressions' => $program->expression_visits,
				'name_lookups' => $program->name_lookups]];
	}

	/** Constants use decimal JSON strings so inspection does not lose int64 precision in consumers. */
	private static function value(ast_expression $value): array /** array<string,mixed> */
	{
		$row = ['type' => $value->type_def()->name,
			'constant' => $value->constant() === null ? null : (string) $value->constant()];
		if ($value instanceof ast_name_expression) {
			$row['binding'] = $value->local_def()->id;
		}
		return $row;
	}
}
