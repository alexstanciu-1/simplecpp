<?php
declare(strict_types=1);

namespace Scpp\S2S\Metadata;

use Scpp\S2S\IR\ArgNormalizationRule;

/**
 * Parses docblock lines shaped like:
 * @arg.order_id.from(Order\Id) = order_id->get_id()
 */
final class ArgNormalizationCommentParser
{
	/**
	 * @return array{rules:list<ArgNormalizationRule>,errors:list<string>}
	 */
	public function parse(mixed $docComment, string $ownerLabel): array
	{
		if (!is_string($docComment) || trim($docComment) === '') {
			return ['rules' => [], 'errors' => []];
		}

		$rules = [];
		$errors = [];
		$lines = preg_split('/\R/', $docComment) ?: [];
		foreach ($lines as $index => $line) {
			$trimmed = trim($line);
			$trimmed = preg_replace('/^\/\*\*?/', '', $trimmed) ?? $trimmed;
			$trimmed = preg_replace('/\*\/$/', '', $trimmed) ?? $trimmed;
			$trimmed = ltrim(trim($trimmed), '*');
			$trimmed = trim($trimmed);
			if ($trimmed === '' || !str_starts_with($trimmed, '@arg.')) {
				continue;
			}

			$rule = $this->parseSingleRule($trimmed, $index + 1, $ownerLabel, $errors);
			if ($rule !== null) {
				$rules[] = $rule;
			}
		}

		return ['rules' => $rules, 'errors' => $errors];
	}

	/**
	 * @param list<string> $errors
	 */
	private function parseSingleRule(string $line, int $lineNumber, string $ownerLabel, array &$errors): ?ArgNormalizationRule
	{
		if (preg_match('/^@arg\.([A-Za-z_][A-Za-z0-9_]*)\.from\(([^)]+)\)\s*=\s*(.+)$/', $line, $matches) !== 1) {
			$errors[] = 'Malformed @arg normalization annotation in ' . $ownerLabel . ' near doc line ' . $lineNumber . ': ' . $line;
			return null;
		}

		$paramName = trim($matches[1]);
		$sourceType = trim($matches[2]);
		$expression = trim($matches[3]);
		if ($sourceType === '' || $expression === '') {
			$errors[] = 'Malformed @arg normalization annotation in ' . $ownerLabel . ' near doc line ' . $lineNumber . ': ' . $line;
			return null;
		}

		return new ArgNormalizationRule($paramName, $sourceType, $expression, $lineNumber);
	}
}
