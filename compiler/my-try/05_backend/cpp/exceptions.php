<?php
namespace scpp\compiler;

/** Exception bridges preserve the native shared payload; application headers expose no templates. */
final class CPP_Exceptions
{
	public static function generate_throw(throw_node $node, cpp_generation_context $context): string
	{
		$expression = (new CPP_Syntax($context))->expression($node->expression);
		if (!$context->concrete_types()) {
			$context->headers['lang/php/php_exceptions.hpp'] = true;
			CPP_Types::record_dependencies(CPP_Types::payload($node->expression->require_preparation()->type), $context);
			return 'throw ::scpp::php::make_thrown(' . $expression . ");\n";
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::exception_throw);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($node->expression->require_preparation()->type);
		$name = 'throw_value_' . $context->next_temporary;
		$context->next_temporary++;
		return '{ const auto& ' . $name . ' = ' . $expression . '; '
			. CPP_Runtime_Operations::invoke($request, [$name], $context, '') . "; }\n";
	}

	/** Active exception extraction happens in compiled middleware; foreign/unmatched throws propagate. */
	public static function generate_catch(try_node $node, cpp_generation_context $context): string
	{
		$handler /** catch_node */ = $node->handler;
		$binding = $handler->binding->require_preparation();
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::exception_catch);
		$request->result = $binding->type;
		$syntax = new CPP_Syntax($context);
		return 'try ' . $syntax->generate_block($node->body) . "catch (...) {\n"
			. $syntax->initialized_binding($binding, CPP_Runtime_Operations::invoke($request, [], $context, ''))
			. CPP_Generator::generate_statements($handler->body, $context) . "}\n";
	}

	/** Inline native exception_ptr storage uses the existing layout-probe publication contract. */
	public static function state_entry(cpp_runtime_operation $request): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$macro = 'SCPP_LAYOUT_' . $name;
		$declaration = "#pragma once\nnamespace scpp::internal {\n"
			. 'class alignas(' . $macro . '_ALIGN) ' . $name . " final {\n"
			. 'unsigned char storage_[' . $macro . "_SIZE];\npublic:\n"
			. $name . '() noexcept; ~' . $name . "() noexcept;\n"
			. $name . '(const ' . $name . '&) = delete; ' . $name . '& operator=(const ' . $name . "&) = delete;\n"
			. "void capture() noexcept; void replay() const; [[noreturn]] static void unreachable() noexcept;\n};\n}\n";
		$native = 'std::exception_ptr';
		$aliases = "#include <exception>\n";
		$implementation = $aliases . "#include <new>\n#include <memory>\nnamespace scpp::internal {\n"
			. 'static_assert(sizeof(' . $name . ') == sizeof(' . $native . "));\n"
			. 'static_assert(alignof(' . $name . ') == alignof(' . $native . "));\n"
			. $name . '::' . $name . '() noexcept { ::new(storage_) ' . $native . "(); }\n"
			. $name . '::~' . $name . '() noexcept { std::destroy_at(std::launder(reinterpret_cast<' . $native . "*>(storage_))); }\n"
			. 'void ' . $name . '::capture() noexcept { *std::launder(reinterpret_cast<' . $native . "*>(storage_)) = std::current_exception(); }\n"
			. 'void ' . $name . '::replay() const { const auto& pending = *std::launder(reinterpret_cast<const ' . $native
			. "*>(storage_)); if (pending) { std::rethrow_exception(pending); } }\n"
			. 'void ' . $name . "::unreachable() noexcept { std::terminate(); }\n}\n";
		$probe = 'std::printf("#define ' . $macro . '_SIZE %zu\\n#define ' . $macro . '_ALIGN %zu\\n", sizeof('
			. $native . '), alignof(' . $native . "));\n";
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $declaration,
			$implementation, [], $aliases, $probe);
	}
}
