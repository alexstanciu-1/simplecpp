<?php
namespace scpp\compiler;

/** Shared wire contract, not a build executor or semantic-stage worker. */
final class Runtime_Build_Contract
{
	private static function identifier(string $text): bool
	{
		if ($text === '') { return false; }
		for ($index = 0; $index < string_byte_len($text); $index++) {
			$byte = string_byte_at($text, $index);
			if (($byte === 95) || (($byte >= 65) && ($byte <= 90)) || (($byte >= 97) && ($byte <= 122))) { continue; }
			if (($index > 0) && ($byte >= 48) && ($byte <= 57)) { continue; }
			return false;
		}
		return true;
	}

	public static function definition_name(string $text): string
	{
		$parts /** vector<string> */ = self::split_byte($text, 61);
		if (q_count($parts) !== 2) { throw new \RuntimeException('Runtime build definition requires NAME=VALUE'); }
		if (!self::identifier($parts[0]) || ($parts[1] === '')) {
			throw new \RuntimeException('Runtime build definition requires NAME=VALUE');
		}
		if ($parts[0] === 'SCPP_LANGUAGE_TARGET_PHP') { throw new \RuntimeException('Runtime module cannot override the language target'); }
		for ($index = 0; $index < string_byte_len($parts[1]); $index++) {
			$byte = string_byte_at($parts[1], $index);
			if ((($byte >= 48) && ($byte <= 57)) || (($byte >= 65) && ($byte <= 90))
				|| (($byte >= 97) && ($byte <= 122)) || ($byte === 95) || ($byte === 45) || ($byte === 46)) { continue; }
			throw new \RuntimeException('Unsupported runtime build definition value');
		}
		return $parts[0];
	}

	public static function validate(runtime_cpp_build_spec $spec): void
	{
		foreach ($spec->sources as $source) {
			if (!string_byte_ends_with($source, '.cpp')) { throw new \RuntimeException('Runtime provider source requires .cpp'); }
			foreach (self::split_byte($source, 47) as $component) {
				if (($component === '') || ($component === '.') || ($component === '..')) { throw new \RuntimeException('Invalid relative runtime provider path'); }
				self::token($component);
			}
		}
		foreach ($spec->libraries as $library) { self::token($library); }
		$definitions /** hash<string> */ = [];
		foreach ($spec->definitions as $definition) {
			$name = self::definition_name($definition);
			if (isset($definitions[$name])) {
				if ($definitions[$name] !== $definition) { throw new \RuntimeException('Conflicting runtime build definition: ' . $name); }
			}
			$definitions[$name] = $definition;
		}
	}

	private static function token(string $text): void
	{
		if (($text === '') || string_byte_starts_with($text, '-')) { throw new \RuntimeException('Invalid runtime build token'); }
		for ($index = 0; $index < string_byte_len($text); $index++) {
			$byte = string_byte_at($text, $index);
			if ((($byte >= 48) && ($byte <= 57)) || (($byte >= 65) && ($byte <= 90))
				|| (($byte >= 97) && ($byte <= 122)) || ($byte === 95) || ($byte === 45) || ($byte === 46) || ($byte === 43)) { continue; }
			throw new \RuntimeException('Invalid runtime build token');
		}
	}

	/** Preserve empty components so validation rejects malformed wire spellings. */
	private static function split_byte(string $text, int $separator): array /** vector<string> */
	{
		$result /** vector<string> */ = [];
		$start = 0;
		$length = string_byte_len($text);
		for ($index = 0; $index < $length; $index++) {
			if (string_byte_at($text, $index) === $separator) {
				$result[] = string_byte_slice($text, $start, $index - $start);
				$start = $index + 1;
			}
		}
		$result[] = string_byte_slice($text, $start, $length - $start);
		return $result;
	}

	public static function encode(runtime_cpp_build_spec $spec): string
	{
		self::validate($spec);
		return '{"sources":' . self::strings($spec->sources) . ',"libraries":' . self::strings($spec->libraries)
			. ',"definitions":' . self::strings($spec->definitions) . '}';
	}

	private static function strings(array $values /** vector<string> */): string
	{
		$text = '[';
		$separator = '';
		foreach ($values as $value) { $text .= $separator . json_quote($value); $separator = ','; }
		return $text . ']';
	}
}
