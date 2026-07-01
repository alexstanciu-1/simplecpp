<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

/**
 * Rewrites Prism++ fixed-width enum backing types to PHP-AST enum carriers.
 */
final class EnumBackingSyntaxRewriter
{
	/** @var array<string,bool> */
	private const FIXED_BACKING_TYPES = [
		'int8' => true,
		'int16' => true,
		'int32' => true,
		'int64' => true,
		'uint8' => true,
		'byte' => true,
		'uint16' => true,
		'uint32' => true,
		'uint64' => true,
	];

	public function rewrite(string $source): string
	{
		$lexed = new LexedSource($source);
		$tokens = $lexed->tokens;
		$edits = [];
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++) {
			if (!$this->isEnumKeyword($tokens[$i])) {
				continue;
			}
			$nameIndex = $this->findNextMeaningfulIndex($tokens, $i + 1, null);
			if ($nameIndex === null || !$this->isNameLikeToken($tokens[$nameIndex])) {
				continue;
			}
			$colonIndex = $this->findNextMeaningfulIndex($tokens, $nameIndex + 1, null);
			if ($colonIndex === null || $tokens[$colonIndex]['text'] !== ':') {
				continue;
			}
			$typeIndex = $this->findNextMeaningfulIndex($tokens, $colonIndex + 1, null);
			if ($typeIndex === null || !$this->isNameLikeToken($tokens[$typeIndex])) {
				continue;
			}
			$type = strtolower($tokens[$typeIndex]['text']);
			if (!isset(self::FIXED_BACKING_TYPES[$type])) {
				continue;
			}

			$edits[] = [
				'start' => $tokens[$i]['offset'],
				'end' => $tokens[$i]['offset'] + strlen($tokens[$i]['text']),
				'replacement' => '/** @scpp-enum-backing ' . $type . ' */ enum',
			];
			$edits[] = [
				'start' => $tokens[$typeIndex]['offset'],
				'end' => $tokens[$typeIndex]['offset'] + strlen($tokens[$typeIndex]['text']),
				'replacement' => 'int',
			];
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

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findNextMeaningfulIndex(array $tokens, int $start, ?int $endExclusive): ?int
	{
		$end = $endExclusive ?? count($tokens);
		for ($i = $start; $i < $end; $i++) {
			if (!in_array($tokens[$i]['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				return $i;
			}
		}
		return null;
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isEnumKeyword(array $token): bool
	{
		return strtolower($token['text']) === 'enum' && $this->isNameLikeToken($token);
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isNameLikeToken(array $token): bool
	{
		return in_array($token['id'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true);
	}
}
