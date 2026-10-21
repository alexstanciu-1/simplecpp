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
		return '([&]() -> ' . $storage . ' { auto callback = ' . $expression . '; using Captures = decltype(callback); '
			. 'return ' . $storage . '(new Captures(static_cast<Captures&&>(callback)), '
			. '+[](void* state' . $declarations . ') -> ' . $result . ' { return (*static_cast<Captures*>(state))(' . $arguments . '); }, '
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
		$declarations = '';
		$arguments = '';
		$body = '';
		foreach ($parameters as $index => $parameter) {
			$input = $parameter->type;
			$physical_input = CPP_Types::representation($input, $physical_context);
			$native_input = CPP_Types::implementation_representation($input, $engine);
			CPP_Types::record_dependencies($physical_input, $context);
			CPP_Types::record_dependencies($native_input, $context);
			$name = 'value_' . $index;
			$argument_type = $native_payloads ? $native_input->spelling : $physical_input->spelling;
			$declarations .= ($index === 0 ? '' : ', ') . $argument_type . ' ' . $name;
			$argument = $name;
			if ($native_payloads && $physical_input->opaque) {
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
		$native_output = CPP_Types::implementation_representation($output, $engine);
		$convert_output = $native_payloads || $output->matches(Language_Types::boolean(Model::$language_scope));
		CPP_Types::record_dependencies($physical_output, $context);
		CPP_Types::record_dependencies($native_output, $context);
		$result_type = $convert_output ? $native_output->spelling : $physical_output->spelling;
		if (Type_Queries::canonical($output)->family() === type_family::no_value) {
			$body .= 'callback(' . $arguments . '); ';
		}
		else {
			$body .= 'auto result = callback(' . $arguments . '); ';
			$body .= ($convert_output && $physical_output->opaque)
				? 'return *static_cast<const ' . $native_output->spelling . '*>(result.address()); '
				: 'return result; ';
		}
		// The adapter may outlive the operation helper: never retain its stack inputs.
		return '[callback = (' . $expression . ')](' . $declarations . ') mutable -> ' . $result_type . ' { ' . $body . '}';
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
		$interface->declaration_prefix = CPP_Artifacts::includes($context->headers, '');
		$inputs = 'void* state, ' . $result . ' (*call)(void*' . $types . '), void* (*copy)(const void*), void (*destroy)(void*)';
		$interface->declaration = $name . '(' . $inputs . " );\n"
			. $result . ' operator()(' . $declarations . ") const;\nexplicit operator bool() const;\n";
		$owner = 'callback_owner_' . $name;
		$interface->implementation = 'struct ' . $owner . ' { void* state; '
			. $result . ' (*call)(void*' . $types . '); void* (*copy)(const void*); void (*destroy)(void*); '
			. $owner . '(' . $inputs . ') : state(state), call(call), copy(copy), destroy(destroy) {} '
			. $owner . '(const ' . $owner . '& other) : state(other.copy(other.state)), call(other.call), copy(other.copy), destroy(other.destroy) {} '
			. $owner . '(' . $owner . '&& other) noexcept : state(other.state), call(other.call), copy(other.copy), destroy(other.destroy) { other.state = nullptr; } '
			. '~' . $owner . '() { if (state) destroy(state); } '
			. $result . ' operator()(' . $declarations . ') const { return call(state' . ($arguments === '' ? '' : ', ' . $arguments) . '); } }; '
			. $name . '::' . $name . '(' . $inputs . ') { ' . $owner . ' owner(state, call, copy, destroy); '
			. '::new(address()) ' . $native . '(std::move(owner)); }' . "\n"
			. $result . ' ' . $name . '::operator()(' . $declarations . ') const { return unwrap(*this)(' . $arguments . '); }' . "\n"
			. $name . '::operator bool() const { return static_cast<bool>(unwrap(*this)); }' . "\n";
		return $interface;
	}
}
