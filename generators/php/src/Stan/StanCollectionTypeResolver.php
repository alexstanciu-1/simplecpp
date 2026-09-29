<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

/** Describes typed collection elements and compiler-storage method signatures for STAN. */
final class StanCollectionTypeResolver
{
	/** @return array{value:string,key:string}|null */
	public static function storageCarrier(string $type): ?array
	{
		if (preg_match('/^(Storage|Keyed_Storage)\s*<(.+)>$/', trim($type), $parts) !== 1) {
			return null;
		}
		return ['value' => trim($parts[2]), 'key' => $parts[1] === 'Storage' ? 'int' : 'string'];
	}

	/** Instantiate the runtime-owned method surface for one explicit record type. */
	public static function storageClass(string $type): ?array
	{
		$carrier = self::storageCarrier($type);
		if ($carrier === null) {
			return null;
		}
		$key = ['name' => 'key', 'type' => $carrier['key']];
		$record = ['name' => 'record', 'type' => $carrier['value']];
		$methods = [
			'replace' => ['void', [$key, $record]], 'remove' => ['void', [$key]],
			'reserve' => ['void', [['name' => 'capacity', 'type' => 'int']]],
			'is_empty' => ['bool', []], 'count' => ['int', []],
		];
		if ($carrier['key'] === 'int') {
			$methods['append'] = ['int', [$record]];
		} else {
			$methods['add'] = ['void', [$key, $record]];
		}
		$signatures = [];
		$returns = [];
		foreach ($methods as $name => [$return, $params]) {
			$signatures[$name] = ['name' => $name, 'params' => $params, 'return_type' => $return, 'is_static' => false, 'visibility' => 'public'];
			$returns[$name] = $return;
		}
		return ['fqcn' => $type, 'name' => $type, 'method_signatures' => $signatures,
			'method_return_types' => $returns, 'property_types' => [], 'ancestor_types' => []];
	}

	/** @return array{value:string,key:string}|null */
	public static function carrier(string $type): ?array
	{
		$type = preg_replace('/\s+/', '', $type);
		if (preg_match('/^(vector|fixed_array|hash)(?:_t)?<(.+)>$/i', $type, $match) !== 1) {
			return null;
		}
		$parts = self::split($match[2]);
		$family = strtolower($match[1]);
		$sequence = in_array($family, ['vector', 'fixed_array'], true);
		if (count($parts) < 1 || count($parts) > ($family === 'vector' ? 1 : 2) || ($family === 'fixed_array' && count($parts) !== 2)) {
			return null;
		}
		$value = $parts[0];
		$key = $sequence ? 'int' : ($parts[1] ?? ($value === 'mixed' ? 'mixed' : 'string'));
		return ['value' => $value, 'key' => $key];
	}

	/** @return list<string> */
	private static function split(string $text): array
	{
		$parts = [];
		$depth = 0;
		$start = 0;
		for ($i = 0; $i < strlen($text); $i++) {
			if ($text[$i] === '<' || $text[$i] === '(') { $depth++; }
			if ($text[$i] === '>' || $text[$i] === ')') { $depth--; }
			if ($text[$i] === ',' && $depth === 0) { $parts[] = substr($text, $start, $i - $start); $start = $i + 1; }
		}
		$parts[] = substr($text, $start);
		return $parts;
	}
}
