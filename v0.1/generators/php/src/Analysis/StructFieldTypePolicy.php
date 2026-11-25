<?php
declare(strict_types=1);

namespace Scpp\S2S\Analysis;

/**
 * Field eligibility shared by STAN and the generator's declaration checks.
 * Declaration kinds come from each caller's existing metadata, not type inference.
 * Union payload eligibility remains a separate, stricter recursive contract.
 */
final class StructFieldTypePolicy
{
	/** @param callable(string): ?string $declarationKind */
	public static function supports(string $type, callable $declarationKind): bool
	{
		$type = trim($type);
		if (in_array(strtolower($type), ['string', 'bool', 'int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'], true)) {
			return true;
		}
		if (in_array($declarationKind($type), ['class', 'enum', 'struct', 'union'], true)) {
			return true;
		}
		if (preg_match('/^(vector|vector_t|hash|hash_t|fixed_array|fixed_array_t)\\s*<(.+)>$/', $type, $matches) !== 1) {
			return false;
		}
		// Split only outer arguments; nested containers follow the same field contract.
		$args = [];
		$depth = 0;
		$start = 0;
		$body = $matches[2];
		for ($i = 0; $i < strlen($body); $i++) {
			if ($body[$i] === '<') {
				$depth++;
			}
			if ($body[$i] === '>') {
				$depth--;
				if ($depth < 0) {
					return false;
				}
			}
			if ($body[$i] === ',' && $depth === 0) {
				$args[] = trim(substr($body, $start, $i - $start));
				$start = $i + 1;
			}
		}
		$args[] = trim(substr($body, $start));
		$countValid = match ($matches[1]) {
			'vector', 'vector_t' => count($args) === 1,
			'hash', 'hash_t' => count($args) >= 1 && count($args) <= 2,
			'fixed_array', 'fixed_array_t' => count($args) === 2,
		};
		// Existing type lowering owns hash key and fixed-array size validation.
		return $depth === 0 && $countValid && self::supports($args[0], $declarationKind);
	}
}
