<?php
namespace scpp\compiler;

/** Concrete physical interfaces for the existing runtime value-iteration protocol. */
final class CPP_Concrete_Iteration
{
	/** Retain only the settled iteration facts in the existing fragment request stream. */
	public static function require_interface(foreach_node $node, cpp_generation_context $context): void
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::concrete_iteration);
		$request->iteration = $node->require_preparation()->iteration;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($node->iterable->require_preparation()->type);
		CPP_Runtime_Operations::reference($request, $context);
	}

	/** Resolve native protocol layouts through the existing specialization and layout owners. */
	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) {
			return $existing;
		}
		$facts /** prepared_iteration */ = $request->iteration;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$context = new cpp_generation_context($engine);
		$source = CPP_Types::representation($inputs[0], $context);
		if (!$source->opaque) {
			throw new \RuntimeException('Concrete iteration adapter requires a substituted source representation');
		}
		$native = CPP_Types::implementation_representation($inputs[0], $engine);
		$key = CPP_Types::representation($facts->key_type, $context);
		$value = CPP_Types::representation($facts->value_type, $context);
		$headers = $source->headers;
		foreach ($key->headers as $path => $needed) {
			$headers[$path] = $needed;
		}
		foreach ($value->headers as $path => $needed) {
			$headers[$path] = $needed;
		}
		$aliases = CPP_Artifacts::includes($native->headers, '')
			. "#include <scpp/value_iteration.hpp>\n#include <utility>\n#include <type_traits>\n"
			. 'using native_' . $name . '_range = decltype(scpp::value_iteration(std::declval<const ' . $native->spelling . "&>()));\n"
			. 'using native_' . $name . '_iterator = decltype(std::declval<native_' . $name . "_range&>().begin());\n"
			. 'using native_' . $name . '_sentinel = decltype(std::declval<native_' . $name . "_range&>().end());\n"
			. 'using native_' . $name . '_entry = std::remove_cvref_t<decltype(*std::declval<native_' . $name . "_iterator&>())>;\n";
		$declaration = "#pragma once\n" . CPP_Artifacts::includes($headers, '') . "namespace scpp::internal {\n";
		$implementation = $aliases . "#include <memory>\n#include <new>\nnamespace scpp::internal {\n";
		$probe = '';
		// These are backend artifacts, not new semantic types or registry entries.
		foreach (['entry', 'sentinel', 'iterator', 'range'] as $role)
		{
			$type = $name . '_' . $role;
			$physical = 'native_' . $type;
			$macro = 'SCPP_LAYOUT_' . $type;
			$probe .= 'std::printf("#define ' . $macro . '_SIZE %zu\\n#define ' . $macro . '_ALIGN %zu\\n", sizeof('
				. $physical . '), alignof(' . $physical . "));\n";
			$declaration .= 'struct alignas(' . $macro . '_ALIGN) ' . $type . " final {\n"
				. 'private: unsigned char storage_[' . $macro . "_SIZE];\npublic:\n"
				. "void* address() noexcept { return storage_; }\nconst void* address() const noexcept { return storage_; }\n"
				. $type . '(void (*construct)(void*, const void*), const void* input);' . "\n"
				. $type . '(const ' . $type . "&) = delete;\n"
				. $type . '& operator=(const ' . $type . "&) = delete;\n"
				. '~' . $type . "() noexcept;\n";
			$implementation .= 'static_assert(sizeof(' . $type . ') == sizeof(' . $physical . "));\n"
				. 'static_assert(alignof(' . $type . ') == alignof(' . $physical . "));\n"
				. 'static_assert(std::is_nothrow_destructible_v<' . $physical . ">);\n"
				. $physical . '& unwrap(' . $type . '& value) { return *std::launder(static_cast<' . $physical . "*>(value.address())); }\n"
				. 'const ' . $physical . '& unwrap(const ' . $type . '& value) { return *std::launder(static_cast<const ' . $physical . "*>(value.address())); }\n"
				. $type . '::' . $type . '(void (*construct)(void*, const void*), const void* input) { construct(address(), input); }' . "\n"
				. $type . '::~' . $type . "() noexcept { std::destroy_at(&unwrap(*this)); }\n";
			if ($role === 'range')
			{
				foreach (['begin', 'end'] as $method)
				{
					$result = $name . ($method === 'begin' ? '_iterator' : '_sentinel');
					$declaration .= $result . ' ' . $method . "();\n";
					$implementation .= $result . ' ' . $type . '::' . $method . '() { '
						. self::construct($result, $physical, 'source.' . $method . '()', 'address()') . " }\n";
				}
			}
			elseif ($role === 'iterator')
			{
				$declaration .= $type . "& operator++();\nbool operator!=(const " . $name . "_sentinel& other) const;\n"
					. $name . "_entry operator*();\n";
				$implementation .= $type . '& ' . $type . "::operator++() { ++unwrap(*this); return *this; }\n"
					. 'bool ' . $type . '::operator!=(const ' . $name . "_sentinel& other) const { return unwrap(*this) != unwrap(other); }\n"
					. $name . '_entry ' . $type . '::operator*() { '
					. self::construct($name . '_entry', $physical, '*source', 'address()') . " }\n";
			}
			elseif ($role === 'entry')
			{
				foreach (['key', 'value_copy'] as $method)
				{
					$mapping = $method === 'key' ? $key : $value;
					$semantic = $method === 'key' ? $facts->key_type : $facts->value_type;
					$native_result = CPP_Types::implementation_representation($semantic, $engine);
					$expression = 'unwrap(*this).' . $method . '()';
					$body = $mapping->opaque ? CPP_Runtime_Operations::concrete_call_result($mapping->spelling, $native_result->spelling, '', $expression)
						: 'return ' . $expression . ';';
					$implementation = CPP_Artifacts::includes($native_result->headers, '') . $implementation;
					$declaration .= $mapping->spelling . ' ' . $method . "();\n";
					$implementation .= $mapping->spelling . ' ' . $type . '::' . $method . '() { ' . $body . " }\n";
				}
			}
			$declaration .= "};\n";
		}
		$signature = 'scpp::internal::' . $name . '_range value_iteration(const ' . $source->spelling . '& source)';
		$declaration .= "}\nnamespace scpp { " . $signature . "; }\n";
		$address = $source->opaque ? 'source.address()' : '&source';
		$implementation .= "}\nnamespace scpp { " . $signature . ' { using namespace scpp::internal; '
			. self::construct($name . '_range', $native->spelling, 'scpp::value_iteration(source)', $address, true) . " } }\n";
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $declaration,
			$implementation, $native->headers, $aliases, $probe);
	}

	/** Guaranteed elision constructs each protocol object directly in its final inline storage. */
	private static function construct(string $result, string $input, string $expression, string $address, bool $readonly = false): string
	{
		$pointer = $readonly ? 'input' : 'const_cast<void*>(input)';
		$qualifier = $readonly ? 'const ' : '';
		return 'return ' . $result . '(+[](void* destination, const void* input) { auto& source = *std::launder(static_cast<'
			. $qualifier . $input . '*>(' . $pointer . ')); ::new(destination) native_' . $result . '(' . $expression . '); }, ' . $address . ');';
	}
}
