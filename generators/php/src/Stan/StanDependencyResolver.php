<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanDependencyResolver
{
	public function __construct(
		private readonly StanPathMapper $pathMapper = new StanPathMapper(),
	)
	{
	}

	/** @param list<array<string,mixed>> $symbolIndex @return array<string,list<array<string,mixed>>> */
	public function buildResolutionLookup(array $symbolIndex): array
	{
		$lookup = [];
		foreach ($symbolIndex as $symbol) {
			$name = (string) ($symbol['name'] ?? '');
			$scope = (string) ($symbol['scope'] ?? '');
			$kind = (string) ($symbol['kind'] ?? '');
			if ($name === '' || $kind === '') {
				continue;
			}
			$fq = $scope === '' ? $name : $scope . '::' . $name;
			$lookup[$kind . '|' . strtolower($name)][] = $symbol;
			$lookup[$kind . '|' . strtolower($fq)][] = $symbol;
			if ($kind === 'class') {
				$lookup['interface|' . strtolower($name)][] = $symbol;
				$lookup['interface|' . strtolower($fq)][] = $symbol;
			}
		}
		$this->addBuiltinFunctionResolutionEntries($lookup);
		return $lookup;
	}

	/** @param array<string,list<array<string,mixed>>> $lookup */
	private function addBuiltinFunctionResolutionEntries(array &$lookup): void
	{
		foreach ($this->builtinFunctionNames() as $name) {
			$key = 'function|' . strtolower($name);
			if (isset($lookup[$key])) {
				continue;
			}
			$lookup[$key][] = [
				'kind' => 'function',
				'name' => $name,
				'scope' => '',
				'key' => 'builtin:function:' . $name,
				'path' => '',
				'line' => 0,
				'signature' => $name . '(...)',
			];
		}
	}

	/** @return list<string> */
	private function builtinFunctionNames(): array
	{
		return [
			'async_sleep_ms',
			'async_wait',
			'enum_value',
			'enum_name',
			'enum_from_value',
			'vector_reserve',
			'vector_capacity',
			'vector_resize',
			'vector_filled',
			'vector_clear',
			'vector_clear_keep_capacity',
			'vector_compact',
			'source_buffer_empty',
			'source_buffer_take',
			'source_buffer_release',
			'source_text_vector_move_append',
			'source_buffer_byte_len',
			'source_buffer_byte_at',
			'source_buffer_span',
			'source_buffer_slice',
			'source_line_index_build',
			'source_line_index_line_count',
			'source_line_index_offset_to_location',
			'source_line_index_line_column_to_offset',
			'source_location_offset',
			'source_location_line',
			'source_location_column',
			'byte_span_len',
			'byte_span_at',
			'byte_span_to_string',
			'hash_bytes',
			'stable_hash_string_u64',
			'stable_hash_bytes_u64',
			'string_parts_builder_create',
			'string_parts_builder_reserve',
			'string_parts_builder_count',
			'string_parts_builder_capacity',
			'string_parts_builder_byte_len',
			'string_parts_builder_append_string',
			'string_parts_builder_append_int',
			'string_parts_builder_append_bool',
			'string_parts_builder_to_string',
			'string_parts_builder_clear',
			'text_builder_create',
			'text_builder_reserve_bytes',
			'text_builder_capacity_bytes',
			'text_builder_byte_len',
			'text_builder_append_string',
			'text_builder_append_int',
			'text_builder_append_bool',
			'text_builder_append_byte_span',
			'text_builder_to_string',
			'text_builder_take_string',
			'text_builder_clear',
		];
	}

	/** @param array<string,list<array<string,mixed>>> $lookup @return list<array<string,mixed>> */
	public function resolveDependencyTarget(string $kind, string $target, array $lookup): array
	{
		$normalizedTarget = strtolower(str_replace('\\', '::', ltrim($target, '\\')));
		$shortTarget = strtolower(($pos = strrpos($normalizedTarget, '::')) !== false ? substr($normalizedTarget, $pos + 2) : $normalizedTarget);
		$keys = [];
		if ($kind === 'extends' || $kind === 'implements') {
			$keys = ['class|' . $normalizedTarget, 'class|' . $shortTarget, 'interface|' . $normalizedTarget, 'interface|' . $shortTarget];
		} elseif ($kind === 'use') {
			$keys = ['function|' . $normalizedTarget, 'function|' . $shortTarget, 'class|' . $normalizedTarget, 'class|' . $shortTarget, 'constant|' . $normalizedTarget, 'constant|' . $shortTarget];
		} else {
			$keys = ['class|' . $normalizedTarget, 'class|' . $shortTarget, 'function|' . $normalizedTarget, 'function|' . $shortTarget, 'constant|' . $normalizedTarget, 'constant|' . $shortTarget];
		}
		$matches = [];
		foreach ($keys as $key) {
			foreach (($lookup[$key] ?? []) as $symbol) {
				$bucketKey = (string) ($symbol['key'] ?? '') . '|' . (string) ($symbol['path'] ?? '') . '|' . (int) ($symbol['line'] ?? 0);
				$matches[$bucketKey] = $symbol;
			}
		}
		return array_values($matches);
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<array<string,mixed>> $symbolIndex @return array<string,list<string>> */
	public function collectFileDependencyKeys(array $fileSummaries, array $symbolIndex, string $projectRoot): array
	{
		$lookup = $this->buildResolutionLookup($symbolIndex);
		$dependencyKeys = [];
		foreach ($fileSummaries as $sourceKey => $summary) {
			$keys = [];
			foreach (($summary['dependencies'] ?? []) as $dependency) {
				if (!is_array($dependency)) {
					continue;
				}
				$kind = (string) ($dependency['kind'] ?? '');
				$target = (string) ($dependency['target'] ?? '');
				if ($kind === '' || $target === '') {
					continue;
				}
				foreach ($this->resolveDependencyTarget($kind, $target, $lookup) as $symbol) {
					$path = (string) ($symbol['path'] ?? '');
					if ($path === '') {
						continue;
					}
					$resolvedKey = $this->pathMapper->sourceKey($projectRoot, $path);
					if ($resolvedKey !== $sourceKey) {
						$keys[$resolvedKey] = true;
					}
				}
			}
			$resolvedKeys = array_keys($keys);
			sort($resolvedKeys, SORT_STRING);
			$dependencyKeys[$sourceKey] = $resolvedKeys;
		}
		return $dependencyKeys;
	}
}
