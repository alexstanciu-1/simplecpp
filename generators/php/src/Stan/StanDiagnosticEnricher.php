<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanDiagnosticEnricher
{
	/** @var array<string,list<string>> */
	private array $sourceLineCache = [];

	/** @param list<array<string,mixed>> $diagnostics @return list<array<string,mixed>> */
	public function enrichList(array $diagnostics): array
	{
		$enriched = [];
		foreach ($diagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			$enriched[] = $this->enrich($diagnostic);
		}
		return $enriched;
	}

	/** @param array<string,mixed> $diagnostic @return array<string,mixed> */
	public function enrich(array $diagnostic): array
	{
		$kind = trim((string) ($diagnostic['kind'] ?? 'unknown'));
		$code = 'stan.' . ($kind === '' ? 'unknown' : $kind);
		$diagnostic['severity'] = (string) ($diagnostic['severity'] ?? 'warning');
		$diagnostic['code'] = (string) ($diagnostic['code'] ?? $code);
		$diagnostic['diagnostic_id'] = (string) ($diagnostic['diagnostic_id'] ?? $this->makeDiagnosticId($diagnostic));
		$diagnostic['source'] = (string) ($diagnostic['source'] ?? 'stan');
		$diagnostic['span'] = $this->buildSpan($diagnostic);
		return $diagnostic;
	}

	/** @param array<string,mixed> $diagnostic */
	private function makeDiagnosticId(array $diagnostic): string
	{
		$parts = [];
		foreach ([
			'kind',
			'path',
			'context',
			'line',
			'name',
			'local_name',
			'property_name',
			'target',
			'owner',
			'class',
			'ancestor_class',
			'dependency_kind',
			'initialization_kind',
			'failure_kind',
			'chain',
		] as $key) {
			$value = $diagnostic[$key] ?? null;
			if (is_string($value) || is_int($value)) {
				$parts[] = $key . '=' . (string) $value;
			}
		}
		if ($parts === []) {
			$parts[] = 'message=' . (string) ($diagnostic['message'] ?? '');
		}
		return 'stan:' . hash('sha256', implode('|', $parts));
	}

	/** @param array<string,mixed> $diagnostic @return array<string,array<string,int>>|null */
	private function buildSpan(array $diagnostic): ?array
	{
		$line = (int) ($diagnostic['line'] ?? 0);
		if ($line <= 0) {
			return null;
		}
		$anchor = $this->findAnchorWithWindow($diagnostic);
		if ($anchor !== null) {
			return [
				'start' => ['line' => $anchor['line'], 'column' => $anchor['start']],
				'end' => ['line' => $anchor['line'], 'column' => $anchor['end']],
			];
		}
		return [
			'start' => ['line' => $line, 'column' => 1],
			'end' => ['line' => $line, 'column' => 1],
		];
	}

	private function loadSourceLine(string $path, int $line): ?string
	{
		if ($path === '' || $line <= 0 || !is_file($path)) {
			return null;
		}
		if (!isset($this->sourceLineCache[$path])) {
			$contents = file_get_contents($path);
			if (!is_string($contents)) {
				return null;
			}
			$this->sourceLineCache[$path] = preg_split("/\\r\\n|\\n|\\r/", $contents) ?: [];
		}
		return $this->sourceLineCache[$path][$line - 1] ?? null;
	}

	/** @return list<string> */
	private function loadSourceLines(string $path): array
	{
		if ($path === '' || !is_file($path)) {
			return [];
		}
		if (!isset($this->sourceLineCache[$path])) {
			$contents = file_get_contents($path);
			if (!is_string($contents)) {
				return [];
			}
			$this->sourceLineCache[$path] = preg_split("/\\r\\n|\\n|\\r/", $contents) ?: [];
		}
		return $this->sourceLineCache[$path];
	}

	/** @param array<string,mixed> $diagnostic @return array{start:int,end:int}|null */
	private function findAnchor(array $diagnostic, ?string $lineText): ?array
	{
		if (!is_string($lineText) || $lineText === '') {
			return null;
		}

		$localName = trim((string) ($diagnostic['local_name'] ?? ''));
		if ($localName !== '') {
			$span = $this->findFirstToken($lineText, '$' . $localName);
			if ($span !== null) {
				return $span;
			}
		}

		$chain = trim((string) ($diagnostic['chain'] ?? ''));
		if ($chain !== '') {
			$span = $this->findChainSpan($lineText, $chain);
			if ($span !== null) {
				return $span;
			}
		}

		$propertyName = trim((string) ($diagnostic['property_name'] ?? ''));
		if ($propertyName !== '') {
			foreach (['$this->' . $propertyName, '->' . $propertyName, '$' . $propertyName] as $token) {
				$span = $this->findFirstToken($lineText, $token);
				if ($span !== null) {
					return $span;
				}
			}
		}

		$callName = $this->extractCallName($diagnostic);
		if ($callName !== null) {
			foreach ([$callName . '(', $callName . ' ('] as $token) {
				$span = $this->findFirstToken($lineText, $token);
				if ($span !== null) {
					return $span;
				}
			}
		}

		return null;
	}

	/** @param array<string,mixed> $diagnostic @return array{line:int,start:int,end:int}|null */
	private function findAnchorWithWindow(array $diagnostic): ?array
	{
		$line = (int) ($diagnostic['line'] ?? 0);
		$path = (string) ($diagnostic['path'] ?? '');
		if ($line <= 0 || $path === '') {
			return null;
		}

		$lines = $this->loadSourceLines($path);
		if ($lines === []) {
			return null;
		}

		foreach ([$line, $line - 1, $line + 1, $line - 2, $line + 2] as $candidateLine) {
			if ($candidateLine <= 0) {
				continue;
			}
			$lineText = $lines[$candidateLine - 1] ?? null;
			$anchor = $this->findAnchor($diagnostic, is_string($lineText) ? $lineText : null);
			if ($anchor !== null) {
				return [
					'line' => $candidateLine,
					'start' => $anchor['start'],
					'end' => $anchor['end'],
				];
			}
		}

		return null;
	}

	/** @param array<string,mixed> $diagnostic */
	private function extractCallName(array $diagnostic): ?string
	{
		$message = (string) ($diagnostic['message'] ?? '');
		if (preg_match('/for `([^`]+)` parameter/u', $message, $matches) === 1) {
			return rtrim((string) ($matches[1] ?? ''), '()');
		}
		return null;
	}

	/** @return array{start:int,end:int}|null */
	private function findChainSpan(string $lineText, string $chain): ?array
	{
		$searches = [$chain];
		if (str_starts_with($chain, '$this->')) {
			$searches[] = substr($chain, 1);
		}
		foreach ($searches as $search) {
			$span = $this->findFirstToken($lineText, $search);
			if ($span !== null) {
				return $span;
			}
		}
		return null;
	}

	/** @return array{start:int,end:int}|null */
	private function findFirstToken(string $lineText, string $token): ?array
	{
		if ($token === '') {
			return null;
		}
		$offset = strpos($lineText, $token);
		if ($offset === false) {
			return null;
		}
		$start = $offset + 1;
		$end = $start + strlen($token) - 1;
		return ['start' => $start, 'end' => $end];
	}
}
