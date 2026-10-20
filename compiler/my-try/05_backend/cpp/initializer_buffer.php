<?php
namespace scpp\compiler;

/** Physical transport of the existing call-scoped runtime initializer buffer. */
final class CPP_Initializer_Buffer
{
	/** Concrete initialization shares ordinary sequencing and buffer lifetime ownership. */
	public static function active(cpp_generation_context $context): bool
	{
		$engine = $context->specialization_engine;
		return $engine !== null;
	}

	/** Buffer elements are physical values; only the outer result uses its native implementation. */
	private static function physical_type(canonical_type_use $type, cpp_generation_context $context,
		CPP_Specializations $engine, bool $implementation): string
	{
		$mapping = $implementation ? CPP_Types::implementation_representation($type, $engine)
			: CPP_Types::representation($type, new cpp_generation_context($engine));
		CPP_Types::record_dependencies($mapping, $context);
		return $mapping->spelling;
	}

	/** Empty initialization still invokes the selected factory, not an assumed default constructor. */
	public static function empty_entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$binding /** initialization_binding */ = $request->initialization;
		$result /** canonical_type_use */ = $request->result;
		$context = new cpp_generation_context();
		$factory = CPP_Data_Construction::reference($binding, $context, $engine);
		$parameters /** Storage<callable_parameter> */ = $binding->factory()->signature()->parameters;
		$input = object_cast(Type_Queries::canonical($parameters[0]->type), applied_template_type::class);
		$element = self::physical_type($input->arguments()[0], $context, $engine, $binding->is_keyed());
		// Keep the registered input-view binding, substituting its element's physical representation.
		$input_binding = Model::$cpp_type_bindings->definition($input->definition()->definition_id());
		$context->headers[$input_binding->header()] = true;
		$native = self::physical_type($result, $context, $engine, true);
		$mapping = CPP_Types::representation($result, new cpp_generation_context($engine));
		$name = CPP_Runtime_Operations::name($request);
		$expression = $factory . '(' . $input_binding->name() . '<' . $element . '>{})';
		$body = 'return ' . $expression . ';';
		if ($mapping->opaque) {
			$body = 'return ' . $mapping->spelling . '(' . $mapping->spelling
				. '::operation_result{}, +[](void* destination, const void*) { ::new(destination) '
				. $native . '(' . $expression . '); }, nullptr);';
		}
		$prototype = $mapping->spelling . ' ' . $name . '()';
		$header = 'specializations/' . $name . '.hpp';
		$declaration = "#pragma once\n" . CPP_Artifacts::includes($mapping->headers, '')
			. 'namespace scpp::internal { ' . $prototype . "; }\n";
		$implementation = CPP_Artifacts::includes($context->headers, '') . "#include <new>\n"
			. 'namespace scpp::internal { ' . $prototype . ' { ' . $body . " } }\n";
		return new cpp_specialization_entry($name, $header, $declaration, $implementation, []);
	}

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
			$arguments = self::input_address($facts->key_conversion->target_type, $key, $context) . ', ';
		}
		$arguments .= self::input_address($facts->value_conversion->target_type, $value, $context);
		return $buffer . '.append(' . $arguments . ')';
	}

	private static function input_address(canonical_type_use $type, string $value, cpp_generation_context $context): string
	{
		return '&(' . $value . ')';
	}

	/** A nonmovable inline owner preserves the native buffer's self-referencing local storage. */
	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$binding /** initialization_binding */ = $request->initialization;
		$result /** canonical_type_use */ = $request->result;
		CPP_Opaque_Representation::require_native_type($result);
		$native_context = new cpp_generation_context();
		$factory = CPP_Data_Construction::reference($binding, $native_context, $engine);
		$parameters /** Storage<callable_parameter> */ = $binding->factory()->signature()->parameters;
		$input = object_cast(Type_Queries::canonical($parameters[0]->type), applied_template_type::class);
		$element_type = $input->arguments()[0];
		CPP_Opaque_Representation::require_native_type($element_type);
		$element = self::physical_type($element_type, $native_context, $engine, $binding->is_keyed());
		$native_result = self::physical_type($result, $native_context, $engine, true);
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
			$values .= '*std::launder(static_cast<const ' . self::physical_type($type, $native_context, $engine, false) . '*>(p' . $position . '))';
		}
		// Match the ordinary append expression exactly: lvalue for list entries, aggregate prvalue for keyed entries.
		if ($binding->is_keyed()) { $values = $element . '{' . $values . '}'; }
		$native_context->headers['scpp/data_initializer.hpp'] = true;
		$aliases = CPP_Artifacts::includes($native_context->headers, '')
			. 'using ' . $native . ' = scpp::initializer_buffer<' . $element . ">;\n";
		$probe = 'std::printf("#define ' . $prefix . '_SIZE %zu\\n#define ' . $prefix . '_ALIGN %zu\\n", sizeof('
			. $native . '), alignof(' . $native . "));\n";
		$header = 'specializations/' . $name . '.hpp';
		$declaration = "#pragma once\n#include <stddef.h>\n" . CPP_Artifacts::includes($mapping->headers, '')
			. '#ifndef ' . $prefix . "_SIZE\n#error Native layout resolution is required\n#endif\n"
			. 'namespace scpp::internal { class alignas(' . $prefix . '_ALIGN) ' . $name . " final {\n"
			. 'unsigned char storage_[' . $prefix . "_SIZE];\npublic:\n"
			. 'explicit ' . $name . '(size_t count); ~' . $name . "() noexcept;\n"
			. $name . '(const ' . $name . '&) = delete; ' . $name . '& operator=(const ' . $name . "&) = delete;\n"
			. $name . '(' . $name . '&&) = delete; ' . $name . '& operator=(' . $name . "&&) = delete;\n"
			. 'void append(' . $signature . '); ' . $mapping->spelling . " finish();\n}; }\n";
		$implementation = $aliases . "#include <memory>\n#include <new>\n#include <type_traits>\nnamespace scpp::internal {\n"
			. 'static_assert(sizeof(' . $name . ') == sizeof(' . $native . ')); static_assert(alignof(' . $name . ') == alignof(' . $native . "));\n"
			. 'static_assert(std::is_nothrow_destructible_v<' . $native . ">);\n"
			. $name . '::' . $name . '(size_t count) { ::new(storage_) ' . $native . "(count); }\n"
			. $name . '::~' . $name . '() noexcept { std::destroy_at(std::launder(reinterpret_cast<' . $native . "*>(storage_))); }\n"
			. 'void ' . $name . '::append(' . $signature . ') { std::launder(reinterpret_cast<' . $native . '*>(storage_))->append(' . $values . "); }\n";
		$finish = $factory . '(std::launder(reinterpret_cast<const ' . $native . '*>(storage_))->view())';
		if ($mapping->opaque) {
			$finish = $mapping->spelling . '(' . $mapping->spelling . '::operation_result{}, +[](void* destination, const void* input) { '
				. '::new(destination) ' . $native_result . '(' . $factory . '(std::launder(static_cast<const '
				. $native . '*>(input))->view())); }, storage_)';
		}
		$implementation .= $mapping->spelling . ' ' . $name . '::finish() { return ' . $finish . "; }\n}\n";
		return new cpp_specialization_entry($name, $header, $declaration, $implementation, $native_context->headers, $aliases, $probe);
	}
}
