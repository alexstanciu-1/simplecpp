<?php

declare(strict_types=1);
namespace scpp\compiler;

require_once __DIR__ . '/../../generated/cpp/native_values.php';

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
	use cpp_native_values;

	/** Only a completed semantic program is accepted; output is assembled privately before publication. */
	public function emit(prepared_entry $program): string
	{
		return $this->render($program, 'main');
	}

	public function entry_body(prepared_entry $program): string
	{
		return $this->render($program, 'scpp_entry_body');
	}

	/**
	 * Each source emits its own definitions; foreign information is limited to settled call contracts.
	 *
	 * @param ast_function_declaration[] $functions
	 */
	public function functions(array $functions /** list<ast_function_declaration> */): string
	{
		if ($functions === []) {
			return Cpp_Program::empty_unit();
		}
		return $this->unit(null, $functions, '');
	}

	private function render(prepared_entry $program, string $function): string
	{
		$program->require_prepared();
		return $this->unit($program, $program->file->functions(), $function);
	}

	/**
	 * Assemble local definitions and deterministic forward declarations without a shared naming registry.
	 *
	 * @param ast_function_declaration[] $functions
	 */
	private function unit(?prepared_entry $entry, array $functions /** list<ast_function_declaration> */, string $entry_name): string
	{
		$declarations = [];
		$definitions = [];
		$uses_output = false;
		foreach ($functions as $function) {
			$name = self::function_name($function);
			$declarations[$name] = true;
			$definitions[] = 'void ' . $name . "()\n{\n"
				. implode("\n", $this->statements($function->body(), $declarations, $uses_output)) . "\n}\n";
		}
		if ($entry !== null) {
			$definitions[] = 'int ' . $entry_name . "()\n{\n"
				. implode("\n", $this->statements($entry->body(), $declarations, $uses_output)) . "\n}\n";
		}
		$header = "// Generated from completed source-owned bodies.\n";
		if ($entry !== null) {
			$header .= "#include <scpp/entry.hpp>\n";
			$header .= 'SCPP_REQUIRE_ENTRY_TARGET(' . $entry->target->digits . ");\n\n";
		}
		if ($uses_output) {
			$header .= "#include <scpp/output.hpp>\n";
		}
		ksort($declarations, \SORT_STRING);
		foreach ($declarations as $name => $unused) {
			$header .= 'void ' . $name . "();\n";
		}
		return $header . implode("\n", $definitions);
	}

	/**
	 * Lower already resolved statements and retain only contract-derived external declarations.
	 *
	 * @param bool[] $declarations
	 * @return string[]
	 */
	private function statements(ast_function_body $body, array &$declarations /** array<string,bool> */,
		bool &$uses_output): array /** list<string> */
	{
		$body->require_prepared();
		$lines = [];
		foreach ($body->statements as $statement)
		{
			if ($statement instanceof ast_local_declaration) {
				$lines[] = "\t[[maybe_unused]] " . $this->native_type($statement->type_def()) . ' ' . $this->name($statement)
					. ' = ' . $this->value($statement->initializer) . ';';
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$lines[] = "\treturn " . Cpp_Exit::expression($statement->conversion(), $this->value($statement->value))
					. ';';
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				$uses_output = true;
				$value = $statement->value;
				if ($value instanceof ast_string_expression) {
					$lines[] = "\t" . 'scpp::echo_bytes(' . Cpp_Output::bytes($value->bytes()) . ', '
						. strlen($value->bytes()) . ');';
				}
				else {
					$lines[] = "\t" . 'scpp::echo_int(' . $this->value($value) . ');';
				}
			}
			elseif ($statement instanceof ast_return_statement) {
				$lines[] = "\treturn;";
			}
			elseif ($statement instanceof ast_call_statement) {
				$name = self::function_name($statement->function_def());
				$declarations[$name] = true;
				$lines[] = "\t" . $name . '();';
			}
			else {
				throw new \LogicException('Unhandled prepared statement');
			}
		}
		return $lines;
	}

	private function name(ast_local_declaration $binding): string
	{
		return 'local_' . $binding->id;
	}

	/** Typed values carry normalized constants or direct bindings; no source-name lookup occurs here. */
	private function value(ast_expression $value): string
	{
		if ($value instanceof ast_integer_expression) {
			return $this->native_integer($value->integer());
		}
		if ($value instanceof ast_name_expression) {
			return $this->name($value->local_def());
		}
		throw new \LogicException('Unhandled prepared value');
	}

	public static function function_name(ast_function_declaration $declaration): string
	{
		return 'scpp_function_' . bin2hex($declaration->name);
	}
}
