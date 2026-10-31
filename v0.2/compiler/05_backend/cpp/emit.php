<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Spells the already-selected entry exit conversion and its optional checked C++ helper.
 */
final class Cpp_Exit
{
	/** Emit one checked adapter; caller supplies the already evaluated source integer exactly once. */
	public static function checked_helper(): string
	{
		return <<<'CPP'
static int scpp_entry_exit(scpp::int64 value)
{
	const auto raw = value.native_value();
	if ((raw < std::numeric_limits<int>::min()) || (raw > std::numeric_limits<int>::max())) {
		throw std::out_of_range("Entry return is outside native int range");
	}
	return static_cast<int>(raw);
}

CPP;
	}

	/** Rendering follows the settled conversion; it never infers or validates source semantics. */
	public static function expression(exit_conversion $conversion, string $value): string
	{
		return match ($conversion) {
			exit_conversion::proven => 'static_cast<int>((' . $value . ').native_value())',
			exit_conversion::checked => 'scpp_entry_exit(' . $value . ')',
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

	/** Each source emits its own definitions; foreign information is limited to settled call contracts. */
	public function functions(array $functions /** list<prepared_function> */): string
	{
		if ($functions === []) {
			return Cpp_Program::empty_unit();
		}
		return $this->unit(null, $functions, '');
	}

	private function render(prepared_entry $program, string $function): string
	{
		return $this->unit($program, $program->functions, $function);
	}

	/** Assemble local definitions and deterministic forward declarations without a shared naming registry. */
	private function unit(?prepared_entry $entry, array $functions /** list<prepared_function> */, string $entry_name): string
	{
		$declarations = [];
		$definitions = [];
		$checked = false;
		$uses_output = false;
		foreach ($functions as $function) {
			$name = self::function_name($function->declaration);
			$declarations[$name] = true;
			$definitions[] = 'void ' . $name . "()\n{\n"
				. implode("\n", $this->statements($function->body, $declarations, $checked, $uses_output)) . "\n}\n";
		}
		if ($entry !== null) {
			$definitions[] = 'int ' . $entry_name . "()\n{\n"
				. implode("\n", $this->statements($entry, $declarations, $checked, $uses_output)) . "\n}\n";
		}
		$header = "// Generated from completed source-owned bodies.\n#include <scpp/integers.hpp>\n";
		if ($entry !== null) {
			$header .= "#include <limits>\n";
			$header .= 'static_assert(std::numeric_limits<int>::is_signed && std::numeric_limits<int>::digits == '
				. $entry->target->digits . ', "Entry target does not match prepared native int");' . "\n\n";
		}
		if ($checked) {
			$header .= "#include <stdexcept>\n" . Cpp_Exit::checked_helper() . "\n";
		}
		if ($uses_output) {
			$header .= Cpp_Output::helpers();
		}
		ksort($declarations, SORT_STRING);
		foreach ($declarations as $name => $unused) {
			$header .= 'void ' . $name . "();\n";
		}
		return $header . implode("\n", $definitions);
	}

	/** Lower already resolved statements and retain only contract-derived external declarations. */
	private function statements(prepared_body $body, array &$declarations /** array<string,bool> */,
		bool &$checked, bool &$uses_output): array /** list<string> */
	{
		$lines = [];
		foreach ($body->statements as $statement)
		{
			if ($statement instanceof prepared_declaration) {
				$lines[] = "\t[[maybe_unused]] " . $this->type($statement->binding->type_def()) . ' ' . $this->name($statement->binding)
					. ' = ' . $this->value($statement->initializer) . ';';
			}
			elseif ($statement instanceof prepared_return) {
				$checked = $checked || ($statement->conversion === exit_conversion::checked);
				$lines[] = "\treturn " . Cpp_Exit::expression($statement->conversion, $this->value($statement->value)) . ';';
			}
			elseif ($statement instanceof prepared_echo)
			{
				$uses_output = true;
				$value = $statement->value;
				if ($value instanceof prepared_output_string) {
					$lines[] = "\t" . 'scpp_echo_bytes(' . Cpp_Output::bytes($value->bytes) . ', ' . strlen($value->bytes) . ');';
				}
				else {
					$lines[] = "\t" . 'scpp_echo_int((' . $this->value($value) . ').native_value());';
				}
			}
			elseif ($statement instanceof prepared_void_return) {
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

	public static function function_name(ast_function_declaration $declaration): string
	{
		return 'scpp_function_' . bin2hex($declaration->name);
	}

	private function type(type_definition $type): string
	{
		return match ($type) {
			type_definition::int64 => 'scpp::int64',
		};
	}

	private function name(ast_local_declaration $binding): string
	{
		return 'local_' . $binding->id;
	}

	/** Typed values carry normalized constants or direct bindings; no source-name lookup occurs here. */
	private function value(prepared_value $value): string
	{
		if ($value instanceof prepared_integer) {
			return $this->type($value->type) . '{' . $value->value . 'LL}';
		}
		if ($value instanceof prepared_reference) {
			return $this->name($value->binding);
		}
		throw new \LogicException('Unhandled prepared value');
	}
}
