<?php
namespace scpp\compiler;

/** Physical representation only: canonical identities/capabilities remain in their existing owners. */
final class CPP_Opaque_Representation
{
	public static function representation(canonical_type_use $type, CPP_Specializations $engine, bool $compose_arguments = false): cpp_type
	{
		$entry = $engine->entry($compose_arguments ? $engine->request_composed($type) : $engine->request_opaque($type));
		$mapping = new cpp_type();
		$mapping->spelling = 'scpp::internal::' . $entry->name;
		$mapping->headers[$entry->header] = true;
		$mapping->specialization = $type;
		$mapping->opaque = true;
		$mapping->literal = CPP_Types::representation($type)->literal;
		return $mapping;
	}

	/** Callable transport remains unsupported; physical layout ordering belongs to the engine. */
	public static function require_native_type(canonical_type_use $type): void
	{
		$canonical = Type_Queries::canonical($type);
		if ($canonical->definition() instanceof callable_type_definition) {
			throw new \RuntimeException('Opaque runtime callable storage needs callback ABI transport');
		}
		if ($canonical instanceof applied_template_type) {
			foreach (object_cast($canonical, applied_template_type::class)->arguments() as $argument) {
				self::require_native_type($argument);
			}
		}
	}

	/** Stable output identity: distinct semantic types may share one native C++ spelling. */
	public static function identity(canonical_type_use $type): string
	{
		$canonical = Type_Queries::canonical($type);
		$text = $canonical->definition()->name();
		if ($canonical instanceof applied_template_type) {
			$text .= '<';
			$separator = '';
			foreach (object_cast($canonical, applied_template_type::class)->arguments() as $argument) {
				$text .= $separator . self::identity($argument);
				$separator = ',';
			}
			$text .= '>';
		}
		return $type->by_value() ? 'value<' . $text . '>' : $text;
	}

	/** Consume the prepared boundary; never infer borrowing from a spelling or capability. */
	public static function borrowed_argument(prepared_call_argument $argument, string $expression): string
	{
		if (($argument->boundary !== value_boundary_operation::observe_value)
			&& ($argument->boundary !== value_boundary_operation::bind_reference)) {
			throw new \LogicException('Opaque borrowed transport requires a prepared borrow/observation');
		}
		if ($argument->conversion !== null) { throw new \LogicException('Borrowed transport cannot convert storage'); }
		return '(' . $expression . ').address()';
	}

	/** First operation mechanism: one readonly observation and direct owned-result construction. */
	public static function observation(prepared_call $call, CPP_Specializations $engine): cpp_specialization_entry
	{
		$arguments /** Storage<prepared_call_argument> */ = $call->arguments;
		$parameters /** Storage<callable_parameter> */ = $call->signature->parameters;
		if ((q_count($arguments) !== 1) || (q_count($parameters) !== 1)
			|| ($call->signature->reference_return !== null) || ($call->variadic_parameter !== null)) {
			throw new \RuntimeException('Opaque observation requires one input and an owned result');
		}
		if (($arguments[0]->boundary !== value_boundary_operation::observe_value)
			|| ($parameters[0]->mode !== passing_mode::observation)) {
			throw new \RuntimeException('Opaque operation requires a prepared observation');
		}
		self::borrowed_argument($arguments[0], 'input');
		self::require_native_type($parameters[0]->type);
		self::require_native_type($call->signature->return_type);
		$target = object_cast(weakref_get($call->target), runtime_function_definition::class);
		$binding = Model::$cpp_callable_bindings->function_binding($target);
		$input = CPP_Types::representation($parameters[0]->type);
		$output = CPP_Types::representation($call->signature->return_type);
		$name = 'observe_' . CPP_Artifacts::component($target->key() . ':' . $input->spelling . ':' . $output->spelling);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		$header = 'specializations/' . $name . '.hpp';
		$signature = 'void ' . $name . '(void* destination, const void* input)';
		$headers = $input->headers;
		foreach ($output->headers as $path => $needed) { $headers[$path] = $needed; }
		$headers[$binding->header()] = true;
		$implementation = CPP_Artifacts::includes($headers, '') . "#include <new>\nnamespace scpp::internal {\n"
			. $signature . ' { ::new(destination) ' . $output->spelling . '(' . $binding->symbol()
			. '(*std::launder(static_cast<const ' . $input->spelling . "*>(input)))); }\n}\n";
		return new cpp_specialization_entry($name, $header,
			"#pragma once\nnamespace scpp::internal { " . $signature . "; }\n", $implementation, []);
	}

	public static function entry(canonical_type_use $type, CPP_Specializations $engine, bool $compose_arguments = false): cpp_specialization_entry
	{
		self::require_native_type($type);
		$capabilities = new Capability_Facts();
		if (!$capabilities->has_capability($type, generic_contract::value_storable)) {
			throw new \RuntimeException('Opaque representation requires a storable type');
		}
		// Both mappings consume the existing registry; composition substitutes physical arguments.
		$mapping = $compose_arguments ? CPP_Types::implementation_representation($type, $engine) : CPP_Types::representation($type);
		$identity = self::identity($type);
		$name = $compose_arguments ? 'concrete_' . CPP_Artifacts::bounded_component($identity, $identity . ':' . $mapping->spelling)
			: 'opaque_' . CPP_Artifacts::component($identity . ':' . $mapping->spelling);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		$native = 'native_' . $name;
		$prefix = 'SCPP_LAYOUT_' . $name;
		$includes = CPP_Artifacts::includes($mapping->headers, '');
		$aliases = $includes . 'using ' . $native . ' = ' . $mapping->spelling . ";\n";
		$copyable = $capabilities->has_capability($type, generic_contract::copyable_value);
		$probe = 'std::printf("#define ' . $prefix . '_SIZE %zu\\n#define ' . $prefix . '_ALIGN %zu\\n", sizeof('
			. $native . '), alignof(' . $native . "));\n";
		$declaration = "#pragma once\n#ifndef " . $prefix . "_SIZE\n#error Native layout resolution is required\n#endif\n"
			. 'namespace scpp::internal { struct alignas(' . $prefix . '_ALIGN) ' . $name . " final {\n"
			. 'struct operation_result {}; ' . $name . '(operation_result, void (*construct)(void*, const void*), const void* input);' . "\n"
			. 'private: unsigned char storage_[' . $prefix . "_SIZE];\npublic:\n"
			. "void* address() noexcept { return storage_; }\nconst void* address() const noexcept { return storage_; }\n";
		$implementation = $aliases . "#include <memory>\n#include <new>\n#include <utility>\nnamespace scpp::internal {\n"
			. 'static_assert(sizeof(' . $name . ') == sizeof(' . $native . "));\n"
			. 'static_assert(alignof(' . $name . ') == alignof(' . $native . "));\n"
			. $native . '& unwrap(' . $name . '& value) { return *std::launder(static_cast<' . $native . "*>(value.address())); }\n"
			. 'const ' . $native . '& unwrap(const ' . $name . '& value) { return *std::launder(static_cast<const ' . $native . "*>(value.address())); }\n";
		$implementation .= $name . '::' . $name . '(operation_result, void (*construct)(void*, const void*), const void* input) { construct(address(), input); }' . "\n";
		// Native traits govern the physical special members; semantic copy permission is independent.
		foreach (['default', 'copy', 'move', 'copy_assign', 'move_assign'] as $operation) {
			$trait = '';
			$signature = '';
			$body = '';
			if ($operation === 'default') {
				$trait = 'is_default_constructible'; $signature = $name . '()';
				$body = '::new(address()) ' . $native . '();';
			}
			elseif ($operation === 'copy') {
				$trait = 'is_copy_constructible'; $signature = $name . '(const ' . $name . '& other)';
				$body = '::new(address()) ' . $native . '(unwrap(other));';
			}
			elseif ($operation === 'move') {
				$trait = 'is_move_constructible'; $signature = $name . '(' . $name . '&& other)';
				$body = '::new(address()) ' . $native . '(std::move(unwrap(other)));';
			}
			elseif ($operation === 'copy_assign') {
				$trait = 'is_copy_assignable'; $signature = 'operator=(const ' . $name . '& other)';
				$body = 'unwrap(*this) = unwrap(other); return *this;';
			}
			else {
				$trait = 'is_move_assignable'; $signature = 'operator=(' . $name . '&& other)';
				$body = 'unwrap(*this) = std::move(unwrap(other)); return *this;';
			}
			$return_type = ($operation === 'copy_assign') || ($operation === 'move_assign') ? $name . '& ' : '';
			$permission = (!$copyable && (($operation === 'copy') || ($operation === 'copy_assign'))) ? 'false' : 'true';
			$macro = $prefix . '_' . $operation;
			$probe .= 'std::printf("#define ' . $macro . ' %d\\n", int(' . $permission . ' && std::' . $trait . '_v<' . $native . ">));\n";
			$nothrow_trait = 'is_nothrow_' . string_byte_slice($trait, 3, string_byte_len($trait));
			$probe .= 'std::printf("#define ' . $macro . '_nothrow %d\\n", int(std::' . $nothrow_trait . '_v<' . $native . ">));\n";
			$signature .= ' noexcept(' . $macro . '_nothrow)';
			$declaration .= '#if ' . $macro . "\n" . $return_type . $signature . ";\n#else\n"
				. $return_type . $signature . " = delete;\n#endif\n";
			$implementation .= '#if ' . $macro . "\n" . $return_type . $name . '::' . $signature . ' { ' . $body . " }\n#endif\n";
		}
		if ($mapping->literal === cpp_literal_kind::signed_integer) {
			$definition = object_cast(Type_Queries::canonical($type)->definition(), integer_type_definition::class);
			$scalar = ($definition->signed() ? 'int' : 'uint') . $definition->bit_width() . '_t';
			$declaration = "#include <cstdint>\n" . $declaration;
			$declaration .= 'using scpp_runtime_integer_rep = ' . $scalar . ";\n";
			$declaration .= 'explicit ' . $name . '(' . $scalar . ' value); ' . $scalar . " native_value() const noexcept;\n";
			$implementation .= $name . '::' . $name . '(' . $scalar . ' value) { ::new(address()) ' . $native . '(value); }' . "\n"
				. $scalar . ' ' . $name . '::native_value() const noexcept { return unwrap(*this).native_value(); }' . "\n";
		}
		$declaration .= '~' . $name . "() noexcept;\n";
		$implementation .= 'static_assert(std::is_nothrow_destructible_v<' . $native . ">);\n"
			. $name . '::~' . $name . "() noexcept { std::destroy_at(&unwrap(*this)); }\n}\n";
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp',
			$declaration, $implementation, $mapping->headers, $aliases, $probe, "}; }\n");
	}
}
