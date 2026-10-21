<?php
namespace scpp\compiler;

/** Expression-local snapshots use an owned coroutine scope only across real await. */
final class CPP_Sequence
{
	public static function suspends(cpp_generation_context $context): bool
	{
		return $context->asynchronous && $context->suspending_expression;
	}

	public static function open(string $type, cpp_generation_context $context, bool $reference = false): string
	{
		if (!self::suspends($context)) { return '([&]() -> ' . $type . ($reference ? '&' : '') . ' { '; }
		$context->headers['scpp/async_core.hpp'] = true;
		return ($reference ? '(*' : '(') . 'co_await scpp::async_core::invoke_owned([&]() -> scpp::async_core::task<'
			. $type . ($reference ? '*' : '') . '> { ';
	}

	public static function close(cpp_generation_context $context): string
	{
		return self::suspends($context) ? '}))' : '}())';
	}

	public static function result(string $value, cpp_generation_context $context, bool $reference = false): string
	{
		if (!self::suspends($context)) { return 'return ' . $value . '; '; }
		return 'co_return ' . ($reference ? '&(' . $value . ')' : $value) . '; ';
	}
}
