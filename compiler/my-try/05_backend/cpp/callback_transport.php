<?php
namespace scpp\compiler;

/** Owned synchronous value callbacks; semantic capture permission remains in preparation. */
final class CPP_Callback_Transport
{
	public static function signature(canonical_type_use $type): callable_signature
	{
		$definition = object_cast(Type_Queries::canonical($type)->definition(), callable_type_definition::class);
		$signature = $definition->signature();
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		if ($parameters->count() !== 1) {
			throw new \RuntimeException('prelower_templates: callback transport requires one value input and a value result');
		}
		if (($signature->reference_return !== null) || ($parameters[0]->mode !== passing_mode::value) || ($parameters[0]->element_type !== null)
			|| (Type_Queries::canonical($signature->return_type)->family() === type_family::no_value)) {
			throw new \RuntimeException('prelower_templates: callback transport requires one value input and a value result');
		}
		return $signature;
	}

	public static function wrap(string $expression, canonical_type_use $type, cpp_generation_context $context): string
	{
		$signature = self::signature($type);
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$argument = CPP_Declarations::type($parameters[0]->type, $context);
		$result = CPP_Declarations::type($signature->return_type, $context);
		$storage = CPP_Declarations::type($type, $context);
		return '([&]() -> ' . $storage . ' { auto callback = ' . $expression . '; using Captures = decltype(callback); '
			. 'return ' . $storage . '(new Captures(static_cast<Captures&&>(callback)), '
			. '+[](void* state, ' . $argument . ' value) -> ' . $result . ' { return (*static_cast<Captures*>(state))(static_cast<' . $argument . '&&>(value)); }, '
			. '+[](const void* state) -> void* { return new Captures(*static_cast<const Captures*>(state)); }, '
			. '+[](void* state) { delete static_cast<Captures*>(state); }); }())';
	}

	/** Provider-native payloads are adapted at the existing runtime call boundary. */
	public static function provider(canonical_type_use $type, string $expression, bool $native_payloads,
		cpp_generation_context $context): string
	{
		$signature = self::signature($type);
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$engine /** CPP_Specializations */ = $context->implementation_engine;
		$physical_context = new cpp_generation_context($engine);
		$input = $parameters[0]->type;
		$output = $signature->return_type;
		$physical_input = CPP_Types::representation($input, $physical_context);
		$physical_output = CPP_Types::representation($output, $physical_context);
		$native_input = CPP_Types::implementation_representation($input, $engine);
		$native_output = CPP_Types::implementation_representation($output, $engine);
		$convert_output = $native_payloads || $output->matches(Language_Types::boolean(Model::$language_scope));
		$mappings /** vector<cpp_type> */ = [$physical_input, $physical_output, $native_input, $native_output];
		foreach ($mappings as $mapping) {
			CPP_Types::record_dependencies($mapping, $context);
		}
		$argument_type = $native_payloads ? $native_input->spelling : $physical_input->spelling;
		$result_type = $convert_output ? $native_output->spelling : $physical_output->spelling;
		$body = '';
		$argument = 'value';
		if ($native_payloads && $physical_input->opaque) {
			$context->headers['new'] = true;
			$body = $physical_input->spelling . ' argument(' . $physical_input->spelling . '::operation_result{}, '
				. '+[](void* out, const void* in) { ::new(out) ' . $native_input->spelling
				. '(*static_cast<const ' . $native_input->spelling . '*>(in)); }, &value); ';
			$argument = 'argument';
		}
		$body .= 'auto result = (' . $expression . ')(' . $argument . '); ';
		$body .= ($convert_output && $physical_output->opaque)
			? 'return *static_cast<const ' . $native_output->spelling . '*>(result.address()); '
			: 'return result; ';
		return '[&](' . $argument_type . ' value) -> ' . $result_type . ' { ' . $body . '}';
	}

	public static function interface(canonical_type_use $type, string $name, string $native,
		CPP_Specializations $engine): cpp_type_interface
	{
		$signature = self::signature($type);
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$context = new cpp_generation_context($engine);
		$argument = CPP_Declarations::type($parameters[0]->type, $context);
		$result = CPP_Declarations::type($signature->return_type, $context);
		$interface = new cpp_type_interface();
		$interface->declaration_prefix = CPP_Artifacts::includes($context->headers, '');
		$inputs = 'void* state, ' . $result . ' (*call)(void*, ' . $argument . '), void* (*copy)(const void*), void (*destroy)(void*)';
		$interface->declaration = $name . '(' . $inputs . " );\n"
			. $result . ' operator()(' . $argument . " value) const;\nexplicit operator bool() const;\n";
		$owner = 'callback_owner_' . $name;
		$interface->implementation = 'struct ' . $owner . ' { void* state; '
			. $result . ' (*call)(void*, ' . $argument . '); void* (*copy)(const void*); void (*destroy)(void*); '
			. $owner . '(' . $inputs . ') : state(state), call(call), copy(copy), destroy(destroy) {} '
			. $owner . '(const ' . $owner . '& other) : state(other.copy(other.state)), call(other.call), copy(other.copy), destroy(other.destroy) {} '
			. $owner . '(' . $owner . '&& other) noexcept : state(other.state), call(other.call), copy(other.copy), destroy(other.destroy) { other.state = nullptr; } '
			. '~' . $owner . '() { if (state) destroy(state); } '
			. $result . ' operator()(' . $argument . ' value) const { return call(state, std::move(value)); } }; '
			. $name . '::' . $name . '(' . $inputs . ') { ' . $owner . ' owner(state, call, copy, destroy); '
			. '::new(address()) ' . $native . '(std::move(owner)); }' . "\n"
			. $result . ' ' . $name . '::operator()(' . $argument . ' value) const { return unwrap(*this)(std::move(value)); }' . "\n"
			. $name . '::operator bool() const { return static_cast<bool>(unwrap(*this)); }' . "\n";
		return $interface;
	}
}
