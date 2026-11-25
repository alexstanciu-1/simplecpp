<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Bootstrap equivalent of extracting regex_find_span's result<int>. Errors throw before writing the exclusive end. */
function regex_find_span(string $pattern, string $subject, int $offset /** int64 */, int &$end /** int64 */): int /** int64 */
{
	if ($offset < 0 || $offset > strlen($subject)) {
		throw new \RuntimeException('regex_find_span: offset outside subject');
	}
	$status = @preg_match($pattern, $subject, $matches, \PREG_OFFSET_CAPTURE, $offset);
	if ($status === false) {
		throw new \RuntimeException('regex_find_span: ' . preg_last_error_msg());
	}
	if ($status === 0) {
		return -1;
	}
	[$text, $start] = $matches[0];
	$end = $start + strlen($text);
	return $start;
}
