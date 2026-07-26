<?php
declare(strict_types=1);

namespace Scpp\S2S\Analysis;

final class RuntimeShallowSourceGenerator
{
	/** @return array{profile:string,path:string,generated:int,skipped:list<string>} */
	public function generate(string $repoRoot, string $profile): array
	{
		$profile = strtolower(trim($profile));
		if (!in_array($profile, ['legacy', 'strict'], true)) {
			throw new \RuntimeException('Unsupported runtime shallow source profile: ' . $profile);
		}

		$registryPath = $repoRoot . '/generators/php/specs/php_runtime_symbols_' . $profile . '.json';
		$json = file_get_contents($registryPath);
		if (!is_string($json) || $json === '') {
			throw new \RuntimeException('Failed to read runtime symbol registry: ' . $registryPath);
		}

		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		$targets = is_array($data['php_runtime_symbol_targets'] ?? null) ? $data['php_runtime_symbol_targets'] : [];
		$sourcePath = $this->buildOutputPath($repoRoot, $profile);
		$functions = [];
		$classes = $this->renderRuntimeClasses($profile);
		$skipped = [];

		foreach (array_keys($targets) as $symbolName) {
			if (!is_string($symbolName)) {
				continue;
			}
			if (!$this->isSourceSafeFunctionName($symbolName)) {
				$skipped[] = $symbolName;
				continue;
			}
			$functions[] = $this->renderFunctionStub($symbolName, $profile);
		}
		foreach ($this->alwaysIncludedFunctionNames() as $symbolName) {
			if (!$this->isSourceSafeFunctionName($symbolName)) {
				continue;
			}
			$functions[] = $this->renderFunctionStub($symbolName, $profile);
		}

		sort($functions, SORT_STRING);
		sort($skipped, SORT_STRING);

		$contents = $this->renderSourceFile($profile, $functions, $classes, $skipped);
		$this->writeTextFile($sourcePath, $contents);

		return [
			'profile' => $profile,
			'path' => $sourcePath,
			'generated' => count($functions),
			'skipped' => $skipped,
		];
	}

	private function buildOutputPath(string $repoRoot, string $profile): string
	{
		$dir = $repoRoot . '/runtime/generated/stan';
		$extension = $profile === 'strict' ? 'phs' : 'php';
		return $dir . '/runtime_symbols_' . $profile . '.' . $extension;
	}

	private function isSourceSafeFunctionName(string $name): bool
	{
		if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
			return false;
		}

		static $reserved = [
			'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class',
			'clone', 'const', 'continue', 'declare', 'default', 'do', 'echo', 'else',
			'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch',
			'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for',
			'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include',
			'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list',
			'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public',
			'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw',
			'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield',
		];

		return !in_array(strtolower($name), $reserved, true);
	}

	private function renderFunctionStub(string $name, string $profile): string
	{
		$signature = $this->resolveSignature($name, $profile);
		$isStrict = $profile === 'strict';
		$params = [];
		foreach ($signature['params'] as $param) {
			$params[] = $this->renderParamWithMetadata($param, $isStrict);
		}

		$returnSuffix = $this->renderReturnSuffix($signature['return'], $isStrict);
		return 'function ' . $name . '(' . implode(', ', $params) . ')' . $returnSuffix . ' {}';
	}

	/** @param list<string> $functions @param list<string> $classes @param list<string> $skipped */
	private function renderSourceFile(string $profile, array $functions, array $classes, array $skipped): string
	{
		$lines = [];
		$lines[] = '// Generated shallow runtime symbol surface for STAN.';
		$lines[] = '// Profile: ' . $profile;
		$lines[] = '// This file is for front-end symbol extraction only.';
		if ($skipped !== []) {
			$lines[] = '// Skipped reserved or unsafe names: ' . implode(', ', $skipped);
		}
		$lines[] = '';
		foreach ($functions as $function) {
			$lines[] = $function;
		}
		if ($classes !== []) {
			$lines[] = '';
			foreach ($classes as $classBlock) {
				$lines[] = $classBlock;
				$lines[] = '';
			}
			array_pop($lines);
		}
		$lines[] = '';
		return implode(PHP_EOL, $lines);
	}

	private function writeTextFile(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
			throw new \RuntimeException('Failed to create directory: ' . $dir);
		}
		$existing = is_file($path) ? file_get_contents($path) : false;
		if (is_string($existing) && $existing === $contents) {
			return;
		}
		if (file_put_contents($path, $contents) === false) {
			throw new \RuntimeException('Failed to write runtime shallow source: ' . $path);
		}
	}

	private function renderParam(string $name, string $type, bool $strictShorthand): string
	{
		$native = $this->nativeTypeForSource($type);
		if ($native !== null) {
			return $native . ' $' . $name;
		}
		if ($strictShorthand) {
			return '$' . $name . ' ' . $type;
		}
		return '/** ' . $type . ' */ $' . $name;
	}

	/** @param array{name:string,type:string,has_default?:bool} $param */
	private function renderParamWithMetadata(array $param, bool $strictShorthand): string
	{
		$text = $this->renderParam((string) ($param['name'] ?? 'arg'), (string) ($param['type'] ?? 'mixed'), $strictShorthand);
		if ((bool) ($param['has_default'] ?? false)) {
			$defaultLiteral = $this->defaultLiteralForType((string) ($param['type'] ?? 'mixed'));
			if ($defaultLiteral !== null) {
				$text .= ' = ' . $defaultLiteral;
			}
		}
		return $text;
	}

	private function renderReturnSuffix(string $type, bool $strictShorthand): string
	{
		$native = $this->nativeTypeForSource($type);
		if ($native !== null || $strictShorthand) {
			$typeText = $native ?? $type;
			return ': ' . $typeText;
		}
		return ' /** ' . $type . ' */';
	}

	private function nativeTypeForSource(string $type): ?string
	{
		$trimmed = trim($type);
		$normalized = strtolower($trimmed);
		$scalar = match ($normalized) {
			'int', 'float', 'bool', 'string', 'mixed', 'void', 'array', 'callable' => $normalized,
			default => null,
		};
		if ($scalar !== null) {
			return $scalar;
		}
		if (preg_match('/^\??[A-Za-z_\\\\][A-Za-z0-9_\\\\]*$/', $trimmed) === 1) {
			return $trimmed;
		}
		return null;
	}

	private function defaultLiteralForType(string $type): ?string
	{
		return match (strtolower(trim($type))) {
			'int' => '0',
			'float' => '0.0',
			'bool' => 'false',
			'string' => '""',
			'mixed' => 'null',
			default => null,
		};
	}

	/** @return array{return:string,params:list<array{name:string,type:string,has_default?:bool}>} */
	private function resolveSignature(string $name, string $profile): array
	{
		$signatures = $this->signatureMap();
		$profileMap = $signatures[$profile] ?? [];
		if (isset($profileMap[$name])) {
			return $profileMap[$name];
		}
		$sharedMap = $signatures['shared'] ?? [];
		if (isset($sharedMap[$name])) {
			return $sharedMap[$name];
		}
		return [
			'return' => 'mixed',
			'params' => [],
		];
	}

	/** @return array<string, array<string, array{return:string,params:list<array{name:string,type:string,has_default?:bool}>}>> */
	private function signatureMap(): array
	{
		return [
			'shared' => [
				'to_hash' => ['return' => 'hash<mixed>', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'count' => ['return' => 'int', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'empty' => ['return' => 'bool', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'isset' => ['return' => 'bool', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'isset_eval' => ['return' => 'bool', 'params' => []],
				'coalesce_eval' => ['return' => 'mixed', 'params' => []],
				'ternary_eval' => ['return' => 'mixed', 'params' => []],
				'condition_truthy' => ['return' => 'bool', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'identical' => ['return' => 'bool', 'params' => [['name' => 'left', 'type' => 'mixed'], ['name' => 'right', 'type' => 'mixed']]],
				'not_identical' => ['return' => 'bool', 'params' => [['name' => 'left', 'type' => 'mixed'], ['name' => 'right', 'type' => 'mixed']]],
				'dbg' => ['return' => 'void', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'dbg_if' => ['return' => 'void', 'params' => [['name' => 'flag', 'type' => 'bool'], ['name' => 'value', 'type' => 'mixed']]],
				'__scpp_debug_dump' => ['return' => 'void', 'params' => [['name' => 'phase', 'type' => 'string'], ['name' => 'label', 'type' => 'string'], ['name' => 'value', 'type' => 'mixed']]],
				'__scpp_debug_exit' => ['return' => 'void', 'params' => []],
				'__scpp_debug_break' => ['return' => 'void', 'params' => []],
				'__scpp_debug_call_entry' => ['return' => 'void', 'params' => []],
				'dbg_set' => ['return' => 'void', 'params' => [['name' => 'flag', 'type' => 'int']]],
				'dbg_unset' => ['return' => 'void', 'params' => [['name' => 'flag', 'type' => 'int']]],
				'dbg_enabled' => ['return' => 'bool', 'params' => [['name' => 'flag', 'type' => 'int']]],
				'var_dump' => ['return' => 'void', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'echo_one' => ['return' => 'void', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'echo_eval' => ['return' => 'void', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'cli_argc' => ['return' => 'int', 'params' => []],
				'cli_argv' => ['return' => 'mixed', 'params' => []],
				'cli_args' => ['return' => 'mixed', 'params' => []],
				'getenv' => ['return' => 'mixed', 'params' => [['name' => 'name', 'type' => 'string']]],
				'shell_exec' => ['return' => 'mixed', 'params' => [['name' => 'command', 'type' => 'string']]],
				'microtime' => ['return' => 'mixed', 'params' => [['name' => 'as_float', 'type' => 'bool', 'has_default' => true]]],
				'layout_sizeof' => ['return' => 'int', 'params' => [['name' => 'type_name', 'type' => 'mixed']]],
				'layout_alignof' => ['return' => 'int', 'params' => [['name' => 'type_name', 'type' => 'mixed']]],
				'layout_offsetof' => ['return' => 'int', 'params' => [['name' => 'type_name', 'type' => 'mixed'], ['name' => 'field_name', 'type' => 'mixed']]],
				'layout_field_sizeof' => ['return' => 'int', 'params' => [['name' => 'type_name', 'type' => 'mixed'], ['name' => 'field_name', 'type' => 'mixed']]],
				'memory_get_usage' => ['return' => 'int', 'params' => [['name' => 'real_usage', 'type' => 'bool', 'has_default' => true]]],
				'memory_get_peak_usage' => ['return' => 'int', 'params' => [['name' => 'real_usage', 'type' => 'bool', 'has_default' => true]]],
				'substr' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int', 'has_default' => true]]],
				'substr_compare' => ['return' => 'int', 'params' => [['name' => 'main', 'type' => 'string'], ['name' => 'str', 'type' => 'string'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int', 'has_default' => true]]],
				'substr_replace' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'replace', 'type' => 'string'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int', 'has_default' => true]]],
				'str_pad' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'length', 'type' => 'int']]],
				'str_replace' => ['return' => 'string', 'params' => [['name' => 'search', 'type' => 'string'], ['name' => 'replace', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'explode' => ['return' => 'vector<string>', 'params' => [['name' => 'separator', 'type' => 'string'], ['name' => 'text', 'type' => 'string']]],
				'implode' => ['return' => 'string', 'params' => [['name' => 'separator', 'type' => 'string'], ['name' => 'parts', 'type' => 'mixed']]],
				'hex2bin' => ['return' => 'result_or_false<string>', 'params' => [['name' => 'hex', 'type' => 'string']]],
				'bin2hex' => ['return' => 'string', 'params' => [['name' => 'bytes', 'type' => 'string']]],
				'number_format' => ['return' => 'string', 'params' => [['name' => 'value', 'type' => 'float']]],
				'hash_string' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'stable_hash_string_u64' => ['return' => 'uint64', 'params' => [['name' => 'text', 'type' => 'string']]],
				'vector_reserve' => ['return' => 'void', 'params' => [['name' => 'values', 'type' => 'mixed'], ['name' => 'capacity', 'type' => 'int']]],
				'vector_capacity' => ['return' => 'int', 'params' => [['name' => 'values', 'type' => 'mixed']]],
				'vector_resize' => ['return' => 'void', 'params' => [['name' => 'values', 'type' => 'mixed'], ['name' => 'count', 'type' => 'int'], ['name' => 'default_value', 'type' => 'mixed']]],
				'vector_filled' => ['return' => 'mixed', 'params' => [['name' => 'count', 'type' => 'int'], ['name' => 'default_value', 'type' => 'mixed']]],
				'vector_clear' => ['return' => 'void', 'params' => [['name' => 'values', 'type' => 'mixed']]],
				'vector_clear_keep_capacity' => ['return' => 'void', 'params' => [['name' => 'values', 'type' => 'mixed']]],
				'vector_compact' => ['return' => 'void', 'params' => [['name' => 'values', 'type' => 'mixed'], ['name' => 'capacity', 'type' => 'int', 'has_default' => true]]],
				'source_buffer_take' => ['return' => 'source_buffer', 'params' => [['name' => 'text', 'type' => 'string']]],
				'source_buffer_release' => ['return' => 'string', 'params' => [['name' => 'buffer', 'type' => 'source_buffer']]],
				'source_buffer_byte_len' => ['return' => 'uint32', 'params' => [['name' => 'buffer', 'type' => 'source_buffer']]],
					'source_buffer_byte_at' => ['return' => 'byte', 'params' => [['name' => 'buffer', 'type' => 'source_buffer'], ['name' => 'offset', 'type' => 'int']]],
					'source_buffer_span' => ['return' => 'byte_span', 'params' => [['name' => 'buffer', 'type' => 'source_buffer'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int']]],
					'source_buffer_slice' => ['return' => 'string', 'params' => [['name' => 'buffer', 'type' => 'source_buffer'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int']]],
					'source_line_index_build' => ['return' => 'source_line_index', 'params' => [['name' => 'buffer', 'type' => 'source_buffer']]],
					'source_line_index_line_count' => ['return' => 'uint32', 'params' => [['name' => 'index', 'type' => 'source_line_index']]],
					'source_line_index_offset_to_location' => ['return' => 'source_location', 'params' => [['name' => 'index', 'type' => 'source_line_index'], ['name' => 'offset', 'type' => 'int']]],
					'source_line_index_line_column_to_offset' => ['return' => 'uint32', 'params' => [['name' => 'index', 'type' => 'source_line_index'], ['name' => 'line', 'type' => 'int'], ['name' => 'column', 'type' => 'int']]],
					'source_location_offset' => ['return' => 'uint32', 'params' => [['name' => 'location', 'type' => 'source_location']]],
					'source_location_line' => ['return' => 'uint32', 'params' => [['name' => 'location', 'type' => 'source_location']]],
					'source_location_column' => ['return' => 'uint32', 'params' => [['name' => 'location', 'type' => 'source_location']]],
					'byte_span_len' => ['return' => 'uint32', 'params' => [['name' => 'span', 'type' => 'byte_span']]],
				'byte_span_at' => ['return' => 'byte', 'params' => [['name' => 'span', 'type' => 'byte_span'], ['name' => 'offset', 'type' => 'int']]],
				'byte_span_to_string' => ['return' => 'string', 'params' => [['name' => 'span', 'type' => 'byte_span']]],
				'hash_bytes' => ['return' => 'string', 'params' => [['name' => 'span', 'type' => 'byte_span']]],
				'stable_hash_bytes_u64' => ['return' => 'uint64', 'params' => [['name' => 'span', 'type' => 'byte_span']]],
				'jss_tokenize' => ['return' => 'token_buffer', 'params' => [['name' => 'source', 'type' => 'string']]],
				'jss_tokenize_buffer' => ['return' => 'token_buffer', 'params' => [['name' => 'source', 'type' => 'string']]],
				'phs_tokenize' => ['return' => 'token_buffer', 'params' => [['name' => 'source', 'type' => 'string']]],
				'phs_tokenize_buffer' => ['return' => 'token_buffer', 'params' => [['name' => 'source', 'type' => 'string']]],
				'token_buffer_count' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer']]],
				'token_buffer_kind_id' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer'], ['name' => 'index', 'type' => 'int']]],
				'token_buffer_length' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer'], ['name' => 'index', 'type' => 'int']]],
				'token_buffer_start_offset' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer'], ['name' => 'index', 'type' => 'int']]],
				'token_buffer_line' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer'], ['name' => 'index', 'type' => 'int']]],
				'token_buffer_column' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer'], ['name' => 'index', 'type' => 'int']]],
				'token_buffer_flags' => ['return' => 'int', 'params' => [['name' => 'buffer', 'type' => 'token_buffer'], ['name' => 'index', 'type' => 'int']]],
				'token_buffer_to_mixed' => ['return' => 'mixed', 'params' => [['name' => 'buffer', 'type' => 'token_buffer']]],
				'strlen' => ['return' => 'int', 'params' => [['name' => 'text', 'type' => 'string']]],
				'string_byte_len' => ['return' => 'int', 'params' => [['name' => 'text', 'type' => 'string']]],
				'string_byte_at' => ['return' => 'int', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'offset', 'type' => 'int']]],
				'string_byte_find' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'haystack', 'type' => 'string'], ['name' => 'needle', 'type' => 'string'], ['name' => 'offset', 'type' => 'int', 'has_default' => true]]],
				'string_byte_slice' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int']]],
				'string_byte_slice_equals' => ['return' => 'bool', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'offset', 'type' => 'int'], ['name' => 'length', 'type' => 'int'], ['name' => 'literal', 'type' => 'string']]],
				'string_utf8_codepoint_count' => ['return' => 'int', 'params' => [['name' => 'text', 'type' => 'string']]],
				'string_utf8_codepoint_at' => ['return' => 'int', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'index', 'type' => 'int']]],
				'string_utf8_slice_codepoints' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'start', 'type' => 'int'], ['name' => 'length', 'type' => 'int']]],
				'string_grapheme_count' => ['return' => 'int', 'params' => [['name' => 'text', 'type' => 'string']]],
				'string_grapheme_slice' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'start', 'type' => 'int'], ['name' => 'length', 'type' => 'int']]],
				'string_parts_builder_create' => ['return' => 'string_parts_builder', 'params' => []],
				'string_parts_builder_reserve' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder'], ['name' => 'capacity', 'type' => 'int']]],
				'string_parts_builder_count' => ['return' => 'int', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder']]],
				'string_parts_builder_capacity' => ['return' => 'int', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder']]],
				'string_parts_builder_byte_len' => ['return' => 'int', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder']]],
				'string_parts_builder_append_string' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder'], ['name' => 'value', 'type' => 'string']]],
				'string_parts_builder_append_int' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder'], ['name' => 'value', 'type' => 'int']]],
				'string_parts_builder_append_bool' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder'], ['name' => 'value', 'type' => 'bool']]],
				'string_parts_builder_to_string' => ['return' => 'string', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder']]],
				'string_parts_builder_clear' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'string_parts_builder']]],
				'text_builder_create' => ['return' => 'text_builder', 'params' => []],
				'text_builder_reserve_bytes' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'text_builder'], ['name' => 'capacity', 'type' => 'int']]],
				'text_builder_capacity_bytes' => ['return' => 'int', 'params' => [['name' => 'builder', 'type' => 'text_builder']]],
				'text_builder_byte_len' => ['return' => 'int', 'params' => [['name' => 'builder', 'type' => 'text_builder']]],
				'text_builder_append_string' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'text_builder'], ['name' => 'value', 'type' => 'string']]],
				'text_builder_append_int' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'text_builder'], ['name' => 'value', 'type' => 'int']]],
				'text_builder_append_bool' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'text_builder'], ['name' => 'value', 'type' => 'bool']]],
				'text_builder_append_byte_span' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'text_builder'], ['name' => 'span', 'type' => 'byte_span']]],
				'text_builder_to_string' => ['return' => 'string', 'params' => [['name' => 'builder', 'type' => 'text_builder']]],
				'text_builder_take_string' => ['return' => 'string', 'params' => [['name' => 'builder', 'type' => 'text_builder']]],
				'text_builder_clear' => ['return' => 'void', 'params' => [['name' => 'builder', 'type' => 'text_builder']]],
				'strpos' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'haystack', 'type' => 'string'], ['name' => 'needle', 'type' => 'string']]],
				'strrpos' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'haystack', 'type' => 'string'], ['name' => 'needle', 'type' => 'string']]],
				'strtolower' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'strtoupper' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'lcfirst' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'ucfirst' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'str_starts_with' => ['return' => 'bool', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'prefix', 'type' => 'string']]],
				'str_ends_with' => ['return' => 'bool', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'suffix', 'type' => 'string']]],
				'str_contains' => ['return' => 'bool', 'params' => [['name' => 'text', 'type' => 'string'], ['name' => 'needle', 'type' => 'string']]],
				'trim' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'ltrim' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'rtrim' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'json_decode' => ['return' => 'dynamic', 'params' => [['name' => 'json', 'type' => 'string']]],
				'json_encode' => ['return' => 'string', 'params' => [['name' => 'value', 'type' => 'mixed']]],
				'dt_now' => ['return' => 'int', 'params' => []],
				'dt_now_ms' => ['return' => 'int', 'params' => []],
				'dt_monotonic_ms' => ['return' => 'int', 'params' => []],
				'dt_monotonic_us' => ['return' => 'uint64', 'params' => []],
				'dt_monotonic_ns' => ['return' => 'uint64', 'params' => []],
				'dt_sleep_ms' => ['return' => 'void', 'params' => [['name' => 'millis', 'type' => 'int']]],
				'dt_format_iso_utc' => ['return' => 'string', 'params' => [['name' => 'stamp', 'type' => 'int']]],
				'dt_parse_iso_utc' => ['return' => 'result<int>', 'params' => [['name' => 'text', 'type' => 'string']]],
				'dt_format' => ['return' => 'string', 'params' => [['name' => 'stamp', 'type' => 'int'], ['name' => 'format', 'type' => 'string']]],
				'dt_format_now' => ['return' => 'string', 'params' => [['name' => 'format', 'type' => 'string']]],
				'dt_parse' => ['return' => 'result<int>', 'params' => [['name' => 'text', 'type' => 'string']]],
				'take' => ['return' => 'bool', 'params' => [['name' => 'out', 'type' => 'mixed'], ['name' => 'source', 'type' => 'mixed']]],
				'curl_strerror' => ['return' => 'string', 'params' => [['name' => 'code', 'type' => 'int']]],
			],
			'strict' => [
				'fs_is_file' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_is_dir' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_is_link' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_exists' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_get' => ['return' => 'result<string>', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_put' => ['return' => 'result<int>', 'params' => [['name' => 'path', 'type' => 'string'], ['name' => 'data', 'type' => 'string']]],
				'fs_mkdir' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_scan' => ['return' => 'result<vector<string>>', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_size' => ['return' => 'result<int>', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_mtime' => ['return' => 'result<int>', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_touch' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_rmdir' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_remove' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_copy' => ['return' => 'bool', 'params' => [['name' => 'from', 'type' => 'string'], ['name' => 'to', 'type' => 'string']]],
				'fs_rename' => ['return' => 'bool', 'params' => [['name' => 'from', 'type' => 'string'], ['name' => 'to', 'type' => 'string']]],
				'fs_realpath' => ['return' => 'result<string>', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_dirname' => ['return' => 'string', 'params' => [['name' => 'path', 'type' => 'string']]],
				'fs_basename' => ['return' => 'string', 'params' => [['name' => 'path', 'type' => 'string']]],
				'io_open' => ['return' => 'result_or_false<resource_handle>', 'params' => [['name' => 'path', 'type' => 'string'], ['name' => 'mode', 'type' => 'string']]],
				'io_seek' => ['return' => 'nullable<int>', 'params' => [['name' => 'fh', 'type' => 'resource_handle'], ['name' => 'offset', 'type' => 'int']]],
				'io_tell' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'fh', 'type' => 'resource_handle']]],
				'io_read_line' => ['return' => 'result_or_false<string>', 'params' => [['name' => 'fh', 'type' => 'resource_handle']]],
				'io_read' => ['return' => 'result_or_false<string>', 'params' => [['name' => 'fh', 'type' => 'resource_handle'], ['name' => 'length', 'type' => 'int']]],
				'io_write' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'fh', 'type' => 'resource_handle'], ['name' => 'data', 'type' => 'string']]],
				'io_rewind' => ['return' => 'bool', 'params' => [['name' => 'fh', 'type' => 'resource_handle']]],
				'io_flush' => ['return' => 'bool', 'params' => [['name' => 'fh', 'type' => 'resource_handle']]],
				'io_eof' => ['return' => 'bool', 'params' => [['name' => 'fh', 'type' => 'resource_handle']]],
				'io_close' => ['return' => 'bool', 'params' => [['name' => 'fh', 'type' => 'resource_handle']]],
				'regex_jit_available' => ['return' => 'bool', 'params' => []],
				'regex_filter' => ['return' => 'vector<string>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'input', 'type' => 'vector<string>']]],
				'regex_grep' => ['return' => 'vector<string>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'input', 'type' => 'vector<string>']]],
				'regex_match' => ['return' => 'result_or_false<vector<string>>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_match_all_named' => ['return' => 'result_or_false<hash<vector<string>>>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_match_all' => ['return' => 'result_or_false<vector<vector<string>>>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_match_all_pattern_order' => ['return' => 'result_or_false<vector<vector<string>>>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_match_named' => ['return' => 'result_or_false<hash<string>>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_quote' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'regex_replace' => ['return' => 'result_or_false<string>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'replacement', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_replace_callback' => ['return' => 'result_or_false<string>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'regex_replace_callback_array' => ['return' => 'result_or_false<string>', 'params' => [['name' => 'subject', 'type' => 'string']]],
				'regex_split' => ['return' => 'result_or_false<vector<string>>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'curl_init' => ['return' => 'result<curl_handle>', 'params' => []],
				'curl_setopt' => ['return' => 'result<bool>', 'params' => [['name' => 'handle', 'type' => 'curl_handle'], ['name' => 'option', 'type' => 'int'], ['name' => 'value', 'type' => 'mixed']]],
				'curl_exec' => ['return' => 'result<curl_response>', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_getinfo' => ['return' => 'mixed', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_errno' => ['return' => 'int', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_error' => ['return' => 'string', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_reset' => ['return' => 'void', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_close' => ['return' => 'result<bool>', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'task_run' => ['return' => 'mixed', 'params' => [
					['name' => 'items', 'type' => 'mixed'],
					['name' => 'workers', 'type' => 'int'],
					['name' => 'exec', 'type' => 'mixed'],
					['name' => 'index', 'type' => 'mixed', 'has_default' => true],
					['name' => 'result', 'type' => 'mixed', 'has_default' => true],
					['name' => 'error', 'type' => 'mixed', 'has_default' => true],
					['name' => 'timeout_ms', 'type' => 'int', 'has_default' => true],
				]],
				'task_run_publish' => ['return' => 'int', 'params' => [
					['name' => 'items', 'type' => 'mixed'],
					['name' => 'workers', 'type' => 'int'],
					['name' => 'work', 'type' => 'mixed'],
					['name' => 'publish', 'type' => 'mixed'],
					['name' => 'error', 'type' => 'mixed', 'has_default' => true],
					['name' => 'timeout_ms', 'type' => 'int', 'has_default' => true],
				]],
				'task_start' => ['return' => 'task_batch', 'params' => [
					['name' => 'items', 'type' => 'mixed'],
					['name' => 'workers', 'type' => 'int'],
					['name' => 'exec', 'type' => 'mixed'],
					['name' => 'index', 'type' => 'mixed', 'has_default' => true],
					['name' => 'result', 'type' => 'mixed', 'has_default' => true],
					['name' => 'error', 'type' => 'mixed', 'has_default' => true],
					['name' => 'timeout_ms', 'type' => 'int', 'has_default' => true],
				]],
				'task_join' => ['return' => 'mixed', 'params' => [['name' => 'batch', 'type' => 'task_batch']]],
				'task_cancel' => ['return' => 'void', 'params' => [['name' => 'batch', 'type' => 'task_batch']]],
				'task_done' => ['return' => 'bool', 'params' => [['name' => 'batch', 'type' => 'task_batch']]],
				'task_status' => ['return' => 'string', 'params' => [['name' => 'batch', 'type' => 'task_batch']]],
				'task_progress' => ['return' => 'task_progress_info', 'params' => [['name' => 'batch', 'type' => 'task_batch']]],
				'task_set_status' => ['return' => 'void', 'params' => [['name' => 'ctx', 'type' => 'task_context'], ['name' => 'status', 'type' => 'string']]],
				'task_set_worker_pool_size' => ['return' => 'void', 'params' => [['name' => 'workers', 'type' => 'int']]],
				'ui_app_create' => ['return' => 'result<ui_app>', 'params' => []],
				'ui_window_create' => ['return' => 'result<ui_window>', 'params' => [
					['name' => 'app', 'type' => 'ui_app'],
					['name' => 'title', 'type' => 'string'],
					['name' => 'width', 'type' => 'int'],
					['name' => 'height', 'type' => 'int'],
				]],
				'ui_window_show' => ['return' => 'result<bool>', 'params' => [['name' => 'window', 'type' => 'ui_window']]],
				'ui_window_close' => ['return' => 'result<bool>', 'params' => [['name' => 'window', 'type' => 'ui_window']]],
				'ui_app_poll' => ['return' => 'bool', 'params' => [['name' => 'app', 'type' => 'ui_app']]],
					'ui_app_next_event' => ['return' => 'ui_event', 'params' => [['name' => 'app', 'type' => 'ui_app']]],
					'ui_app_exit' => ['return' => 'void', 'params' => [['name' => 'app', 'type' => 'ui_app']]],
					'ui_event_type' => ['return' => 'string', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
					'ui_event_window' => ['return' => 'ui_window', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
					'ui_event_text' => ['return' => 'string', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
					'ui_event_webview' => ['return' => 'webview', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
					'ui_event_message' => ['return' => 'string', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
					'ui_event_url' => ['return' => 'string', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
					'webview_create' => ['return' => 'result<webview>', 'params' => [['name' => 'window', 'type' => 'ui_window']]],
				'webview_load_url' => ['return' => 'result<bool>', 'params' => [['name' => 'view', 'type' => 'webview'], ['name' => 'url', 'type' => 'string']]],
				'webview_load_html' => ['return' => 'result<bool>', 'params' => [['name' => 'view', 'type' => 'webview'], ['name' => 'html', 'type' => 'string']]],
				'webview_load_app' => ['return' => 'result<bool>', 'params' => [['name' => 'view', 'type' => 'webview'], ['name' => 'folder', 'type' => 'string']]],
				'webview_eval' => ['return' => 'result<bool>', 'params' => [['name' => 'view', 'type' => 'webview'], ['name' => 'script', 'type' => 'string']]],
				'webview_reply_ok' => ['return' => 'result<bool>', 'params' => [['name' => 'view', 'type' => 'webview'], ['name' => 'id', 'type' => 'int'], ['name' => 'value_json', 'type' => 'string']]],
				'webview_reply_error' => ['return' => 'result<bool>', 'params' => [['name' => 'view', 'type' => 'webview'], ['name' => 'id', 'type' => 'int'], ['name' => 'code', 'type' => 'string'], ['name' => 'message', 'type' => 'string']]],
				'webview_message_id' => ['return' => 'int', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
				'webview_message_command' => ['return' => 'string', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
				'webview_message_payload_json' => ['return' => 'string', 'params' => [['name' => 'event', 'type' => 'ui_event']]],
				'webview_close' => ['return' => 'void', 'params' => [['name' => 'view', 'type' => 'webview']]],
			],
			'legacy' => [
				'fopen' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string'], ['name' => 'mode', 'type' => 'string']]],
				'fseek' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed'], ['name' => 'offset', 'type' => 'int']]],
				'ftell' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed']]],
				'fgets' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed']]],
				'fread' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed'], ['name' => 'length', 'type' => 'int']]],
				'fwrite' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed'], ['name' => 'data', 'type' => 'string']]],
				'fputs' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed'], ['name' => 'data', 'type' => 'string']]],
				'rewind' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed']]],
				'fflush' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed']]],
				'feof' => ['return' => 'bool', 'params' => [['name' => 'fh', 'type' => 'mixed']]],
				'fclose' => ['return' => 'mixed', 'params' => [['name' => 'fh', 'type' => 'mixed']]],
				'is_file' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'is_dir' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'is_link' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'file_exists' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'file_get_contents' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string']]],
				'file_put_contents' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string'], ['name' => 'data', 'type' => 'string']]],
				'mkdir' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'scandir' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string']]],
				'filesize' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string']]],
				'filemtime' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string']]],
				'touch' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'rmdir' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'unlink' => ['return' => 'bool', 'params' => [['name' => 'path', 'type' => 'string']]],
				'copy' => ['return' => 'bool', 'params' => [['name' => 'from', 'type' => 'string'], ['name' => 'to', 'type' => 'string']]],
				'rename' => ['return' => 'bool', 'params' => [['name' => 'from', 'type' => 'string'], ['name' => 'to', 'type' => 'string']]],
				'realpath' => ['return' => 'mixed', 'params' => [['name' => 'path', 'type' => 'string']]],
				'dirname' => ['return' => 'string', 'params' => [['name' => 'path', 'type' => 'string']]],
				'basename' => ['return' => 'string', 'params' => [['name' => 'path', 'type' => 'string']]],
				'time' => ['return' => 'int', 'params' => []],
				'date' => ['return' => 'string', 'params' => [['name' => 'format', 'type' => 'string']]],
				'strtotime' => ['return' => 'mixed', 'params' => [['name' => 'text', 'type' => 'string']]],
				'preg_jit_available' => ['return' => 'bool', 'params' => []],
				'preg_filter' => ['return' => 'mixed', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'replacement', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'preg_grep' => ['return' => 'mixed', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'input', 'type' => 'mixed']]],
				'preg_match' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'preg_match_all' => ['return' => 'result_or_false<int>', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'preg_quote' => ['return' => 'string', 'params' => [['name' => 'text', 'type' => 'string']]],
				'preg_replace' => ['return' => 'mixed', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'replacement', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'preg_replace_callback' => ['return' => 'mixed', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'preg_replace_callback_array' => ['return' => 'mixed', 'params' => [['name' => 'subject', 'type' => 'string']]],
				'preg_split' => ['return' => 'mixed', 'params' => [['name' => 'pattern', 'type' => 'string'], ['name' => 'subject', 'type' => 'string']]],
				'curl_init' => ['return' => 'result_or_false<curl_handle>', 'params' => []],
				'curl_setopt' => ['return' => 'result_or_false<bool>', 'params' => [['name' => 'handle', 'type' => 'curl_handle'], ['name' => 'option', 'type' => 'int'], ['name' => 'value', 'type' => 'mixed']]],
				'curl_exec' => ['return' => 'mixed', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_getinfo' => ['return' => 'mixed', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_errno' => ['return' => 'int', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_error' => ['return' => 'string', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_reset' => ['return' => 'void', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
				'curl_close' => ['return' => 'result_or_false<bool>', 'params' => [['name' => 'handle', 'type' => 'curl_handle']]],
			],
		];
	}

	/** @return list<string> */
	private function alwaysIncludedFunctionNames(): array
	{
		return [
			'to_hash',
		];
	}

	/** @return list<string> */
	private function renderRuntimeClasses(string $profile): array
	{
		$isStrict = $profile === 'strict';
		$blocks = [];
		$scppClasses = [];
		if ($isStrict) {
			$scppClasses = [
				$this->renderClassStub('token_buffer', [], $isStrict),
				$this->renderClassStub('string_parts_builder', [], $isStrict),
				$this->renderClassStub('text_builder', [], $isStrict),
				$this->renderClassStub('source_line_index', [], $isStrict),
				$this->renderClassStub('source_location', [], $isStrict),
				$this->renderClassStub('task_batch', [], $isStrict),
				$this->renderClassStub('task_context', [], $isStrict),
				$this->renderClassStub('task_progress_info', [
					['kind' => 'method', 'name' => 'total', 'return' => 'int', 'params' => []],
					['kind' => 'method', 'name' => 'completed', 'return' => 'int', 'params' => []],
					['kind' => 'method', 'name' => 'queued', 'return' => 'int', 'params' => []],
					['kind' => 'method', 'name' => 'active', 'return' => 'int', 'params' => []],
					['kind' => 'method', 'name' => 'errors', 'return' => 'int', 'params' => []],
					['kind' => 'method', 'name' => 'stop_requested', 'return' => 'bool', 'params' => []],
					['kind' => 'method', 'name' => 'status', 'return' => 'string', 'params' => []],
				], $isStrict),
				$this->renderClassStub('task_error', [
					['kind' => 'property', 'name' => 'message', 'type' => 'string'],
					['kind' => 'property', 'name' => 'kind', 'type' => 'string'],
					['kind' => 'property', 'name' => 'key', 'type' => 'mixed'],
					['kind' => 'property', 'name' => 'worker_id', 'type' => 'int'],
					['kind' => 'property', 'name' => 'timeout', 'type' => 'bool'],
					['kind' => 'property', 'name' => 'source_file', 'type' => 'string'],
					['kind' => 'property', 'name' => 'source_line', 'type' => 'int'],
				], $isStrict),
				$this->renderClassStub('ui_app', [], $isStrict),
				$this->renderClassStub('ui_window', [
					['kind' => 'property', 'name' => 'title', 'type' => 'string'],
					['kind' => 'property', 'name' => 'width', 'type' => 'int'],
					['kind' => 'property', 'name' => 'height', 'type' => 'int'],
					['kind' => 'property', 'name' => 'visible', 'type' => 'bool'],
					['kind' => 'property', 'name' => 'closed', 'type' => 'bool'],
				], $isStrict),
				$this->renderClassStub('ui_event', [
					['kind' => 'property', 'name' => 'type', 'type' => 'string'],
					['kind' => 'property', 'name' => 'window_handle', 'type' => 'ui_window'],
					['kind' => 'property', 'name' => 'webview_handle', 'type' => 'webview'],
					['kind' => 'property', 'name' => 'message', 'type' => 'string'],
					['kind' => 'property', 'name' => 'url', 'type' => 'string'],
				], $isStrict),
				$this->renderClassStub('webview', [], $isStrict),
			];
		}
		$blocks[] = $this->renderNamespaceBlock('scpp', [
			...$scppClasses,
			$this->renderClassStub('mysqli_result', [
				['kind' => 'method', 'name' => 'fetch_assoc', 'return' => 'dynamic', 'params' => []],
			], $isStrict),
			$this->renderClassStub('mysqli', [
				['kind' => 'property', 'name' => 'connect_errno', 'type' => 'int'],
				['kind' => 'property', 'name' => 'connect_error', 'type' => 'string'],
				['kind' => 'property', 'name' => 'errno_code', 'type' => 'int'],
				['kind' => 'property', 'name' => 'error', 'type' => 'string'],
				['kind' => 'method', 'name' => 'query', 'return' => 'result_or_bool<scpp\\mysqli_result>', 'params' => [['name' => 'sql', 'type' => 'string']]],
				['kind' => 'method', 'name' => 'close', 'return' => 'void', 'params' => []],
				['kind' => 'method', 'name' => 'set_charset', 'return' => 'bool', 'params' => [['name' => 'charset', 'type' => 'string']]],
			], $isStrict),
		]);
		return $blocks;
	}

	/** @param list<string> $members */
	private function renderNamespaceBlock(string $namespace, array $members): string
	{
		$lines = [];
		$lines[] = 'namespace ' . $namespace . ';';
		$lines[] = '';
		foreach ($members as $member) {
			$memberLines = explode(PHP_EOL, $member);
			foreach ($memberLines as $memberLine) {
				$lines[] = $memberLine;
			}
			$lines[] = '';
		}
		array_pop($lines);
		return implode(PHP_EOL, $lines);
	}

	/** @param list<array<string,mixed>> $members */
	private function renderClassStub(string $name, array $members, bool $isStrict): string
	{
		$lines = [];
		$lines[] = 'class ' . $name;
		$lines[] = '{';
		foreach ($members as $member) {
			$kind = (string) ($member['kind'] ?? '');
			if ($kind === 'property') {
				$type = (string) ($member['type'] ?? 'mixed');
				$native = $this->nativeTypeForSource($type);
				$typeText = $native ?? $type;
				if ($isStrict || $native !== null) {
					$lines[] = "\tpublic " . $typeText . ' $' . $member['name'] . ';';
				} else {
					$lines[] = "\t/** " . $typeText . ' */ public $' . $member['name'] . ';';
				}
				continue;
			}
			if ($kind === 'method') {
				$params = [];
				foreach (($member['params'] ?? []) as $param) {
					if (!is_array($param)) {
						continue;
					}
					$params[] = $this->renderParam((string) ($param['name'] ?? 'arg'), (string) ($param['type'] ?? 'mixed'), $isStrict);
				}
				$returnSuffix = $this->renderReturnSuffix((string) ($member['return'] ?? 'mixed'), $isStrict);
				$lines[] = "\tpublic function " . $member['name'] . '(' . implode(', ', $params) . ')' . $returnSuffix . ' {}';
			}
		}
		$lines[] = '}';
		return implode(PHP_EOL, $lines);
	}
}
