<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Spells exact C++ literal bytes for string construction and direct output. */
final class Cpp_String_Literals
{
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
