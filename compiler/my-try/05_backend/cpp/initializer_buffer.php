<?php
namespace scpp\compiler;

/** Physical transport of the existing call-scoped runtime initializer buffer. */
final class CPP_Initializer_Buffer
{
	public static function reference(data_construction_node $node, initialization_binding $binding,
		cpp_generation_context $context): string
	{
		$entries /** Storage<data_entry_node> */ = $node->entries;
		if ($entries->is_empty()) { throw new \LogicException('Initializer buffer requires nonempty input'); }
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::initialize_buffer);
		$request->initialization = $binding;
		$request->result = $binding->target();
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$first = $entries[0]->require_preparation();
		if ($first->key_conversion !== null) { $inputs->append($first->key_conversion->target_type); }
		$inputs->append($first->value_conversion->target_type);
		$entry = CPP_Runtime_Operations::reference($request, $context);
		return 'scpp::internal::' . $entry->name;
	}

	/** Entry locals already contain the ordered, converted source snapshots. */
	public static function append(prepared_data_entry $facts, string $buffer, string $key, string $value,
		cpp_generation_context $context): string
	{
		$arguments = '';
		if ($facts->key_conversion !== null) {
			$arguments = CPP_Runtime_Operations::address(new cpp_runtime_operand($facts->key_conversion->target_type, $key), $context) . ', ';
		}
		$arguments .= CPP_Runtime_Operations::address(new cpp_runtime_operand($facts->value_conversion->target_type, $value), $context);
		return $buffer . '.append(' . $arguments . ')';
	}

	/** A nonmovable inline owner preserves the native buffer's self-referencing local storage. */
	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$binding /** initialization_binding */ = $request->initialization;
		$result /** canonical_type_use */ = $request->result;
		CPP_Opaque_Representation::require_native_type($result);
		$native_context = new cpp_generation_context();
		$factory = CPP_Data_Construction::reference($binding, $native_context);
		$parameters /** Storage<callable_parameter> */ = $binding->factory()->signature()->parameters;
		$input = object_cast(Type_Queries::canonical($parameters[0]->type), applied_template_type::class);
		$element_type = $input->arguments()[0];
		CPP_Opaque_Representation::require_native_type($element_type);
		$element = CPP_Declarations::type($element_type, $native_context);
		$native_result = CPP_Declarations::type($result, $native_context);
		$mapping = CPP_Types::representation($result, new cpp_generation_context($engine));
		$name = CPP_Runtime_Operations::name($request);
		$native = 'native_' . $name;
		$prefix = 'SCPP_LAYOUT_' . $name;
		$signature = '';
		$values = '';
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($inputs as $position => $type) {
			CPP_Opaque_Representation::require_native_type($type);
			if ($position !== 0) { $signature .= ', '; $values .= ', '; }
			$signature .= 'const void* p' . $position;
			$values .= '*std::launder(static_cast<const ' . CPP_Declarations::type($type, $native_context) . '*>(p' . $position . '))';
		}
		// Match the ordinary append expression exactly: lvalue for list entries, aggregate prvalue for keyed entries.
		if ($binding->is_keyed()) { $values = $element . '{' . $values . '}'; }
		$native_context->headers['scpp/data_initializer.hpp'] = true;
		$aliases = CPP_Artifacts::includes($native_context->headers, '')
			. 'using ' . $native . ' = scpp::initializer_buffer<' . $element . ">;\n";
		$probe = 'std::printf("#define ' . $prefix . '_SIZE %zu\\n#define ' . $prefix . '_ALIGN %zu\\n", sizeof('
			. $native . '), alignof(' . $native . "));\n";
		$header = 'specializations/' . $name . '.hpp';
		$declaration = "#pragma once\n#include <cstddef>\n" . CPP_Artifacts::includes($mapping->headers, '')
			. '#ifndef ' . $prefix . "_SIZE\n#error Native layout resolution is required\n#endif\n"
			. 'namespace scpp::internal { class alignas(' . $prefix . '_ALIGN) ' . $name . " final {\n"
			. 'unsigned char storage_[' . $prefix . "_SIZE];\npublic:\n"
			. 'explicit ' . $name . '(std::size_t count); ~' . $name . "() noexcept;\n"
			. $name . '(const ' . $name . '&) = delete; ' . $name . '& operator=(const ' . $name . "&) = delete;\n"
			. $name . '(' . $name . '&&) = delete; ' . $name . '& operator=(' . $name . "&&) = delete;\n"
			. 'void append(' . $signature . '); ' . $mapping->spelling . " finish();\n}; }\n";
		$implementation = $aliases . "#include <memory>\n#include <new>\n#include <type_traits>\nnamespace scpp::internal {\n"
			. 'static_assert(sizeof(' . $name . ') == sizeof(' . $native . ')); static_assert(alignof(' . $name . ') == alignof(' . $native . "));\n"
			. 'static_assert(std::is_nothrow_destructible_v<' . $native . ">);\n"
			. $name . '::' . $name . '(std::size_t count) { ::new(storage_) ' . $native . "(count); }\n"
			. $name . '::~' . $name . '() noexcept { std::destroy_at(std::launder(reinterpret_cast<' . $native . "*>(storage_))); }\n"
			. 'void ' . $name . '::append(' . $signature . ') { std::launder(reinterpret_cast<' . $native . '*>(storage_))->append(' . $values . "); }\n";
		$finish = $factory . '(std::launder(reinterpret_cast<const ' . $native . '*>(storage_))->view())';
		if ($mapping->opaque) {
			$finish = $mapping->spelling . '(' . $mapping->spelling . '::operation_result{}, +[](void* destination, const void* input) { '
				. '::new(destination) ' . $native_result . '(' . $factory . '(std::launder(static_cast<const '
				. $native . '*>(input))->view())); }, storage_)';
		}
		$implementation .= $mapping->spelling . ' ' . $name . '::finish() { return ' . $finish . "; }\n}\n";
		return new cpp_specialization_entry($name, $header, $declaration, $implementation, [], $aliases, $probe);
	}
}
