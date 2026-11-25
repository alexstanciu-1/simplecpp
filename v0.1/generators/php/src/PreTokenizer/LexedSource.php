<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

use Scpp\S2S\Support\PhpParserCompatibility;

/**
 * Wraps token_get_all output with stable offsets into the original source text.
 */
final class LexedSource
{
	/** @var list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> */
	public readonly array $tokens;

	public function __construct(
		public readonly string $source,
	) {
		$rawTokens = PhpParserCompatibility::tokenizeSource($source);
		$tokens = [];
		$offset = 0;

		foreach ($rawTokens as $index => $token) {
			$text = is_array($token) ? (string) ($token[1] ?? '') : (string) $token;
			$tokens[] = [
				'index' => $index,
				'text' => $text,
				'line' => is_array($token) ? (int) ($token[2] ?? 0) : 0,
				'offset' => $offset,
				'is_array' => is_array($token),
				'id' => is_array($token) ? (int) ($token[0] ?? 0) : null,
			];
			$offset += strlen($text);
		}

		$this->tokens = $tokens;
	}
}
