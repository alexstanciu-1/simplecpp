<?php

/* Emit prepared signatures, value layouts and sequenced call boundaries. */
namespace scpp\compiler;

final class CPP_Declarations
{
	/** Render settled access without interpreting source modifiers or ancestry. */
	public static function visibility(member_visibility $visibility): string
	{
		if ($visibility === member_visibility::public_access) {
			return "public:\n";
		}
		if ($visibility === member_visibility::protected_access) {
			return "protected:\n";
		}
		return "private:\n";
	}

	/** Value/layout sites use complete definitions unless explicitly classified otherwise. */
	public static function type(canonical_type_use $type, cpp_generation_context $context): string
	{
		return self::type_for($type, $context, cpp_type_requirement::complete);
	}

	/** Record use-site visibility together with the selected C++ spelling. */
	public static function type_for(canonical_type_use $type, cpp_generation_context $context,
		cpp_type_requirement $requirement): string
	{
		$mapping = CPP_Types::representation_for($type, $requirement);
		CPP_Types::record_dependencies($mapping, $context);

		return $mapping->spelling;
	}

	/** Render only the operation selected by shared conversion preparation. */
	public static function conversion(string $expression, conversion_decision $decision, cpp_generation_context $context): string
	{
		if ($decision->operation === conversion_operation::identity) {
			return $expression;
		}
		if ($decision->operation === conversion_operation::text_conversion) {
			$context->headers['lang/php/support/php_string.hpp'] = true;
			return 'scpp::php::to_string(' . $expression . ')';
		}
		if (($decision->operation === conversion_operation::nullable_empty)
			|| ($decision->operation === conversion_operation::ownership_empty)) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if (($decision->operation === conversion_operation::nullable_wrap)
			|| ($decision->operation === conversion_operation::boxed_wrap)) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if (($decision->operation === conversion_operation::result_wrap)
			|| ($decision->operation === conversion_operation::result_false)
			|| ($decision->operation === conversion_operation::result_true)) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if ($decision->operation === conversion_operation::nullable_extract) {
			return self::type($decision->target_type, $context) . '((' . $expression
				. ').require_value("nullable required-value extraction"))';
		}
		if ($decision->operation === conversion_operation::result_extract) {
			return self::type($decision->target_type, $context) . '((' . $expression
				. ').require_value("Result required-value extraction"))';
		}
		if ($decision->operation === conversion_operation::integer_value_cast) {
			return 'static_cast<' . self::type($decision->target_type, $context)
				. '>((' . $expression . ').native_value())';
		}
		if ($decision->operation === conversion_operation::boxed_extract) {
			$context->headers['scpp/cast.hpp'] = true;
			return 'scpp::required_cast<' . self::type($decision->target_type, $context)
				. '>(' . $expression . ')';
		}
		if ($decision->operation === conversion_operation::explicit_runtime_cast) {
			$context->headers['scpp/cast.hpp'] = true;
			return 'scpp::cast<' . self::type($decision->target_type, $context)
				. '>(' . $expression . ')';
		}

		throw new \RuntimeException('C++ conversion operation is not implemented');
	}

	/** Render the prepared condition operation using the established runtime authority. */
	public static function truthiness(string $expression, truthiness_decision $decision, cpp_generation_context $context): string
	{
		if ($decision->operation === truthiness_operation::always_true) {
			return '(static_cast<void>(' . $expression . '), true)';
		}
		if ($decision->operation === truthiness_operation::value_presence) {
			return 'static_cast<bool>((' . $expression . ').has_value())';
		}
		if ($decision->operation === truthiness_operation::boolean_identity) {
			return '(' . $expression . ').native_value()';
		}
		if (($decision->operation === truthiness_operation::scalar_condition)
			|| ($decision->operation === truthiness_operation::boxed_condition)
			|| ($decision->operation === truthiness_operation::error_condition)
			|| ($decision->operation === truthiness_operation::result_condition)
			|| ($decision->operation === truthiness_operation::nullable_condition)) {
			if ($decision->operation === truthiness_operation::nullable_condition) {
				$context->headers['scpp/cast.hpp'] = true;
			}
			$context->headers['lang/php/operators/conditional/condition_truthiness.hpp'] = true;
			return 'scpp::php::condition_truthy(' . $expression . ').native_value()';
		}
		throw new \LogicException('C++ truthiness operation is not implemented');
	}

	/** Prototypes only name records; value definitions need layouts, reference definitions do not. */
	public static function signature(callable_declaration_node $syntax, cpp_generation_context $context, bool $prototype): string
	{
		$facts = $syntax->require_preparation();
		$parts = '';
		$separator = '';
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		foreach ($parameters as $storage) {
			$entry = object_cast(weakref_get($storage->declaration), collected_name::class);
			$parameter = $storage->contract;
			$reference = $parameter->mode === passing_mode::reference ? '&' : '';
			$qualifier = $parameter->access === storage_access::read_only ? 'const ' : '';
			$requirement = self::signature_requirement($prototype, $parameter->mode === passing_mode::reference);
			$parts .= $separator . $qualifier . self::type_for($parameter->type, $context, $requirement) . $reference . ' ' . CPP_Generator::local_name($entry);
			if ($prototype && ($syntax instanceof function_node) && ($parameter->default_value !== null)) {
				$parts .= ' = ' . self::parameter_default($parameter->default_value, $context);
			}
			elseif ($prototype && ($parameter->element_type !== null)) {
				// An empty tail is implicit in source; this also permits preceding C++ defaults.
				$parts .= ' = ' . self::type($parameter->type, $context) . '{}';
			}
			$separator = ', ';
		}

		$function_declaration = object_cast($syntax->occurrence(), collected_source_callable::class);
		$return_reference = $facts->signature->reference_return !== null ? '&' : '';
		$return_requirement = self::signature_requirement($prototype, $facts->signature->reference_return !== null);
		$return_prefix = self::type_for($facts->signature->return_type, $context, $return_requirement) . $return_reference . ' ';
		if ($function_declaration instanceof collected_method) {
			$method = object_cast($function_declaration, collected_method::class);
			$record_name = CPP_Generator::source_name('record', $method->declaring_type()->qualified_name());
			$name = $syntax->name === '__construct' ? $record_name : CPP_Generator::source_name('method', $syntax->name);
			$destructor = $syntax->name === '__destruct';
			if ($destructor) { $name = '~' . $record_name; }
			if (!$prototype) { $name = $record_name . '::' . $name; }
			$prefix = (($syntax->name === '__construct') || $destructor) ? '' : $return_prefix;
			if ($prototype && $facts->static_member) { $prefix = 'static ' . $prefix; }
			return $prefix . $name . '(' . $parts . ')' . ($destructor ? ' noexcept' : '');
		}
		return $return_prefix . CPP_Callables::source_reference(object_cast($function_declaration, collected_function::class)) . '(' . $parts . ')';
	}

	private static function signature_requirement(bool $prototype, bool $reference): cpp_type_requirement
	{
		return ($prototype || $reference) ? cpp_type_requirement::declaration : cpp_type_requirement::complete;
	}

	/** Property-backed inputs initialize members before the body, preserving parameter values. */
	public static function constructor_initializers(collected_method $method): string
	{
		$text = '';
		$parameters /** Storage<prepared_parameter> */ = $method->callable_syntax()->require_preparation()->parameters;
		$by_property /** hash<string> */ = [];
		foreach ($parameters as $parameter) {
			if ($parameter->initialized_property !== null) {
				$field = object_cast(weakref_get($parameter->initialized_property->declaration), collected_name::class);
				$by_property[$field->name] = CPP_Generator::local_name(object_cast(weakref_get($parameter->declaration), collected_name::class));
			}
		}
		$record = object_cast($method->declaring_type(), collected_record::class);
		$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->fields;
		foreach ($fields->items() as $field) {
			$name = object_cast(weakref_get($field->declaration), collected_name::class)->name;
			if (isset($by_property[$name])) {
				$text .= ($text === '' ? ' : ' : ', ') . CPP_Generator::source_name('field', $name) . '(' . $by_property[$name] . ')';
			}
		}
		return $text;
	}

	/** Consume normalized default facts; syntax and source tokens have no authority here. */
	public static function parameter_default(prepared_parameter_default $default_facts, cpp_generation_context $context): string
	{
		if ($default_facts instanceof prepared_property_parameter_default) {
			return self::property_default(object_cast($default_facts, prepared_property_parameter_default::class)->value, $context);
		}
		if ($default_facts instanceof prepared_type_parameter_default) {
			return self::type(object_cast($default_facts, prepared_type_parameter_default::class)->type, $context) . '{}';
		}
		$facts = object_cast($default_facts, prepared_literal_parameter_default::class);
		$value = $facts->value;
		$text = '';
		if ($value instanceof prepared_integer_literal) {
			$text = CPP_Generator::generate_integer(object_cast($value, prepared_integer_literal::class), $context);
		}
		elseif ($value instanceof prepared_float_literal) {
			$text = CPP_Generator::generate_float(object_cast($value, prepared_float_literal::class), $context);
		}
		elseif ($value instanceof prepared_boolean_literal) {
			$text = CPP_Generator::generate_boolean(object_cast($value, prepared_boolean_literal::class), $context);
		}
		elseif ($value instanceof prepared_string_literal) {
			$text = CPP_Generator::generate_string(object_cast($value, prepared_string_literal::class), $context);
		}
		elseif ($value instanceof prepared_enum_case_reference) {
			$text = CPP_Enums::reference(object_cast($value, prepared_enum_case_reference::class), $context);
		}
		else { throw new \LogicException('Unsupported prepared parameter default'); }
		return self::conversion($text, $facts->conversion, $context);
	}

	/** Render only this layout; nested representations contribute header dependencies. */
	public static function generate_record(type_declaration_node $syntax, cpp_generation_context $context): string
	{
		$definition = object_cast($syntax->occurrence(), collected_record::class);
		$key = $definition->qualified_name();
		if (isset($context->record_states[$key])) {
			if ($context->record_states[$key] === cpp_record_state::visiting) {
				throw new \RuntimeException('S2S recursive by-value struct layout is unsupported');
			}
			return '';
		}

		// Mark before descending so a by-value cycle fails before a partial layout is emitted.
		$context->record_states[$key] = cpp_record_state::visiting;
		$text = 'struct ' . CPP_Generator::source_name('record', $key) . "\n{\n";

		$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
		foreach ($fields->items() as $field)
		{
			$type = $field->type;
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$requirement = Type_Queries::is_class_handle($type)
				? cpp_type_requirement::declaration : cpp_type_requirement::complete;
			$representation = CPP_Types::representation_for($type, $requirement);
			CPP_Types::record_dependencies($representation, $context);
			$text .= self::visibility($field->visibility);
			$text .= "\t" . $representation->spelling . ' ' . CPP_Generator::source_name('field', $entry->name);
			$default_value = $field->default_value;
			if ($default_value !== null) {
				$text .= ' = ' . self::property_default($default_value, $context);
			}
			$text .= ";\n";
		}

		$initialization = $syntax->require_preparation()->initialization;
		$static_fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->static_fields;
		foreach ($static_fields->items() as $field) {
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$text .= self::visibility($field->visibility) . "\tstatic "
				. self::type_for($field->type, $context, cpp_type_requirement::declaration) . ' '
				. CPP_Generator::source_name('field', $entry->name) . ";\n";
		}
		$text .= "public:\n";
		if (($initialization !== null) && (weakref_get($syntax->require_preparation()->constructor) === null)) {
			$text .= CPP_Data_Construction::constructor(CPP_Generator::source_name('record', $key), $initialization, $context);
		}
		$context->records .= $text;
		$methods /** Key_Storage_List<collected_method> */ = $syntax->require_preparation()->methods;
		if (q_count($methods->named('__destruct')) !== 0) {
			// A user destructor suppresses implicit C++ moves. Restore ordinary memberwise
			// operations explicitly; defaulted copies remain deleted for noncopyable fields.
			$name = CPP_Generator::source_name('record', $key);
			if (($initialization === null) && (weakref_get($syntax->require_preparation()->constructor) === null)) {
				$context->records .= "\t" . $name . "() = default;\n";
			}
			$context->records .= "\t" . $name . '(const ' . $name . "&) = default;\n"
				. "\t" . $name . '(' . $name . "&&) = default;\n"
				. "\t" . $name . '& operator=(const ' . $name . "&) = default;\n"
				. "\t" . $name . '& operator=(' . $name . "&&) = default;\n";
		}
		if (q_count($methods->items()) === 0) { $context->records .= "};\n\n"; }
		$context->record_states[$key] = cpp_record_state::complete;
		return '';
	}

	public static function static_definitions(collected_record $record, cpp_generation_context $context): string
	{
		$text = '';
		$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->static_fields;
		foreach ($fields->items() as $field) {
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$text .= self::type($field->type, $context) . ' ' . CPP_Generator::source_name('record', $record->qualified_name())
				. '::' . CPP_Generator::source_name('field', $entry->name);
			if ($field->default_value !== null) { $text .= ' = ' . self::property_default($field->default_value, $context); }
			else { $text .= '{}'; }
			$text .= ";\n";
		}
		return $text;
	}

	public static function static_property(prepared_static_property $facts, cpp_generation_context $context): string
	{
		$record = Type_Queries::source_record($facts->record_type);
		$context->headers[CPP_Artifacts::type_header($record->qualified_name())] = true;
		$entry = object_cast(weakref_get($facts->field->declaration), collected_name::class);
		return CPP_Generator::source_name('record', $record->qualified_name()) . '::' . CPP_Generator::source_name('field', $entry->name);
	}

	/** Constant snapshots and runtime constructions share the same typed default boundary. */
	public static function property_default(prepared_property_default $facts, cpp_generation_context $context): string
	{
		$value = '';
		if ($facts instanceof prepared_constant_property_default) {
			$value = CPP_Generator::generate_constant_value(object_cast($facts, prepared_constant_property_default::class)->value, $context);
		}
		else {
			$construction = object_cast($facts, prepared_construction_property_default::class);
			$value = CPP_Data_Construction::generate($construction->construction, $context);
		}
		return self::conversion($value, $facts->conversion, $context);
	}

	/** Locals snapshot value arguments left-to-right; reference arguments keep the original place. */
	public static function generate_call(call_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_call_preparation();
		if ($facts->evaluates_qualifier) {
			$qualifier /** expression_node */ = $syntax->receiver;
			$evaluation = self::qualifier_evaluation($qualifier, $context);
			return self::selected_call($facts, $syntax->arguments, $context, '', null, $evaluation);
		}
		if ($facts->receiver !== null) {
			$receiver /** prepared_method_receiver */ = $facts->receiver;
			$base /** expression_node */ = $syntax->receiver;
			CPP_Types::record_dependencies(CPP_Types::payload($receiver->record_type), $context);
			$value = (new CPP_Syntax($context))->expression($base);
			$name = 'receiver_' . $context->next_temporary;
			$context->next_temporary++;
			$binding = $receiver->lifetime === method_receiver_lifetime::borrow_inline ? 'auto& ' : 'auto ';
			$prefix = $binding . $name . ' = ' . $value . ";\n";
			$arrow = ($receiver->lifetime !== method_receiver_lifetime::borrow_inline) || $base->require_preparation()->type->by_value();
			if ($receiver->lifetime === method_receiver_lifetime::retain_shared) {
				// Check before evaluating arguments; the retained handle owns the selected object.
				$prefix .= '(void)' . $name . ".operator->();\n";
			}
			$method = object_cast(weakref_get($facts->target), collected_method::class);
			$callee = $name . ($arrow ? '->' : '.') . CPP_Generator::source_name('method', $method->name);
			return self::selected_call($facts, $syntax->arguments, $context, $callee, null, $prefix);
		}
		return self::selected_call($syntax->require_call_preparation(), $syntax->arguments, $context, '', null, '');
	}

	/** Discard without copying; qualifier temporaries end before the selected operation. */
	public static function qualifier_evaluation(expression_node $qualifier, cpp_generation_context $context): string
	{
		return '(void)(' . (new CPP_Syntax($context))->expression($qualifier) . ");\n";
	}

	public static function member_reference(static_member_reference_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_preparation();
		$value = '';
		if ($facts instanceof prepared_constant_reference) { $value = CPP_Generator::generate_constant(object_cast($facts, prepared_constant_reference::class), $context); }
		elseif ($facts instanceof prepared_static_property) { $value = self::static_property(object_cast($facts, prepared_static_property::class), $context); }
		else { $value = CPP_Enums::reference($syntax->enum_case_preparation(), $context); }
		if ($syntax->qualifier === null) { return $value; }
		// The comma expression preserves the member's value category without returning a borrow.
		return '([&]() { ' . self::qualifier_evaluation($syntax->qualifier, $context) . ' }(), ' . $value . ')';
	}

	/** Source syntax and future factory consumers supply the same prepared call and ordered expressions. */
	public static function selected_call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context, string $callee, ?canonical_type_use $constructed_type,
		string $prefix): string
	{
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;

		// The immediately invoked lambda sequences arguments and contains their temporaries.
		$return_reference = $facts->signature->reference_return !== null ? '&' : '';
		$return_requirement = self::signature_requirement(false, $facts->signature->reference_return !== null);
		$result_type = $constructed_type ?? $facts->type;
		$text = '([&]() -> ' . self::type_for($result_type, $context, $return_requirement) . $return_reference . " {\n";
		$text .= $prefix;
		$names = '';
		$separator = '';
		$packed_names = '';
		$packed_separator = '';
		foreach ($prepared_arguments as $index => $prepared_argument)
		{
			$argument = $arguments[$prepared_argument->source_index];
			$parameter_index /** int */ = $facts->fixed_count;
			if ($index < $facts->fixed_count) { $parameter_index = $index; }
			$parameter = $parameters[$parameter_index];
			$argument_type = $parameter->element_type ?? $parameter->type;
			// Generate nested calls before assigning the outer temporary name.
			$value = (new CPP_Syntax($context))->expression($argument);
			$name = 'argument_' . $context->next_temporary;
			$context->next_temporary++;
			$boundary = $prepared_arguments[$index]->boundary;
			$reference = $boundary === value_boundary_operation::bind_reference ? '&' : '';
			if ($boundary !== value_boundary_operation::bind_reference) {
				$value = self::conversion(
					$value, $prepared_arguments[$index]->require_conversion(), $context);
			}

			$qualifier = $parameter->access === storage_access::read_only ? 'const ' : '';
			$argument_requirement = self::signature_requirement(false, $boundary === value_boundary_operation::bind_reference);
			$text .= "\t" . $qualifier . self::type_for($argument_type, $context, $argument_requirement) . $reference . ' ' . $name . ' = ' . $value . ";\n";
			if ($boundary === value_boundary_operation::consume_value) {
				$context->headers['utility'] = true;
				$name = 'std::move(' . $name . ')';
			}
			if ($index < $facts->fixed_count) {
				$names .= $separator . $name;
				$separator = ', ';
			}
			else {
				$packed_names .= $packed_separator . $name;
				$packed_separator = ', ';
			}
		}
		// Semantic defaults do not depend on defaults being repeated in a runtime C++ declaration.
		for ($index = q_count($prepared_arguments); $index < $facts->fixed_count; $index++) {
			$default_value /** prepared_parameter_default */ = $parameters[$index]->default_value;
			$name = 'default_argument_' . $context->next_temporary;
			$context->next_temporary++;
			$text .= self::type($parameters[$index]->type, $context) . ' ' . $name . ' = ' . self::parameter_default($default_value, $context) . ";\n";
			$context->headers['utility'] = true;
			$names .= $separator . 'std::move(' . $name . ')';
			$separator = ', ';
		}
		if ($facts->variadic_parameter !== null) {
			$pack /** callable_parameter */ = $facts->variadic_parameter;
			$names .= $separator . self::type($pack->type, $context) . '{' . $packed_names . '}';
		}

		$reference = $callee;
		if ($callee === '') {
			if ($facts->specialization !== null) {
				$specialization /** prepared_compiler_call */ = $facts->specialization;
				$reference = CPP_Compiler_Functions::reference($specialization, $context);
			}
			else {
				$target = object_cast(weakref_get($facts->target), callable_target_i::class);
				$reference = CPP_Callables::reference($target, $context);
			}
		}
		$text .= "\treturn " . $reference . '(' . $names . ");\n}())";
		return $text;
	}

	/** Field identity comes from shared preparation, never a backend name lookup. */
	public static function generate_field_access(field_access_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_field_access_preparation();
		$entry = object_cast(weakref_get($facts->field->declaration), collected_name::class);
		$base = $syntax->base;
		// A reference declaration does not supply the layout needed by member access.
		$payload = CPP_Types::payload($facts->record_type);
		CPP_Types::record_dependencies($payload, $context);
		$arrow = ($facts->receiver_storage !== record_storage_kind::inline_value)
			|| $base->require_preparation()->type->by_value();
		if ($base instanceof variable_reference_node) {
			$reference = object_cast($base, variable_reference_node::class)->require_variable_reference_preparation();
			if (weakref_get($reference->declaration) instanceof collected_method) { $arrow = true; }
		}
		return '(' . (new CPP_Syntax($context))->expression($base) . ')'
			. ($arrow ? '->' : '.') . CPP_Generator::source_name('field', $entry->name);
	}
}
