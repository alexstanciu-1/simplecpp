<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Requested lowering of one prepared free function. The file revision includes dependent layout edits. */
final class Destination_Plan
{
	/** PROPERTIES */
	/** @var \SplObjectStorage<ast_statement,list<ast_field_assignment>> Empty values suppress the original statement. */
	private readonly \SplObjectStorage $sites;
	private readonly int $revision /** uint32 */;

	/** CONSTRUCTOR */
	private function __construct(private readonly ast_function_body $body)
	{
		$this->revision = $body->root()->prepared_revision();
		$this->sites = new \SplObjectStorage();
		if (!($body->parent->return_type_def() instanceof generator_type_definition)) $this->visit($body);
	}

	/** METHODS */
	/** @return ast_field_assignment[]|null Null means ordinary lowering. */
	public function writes(ast_statement $statement): ?array
	{
		return $this->sites[$statement] ?? null;
	}

	/** Visit nested lexical blocks only in the requested body. */
	private function visit(ast_statement_block $block): void
	{
		$statements = $block->statements;
		$append = $statements[array_key_last($statements)] ?? null;
		if ($append instanceof ast_append_statement) $this->candidate($statements, $append);
		foreach ($statements as $statement) {
			if ($statement instanceof ast_loop_statement || $statement instanceof ast_foreach_statement) $this->visit($statement->body);
			if ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) $this->visit($arm->body);
			}
		}
	}

	/** Admit one terminal contiguous group, so the fresh local cannot escape or be used later. @param ast_statement[] $statements */
	private function candidate(array $statements /** list<ast_statement> */, ast_append_statement $append): void
	{
		if (!($append->receiver instanceof ast_name_expression)
			|| !($append->receiver->declaration() instanceof ast_local_declaration)
			|| !($append->receiver->type_def() instanceof destination_append_type)
			|| !($append->value instanceof ast_name_expression)
			|| $append->conversion() !== value_conversion::identity) return;
		$local = $append->value->declaration();
		$type = $append->value->type_def();
		if (!($local instanceof ast_local_declaration) || !($local->initializer instanceof ast_struct_default)
			|| $local->initializer->arguments !== [] || !($type instanceof record_type_definition)
			|| $append->receiver->type_def()->write_type() !== $type) return;
		$record = $type->declaration;
		if ($record->kind !== record_kind::structure || $record->methods !== [] || $record->base_reference !== null
			|| $record->interface_references !== [] || $record->fields === []) return;
		$start = count($statements) - count($record->fields) - 2;
		if (($statements[$start] ?? null) !== $local) return;
		$writes = [];
		foreach ($record->fields as $field) {
			$write = $statements[++$start];
			if ($field->type_def() !== type_registry::integer() || $field->initializer !== null || $field->is_private
				|| $field->is_protected || $field->is_readonly || !($write instanceof ast_field_assignment)
				|| !($write->target instanceof ast_field_expression) || $write->target->field !== $field->short_name
				|| !($write->target->receiver instanceof ast_name_expression) || !$write->target->receiver->refers_to($local)
				|| $write->conversion !== value_conversion::identity || !self::scalar_input($write->value)) return;
			$writes[] = $write;
		}
		$this->sites[$append] = $writes;
		foreach ([$local, ...$writes] as $statement) $this->sites[$statement] = [];
	}

	/** STATIC METHODS */
	/** Select by qualified source name. Reuse settled plans, including candidate misses.
	 * @param string[] $names @param array<string,array<string,self>> $previous
	 * @return array<string,array<string,self>> Keyed by source path and function name.
	 */
	public static function select(prepared_program $program, array $names, array $previous = []): array
	{
		if ($names === []) return [];
		$symbols = $program->entry->file->scope->parent;
		$selected = [];
		sort($names, \SORT_STRING);
		foreach (array_unique($names) as $name) {
			$function = $symbols?->resolve('func:' . $name);
			if (!($function instanceof ast_function_declaration) || ($body = $function->optional_body()) === null) continue;
			$path = $function->dependency_source_key();
			$prior = $previous[$path][$name] ?? null;
			$selected[$path][$name] = $prior !== null && $prior->body === $body
				&& $prior->revision === $body->root()->prepared_revision() ? $prior : new self($body);
		}
		return $selected;
	}

	private static function scalar_input(ast_expression $value): bool
	{
		if ($value->type_def() !== type_registry::integer()) return false;
		if ($value instanceof ast_name_expression) return $value->declaration() instanceof ast_local_declaration;
		return $value instanceof ast_binary_expression && $value->operator === binary_operator::subtract
			&& $value->left_conversion === value_conversion::identity && $value->right_conversion === value_conversion::identity
			&& self::scalar_input($value->left) && self::scalar_input($value->right);
	}
}
