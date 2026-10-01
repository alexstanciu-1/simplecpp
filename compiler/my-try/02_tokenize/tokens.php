<?php

/*
 * Role: scan source bytes into token spans.
 * Call map: Source_Frontend::run -> Tokenizer::tokenize -> File_Loader / token_end.
 */
namespace scpp\compiler;

final class Tokenizer
{
	private file $source;
	private string $content = '';

	public function __construct(file $file)
	{
		$this->source = $file;
	}

	/** Scan the source into token spans, skipping whitespace without changing offsets. */
	public function tokenize(?string $full_path = null): token_list
	{
		// Disk reads belong to this file's worker; in-memory callers supply their own bytes.
		if ($this->source->disk_source) {
			if ($full_path === null) {
				throw new \LogicException('Disk tokenization requires a full IO path');
			}
			$path /** string */ = $full_path;
			File_Loader::init($this->source, $path);
		}
		// Retain the exact source snapshot used by every token span.
		$this->content = $this->source->content;
		$result = new token_list();
		$tokens /** Storage<token> */ = $result->tokens;
		$result->file = $this->source;
		$result->content = $this->content;

		$length = string_byte_len($this->content);
		$offset = 0;

		while ($offset < $length)
		{
			while ($offset < $length) {
				$byte = string_byte_at($this->content, $offset);
				if (($byte !== 32) && ($byte !== 9) && ($byte !== 13) && ($byte !== 10)) {
					break;
				}
				$offset++;
			}
			if ($offset === $length) {
				break;
			}

			$start = $offset;
			$offset = $this->token_end($start);
			$span_length = $offset - $start;
			$span = new token($start, $span_length, string_byte_slice($this->content, $start, $span_length));
			$tokens[] = $span;
		}

		$result->end_token = q_count($tokens);
		return $result;
	}

	/** Recognize names, variables, decimal numbers and supported punctuation. */
	private function token_end(int $start): int
	{
		$offset = $start;
		$byte = string_byte_at($this->content, $offset);
		if ($byte === 36) {
			$offset++;
			$byte = string_byte_at($this->content, $offset);
			if (!Source_Text::letter($byte)) {
				throw new \RuntimeException('Expected variable name at ' . $this->source->path . ': byte ' . $start);
			}
		}
		if (Source_Text::letter($byte))
		{
			$offset++;
			$next = string_byte_at($this->content, $offset);
			while (Source_Text::letter($next) || Source_Text::digit($next)) {
				$offset++;
				$next = string_byte_at($this->content, $offset);
			}
			return $offset;
		}
		if (($byte === 39) || ($byte === 34)) {
			return $this->quoted_end($start, $byte);
		}
		if (Source_Text::digit($byte) || ($byte === 46)) {
			return $this->numeric_end($start);
		}
		if (string_byte_slice($this->content, $offset, 2) === '->') {
			return $offset + 2;
		}
		$punctuation = ';(){}:,&[]<>+-';
		for ($index = 0; $index < string_byte_len($punctuation); $index++) {
			if ($byte === string_byte_at($punctuation, $index)) {
				return $offset + 1;
			}
		}
		if ($byte === 61) {
			$next = string_byte_at($this->content, $offset + 1);
			if (($next !== 61) && ($next !== 62)) {
				return $offset + 1;
			}
		}
		throw new \RuntimeException('Unsupported token at ' . $this->source->path . ': byte ' . $start);
	}

	/** Scan one complete quoted token; semantic escape decoding belongs to preparation. */
	private function quoted_end(int $start, int $quote): int
	{
		$length = string_byte_len($this->content);
		$offset = $start + 1;
		while ($offset < $length)
		{
			$byte = string_byte_at($this->content, $offset);
			if ($byte === $quote) {
				return $offset + 1;
			}
			if ($byte === 92) {
				$offset++;
				if ($offset === $length) {
					break;
				}
			}
			$offset++;
		}
		throw new \RuntimeException('Unterminated quoted string at ' . $this->source->path . ': byte ' . $start);
	}

	/** Scan decimal mantissa and exponent as one token, preserving every source byte. */
	private function numeric_end(int $start): int
	{
		$offset = $start;
		while (Source_Text::digit(string_byte_at($this->content, $offset))) {
			$offset++;
		}

		$digits = $offset - $start;
		if (string_byte_at($this->content, $offset) === 46)
		{
			$offset++;
			$fraction = $offset;
			while (Source_Text::digit(string_byte_at($this->content, $offset))) {
				$offset++;
			}
			$digits = $digits + ($offset - $fraction);
		}
		if ($digits === 0) {
			throw new \RuntimeException('Expected decimal digits at ' . $this->source->path . ': byte ' . $start);
		}

		// Exponent signs belong to this token; leading unary signs do not.
		$next = string_byte_at($this->content, $offset);
		if (($next === 69) || ($next === 101))
		{
			$offset++;
			$sign = string_byte_at($this->content, $offset);
			if (($sign === 43) || ($sign === 45)) {
				$offset++;
			}
			$exponent = $offset;
			while (Source_Text::digit(string_byte_at($this->content, $offset))) {
				$offset++;
			}
			if ($offset === $exponent) {
				throw new \RuntimeException('Expected exponent digits at ' . $this->source->path . ': byte ' . $start);
			}
		}

		$next = string_byte_at($this->content, $offset);
		if (Source_Text::letter($next) || ($next === 46)) {
			throw new \RuntimeException('Unsupported numeric literal at ' . $this->source->path . ': byte ' . $start);
		}
		return $offset;
	}
}
