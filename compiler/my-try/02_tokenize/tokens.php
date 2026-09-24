<?php

/*
 * Role: scan source bytes into token spans.
 * Call map: Compiler::tokenize -> Tokenizer::__construct -> Tokenizer::tokenize -> token_end.
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

	public function init(file $file): void
	{
		$this->source = $file;
	}

	/** Scan the source into token spans, skipping whitespace without changing offsets. */
	public function tokenize(): token_list
	{
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
				if (($byte !== 32) && ($byte !== 9) && ($byte !== 13) && ($byte !== 10)) { break; }
				$offset++;
			}
			if ($offset === $length) {
				break;
			}

			$start = $offset;
			$offset = $this->token_end($start);
			$span = new token();
			$span->offset = $start;
			$span->length = $offset - $start;
			$span->text = string_byte_slice($this->content, $start, $span->length);
			$tokens[] = $span;
		}
		return $result;
	}

	private static function letter(int $byte): bool
	{
		return ($byte === 95) || (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123));
	}

	private static function digit(int $byte): bool
	{
		return ($byte >= 48) && ($byte < 58);
	}

	/** Recognize names, variables, decimal integers and assignment punctuation. */
	private function token_end(int $start): int
	{
		$offset = $start;
		$byte = string_byte_at($this->content, $offset);
		if ($byte === 36) {
			$offset++;
			$byte = string_byte_at($this->content, $offset);
			if (!self::letter($byte)) {
				throw new \RuntimeException('Expected variable name at ' . $this->source->path . ': byte ' . $start);
			}
		}
		if (self::letter($byte)) {
			$offset++;
			$next = string_byte_at($this->content, $offset);
			while (self::letter($next) || self::digit($next)) {
				$offset++;
				$next = string_byte_at($this->content, $offset);
			}
			return $offset;
		}
		if (self::digit($byte)) {
			$offset++;
			$next = string_byte_at($this->content, $offset);
			while (self::digit($next)) {
				$offset++;
				$next = string_byte_at($this->content, $offset);
			}
			if (self::letter($next) || ($next === 46)) {
				throw new \RuntimeException('Unsupported numeric literal at ' . $this->source->path . ': byte ' . $start);
			}
			return $offset;
		}
		if (string_byte_slice($this->content, $offset, 2) === '->') { return $offset + 2; }
		$punctuation = ';(){}:,&[]<>';
		for ($index = 0; $index < string_byte_len($punctuation); $index++) {
			if ($byte === string_byte_at($punctuation, $index)) { return $offset + 1; }
		}
		if ($byte === 61) {
			$next = string_byte_at($this->content, $offset + 1);
			if (($next !== 61) && ($next !== 62)) { return $offset + 1; }
		}
		throw new \RuntimeException('Unsupported token at ' . $this->source->path . ': byte ' . $start);
	}
}
