<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanPositionResolver
{
	/** @var array<string,list<string>> */
	private array $sourceLineCache = [];

	/** @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function buildDocumentSymbols(array $symbolIndex, string $documentPath, callable $mapSymbolKindToLsp): array
	{
		$symbols = [];
		foreach ($symbolIndex as $symbol) {
			if (!is_array($symbol) || \normalize_path((string) ($symbol['path'] ?? '')) !== $documentPath) {
				continue;
			}
			$line = (int) ($symbol['line'] ?? 0);
			$name = (string) ($symbol['name'] ?? '');
			$kind = (string) ($symbol['kind'] ?? 'symbol');
			$symbols[] = [
				'name' => $name,
				'kind' => $kind,
				'lsp_kind' => $mapSymbolKindToLsp($kind),
				'scope' => (string) ($symbol['scope'] ?? ''),
				'owner_class' => $symbol['owner_class'] ?? null,
				'line' => $line,
				'span' => $this->buildSymbolSpan($documentPath, $line, $name, $kind),
			];
		}

		usort($symbols, static function (array $left, array $right): int {
			$byLine = ((int) ($left['line'] ?? 0)) <=> ((int) ($right['line'] ?? 0));
			if ($byLine !== 0) {
				return $byLine;
			}
			return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
		});

		return $symbols;
	}

	/** @param list<array<string,mixed>> $symbolIndex @return array<string,mixed>|null */
	public function findBestSymbolAtPosition(array $symbolIndex, string $documentPath, int $line, ?int $column = null): ?array
	{
		$lineMatches = [];
		foreach ($symbolIndex as $symbol) {
			if (!is_array($symbol) || \normalize_path((string) ($symbol['path'] ?? '')) !== $documentPath) {
				continue;
			}
			if ((int) ($symbol['line'] ?? 0) !== $line) {
				continue;
			}
			$lineMatches[] = $symbol;
		}

		if ($lineMatches === []) {
			return null;
		}
		if ($column === null) {
			return $lineMatches[0];
		}

		$best = null;
		$bestWidth = null;
		foreach ($lineMatches as $symbol) {
			$span = $this->buildSymbolSpan(
				$documentPath,
				(int) ($symbol['line'] ?? 0),
				(string) ($symbol['name'] ?? ''),
				(string) ($symbol['kind'] ?? 'symbol'),
			);
			$start = (int) ($span['start']['column'] ?? 1);
			$end = (int) ($span['end']['column'] ?? $start);
			if ($column < $start || $column > $end) {
				continue;
			}
			$width = max(1, $end - $start + 1);
			if ($best === null || $width < $bestWidth) {
				$best = $symbol;
				$bestWidth = $width;
			}
		}

		return $best ?? $lineMatches[0];
	}

	/** @param list<array<string,mixed>> $diagnostics @return list<array<string,mixed>> */
	public function collectDiagnosticsForPosition(array $diagnostics, string $documentPath, int $line, ?int $column = null): array
	{
		$matchingDiagnostics = [];
		foreach ($diagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			if (\normalize_path((string) ($diagnostic['path'] ?? '')) !== $documentPath) {
				continue;
			}
			$span = $diagnostic['span'] ?? null;
			$startLine = (int) ($span['start']['line'] ?? ($diagnostic['line'] ?? 0));
			$endLine = (int) ($span['end']['line'] ?? ($diagnostic['line'] ?? 0));
			if ($line < $startLine || $line > $endLine) {
				continue;
			}
			if ($column !== null) {
				$startColumn = (int) ($span['start']['column'] ?? 1);
				$endColumn = (int) ($span['end']['column'] ?? $startColumn);
				if ($line === $startLine && $column < $startColumn) {
					continue;
				}
				if ($line === $endLine && $column > $endColumn) {
					continue;
				}
			}
			$matchingDiagnostics[] = $diagnostic;
		}
		return $matchingDiagnostics;
	}

	public function extractIdentifierAtPosition(string $documentPath, int $line, ?int $column = null): ?string
	{
		if ($column === null) {
			return null;
		}
		$sourceLine = $this->loadSourceLine($documentPath, $line);
		if (!is_string($sourceLine) || $sourceLine === '') {
			return null;
		}

		$index = max(0, $column - 1);
		$length = strlen($sourceLine);
		if ($index >= $length) {
			$index = $length - 1;
		}
		if ($index < 0) {
			return null;
		}

		if (!preg_match('/[A-Za-z0-9_]/', $sourceLine[$index])) {
			if ($index > 0 && preg_match('/[A-Za-z0-9_]/', $sourceLine[$index - 1])) {
				$index--;
			} else {
				return null;
			}
		}

		$start = $index;
		while ($start > 0 && preg_match('/[A-Za-z0-9_]/', $sourceLine[$start - 1])) {
			$start--;
		}
		$end = $index;
		while (($end + 1) < $length && preg_match('/[A-Za-z0-9_]/', $sourceLine[$end + 1])) {
			$end++;
		}

		$identifier = substr($sourceLine, $start, $end - $start + 1);
		return $identifier !== '' ? $identifier : null;
	}

	/** @param array<string,mixed> $symbol @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	public function collectReferenceLocations(array $symbol, array $symbolIndex): array
	{
		$name = (string) ($symbol['name'] ?? '');
		$kind = (string) ($symbol['kind'] ?? '');
		$scope = (string) ($symbol['scope'] ?? '');
		if ($name === '' || $kind === '') {
			return [];
		}

		$declarations = [];
		$declarationPaths = [];
		$searchPaths = [];
		foreach ($symbolIndex as $candidate) {
			if (is_array($candidate)) {
				$candidatePath = \normalize_path((string) ($candidate['path'] ?? ''));
				if ($candidatePath !== '') {
					$searchPaths[$candidatePath] = true;
				}
			}
			if (!is_array($candidate)) {
				continue;
			}
			if ((string) ($candidate['name'] ?? '') !== $name || (string) ($candidate['kind'] ?? '') !== $kind) {
				continue;
			}
			if (($kind === 'method' || $kind === 'property') && (string) ($candidate['scope'] ?? '') !== $scope) {
				continue;
			}
			$candidatePath = \normalize_path((string) ($candidate['path'] ?? ''));
			$candidateLine = (int) ($candidate['line'] ?? 0);
			$declarations[] = [
				'name' => $name,
				'kind' => $kind,
				'path' => $candidatePath,
				'uri' => 'file://' . $candidatePath,
				'line' => $candidateLine,
				'span' => $this->buildSymbolSpan($candidatePath, $candidateLine, $name, $kind),
			];
			$declarationPaths[$candidatePath] = true;
		}

		$references = [];
		$seen = [];
		foreach ($declarations as $declaration) {
			$key = $this->referenceKey($declaration);
			$seen[$key] = true;
			$references[] = $declaration;
		}

		foreach (array_keys($searchPaths) as $path) {
			foreach ($this->findTextualReferencesInFile($path, $name, $kind) as $reference) {
				$key = $this->referenceKey($reference);
				if (isset($seen[$key])) {
					continue;
				}
				$seen[$key] = true;
				$references[] = $reference;
			}
		}

		usort($references, static function (array $left, array $right): int {
			$byPath = strcmp((string) ($left['path'] ?? ''), (string) ($right['path'] ?? ''));
			if ($byPath !== 0) {
				return $byPath;
			}
			return ((int) ($left['line'] ?? 0)) <=> ((int) ($right['line'] ?? 0));
		});

		return $references;
	}

	/** @return array<string,array<string,int>> */
	public function buildSymbolSpan(string $path, int $line, string $name, string $kind): array
	{
		$sourceLine = $this->loadSourceLine($path, $line);
		if (!is_string($sourceLine) || $sourceLine === '') {
			return [
				'start' => ['line' => $line, 'column' => 1],
				'end' => ['line' => $line, 'column' => 1],
			];
		}

		foreach ($this->symbolTokensForKind($kind, $name) as $token) {
			$offset = strpos($sourceLine, $token);
			if ($offset === false) {
				continue;
			}
			$start = $offset + 1;
			return [
				'start' => ['line' => $line, 'column' => $start],
				'end' => ['line' => $line, 'column' => $start + strlen($token) - 1],
			];
		}

		return [
			'start' => ['line' => $line, 'column' => 1],
			'end' => ['line' => $line, 'column' => 1],
		];
	}

	/** @return list<string> */
	private function symbolTokensForKind(string $kind, string $name): array
	{
		return match ($kind) {
			'function', 'method' => [$name . '(', 'function ' . $name, 'function &' . $name],
			'class' => ['class ' . $name, 'interface ' . $name, 'enum ' . $name, 'abstract class ' . $name],
			'property' => ['$' . $name],
			'constant' => ['const ' . $name],
			default => [$name],
		};
	}

	private function loadSourceLine(string $path, int $line): ?string
	{
		if ($path === '' || $line <= 0) {
			return null;
		}

		$normalizedPath = \normalize_path($path);
		if (!isset($this->sourceLineCache[$normalizedPath])) {
			if (!is_file($normalizedPath)) {
				return null;
			}
			$contents = file_get_contents($normalizedPath);
			if (!is_string($contents)) {
				return null;
			}
			$this->sourceLineCache[$normalizedPath] = preg_split("/\\r\\n|\\n|\\r/", $contents) ?: [];
		}

		return $this->sourceLineCache[$normalizedPath][$line - 1] ?? null;
	}

	/** @return list<array<string,mixed>> */
	private function findTextualReferencesInFile(string $path, string $name, string $kind): array
	{
		$lines = $this->loadAllSourceLines($path);
		if ($lines === null) {
			return [];
		}
		$references = [];
		foreach ($lines as $index => $lineText) {
			foreach ($this->referenceTokensForKind($kind, $name) as $token) {
				$offset = strpos($lineText, $token);
				if ($offset === false) {
					continue;
				}
				$startColumn = $offset + 1;
				$endColumn = $startColumn + strlen($token) - 1;
				$references[] = [
					'name' => $name,
					'kind' => $kind,
					'path' => $path,
					'uri' => 'file://' . $path,
					'line' => $index + 1,
					'span' => [
						'start' => ['line' => $index + 1, 'column' => $startColumn],
						'end' => ['line' => $index + 1, 'column' => $endColumn],
					],
				];
				break;
			}
		}
		return $references;
	}

	/** @return list<string> */
	private function referenceTokensForKind(string $kind, string $name): array
	{
		return match ($kind) {
			'function' => [$name . '('],
			'method' => ['->' . $name . '(', '::' . $name . '('],
			'property' => ['->' . $name],
			'constant' => ['::' . $name, 'const ' . $name],
			'class' => ['new ' . $name . '(', $name . '::', ' ' . $name . ' '],
			default => [$name],
		};
	}

	/** @param array<string,mixed> $reference */
	private function referenceKey(array $reference): string
	{
		return implode(':', [
			(string) ($reference['path'] ?? ''),
			(string) ($reference['line'] ?? 0),
			(string) ($reference['span']['start']['column'] ?? 0),
			(string) ($reference['kind'] ?? ''),
			(string) ($reference['name'] ?? ''),
		]);
	}

	/** @return list<string>|null */
	private function loadAllSourceLines(string $path): ?array
	{
		if ($path === '') {
			return null;
		}
		$normalizedPath = \normalize_path($path);
		if (!isset($this->sourceLineCache[$normalizedPath])) {
			if (!is_file($normalizedPath)) {
				return null;
			}
			$contents = file_get_contents($normalizedPath);
			if (!is_string($contents)) {
				return null;
			}
			$this->sourceLineCache[$normalizedPath] = preg_split("/\\r\\n|\\n|\\r/", $contents) ?: [];
		}
		return $this->sourceLineCache[$normalizedPath];
	}
}
