<?php

/* Emit prepared signatures, value layouts and sequenced call boundaries. */
namespace scpp\compiler;

final class CPP_Declarations
{
	/** Change value category using the already prepared physical type, without a template helper. */
	public static function move(string $value, canonical_type_use $type, cpp_generation_context $context, bool $read_only = false): string
	{
		if (!$context->concrete_types()) {
			$context->headers['utility'] = true;
			return 'std::move(' . $value . ')';
		}
		return 'static_cast<' . ($read_only ? 'const ' : '')
			. self::type_for($type, $context, cpp_type_requirement::declaration) . '&&>(' . $value . ')';
	}

	/** Preparation owns output admission; the bridge only changes physical transport. */
	public static function output(string $expression, canonical_type_use $type, cpp_generation_context $context): string
	{
		// Native representations also need the compiled output boundary.
		if ($context->concrete_types()) {
			return CPP_Runtime_Operations::output($expression, $type, $context);
		}
		$context->headers['lang/php/support/php_string.hpp'] = true;
		return '::scpp::php::echo_one(' . $expression . ')';
	}

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
		$mapping = CPP_Types::representation_for($type, $requirement, $context);
		CPP_Types::record_dependencies($mapping, $context);

		return $mapping->spelling;
	}

	/** Render only the operation selected by shared conversion preparation. */
	public static function conversion(string $expression, conversion_decision $decision, cpp_generation_context $context): string
	{

		if (CPP_Runtime_Operations::concrete_conversion($decision, $context)) {
			return CPP_Runtime_Operations::convert($expression, $decision, $context);
		}
		if ((($decision->operation === conversion_operation::nullable_wrap)
			|| ($decision->operation === conversion_operation::nullable_extract)
			|| ($decision->operation === conversion_operation::nullable_map))
			&& ((Type_Queries::nullable_handle_payload($decision->source_type) !== null)
				|| (Type_Queries::nullable_handle_payload($decision->target_type) !== null))) {
			return self::nullable_handle_conversion($expression, $decision, $context);
		}
		if ($decision->operation === conversion_operation::callable_wrap) {
			if ($context->concrete_types()) { return CPP_Callback_Transport::wrap($expression, $decision->target_type, $context); }
			return '([&]() { auto callback = ' . $expression . '; auto permissions = callback.transfer_permissions(); return '
				. self::type($decision->target_type, $context) . '{static_cast<decltype(callback)&&>(callback), permissions}; }())';
		}
		if ($decision->operation === conversion_operation::identity) {
			return $expression;
		}
		if (($decision->operation === conversion_operation::ownership_upcast)
			|| ($decision->operation === conversion_operation::unique_upcast)) {
			// The runtime's converting handle constructor needs complete ancestry, not just forward declarations.
			self::type($decision->source_type, $context);
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if ($decision->operation === conversion_operation::required_payload_text) {
			$context->headers['lang/php/support/php_string.hpp'] = true;
			return 'scpp::php::to_string((' . $expression . ').require_value("text: required Result payload"))';
		}
		if ($decision->operation === conversion_operation::optional_payload_text) {
			$context->headers['lang/php/support/php_string.hpp'] = true;
			return '[](const auto& text_value) { return text_value.has_value().native_value()'
				. ' ? scpp::php::to_string(text_value.require_value("text: present payload")) : scpp::string_t{}; }('
				. $expression . ')';
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
			|| ($decision->operation === conversion_operation::result_error)
			|| ($decision->operation === conversion_operation::result_false)
			|| ($decision->operation === conversion_operation::result_true)) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if ($decision->operation === conversion_operation::coalesce_selected_value) {
			$context->headers['operators/coalesce/coalesce.hpp'] = true;
			return 'scpp::detail::normalize_coalesce_branch<' . self::type($decision->target_type, $context)
				. '>(' . $expression . ')';
		}
		if ($decision->operation === conversion_operation::nullable_extract) {
			return self::type($decision->target_type, $context) . '((' . $expression
				. ').require_value("nullable required-value extraction"))';
		}
		if ($decision->operation === conversion_operation::result_extract) {
			return self::type($decision->target_type, $context) . '((' . $expression
				. ').require_value("Result required-value extraction"))';
		}
		if ($decision->operation === conversion_operation::integer_to_floating) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if ($decision->operation === conversion_operation::integer_value_cast) {
			return 'static_cast<' . self::type($decision->target_type, $context)
				. '>((' . $expression . ').native_value())';
		}
		if ($decision->operation === conversion_operation::boxed_extract) {
			if ($context->concrete_types()) { throw new \RuntimeException('prelower_templates: boxed extraction requires concrete transport'); }
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

	/** Shared storage carries optional presence without a second physical wrapper. */
	private static function nullable_handle_conversion(string $expression, conversion_decision $decision,
		cpp_generation_context $context): string
	{
		$target = self::type($decision->target_type, $context);
		$value = 'nullable_value';
		$converted = $value;
		if ($decision->payload_conversion !== null) {
			$converted = self::conversion($value, $decision->payload_conversion, $context);
		}
		$prefix = '([&]() -> ' . $target . ' { auto ' . $value . ' = ' . $expression . '; ';
		if ($decision->operation === conversion_operation::nullable_extract) {
			$context->headers['scpp/runtime_error.hpp'] = true;
			$prefix .= 'if (!static_cast<bool>(' . $value . '.has_value())) { throw scpp::runtime_error('
				. '"nullable required-value extraction", "invalid_nullable_unwrap_empty", "nullable", "required value"); } ';
		}
		elseif ($decision->operation === conversion_operation::nullable_map) {
			$prefix .= 'if (!static_cast<bool>(' . $value . '.has_value())) { return ' . $target . '{}; } ';
		}
		return $prefix . 'return ' . $target . '{' . $converted . '}; }())';
	}

	/** Render the prepared condition operation using the established runtime authority. */
	public static function truthiness(string $expression, truthiness_decision $decision, cpp_generation_context $context): string
	{
		if ($decision->operation === truthiness_operation::always_true) {
			return '(static_cast<void>(' . $expression . '), true)';
		}
		if ($decision->operation === truthiness_operation::sentinel_presence) {
			if ($context->concrete_types()) { return CPP_Runtime_Operations::wrapper_isset($decision->source_type, $expression, $context); }
			$context->headers['lang/php/operators/isset/isset.hpp'] = true;
			return '(scpp::php::isset(' . $expression . ')).native_value()';
		}
		if ($decision->operation === truthiness_operation::value_presence) {
			if ($context->concrete_types()) { return CPP_Runtime_Operations::wrapper_presence($decision->source_type, $expression, $context); }
			return 'static_cast<bool>((' . $expression . ').has_value())';
		}
		if ($decision->operation === truthiness_operation::boolean_identity) {
			// Reading a borrowed bool's member requires its definition, not just the signature's forward declaration.
			self::type($decision->source_type, $context);
			return '(' . $expression . ').native_value()';
		}
		if ($context->concrete_types() && ($decision->operation === truthiness_operation::error_condition)) {
			return CPP_Runtime_Operations::error_condition($decision->source_type, $expression, $context);
		}
		if (($decision->operation === truthiness_operation::callable_presence)
			|| ($decision->operation === truthiness_operation::scalar_condition)
			|| ($decision->operation === truthiness_operation::boxed_condition)
			|| ($decision->operation === truthiness_operation::error_condition)
			|| ($decision->operation === truthiness_operation::result_condition)
			|| ($decision->operation === truthiness_operation::nullable_condition)) {
			if (($context->specialization_engine !== null) && ($decision->operation === truthiness_operation::scalar_condition)
				&& (Type_Queries::canonical($decision->source_type)->family() === type_family::integer)) { return '((' . $expression . ').native_value() != 0)'; }
			if ($context->concrete_types()) { throw new \RuntimeException('prelower_templates: runtime condition requires concrete transport'); }
			if ($decision->operation === truthiness_operation::nullable_condition) {
				$context->headers['scpp/cast.hpp'] = true;
			}
			$context->headers['lang/php/operators/conditional/condition_truthiness.hpp'] = true;
			return 'scpp::php::condition_truthy(' . $expression . ').native_value()';
		}
		throw new \LogicException('C++ truthiness operation is not implemented');
	}

	/** Prototypes only name records; value definitions need layouts, reference definitions do not. */
	public static function signature(callable_declaration_node $syntax, cpp_generation_context $context, bool $prototype, bool $async_implementation = false): string
	{
		$facts = $syntax->require_preparation();
		$parts = '';
		$separator = '';
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		foreach ($parameters as $storage) {
			$entry = object_cast(weakref_get($storage->declaration), collected_name::class);
			$parameter = $storage->contract;
			$parts .= $separator . self::parameter_type($parameter, $context, $prototype) . ' ' . CPP_Generator::local_name($entry);
			// Source calls materialize every default and pack; C++ declarations carry no defaults.
			$separator = ', ';
		}

		$function_declaration = object_cast($syntax->occurrence(), collected_source_callable::class);
		$return_prefix = self::return_type($facts->signature, $context, $prototype) . ' ';
		if ($async_implementation) { $return_prefix = CPP_Async::native_task($facts->signature->return_type, $context) . ' '; }
		if ($function_declaration instanceof collected_method) {
			$method = object_cast($function_declaration, collected_method::class);
			$record_name = CPP_Generator::source_name('record', $method->declaring_type()->qualified_name());
			if (CPP_Record_Construction::keyed($method)) {
				$parts = $record_name . '::scpp_construction_key' . ($parts === '' ? '' : ', ' . $parts);
			}
			$name = $syntax->name === '__construct' ? $record_name : CPP_Callables::method_name($method);
			$destructor = $syntax->name === '__destruct';
			if ($destructor) { $name = '~' . $record_name; }
			if (!$prototype) { $name = $record_name . '::' . $name; }
			$prefix = (($syntax->name === '__construct') || $destructor) ? '' : $return_prefix;
			if ($prototype && $facts->static_member) { $prefix = 'static ' . $prefix; }
			return $prefix . $name . '(' . $parts . ')' . ($destructor ? ' noexcept' : '');
		}
		return $return_prefix . ($async_implementation ? 'coroutine_' : '') . CPP_Callables::source_reference(object_cast($function_declaration, collected_function::class)) . '(' . $parts . ')';
	}

	private static function signature_requirement(bool $prototype, bool $reference): cpp_type_requirement
	{
		return ($prototype || $reference) ? cpp_type_requirement::declaration : cpp_type_requirement::stored_value;
	}

	/** Shared by authored signatures and declaration-independent dispatch entries. */
	public static function parameter_type(callable_parameter $parameter, cpp_generation_context $context, bool $prototype): string
	{
		$reference = $parameter->mode === passing_mode::reference;
		$mapping = CPP_Types::parameter_representation($parameter, self::signature_requirement($prototype, $reference), $context);
		CPP_Types::record_dependencies($mapping, $context);
		return $mapping->spelling;
	}

	public static function return_type(callable_signature $signature, cpp_generation_context $context, bool $prototype): string
	{
		$reference = $signature->reference_return !== null;
		$mapping = CPP_Types::return_representation($signature, self::signature_requirement($prototype, $reference), $context);
		CPP_Types::record_dependencies($mapping, $context);
		return $mapping->spelling;
	}

	/** Property-backed inputs initialize members before the body, preserving parameter values. */
	public static function constructor_initializers(collected_method $method, cpp_generation_context $context): string
	{
		$text = '';
		$base = $method->callable_syntax()->require_body()->base_initialization;
		if ($base !== null) {
			$mapping = CPP_Types::payload($base->base_type, $context);
			CPP_Types::record_dependencies($mapping, $context);
			$arguments /** Storage<expression_node> */ = new Storage /** Storage<expression_node> */();
			$source_call = weakref_get($base->source_call);
			if ($source_call !== null) { $arguments = $source_call->arguments; }
			$values = '';
			if ($base->call !== null) { $values = self::initializer_arguments($base->call, $arguments, $context); }
			// Braced base initialization sequences parameter initializations left-to-right,
			// without materializing/copying a complete base object.
			$text = ' : ' . $mapping->spelling . '{' . $values . '}';
		}
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

	/** Fixed by-value constructors use the same conversions/defaults, in braced-list order. */
	private static function initializer_arguments(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context): string
	{
		$values = '';
		$separator = '';
		$target = object_cast(weakref_get($facts->target), collected_method::class);
		$values = CPP_Record_Construction::key($target);
		if ($values !== '') { $separator = ', '; }
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		foreach ($parameters as $index => $parameter) {
			$value = '';
			if ($index < $prepared_arguments->count()) {
				$argument = $prepared_arguments[$index];
				$value = self::argument_value($argument, $arguments, $context);
			}
			else { $value = self::parameter_default($parameter->default_value, $context); }
			// An explicit typed value boundary also handles copies from lvalues and moves.
			$values .= $separator . self::type($parameter->type, $context) . '(' . $value . ')';
			$separator = ', ';
		}
		return $values;
	}

	/** Shared explicit-argument evaluation and typed boundary for calls and base initialization. */
	private static function argument_value(prepared_call_argument $argument, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context): string
	{
		$value = (new CPP_Syntax($context))->expression($arguments[$argument->source_index]);
		if ($argument->boundary !== value_boundary_operation::bind_reference) {
			$value = self::conversion($value, $argument->require_conversion(), $context);
		}
		return $value;
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
		elseif ($value instanceof prepared_null_literal) {
			self::type($value->type, $context);
			$text = 'scpp::null';
		}
		elseif ($value instanceof prepared_enum_case_reference) {
			$text = CPP_Enums::reference(object_cast($value, prepared_enum_case_reference::class), $context);
		}
		else { throw new \LogicException('Unsupported prepared parameter default'); }
		// Constant defaults have no caller locals. Give concrete conversion sequencing
		// a function scope even when the default appears in a namespace declaration.
		if (CPP_Runtime_Operations::concrete_conversion($facts->conversion, $context)) {
			return '([]() { return ' . self::conversion($text, $facts->conversion, $context) . '; }())';
		}
		return self::conversion($text, $facts->conversion, $context);
	}

	/** Render only this layout; nested representations contribute header dependencies. */
	public static function generate_record(type_declaration_node $syntax, cpp_generation_context $context, bool $outline = false): string
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
		// Relationship spelling belongs to final assembly, not the cached field-layout fragment.
		$text = '';

		$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
		foreach ($fields->items() as $field)
		{
			$type = $field->type;
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$requirement = Type_Queries::is_nominal_handle($type)
				? cpp_type_requirement::declaration : cpp_type_requirement::complete;
			$representation = CPP_Types::representation_for($type, $requirement, $context);
			CPP_Types::record_dependencies($representation, $context);
			$text .= self::visibility($field->visibility);
			$text .= "\t" . $representation->spelling . ' ' . CPP_Generator::source_name('field', $entry->name);
			$default_value = $field->default_value;
			if (($default_value !== null) && !$outline) {
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
		if ($outline) { $text .= "\t" . CPP_Generator::source_name('record', $key) . "();\n"; }
		$constructor = weakref_get($syntax->require_preparation()->constructor);
		if ($constructor !== null) {
			if ($constructor->declaring_type() !== $syntax->occurrence()) {
				$base_mapping = CPP_Types::payload($syntax->require_preparation()->base_type, $context);
				$text .= "\tusing " . $base_mapping->spelling . '::' . $base_mapping->spelling . ";\n";
			}
		}
		if (($initialization !== null) && (weakref_get($syntax->require_preparation()->constructor) === null)) {
			$text .= CPP_Data_Construction::constructor(CPP_Generator::source_name('record', $key), $initialization, $context);
		}
		if ($syntax->declaration_kind === type_declaration_kind::union_type) {
			// No member is active until a whole payload write constructs it.
			$text .= "\t" . CPP_Generator::source_name('record', $key) . "() noexcept {}\n";
		}
		$context->records .= $text;
		$context->record_states[$key] = cpp_record_state::complete;
		return '';
	}

	/** Shared declaration spelling; interfaces add no record fields or source constructors. */
	public static function type_opening(collected_member_owner $owner, cpp_generation_context $context): string
	{
		$relations = $owner->relationship_work->require_facts();
		$keyword = $owner->syntax()->declaration_kind === type_declaration_kind::union_type ? 'union ' : 'struct ';
		$text = $keyword . CPP_Generator::source_name('record', $owner->qualified_name());
		if ($relations->final_type) { $text .= ' final'; }
		$separator = ' : ';
		$base = weakref_get($relations->base);
		if ($base !== null) {
			$context->headers[CPP_Artifacts::type_header($base->qualified_name())] = true;
			$text .= $separator . 'public ' . CPP_Generator::source_name('record', $base->qualified_name());
			$separator = ', ';
		}
		if (($base === null) && $relations->exception_type) {
			$root = CPP_Types::payload(Language_Types::exception_type(Model::$language_scope));
			CPP_Types::record_dependencies($root, $context);
			$text .= $separator . 'public ' . $root->spelling;
			$separator = ', ';
		}
		foreach ($relations->interfaces as $interface) {
			$context->headers[CPP_Artifacts::type_header($interface->qualified_name())] = true;
			$text .= $separator . 'public virtual ' . CPP_Generator::source_name('record', $interface->qualified_name());
			$separator = ', ';
		}
		$kind = $owner->syntax()->declaration_kind;
		if (($kind === type_declaration_kind::class_type) || ($kind === type_declaration_kind::interface_type)) {
			$text .= $separator . 'public virtual ' . CPP_Self::base($context);
		}
		return $text . "\n{\n";
	}

	/** Contract-owned virtual cleanup must preserve the ordinary copy/move/default operations. */
	public static function lifecycle_special_members(collected_record $record, prepared_type_contract $contract): string
	{
		$layout = $record->syntax()->require_preparation();
		$methods /** Key_Storage_List<collected_method> */ = $layout->methods;
		$authored_destructor = q_count($methods->named('__destruct')) !== 0;
		if (!$contract->polymorphic && !$authored_destructor) { return ''; }
		$name = CPP_Generator::source_name('record', $record->qualified_name());
		$text = "public:\n";
		if (!$authored_destructor) { $text .= "\tvirtual ~" . $name . "() noexcept = default;\n"; }
		return $text . self::defaulted_special_members($record);
	}

	/** Any declared destructor suppresses implicit moves; restore the shared lifecycle policy. */
	private static function defaulted_special_members(collected_record $record): string
	{
		$layout = $record->syntax()->require_preparation();
		$name = CPP_Generator::source_name('record', $record->qualified_name());
		$text = '';
		$constructor = weakref_get($layout->constructor);
		$default_constructor = ($constructor === null) && ($layout->initialization === null);
		if ($constructor !== null) {
			if ($constructor->declaring_type() !== $record) {
				$parameters /** Storage<callable_parameter> */ = $constructor->signature()->parameters;
				$default_constructor = $parameters->is_empty();
			}
		}
		if ($default_constructor) {
			$text .= "\t" . $name . "() = default;\n";
		}
		return $text . "\t" . $name . '(const ' . $name . "&) = default;\n"
			. "\t" . $name . '(' . $name . "&&) = default;\n"
			. "\t" . $name . '& operator=(const ' . $name . "&) = default;\n"
			. "\t" . $name . '& operator=(' . $name . "&&) = default;\n";
	}

	public static function static_definitions(collected_record $record, cpp_generation_context $context): string
	{
		$text = '';
		$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->static_fields;
		foreach ($fields->items() as $field) {
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$text .= self::type($field->type, $context) . ' ' . CPP_Generator::source_name('record', $record->qualified_name())
				. '::' . CPP_Generator::source_name('field', $entry->name);
			if ($field->default_value !== null) { $text .= ' = ' . self::static_initializer($field, $context); }
			else { $text .= '{}'; }
			$text .= ";\n";
		}
		return $text;
	}

	/** Namespace-scope storage gives sequenced conversions/construction a function scope. */
	private static function static_initializer(prepared_field $field, cpp_generation_context $context): string
	{
		return '([]() -> ' . self::type($field->type, $context) . ' { return '
			. self::property_default($field->default_value, $context) . '; }())';
	}

	public static function static_property(prepared_static_property $facts, cpp_generation_context $context): string
	{
		if ($facts->field instanceof registered_property) {
			return CPP_Runtime_Catalog::property(object_cast($facts->field, registered_property::class), $context);
		}
		$field = object_cast($facts->field, prepared_field::class);
		$record = Type_Queries::source_record($facts->record_type);
		$context->headers[CPP_Artifacts::type_header($record->qualified_name())] = true;
		$entry = object_cast(weakref_get($field->declaration), collected_name::class);
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
		if ($facts->specialization instanceof prepared_async_call) {
			return CPP_Async::call($syntax, object_cast($facts->specialization, prepared_async_call::class), $context);
		}
		if ($syntax->callee !== null) {
			$value = (new CPP_Syntax($context))->expression($syntax->callee);
			$name = 'callee_' . $context->next_temporary;
			$context->next_temporary++;
			return self::selected_call($facts, $syntax->arguments, $context, $name, null, 'auto ' . $name . ' = ' . $value . ";\n");
		}
		if ($facts->evaluates_qualifier) {
			$qualifier /** expression_node */ = $syntax->receiver;
			$evaluation = self::qualifier_evaluation($qualifier, $context);
			return self::selected_call($facts, $syntax->arguments, $context, '', null, $evaluation);
		}
		if ($facts->receiver !== null) {
			$receiver /** prepared_method_receiver */ = $facts->receiver;
			$target = weakref_get($facts->target);
			if ($context->concrete_types() && ($target instanceof runtime_function_definition)
				&& ($receiver->lifetime === method_receiver_lifetime::current_instance)) {
				return CPP_Concrete_Members::current_instance($facts, $context);
			}
			$concrete_shared = $context->concrete_types() && ($target instanceof runtime_function_definition)
				&& ($receiver->lifetime === method_receiver_lifetime::retain_shared);
			if (!$concrete_shared) { CPP_Types::record_dependencies(CPP_Types::payload($receiver->record_type, $context), $context); }
			$value = 'this';
			$arrow = true;
			if ($receiver->lifetime !== method_receiver_lifetime::current_instance) {
				$base /** expression_node */ = $syntax->receiver;
				$value = (new CPP_Syntax($context))->expression($base);
				$arrow = ($receiver->lifetime !== method_receiver_lifetime::borrow_inline) || $base->require_preparation()->type->by_value();
			}
			$name = 'receiver_' . $context->next_temporary;
			$context->next_temporary++;
			$binding = $receiver->lifetime === method_receiver_lifetime::borrow_inline ? 'auto& ' : 'auto ';
			$prefix = $binding . $name . ' = ' . $value . ";\n";
			if ($receiver->lifetime === method_receiver_lifetime::retain_shared) {
				// Check before evaluating arguments; the retained handle owns the selected object.
				$prefix .= $concrete_shared ? 'scpp::internal::require_receiver(' . $name . ");\n"
					: '(void)' . $name . ".operator->();\n";
			}
			$target = weakref_get($facts->target);
			if ($target instanceof runtime_function_definition) {
				if ($concrete_shared) { $arrow = false; }
				$member_name = '';
				$engine = $context->specialization_engine;
				if ($engine !== null) { $member_name = CPP_Concrete_Members::reference($facts, $context); }
				else { $member_name = CPP_Callables::reference($target, $context); }
				$callee = $name . ($arrow ? '->' : '.') . $member_name;
				return self::selected_call($facts, $syntax->arguments, $context, $callee, null, $prefix);
			}
			$callee = self::method_reference($facts, $name, $arrow, $context);
			return self::selected_call($facts, $syntax->arguments, $context, $callee, null, $prefix);
		}
		return self::selected_call($facts, $syntax->arguments, $context, '', null, '', null, CPP_Callsites::for_call($syntax, $context));
	}

	/** One source dispatch spelling for ordinary calls and concrete ABI bridges. */
	public static function method_reference(prepared_call $facts, string $name, bool $arrow, cpp_generation_context $context): string
	{
		$receiver /** prepared_method_receiver */ = $facts->receiver;
		$method = object_cast(weakref_get($facts->target), collected_method::class);
		CPP_Types::record_dependencies(CPP_Types::payload($receiver->record_type, $context), $context);
		$qualification = '';
		if ($receiver->dispatch_type !== null) {
			$mapping = CPP_Types::payload($receiver->dispatch_type, $context);
			CPP_Types::record_dependencies($mapping, $context);
			$qualification = $mapping->spelling . '::';
		}
		$member_name = CPP_Generator::source_name('method', $method->name);
		if ($receiver->dispatch_type !== null) { $member_name = CPP_Callables::method_name($method); }
		return $name . ($arrow ? '->' : '.') . $qualification . $member_name;
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
		return '(' . CPP_Sequence::open('void', $context) . self::qualifier_evaluation($syntax->qualifier, $context)
			. CPP_Sequence::result('', $context) . CPP_Sequence::close($context) . ', ' . $value . ')';
	}

	/** Source syntax and future factory consumers supply the same prepared call and ordered expressions. */
	public static function selected_call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context, string $callee, ?canonical_type_use $constructed_type,
		string $prefix, ?cpp_runtime_operation $transport = null, ?cpp_callsite $callsite = null): string
	{

		if (($transport === null) && ($context->concrete_types() && ($facts->receiver === null))
			&& (weakref_get($facts->target) instanceof runtime_function_definition)) {
			if (($callee !== '') || ($prefix !== '') || ($constructed_type !== null)) {
				throw new \RuntimeException('prelower_templates: receiver call transport is not yet implemented');
			}
			return CPP_Runtime_Operations::call($facts, $arguments, $context, $callsite);
		}
		if (($transport === null) && $context->concrete_types() && ($facts->specialization instanceof prepared_enum_call)) {
			$transport = CPP_Compiler_Functions::transport(object_cast($facts->specialization, prepared_enum_call::class));
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		if (q_count($prepared_arguments) === 1) {
			if ($prepared_arguments[0]->boundary === value_boundary_operation::observe_value) {
				return self::observed_call($facts, $arguments, $context, $callee, $constructed_type, $prefix);
			}
		}

		// The immediately invoked lambda sequences arguments and contains their temporaries.
		$return_reference = $facts->signature->reference_return !== null ? '&' : '';
		$return_requirement = self::signature_requirement(false, $facts->signature->reference_return !== null);
		$result_type = $constructed_type ?? $facts->type;
		$text = CPP_Sequence::open(self::type_for($result_type, $context, $return_requirement), $context, $return_reference !== '');
		$text .= $prefix;
		$physical_arguments /** vector<string> */ = [];
		$construction_key = '';
		$names = '';
		$separator = '';
		if ($constructed_type !== null) {
			$constructor = object_cast(weakref_get($facts->target), collected_method::class);
			$names = CPP_Record_Construction::key($constructor);
			if ($names !== '') {
				$separator = ', ';
				if ($transport !== null) {
					$construction_key = 'construction_key_' . $context->next_temporary++;
					$text .= 'const auto ' . $construction_key . ' = ' . $names . ";\n";
				}
			}
		}
		$transfer_checks = '';
		$packed_names = '';
		$packed_separator = '';
		$packed_values /** vector<string> */ = [];
		foreach ($prepared_arguments as $index => $prepared_argument)
		{
			$parameter_index /** int */ = $facts->fixed_count;
			if ($index < $facts->fixed_count) { $parameter_index = $index; }
			$parameter = $parameters[$parameter_index];
			$argument_type = $parameter->element_type ?? $parameter->type;
			// Generate nested calls before assigning the outer temporary name.
			$value = self::argument_value($prepared_argument, $arguments, $context);
			$name = 'argument_' . $context->next_temporary;
			$context->next_temporary++;
			$boundary = $prepared_arguments[$index]->boundary;
			$borrow = $prepared_argument->borrow_snapshot;
			$reference = (($boundary === value_boundary_operation::bind_reference) || $borrow) ? '&' : '';

			// Pack access constrains the completed vector, not its owned element snapshots.
			$read_only_input = ($parameter->element_type === null) && ($parameter->access === storage_access::read_only);
			$qualifier = ($read_only_input || $borrow) ? 'const ' : '';
			$argument_requirement = self::signature_requirement(false, ($boundary === value_boundary_operation::bind_reference) || $borrow);
			$text .= "\t" . $qualifier . self::type_for($argument_type, $context, $argument_requirement) . $reference . ' ' . $name . ' = ' . $value . ";\n";
			if ($parameter->transfer_policy !== task_transfer_policy::none) {
				$policy = $parameter->transfer_policy === task_transfer_policy::joined_shared ? 'joined_shared' : 'isolated_transfer';
				$context->headers['scpp/task_permissions.hpp'] = true;
				$transfer_checks .= $name . '.require_transfer(scpp::task_policy::' . $policy . ");\n";
			}
			if ($transport !== null) { $physical_arguments[] = $name; }
			if ($boundary === value_boundary_operation::consume_value) {
				$name = self::move($name, $argument_type, $context, $read_only_input);
			}
			if ($index < $facts->fixed_count) {
				$names .= $separator . $name;
				$separator = ', ';
			}
			else {
				$packed_values[] = $name;
				$packed_names .= $packed_separator . $name;
				$packed_separator = ', ';
			}
		}
		// Semantic defaults do not depend on defaults being repeated in a runtime C++ declaration.
		for ($index = q_count($prepared_arguments); $index < $facts->fixed_count; $index++) {
			$default_value /** prepared_parameter_default */ = $parameters[$index]->default_value;
			$name = 'default_argument_' . $context->next_temporary;
			$context->next_temporary++;
			$text .= self::type_for($parameters[$index]->type, $context, cpp_type_requirement::stored_value) . ' ' . $name . ' = ' . self::parameter_default($default_value, $context) . ";\n";
			if ($transport !== null) { $physical_arguments[] = $name; }
			// An omitted reference borrows this call-local owner until invocation completes.
			$names .= $separator . ($parameters[$index]->mode === passing_mode::reference
				? $name : self::move($name, $parameters[$index]->type, $context));
			$separator = ', ';
		}
		$text .= $transfer_checks;
		if ($facts->variadic_parameter !== null) {
			$pack /** callable_parameter */ = $facts->variadic_parameter;
			$names .= $separator . (($context->concrete_types() && (q_count($packed_values) > 0))
				? self::concrete_value_pack($pack, $packed_values, $context)
				: self::type($pack->type, $context) . '{' . $packed_names . '}');
		}

		if ($transport !== null) {
			return $text . CPP_Sequence::result(CPP_Runtime_Operations::invoke($transport, $physical_arguments, $context, $construction_key), $context, $return_reference !== '') . CPP_Sequence::close($context);
		}
		$reference = $callee;
		if ($callee === '') {
			if ($facts->specialization !== null) {
				$specialization /** prepared_compiler_call */ = $facts->specialization;
				$reference = CPP_Compiler_Functions::reference($specialization, $context);
			}
			else {
				$reference = CPP_Callables::call_reference($facts, $context);
			}
		}
		$names = CPP_Callsites::arguments($callsite, $names);
		$text .= CPP_Sequence::result($reference . '(' . $names . ')', $context, $return_reference !== '') . CPP_Sequence::close($context);
		return $text;
	}

	/** Construct an owned pack only after every explicit argument snapshot has settled. */
	private static function concrete_value_pack(callable_parameter $parameter, array $values /** vector<string> */,
		cpp_generation_context $context): string
	{
		$element /** canonical_type_use */ = $parameter->element_type;
		$language = Model::$language_scope;
		if (!$element->matches(Language_Types::integer($language))
			&& !$element->matches(Language_Types::floating($language))
			&& !$element->matches(Language_Types::boolean($language))
			&& !$element->matches(Language_Types::string_type($language)))
		{
			throw new \RuntimeException('prelower_templates: wider value-pack elements require separate transport');
		}
		$binding = Model::$initialization_contracts->existing($parameter->type, false);
		if ($binding === null)
		{
			throw new \LogicException('Prepared value pack requires its list initialization binding');
		}
		$buffer_type = CPP_Initializer_Buffer::reference($binding, null, $element, $context);
		$buffer = 'call_pack_' . $context->next_temporary;
		$context->next_temporary++;
		$text = CPP_Sequence::open(self::type($parameter->type, $context), $context)
			. $buffer_type . ' ' . $buffer . '(' . q_count($values) . '); ';
		foreach ($values as $value)
		{
			$text .= $buffer . '.append(&(' . $value . ')); ';
		}
		return $text . CPP_Sequence::result($buffer . '.finish()', $context) . CPP_Sequence::close($context);
	}

	/** One full expression keeps argument/owner temporaries alive without retaining a borrow. */
	private static function observed_call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context, string $callee, ?canonical_type_use $constructed_type, string $prefix): string
	{
		if (($constructed_type !== null) || ($facts->signature->reference_return !== null)) {
			throw new \LogicException('Observation requires a direct non-retaining runtime call');
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		$argument = $prepared_arguments[0];
		$value = (new CPP_Syntax($context))->expression($arguments[$argument->source_index]);
		$type = self::type($parameters[0]->type, $context);
		$reference = $callee !== '' ? $callee : CPP_Callables::call_reference($facts, $context);
		$call = $reference . '(static_cast<const ' . $type . '&>(' . $value . '))';
		if ($prefix === '') { return $call; }
		return CPP_Sequence::open(self::type($facts->type, $context), $context)
			. $prefix . CPP_Sequence::result($call, $context) . CPP_Sequence::close($context);
	}

	/** Field identity comes from shared preparation, never a backend name lookup. */
	public static function generate_field_access(field_access_node $syntax, cpp_generation_context $context, ?string $bound_base = null): string
	{
		if (CPP_Provider_Properties::applies($syntax, $context)) {
			if ($bound_base !== null) {
				throw new \RuntimeException('prelower_templates: provider property storage projections require separate transport');
			}
			return CPP_Provider_Properties::read($syntax, $context);
		}
		$facts = $syntax->require_field_access_preparation();
		$name = '';
		if ($facts->field instanceof registered_property) {
			$name = CPP_Runtime_Catalog::property(object_cast($facts->field, registered_property::class), $context);
		}
		else {
			$field = object_cast($facts->field, prepared_field::class);
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$name = CPP_Generator::source_name('field', $entry->name);
		}
		$base = $syntax->base;
		// A reference declaration does not supply the layout needed by member access.
		$payload = CPP_Types::payload($facts->record_type, $context);
		CPP_Types::record_dependencies($payload, $context);
		$arrow = ($facts->receiver_storage !== record_storage_kind::inline_value)
			|| $base->require_preparation()->type->by_value();
		if ($base instanceof variable_reference_node) {
			$reference = object_cast($base, variable_reference_node::class)->require_variable_reference_preparation();
			if (weakref_get($reference->declaration) instanceof collected_method) { $arrow = true; }
		}
		return '(' . ($bound_base ?? (new CPP_Syntax($context))->expression($base)) . ')'
			. ($arrow ? '->' : '.') . $name;
	}

	/** Bind storage projections while retaining each shared owner through later effects. */
	public static function bind_storage_receiver(expression_node $syntax, cpp_generation_context $context,
		bool $value_read = false): cpp_assignment_sequence
	{
		$result = new cpp_assignment_sequence();
		if ($value_read && ($syntax instanceof field_access_node)
			&& CPP_Provider_Properties::applies(object_cast($syntax, field_access_node::class), $context)) {
			$result->value = CPP_Provider_Properties::read(object_cast($syntax, field_access_node::class), $context);
			return $result;
		}
		if (!($syntax instanceof field_access_node)) {
			$result->value = (new CPP_Syntax($context))->expression($syntax);
			return $result;
		}
		$field = object_cast($syntax, field_access_node::class);
		$base = self::bind_storage_receiver($field->base, $context);
		$name = 'storage_owner_' . $context->next_temporary;
		$context->next_temporary++;
		// Nonshared reads may start at a temporary inline value; extend its lifetime without copying.
		$binding = $field->require_field_access_preparation()->receiver_storage === record_storage_kind::shared ? 'auto ' : 'auto&& ';
		if ($field->base instanceof variable_reference_node) {
			$reference = object_cast($field->base, variable_reference_node::class)->require_variable_reference_preparation();
			if (weakref_get($reference->declaration) instanceof collected_method) { $binding = 'auto '; }
		}
		$result->statements = $base->statements . $binding . $name . ' = ' . $base->value . '; ';
		$result->value = self::generate_field_access($field, $context, $name);
		return $result;
	}
}
