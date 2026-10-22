<?php

/* Delayed exit transport is backend-local; syntax admission and completion are prepared. */
namespace scpp\compiler;

final class CPP_Finally
{
	/** Capture a fully converted value (or evaluate void forwarding), then exit this lambda. */
	public static function capture_return(cpp_finally_exit $pending, string $value): string
	{
		$text = '';
		if ($pending->value !== null) {
			$text = $pending->value . '.emplace(' . $value . ");\n";
		}
		elseif ($value !== '') {
			$text = $value . ";\n";
		}
		return $text . $pending->flag . " = true;\n" . ($pending->asynchronous ? "co_return;\n" : "return;\n");
	}

	/** Native unwinding finishes protected locals before finally runs outside exception capture. */
	public static function generate(try_node $node, cpp_generation_context $context): string
	{
		$facts = $node->require_preparation();
		$id = $context->next_temporary;
		$context->next_temporary++;
		$exception = 'finally_exception_' . $id;
		$pending = new cpp_finally_exit();
		$pending->flag = 'finally_return_' . $id;
		$pending->transfer = 'finally_transfer_' . $id;
		$pending->break_target = $context->break_target;
		$pending->continue_target = $context->continue_target;
		$pending->concrete = $context->concrete_types();
		$pending->asynchronous = $context->asynchronous;
		$text = "{\n";
		if ($pending->concrete)
		{
			$request = new cpp_runtime_operation(cpp_runtime_operation_kind::finally_storage);
			if ($facts->captures_return) {
				$request->result = $context->return_type;
				$request->spelling = $context->return_type === null ? 'entry' : 'callable';
			}
			$storage = CPP_Runtime_Operations::reference($request, $context);
			$text .= 'scpp::internal::' . $storage->name . ' ' . $exception . ";\n";
		}
		else {
			$context->headers['exception'] = true;
			$text .= 'std::exception_ptr ' . $exception . ";\n";
		}
		if ($facts->captures_return)
		{
			$text .= 'bool ' . $pending->flag . " = false;\n";
			$type = $context->return_type;
			// Entry returns use native int; only callable returns have a semantic type.
			$spelling = 'int';
			if ($type !== null) {
				$spelling = '';
				if (Type_Queries::canonical($type)->family() !== type_family::no_value) {
					$spelling = CPP_Declarations::type($type, $context);
				}
			}
			if ($spelling !== '') {
				if ($pending->concrete) {
					$pending->value = $exception;
				}
				else {
					$pending->value = 'finally_value_' . $id;
					$context->headers['optional'] = true;
					$context->headers['utility'] = true;
					$text .= 'std::optional<' . $spelling . '> ' . $pending->value . ";\n";
				}
			}
		}
		if (($facts->captures_break) || ($facts->captures_continue)) {
			$text .= 'int ' . $pending->transfer . " = 0;\n";
		}
		$outer = $context->finally_exit;
		$context->finally_exit = $pending;
		try
		{
			$protected_body = (new CPP_Syntax($context))->generate_protected($node);
			if ($pending->asynchronous) {
				$context->headers['scpp/async_core.hpp'] = true;
				$text .= "try { co_await scpp::async_core::invoke_owned([&]() -> scpp::async_core::task<void> {\n"
					. $protected_body . "co_return;\n}); }\n";
			}
			else {
				$text .= "try { [&]() -> void {\n" . $protected_body . "}(); }\n";
			}
		}
		finally {
			$context->finally_exit = $outer;
		}
		$text .= 'catch (...) { ' . $exception . ($pending->concrete ? '.capture_exception();' : ' = std::current_exception();') . " }\n";
		$finalizer /** block_node */ = $node->finalizer;
		$text .= (new CPP_Syntax($context))->generate_block($finalizer);
		$text .= $pending->concrete ? $exception . ".rethrow_if_exception();\n"
			: 'if (' . $exception . ') { std::rethrow_exception(' . $exception . "); }\n";
		if ($facts->captures_return)
		{
			$value = '';
			if ($pending->value !== null) {
				$value = $pending->concrete ? $pending->value . '.value()' : 'std::move(*' . $pending->value . ')';
			}
			$replay = $outer === null
				? ($pending->asynchronous ? 'co_return' : 'return') . ($value === '' ? '' : ' ' . $value) . ";\n"
				: self::capture_return($outer, $value);
			$text .= 'if (' . $pending->flag . ") {\n" . $replay . "}\n";
		}
		if ($facts->captures_break) {
			$text .= 'if (' . $pending->transfer . " == 1) {\n"
				. self::replay_transfer($outer, $pending->break_target, false) . "}\n";
		}
		if ($facts->captures_continue) {
			$text .= 'if (' . $pending->transfer . " == 2) {\n"
				. self::replay_transfer($outer, $pending->continue_target, true) . "}\n";
		}
		if (!$facts->can_fall_through) {
			// The prepared exit composition excludes this path; keep native return analysis explicit.
			$text .= ($pending->concrete ? $exception . ".unreachable();" : "std::terminate();")
				. " // unreachable: prepared finally region cannot fall through\n";
		}
		return $text . "}\n";
	}

	/** Replay through another protected lambda only when the same target crosses it. */
	public static function replay_transfer(?cpp_finally_exit $pending, ?breakable_node $target, bool $continuing): string
	{
		if ($pending !== null) {
			$boundary = $continuing ? $pending->continue_target : $pending->break_target;
			if (($target !== null) && ($target === $boundary)) {
				return $pending->transfer . ($continuing ? " = 2;\n" : " = 1;\n")
					. ($pending->asynchronous ? "co_return;\n" : "return;\n");
			}
		}
		return $continuing ? "continue;\n" : "break;\n";
	}

	/** Inline pending-exit storage uses physical values and the existing layout probe protocol. */
	public static function storage_entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) {
			return $existing;
		}
		$context = new cpp_generation_context($engine);
		$spelling = $request->spelling === 'entry' ? 'int' : '';
		$copyable = true;
		if ($request->result !== null)
		{
			$type /** canonical_type_use */ = $request->result;
			if (Type_Queries::canonical($type)->family() !== type_family::no_value) {
				$spelling = CPP_Declarations::type($type, $context);
				$capabilities = new Capability_Facts();
				$copyable = $capabilities->has_capability($type, generic_contract::copyable_value);
			}
		}
		$native = 'native_' . $name;
		$macro = 'SCPP_LAYOUT_' . $name;
		$aliases = CPP_Artifacts::includes($context->headers, '') . "#include <exception>\n#include <optional>\n"
			. 'struct ' . $native . ' { std::exception_ptr exception; '
			. ($spelling === '' ? '' : 'std::optional<' . $spelling . '> value; ') . "};\n";
		$probe = 'std::printf("#define ' . $macro . '_SIZE %zu\\n#define ' . $macro . '_ALIGN %zu\\n", sizeof('
			. $native . '), alignof(' . $native . "));\n";
		$declaration = "#pragma once\n" . CPP_Artifacts::includes($context->headers, '')
			. 'namespace scpp::internal { class alignas(' . $macro . '_ALIGN) ' . $name . " final {\n"
			. 'unsigned char storage_[' . $macro . "_SIZE];\npublic:\n"
			. $name . '(); ~' . $name . "() noexcept;\n"
			. $name . '(const ' . $name . '&) = delete; ' . $name . '& operator=(const ' . $name . "&) = delete;\n"
			. "void capture_exception() noexcept; void rethrow_if_exception(); [[noreturn]] void unreachable();\n";
		$access = 'std::launder(reinterpret_cast<' . $native . '*>(storage_))';
		$implementation = $aliases . "#include <memory>\n#include <new>\n#include <utility>\nnamespace scpp::internal {\n"
			. 'static_assert(sizeof(' . $name . ') == sizeof(' . $native . ')); static_assert(alignof(' . $name . ') == alignof(' . $native . "));\n"
			. $name . '::' . $name . '() { ::new(storage_) ' . $native . "; }\n"
			. $name . '::~' . $name . '() noexcept { std::destroy_at(' . $access . "); }\n"
			. 'void ' . $name . '::capture_exception() noexcept { ' . $access . "->exception = std::current_exception(); }\n"
			. 'void ' . $name . '::rethrow_if_exception() { if (' . $access . '->exception) { std::rethrow_exception(' . $access . "->exception); } }\n"
			. '[[noreturn]] void ' . $name . "::unreachable() { std::terminate(); }\n";
		if ($spelling !== '')
		{
			$declaration .= 'void emplace(' . $spelling . '&& value); ' . $spelling . "&& value();\n";
			$implementation .= 'void ' . $name . '::emplace(' . $spelling . '&& value) { ' . $access . "->value.emplace(std::move(value)); }\n"
				. $spelling . '&& ' . $name . '::value() { return std::move(*' . $access . "->value); }\n";
			if ($copyable) {
				$declaration .= 'void emplace(const ' . $spelling . "& value);\n";
				$implementation .= 'void ' . $name . '::emplace(const ' . $spelling . '& value) { ' . $access . "->value.emplace(value); }\n";
			}
		}
		$declaration .= "}; }\n";
		$implementation .= "}\n";
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $declaration,
			$implementation, $context->headers, $aliases, $probe);
	}

}
