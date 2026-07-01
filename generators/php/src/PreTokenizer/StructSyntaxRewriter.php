<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

/**
 * Rewrites Prism++ struct declarations into PHP-AST-compatible class carriers.
 */
final class StructSyntaxRewriter
{
	public function rewrite(string $source): string
	{
		$lexed = new LexedSource($source);
		$tokens = $lexed->tokens;
		$edits = [];
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++) {
			if (!$this->isStructKeyword($tokens[$i])) {
				continue;
			}

			$nameIndex = $this->findNextMeaningfulIndex($tokens, $i + 1, null);
			if ($nameIndex === null || !$this->isNameLikeToken($tokens[$nameIndex])) {
				continue;
			}

			$openBraceIndex = $this->findNextMeaningfulIndex($tokens, $nameIndex + 1, null);
			if ($openBraceIndex === null || $tokens[$openBraceIndex]['text'] !== '{') {
				continue;
			}

			$closeBraceIndex = $this->findMatchingCloser($tokens, $openBraceIndex);
			if ($closeBraceIndex === null) {
				continue;
			}

			$edits[] = [
				'start' => $tokens[$i]['offset'],
				'end' => $tokens[$i]['offset'] + strlen($tokens[$i]['text']),
				'replacement' => '/** @scpp-struct */ class',
			];

			foreach ($this->collectPublicFieldInsertions($tokens, $openBraceIndex, $closeBraceIndex) as $offset) {
				$edits[] = [
					'start' => $offset,
					'end' => $offset,
					'replacement' => 'public ',
				];
			}

			$i = $closeBraceIndex;
		}

		if ($edits === []) {
			return $source;
		}

		usort($edits, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);
		$out = '';
		$cursor = 0;
		foreach ($edits as $edit) {
			$out .= substr($source, $cursor, $edit['start'] - $cursor);
			$out .= $edit['replacement'];
			$cursor = $edit['end'];
		}
		$out .= substr($source, $cursor);
		return $out;
	}

	/**
	 * @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens
	 * @return list<int>
	 */
	private function collectPublicFieldInsertions(array $tokens, int $openBraceIndex, int $closeBraceIndex): array
	{
		$insertions = [];
		$depth = 1;
		for ($i = $openBraceIndex + 1; $i < $closeBraceIndex; $i++) {
			$text = $tokens[$i]['text'];
			if ($text === '{') {
				$depth++;
				continue;
			}
			if ($text === '}') {
				$depth--;
				continue;
			}
			if ($depth !== 1 || !$this->isVariableToken($tokens[$i])) {
				continue;
			}

			$typeEndIndex = $this->findPreviousMeaningfulIndex($tokens, $i - 1, $openBraceIndex + 1);
			if ($typeEndIndex === null || !$this->canEndType($tokens[$typeEndIndex])) {
				continue;
			}

			$typeStartIndex = $this->findTypeStartIndex($tokens, $typeEndIndex, $openBraceIndex + 1);
			$beforeTypeIndex = $this->findPreviousMeaningfulIndex($tokens, $typeStartIndex - 1, $openBraceIndex + 1);
			if ($beforeTypeIndex !== null && $this->isPropertyModifier($tokens[$beforeTypeIndex]['text'])) {
				continue;
			}
			if ($beforeTypeIndex !== null && !in_array($tokens[$beforeTypeIndex]['text'], ['{', ';'], true)) {
				continue;
			}

			$insertions[] = $tokens[$typeStartIndex]['offset'];
		}
		return $insertions;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findTypeStartIndex(array $tokens, int $typeEndIndex, int $lowerBound): int
	{
		$start = $typeEndIndex;
		for ($i = $typeEndIndex - 1; $i >= $lowerBound; $i--) {
			if (!$this->isMeaningful($tokens[$i])) {
				continue;
			}
			if (in_array($tokens[$i]['text'], ['?', '\\'], true) || $this->isNameLikeToken($tokens[$i])) {
				$start = $i;
				continue;
			}
			break;
		}
		return $start;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findNextMeaningfulIndex(array $tokens, int $start, ?int $endExclusive): ?int
	{
		$end = $endExclusive ?? count($tokens);
		for ($i = $start; $i < $end; $i++) {
			if ($this->isMeaningful($tokens[$i])) {
				return $i;
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findPreviousMeaningfulIndex(array $tokens, int $start, int $lowerBound): ?int
	{
		for ($i = $start; $i >= $lowerBound; $i--) {
			if ($this->isMeaningful($tokens[$i])) {
				return $i;
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findMatchingCloser(array $tokens, int $openIndex): ?int
	{
		$depth = 0;
		$count = count($tokens);
		for ($i = $openIndex; $i < $count; $i++) {
			if ($tokens[$i]['text'] === '{') {
				$depth++;
				continue;
			}
			if ($tokens[$i]['text'] === '}') {
				$depth--;
				if ($depth === 0) {
					return $i;
				}
			}
		}
		return null;
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isStructKeyword(array $token): bool
	{
		return strtolower($token['text']) === 'struct' && $this->isNameLikeToken($token);
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isVariableToken(array $token): bool
	{
		return $token['id'] === T_VARIABLE;
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isNameLikeToken(array $token): bool
	{
		return in_array($token['id'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true);
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function canEndType(array $token): bool
	{
		return $this->isNameLikeToken($token);
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isMeaningful(array $token): bool
	{
		return !in_array($token['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
	}

	private function isPropertyModifier(string $text): bool
	{
		return in_array(strtolower($text), ['public', 'protected', 'private', 'static', 'readonly', 'var'], true);
	}
}
