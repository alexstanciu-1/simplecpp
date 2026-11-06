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

	/** CONSTRUCTOR */
	public function __construct(?Cpp_Headers $headers = null)
	{
		$this->headers = $headers;
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
		return 'extern ' . $node->type_def()->cpp_type() . ' ' . self::global_name($node) . ';';
	}

	/** Only a completed semantic program is accepted; output is assembled privately before publication. */
	public function emit(prepared_entry $program): string
	{
		return $this->render($program, 'main');
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
			$definitions[] = 'constinit ' . $global->type_def()->cpp_type() . ' ' . self::global_name($global)
				. ' = ' . $global->type_def()->lower_cpp_conversion($global->initialization(),
					$global->initializer->type_def()->lower_cpp_literal($global->initializer)) . ";\n";
			foreach ($global->type_def()->conversion_headers($global->initialization()) as $header) {
				$required_headers[$header] = true;
			}
		}
		foreach ($file->functions() as $function) {
			$name = self::function_name($function);
			$declarations[$name] = $function;
			$definitions[] = $this->signature($function, $required_headers) . "\n{\n"
				. implode("\n", $this->statements($function->body(), $declarations, $global_references, $required_headers)) . "\n}\n";
		}
		if ($entry !== null) {
			$definitions[] = 'int ' . $entry_name . "()\n{\n"
				. implode("\n", $this->statements($entry->body(), $declarations, $global_references, $required_headers)) . "\n}\n";
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
		$header = "// Generated from completed source-owned bodies.\n";
		if ($entry !== null) {
			$header .= '#include <' . Cpp_Entry_Binding::header() . ">\n";
			$header .= 'SCPP_REQUIRE_ENTRY_TARGET(' . $entry->target->digits . ");\n\n";
		}
		ksort($required_headers, \SORT_STRING);
		foreach ($required_headers as $include => $unused) {
			$header .= '#include <' . $include . ">\n";
		}
		ksort($global_references, \SORT_STRING);
		foreach ($global_references as $name => $global) {
			$include = $this->headers?->declaration_header($global);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
			else {
				$header .= $this->declaration($global, $required_headers) . "\n";
			}
		}
		foreach ($file->globals() as $global) {
			$include = $this->headers?->declaration_header($global);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
		}
		ksort($unit_headers, \SORT_STRING);
		foreach ($unit_headers as $include => $_) {
			$header .= '#include "' . $include . "\"\n";
		}
		return $header . $prototypes . implode("\n", $definitions);
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
		$operand = $this->value($condition, $global_references, $declarations, $headers, $direct);
		if ($direct && ($condition instanceof ast_binary_expression) && ($type === type_bool::instance())) {
			// Native binary handlers supply one outer pair; the condition statement supplies its own.
			$operand = substr($operand, 1, -1);
		}
		foreach ($type->condition_headers() as $header) {
			$headers[$header] = true;
		}
		return $type->lower_cpp_condition($operand);
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
				$compound_header = ($statement instanceof ast_for_statement)
					&& ((($statement->update instanceof ast_assignment_statement) && ($statement->update->operator !== null))
						|| (($statement->initializer instanceof ast_assignment_statement) && ($statement->initializer->operator !== null)));
				if ($compound_header) {
					/** @var ast_assignment_statement $binding */
					$binding = (($statement->update instanceof ast_assignment_statement) && ($statement->update->operator !== null)) ? $statement->update : $statement->initializer;
					$lines[] = "\t{ " . $binding->local_def()->type_def()->cpp_type() . ' compound_previous;';
				}
				if ($statement instanceof ast_for_statement) {
					$initializer = $this->binding($statement->initializer, $global_references, $declarations, $required_headers);
					$update = $this->binding($statement->update, $global_references, $declarations, $required_headers);
					$lines[] = "\tfor (" . $initializer . '; ' . $condition . '; ' . $update . ') {';
				}
				else {
					$lines[] = "\t" . ($post_test ? 'do' : 'while (' . $condition . ')') . ' {';
				}
				foreach ($this->statements($statement->body, $declarations, $global_references, $required_headers, $function) as $line) {
					$lines[] = "\t" . $line;
				}
				$lines[] = "\t}" . ($post_test ? ' while (' . $condition . ');' : '');
				if ($compound_header) {
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
			elseif (($statement instanceof ast_assignment_statement) && ($statement->operator !== null)) {
				$snapshot = '';
				$write = $this->compound($statement, $global_references, $declarations, $required_headers, $snapshot);
				$lines[] = "\t{ const auto " . $snapshot . '; ' . $write . '; }';
			}
			elseif (($statement instanceof ast_local_declaration) || ($statement instanceof ast_storage_write)) {
				$lines[] = "\t" . $this->binding($statement, $global_references, $declarations, $required_headers) . ';';
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$conversion = $statement->conversion();
				if ($conversion instanceof exit_conversion) {
					$value = Cpp_Exit::expression($conversion, $value);
				}
				else {
					$type = $function->return_type_def();
					$value = $type->lower_cpp_conversion($conversion, $value);
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
		return 'local_' . $binding->storage_id();
	}

	/**
	 * Produce a snapshot assignment and final write expression; enclosing syntax owns storage and sequencing.
	 * $snapshot: output assignment to the context-owned compound_previous temporary.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function compound(ast_assignment_statement $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, string &$snapshot): string
	{
		$type = $statement->local_def()->type_def();
		$execution = $statement->execution_type();
		$name = $this->name($statement->local_def());
		$right = $this->value($statement->value, $global_references, $declarations, $headers);
		$conversion = $statement->conversion();
		if ($conversion !== value_conversion::identity) {
			$right = $execution->lower_cpp_conversion($conversion, $right);
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
	 * Render a declaration/write without its delimiter, shared by body statements and for headers.
	 * A compound header requires compound_previous storage supplied by the enclosing loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function binding(ast_local_declaration|ast_storage_write $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		if ($statement instanceof ast_update_statement) {
			return $statement->local_def()->type_def()->lower_cpp_update($statement->operator,
				$this->name($statement->local_def()), $statement->postfix);
		}
		if (($statement instanceof ast_assignment_statement) && ($statement->operator !== null)) {
			$snapshot = '';
			$write = $this->compound($statement, $global_references, $declarations, $headers, $snapshot);
			return '(' . $snapshot . ', ' . $write . ')';
		}
		if ($statement instanceof ast_local_declaration) {
			$type = $statement->type_def();
			$conversion = $statement->initialization();
			$value = $statement->initializer;
			$prefix = '[[maybe_unused]] ' . $type->cpp_type() . ' ' . $this->name($statement);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
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
		$operand = $this->value($value, $global_references, $declarations, $headers);
		foreach ($type->conversion_headers($conversion) as $header) {
			$headers[$header] = true;
		}
		return $prefix . ' = ' . $type->lower_cpp_conversion($conversion, $operand);
	}

	/**
	 * Typed values carry normalized constants or settled bindings; no source-name lookup occurs here.
	 * $reorderable: output proof for this subtree, computed during lowering, never retained in preparation.
	 * True permits unsequenced evaluation only alongside other proven subtrees; calls always remain false.
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 * @param ast_global_declaration[] $global_references
	 */
	private function value(ast_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */,
		bool &$reorderable = false): string
	{
		$reorderable = false;
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
			return '(' . $type->lower_cpp_conversion($conversion, $operand) . ')';
		}
		if ($value instanceof ast_unary_expression) {
			$operand_safe = false;
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers, $operand_safe);
			$type = $value->operand->type_def();
			$reorderable = $operand_safe && $type->cpp_unary_reorderable($value->operator);
			return $type->lower_cpp_unary($value->operator, $operand);
		}
		if ($value instanceof ast_not_expression) {
			return '(!(' . $this->condition($value->operand, $global_references, $declarations, $required_headers) . '))';
		}
		if ($value instanceof ast_binary_expression) {
			if ($value->operator->logical()) {
				$left = $this->condition($value->left, $global_references, $declarations, $required_headers);
				$right = $this->condition($value->right, $global_references, $declarations, $required_headers);
				if (!$value->operator->lazy()) {
					return '([&]() -> bool { const bool logical_left = ' . $left
						. '; const bool logical_right = ' . $right . '; return logical_left != logical_right; }())';
				}
				return '((' . $left . ') ' . ($value->operator === binary_operator::logical_and ? '&&' : '||')
					. ' (' . $right . '))';
			}
			$type = $value->execution_type();
			$left_safe = false;
			$right_safe = false;
			$left = $this->value($value->left, $global_references, $declarations, $required_headers, $left_safe);
			$right = $this->value($value->right, $global_references, $declarations, $required_headers, $right_safe);
			if ($value->left_conversion !== value_conversion::identity) {
				$left = $type->lower_cpp_conversion($value->left_conversion, $left);
				foreach ($type->conversion_headers($value->left_conversion) as $header) {
					$required_headers[$header] = true;
				}
			}
			if ($value->right_conversion !== value_conversion::identity) {
				$right = $type->lower_cpp_conversion($value->right_conversion, $right);
				foreach ($type->conversion_headers($value->right_conversion) as $header) {
					$required_headers[$header] = true;
				}
			}
			foreach ($type->binary_headers($value->operator) as $header) {
				$required_headers[$header] = true;
			}
			// Retain snapshots for identical spellings to avoid Clang self-comparison diagnostics.
			$reorderable = $left_safe && $right_safe && ($left !== $right)
				&& $type->cpp_binary_reorderable($value->operator);
			if ($reorderable) {
				return $type->lower_cpp_binary($value->operator, $left, $right, $value->right->type_def());
			}
			// Snapshot before RHS effects, including writes through reference parameters. Nested lambdas own their names.
			return '([&]() -> ' . $value->type_def()->cpp_type() . ' { const auto binary_left = ' . $left
				. '; const auto binary_right = ' . $right . '; return '
				. $type->lower_cpp_binary($value->operator, 'binary_left', 'binary_right', $value->right->type_def()) . '; }())';
		}
		if ($value instanceof ast_call_expression) {
			$function = $value->function_def();
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
				$operand = $this->value($argument, $global_references, $declarations, $required_headers, $operand_safe);
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
					$steps[] = $type->cpp_type() . ' ' . $temporary . ' = '
						. $type->lower_cpp_conversion($conversion, $operand) . ';';
					$arguments[] = $type->cpp_trivial_value() ? $temporary
						: 'static_cast<' . $type->cpp_type() . '&&>(' . $temporary . ')';
				}
				foreach ($type->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
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
			if ($declaration instanceof ast_constant_declaration) {
				$type = $declaration->type_def();
				$conversion = $declaration->initialization();
				$reorderable = $reorderable && ($conversion === value_conversion::identity);
				$literal = $this->value($declaration->initializer, $global_references, $declarations, $required_headers);
				foreach (array_merge($type->cpp_headers(), $declaration->initializer->type_def()->cpp_headers(),
					$type->conversion_headers($conversion)) as $header) {
					$required_headers[$header] = true;
				}
				return $type->lower_cpp_conversion($conversion, $literal);
			}
			if ($declaration instanceof ast_global_declaration) {
				$name = self::global_name($declaration);
				$global_references[$name] = $declaration;
				return $name;
			}
			return $this->name($declaration);
		}
		throw new \LogicException('Unhandled prepared value');
	}

	/**
	 * Emit declarations from settled signatures, without looking inside foreign bodies.
	 * @param bool[] $headers
	 */
	private function signature(ast_function_declaration $function, array &$headers /** array<string,bool> */): string
	{
		$parameters = [];
		foreach ($function->return_type_def()->cpp_headers() as $header) {
			$headers[$header] = true;
		}
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			$parameters[] = '[[maybe_unused]] ' . $type->cpp_type() . ' '
				. ($parameter->mode === passing_mode::reference ? '&' : '') . $this->name($parameter);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
			}
		}
		return $function->return_type_def()->cpp_type() . ' ' . self::function_name($function)
			. '(' . implode(', ', $parameters) . ')';
	}

	/** STATIC METHODS */
	public static function global_name(ast_global_declaration $declaration): string
	{
		return 'scpp_global_' . bin2hex($declaration->name());
	}

	public static function function_name(ast_function_declaration $declaration): string
	{
		return 'scpp_function_' . bin2hex($declaration->name());
	}
}
