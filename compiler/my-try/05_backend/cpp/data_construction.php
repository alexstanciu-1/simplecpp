<?php

/* Backend registration and ordered construction lowering; no semantic discovery. */
namespace scpp\compiler;

final class cpp_initialization_adapter
{
	public function __construct(private type_definition_i $semantic_definition,
		private string $template_name, private string $required_header)
	{
	}

	public function definition(): type_definition_i { return $this->semantic_definition; }
	public function name(): string { return $this->template_name; }
	public function header(): string { return $this->required_header; }
}

final class CPP_Initialization_Registration
{
	/** Backend metadata is installed alongside semantic roots, never by preparation. */
	public static function install(): void
	{
		$contracts = Model::$initialization_contracts;
		Model::$cpp_type_bindings->bind_definition($contracts->sequence(), 'scpp::initializer_list', 'scpp/data_initializer.hpp');
		Model::$cpp_type_bindings->bind_definition($contracts->entry(), 'scpp::initializer_entry', 'scpp/data_initializer.hpp');
		foreach (['vector', 'hash', 'mixed', 'dynamic'] as $name) {
			Model::$cpp_callable_bindings->bind_initialization_definition(Model::$type_catalog->definition($name),
				'scpp::data_initializer', 'scpp/data_initializer.hpp');
		}
	}
}

final class CPP_Data_Construction
{
	/** Publish one backend binding for the already selected concrete semantic factory. */
	private static function reference(initialization_binding $binding, cpp_generation_context $context): string
	{
		$factory = object_cast($binding->factory(), registered_static_factory::class);
		$catalog = Model::$cpp_callable_bindings;
		if (!$catalog->contains($factory)) {
			$definition = Type_Queries::canonical($binding->target())->definition();
			$adapter = $catalog->initialization_adapter($definition);
			$member = $binding->is_keyed() ? 'from_key_value' : 'from_list';
			$catalog->bind_adapter($factory, $adapter->name(), $member, $adapter->header());
		}
		return CPP_Callables::reference($factory, $context);
	}

	/** Input backing and each entry's snapshots stay inside this synchronous construction call. */
	public static function generate(data_construction_node $node, cpp_generation_context $context): string
	{
		$binding = $node->require_data_preparation()->binding;
		$factory = self::reference($binding, $context);
		$entries /** Storage<data_entry_node> */ = $node->entries;
		$signature = $binding->factory()->signature();
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$input_type = CPP_Declarations::type($parameters[0]->type, $context);
		if ($entries->is_empty()) { return $factory . '(' . $input_type . '{})'; }

		// The input signature already identifies the complete entry/element type.
		$input = object_cast(Type_Queries::canonical($parameters[0]->type), applied_template_type::class);
		$element = CPP_Declarations::type($input->arguments()[0], $context);
		$buffer = 'initializer_' . $context->next_temporary;
		$context->next_temporary++;
		$text = '([&]() -> ' . CPP_Declarations::type($binding->target(), $context) . " {\n";
		$text .= 'scpp::initializer_buffer<' . $element . '> ' . $buffer . '(' . q_count($entries) . ");\n";
		$emitter = new CPP_Syntax($context);
		foreach ($entries as $entry) {
			$facts = $entry->require_preparation();
			$text .= "{\n";
			$key_name = '';
			if ($entry->key !== null) {
				$key_decision /** conversion_decision */ = $facts->key_conversion;
				$key_name = 'initializer_key_' . $context->next_temporary;
				$context->next_temporary++;
				$key = CPP_Declarations::conversion($emitter->expression($entry->key), $key_decision, $context);
				$text .= CPP_Declarations::type($key_decision->target_type, $context) . ' ' . $key_name . ' = ' . $key . ";\n";
			}
			$value_name = 'initializer_value_' . $context->next_temporary;
			$context->next_temporary++;
			$value = CPP_Declarations::conversion($emitter->expression($entry->value), $facts->value_conversion, $context);
			$text .= CPP_Declarations::type($facts->value_conversion->target_type, $context) . ' ' . $value_name . ' = ' . $value . ";\n";
			$argument = $value_name;
			if ($entry->key !== null) { $argument = $element . '{' . $key_name . ', ' . $value_name . '}'; }
			$text .= $buffer . '.append(' . $argument . ");\n}\n";
		}
		return $text . 'return ' . $factory . '(' . $buffer . ".view());\n}())";
	}
}
