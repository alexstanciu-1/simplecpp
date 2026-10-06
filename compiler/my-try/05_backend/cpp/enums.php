<?php

/* Emit settled enum facts; source admission, range checks and width selection belong to analysis. */
namespace scpp\compiler;

final class CPP_Enums
{
	/** Each enum gets a small independent type header, without a runtime wrapper dependency. */
	public static function declaration(enum_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_preparation();
		$storage = $facts->storage;
		$context->headers['cstdint'] = true;
		$backing = 'std::' . ($storage->signed() ? 'int' : 'uint') . (string)$storage->bit_width() . '_t';
		$name = CPP_Generator::source_name('enum', $syntax->occurrence()->qualified_name());
		$text = 'enum class ' . $name . ' : ' . $backing . " {\n";
		$cases /** Keyed_Storage<prepared_enum_case> */ = $facts->cases;
		foreach ($cases as $case_value) {
			$text .= "\t" . CPP_Generator::source_name('case', $case_value->name)
				. ' = ' . CPP_Integer_Literals::constant($case_value->decimal, !$storage->signed()) . ",\n";
		}
		return $text . "};\n";
	}

	public static function reference(prepared_enum_case_reference $facts, cpp_generation_context $context): string
	{
		return CPP_Declarations::type($facts->type, $context) . '::'
			. CPP_Generator::source_name('case', $facts->selected_case->name);
	}

}
