<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Provides bounded C++ output helpers and byte-safe literal spelling for the current echo slice.
 */
final class Cpp_Output
{
	/** Fixed-width octal spelling preserves arbitrary bytes without C++ escape-boundary ambiguity. */
	public static function bytes(string $bytes): string
	{
		$literal = '"';
		for ($index = 0; $index < strlen($bytes); ++$index) {
			$literal .= '\\' . str_pad(decoct(ord($bytes[$index])), 3, '0', STR_PAD_LEFT);
		}
		return $literal . '"';
	}

	/** @todo Integrate the full runtime output API; see specs/planning/echo_debt.md. */
	public static function helpers(): string
	{
		return <<<'CPP'
#include <charconv>
#include <cstddef>
#include <cstdint>
#include <cstdio>
#include <stdexcept>
#include <system_error>

[[maybe_unused]] static void scpp_echo_bytes(const char *data, std::size_t length)
{
	if (std::fwrite(data, 1, length, stdout) != length) {
		throw std::runtime_error("echo: stdout write failed");
	}
}

[[maybe_unused]] static void scpp_echo_int(std::int64_t value)
{
	char buffer[32];
	const auto result = std::to_chars(buffer, buffer + sizeof(buffer), value);
	if (result.ec != std::errc{}) {
		throw std::runtime_error("echo: integer formatting failed");
	}
	scpp_echo_bytes(buffer, static_cast<std::size_t>(result.ptr - buffer));
}

CPP;
	}
}
