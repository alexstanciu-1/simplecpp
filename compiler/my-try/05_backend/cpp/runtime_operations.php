<?php
namespace scpp\compiler;

/** Concrete bridges consume selected contracts; family algorithms remain in the runtime. */
final class CPP_Runtime_Operations
{
	private static function join(array $parts /** vector<string> */): string
	{
		$result = '';
		$separator = '';
		foreach ($parts as $part) { $result .= $separator . $part; $separator = ', '; }
		return $result;
	}

	public static function name(cpp_runtime_operation $request): string
	{
		// Preserve the existing identity seed while retiring the old indexed-write fields.
		$key = $request->kind_name() . ':' . $request->spelling . ':store';
		if ($request->enum_helper !== null) { $key .= ':' . CPP_Compiler_Functions::identity($request->enum_helper); }
		if ($request->ownership_observation !== null) {
			$observation /** ownership_observation_kind */ = $request->ownership_observation;
			$key .= ':' . Ownership_Observation_Kind_Name::text($observation);
		}
		if ($request->object_type !== null) {
			$key .= ':' . ($request->object_type->operation === object_type_operation::checked_cast ? 'cast' : 'test')
				. ':' . CPP_Opaque_Representation::identity($request->object_type->target_payload);
		}
		if ($request->callsite !== null) { $key .= ':callsite=' . json_quote($request->callsite->path) . ':' . $request->callsite->line; }
		if ($request->property !== null) {
			$property_context = new cpp_generation_context();
			$key .= ':property=' . $request->property->definition->identity . ':'
				. CPP_Runtime_Catalog::property($request->property, $property_context);
			foreach ($property_context->headers as $header => $used) { $key .= ':header=' . $header; }
		}
		if ($request->iteration !== null) {
			$key .= ':key=' . CPP_Opaque_Representation::identity($request->iteration->key_type)
				. ':value=' . CPP_Opaque_Representation::identity($request->iteration->value_type);
		}
		if ($request->call !== null) {
			if ($request->call->receiver !== null) {
				$lifetime /** method_receiver_lifetime */ = $request->call->receiver->lifetime;
				$key .= ':receiver=' . Method_Receiver_Lifetime_Name::text($lifetime);
			}
			$key .= ':' . ((($request->kind === cpp_runtime_operation_kind::concrete_member) || ($request->kind === cpp_runtime_operation_kind::runtime_call))
				? CPP_Callables::argument_identity($request->call) : CPP_Source_Calls::identity($request->call));
		}
		if ($request->construction !== null) {
			$construction /** prepared_object_construction */ = $request->construction;
			$storage /** record_storage_kind */ = $construction->storage;
			$key .= ':payload=' . CPP_Opaque_Representation::identity($construction->constructed_type)
				. ':storage=' . Record_Storage_Kind_Name::text($storage);
		}
		if ($request->record_construction !== null) {
			$record = $request->record_construction;
			$key .= ':record=' . CPP_Opaque_Representation::identity($record->payload_type)
				. ':' . Record_Storage_Kind_Name::text($record->storage);
		}
		if ($request->binding !== null) { $key .= ':' . $request->binding->target()->key() . ':' . $request->binding->symbol() . ':' . $request->binding->header() . ($request->binding->native_payloads ? ':native_payloads' : ''); }
		if ($request->initialization !== null) { $key .= ':' . CPP_Data_Construction::binding_identity($request->initialization); }
		if ($request->decision !== null) { $key .= ':' . $request->decision->operation_name(); }
		if ($request->conversion !== null) { $key .= ':' . $request->conversion->operation_name(); }
		if ($request->result !== null) { $key .= ':result=' . CPP_Opaque_Representation::identity($request->result); }
		foreach ($request->inputs as $input) { $key .= ':input=' . CPP_Opaque_Representation::identity($input); }
		return 'runtime_' . $request->kind_name() . '_' . content_sha256($key);
	}

	/** Inputs designate existing objects. Calling code owns sequencing and lifetime. */
	public static function invoke(cpp_runtime_operation $request, array $expressions /** vector<string> */,
		cpp_generation_context $context, string $construction_key): string
	{
		$entry = self::reference($request, $context);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$parts /** vector<string> */ = [];
		foreach ($inputs as $position => $input) {
			$operand = new cpp_runtime_operand($input, $expressions[$position]);
			// Source constructors consume application representations, not native unwrapped values.
			$parts[] = ($context->concrete_types() && (CPP_Source_Calls::construction_transport($request)
				|| (self::take_application_output($request) && ($position === 0))))
				? '&(' . $operand->expression . ')' : self::address($operand, $context);
		}
		if (CPP_Source_Calls::construction_key_type($request) !== '') {
			if ($construction_key === '') { throw new \LogicException('Constructor transport requires an authorized key'); }
			$parts[] = '&(' . $construction_key . ')';
		}
		return 'scpp::internal::' . $entry->name . '(' . self::join($parts) . ')';
	}

	/** Record every helper use with its fragment, including a reused engine entry. */
	public static function reference(cpp_runtime_operation $request, cpp_generation_context $context): cpp_specialization_entry
	{
		$engine /** CPP_Specializations */ = $context->specialization_engine;
		$entry = $engine->entry($engine->request_operation($request));
		$context->runtime_operations[$entry->name] = $request;
		$context->headers[$entry->header] = true;
		return $entry;
	}

	public static function address(cpp_runtime_operand $operand, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($operand->type, $context);
		CPP_Types::record_dependencies($mapping, $context);
		return $mapping->opaque ? '(' . $operand->expression . ').address()' : '&(' . $operand->expression . ')';
	}

	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = self::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		if ($request->kind === cpp_runtime_operation_kind::initialize_empty) {
			return CPP_Initializer_Buffer::empty_entry($request, $engine);
		}
		if ($request->kind === cpp_runtime_operation_kind::initialize_buffer) {
			return CPP_Initializer_Buffer::entry($request, $engine);
		}
		// Native outer implementations retain concrete stored arguments. Key checking
		// alone consumes its original scalar representation.
		$implementation_engine /** nullable<CPP_Specializations> */ = null;
		if (($request->kind === cpp_runtime_operation_kind::binary)
			|| ($request->kind === cpp_runtime_operation_kind::self_acquire)
			|| ($request->kind === cpp_runtime_operation_kind::conversion)
			|| ($request->kind === cpp_runtime_operation_kind::observation)
			|| ($request->kind === cpp_runtime_operation_kind::object_type)
			|| ($request->kind === cpp_runtime_operation_kind::ownership_observation)
			|| ($request->kind === cpp_runtime_operation_kind::output)
			|| ($request->kind === cpp_runtime_operation_kind::runtime_call)
			|| ($request->kind === cpp_runtime_operation_kind::take)
			|| ($request->kind === cpp_runtime_operation_kind::error_condition)
			|| ($request->kind === cpp_runtime_operation_kind::wrapper_presence)
			|| ($request->kind === cpp_runtime_operation_kind::wrapper_isset)
			|| ($request->kind === cpp_runtime_operation_kind::property_read)
			|| ($request->kind === cpp_runtime_operation_kind::property_write)
			|| ($request->kind === cpp_runtime_operation_kind::property_target)
			|| ($request->kind === cpp_runtime_operation_kind::construct_default)
			|| CPP_Source_Calls::construction_transport($request)) { $implementation_engine = $engine; }
		$native_context = new cpp_generation_context();
		$native_context->implementation_engine = $implementation_engine;
		$signature = '';
		$unpack = '';
		$arguments /** vector<string> */ = [];
		$pointers /** vector<string> */ = [];
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($inputs as $position => $input) {
			CPP_Opaque_Representation::require_native_type($input);
			$input_context = $native_context;
			if (CPP_Source_Calls::construction_transport($request)
				|| (self::take_application_output($request) && ($position === 0))) {
				$input_context = new cpp_generation_context($engine);
			}
			$native /** string */ = '';
			// Self acquisition borrows the source payload, not a stored handle facade.
			if ($request->kind === cpp_runtime_operation_kind::self_acquire) {
				$payload = CPP_Types::payload($input, $input_context);
				CPP_Types::record_dependencies($payload, $input_context);
				$native = $payload->spelling;
			} else { $native = CPP_Declarations::type($input, $input_context); }
			foreach ($input_context->headers as $header => $used) { $native_context->headers[$header] = true; }
			$mutable_input = self::mutable_input($request, $position);
			$qualifier = $mutable_input ? '' : 'const ';
			if ($position !== 0) { $signature .= ', '; }
			$signature .= $qualifier . 'void* p' . $position;
			$unpack .= 'auto& a' . $position . ' = *std::launder(static_cast<' . $qualifier . $native . '*>(p' . $position . ')); ';
			$arguments[] = 'a' . $position;
			$pointers[] = 'p' . $position;
		}
		$key_type = CPP_Source_Calls::construction_key_type($request);
		if ($key_type !== '') {
			if ($signature !== '') { $signature .= ', '; }
			$signature .= 'const void* construction_key';
			$unpack .= 'const auto& construction_key_value = *static_cast<const ' . $key_type . '*>(construction_key); ';
			$pointers[] = 'construction_key';
		}
		$expression = self::native_operation($request, $arguments, $native_context);
		$output = 'void';
		$declaration_headers /** hash<bool> */ = [];
		$body = $unpack . $expression . ';';
		if ($request->result !== null) {
			CPP_Opaque_Representation::require_native_type($request->result);
			$native_result = CPP_Declarations::type($request->result, $native_context);
			$mapping = CPP_Types::representation($request->result, new cpp_generation_context($engine));
			$output = $mapping->spelling;
			$declaration_headers = $mapping->headers;
			$body = $unpack . 'return ' . $expression . ';';
			if ((($request->kind === cpp_runtime_operation_kind::observation)
				|| ($request->kind === cpp_runtime_operation_kind::object_type)
				|| ($request->kind === cpp_runtime_operation_kind::ownership_observation)
				|| ($request->kind === cpp_runtime_operation_kind::runtime_call)
				|| ($request->kind === cpp_runtime_operation_kind::property_read)
				|| ($request->kind === cpp_runtime_operation_kind::conversion)) && $mapping->opaque) {
				$body = self::concrete_call_result($output, $native_result, $unpack, $expression);
				$native_context->headers['type_traits'] = true;
			}
			elseif ($mapping->opaque) {
				// The callback initializes the final result storage; neither side materializes a second value.
				$reload = '';
				foreach ($inputs as $position => $input) {
					$reload .= self::mutable_input($request, $position)
						? 'void* p' . $position . ' = const_cast<void*>(addresses[' . $position . ']); '
						: 'const void* p' . $position . ' = addresses[' . $position . ']; ';
				}
				if ($key_type !== '') { $reload .= 'const void* construction_key = addresses[' . q_count($inputs) . ']; '; }
				$body = 'const void* addresses[] = {' . (q_count($pointers) === 0 ? 'nullptr' : self::join($pointers)) . '}; '
					. 'return ' . $output . '(' . $output . '::operation_result{}, +[](void* destination, const void* input) { '
					. 'auto addresses = static_cast<const void* const*>(input); ' . $reload . $unpack
					. '::new(destination) ' . $native_result . '(' . $expression . '); }, addresses);';
			}
		}
		$never_returns = ($request->kind === cpp_runtime_operation_kind::exception_throw)
			|| ($request->kind === cpp_runtime_operation_kind::match_failure);
		$prototype = ($never_returns ? '[[noreturn]] ' : '')
			. $output . ' ' . $name . '(' . $signature . ')';
		$header = 'specializations/' . $name . '.hpp';
		$declaration = "#pragma once\n" . CPP_Artifacts::includes($declaration_headers, '')
			. 'namespace scpp::internal { ' . $prototype . "; }\n";
		$implementation = CPP_Artifacts::includes($native_context->headers, '') . "#include <memory>\n#include <new>\n"
			. 'namespace scpp::internal { ' . $prototype . ' { ' . $body . " } }\n";
		$layout_dependencies /** hash<bool> */ = [];
		return new cpp_specialization_entry($name, $header, $declaration, $implementation, $layout_dependencies);
	}

	/** Providers can return native outer objects or already-substituted physical payloads. */
	public static function concrete_call_result(string $output, string $native, string $unpack, string $expression): string
	{
		return $unpack . 'auto operation = [&]() -> decltype(auto) { return ' . $expression . '; }; '
			. 'return []<class Operation>(const Operation& operation) -> ' . $output . ' { '
			. 'using Result = std::remove_cvref_t<decltype(operation())>; '
			. 'if constexpr (std::is_same_v<Result, ' . $output . '>) { return operation(); } '
			. 'else { static_assert(std::is_same_v<Result, ' . $native . '>); return ' . $output . '(' . $output
			. '::operation_result{}, +[](void* destination, const void* input) { '
			. 'const auto& invoke = *static_cast<const Operation*>(input); ::new(destination) ' . $native
			. '(invoke()); }, &operation); } }(operation);';
	}

	/** Physical wrapper payloads use concrete outputs; erased optional handles use native storage. */
	private static function take_application_output(cpp_runtime_operation $request): bool
	{
		if ($request->kind !== cpp_runtime_operation_kind::take) { return false; }
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		return CPP_Wrapper_Transport::payload($inputs[$inputs->count() - 1]) !== null;
	}

	private static function mutable_input(cpp_runtime_operation $request, int $position): bool
	{
		if ($request->kind === cpp_runtime_operation_kind::self_acquire) { return true; }
		if ($request->record_construction !== null) { return true; }
		if ($request->kind === cpp_runtime_operation_kind::take) {
			$inputs /** Storage<canonical_type_use> */ = $request->inputs;
			return $position < $inputs->count() - 1;
		}
		if ($request->conversion !== null) {
			if ($request->conversion->operation === conversion_operation::unique_upcast) {
				return true;
			}
		}
		if ($request->call !== null) { return CPP_Source_Calls::mutable_input($request->call, $position, false); }
		return false;
	}

	private static function native_operation(cpp_runtime_operation $request, array $arguments /** vector<string> */,
		cpp_generation_context $context): string
	{
		if ($request->kind === cpp_runtime_operation_kind::self_acquire) {
			$context->headers['scpp/source_self.hpp'] = true;
			return '::scpp::retain_source_self(&' . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::match_failure) {
			$context->headers['stdexcept'] = true;
			return 'throw std::runtime_error("Unhandled match expression")';
		}
		if ($request->kind === cpp_runtime_operation_kind::exception_throw) {
			$context->headers['lang/php/php_exceptions.hpp'] = true;
			return 'throw ::scpp::php::make_thrown(' . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::exception_catch) {
			$context->headers['lang/php/php_exceptions.hpp'] = true;
			$type /** canonical_type_use */ = $request->result;
			$payload = CPP_Types::payload($type);
			CPP_Types::record_dependencies($payload, $context);
			// Called only inside an active catch. Foreign failures resume untouched.
			return '([]() { try { throw; } catch (const ::scpp::php::thrown_object& caught) { '
				. 'auto value = ::scpp::php::catch_as<' . $payload->spelling . '>(caught); '
				. 'if (!value) { throw; } return value; } }())';
		}
		if (($request->kind === cpp_runtime_operation_kind::property_read)
			|| ($request->kind === cpp_runtime_operation_kind::property_write)
			|| ($request->kind === cpp_runtime_operation_kind::property_target)) {
			$property /** registered_property */ = $request->property;
			$member = CPP_Runtime_Catalog::property($property, $context);
			$access = '(' . $arguments[0] . ')->' . $member;
			if ($request->kind === cpp_runtime_operation_kind::property_target) {
				return 'static_cast<void>(&(' . $access . '))';
			}
			$engine /** CPP_Specializations */ = $context->implementation_engine;
			return $request->kind === cpp_runtime_operation_kind::property_read
				? CPP_Wrapper_Transport::provider_vector($property->type, $access, true, $engine, $context)
				: $access . ' = ' . CPP_Wrapper_Transport::provider_vector($property->type, $arguments[1], false, $engine, $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::object_type) {
			$facts /** prepared_object_type */ = $request->object_type;
			$target = CPP_Types::payload($facts->target_payload, $context);
			CPP_Types::record_dependencies($target, $context);
			$context->headers['scpp/object_cast.hpp'] = true;
			$helper = $facts->operation === object_type_operation::checked_cast ? 'checked_object_cast' : 'object_is';
			return '::scpp::' . $helper . '<' . $target->spelling . '>(' . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::ownership_observation) {
			$context->headers['scpp/memory.hpp'] = true;
			$context->headers['scpp/weak_p.hpp'] = true;
			$observation /** ownership_observation_kind */ = $request->ownership_observation;
			return $observation === ownership_observation_kind::derive_weak
				? '::scpp::weak(' . $arguments[0] . ')' : '(' . $arguments[0] . ').lock()';
		}
		if ($request->kind === cpp_runtime_operation_kind::enum_helper) {
			$facts /** prepared_enum_call */ = $request->enum_helper;
			return CPP_Compiler_Functions::native_expression($facts, $arguments[0], $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::error_condition) {
			$context->headers['lang/php/operators/conditional/condition_truthiness.hpp'] = true;
			return 'scpp::php::condition_truthy(' . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::wrapper_isset) {
			$context->headers['lang/php/operators/isset/isset.hpp'] = true;
			return 'scpp::php::isset(' . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::wrapper_presence) {
			return '(' . $arguments[0] . ').has_value()';
		}
		if ($request->kind === cpp_runtime_operation_kind::take) {
			$context->headers['lang/php/support/php_take.hpp'] = true;
			return 'scpp::php::take(' . self::join($arguments) . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::output) {
			$inputs /** Storage<canonical_type_use> */ = $request->inputs;
			return CPP_Declarations::output($arguments[0], $inputs[0], $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::construct_record) { return CPP_Data_Construction::native_record($request, $arguments, $context); }
		if ($request->kind === cpp_runtime_operation_kind::construct_call) { return CPP_Source_Calls::native_construction($request, $arguments, $context); }
		if ($request->kind === cpp_runtime_operation_kind::construct_default) {
			$facts /** prepared_object_construction */ = $request->construction;
			return CPP_Record_Construction::object($facts, new Storage /** Storage<expression_node> */(), $context);
		}
		if (($request->kind === cpp_runtime_operation_kind::observation) || ($request->kind === cpp_runtime_operation_kind::runtime_call)) {
			$binding /** cpp_function_binding */ = $request->binding;
			if ($binding->header() !== '') { $context->headers[$binding->header()] = true; }
			$engine /** CPP_Specializations */ = $context->implementation_engine;
			$inputs /** Storage<canonical_type_use> */ = $request->inputs;
			foreach ($inputs as $position => $input) {
				if ($binding->native_payloads) {
					$arguments[$position] = Type_Queries::canonical($input)->definition() instanceof callable_type_definition
						? CPP_Callback_Transport::provider($input, $arguments[$position], true, $context, true)
						: CPP_Wrapper_Transport::provider_value($input, $arguments[$position], false, $engine, $context);
					continue;
				}
				if (Type_Queries::canonical($input)->definition() instanceof callable_type_definition) {
					$arguments[$position] = CPP_Callback_Transport::provider($input, $arguments[$position],
						$binding->target() instanceof registered_function, $context);
				}
				// Concrete provider contracts consume native vectors; inferred generic
				// contracts continue to use their substituted physical element types.
				if (($binding->target() instanceof registered_function) && CPP_Wrapper_Transport::is_string_vector($input)) {
					if ($request->call !== null) {
						$vector_parameters /** Storage<callable_parameter> */ = $request->call->signature->parameters;
						$vector_parameter = $vector_parameters[$position];
						if (($vector_parameter->mode === passing_mode::reference)
							&& ($vector_parameter->access !== storage_access::read_only)) {
							throw new \RuntimeException('prelower_templates: mutable provider vectors require separate write-back transport');
						}
					}
					$arguments[$position] = CPP_Wrapper_Transport::provider_vector($input, $arguments[$position], false, $engine, $context);
				}
				if (CPP_Wrapper_Transport::payload($input) !== null) {
					if ($request->call !== null) {
						$parameters /** Storage<callable_parameter> */ = $request->call->signature->parameters;
						$parameter = $parameters[$position];
						if (($parameter->mode === passing_mode::reference) && ($parameter->access !== storage_access::read_only)) {
							throw new \RuntimeException('prelower_templates: mutable provider wrapper arguments require write-back transport');
						}
					}
					$arguments[$position] = CPP_Wrapper_Transport::adapt($input, $arguments[$position], false, $engine, $context);
				}
			}
			$values = self::join($arguments);
			if ($request->call !== null) { $values = CPP_Source_Calls::native_arguments($request->call, $arguments, 0, $context); }
			$values = CPP_Callsites::arguments($request->callsite, $values);
			$callee = $binding->symbol();
			if ($request->call !== null) { $callee = CPP_Callables::call_reference($request->call, $context); }
			$expression = $callee . '(' . $values . ')';
			if ($binding->native_payloads && ($request->result !== null)) {
				return CPP_Wrapper_Transport::provider_value($request->result, $expression, true, $engine, $context);
			}
			if (($request->result !== null) && (CPP_Wrapper_Transport::payload($request->result) !== null)) {
				return CPP_Wrapper_Transport::adapt($request->result, $expression, true, $engine, $context);
			}
			return $expression;
		}
		if ($request->kind === cpp_runtime_operation_kind::binary) {
			$decision /** operator_decision */ = $request->decision;
			CPP_Generator::record_operator_headers($decision, $context);
			return CPP_Generator::render_eager_binary($decision, $request->spelling, $arguments[0], $arguments[1], $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::conversion) {
			$conversion /** conversion_decision */ = $request->conversion;
			$engine /** CPP_Specializations */ = $context->implementation_engine;
			if ((($conversion->operation === conversion_operation::nullable_wrap)
				|| ($conversion->operation === conversion_operation::nullable_extract)
				|| ($conversion->operation === conversion_operation::nullable_map))
				&& ((Type_Queries::nullable_handle_payload($conversion->source_type) !== null)
					|| (Type_Queries::nullable_handle_payload($conversion->target_type) !== null))) {
				return CPP_Declarations::conversion($arguments[0], $conversion, $context);
			}
			if (($conversion->operation === conversion_operation::nullable_wrap)
				|| ($conversion->operation === conversion_operation::result_wrap)) {
				$payload = CPP_Wrapper_Transport::payload_value($conversion->source_type, $arguments[0], true, $engine, $context);
				return CPP_Declarations::type($conversion->target_type, $context) . '(' . $payload . ')';
			}
			if (($conversion->operation === conversion_operation::nullable_extract)
				|| ($conversion->operation === conversion_operation::result_extract)) {
				$message = $conversion->operation === conversion_operation::nullable_extract
					? 'nullable required-value extraction' : 'Result required-value extraction';
				return '(' . $arguments[0] . ').require_value("' . $message . '")';
			}
			$operand = $arguments[0];
			if ($conversion->operation === conversion_operation::explicit_runtime_cast) {
				// Runtime casts consume native payloads, including lifted wrapper casts.
				$operand = CPP_Wrapper_Transport::provider_value($conversion->source_type, $operand, false, $engine, $context);
			}
			if ($conversion->operation === conversion_operation::unique_upcast) {
				$context->headers['utility'] = true;
				$operand = 'std::move(' . $operand . ')';
			}
			return CPP_Declarations::conversion($operand, $conversion, $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::write_key) {
			$context->headers['scpp/index_write.hpp'] = true;
			return 'scpp::require_write_key(' . $arguments[0] . ')';
		}
		throw new \LogicException('Unimplemented concrete runtime operation');
	}

	public static function construct_default(prepared_object_construction $facts, cpp_generation_context $context): string
	{
		if ($facts->constructor_call !== null) {
			throw new \LogicException('Selected constructor requires source-call transport');
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::construct_default);
		$request->construction = $facts;
		$request->result = $facts->type;
		return self::invoke($request, /** vector<string> */ [], $context, '');
	}

	public static function initialize(data_construction_node $node, initialization_binding $binding,
		cpp_generation_context $context): string
	{
		$entries /** Storage<data_entry_node> */ = $node->entries;
		if (!$entries->is_empty()) {
			return CPP_Data_Construction::sequence($node, $binding, $context);
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::initialize_empty);
		$request->initialization = $binding;
		$request->result = $binding->target();
		return self::invoke($request, /** vector<string> */ [], $context, '');
	}

	public static function write_key(canonical_type_use $type, string $key, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::write_key);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($type);
		return self::invoke($request, [$key], $context, '');
	}

	public static function binary(operator_decision $decision, string $spelling, string $left, string $right,
		cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::binary);
		$request->result = $decision->result_type;
		$request->decision = $decision;
		$request->spelling = $spelling;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($decision->operands as $conversion) { $inputs->append($conversion->target_type); }
		return self::invoke($request, [$left, $right], $context, '');
	}

	/** Selected concrete conversions reuse the prepared decision and existing native renderer. */
	public static function concrete_conversion(conversion_decision $decision, cpp_generation_context $context): bool
	{
		$engine = $context->specialization_engine;
		if ($engine === null) { return false; }
		if ($decision->operation === conversion_operation::boxed_wrap) {
			return true;
		}
		if ($decision->operation === conversion_operation::boxed_extract) {
			$source = Type_Queries::canonical($decision->source_type)->definition();
			return (($source instanceof mixed_semantics) || ($source instanceof dynamic_semantics))
				&& ($decision->target_type->matches(Language_Types::integer(Model::$language_scope))
					|| $decision->target_type->matches(Language_Types::string_type(Model::$language_scope)));
		}
		return ($decision->operation === conversion_operation::explicit_runtime_cast)
			|| ($decision->operation === conversion_operation::nullable_empty)
			|| ($decision->operation === conversion_operation::nullable_wrap)
			|| ($decision->operation === conversion_operation::nullable_extract)
			|| ($decision->operation === conversion_operation::nullable_map)
			|| ($decision->operation === conversion_operation::result_wrap)
			|| ($decision->operation === conversion_operation::result_extract)
			|| ($decision->operation === conversion_operation::result_error)
			|| ($decision->operation === conversion_operation::result_false)
			|| ($decision->operation === conversion_operation::result_true)
			|| ($decision->operation === conversion_operation::text_conversion)
			|| ($decision->operation === conversion_operation::integer_value_cast)
			|| ($decision->operation === conversion_operation::ownership_empty)
			|| ($decision->operation === conversion_operation::ownership_upcast)
			|| ($decision->operation === conversion_operation::unique_upcast);
	}

	public static function convert(string $expression, conversion_decision $decision, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::conversion);
		$request->result = $decision->target_type;
		$request->conversion = $decision;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($decision->source_type);
		return self::single_value($request, $expression, $context);
	}

	/** Borrow the operand once; the existing runtime preserves identity and checks presence/type. */
	public static function object_type(prepared_object_type $facts, canonical_type_use $input,
		string $expression, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::object_type);
		$request->object_type = $facts;
		$request->result = $facts->type;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($input);
		$operand = 'static_cast<const ' . CPP_Declarations::type($input, $context) . '&>(' . $expression . ')';
		return self::invoke($request, [$operand], $context, '');
	}

	public static function ownership_observation(prepared_ownership_observation $facts,
		canonical_type_use $input, string $expression, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::ownership_observation);
		$request->ownership_observation = $facts->operation;
		$request->result = $facts->type;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($input);
		// Borrow in the caller's full expression so lock(weak(make())) keeps its owner alive.
		$operand = 'static_cast<const ' . CPP_Declarations::type($input, $context) . '&>(' . $expression . ')';
		return self::invoke($request, [$operand], $context, '');
	}

	/** Observe the prepared error state; transport does not grant other truthiness contracts. */
	public static function error_condition(canonical_type_use $type, string $expression, cpp_generation_context $context): string
	{
		return self::boolean_observation($type, $expression, cpp_runtime_operation_kind::error_condition, $context);
	}

	public static function wrapper_presence(canonical_type_use $type, string $expression, cpp_generation_context $context): string
	{
		return self::boolean_observation($type, $expression, cpp_runtime_operation_kind::wrapper_presence, $context);
	}

	/** Sentinel-aware probe presence is separate from checked payload availability. */
	public static function wrapper_isset(canonical_type_use $type, string $expression, cpp_generation_context $context): string
	{
		return self::boolean_observation($type, $expression, cpp_runtime_operation_kind::wrapper_isset, $context);
	}

	private static function boolean_observation(canonical_type_use $type, string $expression,
		cpp_runtime_operation_kind $operation, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation($operation);
		$request->result = Model::$type_catalog->registry()->use(Model::$type_catalog->canonical('bool')->type_id());
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($type);
		$operand = 'static_cast<const ' . CPP_Declarations::type($type, $context) . '&>(' . $expression . ')';
		return '(' . self::invoke($request, [$operand], $context, '') . ').native_value()';
	}

	/** Borrow within the full expression, including any temporary that owns a nested reference. */
	public static function output(string $expression, canonical_type_use $type, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::output);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($type);
		$operand = 'static_cast<const ' . CPP_Declarations::type($type, $context) . '&>(' . $expression . ')';
		return self::invoke($request, [$operand], $context, '');
	}

	private static function single_value(cpp_runtime_operation $request, string $expression, cpp_generation_context $context): string
	{
		$name = 'runtime_input_' . $context->next_temporary++;
		$result /** canonical_type_use */ = $request->result;
		return CPP_Sequence::open(CPP_Declarations::type($result, $context), $context) . (self::mutable_input($request, 0) ? 'auto ' : 'const auto& ') . $name . ' = '
			. $expression . '; ' . CPP_Sequence::result(self::invoke($request, [$name], $context, ''), $context) . CPP_Sequence::close($context);
	}

	/** Dispatch settled observation versus value/reference contracts without naming runtime functions. */
	public static function call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context, ?cpp_callsite $callsite = null): string
	{
		if (($facts->signature->reference_return !== null) || ($facts->variadic_parameter !== null)) {
			throw new \RuntimeException('prelower_templates: reference-result/variadic call transport is not yet implemented');
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		if ($parameters->is_empty()) {
			return self::value_call($facts, $arguments, $context, $callsite);
		}
		foreach ($parameters as $parameter) {
			if ($parameter->mode !== passing_mode::observation) {
				return self::value_call($facts, $arguments, $context, $callsite);
			}
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::observation);
		$target = object_cast(weakref_get($facts->target), runtime_function_definition::class);
		$request->binding = Model::$cpp_callable_bindings->function_binding($target);
		if (Type_Queries::canonical($facts->type)->family() !== type_family::no_value) { $request->result = $facts->type; }
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		if (q_count($prepared_arguments) > 1) { throw new \RuntimeException('prelower_templates: multiple observation inputs need a sequencing contract'); }
		if (q_count($parameters) !== q_count($prepared_arguments)) { throw new \RuntimeException('prelower_templates: default call inputs are not yet implemented'); }
		$names /** vector<string> */ = [];
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$emitter = new CPP_Syntax($context);
		foreach ($prepared_arguments as $position => $argument) {
			if (($argument->boundary !== value_boundary_operation::observe_value) || ($argument->conversion !== null)) { throw new \RuntimeException('prelower_templates: call requires prepared observation inputs'); }
			$type = $parameters[$position]->type;
			$inputs->append($type);
			// Preserve the full-expression lifetime of both the observed value and any
			// temporary owner. A local reference would not extend an indirect owner's life.
			$names[] = 'static_cast<const ' . CPP_Declarations::type($type, $context) . '&>('
				. $emitter->expression($arguments[$argument->source_index]) . ')';
		}
		return self::invoke($request, $names, $context, '');
	}

	/** Reuse ordinary argument/default sequencing and prepared copy/consume/reference boundaries. */
	private static function value_call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context, ?cpp_callsite $callsite): string
	{
		CPP_Source_Calls::require_signature($facts);
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::runtime_call);
		$request->callsite = $callsite;
		$request->call = $facts;
		$target = object_cast(weakref_get($facts->target), runtime_function_definition::class);
		$request->binding = Model::$cpp_callable_bindings->function_binding($target);
		if (Type_Queries::canonical($facts->type)->family() !== type_family::no_value) { $request->result = $facts->type; }
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($facts->signature->parameters as $parameter) { $inputs->append($parameter->type); }
		return CPP_Declarations::selected_call($facts, $arguments, $context, '', null, '', $request);
	}

}
