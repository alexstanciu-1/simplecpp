<?php
namespace scpp\compiler;

/** Emit reusable compiler helpers from settled specialization facts, never source call names. */
final class CPP_Compiler_Functions
{
	private static function enum_name_facts(prepared_compiler_call $specialization): prepared_enum_name_call
	{
		if (!($specialization instanceof prepared_enum_name_call)) {
			throw new \LogicException('Unsupported compiler helper specialization');
		}
		return object_cast($specialization, prepared_enum_name_call::class);
	}

	private static function owner_name(prepared_enum_name_call $facts): string
	{
		$entry = object_cast(Type_Queries::source_declaration($facts->enumeration->type), collected_enum::class);
		return $entry->qualified_name();
	}

	private static function path(string $name): string
	{
		return 'helpers/enum_name_' . CPP_Artifacts::component($name);
	}

	public static function reference(prepared_compiler_call $specialization, cpp_generation_context $context): string
	{
		$facts = self::enum_name_facts($specialization);
		$name = self::owner_name($facts);
		$context->compiler_helpers['enum_name:' . $name] = $facts;
		$context->headers[self::path($name) . '.hpp'] = true;
		return CPP_Generator::source_name('enum_name', $name);
	}

	/** Separate implementation keeps metadata/string construction out of the enum type header. */
	public static function append_artifacts(prepared_compiler_call $specialization,
		Storage $artifacts /** Storage<cpp_artifact> */): void
	{
		$facts = self::enum_name_facts($specialization);
		$name = self::owner_name($facts);
		$path = self::path($name);
		$context = new cpp_generation_context();
		$return_type = CPP_Declarations::type($facts->result_type, $context);
		$input_type = CPP_Declarations::type($facts->enumeration->type, $context);
		$signature = $return_type . ' ' . CPP_Generator::source_name('enum_name', $name) . '(' . $input_type . ' value)';
		$artifacts->append(new cpp_artifact('helper:enum_name:' . $name . ':header', cpp_artifact_kind::header,
			$path . '.hpp', "#pragma once\n" . CPP_Artifacts::includes($context->headers, '') . $signature . ";\n"));
		$text = $signature . " {\n\tswitch (value) {\n";
		$cases /** Keyed_Storage<prepared_enum_case> */ = $facts->enumeration->cases;
		foreach ($cases as $case_value) {
			$text .= "\tcase " . $input_type . '::' . CPP_Generator::source_name('case', $case_value->name)
				. ': return ' . CPP_Generator::generate_string_value($facts->result_type, $case_value->name, $context) . ";\n";
		}
		$text .= "\t}\n\tthrow std::runtime_error(\"Invalid value for enum_name\");\n}\n";
		$context->headers['stdexcept'] = true;
		$context->headers[$path . '.hpp'] = true;
		$artifacts->append(new cpp_artifact('helper:enum_name:' . $name . ':implementation', cpp_artifact_kind::translation_unit,
			$path . '.cpp', CPP_Artifacts::includes($context->headers, '') . $text));
	}
}
