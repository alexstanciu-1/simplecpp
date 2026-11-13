<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Spells the already-selected entry exit conversion through the public runtime boundary. */
final class Cpp_Exit
{
	/** Rendering follows the settled conversion; it never infers or validates source semantics. */
	public static function expression(exit_conversion $conversion, string $value): string
	{
		return match ($conversion) {
			exit_conversion::proven => 'static_cast<int>(' . $value . ')',
				exit_conversion::checked => Cpp_Entry_Binding::checked($value),
		};
	}
}

/**
 * Lowers completed bodies to C++ using settled declaration links and value facts, without performing name resolution.
 */
final class Cpp_Emitter
{
	/** PROPERTIES */
	/** Native non-owning; null keeps standalone single-file emission self-contained. */
	private readonly ?Cpp_Headers $headers;
	private readonly string $root;
	/** Set while lowering a function that calls a generated flavor adapter. */
	private ?string $adapter_flavor = null;

	/** CONSTRUCTOR */
	public function __construct(?Cpp_Headers $headers = null, string $root = 'App')
	{
		$this->headers = $headers;
		$this->root = $root;
	}

	/** METHODS */
	/** Share declaration spelling between local prototypes and generated headers. @param bool[] $includes */
	public function declaration(ast_function_declaration|ast_global_declaration $node,
		array &$includes /** array<string,bool> */): string
	{
		if ($node instanceof ast_function_declaration) {
			return $this->signature($node, $includes) . ';';
		}
		foreach ($node->type_def()->cpp_headers() as $header) {
			$includes[$header] = true;
		}
		return 'extern ' . $this->signature_type($node->type_def(), false) . ' ' . self::global_name($node) . ';';
	}

	/** Only a completed semantic program is accepted; output is assembled privately before publication. */
	public function emit(prepared_entry $program): string
	{
		return $this->entry_body($program) . Cpp_Program::main_adapter($this->root);
	}

	public function entry_body(prepared_entry $program): string
	{
		return $this->render($program, 'scpp_entry_body');
	}

	/** Emit this file's settled function and global definitions, including declaration-only source files. */
	public function source_definitions(ast_file_node $file): string
	{
		$file->prepared_revision();
		if ($file->published_declarations() === []) {
			return Cpp_Program::empty_unit();
		}
		return $this->unit(null, $file, '');
	}

	private function render(prepared_entry $program, string $function): string
	{
		$program->require_prepared();
		return $this->unit($program, $program->file, $function);
	}

	/** Assemble local definitions and deterministic forward declarations without a shared naming registry. */
	private function unit(?prepared_entry $entry, ast_file_node $file, string $entry_name): string
	{
		$declarations = [];
		$definitions = [];
		$global_references = [];
		$required_headers = [];
		foreach ($file->globals() as $global) {
			$type = $global->type_def();
			$initializer = self::plain_integer_literal($global->initializer, $global->initialization())
				?? $type->lower_cpp_conversion($global->initialization(),
					$global->initializer->type_def()->lower_cpp_literal($global->initializer));
			$definitions[] = 'constinit ' . $type->cpp_type() . ' ' . self::global_name($global)
				. ' = ' . $initializer . ";\n";
			foreach (array_merge($type->cpp_headers(), $type->conversion_headers($global->initialization())) as $header) {
				$required_headers[$header] = true;
			}
		}
		$unit_flavor = null;
		foreach ($file->functions() as $function) {
			$name = self::function_name($function);
			$declarations[$name] = $function;
			$this->adapter_flavor = null;
			$lines = $this->statements($function->body(), $declarations, $global_references, $required_headers);
			$unit_flavor ??= $this->adapter_flavor;
			$definitions[] = $this->signature($function, $required_headers, $this->adapter_flavor !== null && $this->root !== '')
				. "\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		if ($entry !== null) {
			$this->adapter_flavor = null;
			$lines = $this->statements($entry->body(), $declarations, $global_references, $required_headers);
			$unit_flavor ??= $this->adapter_flavor;
			$definitions[] = 'int ' . $entry_name . "()\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		$prototypes = '';
		$unit_headers = [];
		ksort($declarations, \SORT_STRING);
		foreach ($declarations as $function) {
			$include = $this->headers?->declaration_header($function);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
			else {
				$prototypes .= $this->declaration($function, $required_headers) . "\n";
			}
		}
		ksort($global_references, \SORT_STRING);
		foreach ($global_references as $name => $global) {
			$include = $this->headers?->declaration_header($global);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
			else {
				$prototypes .= $this->declaration($global, $required_headers) . "\n";
			}
		}
		foreach ($file->globals() as $global) {
			$include = $this->headers?->declaration_header($global);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
		}
		$header = "// Generated from completed source-owned bodies.\n";
		if ($entry !== null) {
			$header .= '#include <' . Cpp_Entry_Binding::header() . ">\n";
			$header .= 'SCPP_REQUIRE_ENTRY_TARGET(' . $entry->target->digits . ");\n\n";
		}
		ksort($required_headers, \SORT_STRING);
		foreach ($required_headers as $include => $unused) {
			$header .= '#include <' . $include . ">\n";
		}
		ksort($unit_headers, \SORT_STRING);
		foreach ($unit_headers as $include => $_) {
			$header .= '#include "' . $include . "\"\n";
		}
		$imports = $unit_flavor !== null && $this->root !== '' ? self::imports($unit_flavor) : '';
		return $header . self::namespace_body($this->root, $imports . $prototypes . implode("\n", $definitions));
	}

	/**
	 * Render the admitted test through its generated type handler; no backend type admission.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function condition(ast_expression $condition, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		$type = $condition->type_def();
		$direct = false;
		$operand = $this->value($condition, $global_references, $declarations, $headers, $direct, logical_root: true);
		$logical_parent = $condition->parent;
		$negated = $logical_parent instanceof ast_not_expression;
		if ($condition instanceof ast_binary_expression && $condition->operator->lazy()
			&& ($negated || ($logical_parent instanceof ast_binary_expression && $logical_parent->operator->lazy()
				&& $logical_parent->operator !== $condition->operator))) {
			// Mixed && and || need explicit grouping for precedence or Clang's -Wlogical-op-parentheses.
			$operand = '(' . $operand . ')';
		}
		elseif (!$negated && $direct && ($condition instanceof ast_binary_expression) && ($type === type_bool::instance())) {
			// Direct boolean comparisons already have one outer pair. A negated comparison needs it.
			$operand = substr($operand, 1, -1);
		}
		foreach ($type->condition_headers() as $header) {
			$headers[$header] = true;
		}
		return $type->lower_cpp_condition($operand, $negated);
	}

	/**
	 * Lower already resolved statements and retain only contract-derived external declarations.
	 *
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $global_references
	 * @param bool[] $required_headers
	 * @return string[]
	 */
	private function statements(ast_statement_block $body, array &$declarations /** array<string,ast_function_declaration> */,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$required_headers /** array<string,bool> */, ?ast_function_declaration $function = null): array /** list<string> */
	{
		if ($body instanceof ast_function_body) {
			$body->require_prepared();
			/** @var ast_function_declaration $function */
			$function = $body->parent;
		}
		$lines = [];
		foreach ($body->statements as $statement)
		{
			if (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement)) {
				$lines[] = "\t" . ($statement instanceof ast_break_statement ? 'break;' : 'continue;');
			}
			elseif ($statement instanceof ast_loop_statement) {
				$condition = $this->condition($statement->condition, $global_references, $declarations, $required_headers);
				$post_test = $statement instanceof ast_do_while_statement;
				$update_snapshot = null;
				if ($statement instanceof ast_for_statement) {
					$initializer = $this->binding($statement->initializer, $global_references, $declarations, $required_headers, $init_snapshot);
					if ($init_snapshot !== null) {
						$lines[] = "\t{ const auto " . $init_snapshot . '; ' . $initializer . '; }';
						$initializer = '';
					}
					$update = $this->binding($statement->update, $global_references, $declarations, $required_headers, $update_snapshot);
					if ($update_snapshot !== null) {
						$lines[] = "\t{ " . $statement->update->local_def()->type_def()->cpp_type() . ' compound_previous;';
						$update = '(' . $update_snapshot . ', ' . $update . ')';
					}
					$lines[] = "\tfor (" . $initializer . '; ' . $condition . '; ' . $update . ') {';
				}
				else {
					$lines[] = "\t" . ($post_test ? 'do' : 'while (' . $condition . ')') . ' {';
				}
				foreach ($this->statements($statement->body, $declarations, $global_references, $required_headers, $function) as $line) {
					$lines[] = "\t" . $line;
				}
				$lines[] = "\t}" . ($post_test ? ' while (' . $condition . ');' : '');
				if ($update_snapshot !== null) {
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $index => $arm) {
					$head = $index === 0 ? 'if' : 'else';
					if ($arm->condition !== null) {
						$head = ($index === 0 ? 'if' : 'else if') . ' ('
							. $this->condition($arm->condition, $global_references, $declarations, $required_headers) . ')';
					}
					$lines[] = "\t" . $head . ' {';
					foreach ($this->statements($arm->body, $declarations, $global_references, $required_headers, $function) as $line) {
						$lines[] = "\t" . $line;
					}
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_index_assignment) {
				/** @var index_write_type $type */
				$type = $statement->receiver->type_def();
				$receiver = $this->value($statement->receiver, $global_references, $declarations, $required_headers);
				$key = $this->value($statement->index, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$conversion = $statement->conversion();
				$destination = $type->write_type();
				foreach ($destination->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$lines[] = "\t" . $type->lower_cpp_store($statement, $receiver, $key, $value);
			}
			elseif ($statement instanceof ast_append_statement) {
				/** @var appendable_collection_type $type */
				$type = $statement->receiver->type_def();
				$receiver = $this->value($statement->receiver, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$conversion = $statement->conversion();
				foreach ($type->write_type()->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$lines[] = "\t" . $type->lower_cpp_append($statement, $receiver, $value) . ';';
			}
			elseif (($statement instanceof ast_local_declaration) || ($statement instanceof ast_storage_write)) {
				$write = $this->binding($statement, $global_references, $declarations, $required_headers, $snapshot);
				$lines[] = "\t" . ($snapshot === null ? $write . ';' : '{ const auto ' . $snapshot . '; ' . $write . '; }');
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$conversion = $statement->conversion();
				if ($conversion instanceof exit_conversion) {
					$value = self::plain_integer_literal($statement->value, $conversion)
						?? Cpp_Exit::expression($conversion,
							$this->value($statement->value, $global_references, $declarations, $required_headers));
				}
				else {
					$value = $this->value($statement->value, $global_references, $declarations, $required_headers,
						logical_root: $conversion === value_conversion::identity);
					$type = $function->return_type_def();
					$value = $type->lower_cpp_conversion($conversion, $value, $statement->value);
					foreach ($type->conversion_headers($conversion) as $header) {
						$required_headers[$header] = true;
					}
				}
				$lines[] = "\treturn " . $value . ';';
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				foreach ($statement->values as $value) {
					$type = $value->type_def();
					foreach ($type->output_headers() as $header) {
						$required_headers[$header] = true;
					}
					$operand = $value instanceof ast_string_expression ? null
						: $this->value($value, $global_references, $declarations, $required_headers);
					$lines[] = "\t" . $type->lower_cpp_output($value, $operand);
				}
			}
			elseif ($statement instanceof ast_return_statement) {
				$lines[] = "\treturn;";
			}
			elseif ($statement instanceof ast_call_statement) {
				$lines[] = "\t" . $this->value($statement->value, $global_references, $declarations, $required_headers) . ';';
			}
			else {
				throw new \LogicException('Unhandled prepared statement');
			}
		}
		return $lines;
	}

	private function name(ast_local_binding $binding): string
	{
		return 'var_' . self::name_component($binding->declaration_key()) . '_' . $binding->storage_id();
	}

	/**
	 * A statement owns its snapshot locally. A for update assigns to storage supplied by its loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function compound(ast_assignment_statement $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?string &$snapshot): string
	{
		$type = $statement->local_def()->type_def();
		$execution = $statement->execution_type();
		$name = $this->name($statement->local_def());
		$right = $this->value($statement->value, $global_references, $declarations, $headers);
		$conversion = $statement->conversion();
		if (($type === $execution) && ($statement->local_def() instanceof ast_local_declaration)
			&& ($conversion === value_conversion::identity) && ($statement->left_conversion() === value_conversion::identity)
			&& ($statement->write_back() === value_conversion::identity)
			&& (($statement->value instanceof ast_integer_expression) || (($statement->value instanceof ast_name_expression)
				&& ($statement->value->declaration() instanceof ast_local_declaration)))) {
			$operator = $execution->cpp_compound_operator($statement->operator);
			if ($operator !== null) {
				return $name . ' ' . $operator . ' ' . $right;
			}
		}
		if ($conversion !== value_conversion::identity) {
			$right = $execution->lower_cpp_conversion($conversion, $right, $statement->value);
			foreach ($execution->conversion_headers($conversion) as $header) {
				$headers[$header] = true;
			}
		}
		foreach ($execution->binary_headers($statement->operator) as $header) {
			$headers[$header] = true;
		}
		$left = 'compound_previous';
		if ($statement->left_conversion() !== value_conversion::identity) {
			$left = $execution->lower_cpp_conversion($statement->left_conversion(), $left);
			foreach ($execution->conversion_headers($statement->left_conversion()) as $header) {
				$headers[$header] = true;
			}
		}
		$result = $execution->lower_cpp_binary($statement->operator, $left, $right, $execution);
		if ($statement->write_back() !== value_conversion::identity) {
			$result = $type->lower_cpp_conversion($statement->write_back(), $result);
			foreach ($type->conversion_headers($statement->write_back()) as $header) {
				$headers[$header] = true;
			}
		}
		$snapshot = 'compound_previous = ' . $name;
		return $name . ' = ' . $result;
	}

	/**
	 * Render a binding without its delimiter and report any required prior read to its enclosing statement or loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function binding(ast_local_declaration|ast_storage_write $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?string &$snapshot): string
	{
		$snapshot = null;
		if (($statement instanceof ast_assignment_statement) && ($statement->operator !== null)) {
			return $this->compound($statement, $global_references, $declarations, $headers, $snapshot);
		}
		if ($statement instanceof ast_update_statement) {
			return $statement->local_def()->type_def()->lower_cpp_update($statement->operator,
				$this->name($statement->local_def()), $statement->postfix);
		}
		if ($statement instanceof ast_local_declaration) {
			$type = $statement->type_def();
			$conversion = $statement->initialization();
			$value = $statement->initializer;
			$prefix = '[[maybe_unused]] ' . $type->cpp_type() . ' ' . $this->name($statement);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
			}
			$literal = self::plain_integer_literal($value, $conversion);
			if ($literal !== null) {
				return $prefix . ' = ' . $literal;
			}
		}
		else {
			$type = $statement->local_def()->type_def();
			$conversion = $statement->conversion();
			$value = $statement->value;
			$prefix = $this->name($statement->local_def());
			if (($conversion === value_conversion::identity) && ($value instanceof ast_name_expression)
				&& ($value->declaration() === $statement->local_def())) {
				return '(void)' . $prefix;
			}
		}
		$operand = $this->value($value, $global_references, $declarations, $headers,
			typed_initializer: ($statement instanceof ast_local_declaration) && ($conversion === value_conversion::identity),
			logical_root: $conversion === value_conversion::identity);
		foreach ($type->conversion_headers($conversion) as $header) {
			$headers[$header] = true;
		}
		return $prefix . ' = ' . $type->lower_cpp_conversion($conversion, $operand, $value);
	}

	/**
	 * Only the root of an identity initializer may rely on its destination for the native result type.
	 * Typed values carry normalized constants or settled bindings; no source-name lookup occurs here.
	 * $logical_root: the caller supplies any required grouping for a logical expression.
	 * $reorderable: output proof for this subtree, computed during lowering, never retained in preparation.
	 * True permits unsequenced evaluation only alongside other proven subtrees; calls always remain false.
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 * @param ast_global_declaration[] $global_references
	 */
	private function value(ast_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */,
		bool &$reorderable = false, bool $typed_initializer = false, bool $logical_root = false): string
	{
		$reorderable = false;
		foreach ($value->type_def()->cpp_headers() as $header) {
			$required_headers[$header] = true;
		}
		if ($value instanceof ast_empty_collection_expression) {
			/** @var indexed_collection_type $type */
			$type = $value->type_def();
			return $type->lower_cpp_empty();
		}
		if ($value instanceof ast_index_expression) {
			/** @var index_read_type $type */
			$type = $value->receiver->type_def();
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			$index = $this->value($value->index, $global_references, $declarations, $required_headers);
			return $type->lower_cpp_index($value->index, $receiver, $index);
		}
		if ($value instanceof ast_interpolated_string) {
			$type = $value->type_def();
			$code = '';
			foreach ($value->parts as $part) {
				$operand = $this->value($part, $global_references, $declarations, $required_headers);
				if ($part instanceof ast_interpolation_name) {
					$conversion = $part->conversion();
					foreach ($type->scalar_conversion_headers($conversion) as $header) {
						$required_headers[$header] = true;
					}
					$operand = $type->lower_scalar_conversion($conversion, $operand);
				}
				if ($code === '') {
					if (count($value->parts) === 1) {
						return $type->cpp_type() . '(' . $operand . ')';
					}
					$code = '([&]() { ' . $type->cpp_type() . ' result = ' . $operand . ';';
				}
				else {
					$code .= ' result.append(' . $operand . ');';
				}
			}
			return $code . ' return result; }())';
		}
		if ($value instanceof ast_cast_expression) {
			$type = $value->type_def();
			$conversion = $value->conversion();
			$operand_safe = false;
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers, $operand_safe);
			foreach (array_merge($type->cpp_headers(), $value->operand->type_def()->cpp_headers(),
				$type->conversion_headers($conversion)) as $header) {
				$required_headers[$header] = true;
			}
			$reorderable = $operand_safe && $type->cpp_trivial_value() && ($conversion === value_conversion::identity);
			return '(' . $type->lower_cpp_conversion($conversion, $operand, $value->operand) . ')';
		}
		if ($value instanceof ast_unary_expression) {
			$operand_safe = false;
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers, $operand_safe);
			$type = $value->operand->value_type();
			if ($value->operand->type_def() instanceof carrier_type_definition) {
				$operand = $value->operand->type_def()->lower_cpp_extract($operand);
				$operand_safe = false;
			}
			$reorderable = $operand_safe && $type->cpp_unary_reorderable($value->operator);
			return $type->lower_cpp_unary($value->operator, $operand);
		}
		if ($value instanceof ast_isset_expression) {
			/** @var carrier_type_definition $type */
			$type = $value->operand->type_def();
			return $type->lower_cpp_presence($this->value($value->operand, $global_references, $declarations, $required_headers));
		}
		if ($value instanceof ast_not_expression) {
			$expression = '!' . $this->condition($value->operand, $global_references, $declarations, $required_headers);
			return $logical_root ? $expression : '(' . $expression . ')';
		}
		if ($value instanceof ast_binary_expression) {
			if ($value->operator->logical()) {
				$left = $this->condition($value->left, $global_references, $declarations, $required_headers);
				$right = $this->condition($value->right, $global_references, $declarations, $required_headers);
				if (!$value->operator->lazy()) {
					return '([&]() -> bool { const bool logical_left = ' . $left
						. '; const bool logical_right = ' . $right . '; return logical_left != logical_right; }())';
				}
				$expression = $left . ($value->operator === binary_operator::logical_and ? ' && ' : ' || ') . $right;
				return $logical_root ? $expression : '(' . $expression . ')';
			}
			$type = $value->execution_type();
			$left_safe = false;
			$right_safe = false;
			$left = $this->value($value->left, $global_references, $declarations, $required_headers, $left_safe);
			$right = $this->value($value->right, $global_references, $declarations, $required_headers, $right_safe);
			if ($value->left_conversion !== value_conversion::identity) {
				$left = $type->lower_cpp_conversion($value->left_conversion, $left, $value->left);
				foreach ($type->conversion_headers($value->left_conversion) as $header) {
					$required_headers[$header] = true;
				}
			}
			if ($value->right_conversion !== value_conversion::identity) {
				$right = $type->lower_cpp_conversion($value->right_conversion, $right, $value->right);
				foreach ($type->conversion_headers($value->right_conversion) as $header) {
					$required_headers[$header] = true;
				}
			}
			foreach ($type->binary_headers($value->operator) as $header) {
				$required_headers[$header] = true;
			}
			$same_spelling = $left === $right;
			$operands_safe = $left_safe && $right_safe;
			$reorderable = $operands_safe && $type->cpp_binary_reorderable($value->operator, $same_spelling);
			// Direct spelling does not make a checked result reorderable in its parent.
			if ($reorderable || ($value->left_conversion === value_conversion::identity
				&& $value->right_conversion === value_conversion::identity
				&& $type->cpp_direct_binary($value, $operands_safe, $same_spelling))) {
				return $type->lower_prepared_binary($value, $left, $right, $typed_initializer);
			}
			// Snapshot before RHS effects, including writes through reference parameters. Nested lambdas own their names.
			return '([&]() -> ' . $value->type_def()->cpp_type() . ' { const auto binary_left = ' . $left
				. '; const auto binary_right = ' . $right . '; return '
				. $type->lower_prepared_binary($value, 'binary_left', 'binary_right', $typed_initializer) . '; }())';
		}
		if ($value instanceof ast_call_expression) {
			if ($value->cardinality !== null) {
				$receiver = $value->arguments[0];
				/** @var counted_collection_type $type */
				$type = $receiver->type_def();
				return $type->lower_cpp_cardinality($value->cardinality,
					$this->value($receiver, $global_references, $declarations, $required_headers));
			}
			$function = $value->function_def();
			if ($function instanceof runtime_function) {
				$required_headers[$function->header] = true;
				$symbol = $function->symbol;
				if ($function->adapter_flavor !== null) {
					$this->adapter_flavor = $function->adapter_flavor;
					$short = substr($symbol, strrpos($symbol, '::') + 2);
					// Generated app declarations and locals are encoded. A namespace component can still hide the spelling.
					$symbol = in_array($short, explode('::', $this->root), true) ? '::' . $symbol : $short;
				}
				$operands = $value->runtime_operands();
				if (count($operands) === 1 && !$function->overloaded_first) {
					return $symbol . '(' . $this->value($operands[0], $global_references, $declarations, $required_headers) . ')';
				}
				$arguments = [];
				$direct = true;
				foreach ($operands as $index => $argument) {
					$safe = false;
					$operand = $this->value($argument, $global_references, $declarations, $required_headers, $safe);
					$arguments[] = $index === 0 && $function->overloaded_first
						? 'static_cast<' . $function->parameters[0]->cpp_type() . '>(' . $operand . ')' : $operand;
					// Reading a local string or literal cannot mutate another argument.
					$direct = $direct && ($safe || $argument instanceof ast_string_expression
						|| ($argument instanceof ast_name_expression && $argument->declaration() instanceof ast_local_binding));
				}
				$steps = [];
				if (!$direct && count($arguments) > 1) {
					foreach ($arguments as $index => $operand) {
						$arguments[$index] = 'argument_' . $value->start . '_' . $index;
						$steps[] = ($function->is_reference($index) ? 'auto& ' : 'const auto ') . $arguments[$index] . ' = ' . $operand . ';';
					}
				}
				$call = $symbol . '(' . implode(', ', $arguments) . ')';
				return $steps === [] ? $call : '([&]() { ' . implode(' ', $steps) . ' return ' . $call . '; }())';
			}
			$name = self::function_name($function);
			$declarations[$name] = $function;
			if ($function->parameters === []) {
				return $name . '()';
			}
			$steps = [];
			$arguments = [];
			$direct_arguments = [];
			$direct = true;
			foreach ($function->parameters as $index => $parameter) {
				$argument = $value->arguments[$index] ?? $parameter->default_value;
				$conversion = isset($value->arguments[$index]) ? $value->conversion($index) : $parameter->default_conversion();
				$type = $parameter->type_def();
				$operand_safe = false;
				$operand = $this->value($argument, $global_references, $declarations, $required_headers, $operand_safe,
					logical_root: $conversion === value_conversion::identity);
				foreach ($type->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				if ($parameter->mode === passing_mode::value) {
					$operand = $type->lower_cpp_conversion($conversion, $operand, $argument);
				}
				if (count($function->parameters) === 1 && ($parameter->mode === passing_mode::value
					|| ($argument instanceof ast_name_expression && $argument->declaration() instanceof ast_local_declaration))) {
					return $name . '(' . $operand . ')';
				}
				$direct_arguments[] = $operand;
				$direct = $direct && $operand_safe && $type->cpp_trivial_value()
					&& ($conversion === value_conversion::identity) && ($parameter->mode === passing_mode::value);
				// Position-derived names can shadow outer lambda snapshots during nested calls; use source positions.
				$temporary = 'argument_' . $value->start . '_' . $index;
				if ($parameter->mode === passing_mode::reference) {
					$steps[] = $type->cpp_type() . ' &' . $temporary . ' = ' . $operand . ';';
					$arguments[] = $temporary;
				}
				else {
					$steps[] = $type->cpp_type() . ' ' . $temporary . ' = ' . $operand . ';';
					$arguments[] = $type->cpp_trivial_value() ? $temporary
						: 'static_cast<' . $type->cpp_type() . '&&>(' . $temporary . ')';
				}
			}
			if ($direct) {
				return $name . '(' . implode(', ', $direct_arguments) . ')';
			}
			return '([&]() -> ' . $function->return_type_def()->cpp_type() . ' { ' . implode(' ', $steps)
				. ' return ' . $name . '(' . implode(', ', $arguments) . '); }())';
		}
		if (($value instanceof ast_integer_expression) || ($value instanceof ast_float_expression)
			|| ($value instanceof ast_keyword_expression) || ($value instanceof ast_string_expression)) {
			$reorderable = $value->type_def()->cpp_trivial_value();
			return $value->type_def()->lower_cpp_literal($value);
		}
		if ($value instanceof ast_name_expression) {
			$reorderable = $value->type_def()->cpp_trivial_value();
			$declaration = $value->declaration();
			if ($declaration instanceof runtime_integer_constant) {
				return $declaration->value . 'LL';
			}
			if ($declaration instanceof ast_constant_declaration) {
				$type = $declaration->type_def();
				$conversion = $declaration->initialization();
				$reorderable = $reorderable && ($conversion === value_conversion::identity);
				$literal = $this->value($declaration->initializer, $global_references, $declarations, $required_headers);
				foreach (array_merge($type->cpp_headers(), $declaration->initializer->type_def()->cpp_headers(),
					$type->conversion_headers($conversion)) as $header) {
					$required_headers[$header] = true;
				}
				return $type->lower_cpp_conversion($conversion, $literal, $declaration->initializer);
			}
			if ($declaration instanceof ast_global_declaration) {
				$name = self::global_name($declaration);
				$global_references[$name] = $declaration;
				return '::' . ($this->root === '' ? '' : $this->root . '::') . $name;
			}
			return $this->name($declaration);
		}
		throw new \LogicException('Unhandled prepared value');
	}

	/**
	 * Emit declarations from settled signatures, without looking inside foreign bodies.
	 * @param bool[] $headers
	 */
	private function signature(ast_function_declaration $function, array &$headers /** array<string,bool> */, bool $short_types = false): string
	{
		$parameters = [];
		foreach ($function->return_type_def()->cpp_headers() as $header) {
			$headers[$header] = true;
		}
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			$parameters[] = '[[maybe_unused]] ' . $this->signature_type($type, $short_types) . ' '
				. ($parameter->mode === passing_mode::reference ? '&' : '') . $this->name($parameter);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
			}
		}
		return $this->signature_type($function->return_type_def(), $short_types) . ' ' . self::function_name($function)
			. '(' . implode(', ', $parameters) . ')';
	}

	/** Public headers qualify runtime names; private adapter units may use provider-exported type names. */
	private function signature_type(type_definition $type, bool $short): string
	{
		$name = $type->cpp_type();
		if (!str_starts_with($name, 'scpp::')) {
			return $name;
		}
		$word = substr($name, 6);
		if ($short && type_registry::has_cpp_type_alias($word) && !in_array($word, explode('::', $this->root), true)) {
			return $word;
		}
		return '::' . $name;
	}

	private function local_imports(): string
	{
		return $this->root === '' && $this->adapter_flavor !== null ? self::imports($this->adapter_flavor) : '';
	}

	/** STATIC METHODS */
	private static function imports(string $flavor): string
	{
		return "using namespace ::scpp::types;\nusing namespace ::scpp::$flavor;\n";
	}

	public static function namespace_body(string $root, string $body): string
	{
		return $root === '' ? $body : "namespace $root {\n" . $body . "}\n";
	}

	/** The typed destination supplies the native type. Leave the signed minimum to the literal renderer. */
	private static function plain_integer_literal(ast_expression $value, int|exit_conversion $conversion /** uint16|exit_conversion */): ?string
	{
		if (!($value instanceof ast_integer_expression)
			|| ($conversion !== value_conversion::identity && $conversion !== value_conversion::integer_proven
				&& $conversion !== exit_conversion::proven)) {
			return null;
		}
		$integer = $value->integer();
		return $integer === \PHP_INT_MIN ? null : (string)$integer;
	}

	/** Escape individual bytes as Zhh. Keep C++ reserved underscores and our namespace delimiter out of components. */
	private static function name_component(string $name): string
	{
		return preg_replace_callback('/Z|[^A-Za-z0-9_]|^[_0-9]|_(?=_|$|ns(?:_|$))/',
			static fn(array $match): string => 'Z' . bin2hex($match[0]), $name);
	}

	private static function qualified_name(ast_declaration_interface $declaration): string
	{
		return implode('_ns_', array_map(self::name_component(...), explode('\\', $declaration->name())));
	}

	public static function global_name(ast_global_declaration $declaration): string
	{
		return 'var_' . self::qualified_name($declaration);
	}

	public static function function_name(ast_function_declaration $declaration): string
	{
		return self::qualified_name($declaration) . '_';
	}
}
