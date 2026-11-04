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
				exit_conversion::checked => 'scpp::entry_exit_checked(' . $value . ')',
		};
	}
}

/**
 * Lowers completed bodies to C++ using settled declaration links and value facts, without performing name resolution.
 */
final class Cpp_Emitter
{
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
		$uses_output = false;
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
				. implode("\n", $this->statements($function->body(), $declarations, $uses_output, $global_references, $required_headers)) . "\n}\n";
		}
		if ($entry !== null) {
			$definitions[] = 'int ' . $entry_name . "()\n{\n"
				. implode("\n", $this->statements($entry->body(), $declarations, $uses_output, $global_references, $required_headers)) . "\n}\n";
		}
		$prototypes = '';
		ksort($declarations, \SORT_STRING);
		foreach ($declarations as $function) {
			$prototypes .= $this->signature($function, $required_headers) . ";\n";
		}
		$header = "// Generated from completed source-owned bodies.\n";
		if ($entry !== null) {
			$header .= "#include <scpp/entry.hpp>\n";
			$header .= 'SCPP_REQUIRE_ENTRY_TARGET(' . $entry->target->digits . ");\n\n";
		}
		if ($uses_output) {
			$header .= "#include <scpp/output.hpp>\n";
		}
		ksort($required_headers, \SORT_STRING);
		foreach ($required_headers as $include => $unused) {
			$header .= '#include <' . $include . ">\n";
		}
		ksort($global_references, \SORT_STRING);
		foreach ($global_references as $name => $global) {
			$header .= 'extern ' . $global->type_def()->cpp_type() . ' ' . $name . ";\n";
		}
		return $header . $prototypes . implode("\n", $definitions);
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
		bool &$uses_output, array &$global_references /** array<string,ast_global_declaration> */,
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
			if ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $index => $arm) {
					$head = $index === 0 ? 'if' : 'else';
					if ($arm->condition !== null) {
						$type = $arm->condition->type_def();
						$operand = $this->value($arm->condition, $global_references, $declarations, $required_headers);
						$head = ($index === 0 ? 'if' : 'else if') . ' (' . $type->lower_cpp_condition($operand) . ')';
						foreach ($type->condition_headers() as $header) {
							$required_headers[$header] = true;
						}
					}
					$lines[] = "\t" . $head . ' {';
					foreach ($this->statements($arm->body, $declarations, $uses_output, $global_references, $required_headers, $function) as $line) {
						$lines[] = "\t" . $line;
					}
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_local_declaration) {
				$lines[] = "\t[[maybe_unused]] " . $statement->type_def()->cpp_type() . ' ' . $this->name($statement)
					. ' = ' . $statement->type_def()->lower_cpp_conversion($statement->initialization(),
						$this->value($statement->initializer, $global_references, $declarations, $required_headers)) . ';';
				foreach (array_merge($statement->type_def()->cpp_headers(),
					$statement->type_def()->conversion_headers($statement->initialization())) as $header) {
					$required_headers[$header] = true;
				}
			}
			elseif ($statement instanceof ast_assignment_statement) {
				// Identity writes to the same local are no-ops for the supported built-in value types.
				if (($statement->conversion() === value_conversion::identity)
					&& ($statement->value instanceof ast_name_expression)
					&& ($statement->value->declaration() === $statement->local_def())) {
					$lines[] = "\t(void)" . $this->name($statement->local_def()) . ';';
					continue;
				}
				$type = $statement->local_def()->type_def();
				$lines[] = "\t" . $this->name($statement->local_def()) . ' = '
					. $type->lower_cpp_conversion($statement->conversion(), $this->value($statement->value, $global_references, $declarations, $required_headers)) . ';';
				foreach ($type->conversion_headers($statement->conversion()) as $header) {
					$required_headers[$header] = true;
				}
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
				$uses_output = true;
				$value = $statement->value;
				if ($value instanceof ast_string_expression) {
					$lines[] = "\t" . 'scpp::echo_bytes(' . Cpp_String_Literals::bytes($value->bytes()) . ', '
						. strlen($value->bytes()) . ');';
				}
				elseif ($value->type_def() === type_string::instance()) {
					$operand = $this->value($value, $global_references, $declarations, $required_headers);
					// A call result must live through both byte-span queries and execute exactly once.
					$lines[] = "\t{ const auto& output_value = " . $operand
						. '; scpp::echo_bytes(output_value.data(), output_value.size()); }';
				}
				else {
					$lines[] = "\t" . 'scpp::echo_int(' . $this->value($value, $global_references, $declarations, $required_headers) . ');';
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
	 * Typed values carry normalized constants or settled bindings; no source-name lookup occurs here.
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 * @param ast_global_declaration[] $global_references
	 */
	private function value(ast_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */): string
	{
		if ($value instanceof ast_call_expression) {
			$function = $value->function_def();
			$name = self::function_name($function);
			$declarations[$name] = $function;
			if ($function->parameters === []) {
				return $name . '()';
			}
			$steps = [];
			$arguments = [];
			foreach ($function->parameters as $index => $parameter) {
				$argument = $value->arguments[$index] ?? $parameter->default_value;
				$conversion = isset($value->arguments[$index]) ? $value->conversion($index) : $parameter->default_conversion();
				$type = $parameter->type_def();
				$operand = $this->value($argument, $global_references, $declarations, $required_headers);
				// Position-derived names can shadow outer lambda snapshots during nested calls; use source positions.
				$temporary = 'argument_' . $value->start . '_' . $index;
				if ($parameter->mode === passing_mode::reference) {
					$steps[] = $type->cpp_type() . ' &' . $temporary . ' = ' . $operand . ';';
					$arguments[] = $temporary;
				}
				else {
					$steps[] = $type->cpp_type() . ' ' . $temporary . ' = '
						. $type->lower_cpp_conversion($conversion, $operand) . ';';
					$arguments[] = 'static_cast<' . $type->cpp_type() . '&&>(' . $temporary . ')';
				}
				foreach ($type->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
			}
			return '([&]() -> ' . $function->return_type_def()->cpp_type() . ' { ' . implode(' ', $steps)
				. ' return ' . $name . '(' . implode(', ', $arguments) . '); }())';
		}
		if (($value instanceof ast_integer_expression) || ($value instanceof ast_float_expression)
			|| ($value instanceof ast_keyword_expression) || ($value instanceof ast_string_expression)) {
			return $value->type_def()->lower_cpp_literal($value);
		}
		if ($value instanceof ast_name_expression) {
			$declaration = $value->declaration();
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

	public static function global_name(ast_global_declaration $declaration): string
	{
		return 'scpp_global_' . bin2hex($declaration->name());
	}

	public static function function_name(ast_function_declaration $declaration): string
	{
		return 'scpp_function_' . bin2hex($declaration->name());
	}
}
