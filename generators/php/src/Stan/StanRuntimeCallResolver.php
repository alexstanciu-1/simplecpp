<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

/** Instantiates bounded runtime call contracts; never participates in lowering. */
final class StanRuntimeCallResolver
{
	/** @var array<string,array<string,mixed>>|null */
	private ?array $contracts = null;

	/** @return array<string,mixed>|null */
	public function contract(string $name): ?array
	{
		if ($this->contracts === null) {
			$data = json_decode((string) file_get_contents(__DIR__ . '/../../specs/php_runtime_symbol_contracts_strict.json'), true, 512, JSON_THROW_ON_ERROR);
			$this->contracts = [];
			foreach ($data['symbol_contracts'] ?? [] as $symbol => $row) {
				if (isset($row['call_contract'])) {
					$this->contracts[strtolower($symbol)] = $row['call_contract'];
				}
			}
		}
		return $this->contracts[strtolower(ltrim($name, '\\'))] ?? null;
	}

	/** @param array<string,mixed> $contract @param list<list<string>> $arguments @return array{return_type:string,errors:list<string>} */
	public function instantiate(array $contract, array $arguments): array
	{
		if (($contract['kind'] ?? '') !== 'collection_transform' || !in_array($contract['operation'] ?? '', ['map', 'filter'], true)) {
			throw new \LogicException('Unsupported runtime call contract.');
		}
		$failure = static fn(string $message): array => ['return_type' => 'unknown', 'errors' => [$message]];
		if (count($arguments) !== 2) {
			return $failure('Expected one collection and one explicitly typed callback.');
		}
		if (count($arguments[0]) !== 1 || count($arguments[1]) !== 1) {
			return $failure('Collection and callback must each have one known type.');
		}
		$carrier = self::carrier($arguments[0][0]);
		if ($carrier === null) {
			return $failure('Unsupported collection type `' . $arguments[0][0] . '`; unwrap results before mapping or filtering.');
		}
		$callback = self::callable($arguments[1][0]);
		if ($callback === null || count($callback['params']) !== 1 || $callback['params'][0] !== $carrier['value']) {
			return $failure('Callback must take exactly one `' . $carrier['value'] . '` value parameter; got `' . $arguments[1][0] . '`.');
		}
		$return = $callback['return'];
		if ($return === 'void' || str_contains($return, '&') || str_starts_with($return, 'function<')) {
			return $failure('Callback must return a storable value.');
		}
		if ($contract['operation'] === 'filter' && $return !== 'bool') {
			return $failure('Filter callback must return `bool`; got `' . $return . '`.');
		}
		$value = $contract['operation'] === 'filter' ? $carrier['value'] : $return;
		if ($carrier['policy'] === 'keyed' && $value === 'mixed') {
			return $failure('A typed-key hash cannot store mixed results; use a mixed-key table input.');
		}
		$result = match ($carrier['policy']) {
			'sequence' => 'vector<' . $value . '>',
			'boxed' => $carrier['source'],
			'keyed' => $carrier['family'] . '<' . $value . ($carrier['explicit_key'] ? ',' . $carrier['key'] : '') . '>',
		};
		return ['return_type' => $result, 'errors' => []];
	}

	/** @return array<string,mixed>|null */
	public static function carrier(string $type): ?array
	{
		$type = preg_replace('/\s+/', '', $type);
		if (in_array($type, ['mixed', 'dynamic'], true)) {
			return ['source' => $type, 'value' => 'mixed', 'key' => 'mixed', 'policy' => 'boxed'];
		}
		if (preg_match('/^(vector|fixed_array|hash|dynamic)(?:_t)?<(.+)>$/', $type, $match) !== 1) {
			return null;
		}
		$parts = self::split($match[2]);
		$family = $match[1];
		$sequence = in_array($family, ['vector', 'fixed_array'], true);
		if (count($parts) < 1 || count($parts) > ($family === 'vector' ? 1 : 2) || ($family === 'fixed_array' && count($parts) !== 2)) {
			return null;
		}
		$value = $parts[0];
		$key = $sequence ? 'int' : ($parts[1] ?? ($value === 'mixed' ? 'mixed' : 'string'));
		return ['source' => $type, 'value' => $value, 'key' => $key, 'family' => $family,
			'explicit_key' => count($parts) === 2,
			'policy' => $sequence ? 'sequence' : ($value === 'mixed' && $key === 'mixed' ? 'boxed' : 'keyed')];
	}

	/** @return array{return:string,params:list<string>}|null */
	public static function callable(string $type): ?array
	{
		$type = preg_replace('/\s+/', '', $type);
		if (preg_match('/^function<(.+)\((.*)\)>$/', $type, $match) !== 1) {
			return null;
		}
		return ['return' => $match[1], 'params' => $match[2] === '' ? [] : self::split($match[2])];
	}

	/** @return list<string> */
	private static function split(string $text): array
	{
		$parts = []; $depth = 0; $start = 0;
		for ($i = 0; $i < strlen($text); $i++) {
			if ($text[$i] === '<' || $text[$i] === '(') { $depth++; }
			if ($text[$i] === '>' || $text[$i] === ')') { $depth--; }
			if ($text[$i] === ',' && $depth === 0) { $parts[] = substr($text, $start, $i - $start); $start = $i + 1; }
		}
		$parts[] = substr($text, $start);
		return $parts;
	}
}
