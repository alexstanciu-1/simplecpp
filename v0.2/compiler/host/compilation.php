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
		return ['locals' => $locals, 'references' => $references, 'functions' => $functions];
	}

	/** Report settled identities, value facts, explicit exit conversions and actual work counts. */
	public static function program(prepared_entry $program): array /** array<string,mixed> */
	{
		$result = self::body($program);
		$result['native_int_digits'] = $program->target->digits;
		$result['functions'] = [];
		foreach ($program->functions as $function) {
			$result['functions'][] = ['name' => $function->declaration->name, 'return_type' => $function->declaration->return_type_def()->name,
				'body' => self::body($function->body)];
		}
		return $result;
	}

	/** Body views report resolved callable contracts without traversing another function's body. */
	private static function body(prepared_body $program): array /** array<string,mixed> */
	{
		$statements = [];
		foreach ($program->statements as $statement)
		{
			if ($statement instanceof prepared_echo) {
				$statements[] = ['kind' => 'echo', 'value' => $statement->value instanceof prepared_output_string
					? ['type' => 'string_literal', 'bytes_hex' => bin2hex($statement->value->bytes)]
					: self::value($statement->value)];
			}
			elseif ($statement instanceof prepared_declaration) {
				$statements[] = ['kind' => 'local', 'id' => $statement->binding->id,
					'type' => $statement->binding->type_def()->name, 'initializer' => self::value($statement->initializer)];
			}
			elseif ($statement instanceof prepared_return) {
				$statements[] = ['kind' => 'return', 'conversion' => $statement->conversion->name,
					'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof prepared_void_return) {
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
	private static function value(prepared_value $value): array /** array<string,mixed> */
	{
		$row = ['type' => $value->type->name,
			'constant' => $value->constant === null ? null : (string) $value->constant];
		if ($value instanceof prepared_reference) {
			$row['binding'] = $value->binding->id;
		}
		return $row;
	}
}
