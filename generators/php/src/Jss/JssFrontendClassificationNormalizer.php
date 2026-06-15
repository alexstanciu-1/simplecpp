<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssFrontendClassificationNormalizer
{
	/** @param array<string,array<string,mixed>> $classifications @return array<string,array<string,mixed>> */
	public function byIdentifier(array $classifications): array
	{
		$indexed = [];
		foreach ($classifications as $classification) {
			if (!is_array($classification) || (string) ($classification['request_kind'] ?? '') !== 'identifier_role') {
				continue;
			}
			$name = (string) ($classification['name'] ?? '');
			if ($name !== '') {
				$indexed[$name] = $classification;
			}
		}
		return $indexed;
	}

	/** @param array<string,array<string,mixed>> $classifications @return array<string,array<string,mixed>> */
	public function byMember(array $classifications): array
	{
		$indexed = [];
		foreach ($classifications as $classification) {
			if (!is_array($classification) || (string) ($classification['request_kind'] ?? '') !== 'member_access') {
				continue;
			}
			$chain = is_array($classification['chain'] ?? null) ? array_values(array_map('strval', $classification['chain'])) : [];
			if ($chain === []) {
				continue;
			}
			$indexed[$this->memberKey($chain, ($classification['is_call'] ?? false) === true)] = $classification;
		}
		return $indexed;
	}

	/** @param array<string,array<string,mixed>> $classifications @return array<string,array<string,mixed>> */
	public function byBinaryPlus(array $classifications): array
	{
		$indexed = [];
		foreach ($classifications as $classification) {
			if (!is_array($classification) || (string) ($classification['request_kind'] ?? '') !== 'binary_plus') {
				continue;
			}
			$key = (string) ($classification['expression_key'] ?? '');
			if ($key !== '') {
				$indexed[$key] = $classification;
			}
		}
		return $indexed;
	}

	/** @param list<string> $chain */
	public function memberKey(array $chain, bool $isCall): string
	{
		return ($isCall ? 'call' : 'read') . ':' . implode('.', $chain);
	}
}
