<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanTakeContractResolver
{
	/** @return array{family:string,output_types:list<string>}|null */
	public function resolve(string $sourceType): ?array
	{
		$type = strtolower(trim($sourceType));
		if ($type === '') {
			return null;
		}
		if (preg_match('/^nullable<(.+)>$/', $type, $matches) === 1) {
			return [
				'family' => 'nullable',
				'output_types' => [trim((string) $matches[1])],
			];
		}
		if (preg_match('/^result_or_false<(.+)>$/', $type, $matches) === 1) {
			return [
				'family' => 'result_or_false',
				'output_types' => [trim((string) $matches[1])],
			];
		}
		if (preg_match('/^result<(.+)>$/', $type, $matches) === 1) {
			return [
				'family' => 'result',
				'output_types' => [trim((string) $matches[1]), 'error'],
			];
		}
		if (preg_match('/^result_or_bool<(.+)>$/', $type, $matches) === 1) {
			return [
				'family' => 'result_or_bool',
				'output_types' => [trim((string) $matches[1]), 'bool'],
			];
		}
		return null;
	}
}
