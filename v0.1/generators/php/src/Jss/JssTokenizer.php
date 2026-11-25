<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

use Scpp\S2S\Support\InputException;

final class JssTokenizer
{
	/** @return list<JssToken> */
	public function tokenize(string $source): array
	{
		$tokens = [];
		$length = strlen($source);
		$offset = 0;
		$line = 1;
		$column = 1;

		while ($offset < $length) {
			$char = $source[$offset];
			if ($char === " " || $char === "\t" || $char === "\r") {
				$this->advance($char, $offset, $line, $column);
				continue;
			}
			if ($char === "\n") {
				$this->advance($char, $offset, $line, $column);
				continue;
			}
			if ($char === '/' && ($source[$offset + 1] ?? '') === '/') {
				while ($offset < $length && $source[$offset] !== "\n") {
					$this->advance($source[$offset], $offset, $line, $column);
				}
				continue;
			}
			if ($char === '/' && ($source[$offset + 1] ?? '') === '*') {
				$startLine = $line;
				$startColumn = $column;
				$this->advance('/', $offset, $line, $column);
				$this->advance('*', $offset, $line, $column);
				while ($offset < $length && !($source[$offset] === '*' && ($source[$offset + 1] ?? '') === '/')) {
					$this->advance($source[$offset], $offset, $line, $column);
				}
				if ($offset >= $length) {
					throw new InputException('Unterminated JSS block comment at ' . $startLine . ':' . $startColumn . '.');
				}
				$this->advance('*', $offset, $line, $column);
				$this->advance('/', $offset, $line, $column);
				continue;
			}
			if ($char === '"' || $char === "'") {
				$tokens[] = $this->readString($source, $offset, $line, $column);
				continue;
			}
			if ($char === '`') {
				$tokens[] = $this->readTemplate($source, $offset, $line, $column);
				continue;
			}
			if (ctype_digit($char)) {
				$tokens[] = $this->readNumber($source, $offset, $line, $column);
				continue;
			}
			if ($char === '=' && ($source[$offset + 1] ?? '') === '=' && ($source[$offset + 2] ?? '') === '=') {
				$tokens[] = new JssToken('===', '===', $offset, $line, $column);
				$this->advance('=', $offset, $line, $column);
				$this->advance('=', $offset, $line, $column);
				$this->advance('=', $offset, $line, $column);
				continue;
			}
			if ($char === '!' && ($source[$offset + 1] ?? '') === '=' && ($source[$offset + 2] ?? '') === '=') {
				$tokens[] = new JssToken('!==', '!==', $offset, $line, $column);
				$this->advance('!', $offset, $line, $column);
				$this->advance('=', $offset, $line, $column);
				$this->advance('=', $offset, $line, $column);
				continue;
			}
			$threeChar = substr($source, $offset, 3);
			if ($threeChar === '===' || $threeChar === '!==') {
				$tokens[] = new JssToken($threeChar, $threeChar, $offset, $line, $column);
				$this->advance($source[$offset], $offset, $line, $column);
				$this->advance($source[$offset], $offset, $line, $column);
				$this->advance($source[$offset], $offset, $line, $column);
				continue;
			}
			$twoChar = substr($source, $offset, 2);
			if (in_array($twoChar, ['++', '--', '&&', '||', '??', '?.', '<=', '>=', '+=', '-=', '*=', '/=', '%=', '=>', '==', '!=', '::'], true)) {
				$tokens[] = new JssToken($twoChar, $twoChar, $offset, $line, $column);
				$this->advance($source[$offset], $offset, $line, $column);
				$this->advance($source[$offset], $offset, $line, $column);
				continue;
			}
			if (substr($source, $offset, 3) === '...') {
				$tokens[] = new JssToken('...', '...', $offset, $line, $column);
				$this->advance('.', $offset, $line, $column);
				$this->advance('.', $offset, $line, $column);
				$this->advance('.', $offset, $line, $column);
				continue;
			}
			if (ctype_alpha($char) || $char === '_') {
				$tokens[] = $this->readIdentifier($source, $offset, $line, $column);
				continue;
			}
			if (str_contains('(){}[];:,.=+-*/<>!%?|&', $char)) {
				$tokens[] = new JssToken($char, $char, $offset, $line, $column);
				$this->advance($char, $offset, $line, $column);
				continue;
			}

			throw new InputException('Unsupported JSS character `' . $char . '` at ' . $line . ':' . $column . '.');
		}

		$tokens[] = new JssToken('eof', '', $offset, $line, $column);
		return $tokens;
	}

	private function readString(string $source, int &$offset, int &$line, int &$column): JssToken
	{
		$quote = $source[$offset];
		$start = $offset;
		$startLine = $line;
		$startColumn = $column;
		$text = '';
		$this->advance($quote, $offset, $line, $column);
		while ($offset < strlen($source)) {
			$char = $source[$offset];
			if ($char === $quote) {
				$this->advance($char, $offset, $line, $column);
				return new JssToken('string', $quote . $text . $quote, $start, $startLine, $startColumn);
			}
			if ($char === '\\') {
				$text .= $char;
				$this->advance($char, $offset, $line, $column);
				if ($offset >= strlen($source)) {
					break;
				}
				$char = $source[$offset];
			}
			$text .= $char;
			$this->advance($char, $offset, $line, $column);
		}
		throw new InputException('Unterminated JSS string at ' . $startLine . ':' . $startColumn . '.');
	}

	private function readTemplate(string $source, int &$offset, int &$line, int &$column): JssToken
	{
		$start = $offset;
		$startLine = $line;
		$startColumn = $column;
		$text = '';
		$this->advance('`', $offset, $line, $column);
		while ($offset < strlen($source)) {
			$char = $source[$offset];
			if ($char === '`') {
				$this->advance($char, $offset, $line, $column);
				return new JssToken('template', $text, $start, $startLine, $startColumn);
			}
			if ($char === '\\') {
				$text .= $char;
				$this->advance($char, $offset, $line, $column);
				if ($offset >= strlen($source)) {
					break;
				}
				$char = $source[$offset];
			}
			$text .= $char;
			$this->advance($char, $offset, $line, $column);
		}
		throw new InputException('Unterminated JSS template literal at ' . $startLine . ':' . $startColumn . '.');
	}

	private function readNumber(string $source, int &$offset, int &$line, int &$column): JssToken
	{
		$start = $offset;
		$startLine = $line;
		$startColumn = $column;
		while ($offset < strlen($source) && ctype_digit($source[$offset])) {
			$this->advance($source[$offset], $offset, $line, $column);
		}
		if (($source[$offset] ?? '') === '.' && ctype_digit($source[$offset + 1] ?? '')) {
			$this->advance('.', $offset, $line, $column);
			while ($offset < strlen($source) && ctype_digit($source[$offset])) {
				$this->advance($source[$offset], $offset, $line, $column);
			}
		}
		return new JssToken('number', substr($source, $start, $offset - $start), $start, $startLine, $startColumn);
	}

	private function readIdentifier(string $source, int &$offset, int &$line, int &$column): JssToken
	{
		$start = $offset;
		$startLine = $line;
		$startColumn = $column;
		while ($offset < strlen($source) && (ctype_alnum($source[$offset]) || $source[$offset] === '_')) {
			$this->advance($source[$offset], $offset, $line, $column);
		}
		$text = substr($source, $start, $offset - $start);
		$kind = in_array($text, ['let', 'const', 'function', 'async', 'await', 'return', 'break', 'continue', 'class', 'struct', 'union', 'extends', 'constructor', 'new', 'namespace', 'use', 'as', 'static', 'if', 'else', 'while', 'do', 'for', 'of', 'switch', 'case', 'default', 'true', 'false', 'null', 'void', 'this', 'delete', 'import', 'export'], true) ? $text : 'identifier';
		return new JssToken($kind, $text, $start, $startLine, $startColumn);
	}

	private function advance(string $char, int &$offset, int &$line, int &$column): void
	{
		$offset++;
		if ($char === "\n") {
			$line++;
			$column = 1;
			return;
		}
		$column++;
	}
}
