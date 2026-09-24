<?php

/*
 * Role: scan source bytes into token spans.
 * Call map: Compiler::tokenize -> Tokenizer::init -> Tokenizer::tokenize -> token_end.
 */
namespace scpp\compiler;

final class Tokenizer
{
	private const LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
	private const DIGITS = '0123456789';

	private file $source;
	private string $content;

	public function init(file $file): void
	{
		$this->source = $file;
	}

	/** Scan the source into token spans, skipping whitespace without changing offsets. */
	public function tokenize(): token_list
	{
		$this->content = $this->source->content;
		$result = new token_list();
		$result->file = $this->source;
		$result->content = $this->content;
		$length = strlen($this->content);
		$offset = 0;

		while ($offset < $length)
		{
			$offset += strspn($this->content, " \t\r\n", $offset);
			if ($offset === $length) {
				break;
			}

			$start = $offset;
			$offset = $this->token_end($start);
			$token = new token();
			$token->offset = $start;
			$token->length = $offset - $start;
			$token->text = substr($this->content, $start, $token->length);
			$result->tokens[] = $token;
		}
		return $result;
	}

	/** Recognize names, variables, decimal integers and assignment punctuation. */
	private function token_end(int $start): int
	{
		$offset = $start;
		$byte = $this->content[$offset];
		if ($byte === '$') {
			$offset++;
			if (($offset === strlen($this->content)) || !str_contains(self::LETTERS, $this->content[$offset])) {
				throw new \RuntimeException("Expected variable name at {$this->source->path}: byte $start");
			}
			$byte = $this->content[$offset];
		}

		if (str_contains(self::LETTERS, $byte)) {
			return $offset + strspn($this->content, self::LETTERS . self::DIGITS, $offset);
		}
		if (str_contains(self::DIGITS, $byte)) {
			$offset += strspn($this->content, self::DIGITS, $offset);
			if (($offset < strlen($this->content)) && str_contains(self::LETTERS . '.', $this->content[$offset])) {
				throw new \RuntimeException("Unsupported numeric literal at {$this->source->path}: byte $start");
			}
			return $offset;
		}
		if (substr($this->content, $offset, 2) === '->') {
			return $offset + 2;
		}
		if (str_contains(';(){}:,&[]<>', $byte)) {
			return $offset + 1;
		}
		if ($byte === '=') {
			$next = $this->content[$offset + 1] ?? '';
			if (($next !== '=') && ($next !== '>')) {
				return $offset + 1;
			}
		}

		throw new \RuntimeException("Unsupported token at {$this->source->path}: byte $start");
	}
}
