<?php
namespace scpp\compiler;

/** Emit reusable compiler helpers from settled specialization facts, never source call names. */
final class CPP_Compiler_Functions
{
	private static function enum_facts(prepared_compiler_call $specialization): prepared_enum_call
	{
		if (!($specialization instanceof prepared_enum_call)) {
			throw new \LogicException('Unsupported compiler helper specialization');
		}
		return object_cast($specialization, prepared_enum_call::class);
	}

	private static function owner_name(prepared_enum_call $facts): string
	{
		$entry = object_cast(Type_Queries::source_declaration($facts->enumeration->type), collected_enum::class);
		return $entry->qualified_name();
	}

	private static function helper_kind(prepared_enum_call $facts): string
	{
		if ($facts->kind() === compiler_function_kind::enum_name) { return 'enum_name'; }
		if ($facts->kind() === compiler_function_kind::enum_value) { return 'enum_value'; }
		$input = object_cast(Type_Queries::canonical($facts->input_type)->definition(), integer_type_definition::class);
		return 'enum_from_value_' . ($input->signed() ? 'i' : 'u') . $input->bit_width();
	}

	private static function path(string $kind, string $name): string
	{
		return 'helpers/' . $kind . '_' . CPP_Artifacts::component($name);
	}

	public static function reference(prepared_compiler_call $specialization, cpp_generation_context $context): string
	{
		$facts = self::enum_facts($specialization);
		$name = self::owner_name($facts);
		$kind = self::helper_kind($facts);
		$context->compiler_helpers[$kind . ':' . $name] = $facts;
		$context->headers[self::path($kind, $name) . '.hpp'] = true;
		return CPP_Generator::source_name($kind, $name);
	}

	/** Separate implementation keeps metadata/string construction out of the enum type header. */
	public static function append_artifacts(prepared_compiler_call $specialization,
		Storage $artifacts /** Storage<cpp_artifact> */): void
	{
		$facts = self::enum_facts($specialization);
		$name = self::owner_name($facts);
		$kind = self::helper_kind($facts);
		$path = self::path($kind, $name);
		$context = new cpp_generation_context();
		$return_type = CPP_Declarations::type($facts->result_type, $context);
		$input_type = CPP_Declarations::type($facts->input_type, $context);
		$signature = $return_type . ' ' . CPP_Generator::source_name($kind, $name) . '(' . $input_type . ' value)';
		$artifacts->append(new cpp_artifact('helper:' . $kind . ':' . $name . ':header', cpp_artifact_kind::header,
			$path . '.hpp', "#pragma once\n" . CPP_Artifacts::includes($context->headers, '') . $signature . ";\n"));
		$text = $signature . " {\n" . self::body($facts, $context) . "}\n";
		$context->headers[$path . '.hpp'] = true;
		$artifacts->append(new cpp_artifact('helper:' . $kind . ':' . $name . ':implementation', cpp_artifact_kind::translation_unit,
			$path . '.cpp', CPP_Artifacts::includes($context->headers, '') . $text));
	}

	private static function body(prepared_enum_call $facts, cpp_generation_context $context): string
	{
		$enum_type = CPP_Declarations::type($facts->enumeration->type, $context);
		$return_type = CPP_Declarations::type($facts->result_type, $context);
		if ($facts->kind() === compiler_function_kind::enum_value) {
			$context->headers['type_traits'] = true;
			return "\treturn " . $return_type . '(static_cast<std::underlying_type_t<' . $enum_type . ">>(value));\n";
		}
		$from_value = $facts->kind() === compiler_function_kind::enum_from_value;
		$text = $from_value ? '' : "\tswitch (value) {\n";
		$cases /** Keyed_Storage<prepared_enum_case> */ = $facts->enumeration->cases;
		foreach ($cases as $case_value) {
			$case_name = $enum_type . '::' . CPP_Generator::source_name('case', $case_value->name);
			if ($from_value) {
				$context->headers['utility'] = true;
				$constant = CPP_Integer_Literals::constant($case_value->decimal, !$facts->enumeration->storage->signed());
				$text .= "\tif (std::cmp_equal(value.native_value(), " . $constant . ')) return ' . $case_name . ";\n";
			}
			else {
				$text .= "\tcase " . $case_name . ': return '
					. CPP_Generator::generate_string_value($facts->result_type, $case_value->name, $context) . ";\n";
			}
		}
		if (!$from_value) { $text .= "\t}\n"; }
		$text .= "\tthrow std::runtime_error(\"Invalid value for enum helper\");\n";
		$context->headers['stdexcept'] = true;
		return $text;
	}
}
