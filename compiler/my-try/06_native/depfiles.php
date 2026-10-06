<?php
namespace scpp\compiler;

/** Read the first Make dependency rule emitted by Clang/GNU ld, not arbitrary Make code. */
final class Native_Depfiles
{
	public static function paths(string $text, string $directory): array /** vector<string> */
	{
		$result /** vector<string> */ = [];
		$seen /** hash<bool> */ = [];
		$word = '';
		$dependencies = false;
		for ($index = 0; $index < string_byte_len($text); $index++)
		{
			$byte = string_byte_at($text, $index);
			if ($byte === 92) {
				$index++;
				if ($index >= string_byte_len($text)) { throw new \RuntimeException('Incomplete dependency escape'); }
				$escaped = string_byte_at($text, $index);
				if ($escaped === 10) { continue; }
				$word .= string_byte_from_int($escaped);
				continue;
			}
			if ($byte === 36) {
				$index++;
				if ($index >= string_byte_len($text)) { throw new \RuntimeException('Incomplete dependency dollar'); }
				if (string_byte_at($text, $index) !== 36) { throw new \RuntimeException('Make expansion in dependency input'); }
				$word .= '$';
				continue;
			}
			if (!$dependencies) {
				if ($byte === 58) { $dependencies = true; $word = ''; }
				continue;
			}
			if (($byte === 32) || ($byte === 9) || ($byte === 10) || ($byte === 13)) {
				if ($word !== '') {
					$path = $word;
					if (!string_byte_starts_with($path, '/')) { $path = $directory . '/' . $path; }
					if (!isset($seen[$path])) { $result[] = $path; $seen[$path] = true; }
					$word = '';
				}
				if ($byte === 10) { break; }
			}
			else { $word .= string_byte_from_int($byte); }
		}
		if (!$dependencies) { throw new \RuntimeException('Missing dependency rule'); }
		if ($word !== '') {
			$path = $word;
			if (!string_byte_starts_with($path, '/')) { $path = $directory . '/' . $path; }
			if (!isset($seen[$path])) { $result[] = $path; }
		}
		return $result;
	}
}
