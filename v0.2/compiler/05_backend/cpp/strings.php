<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Spells exact C++ literal bytes for string construction and direct output. */
final class Cpp_String_Literals
{
	/**
	 * Compare a stable byte-string place against constant bytes, without constructing a string.
	 * @param array<string,bool> $headers
	 */
	public static function equal_bytes(string $subject, string $bytes, array &$headers): string
	{
		$length = strlen($bytes);
		$size_test = $subject . '.size() == ' . $length;
		if ($length === 0) return $size_test;
		$headers['cstring'] = true;
		return $size_test . ' && std::memcmp(' . $subject . '.data(), ' . self::bytes($bytes) . ', ' . $length . ') == 0';
	}

	/** Keep printable ASCII readable and bound numeric escapes to a single byte. */
	public static function bytes(string $bytes): string
	{
		$literal = '"';
		$length = strlen($bytes);
		for ($index = 0; $index < $length; ++$index) {
			$byte = $bytes[$index];
			$code = ord($byte);
			if ($code === 0) {
				$next = ord($bytes[$index + 1] ?? "\0");
				$literal .= ($next >= 48 && $next <= 55) ? '\000' : '\0';
			}
			elseif ($byte === '?' && $index > 0 && $bytes[$index - 1] === '?' && $index + 1 < $length
				&& str_contains("=/'()!<>-", $bytes[$index + 1])) {
				// Clang diagnoses trigraphs such as ??/ even in C++23.
				$literal .= '\?';
			}
			else {
				$literal .= match ($byte) {
					'"' => '\"', '\\' => '\\\\', "\n" => '\n', "\r" => '\r', "\t" => '\t',
					default => ($code >= 32 && $code <= 126) ? $byte : sprintf('\\%03o', $code),
				};
			}
		}
		return $literal . '"';
	}

}
