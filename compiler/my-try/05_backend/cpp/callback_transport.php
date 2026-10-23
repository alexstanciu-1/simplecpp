<?php
namespace scpp\compiler;

/** Owned value callbacks; execution and capture permission remain in preparation. */
final class CPP_Callback_Transport
{
	public static function signature(canonical_type_use $type): callable_signature
	{
		$definition = object_cast(Type_Queries::canonical($type)->definition(), callable_type_definition::class);
		$signature = $definition->signature();
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		if ($signature->reference_return !== null) {
			throw new \RuntimeException('prelower_templates: callback reference results require lifetime transport');
		}
		foreach ($parameters as $parameter) {
			if (($parameter->mode !== passing_mode::value) || ($parameter->element_type !== null)) {
				throw new \RuntimeException('prelower_templates: callback transport requires fixed value inputs');
			}
		}
		return $signature;
	}

	public static function wrap(string $expression, canonical_type_use $type, cpp_generation_context $context): string
	{
		$signature = self::signature($type);
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$declarations = '';
		$arguments = '';
		foreach ($parameters as $index => $parameter) {
			$argument_type = CPP_Declarations::type($parameter->type, $context);
			$name = 'value_' . $index;
			$declarations .= ', ' . $argument_type . ' ' . $name;
			$arguments .= ($index === 0 ? '' : ', ') . 'static_cast<' . $argument_type . '&&>(' . $name . ')';
		}
		$result = CPP_Declarations::type($signature->return_type, $context);
		$storage = CPP_Declarations::type($type, $context);
		return '([&]() -> ' . $storage . ' { auto callback = ' . $expression . '; auto permissions = callback.transfer_permissions(); using Captures = decltype(callback); '
			. 'return ' . $storage . '(new Captures(static_cast<Captures&&>(callback)), '
			. '+[](void* state' . $declarations . ') -> ' . $result . ' { return (*static_cast<Captures*>(state))(' . $arguments . '); }, '
			. '+[](const void* state) -> void* { return new Captures(*static_cast<const Captures*>(state)); }, '
			. '+[](void* state) { delete static_cast<Captures*>(state); }, permissions); }())';
	}

	/** Provider-native payloads are adapted at the existing runtime call boundary. */
	public static function provider(canonical_type_use $type, string $expression, bool $native_payloads,
		cpp_generation_context $context, bool $fully_native = false): string
	{
		$signature = self::signature($type);
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$engine /** CPP_Specializations */ = $context->implementation_engine;
		$physical_context = new cpp_generation_context($engine);
		$input_types /** vector<string> */ = [];
		$declarations = '';
		$arguments = '';
		$body = '';
		foreach ($parameters as $index => $parameter) {
			$input = $parameter->type;
			$physical_input = CPP_Types::representation($input, $physical_context);
			$native_input = $fully_native ? CPP_Types::representation($input) : CPP_Types::implementation_representation($input, $engine);
			CPP_Types::record_dependencies($physical_input, $context);
			CPP_Types::record_dependencies($native_input, $context);
			$name = 'value_' . $index;
			$argument_type = $native_payloads ? $native_input->spelling : $physical_input->spelling;
			$input_types[] = $argument_type;
			$declarations .= ($index === 0 ? '' : ', ') . $argument_type . ' ' . $name;
			$argument = $name;
			if ($fully_native) {
				$argument = CPP_Wrapper_Transport::provider_payload($input, $name, true, $engine, $context);
			}
			elseif ($native_payloads && $physical_input->opaque) {
				$context->headers['new'] = true;
				$argument = 'argument_' . $index;
				$body .= $physical_input->spelling . ' ' . $argument . '(' . $physical_input->spelling . '::operation_result{}, '
					. '+[](void* out, const void* in) { ::new(out) ' . $native_input->spelling
					. '(*static_cast<const ' . $native_input->spelling . '*>(in)); }, &' . $name . '); ';
			}
			$arguments .= ($index === 0 ? '' : ', ') . $argument;
		}
		$output = $signature->return_type;
		$physical_output = CPP_Types::representation($output, $physical_context);
		$native_output = $fully_native ? CPP_Types::representation($output) : CPP_Types::implementation_representation($output, $engine);
		$convert_output = $native_payloads || $output->matches(Language_Types::boolean(Model::$language_scope));
		CPP_Types::record_dependencies($physical_output, $context);
		CPP_Types::record_dependencies($native_output, $context);
		$result_type = $convert_output ? $native_output->spelling : $physical_output->spelling;
		if (Type_Queries::canonical($output)->family() === type_family::no_value) {
			$body .= 'callback(' . $arguments . '); ';
		}
		else {
			$body .= 'auto result = callback(' . $arguments . '); ';
			$body .= $fully_native ? 'return ' . CPP_Wrapper_Transport::provider_payload($output, 'result', false, $engine, $context) . '; '
				: (($convert_output && $physical_output->opaque)
				? 'return *static_cast<const ' . $native_output->spelling . '*>(result.address()); '
				: 'return result; ');
		}
		// The adapter may outlive the operation helper: never retain its stack inputs.
		return '([&]() { auto provider_callback = (' . $expression . '); auto permissions = provider_callback.transfer_permissions(); '
			. 'return scpp::internal::carry_callback_transfer<' . $result_type . '(' . q_implode(', ', $input_types) . ')>([callback = static_cast<decltype(provider_callback)&&>(provider_callback)]('
			. $declarations . ') mutable -> ' . $result_type . ' { ' . $body . '}, permissions); }())';
	}

	public static function interface(canonical_type_use $type, string $name, string $native,
		CPP_Specializations $engine): cpp_type_interface
	{
		$signature = self::signature($type);
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$context = new cpp_generation_context($engine);
		$declarations = '';
		$types = '';
		$arguments = '';
		foreach ($parameters as $index => $parameter) {
			$argument_type = CPP_Declarations::type($parameter->type, $context);
			$name_part = 'value_' . $index;
			$declarations .= ($index === 0 ? '' : ', ') . $argument_type . ' ' . $name_part;
			$types .= ', ' . $argument_type;
			$arguments .= ($index === 0 ? '' : ', ') . 'std::move(' . $name_part . ')';
		}
		$result = CPP_Declarations::type($signature->return_type, $context);
		$interface = new cpp_type_interface();
		$context->headers['scpp/task_permissions.hpp'] = true;
		$interface->declaration_prefix = CPP_Artifacts::includes($context->headers, '');
		$inputs = 'void* state, ' . $result . ' (*call)(void*' . $types . '), void* (*copy)(const void*), void (*destroy)(void*)';
		$interface->declaration = $name . '(' . $inputs . " , scpp::task_permissions permissions);\n"
			. $result . ' operator()(' . $declarations . ") const;\nexplicit operator bool() const;\nscpp::task_permissions transfer_permissions() const;\nbool transfer_allowed(scpp::task_policy policy = scpp::task_policy::isolated_transfer) const;\nvoid require_transfer(scpp::task_policy policy = scpp::task_policy::isolated_transfer) const;\n";
		$owner = 'callback_owner_' . $name;
		$interface->implementation = 'struct ' . $owner . ' { void* state; '
			. $result . ' (*call)(void*' . $types . '); void* (*copy)(const void*); void (*destroy)(void*); '
			. $owner . '(' . $inputs . ') : state(state), call(call), copy(copy), destroy(destroy) {} '
			. $owner . '(const ' . $owner . '& other) : state(other.copy(other.state)), call(other.call), copy(other.copy), destroy(other.destroy) {} '
			. $owner . '(' . $owner . '&& other) noexcept : state(other.state), call(other.call), copy(other.copy), destroy(other.destroy) { other.state = nullptr; } '
			. '~' . $owner . '() { if (state) destroy(state); } '
			. $result . ' operator()(' . $declarations . ') const { return call(state' . ($arguments === '' ? '' : ', ' . $arguments) . '); } }; '
			. $name . '::' . $name . '(' . $inputs . ', scpp::task_permissions permissions) { ' . $owner . ' owner(state, call, copy, destroy); '
			. '::new(address()) ' . $native . '(std::move(owner), permissions); }' . "\n"
			. $result . ' ' . $name . '::operator()(' . $declarations . ') const { return unwrap(*this)(' . $arguments . '); }' . "\n"
			. $name . '::operator bool() const { return static_cast<bool>(unwrap(*this)); }' . "\n"
			. 'scpp::task_permissions ' . $name . '::transfer_permissions() const { return unwrap(*this).transfer_permissions(); }' . "\n"
			. 'bool ' . $name . '::transfer_allowed(scpp::task_policy policy) const { return unwrap(*this).transfer_allowed(policy); }' . "\n"
			. 'void ' . $name . '::require_transfer(scpp::task_policy policy) const { unwrap(*this).require_transfer(policy); }' . "\n";
		return $interface;
	}
}
