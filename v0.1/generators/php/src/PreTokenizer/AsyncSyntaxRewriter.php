<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

/**
 * Normalizes PHS async/await spelling into the parser-compatible async surface.
 */
final class AsyncSyntaxRewriter
{
	public function rewrite(string $source): string
	{
		$lexed = new LexedSource($source);
		$tokens = $lexed->tokens;
		$edits = [];
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];
			if (!$this->isWordToken($token, 'async') && !$this->isWordToken($token, 'await')) {
				continue;
			}

			if ($this->isWordToken($token, 'async')) {
				$next = $this->findNextMeaningfulIndex($tokens, $i + 1, null);
				if ($next !== null && strtolower($tokens[$next]['text']) === 'function') {
					$edits[] = [
						'start' => $token['offset'],
						'end' => $tokens[$next]['offset'],
						'replacement' => '/** @async */' . $this->lineEndingBefore($source, $token['offset']),
					];
				}
				continue;
			}

			$operandStart = $this->findNextMeaningfulIndex($tokens, $i + 1, null);
			if ($operandStart === null) {
				continue;
			}
			$operandEnd = $this->findAwaitOperandEnd($tokens, $operandStart);
			if ($operandEnd === null) {
				continue;
			}

			if ($this->isAsyncSleepCall($tokens, $operandStart)) {
				$edits[] = [
					'start' => $token['offset'],
					'end' => $tokens[$operandStart]['offset'],
					'replacement' => '',
				];
				continue;
			}

			$edits[] = [
				'start' => $token['offset'],
				'end' => $tokens[$operandStart]['offset'],
				'replacement' => 'async_wait(',
			];
			$edits[] = [
				'start' => $operandEnd,
				'end' => $operandEnd,
				'replacement' => ')',
			];
		}

		return $this->applyEdits($source, $edits);
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findAwaitOperandEnd(array $tokens, int $start): ?int
	{
		$count = count($tokens);
		$end = $start;

		if (($tokens[$start]['text'] ?? '') === '(') {
			$close = $this->findMatchingCloser($tokens, $start, '(', ')');
			if ($close === null) {
				return null;
			}
			$end = $close;
		} else {
			$next = $this->findNextMeaningfulIndex($tokens, $start + 1, null);
			if ($next !== null && $tokens[$next]['text'] === '(') {
				$close = $this->findMatchingCloser($tokens, $next, '(', ')');
				if ($close === null) {
					return null;
				}
				$end = $close;
			}
		}

		for (;;) {
			$next = $this->findNextMeaningfulIndex($tokens, $end + 1, null);
			if ($next === null || $next >= $count) {
				break;
			}
			$text = $tokens[$next]['text'];
			if ($text === '[') {
				$close = $this->findMatchingCloser($tokens, $next, '[', ']');
				if ($close === null) {
					return null;
				}
				$end = $close;
				continue;
			}
			if ($text === '->' || $text === '::') {
				$member = $this->findNextMeaningfulIndex($tokens, $next + 1, null);
				if ($member === null) {
					break;
				}
				$end = $member;
				$callOpen = $this->findNextMeaningfulIndex($tokens, $member + 1, null);
				if ($callOpen !== null && $tokens[$callOpen]['text'] === '(') {
					$close = $this->findMatchingCloser($tokens, $callOpen, '(', ')');
					if ($close === null) {
						return null;
					}
					$end = $close;
				}
				continue;
			}
			break;
		}

		return $tokens[$end]['offset'] + strlen($tokens[$end]['text']);
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function isAsyncSleepCall(array $tokens, int $start): bool
	{
		if (!$this->isWordToken($tokens[$start], 'async_sleep_ms')) {
			return false;
		}
		$next = $this->findNextMeaningfulIndex($tokens, $start + 1, null);
		return $next !== null && $tokens[$next]['text'] === '(';
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findMatchingCloser(array $tokens, int $openIndex, string $open, string $close): ?int
	{
		$depth = 0;
		$count = count($tokens);
		for ($i = $openIndex; $i < $count; $i++) {
			$text = $tokens[$i]['text'];
			if ($text === $open) {
				$depth++;
			} elseif ($text === $close) {
				$depth--;
				if ($depth === 0) {
					return $i;
				}
			}
		}
		return null;
	}

	/** @param list<array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null}> $tokens */
	private function findNextMeaningfulIndex(array $tokens, int $start, ?int $end): ?int
	{
		$limit = $end ?? count($tokens);
		for ($i = $start; $i < $limit; $i++) {
			if (!$this->isTrivia($tokens[$i])) {
				return $i;
			}
		}
		return null;
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isTrivia(array $token): bool
	{
		return in_array($token['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
	}

	/** @param array{index:int,text:string,line:int,offset:int,is_array:bool,id:int|null} $token */
	private function isWordToken(array $token, string $word): bool
	{
		return $token['is_array'] && (int) $token['id'] === T_STRING && strtolower($token['text']) === strtolower($word);
	}

	private function lineEndingBefore(string $source, int $offset): string
	{
		$before = substr($source, 0, $offset);
		return str_contains($before, "\r\n") ? "\r\n" : "\n";
	}

	/** @param list<array{start:int,end:int,replacement:string}> $edits */
	private function applyEdits(string $source, array $edits): string
	{
		if ($edits === []) {
			return $source;
		}
		usort($edits, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

		$out = '';
		$cursor = 0;
		foreach ($edits as $edit) {
			if ($edit['start'] < $cursor) {
				continue;
			}
			$out .= substr($source, $cursor, $edit['start'] - $cursor);
			$out .= $edit['replacement'];
			$cursor = $edit['end'];
		}
		return $out . substr($source, $cursor);
	}
}
