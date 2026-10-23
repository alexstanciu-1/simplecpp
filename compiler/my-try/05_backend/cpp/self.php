<?php
namespace scpp\compiler;

/** Source self ownership uses prepared acquisition and construction, never call-site inference. */
final class CPP_Self
{
	public static function base(cpp_generation_context $context): string
	{
		if (!$context->concrete_types()) {
			$context->headers['scpp/source_self.hpp'] = true;
			return '::scpp::source_self_state';
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::self_storage);
		$entry = CPP_Runtime_Operations::reference($request, $context);
		return '::scpp::internal::' . $entry->name;
	}

	public static function acquire(prepared_variable_reference $reference, cpp_generation_context $context): string
	{
		if (!$context->concrete_types()) {
			$context->headers['scpp/source_self.hpp'] = true;
			return '::scpp::retain_source_self(this)';
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::self_acquire);
		$request->result = $reference->type;
		$request->inputs->append($reference->type);
		$entry = CPP_Runtime_Operations::reference($request, $context);
		return 'scpp::internal::' . $entry->name . '(this)';
	}

	/** Internal physical state follows the existing layout-probed specialization protocol. */
	public static function storage_entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		$macro = 'SCPP_LAYOUT_' . $name;
		$native = '::scpp::source_self_state';
		$aliases = "#include <scpp/source_self.hpp>\n";
		$probe = 'std::printf("#define ' . $macro . '_SIZE %zu\\n#define ' . $macro
			. '_ALIGN %zu\\n", sizeof(' . $native . '), alignof(' . $native . "));\n";
		$declaration = "#pragma once\nnamespace scpp::internal { class alignas(" . $macro . '_ALIGN) ' . $name . " {\n"
			. 'unsigned char storage_[' . $macro . "_SIZE];\npublic:\n"
			. $name . '() noexcept; ~' . $name . "() noexcept;\n"
			. $name . '(const ' . $name . '&) noexcept; ' . $name . '(' . $name . "&&) noexcept;\n"
			. $name . '& operator=(const ' . $name . '&) noexcept; ' . $name . '& operator=(' . $name . "&&) noexcept;\n"
			. "void* scpp_self_state_address() noexcept { return storage_; }\n}; }\n";
		$access = 'std::launder(reinterpret_cast<' . $native . '*>(storage_))';
		$implementation = $aliases . "#include <new>\nnamespace scpp::internal {\n"
			. 'static_assert(sizeof(' . $name . ') == sizeof(' . $native . ')); static_assert(alignof(' . $name . ') == alignof(' . $native . "));\n"
			. $name . '::' . $name . '() noexcept { ::new(storage_) ' . $native . "; }\n"
			. $name . '::~' . $name . '() noexcept { std::destroy_at(' . $access . "); }\n"
			. $name . '::' . $name . '(const ' . $name . '&) noexcept : ' . $name . "() {}\n"
			. $name . '::' . $name . '(' . $name . '&&) noexcept : ' . $name . "() {}\n"
			. $name . '& ' . $name . '::operator=(const ' . $name . "&) noexcept { return *this; }\n"
			. $name . '& ' . $name . '::operator=(' . $name . "&&) noexcept { return *this; }\n}\n";
		$dependencies /** hash<bool> */ = [];
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $declaration,
			$implementation, $dependencies, $aliases, $probe);
	}
}
