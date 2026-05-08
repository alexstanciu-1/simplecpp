<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

/**
 * Finds a narrow set of Prism++ typed declaration sites by walking the token stream.
 */
final class TokenSiteScanner
{
	/**
	 * @return list<array{
	 *   kind:string,
	 *   name:?string,
	 *   type:string,
	 *   line:int,
	 *   startOffset:int,
	 *   endOffset:int,
	 *   rewriteStart:int,
	 *   rewriteEnd:int,
	 *   replacement:string,
	 *   ownerName?:?string
	 * }>
	 */
	public function scan(LexedSource $source): array
	{
		$sites = [];
		$tokens = $source->tokens;
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];

			if ($this->isFunctionKeyword($token)) {
				foreach ($this->scanFunctionLike($source, $i) as $site) {
					$sites[] = $site;
				}
				continue;
			}

			if ($this->isFnKeyword($token)) {
				foreach ($this->scanArrowFunction($source, $i) as $site) {
					$sites[] = $site;
				}
				continue;
			}

			if ($this->isVariableToken($token)) {
				$site = $this->scanVariableAssignmentSite($source, $i);
				if ($site !== null) {
					$sites[] = $site;
				}
			}
		}

		usort($sites, static fn (array $a, array $b): int => $a['rewriteStart'] <=> $b['rewriteStart']);
		return $this->dedupeNestedSites($sites);
	}

	/** @return list<array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}> */
	private function scanFunctionLike(LexedSource $source, int $functionIndex): array
	{
		$tokens = $source->tokens;
		$openParenIndex = $this->findNextTokenTextIndex($tokens, $functionIndex + 1, '(');
		if ($openParenIndex === null) {
			return [];
		}

		$closeParenIndex = $this->findMatchingCloser($tokens, $openParenIndex, '(', ')');
		if ($closeParenIndex === null) {
			return [];
		}

		$ownerKind = $this->detectFunctionOwnerKind($tokens, $functionIndex);
		$ownerName = $this->readFunctionLikeName($tokens, $functionIndex, $openParenIndex);
		$sites = $this->scanParameterSites($source, $openParenIndex, $closeParenIndex);

		$returnSite = $this->scanColonReturnSite($source, $closeParenIndex + 1, $ownerKind, $ownerName);
		if ($returnSite !== null) {
			$sites[] = $returnSite;
		}

		return $sites;
	}

	/** @return list<array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}> */
	private function scanArrowFunction(LexedSource $source, int $fnIndex): array
	{
		$tokens = $source->tokens;
		$openParenIndex = $this->findNextTokenTextIndex($tokens, $fnIndex + 1, '(');
		if ($openParenIndex === null) {
			return [];
		}

		$closeParenIndex = $this->findMatchingCloser($tokens, $openParenIndex, '(', ')');
		if ($closeParenIndex === null) {
			return [];
		}

		$sites = $this->scanParameterSites($source, $openParenIndex, $closeParenIndex);

		$returnSite = $this->scanColonReturnSite($source, $closeParenIndex + 1, 'closure_return', null);
		if ($returnSite !== null) {
			$sites[] = $returnSite;
		}

		$trailingReturnSite = $this->scanArrowTrailingReturnSite($source, $closeParenIndex + 1);
		if ($trailingReturnSite !== null) {
			$sites[] = $trailingReturnSite;
		}

		return $sites;
	}

	/** @return list<array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}> */
	private function scanParameterSites(LexedSource $source, int $openParenIndex, int $closeParenIndex): array
	{
		$tokens = $source->tokens;
		$sites = [];

		for ($i = $openParenIndex + 1; $i < $closeParenIndex; $i++) {
			$token = $tokens[$i];
			if (!$this->isVariableToken($token)) {
				continue;
			}

			$nextIndex = $this->findNextMeaningfulIndex($tokens, $i + 1, $closeParenIndex);
			if ($nextIndex === null) {
				continue;
			}

			$typeSlot = $this->parseTypeSlot($source, $nextIndex, [',', ')']);
			if ($typeSlot === null) {
				continue;
			}

			$sites[] = [
				'kind' => 'param',
				'name' => ltrim($token['text'], '$'),
				'type' => $typeSlot['type'],
				'line' => $token['line'],
				'startOffset' => $token['offset'],
				'endOffset' => $typeSlot['slotEndOffset'],
				'rewriteStart' => $token['offset'],
				'rewriteEnd' => $typeSlot['slotEndOffset'],
				'replacement' => '/** ' . $typeSlot['type'] . ' */ ' . $token['text'] . $typeSlot['trailingTrivia'],
			];

			$i = $typeSlot['endTokenIndex'];
		}

		return $sites;
	}

	/** @return array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}|null */
	private function scanVariableAssignmentSite(LexedSource $source, int $variableIndex): ?array
	{
		$tokens = $source->tokens;
		$nextIndex = $this->findNextMeaningfulIndex($tokens, $variableIndex + 1, null);
		if ($nextIndex === null) {
			return null;
		}

		$typeSlot = $this->parseTypeSlot($source, $nextIndex, ['=']);
		if ($typeSlot === null) {
			return null;
		}

		$delimiterIndex = $this->findNextMeaningfulIndex($tokens, $typeSlot['endTokenIndex'] + 1, null);
		if ($delimiterIndex === null || $tokens[$delimiterIndex]['text'] !== '=') {
			return null;
		}

		$token = $tokens[$variableIndex];

		return [
			'kind' => $this->detectVariableKind($tokens, $variableIndex),
			'name' => ltrim($token['text'], '$'),
			'type' => $typeSlot['type'],
			'line' => $token['line'],
			'startOffset' => $typeSlot['slotStartOffset'],
			'endOffset' => $typeSlot['slotEndOffset'],
			'rewriteStart' => $typeSlot['slotStartOffset'],
			'rewriteEnd' => $typeSlot['slotEndOffset'],
			'replacement' => $typeSlot['leadingTrivia'] . '/** ' . $typeSlot['type'] . ' */' . $typeSlot['trailingTrivia'],
		];
	}

	/** @return array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}|null */
	private function scanColonReturnSite(LexedSource $source, int $startIndex, string $returnKind, ?string $ownerName): ?array
	{
		$tokens = $source->tokens;
		$colonIndex = $this->findNextMeaningfulIndex($tokens, $startIndex, null);
		if ($colonIndex === null || $tokens[$colonIndex]['text'] !== ':') {
			return null;
		}

		$typeStartIndex = $this->findNextMeaningfulIndex($tokens, $colonIndex + 1, null);
		if ($typeStartIndex === null) {
			return null;
		}

		$typeSlot = $this->parseTypeSlot($source, $typeStartIndex, ['{', '=>']);
		if ($typeSlot === null) {
			return null;
		}

		if (!$this->needsPhpCompatibleRewrite($typeSlot['type'])) {
			return null;
		}

		return [
			'kind' => $returnKind,
			'name' => null,
			'type' => $typeSlot['type'],
			'line' => $tokens[$typeStartIndex]['line'],
			'startOffset' => $tokens[$colonIndex]['offset'],
			'endOffset' => $typeSlot['slotEndOffset'],
			'rewriteStart' => $tokens[$colonIndex]['offset'],
			'rewriteEnd' => $typeSlot['slotEndOffset'],
			'replacement' => '',
			'ownerName' => $ownerName,
		];
	}

	/** @return array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}|null */
	private function scanArrowTrailingReturnSite(LexedSource $source, int $startIndex): ?array
	{
		$tokens = $source->tokens;
		$rawStartIndex = $startIndex;
		while ($rawStartIndex < count($tokens) && $this->isTrivia($tokens[$rawStartIndex])) {
			$rawStartIndex++;
		}

		$arrowIndex = $this->findTopLevelArrowIndex($tokens, $rawStartIndex);
		if ($arrowIndex === null || $rawStartIndex >= $arrowIndex) {
			return null;
		}

		$slotStartOffset = $tokens[$rawStartIndex]['offset'];
		$slotEndOffset = $tokens[$arrowIndex]['offset'];
		$raw = substr($source->source, $slotStartOffset, $slotEndOffset - $slotStartOffset);
		$type = trim($raw);
		if ($type === '') {
			return null;
		}

		if (!$this->looksLikePrismType($type)) {
			return null;
		}

		return [
			'kind' => 'closure_return',
			'name' => null,
			'type' => preg_replace('/\s+/', ' ', $type) ?? $type,
			'line' => $tokens[$rawStartIndex]['line'],
			'startOffset' => $slotStartOffset,
			'endOffset' => $slotEndOffset,
			'rewriteStart' => $slotStartOffset,
			'rewriteEnd' => $slotEndOffset,
			'replacement' => '',
			'ownerName' => null,
		];
	}

	/**
	 * @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens
	 * @return array{type:string,slotStartOffset:int,slotEndOffset:int,leadingTrivia:string,trailingTrivia:string,endTokenIndex:int}|null
	 */
	private function parseTypeSlot(LexedSource $source, int $startIndex, array $delimiters): ?array
	{
		$tokens = $source->tokens;
		$count = count($tokens);
		$i = $startIndex;
		$leadingTrivia = '';

		while ($i < $count && $this->isTrivia($tokens[$i])) {
			$leadingTrivia .= $tokens[$i]['text'];
			$i++;
		}
		if ($i >= $count) {
			return null;
		}

		$slotStartTokenIndex = $i;
		$slotStartOffset = $tokens[$startIndex]['offset'];
		$depthAngles = 0;
		$depthParens = 0;
		$lastTypeTokenIndex = null;

		for (; $i < $count; $i++) {
			$text = $tokens[$i]['text'];

			if ($this->isTrivia($tokens[$i])) {
				$nextIndex = $this->findNextMeaningfulIndex($tokens, $i + 1, null);
				if ($nextIndex === null) {
					break;
				}
				if ($depthAngles === 0 && $depthParens === 0 && in_array($tokens[$nextIndex]['text'], $delimiters, true)) {
					break;
				}
				$lastTypeTokenIndex = $i;
				continue;
			}

			if ($depthAngles === 0 && $depthParens === 0 && in_array($text, $delimiters, true)) {
				break;
			}

			if (!$this->isAllowedTypeToken($text, $depthAngles, $depthParens)) {
				return null;
			}

			if ($text === '<') {
				$depthAngles++;
			} elseif ($text === '>') {
				$depthAngles--;
			} elseif ($text === '(') {
				$depthParens++;
			} elseif ($text === ')') {
				$depthParens--;
			}

			if ($depthAngles < 0 || $depthParens < 0) {
				return null;
			}

			$lastTypeTokenIndex = $i;
		}

		if ($lastTypeTokenIndex === null || $depthAngles !== 0 || $depthParens !== 0) {
			return null;
		}

		$slotEndOffset = $tokens[$lastTypeTokenIndex]['offset'] + strlen($tokens[$lastTypeTokenIndex]['text']);
		$rawSlot = substr($source->source, $slotStartOffset, $slotEndOffset - $slotStartOffset);
		$type = trim($rawSlot);
		if ($type === '' || !$this->looksLikePrismType($type)) {
			return null;
		}

		$leadingLength = strlen($leadingTrivia);
		$trailingTrivia = '';
		$rawTypeBody = substr($rawSlot, $leadingLength);
		$trimmedBody = rtrim($rawTypeBody);
		if (strlen($rawTypeBody) > strlen($trimmedBody)) {
			$trailingTrivia = substr($rawTypeBody, strlen($trimmedBody));
		}

		return [
			'type' => preg_replace('/\s+/', ' ', trim($trimmedBody)) ?? trim($trimmedBody),
			'slotStartOffset' => $slotStartOffset,
			'slotEndOffset' => $slotEndOffset,
			'leadingTrivia' => $leadingTrivia,
			'trailingTrivia' => $trailingTrivia,
			'endTokenIndex' => $lastTypeTokenIndex,
		];
	}

	private function needsPhpCompatibleRewrite(string $type): bool
	{
		return str_contains($type, '<') || str_contains($type, ' ') || str_contains($type, '|') || str_contains($type, '&');
	}

	private function looksLikePrismType(string $type): bool
	{
		$normalized = trim($type);
		if ($normalized === '') {
			return false;
		}

		$depthAngles = 0;
		$depthParens = 0;
		$len = strlen($normalized);
		for ($i = 0; $i < $len; $i++) {
			$ch = $normalized[$i];
			if (ctype_alnum($ch) || in_array($ch, ['_', '\\', '?', ',', ' ', '|', '&'], true)) {
				continue;
			}
			if ($ch === '<') {
				$depthAngles++;
				continue;
			}
			if ($ch === '>') {
				$depthAngles--;
				if ($depthAngles < 0) {
					return false;
				}
				continue;
			}
			if ($ch === '(') {
				$depthParens++;
				continue;
			}
			if ($ch === ')') {
				$depthParens--;
				if ($depthParens < 0) {
					return false;
				}
				continue;
			}
			return false;
		}

		return $depthAngles === 0 && $depthParens === 0;
	}

	private function isAllowedTypeToken(string $text, int $depthAngles, int $depthParens): bool
	{
		if ($text === '') {
			return false;
		}
		if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $text) === 1) {
			return true;
		}
		if (in_array($text, ['?', '\\', '|', '&'], true)) {
			return true;
		}
		if ($text === ',') {
			return $depthAngles > 0 || $depthParens > 0;
		}
		if ($text === '<') {
			return true;
		}
		if ($text === '>') {
			return $depthAngles > 0;
		}
		if ($text === '(') {
			return true;
		}
		if ($text === ')') {
			return $depthParens > 0;
		}
		return false;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findTopLevelArrowIndex(array $tokens, int $start): ?int
	{
		$depthAngles = 0;
		$depthParens = 0;
		$count = count($tokens);
		for ($i = $start; $i < $count; $i++) {
			$text = $tokens[$i]['text'];
			if ($text === '=>' && $depthAngles === 0 && $depthParens === 0) {
				return $i;
			}
			if ($text === '<') {
				$depthAngles++;
			} elseif ($text === '>') {
				$depthAngles = max(0, $depthAngles - 1);
			} elseif ($text === '(') {
				$depthParens++;
			} elseif ($text === ')') {
				$depthParens = max(0, $depthParens - 1);
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findNextMeaningfulIndex(array $tokens, int $start, ?int $limit): ?int
	{
		$count = $limit ?? count($tokens);
		for ($i = $start; $i < $count; $i++) {
			if (!$this->isTrivia($tokens[$i])) {
				return $i;
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findNextTokenTextIndex(array $tokens, int $start, string $wanted): ?int
	{
		$count = count($tokens);
		for ($i = $start; $i < $count; $i++) {
			if ($tokens[$i]['text'] === $wanted) {
				return $i;
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findMatchingCloser(array $tokens, int $openIndex, string $openText, string $closeText): ?int
	{
		$depth = 0;
		$count = count($tokens);
		for ($i = $openIndex; $i < $count; $i++) {
			$text = $tokens[$i]['text'];
			if ($text === $openText) {
				$depth++;
			} elseif ($text === $closeText) {
				$depth--;
				if ($depth === 0) {
					return $i;
				}
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function detectVariableKind(array $tokens, int $variableIndex): string
	{
		for ($i = $variableIndex - 1; $i >= 0; $i--) {
			if ($this->isTrivia($tokens[$i])) {
				continue;
			}
			$text = strtolower($tokens[$i]['text']);
			if (in_array($text, ['public', 'protected', 'private', 'static', 'var'], true)) {
				return 'property';
			}
			break;
		}
		return 'local';
	}

	private function isTrivia(array $token): bool
	{
		return $token['is_array'] && in_array($token['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
	}

	private function isVariableToken(array $token): bool
	{
		return $token['is_array'] && $token['id'] === T_VARIABLE;
	}

	private function isFunctionKeyword(array $token): bool
	{
		return $token['is_array'] && $token['id'] === T_FUNCTION;
	}

	private function isFnKeyword(array $token): bool
	{
		return $token['is_array'] && $token['id'] === T_FN;
	}

	/**
	 * @param list<array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}> $sites
	 * @return list<array{kind:string,name:?string,type:string,line:int,startOffset:int,endOffset:int,rewriteStart:int,rewriteEnd:int,replacement:string,ownerName?:?string}>
	 */
	private function dedupeNestedSites(array $sites): array
	{
		$out = [];
		$lastEnd = -1;
		foreach ($sites as $site) {
			if ($site['rewriteStart'] < $lastEnd) {
				continue;
			}
			$out[] = $site;
			$lastEnd = $site['rewriteEnd'];
		}
		return $out;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function detectFunctionOwnerKind(array $tokens, int $functionIndex): string
	{
		for ($i = $functionIndex - 1; $i >= 0; $i--) {
			if ($this->isTrivia($tokens[$i])) {
				continue;
			}
			$text = strtolower($tokens[$i]['text']);
			if (in_array($text, ['public', 'protected', 'private', 'static', 'abstract', 'final'], true)) {
				return 'method_return';
			}
			break;
		}
		return 'function_return';
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function readFunctionLikeName(array $tokens, int $functionIndex, int $openParenIndex): ?string
	{
		for ($i = $functionIndex + 1; $i < $openParenIndex; $i++) {
			$token = $tokens[$i];
			if ($token['is_array'] && $token['id'] === T_STRING) {
				return $token['text'];
			}
		}
		return null;
	}
}
